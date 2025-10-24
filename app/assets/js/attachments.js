"use strict";
(function () {
  const BASE = document.querySelector('meta[name="base-url"]')?.content || "";
  const modalEl = document.getElementById("attachModal");
  const entityEl = document.getElementById("att_entity");
  const idEl = document.getElementById("att_entity_id");
  const listEl = document.getElementById("att_list");

  async function loadAttachments(entity, id) {
    if (!listEl) return;
    try {
      const resp = await fetch(
        `${BASE}/app/export/attachments_list.php?entity=${encodeURIComponent(
          entity
        )}&id=${id}`
      );
      const html = await resp.text();
      listEl.innerHTML = html;
    } catch (e) {
      listEl.innerHTML = "<em>Erreur de chargement.</em>";
    }
  }

  document.addEventListener("click", function (e) {
    const btn = e.target.closest && e.target.closest(".btn-attach");
    if (btn) {
      const entity = btn.getAttribute("data-attach-entity");
      const id = btn.getAttribute("data-attach-id");
      if (entityEl) entityEl.value = entity || "";
      if (idEl) idEl.value = id || "";
      loadAttachments(entity, id);
      if (window.bootstrap && window.bootstrap.Modal)
        new bootstrap.Modal(modalEl).show();
      else if (window.__fallbackShowModal)
        window.__fallbackShowModal("attachModal");
    }
  });

  // Délégation pour suppression d'une pièce (CSP-safe)
  if (listEl) {
    listEl.addEventListener("click", async function (e) {
      const delBtn =
        e.target.closest && e.target.closest("[data-delete-attachment]");
      if (!delBtn) return;
      const attId = delBtn.getAttribute("data-delete-attachment");
      if (!attId) return;
      if (!confirm("Supprimer cette pièce ?")) return;
      try {
        await fetch(`${BASE}/app/export/attachments_delete.php?id=${attId}`, {
          method: "POST",
        });
        // Recharger la liste
        const entity = entityEl?.value;
        const id = idEl?.value;
        loadAttachments(entity, id);
      } catch (e) {
        // noop
      }
    });
  }
})();
