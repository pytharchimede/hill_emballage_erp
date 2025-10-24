"use strict";
(function () {
  const BASE = document.querySelector('meta[name="base-url"]')?.content || "";
  const modeSel = document.getElementById("mode_vente");
  const section = document.getElementById("delivery_section");
  const addrInput = document.getElementById("delivery_adresse");
  const sugg = document.getElementById("delivery_suggestions");
  const latEl = document.getElementById("delivery_latitude");
  const lonEl = document.getElementById("delivery_longitude");
  const locateBtn = document.getElementById("delivery_locate_btn");
  const mapId = "delivery_map";

  function showSection(show) {
    if (section) section.style.display = show ? "block" : "none";
  }

  function debounce(fn, d) {
    let t;
    return function (...a) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, a), d);
    };
  }

  let map = null,
    marker = null;
  function ensureMap() {
    const mapEl = document.getElementById(mapId);
    if (!mapEl || !window.L) return;
    if (!map) {
      const la = parseFloat(latEl?.value || "");
      const lo = parseFloat(lonEl?.value || "");
      const center =
        !isNaN(la) && !isNaN(lo) ? [la, lo] : [5.345317, -4.024429];
      map = L.map(mapId).setView(center, !isNaN(la) && !isNaN(lo) ? 14 : 12);
      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "&copy; OpenStreetMap",
      }).addTo(map);
      marker = L.marker(center, { draggable: true }).addTo(map);
      marker.on("dragend", () => {
        const p = marker.getLatLng();
        if (latEl) latEl.value = p.lat.toFixed(6);
        if (lonEl) lonEl.value = p.lng.toFixed(6);
      });
      setTimeout(() => map.invalidateSize(), 100);
    } else {
      setTimeout(() => map.invalidateSize(), 100);
    }
  }

  function clearSugg() {
    if (sugg) {
      sugg.innerHTML = "";
      sugg.style.display = "none";
    }
  }
  function showItems(items) {
    if (!sugg) return;
    sugg.innerHTML = "";
    items.forEach((it) => {
      const a = document.createElement("a");
      a.href = "#";
      a.className = "list-group-item list-group-item-action";
      a.textContent = it.display_name;
      a.addEventListener("click", (ev) => {
        ev.preventDefault();
        if (addrInput) addrInput.value = it.display_name;
        const la = parseFloat(it.lat),
          lo = parseFloat(it.lon);
        if (!isNaN(la) && !isNaN(lo)) {
          if (latEl) latEl.value = la.toFixed(6);
          if (lonEl) lonEl.value = lo.toFixed(6);
          ensureMap();
          if (marker && map) {
            marker.setLatLng([la, lo]);
            map.setView([la, lo], 15);
          }
        }
        clearSugg();
      });
      sugg.appendChild(a);
    });
    sugg.style.display = items.length ? "block" : "none";
  }

  const doSearch = debounce(async function () {
    if (!addrInput) return;
    const q = addrInput.value.trim();
    if (q.length < 3) {
      clearSugg();
      return;
    }
    try {
      const res = await fetch(
        `${BASE}/app/ajax/geocode.php?q=` + encodeURIComponent(q),
        { headers: { Accept: "application/json" } }
      );
      if (!res.ok) {
        clearSugg();
        return;
      }
      const data = await res.json();
      showItems(data);
      if (Array.isArray(data) && data.length) {
        const first = data[0];
        const la = parseFloat(first.lat),
          lo = parseFloat(first.lon);
        if (!isNaN(la) && !isNaN(lo)) {
          if (latEl) latEl.value = la.toFixed(6);
          if (lonEl) lonEl.value = lo.toFixed(6);
          ensureMap();
          if (marker && map) {
            marker.setLatLng([la, lo]);
            map.setView([la, lo], 15);
          }
        }
      }
    } catch {
      clearSugg();
    }
  }, 350);

  if (addrInput) {
    addrInput.addEventListener("input", (e) => {
      const val = addrInput.value.trim();
      if (!val) {
        if (latEl) latEl.value = "";
        if (lonEl) lonEl.value = "";
        clearSugg();
        return;
      }
      doSearch();
    });
    addrInput.addEventListener("keydown", (ev) => {
      if (ev.key === "Enter") {
        const first = sugg?.querySelector(".list-group-item");
        if (first) {
          ev.preventDefault();
          first.click();
        }
      }
    });
    addrInput.addEventListener("blur", () => setTimeout(clearSugg, 200));
  }

  if (locateBtn) {
    locateBtn.addEventListener("click", () => {
      if (!navigator.geolocation) return;
      navigator.geolocation.getCurrentPosition((pos) => {
        const { latitude, longitude } = pos.coords;
        if (latEl) latEl.value = latitude.toFixed(6);
        if (lonEl) lonEl.value = longitude.toFixed(6);
        ensureMap();
        if (marker && map) {
          marker.setLatLng([latitude, longitude]);
          map.setView([latitude, longitude], 15);
        }
      });
    });
  }

  if (modeSel) {
    function onMode() {
      const isLiv = modeSel.value === "livraison";
      showSection(isLiv);
      if (isLiv) {
        ensureMap();
        if (addrInput && addrInput.value.trim().length >= 3) {
          doSearch();
        }
      }
    }
    modeSel.addEventListener("change", onMode);
    onMode();
  }

  // Client-side validation at submit: require address + coords when in delivery mode
  const form = addrInput ? addrInput.closest("form") : null;
  if (form && modeSel) {
    form.addEventListener("submit", (ev) => {
      if (modeSel.value === "livraison") {
        const addr = addrInput?.value.trim() || "";
        const la = parseFloat(latEl?.value || "");
        const lo = parseFloat(lonEl?.value || "");
        if (!addr || isNaN(la) || isNaN(lo)) {
          ev.preventDefault();
          alert(
            "Pour une livraison, veuillez saisir une adresse et valider la position sur la carte."
          );
          try {
            addrInput?.focus();
          } catch {}
        }
      }
    });
  }
})();
