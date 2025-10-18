/**
 * JavaScript principal pour l'interface d'administration HILL EMBALLAGE
 * Gestion de l'authentification, navigation, API calls et interactions UI
 */

class HillAdmin {
  constructor() {
    this.baseURL = "http://localhost/hill/backend/requests";
    this.token = localStorage.getItem("hill_token");
    this.user = JSON.parse(localStorage.getItem("hill_user") || "{}");

    this.init();
  }

  init() {
    this.checkAuth();
    this.setupEventListeners();
    this.setupNavigation();
    this.loadInitialData();
  }

  // === AUTHENTIFICATION ===
  async login(username, password) {
    try {
      const response = await fetch(`${this.baseURL}/auth.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "login",
          username: username,
          password: password,
        }),
      });

      const data = await response.json();

      if (response.ok) {
        this.token = data.token;
        this.user = data.user;

        localStorage.setItem("hill_token", this.token);
        localStorage.setItem("hill_user", JSON.stringify(this.user));

        this.showAlert("success", "Connexion réussie !");
        this.redirectToDashboard();

        return true;
      } else {
        this.showAlert("danger", data.message);
        return false;
      }
    } catch (error) {
      console.error("Erreur de connexion:", error);
      this.showAlert("danger", "Erreur de connexion au serveur.");
      return false;
    }
  }

  logout() {
    localStorage.removeItem("hill_token");
    localStorage.removeItem("hill_user");
    window.location.href = "login.php";
  }

  checkAuth() {
    const currentPage = window.location.pathname.split("/").pop();

    if (!this.token && currentPage !== "login.php") {
      window.location.href = "login.php";
    } else if (this.token && currentPage === "login.php") {
      window.location.href = "dashboard.php";
    }
  }

  redirectToDashboard() {
    setTimeout(() => {
      window.location.href = "dashboard.php";
    }, 1000);
  }

  // === API CALLS ===
  async apiCall(endpoint, method = "GET", data = null) {
    const options = {
      method: method,
      headers: {
        "Content-Type": "application/json",
      },
    };

    if (data && method !== "GET") {
      options.body = JSON.stringify(data);
    }

    try {
      const response = await fetch(`${this.baseURL}/${endpoint}`, options);
      const result = await response.json();

      if (!response.ok) {
        throw new Error(result.message || "Erreur API");
      }

      return result;
    } catch (error) {
      console.error("Erreur API:", error);
      this.showAlert("danger", error.message);
      throw error;
    }
  }

  // === CLIENTS ===
  async loadClients() {
    try {
      this.showLoading("clients-table");
      const clients = await this.apiCall("clients.php");
      this.renderClientsTable(clients);
    } catch (error) {
      this.hideLoading("clients-table");
    }
  }

  renderClientsTable(clients) {
    const tbody = document.getElementById("clients-table");
    if (!tbody) return;

    tbody.innerHTML = clients
      .map(
        (client) => `
            <tr>
                <td>${client.code_client}</td>
                <td>${client.nom} ${client.prenoms || ""}</td>
                <td>${client.telephone || "-"}</td>
                <td>${client.zone || "-"}</td>
                <td>
                    <span class="badge badge-${
                      client.type_client === "entreprise" ? "info" : "success"
                    }">
                        ${client.type_client}
                    </span>
                </td>
                <td class="text-right">${this.formatMoney(
                  client.solde_credit
                )}</td>
                <td class="text-center">${client.points_fidelite}</td>
                <td>
                    <button class="btn btn-sm btn-secondary" onclick="app.editClient(${
                      client.id
                    })">
                        Modifier
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="app.deleteClient(${
                      client.id
                    })">
                        Supprimer
                    </button>
                </td>
            </tr>
        `
      )
      .join("");

    this.hideLoading("clients-table");
  }

  async saveClient(formData) {
    try {
      const method = formData.id ? "PUT" : "POST";
      const endpoint = formData.id
        ? `clients.php?id=${formData.id}`
        : "clients.php";

      const result = await this.apiCall(endpoint, method, formData);

      this.showAlert("success", result.message);
      this.closeModal("client-modal");
      this.loadClients();

      return result;
    } catch (error) {
      return false;
    }
  }

  // === VENTES ===
  async loadSales() {
    try {
      this.showLoading("sales-table");
      const sales = await this.apiCall("sales.php");
      this.renderSalesTable(sales);
    } catch (error) {
      this.hideLoading("sales-table");
    }
  }

  renderSalesTable(sales) {
    const tbody = document.getElementById("sales-table");
    if (!tbody) return;

    tbody.innerHTML = sales
      .map(
        (sale) => `
            <tr>
                <td>${sale.numero_vente}</td>
                <td>${sale.client_nom} ${sale.client_prenoms || ""}</td>
                <td>${sale.depot_nom}</td>
                <td>${sale.vendeur_nom}</td>
                <td class="text-right">${this.formatMoney(
                  sale.montant_total
                )}</td>
                <td class="text-right">${this.formatMoney(
                  sale.montant_restant
                )}</td>
                <td>
                    <span class="badge badge-${this.getSaleStatusClass(
                      sale.statut
                    )}">
                        ${this.getSaleStatusText(sale.statut)}
                    </span>
                </td>
                <td>${this.formatDate(sale.created_at)}</td>
                <td>
                    <button class="btn btn-sm btn-secondary" onclick="app.viewSale(${
                      sale.id
                    })">
                        Voir
                    </button>
                </td>
            </tr>
        `
      )
      .join("");

    this.hideLoading("sales-table");
  }

  // === STOCK ===
  async loadStock() {
    try {
      this.showLoading("stock-table");
      const stock = await this.apiCall("stock.php");
      this.renderStockTable(stock);
    } catch (error) {
      this.hideLoading("stock-table");
    }
  }

  renderStockTable(stock) {
    const tbody = document.getElementById("stock-table");
    if (!tbody) return;

    tbody.innerHTML = stock
      .map(
        (item) => `
            <tr class="${
              item.niveau_stock === "critique"
                ? "table-danger"
                : item.niveau_stock === "bas"
                ? "table-warning"
                : ""
            }">
                <td>${item.depot_nom}</td>
                <td>${item.product_nom}</td>
                <td>${item.code_produit}</td>
                <td class="text-center">${item.quantite_disponible}</td>
                <td class="text-center">${item.seuil_alerte}</td>
                <td class="text-center">
                    <span class="badge badge-${this.getStockStatusClass(
                      item.niveau_stock
                    )}">
                        ${item.niveau_stock}
                    </span>
                </td>
                <td class="text-right">${this.formatMoney(
                  item.prix_unitaire
                )}</td>
                <td>
                    <button class="btn btn-sm btn-secondary" onclick="app.adjustStock(${
                      item.depot_id
                    }, ${item.product_id})">
                        Ajuster
                    </button>
                </td>
            </tr>
        `
      )
      .join("");

    this.hideLoading("stock-table");
  }

  // === PAIEMENTS ===
  async loadPayments() {
    try {
      this.showLoading("payments-table");
      const payments = await this.apiCall("payments.php");
      this.renderPaymentsTable(payments);
    } catch (error) {
      this.hideLoading("payments-table");
    }
  }

  renderPaymentsTable(payments) {
    const tbody = document.getElementById("payments-table");
    if (!tbody) return;

    tbody.innerHTML = payments
      .map(
        (payment) => `
            <tr>
                <td>${payment.numero_paiement}</td>
                <td>${payment.numero_vente}</td>
                <td>${payment.client_nom} ${payment.client_prenoms || ""}</td>
                <td class="text-right">${this.formatMoney(payment.montant)}</td>
                <td>
                    <span class="badge badge-info">
                        ${payment.mode_paiement}
                    </span>
                </td>
                <td>${payment.receveur_nom}</td>
                <td>${this.formatDate(payment.created_at)}</td>
                <td>
                    <button class="btn btn-sm btn-secondary" onclick="app.viewPayment(${
                      payment.id
                    })">
                        Voir
                    </button>
                </td>
            </tr>
        `
      )
      .join("");

    this.hideLoading("payments-table");
  }

  // === DASHBOARD ===
  async loadDashboardStats() {
    try {
      // Charger les statistiques de différentes sources
      const [clientStats, salesStats, stockStats] = await Promise.all([
        this.apiCall("clients.php?stats=1"),
        this.apiCall("sales.php?stats=1"),
        this.apiCall("stock.php?stats=1"),
      ]);

      this.renderDashboardStats(clientStats, salesStats, stockStats);
    } catch (error) {
      console.error("Erreur chargement dashboard:", error);
    }
  }

  renderDashboardStats(clientStats, salesStats, stockStats) {
    // Mise à jour des cartes de statistiques
    if (document.getElementById("total-clients")) {
      document.getElementById("total-clients").textContent =
        clientStats.total_clients || 0;
    }

    if (document.getElementById("total-sales")) {
      document.getElementById("total-sales").textContent =
        salesStats.total_ventes || 0;
    }

    if (document.getElementById("total-revenue")) {
      document.getElementById("total-revenue").textContent = this.formatMoney(
        salesStats.chiffre_affaires || 0
      );
    }

    if (document.getElementById("stock-alerts")) {
      document.getElementById("stock-alerts").textContent =
        stockStats.alertes_critiques || 0;
    }
  }

  // === UI HELPERS ===
  showAlert(type, message) {
    const alertsContainer =
      document.getElementById("alerts-container") || document.body;

    const alert = document.createElement("div");
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
            <span>${message}</span>
            <button type="button" class="btn-close" onclick="this.parentElement.remove()">×</button>
        `;

    alertsContainer.appendChild(alert);

    setTimeout(() => {
      if (alert.parentElement) {
        alert.remove();
      }
    }, 5000);
  }

  showLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
      element.innerHTML = `
                <tr>
                    <td colspan="10" class="text-center">
                        <div class="loading">
                            <div class="spinner"></div>
                            <span class="ml-2">Chargement...</span>
                        </div>
                    </td>
                </tr>
            `;
    }
  }

  hideLoading(elementId) {
    // Le contenu sera remplacé par les données réelles
  }

  openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add("show");
      document.body.style.overflow = "hidden";
    }
  }

  closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.remove("show");
      document.body.style.overflow = "auto";
    }
  }

  // === FORMATTERS ===
  formatMoney(amount) {
    return new Intl.NumberFormat("fr-CI", {
      style: "currency",
      currency: "XOF",
      minimumFractionDigits: 0,
    }).format(amount || 0);
  }

  formatDate(dateString) {
    return new Date(dateString).toLocaleDateString("fr-CI", {
      year: "numeric",
      month: "short",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  getSaleStatusClass(status) {
    const classes = {
      en_cours: "warning",
      partiel: "info",
      solde: "success",
      annule: "danger",
    };
    return classes[status] || "secondary";
  }

  getSaleStatusText(status) {
    const texts = {
      en_cours: "En cours",
      partiel: "Partiel",
      solde: "Soldé",
      annule: "Annulé",
    };
    return texts[status] || status;
  }

  getStockStatusClass(level) {
    const classes = {
      critique: "danger",
      bas: "warning",
      normal: "success",
    };
    return classes[level] || "secondary";
  }

  // === EVENT LISTENERS ===
  setupEventListeners() {
    // Formulaire de connexion
    const loginForm = document.getElementById("login-form");
    if (loginForm) {
      loginForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const formData = new FormData(loginForm);
        await this.login(formData.get("username"), formData.get("password"));
      });
    }

    // Formulaire client
    const clientForm = document.getElementById("client-form");
    if (clientForm) {
      clientForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const formData = new FormData(clientForm);
        const data = Object.fromEntries(formData);
        await this.saveClient(data);
      });
    }

    // Bouton de déconnexion
    document.addEventListener("click", (e) => {
      if (e.target.matches("[data-logout]")) {
        e.preventDefault();
        if (confirm("Êtes-vous sûr de vouloir vous déconnecter ?")) {
          this.logout();
        }
      }
    });

    // Fermeture des modals
    document.addEventListener("click", (e) => {
      if (e.target.matches(".modal-overlay")) {
        const modal = e.target.closest(".modal-overlay");
        if (modal) {
          this.closeModal(modal.id);
        }
      }
    });
  }

  setupNavigation() {
    // Marquer l'élément actif dans la navigation
    const currentPage = window.location.pathname
      .split("/")
      .pop()
      .replace(".html", "")
      .replace(".php", "");
    const navItems = document.querySelectorAll(".nav-item");

    navItems.forEach((item) => {
      const href = item.getAttribute("href");
      if (href && href.includes(currentPage)) {
        item.classList.add("active");
      }
    });
  }

  loadInitialData() {
    const currentPage = window.location.pathname.split("/").pop();

    switch (currentPage) {
      case "dashboard.php":
      case "index.php":
      case "":
        this.loadDashboardStats();
        break;
      case "clients.php":
        this.loadClients();
        break;
      case "sales.php":
        this.loadSales();
        break;
      case "stock.php":
        this.loadStock();
        break;
      case "payments.php":
        this.loadPayments();
        break;
    }
  }
}

// Initialisation de l'application
const app = new HillAdmin();
