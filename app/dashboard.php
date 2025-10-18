<?php
require_once 'includes/config.php';

// Vérifier la connexion
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$userRole = $_SESSION['user_role'];
$userName = $_SESSION['user_name'];
$depotName = $_SESSION['depot_name'] ?? 'Non assigné';

// Obtenir des statistiques selon le rôle
$stats = [];

try {
    switch ($userRole) {
        case 'admin':
            // Statistiques globales pour admin
            $statsQueries = [
                'total_users' => "SELECT COUNT(*) as count FROM users WHERE is_active = 1",
                'total_clients' => "SELECT COUNT(*) as count FROM clients WHERE is_active = 1",
                'total_sales_today' => "SELECT COUNT(*) as count FROM sales WHERE DATE(created_at) = CURDATE()",
                'revenue_today' => "SELECT COALESCE(SUM(total_amount), 0) as amount FROM sales WHERE DATE(created_at) = CURDATE()",
                'total_stock' => "SELECT COUNT(*) as count FROM stock WHERE quantity > 0",
                'low_stock' => "SELECT COUNT(*) as count FROM stock WHERE quantity <= stock_min",
                'total_depots' => "SELECT COUNT(*) as count FROM depots WHERE is_active = 1",
                'pending_payments' => "SELECT COUNT(*) as count FROM payments WHERE status = 'pending'",
            ];
            break;

        case 'vendeur':
            // Statistiques pour vendeur
            $depotId = $_SESSION['depot_id'];
            $statsQueries = [
                'my_sales_today' => "SELECT COUNT(*) as count FROM sales WHERE DATE(created_at) = CURDATE() AND depot_id = ?",
                'my_revenue_today' => "SELECT COALESCE(SUM(total_amount), 0) as amount FROM sales WHERE DATE(created_at) = CURDATE() AND depot_id = ?",
                'my_clients' => "SELECT COUNT(*) as count FROM clients WHERE depot_id = ? AND is_active = 1",
                'pending_orders' => "SELECT COUNT(*) as count FROM sales WHERE status = 'pending' AND depot_id = ?",
                'stock_items' => "SELECT COUNT(*) as count FROM stock WHERE depot_id = ? AND quantity > 0",
                'low_stock_items' => "SELECT COUNT(*) as count FROM stock WHERE depot_id = ? AND quantity <= stock_min",
            ];
            break;

        case 'livreur':
            // Statistiques pour livreur
            $depotId = $_SESSION['depot_id'];
            $statsQueries = [
                'deliveries_today' => "SELECT COUNT(*) as count FROM deliveries WHERE DATE(delivery_date) = CURDATE() AND depot_id = ?",
                'pending_deliveries' => "SELECT COUNT(*) as count FROM deliveries WHERE status = 'pending' AND depot_id = ?",
                'completed_deliveries' => "SELECT COUNT(*) as count FROM deliveries WHERE status = 'delivered' AND depot_id = ?",
                'total_clients' => "SELECT COUNT(*) as count FROM clients WHERE depot_id = ? AND is_active = 1",
            ];
            break;

        case 'comptable':
            // Statistiques pour comptable
            $statsQueries = [
                'revenue_today' => "SELECT COALESCE(SUM(total_amount), 0) as amount FROM sales WHERE DATE(created_at) = CURDATE()",
                'revenue_month' => "SELECT COALESCE(SUM(total_amount), 0) as amount FROM sales WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())",
                'pending_payments' => "SELECT COUNT(*) as count FROM payments WHERE status = 'pending'",
                'paid_payments' => "SELECT COUNT(*) as count FROM payments WHERE status = 'paid'",
                'total_invoices' => "SELECT COUNT(*) as count FROM sales WHERE DATE(created_at) = CURDATE()",
                'overdue_payments' => "SELECT COUNT(*) as count FROM payments WHERE status = 'overdue'",
            ];
            break;
    }

    // Exécuter les requêtes
    foreach ($statsQueries as $key => $query) {
        $stmt = $db->prepare($query);
        if (in_array($userRole, ['vendeur', 'livreur']) && strpos($query, '?') !== false) {
            $stmt->execute([$depotId]);
        } else {
            $stmt->execute();
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats[$key] = $result['count'] ?? $result['amount'] ?? 0;
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
                    <h3><?= number_format($stats['revenue_today'] ?? 0, 0, ',', ' ') ?> €</h3>
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
                    <h3><?= number_format($stats['my_revenue_today'] ?? 0, 0, ',', ' ') ?> €</h3>
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
                    <a href="deliveries.php?status=pending" class="action-btn warning">
                        <i class="fas fa-truck-loading"></i> Livraisons en Attente
                    </a>
                    <a href="deliveries.php?action=new" class="action-btn success">
                        <i class="fas fa-plus-circle"></i> Nouvelle Livraison
                    </a>
                    <a href="deliveries.php" class="action-btn primary">
                        <i class="fas fa-list"></i> Toutes les Livraisons
                    </a>
                    <a href="clients.php" class="action-btn info">
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
                    <h3><?= number_format($stats['revenue_today'] ?? 0, 0, ',', ' ') ?> €</h3>
                    <p>CA Aujourd'hui</p>
                </div>
            </div>

            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['revenue_month'] ?? 0, 0, ',', ' ') ?> €</h3>
                    <p>CA ce Mois</p>
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
                    <p>Paiements en Retard</p>
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