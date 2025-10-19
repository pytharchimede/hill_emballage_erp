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
  });
})();
