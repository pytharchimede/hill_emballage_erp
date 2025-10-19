(function () {
  // Detect if Bootstrap CSS is applied (z-index >= 1055 on .modal)
  function hasBootstrapCss() {
    try {
      var probe = document.createElement("div");
      probe.className = "modal";
      probe.style.position = "absolute";
      probe.style.visibility = "hidden";
      document.body.appendChild(probe);
      var z = parseInt(window.getComputedStyle(probe).zIndex || "0", 10);
      document.body.removeChild(probe);
      return z >= 1055;
    } catch (e) {
      return false;
    }
  }

  if (!hasBootstrapCss()) {
    document.body.classList.add("no-bs-css");
  }

  // Fallback JS if Bootstrap JS unavailable: simple toggle for modals
  if (!(window.bootstrap && window.bootstrap.Modal)) {
    var backdrop;
    function ensureBackdrop() {
      if (!backdrop) {
        backdrop = document.createElement("div");
        backdrop.className = "modal-backdrop fade show";
        backdrop.style.zIndex = "1060";
        document.body.appendChild(backdrop);
      }
      return backdrop;
    }
    function removeBackdrop() {
      if (backdrop && backdrop.parentNode)
        backdrop.parentNode.removeChild(backdrop);
      backdrop = null;
    }
    function showModal(id) {
      var el = document.getElementById(id);
      if (!el) return;
      document.body.classList.add("no-bs-css");
      document.body.classList.add("modal-open");
      ensureBackdrop();
      el.classList.add("show");
      el.style.display = "block";
      el.setAttribute("aria-modal", "true");
      el.removeAttribute("aria-hidden");
    }
    function hideModal(el) {
      el.classList.remove("show");
      el.style.display = "none";
      el.removeAttribute("aria-modal");
      el.setAttribute("aria-hidden", "true");
      removeBackdrop();
      // If no other modals are open, remove modal-open
      if (!document.querySelector(".modal.show")) {
        document.body.classList.remove("modal-open");
      }
    }
    window.__fallbackShowModal = showModal;
    document.addEventListener("click", function (e) {
      var btn = e.target.closest('[data-bs-toggle="modal"]');
      if (btn) {
        var target = btn.getAttribute("data-bs-target");
        if (target && target.startsWith("#")) {
          e.preventDefault();
          showModal(target.substring(1));
        }
      }
      var closeBtn = e.target.closest('[data-bs-dismiss="modal"], .btn-close');
      if (closeBtn) {
        var modal = closeBtn.closest(".modal");
        if (modal) {
          e.preventDefault();
          hideModal(modal);
        }
      }
    });
  }
})();
