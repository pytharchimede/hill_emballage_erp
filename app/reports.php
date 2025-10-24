<?php
require_once 'includes/config.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}
if (!hasPermission('view_reports')) {
    setFlashMessage('warning', 'Accès refusé aux Rapports.');
    header('Location: dashboard.php');
    exit();
}

$d1 = $_GET['d1'] ?? date('Y-m-01');
$d2 = $_GET['d2'] ?? date('Y-m-d');
$scope = $_GET['scope'] ?? 'period'; // 'period' (par défaut) ou 'global'

function qv($db, $sql, $params = [])
{
    $s = $db->prepare($sql);
    $s->execute($params);
    return $s->fetch(PDO::FETCH_ASSOC);
}

// Vérifier si une colonne existe pour construire des expressions de date robustes
function columnExists(PDO $db, $table, $column)
{
    $s = $db->prepare("SELECT COUNT(*) c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $s->execute([$table, $column]);
    return (int)$s->fetch(PDO::FETCH_ASSOC)['c'] > 0;
}

// Pour des KPI cohérents avec vos données actuelles, on base les dates de chiffre d'affaires sur la date de création
// (certaines ventes ont une date_vente ancienne). Utiliser uniquement DATE(v.created_at).
$venteDateExpr = 'DATE(v.created_at)';

$hasPayDate = columnExists($db, 'payments', 'date_payment');
$payDateExpr = $hasPayDate ? 'COALESCE(date_payment, DATE(created_at))' : 'DATE(created_at)';

// CA période en utilisant date_vente si présente sinon created_at
$kpiSales = qv($db, "SELECT COALESCE(SUM(v.montant_total),0) val FROM ventes v WHERE $venteDateExpr BETWEEN ? AND ?", [$d1, $d2])['val'];
// Encaissements reçus: uniquement les paiements validés, basés sur la date métier (fallback created_at)
$kpiPayments = qv($db, "SELECT COALESCE(SUM(montant),0) val FROM payments WHERE statut='valide' AND $payDateExpr BETWEEN ? AND ?", [$d1, $d2])['val'];
// Reste à recouvrer
if ($scope === 'global') {
    $kpiOutstanding = qv($db, "SELECT COALESCE(SUM(montant_total - montant_paye),0) val FROM ventes WHERE montant_paye < montant_total", [])['val'];
} else {
    $kpiOutstanding = qv($db, "SELECT COALESCE(SUM(v.montant_total - v.montant_paye),0) val FROM ventes v WHERE $venteDateExpr BETWEEN ? AND ? AND v.montant_paye < v.montant_total", [$d1, $d2])['val'];
}

$topProducts = $db->prepare("SELECT p.nom, SUM(i.quantite) q, SUM(i.montant) m
    FROM vente_items i JOIN products p ON i.produit_id=p.id JOIN ventes v ON i.vente_id=v.id
    WHERE $venteDateExpr BETWEEN ? AND ? GROUP BY p.id ORDER BY m DESC LIMIT 10");
$topProducts->execute([$d1, $d2]);
$topProducts = $topProducts->fetchAll(PDO::FETCH_ASSOC);

$topClients = $db->prepare("SELECT c.nom, COALESCE(c.entreprise,'') entreprise, SUM(v.montant_total) m
    FROM ventes v JOIN clients c ON v.client_id=c.id WHERE $venteDateExpr BETWEEN ? AND ? GROUP BY c.id ORDER BY m DESC LIMIT 10");
$topClients->execute([$d1, $d2]);
$topClients = $topClients->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Rapports';
include 'includes/header.php';
?>
<div class="page-header">
    <h1><i class="fas fa-chart-bar"></i> Rapports</h1>
    <form method="get" class="row g-2">
        <div class="col-md-3"><input class="form-control" type="date" name="d1" value="<?= htmlspecialchars($d1) ?>" /></div>
        <div class="col-md-3"><input class="form-control" type="date" name="d2" value="<?= htmlspecialchars($d2) ?>" /></div>
        <div class="col-md-3">
            <select class="form-select" name="scope">
                <option value="period" <?= $scope === 'period' ? 'selected' : '' ?>>Encours sur la période</option>
                <option value="global" <?= $scope === 'global' ? 'selected' : '' ?>>Encours global</option>
            </select>
        </div>
        <div class="col-md-2"><button class="btn w-100">Appliquer</button></div>
    </form>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="icon"><i class="fas fa-cash-register"></i></div>
        <div class="value"><?= number_format($kpiSales, 0, ',', ' ') ?></div>
        <div class="label">Chiffre d'affaires</div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
        <div class="value"><?= number_format($kpiPayments, 0, ',', ' ') ?></div>
        <div class="label">Paiements reçus</div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fas fa-clock"></i></div>
        <div class="value"><?= number_format($kpiOutstanding, 0, ',', ' ') ?></div>
        <div class="label">Reste à recouvrer</div>
    </div>
</div>

<div class="card">
    <h2>Top produits</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Produit</th>
                <th>Qté</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($topProducts as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['nom']) ?></td>
                    <td><?= number_format($p['q'], 0, ',', ' ') ?></td>
                    <td><?= number_format($p['m'], 0, ',', ' ') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$topProducts): ?><tr>
                    <td colspan="3">—</td>
                </tr><?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h2>Top clients</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Client</th>
                <th>Entreprise</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($topClients as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['nom']) ?></td>
                    <td><?= htmlspecialchars($c['enterprise'] ?? $c['entreprise'] ?? '') ?></td>
                    <td><?= number_format($c['m'], 0, ',', ' ') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$topClients): ?><tr>
                    <td colspan="3">—</td>
                </tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>