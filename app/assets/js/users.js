(function () {
  function getConfig() {
    const el = document.getElementById("users-config");
    if (!el) return { ROLE_DEFAULTS: {}, USER_OVERRIDES: {} };
    try {
      return {
        ROLE_DEFAULTS: JSON.parse(
          el.getAttribute("data-role-defaults") || "{}"
        ),
        USER_OVERRIDES: JSON.parse(
          el.getAttribute("data-user-overrides") || "{}"
        ),
      };
    } catch (e) {
      return { ROLE_DEFAULTS: {}, USER_OVERRIDES: {} };
    }
  }

  const { ROLE_DEFAULTS, USER_OVERRIDES } = getConfig();

  function openEditModal(data) {
    const id = data.id;
    document.getElementById("edit_id").value = id;
    document.getElementById("edit_full").value = data.full || "";
    document.getElementById("edit_email").value = data.email || "";
    document.getElementById("edit_username").value = data.username || "";
    document.getElementById("edit_role").value = data.role || "vendeur";
    document.getElementById("edit_depot").value = data.depotId || "";

    const modalEl = document.getElementById("editUserModal");
    const checks = modalEl.querySelectorAll(".perm-item");
    const overrides = USER_OVERRIDES[id] || {};
    const defaults = new Set(ROLE_DEFAULTS[data.role] || []);
    checks.forEach((chk) => {
      const k = chk.getAttribute("data-perm");
      if (overrides.hasOwnProperty(k)) {
        chk.checked = overrides[k] === 1;
      } else {
        chk.checked = defaults.has(k);
      }
    });

    if (window.bootstrap && window.bootstrap.Modal)
      new bootstrap.Modal(modalEl).show();
    else if (window.__fallbackShowModal)
      window.__fallbackShowModal("editUserModal");
    else {
      modalEl.classList.add("show");
      modalEl.style.display = "block";
    }
  }

  // Bind edit buttons (no inline JS)
  document.addEventListener("click", function (e) {
    const btn = e.target.closest(".btn-edit");
    if (!btn) return;
    e.preventDefault();
    const data = {
      id: parseInt(btn.getAttribute("data-id"), 10),
      full: btn.getAttribute("data-full"),
      email: btn.getAttribute("data-email"),
      username: btn.getAttribute("data-username"),
      role: btn.getAttribute("data-role"),
      depotId: btn.getAttribute("data-depot-id"),
    };
    openEditModal(data);
  });

  // In create modal, pre-check defaults when role changes
  document.addEventListener("change", function (e) {
    if (
      e.target &&
      e.target.closest("#addUserModal") &&
      e.target.name === "role"
    ) {
      const modal = e.target.closest("#addUserModal");
      const defaults = new Set(ROLE_DEFAULTS[e.target.value] || []);
      modal
        .querySelectorAll('input[type=checkbox][name^="permissions["]')
        .forEach((chk) => {
          const match = chk.name.match(/permissions\[(.+)\]/);
          if (match) chk.checked = defaults.has(match[1]);
        });
    }
  });
})();
