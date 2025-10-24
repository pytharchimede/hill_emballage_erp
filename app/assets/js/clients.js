// Clients page JS (CSP-compliant)
(function () {
  "use strict";

  const modals = {};

  function openModal(modalId) {
    const modalEl = document.getElementById(modalId);
    if (!modalEl) return;
    if (!window.bootstrap || !window.bootstrap.Modal) {
      if (window.__fallbackShowModal) {
        window.__fallbackShowModal(modalId);
        return;
      }
      modalEl.classList.add("show");
      modalEl.style.display = "block";
      return;
    }
    if (!modals[modalId]) {
      modals[modalId] = new bootstrap.Modal(modalEl, { backdrop: "static" });
    }
    modals[modalId].show();
  }

  function fillEditFormFromDataset(btn) {
    const d = btn.dataset;
    const setVal = (id, val) => {
      const el = document.getElementById(id);
      if (el) el.value = val ?? "";
    };

    setVal("edit_id", d.id);
    setVal("edit_name", d.name);
    setVal("edit_email", d.email);
    setVal("edit_phone", d.phone);
    setVal("edit_address", d.address);
    setVal("edit_city", d.city);
    setVal("edit_postal_code", d.postalCode);
    setVal("edit_company", d.company);
    if (d.clientType) setVal("edit_client_type", d.clientType);

    // Livreur (optionnel)
    const livSel = document.getElementById("edit_livreur");
    if (livSel) {
      const lid = d.livreurId || "";
      if (
        lid !== "" &&
        Array.from(livSel.options).some((o) => o.value === String(lid))
      ) {
        livSel.value = String(lid);
      } else {
        livSel.value = "";
      }
    }

    // Affichage du livreur actuel
    const livInfo = document.getElementById("edit_livreur_current");
    if (livInfo) {
      const name = d.livreurNom || "";
      livInfo.textContent = name
        ? `Livreur actuel : ${name}`
        : "Livreur actuel : Aucun";
    }
  }

  function attachEvents() {
    // Edit buttons
    document.querySelectorAll(".btn-edit-client").forEach((btn) => {
      btn.addEventListener("click", () => {
        fillEditFormFromDataset(btn);
        openModal("editClientModal");
      });
    });

    // Delete buttons
    document.querySelectorAll(".btn-delete-client").forEach((btn) => {
      btn.addEventListener("click", () => {
        const id = btn.getAttribute("data-id");
        const name = btn.getAttribute("data-name");
        if (
          confirm(`Êtes-vous sûr de vouloir supprimer le client "${name}" ?`)
        ) {
          const form = document.createElement("form");
          form.method = "POST";
          form.innerHTML =
            '<input type="hidden" name="action" value="delete">' +
            `<input type="hidden" name="id" value="${id}">`;
          document.body.appendChild(form);
          form.submit();
        }
      });
    });

    // Select all for bulk assign
    const all = document.getElementById("select_all_clients");
    if (all) {
      all.addEventListener("change", function () {
        document.querySelectorAll(".client-checkbox").forEach((cb) => {
          cb.checked = all.checked;
        });
      });
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", attachEvents);
  } else {
    attachEvents();
  }
})();
