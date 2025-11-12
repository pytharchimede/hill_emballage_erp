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
  const dlPrint = document.getElementById("dl_print_btn");
  const dlLocLink = document.getElementById("dl_loc_link");
  const dlLocWhatsApp = document.getElementById("dl_loc_whatsapp");
  const dlLocSMS = document.getElementById("dl_loc_sms");

  // Toast léger
  function showToast(message, kind = "info") {
    let t = document.getElementById("app_toast");
    if (!t) {
      t = document.createElement("div");
      t.id = "app_toast";
      t.className = "app-toast d-none";
      const s = document.createElement("span");
      s.id = "app_toast_text";
      t.appendChild(s);
      document.body.appendChild(t);
    }
    t.classList.remove("d-none", "success", "error", "info");
    t.classList.add(kind);
    const span = t.querySelector("#app_toast_text");
    if (span) span.textContent = message;
    setTimeout(() => {
      t.classList.add("show");
    }, 10);
    setTimeout(() => {
      t.classList.remove("show");
    }, 2800);
  }

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
    if (dlLocLink) dlLocLink.dataset.id = String(it.id || "");
    if (dlLocWhatsApp) {
      dlLocWhatsApp.href = "#";
      dlLocWhatsApp.classList.add("disabled");
      dlLocWhatsApp.setAttribute("aria-disabled", "true");
    }
    if (dlLocSMS) {
      dlLocSMS.href = "#";
      dlLocSMS.classList.add("disabled");
      dlLocSMS.setAttribute("aria-disabled", "true");
    }
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
          L.circleMarker([lat, lon], {
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
          showToast("Veuillez signer.", "error");
          return;
        }
        const id = dlEntityId?.value;
        if (!id) {
          return;
        }
        const dataURL = sigPad.toDataURL("image/png");
        // Conversion locale du dataURL en Blob (évite fetch bloqué par CSP)
        const base64 = dataURL.split(",")[1];
        const binary = atob(base64);
        const len = binary.length;
        const bytes = new Uint8Array(len);
        for (let i = 0; i < len; i++) bytes[i] = binary.charCodeAt(i);
        const blob = new Blob([bytes], { type: "image/png" });
        const fd = new FormData();
        fd.append("entity", "ventes");
        fd.append("entity_id", id);
        // Forcer une réponse JSON (éviter PRG qui renvoie 302)
        fd.append("redirect", "");
        fd.append("format", "json");
        fd.append("file", blob, `signature_${id}.png`);
        const resp = await fetch(`${BASE}/app/api/upload_attachment.php`, {
          method: "POST",
          body: fd,
          credentials: "same-origin",
        });
        let j;
        try {
          j = await resp.json();
        } catch (parseErr) {
          const txt = await resp.text();
          showToast(
            `Signature: réponse invalide (${resp.status}) ${txt.slice(0, 80)}`,
            "error"
          );
          return;
        }
        if (!j.ok) {
          const dbg = j.debug
            ? ` [role=${
                j.debug.role || j.debug.user_role || "n/a"
              } perms=${Object.keys(j.debug)
                .map((k) => `${k}=${j.debug[k]}`)
                .join(",")}]`
            : "";
          showToast(
            j.error
              ? `Signature: ${j.error}${dbg}`
              : "Échec de sauvegarde de la signature.",
            "error"
          );
          return;
        }
        showToast("Signature enregistrée.", "success");
      } catch (e) {
        showToast(
          e?.message
            ? `Signature: ${e.message}`
            : "Erreur pendant la sauvegarde.",
          "error"
        );
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
          credentials: "same-origin",
        });
        let j;
        try {
          j = await r.json();
        } catch (parseErr) {
          const txt = await r.text();
          showToast(
            `Confirmation: réponse invalide (${r.status}) ${txt.slice(0, 80)}`,
            "error"
          );
          return;
        }
        if (!j.ok) {
          const dbg = j.debug
            ? ` [role=${j.debug.user_role || "n/a"} deliveries=${
                j.debug.deliveries_update
              } sales=${j.debug.sales_update}]`
            : "";
          showToast(
            j.error
              ? `Confirmation: ${j.error}${dbg}`
              : "Échec de confirmation.",
            "error"
          );
          return;
        }
        if (modal && modal.hide) modal.hide();
        loadData();
        showToast("Livraison confirmée.", "success");
      } catch (e) {
        showToast(
          e?.message ? `Confirmation: ${e.message}` : "Erreur de confirmation.",
          "error"
        );
      }
    });
  }

  if (dlPrint) {
    dlPrint.addEventListener("click", (e) => {
      e.preventDefault();
      try {
        const html = `<!doctype html><html><head><meta charset="utf-8"><title>Fiche de livraison</title>
        <style>body{font-family:Arial,sans-serif;padding:20px;} h1{font-size:20px;margin:0 0 10px;} .box{border:1px solid #ddd;border-radius:8px;padding:10px;margin-bottom:10px}</style></head><body>
        <h1>Fiche de livraison</h1>
        <div class="box"><strong>Commande:</strong> ${
          dlNumero?.textContent || ""
        }<br/>
        <strong>Client:</strong> ${dlClient?.textContent || ""}<br/>
        <strong>Adresse:</strong> ${dlAdresse?.textContent || ""}<br/>
        <strong>Montant:</strong> ${dlMontant?.textContent || ""} FCFA</div>
        <div class="box"><em>La signature et les photos jointes sont enregistrées dans le système.</em></div>
        </body></html>`;
        const w = window.open("", "_blank");
        if (!w) return;
        w.document.write(html);
        w.document.close();
        w.focus();
        w.print();
        setTimeout(() => w.close(), 200);
      } catch {}
    });
  }

  if (dlLocLink) {
    dlLocLink.addEventListener("click", async (e) => {
      e.preventDefault();
      const id = dlLocLink.dataset.id;
      if (!id) return;
      try {
        const r = await fetch(
          `${BASE}/app/ajax/location_link.php?id=${encodeURIComponent(id)}`,
          {
            headers: { Accept: "application/json" },
            credentials: "same-origin",
          }
        );
        const j = await r.json();
        if (!j.ok || !j.url) {
          showToast("Impossible de générer le lien.", "error");
          return;
        }
        const url = j.url;
        // Copier dans le presse-papiers si possible
        if (navigator.clipboard && navigator.clipboard.writeText) {
          await navigator.clipboard.writeText(url);
          showToast("Lien copié dans le presse-papiers.", "success");
        } else {
          // Fallback prompt
          showToast("Copiez le lien affiché…", "info");
          prompt("Copiez le lien:", url);
        }
        const message = `Bonjour, voici le lien pour partager votre position pour la livraison ${
          dlNumero?.textContent || ""
        } : ${url}`;
        if (dlLocWhatsApp) {
          dlLocWhatsApp.href = `https://wa.me/?text=${encodeURIComponent(
            message
          )}`;
          dlLocWhatsApp.classList.remove("disabled");
          dlLocWhatsApp.removeAttribute("aria-disabled");
        }
        if (dlLocSMS) {
          dlLocSMS.href = `sms:?&body=${encodeURIComponent(message)}`;
          dlLocSMS.classList.remove("disabled");
          dlLocSMS.removeAttribute("aria-disabled");
        }
      } catch (e) {
        showToast("Erreur réseau.", "error");
      }
    });
  }

  // init
  loadData();
})();
