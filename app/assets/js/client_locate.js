"use strict";
(function () {
  const baseUrl =
    document.querySelector('meta[name="base-url"]')?.getAttribute("content") ||
    "/";
  // Normaliser la base: s'assurer d'une barre de fin et d'un chemin absolu
  let apiBase = baseUrl;
  if (!apiBase.endsWith("/")) apiBase += "/";
  if (!/^https?:\/\//i.test(apiBase) && !apiBase.startsWith("/"))
    apiBase = "/" + apiBase;
  const body = document.body;
  const valid = body.getAttribute("data-valid") === "1";
  if (!valid) {
    return;
  }
  const id = parseInt(body.getAttribute("data-id") || "0", 10);
  const sig = body.getAttribute("data-sig") || "";
  const $status = window.jQuery ? jQuery("#status") : null;
  const $ok = window.jQuery ? jQuery("#ok") : null;
  const $err = window.jQuery ? jQuery("#err") : null;
  const mapBlock = document.getElementById("mapBlock");
  const btnConfirm = document.getElementById("btnConfirm");
  const btnDecline = document.getElementById("btnDecline");
  const addrInput = document.getElementById("addr");
  let map = null,
    marker = null;
  const redirectTarget = "https://hillemballage.ci";
  let current = { lat: null, lon: null };

  function setText($el, text) {
    if ($el) {
      $el.text(text);
    }
  }
  function show($el) {
    if ($el) {
      $el.removeClass("d-none");
    }
  }
  function hide($el) {
    if ($el) {
      $el.addClass("d-none");
    }
  }

  async function sendPosition(lat, lon) {
    try {
      const form = new URLSearchParams();
      form.append("id", String(id));
      form.append("sig", sig);
      form.append("lat", String(lat));
      form.append("lon", String(lon));
      if (addrInput && addrInput.value) form.append("addr", addrInput.value);
      const resp = await fetch(apiBase + "app/api/client_location_submit.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: form.toString(),
        credentials: "same-origin",
      });
      const data = await resp
        .json()
        .catch(() => ({ ok: false, error: "parse_error" }));
      if (resp.ok && data && data.ok) {
        // Succès
        setText($status, "Position envoyée.");
        if (window.showToast) {
          const extra = data.address ? "\n" + data.address : "";
          window.showToast(
            "Merci, votre position a été partagée." + extra,
            "success"
          );
        } else {
          show($ok);
        }
        // Redirection après un court délai
        setTimeout(function () {
          window.location.href = redirectTarget;
        }, 1400);
      } else {
        const msg = (data && data.error) || "Erreur inconnue";
        if (window.showToast) window.showToast(msg, "error");
        show($err);
        setText($err, msg);
      }
      return data;
    } catch (e) {
      if (window.showToast) window.showToast("Erreur réseau", "error");
      show($err);
      setText($err, "Erreur réseau");
      return { ok: false, error: "network_error" };
    }
  }

  function ensureMap(lat, lon) {
    if (!window.L) return;
    if (!map) {
      map = L.map("cl_map");
      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "&copy; OpenStreetMap",
      }).addTo(map);
    }
    if (!marker) {
      marker = L.marker([lat, lon], { draggable: true }).addTo(map);
      marker.on("dragend", function () {
        const p = marker.getLatLng();
        current.lat = p.lat;
        current.lon = p.lng;
        setText(
          $status,
          "Point ajusté: " + p.lat.toFixed(5) + ", " + p.lng.toFixed(5)
        );
      });
    } else {
      marker.setLatLng([lat, lon]);
    }
    map.setView([lat, lon], 16);
    current.lat = lat;
    current.lon = lon;
    if (mapBlock) mapBlock.classList.remove("d-none");
  }

  function onClick() {
    hide($err);
    hide($ok);
    if (!("geolocation" in navigator)) {
      const msg = "La géolocalisation n'est pas disponible sur cet appareil.";
      if (window.showToast) window.showToast(msg, "error");
      show($err);
      setText($err, msg);
      return;
    }
    setText($status, "Demande d'autorisation de localisation…");
    navigator.geolocation.getCurrentPosition(
      function (pos) {
        const { latitude, longitude } = pos.coords;
        setText(
          $status,
          "Position obtenue. Vous pouvez ajuster le point si besoin."
        );
        ensureMap(latitude, longitude);
      },
      function (error) {
        let msg = "Partage de position annulé.";
        if (error && error.code === 1) {
          // PERMISSION_DENIED
          msg = "Partage de position annulé.";
        } else {
          msg =
            "Impossible d'obtenir votre position." +
            (error && error.message ? " " + error.message : "");
        }
        if (window.showToast) window.showToast(msg, "info");
        show($err);
        setText($err, msg);
        setText($status, "");
        // Rediriger en cas de refus/cas d'erreur
        setTimeout(function () {
          window.location.href = redirectTarget;
        }, 1400);
      },
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
  }

  document.getElementById("btnShare")?.addEventListener("click", onClick);
  btnConfirm?.addEventListener("click", async function () {
    hide($err);
    hide($ok);
    if (current.lat == null || current.lon == null) {
      setText($status, "Veuillez d'abord activer votre position.");
      return;
    }
    setText($status, "Envoi en cours…");
    // Désactiver temporairement le bouton pour éviter les doublons
    btnConfirm.setAttribute("disabled", "disabled");
    try {
      await sendPosition(current.lat, current.lon);
    } finally {
      btnConfirm.removeAttribute("disabled");
    }
  });

  // Bouton Refuser
  btnDecline?.addEventListener("click", function () {
    if (window.showToast)
      window.showToast("Partage de position annulé.", "info");
    setTimeout(function () {
      window.location.href = redirectTarget;
    }, 1400);
  });

  // Essayer d'obtenir la position automatiquement à l'ouverture (certains navigateurs exigent une interaction utilisateur)
  try {
    onClick();
  } catch (e) {}
})();
