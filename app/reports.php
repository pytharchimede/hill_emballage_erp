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

function qv($db, $sql, $params = [])
{
    $s = $db->prepare($sql);
    $s->execute($params);
    return $s->fetch(PDO::FETCH_ASSOC);
}

$kpiSales = qv($db, "SELECT COALESCE(SUM(montant_total),0) val FROM ventes WHERE date_vente BETWEEN ? AND ?", [$d1, $d2])['val'];
$kpiPayments = qv($db, "SELECT COALESCE(SUM(montant),0) val FROM payments WHERE date_payment BETWEEN ? AND ?", [$d1, $d2])['val'];
$kpiCredit = qv($db, "SELECT COALESCE(SUM(montant_total - montant_paye),0) val FROM ventes WHERE date_vente BETWEEN ? AND ? AND type_vente='credit'", [$d1, $d2])['val'];

$topProducts = $db->prepare("SELECT p.nom, SUM(i.quantite) q, SUM(i.montant) m
  FROM vente_items i JOIN products p ON i.produit_id=p.id JOIN ventes v ON i.vente_id=v.id
  WHERE v.date_vente BETWEEN ? AND ? GROUP BY p.id ORDER BY m DESC LIMIT 10");
$topProducts->execute([$d1, $d2]);
$topProducts = $topProducts->fetchAll(PDO::FETCH_ASSOC);

$topClients = $db->prepare("SELECT c.nom, COALESCE(c.entreprise,'') entreprise, SUM(v.montant_total) m
  FROM ventes v JOIN clients c ON v.client_id=c.id WHERE v.date_vente BETWEEN ? AND ? GROUP BY c.id ORDER BY m DESC LIMIT 10");
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
        <div class="value"><?= number_format($kpiCredit, 0, ',', ' ') ?></div>
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