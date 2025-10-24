"use strict";
// Auto-hide flash messages
(function () {
  setTimeout(function () {
    const alerts = document.querySelectorAll(".alert");
    alerts.forEach((alert) => {
      alert.style.transition = "opacity 0.5s ease";
      alert.style.opacity = "0";
      setTimeout(() => alert.remove(), 500);
    });
  }, 5000);

  // Confirmation for delete actions
  document.addEventListener("click", function (e) {
    const dangerBtn =
      e.target.classList && e.target.classList.contains("btn-danger")
        ? e.target
        : e.target.closest && e.target.closest(".btn-danger");
    if (dangerBtn) {
      if (!confirm("Êtes-vous sûr de vouloir effectuer cette action ?")) {
        e.preventDefault();
      }
    }
  });

  // Optional auto-refresh for specific pages
  setInterval(function () {
    const currentPage = window.location.pathname.split("/").pop();
    if (["dashboard.php", "stock.php"].includes(currentPage)) {
      // location.reload();
    }
  }, 120000);
})();
