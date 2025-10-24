<?php
require_once 'includes/config.php';
/** @var PDO $db */

// Vérifier la connexion
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$userRole = $_SESSION['user_role'];
$userName = $_SESSION['user_name'];
$depotName = $_SESSION['depot_name'] ?? 'Non assigné';

// Obtenir des stats réelles alignées avec le schéma (ventes, paiements, stock, etc.)
$stats = [];
try {
    // Stats de base via la fonction utilitaire déjà alignée au schéma
    $stats = getDashboardStats($userRole, $_SESSION['user_id'] ?? null) ?: [];

    // Petites fonctions utilitaires locales
    $scalar = function ($sql, $params = []) {
        global $db;
        try {
            $st = $db->prepare($sql);
            $st->execute($params);
            $row = $st->fetch(PDO::FETCH_NUM);
            return $row ? (float)$row[0] : 0;
        } catch (Exception $e) {
            return 0;
        }
    };

    if ($userRole === 'admin') {
        // Compléter les cartes attendues par le dashboard
        $stats['total_users']       = (int)$scalar("SELECT COUNT(*) FROM users WHERE is_active = 1");
        $stats['total_clients']     = isset($stats['clients_count']) ? (int)$stats['clients_count'] : (int)$scalar("SELECT COUNT(*) FROM clients WHERE is_active = 1");
        $stats['total_sales_today'] = (int)$scalar("SELECT COUNT(*) FROM ventes WHERE DATE(created_at) = CURDATE()");
        $stats['revenue_today']     = (int)$scalar("SELECT COALESCE(SUM(montant_total),0) FROM ventes WHERE DATE(created_at) = CURDATE()");
        $stats['total_stock']       = (int)$scalar("SELECT COUNT(*) FROM stock WHERE quantite > 0");
        // low_stock déjà fourni par getDashboardStats sous 'low_stock'
        if (!isset($stats['low_stock'])) {
            $stats['low_stock'] = (int)$scalar("SELECT COUNT(*) FROM stock WHERE quantite <= COALESCE(seuil_alerte, 0)");
        }
        $stats['total_depots']      = (int)$scalar("SELECT COUNT(*) FROM depots WHERE is_active = 1");
        if (!isset($stats['pending_payments'])) {
            $stats['pending_payments'] = (int)$scalar("SELECT COUNT(*) FROM payments WHERE statut = 'attente'");
        }
    } elseif ($userRole === 'vendeur') {
        $userId  = $_SESSION['user_id'] ?? null;
        $depotId = $_SESSION['depot_id'] ?? null;
        // Compter mes ventes (nombre) et mon CA du jour
        $stats['my_sales_today']   = (int)$scalar("SELECT COUNT(*) FROM ventes WHERE user_id = ? AND DATE(created_at) = CURDATE()", [$userId]);
        $stats['my_revenue_today'] = (int)$scalar("SELECT COALESCE(SUM(montant_total),0) FROM ventes WHERE user_id = ? AND DATE(created_at) = CURDATE()", [$userId]);
        // Mes clients (distinct via ventes ou via table clients du dépôt)
        if (!isset($stats['my_clients'])) {
            $stats['my_clients'] = (int)$scalar("SELECT COUNT(DISTINCT client_id) FROM ventes WHERE user_id = ?", [$userId]);
        }
        // Commandes en attente (statut)
        $stats['pending_orders']   = (int)$scalar("SELECT COUNT(*) FROM ventes WHERE user_id = ? AND statut = 'attente'", [$userId]);
        // Stock par dépôt
        $stats['stock_items']      = (int)$scalar("SELECT COUNT(*) FROM stock WHERE depot_id = ? AND quantite > 0", [$depotId]);
        $stats['low_stock_items']  = (int)$scalar("SELECT COUNT(*) FROM stock WHERE depot_id = ? AND quantite <= COALESCE(seuil_alerte, 0)", [$depotId]);
    } elseif ($userRole === 'livreur') {
        $userId  = $_SESSION['user_id'] ?? null;
        $depotId = $_SESSION['depot_id'] ?? null;
        // Détecter colonnes utiles
        $hasLivreurId = (int)$scalar("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ventes' AND COLUMN_NAME = 'livreur_id'") > 0;
        $hasDeliveryDate = (int)$scalar("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ventes' AND COLUMN_NAME = 'delivery_date'") > 0;
        $hasDeliveryMode = (int)$scalar("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ventes' AND COLUMN_NAME = 'delivery_mode'") > 0;

        // Filtres d'affectation: priorité au livreur_id si dispo, sinon fallback dépôt
        $filterCol = $hasLivreurId ? 'livreur_id' : 'depot_id';
        $filterVal = $hasLivreurId ? $userId : $depotId;

        // Livraisons du jour (terminées)
        if ($hasDeliveryDate) {
            $stats['deliveries_today'] = (int)$scalar("SELECT COUNT(*) FROM ventes WHERE $filterCol = ? AND statut = 'livree' AND delivery_date = CURDATE()" . ($hasDeliveryMode ? " AND delivery_mode='livraison'" : ""), [$filterVal]);
        } else {
            $stats['deliveries_today'] = (int)$scalar("SELECT COUNT(*) FROM ventes WHERE $filterCol = ? AND statut = 'livree' AND DATE(updated_at) = CURDATE()" . ($hasDeliveryMode ? " AND delivery_mode='livraison'" : ""), [$filterVal]);
        }

        // Livraisons en attente (du jour si possible)
        if ($hasDeliveryDate) {
            $stats['pending_deliveries'] = (int)$scalar("SELECT COUNT(*) FROM ventes WHERE $filterCol = ? AND statut IN ('en_attente','validee') AND delivery_date = CURDATE()" . ($hasDeliveryMode ? " AND delivery_mode='livraison'" : ""), [$filterVal]);
        } else {
            // fallback: sans date planifiée, afficher toutes les en_attente/validées
            $stats['pending_deliveries'] = (int)$scalar("SELECT COUNT(*) FROM ventes WHERE $filterCol = ? AND statut IN ('en_attente','validee')" . ($hasDeliveryMode ? " AND delivery_mode='livraison'" : ""), [$filterVal]);
        }

        // Livraisons terminées (toutes)
        $stats['completed_deliveries'] = (int)$scalar("SELECT COUNT(*) FROM ventes WHERE $filterCol = ? AND statut = 'livree'" . ($hasDeliveryMode ? " AND delivery_mode='livraison'" : ""), [$filterVal]);

        // Clients du dépôt (inchangé)
        $stats['total_clients'] = (int)$scalar("SELECT COUNT(*) FROM clients WHERE depot_id = ? AND is_active = 1", [$depotId]);
    } elseif ($userRole === 'comptable') {
        // Chiffres d'affaires
        $stats['revenue_today']   = (int)$scalar("SELECT COALESCE(SUM(montant_total),0) FROM ventes WHERE DATE(created_at) = CURDATE()");
        $stats['revenue_month']   = (int)$scalar("SELECT COALESCE(SUM(montant_total),0) FROM ventes WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
        // Paiements (recalibrés pour refléter l'encours réel)
        // En attente = nombre de ventes avec solde dû (quel que soit l'état des enregistrements payments)
        $stats['pending_payments'] = (int)$scalar("SELECT COUNT(*) FROM ventes WHERE montant_paye < montant_total");
        // Reçus (paiements validés)
        $stats['paid_payments']   = (int)$scalar("SELECT COUNT(*) FROM payments WHERE statut = 'valide'");
        // Rejetés (anciennement 'retard' inexistant dans les statuts réels)
        $stats['overdue_payments'] = (int)$scalar("SELECT COUNT(*) FROM payments WHERE statut = 'rejete'");
        // Montants utiles pour le comptable (basés sur la date de paiement déclarée)
        $stats['received_today_amount'] = (int)$scalar("SELECT COALESCE(SUM(montant),0) FROM payments WHERE statut='valide' AND date_payment = CURDATE()");
        $stats['outstanding_total_amount'] = (int)$scalar("SELECT COALESCE(SUM(montant_total - montant_paye),0) FROM ventes WHERE montant_paye < montant_total");
        $stats['outstanding_today_amount'] = (int)$scalar("SELECT COALESCE(SUM(montant_total - montant_paye),0) FROM ventes WHERE DATE(created_at)=CURDATE() AND montant_paye < montant_total");
        // Factures du jour = ventes du jour
        $stats['total_invoices']  = (int)$scalar("SELECT COUNT(*) FROM ventes WHERE DATE(created_at) = CURDATE()");
    }
} catch (Exception $e) {
    $stats = [];
}

$pageTitle = 'Dashboard - ' . ucfirst($userRole);
include 'includes/header.php';
?>

<div class="dashboard-content">
    <div class="welcome-section">
        <h1><i class="fas fa-tachometer-alt"></i> Tableau de Bord</h1>
        <p>Bienvenue, <strong><?= htmlspecialchars($userName) ?></strong>
            (<?= ucfirst($userRole) ?> - <?= htmlspecialchars($depotName) ?>)</p>
    </div>

    <?php if ($userRole === 'admin'): ?>
        <!-- Dashboard Administrateur -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['total_users'] ?? 0) ?></h3>
                    <p>Utilisateurs Actifs</p>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['total_clients'] ?? 0) ?></h3>
                    <p>Clients Enregistrés</p>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['total_sales_today'] ?? 0) ?></h3>
                    <p>Ventes Aujourd'hui</p>
                </div>
            </div>

            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['revenue_today'] ?? 0, 0, ',', ' ') ?> FCFA</h3>
                    <p>Chiffre d'Affaires Aujourd'hui</p>
                </div>
            </div>

            <div class="stat-card secondary">
                <div class="stat-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['total_stock'] ?? 0) ?></h3>
                    <p>Articles en Stock</p>
                </div>
            </div>

            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['low_stock'] ?? 0) ?></h3>
                    <p>Stock Faible</p>
                </div>
            </div>

            <div class="stat-card purple">
                <div class="stat-icon">
                    <i class="fas fa-store"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['total_depots'] ?? 0) ?></h3>
                    <p>Dépôts Actifs</p>
                </div>
            </div>

            <div class="stat-card orange">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['pending_payments'] ?? 0) ?></h3>
                    <p>Paiements en Attente</p>
                </div>
            </div>
        </div>

        <div class="dashboard-widgets">
            <div class="widget">
                <h3><i class="fas fa-chart-line"></i> Actions Rapides</h3>
                <div class="quick-actions">
                    <a href="users.php" class="action-btn primary">
                        <i class="fas fa-user-plus"></i> Gérer Utilisateurs
                    </a>
                    <a href="depots.php" class="action-btn success">
                        <i class="fas fa-store"></i> Gérer Dépôts
                    </a>
                    <a href="stock.php" class="action-btn warning">
                        <i class="fas fa-boxes"></i> Gérer Stock
                    </a>
                    <a href="reports.php" class="action-btn info">
                        <i class="fas fa-chart-bar"></i> Rapports
                    </a>
                </div>
            </div>
        </div>

    <?php elseif ($userRole === 'vendeur'): ?>
        <!-- Dashboard Vendeur -->
        <div class="stats-grid">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['my_sales_today'] ?? 0) ?></h3>
                    <p>Mes Ventes Aujourd'hui</p>
                </div>
            </div>

            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['my_revenue_today'] ?? 0, 0, ',', ' ') ?> FCFA</h3>
                    <p>Mon CA Aujourd'hui</p>
                </div>
            </div>

            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['my_clients'] ?? 0) ?></h3>
                    <p>Mes Clients</p>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['pending_orders'] ?? 0) ?></h3>
                    <p>Commandes en Attente</p>
                </div>
            </div>

            <div class="stat-card secondary">
                <div class="stat-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['stock_items'] ?? 0) ?></h3>
                    <p>Articles Disponibles</p>
                </div>
            </div>

            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['low_stock_items'] ?? 0) ?></h3>
                    <p>Stock Faible</p>
                </div>
            </div>
        </div>

        <div class="dashboard-widgets">
            <div class="widget">
                <h3><i class="fas fa-tools"></i> Actions Rapides</h3>
                <div class="quick-actions">
                    <a href="sales.php?action=new" class="action-btn success">
                        <i class="fas fa-plus-circle"></i> Nouvelle Vente
                    </a>
                    <a href="clients.php" class="action-btn primary">
                        <i class="fas fa-users"></i> Gérer Clients
                    </a>
                    <a href="stock.php" class="action-btn warning">
                        <i class="fas fa-boxes"></i> Consulter Stock
                    </a>
                    <a href="sales.php" class="action-btn info">
                        <i class="fas fa-list"></i> Mes Ventes
                    </a>
                </div>
            </div>
        </div>

    <?php elseif ($userRole === 'livreur'): ?>
        <!-- Dashboard Livreur -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['deliveries_today'] ?? 0) ?></h3>
                    <p>Livraisons Aujourd'hui</p>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['pending_deliveries'] ?? 0) ?></h3>
                    <p>Livraisons en Attente</p>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['completed_deliveries'] ?? 0) ?></h3>
                    <p>Livraisons Terminées</p>
                </div>
            </div>

            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['total_clients'] ?? 0) ?></h3>
                    <p>Clients du Dépôt</p>
                </div>
            </div>
        </div>

        <div class="dashboard-widgets">
            <div class="widget">
                <h3><i class="fas fa-route"></i> Actions Rapides</h3>
                <div class="quick-actions">
                    <a href="<?= BASE_URL ?>/web_admin/deliveries.php?status=pending" class="action-btn warning">
                        <i class="fas fa-truck-loading"></i> Livraisons en Attente
                    </a>
                    <a href="<?= BASE_URL ?>/web_admin/deliveries.php?action=new" class="action-btn success">
                        <i class="fas fa-plus-circle"></i> Nouvelle Livraison
                    </a>
                    <a href="<?= BASE_URL ?>/web_admin/deliveries.php" class="action-btn primary">
                        <i class="fas fa-list"></i> Toutes les Livraisons
                    </a>
                    <a href="<?= BASE_URL ?>/web_admin/clients.php" class="action-btn info">
                        <i class="fas fa-map-marked-alt"></i> Localiser Clients
                    </a>
                </div>
            </div>
        </div>

    <?php elseif ($userRole === 'comptable'): ?>
        <!-- Dashboard Comptable -->
        <div class="stats-grid">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['revenue_today'] ?? 0, 0, ',', ' ') ?> FCFA</h3>
                    <p>CA Aujourd'hui</p>
                </div>
            </div>

            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['revenue_month'] ?? 0, 0, ',', ' ') ?> FCFA</h3>
                    <p>CA ce Mois</p>
                </div>
            </div>

            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['received_today_amount'] ?? 0, 0, ',', ' ') ?> FCFA</h3>
                    <p>Encaissements Reçus Aujourd'hui</p>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['pending_payments'] ?? 0) ?></h3>
                    <p>Paiements en Attente</p>
                </div>
            </div>

            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['paid_payments'] ?? 0) ?></h3>
                    <p>Paiements Reçus</p>
                </div>
            </div>

            <div class="stat-card secondary">
                <div class="stat-icon">
                    <i class="fas fa-hand-holding"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['outstanding_total_amount'] ?? 0, 0, ',', ' ') ?> FCFA</h3>
                    <p>À Recevoir (Total)</p>
                </div>
            </div>

            <div class="stat-card secondary">
                <div class="stat-icon">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['total_invoices'] ?? 0) ?></h3>
                    <p>Factures Aujourd'hui</p>
                </div>
            </div>

            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['overdue_payments'] ?? 0) ?></h3>
                    <p>Paiements Rejetés</p>
                </div>
            </div>
        </div>

        <div class="dashboard-widgets">
            <div class="widget">
                <h3><i class="fas fa-calculator"></i> Actions Rapides</h3>
                <div class="quick-actions">
                    <a href="payments.php" class="action-btn primary">
                        <i class="fas fa-credit-card"></i> Gérer Paiements
                    </a>
                    <a href="invoices.php" class="action-btn success">
                        <i class="fas fa-file-invoice-dollar"></i> Factures
                    </a>
                    <a href="reports.php" class="action-btn info">
                        <i class="fas fa-chart-bar"></i> Rapports Financiers
                    </a>
                    <a href="accounting.php" class="action-btn warning">
                        <i class="fas fa-balance-scale"></i> Comptabilité
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .dashboard-content {
        padding: 2rem;
    }

    .welcome-section {
        background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
        color: #333;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        text-align: center;
    }

    .welcome-section h1 {
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: transform 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
    }

    .stat-card.primary .stat-icon {
        background: #007bff;
    }

    .stat-card.success .stat-icon {
        background: #28a745;
    }

    .stat-card.warning .stat-icon {
        background: #ffc107;
        color: #333;
    }

    .stat-card.danger .stat-icon {
        background: #dc3545;
    }

    .stat-card.info .stat-icon {
        background: #17a2b8;
    }

    .stat-card.secondary .stat-icon {
        background: #6c757d;
    }

    .stat-card.purple .stat-icon {
        background: #6f42c1;
    }

    .stat-card.orange .stat-icon {
        background: #fd7e14;
    }

    .stat-info h3 {
        font-size: 2rem;
        margin: 0;
        color: #333;
    }

    .stat-info p {
        margin: 0;
        color: #666;
        font-weight: 500;
    }

    .dashboard-widgets {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 1.5rem;
    }

    .widget {
        background: white;
        padding: 1.5rem;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .widget h3 {
        margin-bottom: 1rem;
        color: #333;
        font-size: 1.2rem;
    }

    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }

    .action-btn {
        padding: 1rem;
        border-radius: 10px;
        text-decoration: none;
        color: white;
        text-align: center;
        font-weight: 500;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        color: white;
        text-decoration: none;
    }

    .action-btn.primary {
        background: #007bff;
    }

    .action-btn.success {
        background: #28a745;
    }

    .action-btn.warning {
        background: #ffc107;
        color: #333;
    }

    .action-btn.danger {
        background: #dc3545;
    }

    .action-btn.info {
        background: #17a2b8;
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .dashboard-widgets {
            grid-template-columns: 1fr;
        }

        .quick-actions {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>