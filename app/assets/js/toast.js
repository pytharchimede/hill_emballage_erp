(function () {
  "use strict";
  if (window.showToast) return; // ne pas écraser si déjà défini
  window.showToast = function (message, kind) {
    var t = document.getElementById("app_toast");
    if (!t) {
      t = document.createElement("div");
      t.id = "app_toast";
      t.className = "app-toast d-none";
      var s = document.createElement("span");
      s.id = "app_toast_text";
      t.appendChild(s);
      document.body.appendChild(t);
    }
    t.classList.remove("d-none", "success", "error", "info");
    if (kind) t.classList.add(kind);
    var span = t.querySelector("#app_toast_text");
    if (span) span.textContent = message || "";
    setTimeout(function () {
      t.classList.add("show");
    }, 10);
    setTimeout(function () {
      t.classList.remove("show");
    }, 2800);
  };
})();
