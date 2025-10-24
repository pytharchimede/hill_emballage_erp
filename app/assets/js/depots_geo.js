"use strict";

(function () {
  const DEFAULT_CENTER = [5.345317, -4.024429]; // Abidjan

  function debounce(fn, delay) {
    let t;
    return function (...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), delay);
    };
  }

  function setupMap(modalId, mapId, latId, lonId) {
    const modalEl = document.getElementById(modalId);
    const mapEl = document.getElementById(mapId);
    const latEl = document.getElementById(latId);
    const lonEl = document.getElementById(lonId);
    if (!modalEl || !mapEl || !latEl || !lonEl || !window.L) return null;

    let map, marker;
    function init() {
      if (map) {
        setTimeout(() => map.invalidateSize(), 50);
        return;
      }
      const lat = parseFloat(latEl.value);
      const lon = parseFloat(lonEl.value);
      const center = !isNaN(lat) && !isNaN(lon) ? [lat, lon] : DEFAULT_CENTER;
      map = L.map(mapId).setView(center, !isNaN(lat) && !isNaN(lon) ? 14 : 12);
      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "&copy; OpenStreetMap",
      }).addTo(map);
      marker = L.marker(center, { draggable: true }).addTo(map);
      marker.on("dragend", () => {
        const p = marker.getLatLng();
        latEl.value = p.lat.toFixed(6);
        lonEl.value = p.lng.toFixed(6);
      });
    }
    modalEl.addEventListener("shown.bs.modal", init);
    if (modalEl.classList.contains("show")) setTimeout(init, 50);

    function syncFromInputs() {
      if (!map || !marker) return;
      const la = parseFloat(latEl.value);
      const lo = parseFloat(lonEl.value);
      if (isNaN(la) || isNaN(lo)) return;
      const p = [la, lo];
      marker.setLatLng(p);
      map.setView(p, 14);
    }
    latEl.addEventListener("change", syncFromInputs);
    lonEl.addEventListener("change", syncFromInputs);

    return {
      get map() {
        return map;
      },
      get marker() {
        return marker;
      },
      init,
    };
  }

  function setupAutocomplete(inputId, listId, latId, lonId, mapCtl) {
    const input = document.getElementById(inputId);
    const list = document.getElementById(listId);
    const latEl = document.getElementById(latId);
    const lonEl = document.getElementById(lonId);
    if (!input || !list) return;

    function clearList() {
      list.innerHTML = "";
      list.style.display = "none";
    }
    function showItems(items) {
      list.innerHTML = "";
      for (const it of items) {
        const a = document.createElement("a");
        a.href = "#";
        a.className = "list-group-item list-group-item-action";
        a.textContent = it.display_name;
        a.dataset.lat = it.lat;
        a.dataset.lon = it.lon;
        a.addEventListener("click", (ev) => {
          ev.preventDefault();
          input.value = it.display_name;
          if (latEl && lonEl) {
            latEl.value = parseFloat(it.lat).toFixed(6);
            lonEl.value = parseFloat(it.lon).toFixed(6);
          }
          if (mapCtl && mapCtl.init) mapCtl.init();
          if (mapCtl && mapCtl.marker && mapCtl.map) {
            const p = [parseFloat(it.lat), parseFloat(it.lon)];
            mapCtl.marker.setLatLng(p);
            mapCtl.map.setView(p, 15);
          }
          clearList();
        });
        list.appendChild(a);
      }
      list.style.display = items.length ? "block" : "none";
    }

    const doSearch = debounce(async function () {
      const q = input.value.trim();
      if (q.length < 3) {
        clearList();
        return;
      }
      try {
        const res = await fetch("ajax/geocode.php?q=" + encodeURIComponent(q), {
          headers: { Accept: "application/json" },
        });
        if (!res.ok) {
          clearList();
          return;
        }
        const data = await res.json();
        showItems(data);
      } catch {
        clearList();
      }
    }, 350);

    input.addEventListener("input", doSearch);
    input.addEventListener("blur", () => setTimeout(clearList, 200));
  }

  function setupLocate(btnId, latId, lonId, mapCtl) {
    const btn = document.getElementById(btnId);
    const latEl = document.getElementById(latId);
    const lonEl = document.getElementById(lonId);
    if (!btn || !latEl || !lonEl) return;

    btn.addEventListener("click", () => {
      if (!navigator.geolocation) return;
      navigator.geolocation.getCurrentPosition((pos) => {
        const { latitude, longitude } = pos.coords;
        latEl.value = latitude.toFixed(6);
        lonEl.value = longitude.toFixed(6);
        if (mapCtl && mapCtl.init) mapCtl.init();
        if (mapCtl && mapCtl.marker && mapCtl.map) {
          const p = [latitude, longitude];
          mapCtl.marker.setLatLng(p);
          mapCtl.map.setView(p, 15);
        }
      });
    });
  }

  // Préremplissage modal édition (via data-attributes sur le bouton)
  document.addEventListener("click", function (e) {
    const btn = e.target.closest(".btn-edit-depot");
    if (!btn) return;
    const idEl = document.getElementById("edit_id");
    if (idEl) idEl.value = btn.dataset.id || "";
    const setVal = (id, v) => {
      const el = document.getElementById(id);
      if (el) el.value = v || "";
    };
    setVal("edit_nom", btn.dataset.nom || "");
    setVal("edit_adresse", btn.dataset.adresse || "");
    setVal("edit_responsable", btn.dataset.responsable || "");
    setVal("edit_telephone", btn.dataset.telephone || "");
    if (btn.dataset.email !== undefined)
      setVal("edit_email", btn.dataset.email || "");
    if (btn.dataset.latitude !== undefined)
      setVal("edit_latitude", btn.dataset.latitude || "");
    if (btn.dataset.longitude !== undefined)
      setVal("edit_longitude", btn.dataset.longitude || "");
  });

  // Création
  const createMapCtl = setupMap(
    "addDepotModal",
    "create_map",
    "create_latitude",
    "create_longitude"
  );
  setupAutocomplete(
    "create_adresse",
    "create_address_suggestions",
    "create_latitude",
    "create_longitude",
    createMapCtl
  );
  setupLocate(
    "create_locate_btn",
    "create_latitude",
    "create_longitude",
    createMapCtl
  );

  // Édition
  const editMapCtl = setupMap(
    "editDepotModal",
    "edit_map",
    "edit_latitude",
    "edit_longitude"
  );
  setupAutocomplete(
    "edit_adresse",
    "edit_address_suggestions",
    "edit_latitude",
    "edit_longitude",
    editMapCtl
  );
  setupLocate("edit_locate_btn", "edit_latitude", "edit_longitude", editMapCtl);
})();
