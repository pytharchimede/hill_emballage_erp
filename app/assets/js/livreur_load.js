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
        <div class="col-md-5">
          <label class="form-label">Produit</label>
          <select class="form-select" name="p_${idx}_prod" data-prod-select required></select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Quantité</label>
          <input class="form-control" name="p_${idx}_q" data-qty-input type="number" step="0.01" required />
        </div>
        <div class="col-md-3">
          <label class="form-label">Client</label>
          <select class="form-select" name="p_${idx}_client" data-client-select>
            <option value="">— Sans client —</option>
          </select>
        </div>
        <div class="col-md-1">
          <button class="btn btn-danger" type="button" data-remove>&times;</button>
        </div>
      </div>`;
    itemsArea.appendChild(row);
    // Peupler les options depuis le cache caché
    const prodSelect = row.querySelector("[data-prod-select]");
    const clientSelect = row.querySelector("[data-client-select]");
    const prodCache = document.getElementById("product_options");
    const clientCache = document.getElementById("client_options");
    if (prodSelect && prodCache) {
      prodSelect.innerHTML = prodCache.innerHTML;
    }
    if (clientSelect && clientCache) {
      // garder l'option vide puis concaténer les options clients
      const empty = clientSelect.innerHTML;
      clientSelect.innerHTML = empty + clientCache.innerHTML;
    }
  }

  function collectItems() {
    const rows = [...itemsArea.querySelectorAll("[data-row]")];
    const items = [];
    for (const r of rows) {
      const pid = (r.querySelector("[data-prod-select]")?.value || "").trim();
      const q = (r.querySelector("[data-qty-input]")?.value || "").trim();
      const cid = (r.querySelector("[data-client-select]")?.value || "").trim();
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
