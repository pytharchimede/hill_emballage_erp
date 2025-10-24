"use strict";
(function () {
  const baseUrl =
    document.querySelector('meta[name="base-url"]')?.getAttribute("content") ||
    "/";
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
  const addrInput = document.getElementById("addr");
  let map = null,
    marker = null;
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
      const resp = await fetch(baseUrl + "app/api/client_location_submit.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: form.toString(),
        credentials: "same-origin",
      });
      const data = await resp
        .json()
        .catch(() => ({ ok: false, error: "parse_error" }));
      if (resp.ok && data && data.ok) {
        show($ok);
        setText($status, "Position envoyée.");
      } else {
        show($err);
        setText($err, (data && data.error) || "Erreur inconnue");
      }
    } catch (e) {
      show($err);
      setText($err, "Erreur réseau");
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
      show($err);
      setText(
        $err,
        "La géolocalisation n'est pas disponible sur cet appareil."
      );
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
        let msg = "Impossible d'obtenir votre position.";
        if (error && error.message) msg += " " + error.message;
        show($err);
        setText($err, msg);
        setText($status, "");
      },
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
  }

  document.getElementById("btnShare")?.addEventListener("click", onClick);
  btnConfirm?.addEventListener("click", function () {
    hide($err);
    hide($ok);
    if (current.lat == null || current.lon == null) {
      setText($status, "Veuillez d'abord activer votre position.");
      return;
    }
    setText($status, "Envoi en cours…");
    sendPosition(current.lat, current.lon);
  });
})();
