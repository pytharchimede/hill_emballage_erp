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
        setText($status, "Position obtenue, envoi…");
        sendPosition(latitude, longitude);
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
})();
