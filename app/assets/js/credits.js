// Gestion de l'ouverture du modal d'encaissement sans inline JS (compatible CSP)
(function () {
  function ready(fn) {
    if (document.readyState === "loading")
      document.addEventListener("DOMContentLoaded", fn);
    else fn();
  }

  ready(function () {
    var modalEl = document.getElementById("payModal");
    var modalInstance = null;

    function ensureModal() {
      if (
        !modalInstance &&
        modalEl &&
        window.bootstrap &&
        window.bootstrap.Modal
      ) {
        modalInstance = new bootstrap.Modal(modalEl);
      }
      return modalInstance;
    }

    document.body.addEventListener("click", function (e) {
      var btn = e.target.closest(".btn-encaisser");
      if (!btn) return;
      e.preventDefault();

      var venteId = btn.getAttribute("data-vente-id");
      var numero = btn.getAttribute("data-numero") || "";
      var restant = btn.getAttribute("data-restant") || "0";

      var idEl = document.getElementById("pm_vente_id");
      var invEl = document.getElementById("pm_invoice");
      var mEl = document.getElementById("pm_montant");

      if (idEl) idEl.value = venteId || "";
      if (invEl)
        invEl.textContent =
          numero +
          " — Reste " +
          new Intl.NumberFormat("fr-FR").format(restant) +
          " FCFA";
      if (mEl) {
        mEl.value = restant;
        mEl.setAttribute("min", "1");
        mEl.setAttribute("max", String(restant));
      }

      var mi = ensureModal();
      if (mi) mi.show();
    });
  });
})();
