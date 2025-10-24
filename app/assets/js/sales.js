"use strict";
(function () {
  // Lire les produits depuis un <script type="application/json" id="products-data">
  let PRODUCTS = [];
  (function () {
    try {
      const el = document.getElementById("products-data");
      if (el && el.textContent) {
        PRODUCTS = JSON.parse(el.textContent);
      }
    } catch (e) {
      PRODUCTS = [];
    }
  })();

  const itemsTable = document.getElementById("itemsTable");
  const addBtn = document.getElementById("addItemBtn");
  const saleTotalEl = document.getElementById("saleTotal");

  function optionList() {
    return ['<option value="">-- produit --</option>']
      .concat(
        PRODUCTS.map(
          (p) =>
            `<option value="${p.id}" data-price="${
              p.prix_unitaire
            }">${escapeHtml(p.nom)}</option>`
        )
      )
      .join("");
  }

  function escapeHtml(str) {
    return String(str ?? "").replace(
      /[&<>"]+/g,
      (s) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[s])
    );
  }

  function addItemRow() {
    if (!itemsTable) return;
    const tbody = itemsTable.querySelector("tbody");
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>
        <select class="form-select prod">
          ${optionList()}
        </select>
        <input type="hidden" name="items[][product_id]" class="hid-prod" />
        <input type="hidden" name="items[][price]" class="hid-price" />
        <input type="hidden" name="items[][qty]" class="hid-qty" />
      </td>
      <td><input class="form-control price" type="number" step="0.01" value="0"/></td>
      <td><input class="form-control qty" type="number" step="0.01" value="1"/></td>
      <td class="montant">0</td>
      <td><button type="button" class="btn btn-outline-danger btn-sm row-remove" title="Retirer">&times;</button></td>
    `;
    tbody.appendChild(tr);
  }

  function recalcRow(tr) {
    const price = parseFloat(tr.querySelector(".price").value || "0");
    const qty = parseFloat(tr.querySelector(".qty").value || "0");
    const m = price * qty || 0;
    tr.querySelector(".montant").innerText = m.toFixed(0);
    const prod = tr.querySelector(".prod").value;
    tr.querySelector(".hid-prod").value = prod;
    tr.querySelector(".hid-price").value = price;
    tr.querySelector(".hid-qty").value = qty;
    recalcTotal();
  }

  function recalcTotal() {
    let t = 0;
    itemsTable.querySelectorAll("tbody tr").forEach((tr) => {
      t += parseFloat(tr.querySelector(".montant").innerText || "0");
    });
    if (saleTotalEl) saleTotalEl.innerText = t.toLocaleString("fr-FR");
  }

  function syncPrice(sel) {
    const opt = sel.selectedOptions[0];
    const price = parseFloat(opt?.dataset?.price || "0");
    const tr = sel.closest("tr");
    tr.querySelector(".price").value = isFinite(price) ? price : 0;
    recalcRow(tr);
  }

  if (addBtn) {
    addBtn.addEventListener("click", addItemRow);
  }

  if (itemsTable) {
    itemsTable.addEventListener("change", function (e) {
      const sel = e.target.closest && e.target.closest("select.prod");
      if (sel) {
        syncPrice(sel);
      }
    });
    itemsTable.addEventListener("input", function (e) {
      const input = e.target;
      if (
        input.classList.contains("price") ||
        input.classList.contains("qty")
      ) {
        const tr = input.closest("tr");
        recalcRow(tr);
      }
    });
    itemsTable.addEventListener("click", function (e) {
      const btn = e.target.closest && e.target.closest(".row-remove");
      if (btn) {
        const tr = btn.closest("tr");
        tr.remove();
        recalcTotal();
      }
    });
  }
})();
