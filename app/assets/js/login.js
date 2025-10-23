(function () {
  function onReady(fn) {
    if (document.readyState !== "loading") fn();
    else document.addEventListener("DOMContentLoaded", fn);
  }
  onReady(function () {
    document.addEventListener("click", function (e) {
      var btn = e.target.closest(".quick-login");
      if (!btn) return;
      e.preventDefault();
      var email = btn.getAttribute("data-email");
      var pwd = btn.getAttribute("data-password");
      var emailInput = document.getElementById("email");
      var pwdInput = document.getElementById("password");
      var form = document.querySelector("form");
      if (emailInput && pwdInput && form) {
        emailInput.value = email || "";
        pwdInput.value = pwd || "";
        form.submit();
      }
    });

    // Toggle visibilité mot de passe
    document.addEventListener("click", function (e) {
      var tgl = e.target.closest(".toggle-password");
      if (!tgl) return;
      e.preventDefault();
      var pwd = document.getElementById("password");
      if (!pwd) return;
      var isText = pwd.getAttribute("type") === "text";
      pwd.setAttribute("type", isText ? "password" : "text");
      var icon = tgl.querySelector("i");
      if (icon) {
        if (isText) {
          icon.classList.remove("fa-eye-slash");
          icon.classList.add("fa-eye");
        } else {
          icon.classList.remove("fa-eye");
          icon.classList.add("fa-eye-slash");
        }
      }
      tgl.setAttribute(
        "aria-label",
        isText ? "Afficher le mot de passe" : "Masquer le mot de passe"
      );
      tgl.setAttribute("title", isText ? "Afficher" : "Masquer");
    });
  });
})();
