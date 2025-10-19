(function () {
  function onReady(fn) {
    if (document.readyState !== "loading") fn();
    else document.addEventListener("DOMContentLoaded", fn);
  }
  onReady(function () {
    var btn = document.querySelector(".menu-toggle");
    var backdrop = document.querySelector("[data-menu-backdrop]");
    var sidebar = document.getElementById("sidebar");
    if (!btn || !sidebar) return;

    function setExpanded(exp) {
      btn.setAttribute("aria-expanded", exp ? "true" : "false");
    }
    function openMenu() {
      document.body.classList.add("menu-open");
      setExpanded(true);
    }
    function closeMenu() {
      document.body.classList.remove("menu-open");
      setExpanded(false);
    }
    function toggleMenu() {
      if (document.body.classList.contains("menu-open")) closeMenu();
      else openMenu();
    }

    btn.addEventListener("click", function (e) {
      e.preventDefault();
      toggleMenu();
    });
    if (backdrop) {
      backdrop.addEventListener("click", function () {
        closeMenu();
      });
    }
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") closeMenu();
    });
  });
})();
