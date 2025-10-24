"use strict";
(function () {
  const mapEl = document.getElementById("map");
  if (!mapEl || !window.L) return;

  const DEFAULT_CENTER = [5.345317, -4.024429];
  function debounce(fn, delay) {
    let t;
    return function (...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), delay);
    };
  }

  const map = L.map("map").setView(DEFAULT_CENTER, 11);
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
    attribution: "&copy; OpenStreetMap",
  }).addTo(map);
  const cluster = L.markerClusterGroup();
  map.addLayer(cluster);

  const successToastEl = document.getElementById("mapToast");
  const successToastBody = document.getElementById("mapToastBody");
  const errToastEl = document.getElementById("mapToastErr");
  const errToastBody = document.getElementById("mapToastErrBody");
  function showOk(msg) {
    if (successToastBody)
      successToastBody.textContent = msg || "Position enregistrée";
    if (window.bootstrap && successToastEl)
      new bootstrap.Toast(successToastEl).show();
  }
  function showErr(msg) {
    if (errToastBody) errToastBody.textContent = msg || "Erreur de mise à jour";
    if (window.bootstrap && errToastEl) new bootstrap.Toast(errToastEl).show();
  }

  async function loadDepots() {
    try {
      const res = await fetch("ajax/depots_list.php", {
        headers: { Accept: "application/json" },
      });
      if (!res.ok) return [];
      return await res.json();
    } catch {
      return [];
    }
  }

  function fitCenter(points) {
    if (!points.length) return;
    let lat = 0,
      lng = 0,
      n = 0;
    for (const p of points) {
      const la = parseFloat(p.latitude),
        lo = parseFloat(p.longitude);
      if (!isNaN(la) && !isNaN(lo)) {
        lat += la;
        lng += lo;
        n++;
      }
    }
    if (n > 0) map.setView([lat / n, lng / n], 12);
  }
  function buildPopup(d) {
    const email = d.email
      ? `<div><i class="fas fa-envelope"></i> ${d.email}</div>`
      : "";
    const hrs = d.horaires
      ? `<div><i class="fas fa-clock"></i> ${d.horaires}</div>`
      : "";
    return `<div style="min-width:220px"><div style="font-weight:600;margin-bottom:4px;">${
      d.nom || ""
    }</div><div style="color:#555">${
      d.adresse || ""
    }</div><div style="margin-top:6px;"><div><i class="fas fa-user"></i> ${
      d.responsable || ""
    }</div><div><i class="fas fa-phone"></i> ${
      d.telephone || ""
    }</div>${email}${hrs}</div></div>`;
  }

  function addMarkers(depots) {
    const canDrag = mapEl.getAttribute("data-can-edit") === "1";
    const pts = [];
    for (const d of depots) {
      const la = parseFloat(d.latitude),
        lo = parseFloat(d.longitude);
      if (isNaN(la) || isNaN(lo)) continue;
      pts.push({ latitude: la, longitude: lo });
      const marker = L.marker([la, lo], { draggable: !!canDrag });
      marker.bindPopup(buildPopup(d));
      if (canDrag)
        marker.on("dragend", async () => {
          const p = marker.getLatLng();
          try {
            const res = await fetch(window.location.href, {
              method: "POST",
              headers: { "Content-Type": "application/x-www-form-urlencoded" },
              body: new URLSearchParams({
                action: "update_geo",
                id: String(d.id),
                lat: String(p.lat),
                lon: String(p.lng),
              }),
            });
            const data = await res.json().catch(() => ({ success: false }));
            if (!data.success) {
              showErr("Échec de la mise à jour");
            } else {
              showOk("Position enregistrée");
            }
          } catch {
            showErr("Erreur réseau lors de la mise à jour");
          }
        });
      cluster.addLayer(marker);
    }
    fitCenter(pts);
  }

  loadDepots().then(addMarkers);

  // Recherche d'adresse via proxy interne
  const input = document.getElementById("map_search_input");
  const list = document.getElementById("map_search_suggestions");
  function clearList() {
    if (!list) return;
    list.innerHTML = "";
    list.style.display = "none";
  }
  function showItems(items) {
    if (!list) return;
    list.innerHTML = "";
    items.forEach((it) => {
      const a = document.createElement("a");
      a.href = "#";
      a.className = "list-group-item list-group-item-action";
      a.textContent = it.display_name;
      a.addEventListener("click", (e) => {
        e.preventDefault();
        input.value = it.display_name;
        const la = parseFloat(it.lat),
          lo = parseFloat(it.lon);
        if (!isNaN(la) && !isNaN(lo)) map.setView([la, lo], 15);
        clearList();
      });
      list.appendChild(a);
    });
    list.style.display = items.length ? "block" : "none";
  }
  const doSearch = debounce(async function () {
    if (!input) return;
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
  if (input) {
    input.addEventListener("input", doSearch);
    input.addEventListener("blur", () => setTimeout(clearList, 200));
  }
})();
