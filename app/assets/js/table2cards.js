(function () {
  function onReady(fn) {
    if (document.readyState !== "loading") fn();
    else document.addEventListener("DOMContentLoaded", fn);
  }
  function toText(el) {
    return (el.textContent || "").trim();
  }
  function getIconForTable(table) {
    var t = (table.getAttribute("data-type") || "").toLowerCase();
    if (t === "clients") return '<i class="fas fa-user"></i> ';
    if (t === "produits" || t === "products")
      return '<i class="fas fa-box"></i> ';
    if (t === "ventes" || t === "sales")
      return '<i class="fas fa-shopping-cart"></i> ';
    if (t === "stock") return '<i class="fas fa-warehouse"></i> ';
    return "";
  }
  function asBadge(text) {
    var v = (text || "").toLowerCase();
    var cls = "badge-secondary";
    if (/(valide|livree|active|ok|success)/.test(v)) cls = "badge-success";
    else if (/(attente|pending|en_attente)/.test(v)) cls = "badge-warning";
    else if (/(annulee|rejete|danger|ko|error)/.test(v)) cls = "badge-danger";
    else if (/(info|nouveau|new)/.test(v)) cls = "badge-info";
    return '<span class="badge ' + cls + '">' + text + "</span>";
  }
  function buildCard(table) {
    var headers = [];
    table.querySelectorAll("thead th").forEach(function (th) {
      headers.push(toText(th));
    });
    var list = document.createElement("ul");
    list.className = "data-cards";
    table.querySelectorAll("tbody tr").forEach(function (tr) {
      var card = document.createElement("li");
      card.className = "data-card";
      var cells = [].slice.call(tr.children);
      var header = document.createElement("div");
      header.className = "dc-header";
      var title = document.createElement("div");
      title.className = "dc-title";
      // Title mapping: data-title-col index (0-based) or default first cell
      var titleIdx = parseInt(table.getAttribute("data-title-col") || "0", 10);
      if (isNaN(titleIdx) || titleIdx < 0) titleIdx = 0;
      var iconHtml = getIconForTable(table);
      var actions = document.createElement("div");
      actions.className = "dc-actions";
      // Heuristic: first cell as title if textual
      title.innerHTML = iconHtml + (toText(cells[titleIdx]) || "Item");
      // Move buttons/links from last cell to actions
      var last = cells[cells.length - 1];
      if (last) {
        last.querySelectorAll("a,button").forEach(function (btn) {
          actions.appendChild(btn.cloneNode(true));
        });
      }
      // Optional image preview for products (first cell contains <img>)
      var ttype = (table.getAttribute("data-type") || "").toLowerCase();
      if (ttype === "products" || ttype === "produits") {
        var imgCell = cells[0];
        var img = imgCell && imgCell.querySelector("img");
        if (img) {
          var wrap = document.createElement("div");
          wrap.className = "dc-thumb";
          var thumb = img.cloneNode(true);
          thumb.style.width = "100%";
          thumb.style.height = "160px";
          thumb.style.objectFit = "cover";
          thumb.style.borderRadius = "8px";
          wrap.appendChild(thumb);
          card.appendChild(wrap);
        }
      }

      header.appendChild(title);
      header.appendChild(actions);
      card.appendChild(header);
      // Rows for each pair header/value (skip last if actions already handled)
      // Optional order mapping via data-order="0,2,1,..."
      var order = (table.getAttribute("data-order") || "")
        .split(",")
        .map(function (s) {
          return parseInt(s, 10);
        })
        .filter(function (n) {
          return !isNaN(n);
        });
      var indices = order.length
        ? order
        : cells.map(function (_, i) {
            return i;
          });
      indices.forEach(function (idx) {
        // For products, skip the image column already represented as thumb
        if ((ttype === "products" || ttype === "produits") && idx === 0) return;
        var td = cells[idx];
        if (idx === cells.length - 1 && actions.childElementCount > 0) return; // actions already used
        var row = document.createElement("div");
        row.className = "dc-row";
        var lab = document.createElement("div");
        lab.className = "dc-label";
        lab.textContent = headers[idx] || "Col " + (idx + 1);
        var val = document.createElement("div");
        val.className = "dc-value";
        var raw = toText(td);
        // If cell contains a status-like word, render as badge
        if (
          /(valide|attente|rejete|livree|annulee|active|success|pending|danger|warning|info)/i.test(
            raw
          )
        ) {
          val.innerHTML = asBadge(raw);
        } else {
          val.textContent = raw;
        }
        row.appendChild(lab);
        row.appendChild(val);
        card.appendChild(row);
      });

      // Make card clickable if row had a data-href on the original tr or link in first cell
      var href = tr.getAttribute("data-href");
      if (!href) {
        var link = tr.querySelector("a[href]");
        if (link) href = link.getAttribute("href");
      }
      if (href) {
        card.classList.add("clickable");
        card.addEventListener("click", function (e) {
          // Avoid when clicking on action buttons copied to actions
          if (e.target.closest(".dc-actions a, .dc-actions button")) return;
          window.location.href = href;
        });
      }
      list.appendChild(card);
    });
    return list;
  }
  onReady(function () {
    document.querySelectorAll("table.table").forEach(function (table) {
      // Avoid duplicate conversion
      if (table.dataset.cardsBuilt === "1") return;
      if (table.classList.contains("no-cards")) return;
      var cards = buildCard(table);
      table.insertAdjacentElement("afterend", cards);
      table.dataset.cardsBuilt = "1";
    });
  });
})();
