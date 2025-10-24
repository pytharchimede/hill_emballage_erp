(function () {
  // CSP-safe: no inline handlers. Attach after DOM ready (defer attribute ensures this runs after parse)
  const form = document.getElementById("load-form");
  const itemsArea = document.getElementById("items_area");
  const addBtn = document.querySelector("[data-add-item]");
  const itemsJson = document.getElementById("items_json");

  if (!form || !itemsArea || !addBtn || !itemsJson) return;

  function addRow() {
    const idx = itemsArea.children.length;
    const row = document.createElement("div");
    row.className = "col-12";
    row.innerHTML = `
      <div class="row g-2 align-items-end" data-row>
        <div class="col-md-4">
          <label class="form-label">Produit</label>
          <input class="form-control" name="p_${idx}_id" required placeholder="ID produit" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Quantité</label>
          <input class="form-control" name="p_${idx}_q" type="number" step="0.01" required />
        </div>
        <div class="col-md-3">
          <label class="form-label">Client (ID)</label>
          <input class="form-control" name="p_${idx}_client" list="clients_datalist" placeholder="ID client" />
        </div>
        <div class="col-md-2">
          <button class="btn btn-danger" type="button" data-remove>&times;</button>
        </div>
      </div>`;
    itemsArea.appendChild(row);
  }

  function collectItems() {
    const rows = [...itemsArea.querySelectorAll("[data-row]")];
    const items = [];
    for (const r of rows) {
      const pid = (r.querySelector('input[name$="_id"]')?.value || "").trim();
      const q = (r.querySelector('input[name$="_q"]')?.value || "").trim();
      const cid = (
        r.querySelector('input[name$="_client"]')?.value || ""
      ).trim();
      if (pid && q) {
        const it = {
          produit_id: parseInt(pid, 10),
          quantite: parseFloat(q),
        };
        if (cid) {
          const n = parseInt(cid, 10);
          if (!Number.isNaN(n)) it.client_id = n;
        }
        items.push(it);
      }
    }
    return items;
  }

  addBtn.addEventListener("click", function () {
    addRow();
  });

  itemsArea.addEventListener("click", function (e) {
    const btn = e.target.closest("[data-remove]");
    if (btn) {
      const row = btn.closest(".col-12");
      if (row) row.remove();
    }
  });

  form.addEventListener("submit", function (e) {
    const items = collectItems();
    itemsJson.value = JSON.stringify(items);
    if (!items.length) {
      e.preventDefault();
      alert("Veuillez ajouter au moins un produit.");
      return false;
    }
  });

  // Start with one row to avoid empty-submit confusion
  addRow();
})();
