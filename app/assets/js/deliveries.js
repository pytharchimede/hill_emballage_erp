"use strict";
(function () {
  const BASE = document.querySelector('meta[name="base-url"]')?.content || "";
  const mapEl = document.getElementById("deliveries_map");
  const listEl = document.getElementById("deliveries_list");
  const countEl = document.getElementById("deliveries_count");
  const scopeBtns = document.querySelectorAll("[data-scope]");
  let scope = "pending";

  // Modal elements
  const modalEl = document.getElementById("deliveryModal");
  const dlNumero = document.getElementById("dl_numero");
  const dlClient = document.getElementById("dl_client");
  const dlAdresse = document.getElementById("dl_adresse");
  const dlMontant = document.getElementById("dl_montant");
  const dlEntityId = document.getElementById("dl_entity_id");
  const dlMapEl = document.getElementById("dl_map");
  const dlSigCanvas = document.getElementById("dl_signature");
  const dlSigClear = document.getElementById("dl_sig_clear");
  const dlSigSave = document.getElementById("dl_sig_save");
  const dlConfirm = document.getElementById("dl_confirm_btn");

  // Leaflet map globals
  let map = null;
  let markersLayer = null;
  function initMap() {
    if (!mapEl || !window.L) return;
    if (!map) {
      map = L.map("deliveries_map").setView([5.345317, -4.024429], 12);
      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "&copy; OpenStreetMap",
      }).addTo(map);
      markersLayer = L.layerGroup().addTo(map);
    } else {
      markersLayer.clearLayers();
      setTimeout(() => map.invalidateSize(), 100);
    }
  }

  function colorForStatus(st) {
    if (st === "livree") return "#28a745"; // green
    if (st === "validee") return "#fd7e14"; // orange
    return "#6c757d";
  }

  function addMarkers(items) {
    if (!markersLayer) return;
    let bounds = [];
    items.forEach((it) => {
      if (!it.is_geo) return;
      const lat = parseFloat(it.lat),
        lon = parseFloat(it.lon);
      if (isNaN(lat) || isNaN(lon)) return;
      const m = L.circleMarker([lat, lon], {
        radius: 9,
        color: colorForStatus(it.statut),
        fillColor: colorForStatus(it.statut),
        fillOpacity: 0.8,
      });
      m.bindPopup(
        `<strong>${it.numero_vente}</strong><br/>${it.client_label || ""}<br/>${
          it.delivery_address || ""
        }`
      );
      m.on("click", () => openModal(it));
      m.addTo(markersLayer);
      bounds.push([lat, lon]);
    });
    if (bounds.length) {
      map.fitBounds(bounds, { padding: [20, 20] });
    }
  }

  function renderList(items) {
    if (!listEl) return;
    listEl.innerHTML = "";
    if (countEl) countEl.textContent = String(items.length);
    if (!items.length) {
      const e = document.createElement("div");
      e.className = "p-3 text-muted";
      e.textContent = "Aucune livraison.";
      listEl.appendChild(e);
      return;
    }
    items.forEach((it) => {
      const a = document.createElement("a");
      a.href = "#";
      a.className = "list-group-item list-group-item-action";
      a.innerHTML = `
        <div class="d-flex w-100 justify-content-between">
          <h5 class="mb-1">${it.numero_vente}</h5>
          <small class="badge" style="background:${colorForStatus(
            it.statut
          )};color:#fff;">${it.statut}</small>
        </div>
        <p class="mb-1">${it.client_label || ""}</p>
        <small class="text-muted">${it.delivery_address || ""}</small>`;
      a.addEventListener("click", (ev) => {
        ev.preventDefault();
        openModal(it);
      });
      listEl.appendChild(a);
    });
  }

  async function loadData() {
    initMap();
    try {
      const r = await fetch(
        `${BASE}/app/ajax/deliveries_list.php?scope=${encodeURIComponent(
          scope
        )}`,
        { headers: { Accept: "application/json" } }
      );
      const data = await r.json();
      if (!data.ok) {
        renderList([]);
        return;
      }
      renderList(data.items);
      if (markersLayer) {
        markersLayer.clearLayers();
        addMarkers(data.items);
      }
    } catch (e) {
      renderList([]);
    }
  }

  scopeBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      scope = btn.getAttribute("data-scope") || "pending";
      loadData();
    });
  });

  // Modal logic
  let modal = null;
  let singleMap = null;
  let sigPad = null;
  function openModal(it) {
    if (dlNumero) dlNumero.textContent = it.numero_vente || "-";
    if (dlClient) dlClient.textContent = it.client_label || "-";
    if (dlAdresse) dlAdresse.textContent = it.delivery_address || "-";
    if (dlMontant)
      dlMontant.textContent = (Number(it.montant_total) || 0).toLocaleString(
        "fr-FR"
      );
    if (dlEntityId) dlEntityId.value = String(it.id);
    // small map
    if (dlMapEl && window.L) {
      setTimeout(() => {
        if (singleMap) {
          singleMap.invalidateSize();
        } else {
          singleMap = L.map("dl_map");
          L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution: "&copy; OpenStreetMap",
          }).addTo(singleMap);
        }
        if (it.is_geo) {
          const lat = parseFloat(it.lat),
            lon = parseFloat(it.lon);
          const mk = L.circleMarker([lat, lon], {
            radius: 9,
            color: colorForStatus(it.statut),
            fillColor: colorForStatus(it.statut),
            fillOpacity: 0.8,
          }).addTo(singleMap);
          singleMap.setView([lat, lon], 15);
        }
      }, 50);
    }
    // signature pad
    if (dlSigCanvas && window.SignaturePad) {
      // resize canvas to container
      const parent = dlSigCanvas.parentElement;
      if (parent) {
        dlSigCanvas.width = parent.clientWidth - 2;
        dlSigCanvas.height = 220;
      }
      sigPad = new SignaturePad(dlSigCanvas, {
        backgroundColor: "rgba(255,255,255,1)",
        penColor: "#000",
      });
    }
    // show modal
    if (window.bootstrap && window.bootstrap.Modal) {
      modal = new bootstrap.Modal(modalEl);
      modal.show();
    } else if (window.__fallbackShowModal) {
      window.__fallbackShowModal("deliveryModal");
    }
  }

  if (dlSigClear) {
    dlSigClear.addEventListener("click", (e) => {
      e.preventDefault();
      try {
        sigPad?.clear();
      } catch {}
    });
  }
  if (dlSigSave) {
    dlSigSave.addEventListener("click", async (e) => {
      e.preventDefault();
      try {
        if (!sigPad || sigPad.isEmpty()) {
          alert("Veuillez signer.");
          return;
        }
        const id = dlEntityId?.value;
        if (!id) {
          return;
        }
        const dataURL = sigPad.toDataURL("image/png");
        const blob = await (await fetch(dataURL)).blob();
        const fd = new FormData();
        fd.append("entity", "ventes");
        fd.append("entity_id", id);
        fd.append("redirect", `${BASE}/web_admin/deliveries.php`);
        fd.append("format", "json");
        fd.append("file", blob, `signature_${id}.png`);
        const resp = await fetch(`${BASE}/app/api/upload_attachment.php`, {
          method: "POST",
          body: fd,
        });
        const j = await resp.json();
        if (!j.ok) {
          alert("Échec de sauvegarde de la signature.");
          return;
        }
        alert("Signature enregistrée.");
      } catch (e) {
        alert("Erreur pendant la sauvegarde.");
      }
    });
  }

  if (dlConfirm) {
    dlConfirm.addEventListener("click", async () => {
      const id = dlEntityId?.value;
      if (!id) return;
      try {
        const fd = new FormData();
        fd.append("id", id);
        const r = await fetch(`${BASE}/app/api/confirm_delivery.php`, {
          method: "POST",
          body: fd,
        });
        const j = await r.json();
        if (!j.ok) {
          alert("Échec de confirmation.");
          return;
        }
        if (modal && modal.hide) modal.hide();
        loadData();
        alert("Livraison confirmée.");
      } catch (e) {
        alert("Erreur de confirmation.");
      }
    });
  }

  // init
  loadData();
})();
