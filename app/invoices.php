<?php
require_once 'includes/config.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}
if (!hasPermission('sales_read')) {
    setFlashMessage('warning', "Accès refusé aux Factures.");
    header('Location: dashboard.php');
    exit();
}

$d1 = $_GET['d1'] ?? date('Y-m-01');
$d2 = $_GET['d2'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

function colExists(PDO $db, $table, $col)
{
    $s = $db->prepare("SELECT COUNT(*) c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $s->execute([$table, $col]);
    return (int)$s->fetch(PDO::FETCH_ASSOC)['c'] > 0;
}
// Pour éviter d'exclure des ventes récentes à cause d'une date_vente ancienne,
// on utilise created_at pour générer les "factures de reliquat" et on conserve $dateExpr pour les factures d'origine.
$hasDateVente = colExists($db, 'ventes', 'date_vente');
$dateExpr = $hasDateVente ? 'COALESCE(v.date_vente, DATE(v.created_at))' : 'DATE(v.created_at)';
$createdExpr = 'DATE(v.created_at)';

$where = "WHERE $dateExpr BETWEEN ? AND ?";
$params = [$d1, $d2];
if ($search) {
    $where .= " AND (v.numero_vente LIKE ? OR c.nom LIKE ?)";
    $q = "%$search%";
    array_push($params, $q, $q);
}

// Scoping par dépôt pour vendeur/comptable/livreur (sauf dépôt principal)
$role = $_SESSION['user_role'] ?? '';
$currentDepotId = (int)($_SESSION['depot_id'] ?? 0);
$hasVenteDepot   = colExists($db, 'ventes', 'depot_id');
$hasVenteLivreur = colExists($db, 'ventes', 'livreur_id');
$hasVenteUser    = colExists($db, 'ventes', 'user_id');
$hasVenteClient  = colExists($db, 'ventes', 'client_id');
$hasClientDepot  = colExists($db, 'clients', 'depot_id');

$scopeSql = '';
if (in_array($role, ['vendeur', 'comptable', 'livreur'], true) && $currentDepotId > 0 && !isMainDepot($currentDepotId)) {
    if ($hasVenteDepot) {
        $scopeSql = 'v.depot_id = ?';
    } elseif ($hasVenteLivreur) {
        $scopeSql = 'v.livreur_id IN (SELECT id FROM users WHERE depot_id = ?)';
    } elseif ($hasVenteUser) {
        $scopeSql = 'v.user_id IN (SELECT id FROM users WHERE depot_id = ?)';
    } elseif ($hasVenteClient && $hasClientDepot) {
        $scopeSql = 'c.depot_id = ?';
    }
    if ($scopeSql) {
        $where .= " AND $scopeSql";
        $params[] = $currentDepotId;
    }
}

// Récupération
$sql = "SELECT v.id, v.numero_vente, $dateExpr as d, v.montant_total, v.montant_paye, (v.montant_total - v.montant_paye) as restant, v.statut, c.nom as client
    FROM ventes v JOIN clients c ON v.client_id=c.id
    $where ORDER BY d DESC, v.id DESC";
$st = $db->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

// Générer automatiquement des "factures de reliquat" pour les autres ventes
// Règle: ventes avec encours (montant_paye < montant_total) créées sur la période
// mais dont la facture d'origine n'est pas déjà listée via $dateExpr sur la période.
$relWhere = "WHERE $createdExpr BETWEEN ? AND ? AND (v.montant_paye < v.montant_total) AND NOT ($dateExpr BETWEEN ? AND ?)";
// Appliquer le même scope aux reliquats
if ($scopeSql) {
    $relWhere .= " AND $scopeSql";
}
$stRel = $db->prepare("SELECT v.id, v.numero_vente, $createdExpr as d, v.montant_total, v.montant_paye,
                (v.montant_total - v.montant_paye) as restant, 'reliquat' as statut, c.nom as client
                FROM ventes v JOIN clients c ON v.client_id=c.id
                $relWhere");
$relParams = [$d1, $d2, $d1, $d2];
if ($scopeSql) $relParams[] = $currentDepotId;
$stRel->execute($relParams);
$rowsRel = $stRel->fetchAll(PDO::FETCH_ASSOC);

// KPIs simples
$kpiTotal = 0;
$kpiPaid = 0;
$kpiDue = 0;
foreach ($rows as $r) {
    $kpiTotal += (float)$r['montant_total'];
    $kpiPaid  += (float)$r['montant_paye'];
    $kpiDue   += max(0, (float)$r['restant']);
}

$pageTitle = 'Factures';
include 'includes/header.php';
?>
<div class="page-header">
    <h1><i class="fas fa-file-invoice"></i> Factures</h1>
    <form method="get" class="row g-2">
        <div class="col-md-3"><input class="form-control" type="date" name="d1" value="<?= htmlspecialchars($d1) ?>" /></div>
        <div class="col-md-3"><input class="form-control" type="date" name="d2" value="<?= htmlspecialchars($d2) ?>" /></div>
        <div class="col-md-4"><input class="form-control" name="search" placeholder="Recherche (facture/client)" value="<?= htmlspecialchars($search) ?>" /></div>
        <div class="col-md-2"><button class="btn w-100">Filtrer</button></div>
    </form>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="icon"><i class="fas fa-cash-register"></i></div>
        <div class="value"><?= number_format($kpiTotal, 0, ',', ' ') ?></div>
        <div class="label">Total Facturé</div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
        <div class="value"><?= number_format($kpiPaid, 0, ',', ' ') ?></div>
        <div class="label">Total Perçu</div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fas fa-clock"></i></div>
        <div class="value"><?= number_format($kpiDue, 0, ',', ' ') ?></div>
        <div class="label">Reste à Recouvrer</div>
    </div>
</div>

<div class="card">
    <h2 class="mb-3">Factures d'origine</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Facture</th>
                <th>Date</th>
                <th>Client</th>
                <th class="text-end">Total</th>
                <th class="text-end">Payé</th>
                <th class="text-end">Restant</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['numero_vente']) ?></td>
                    <td><?= htmlspecialchars($r['d']) ?></td>
                    <td><?= htmlspecialchars($r['client']) ?></td>
                    <td class="text-end"><?= number_format($r['montant_total'], 0, ',', ' ') ?></td>
                    <td class="text-end"><?= number_format($r['montant_paye'], 0, ',', ' ') ?></td>
                    <td class="text-end"><?= number_format(max(0, $r['restant']), 0, ',', ' ') ?></td>
                    <td><?= htmlspecialchars($r['statut']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr>
                    <td colspan="7">Aucune facture</td>
                </tr><?php endif; ?>
        </tbody>
    </table>

    <hr class="my-4" />
    <h2 class="mb-3">Factures de reliquat (générées automatiquement)</h2>
    <p class="text-muted">Ces lignes représentent le reliquat à facturer pour des ventes créées sur la période mais dont la facture d'origine n'était pas datée dans la période.</p>
    <table class="table">
        <thead>
            <tr>
                <th>Facture</th>
                <th>Date</th>
                <th>Client</th>
                <th class="text-end">Montant Reliquat</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rowsRel as $r): ?>
                <tr>
                    <td><?= htmlspecialchars('REL-' . $r['numero_vente']) ?></td>
                    <td><?= htmlspecialchars($r['d']) ?></td>
                    <td><?= htmlspecialchars($r['client']) ?></td>
                    <td class="text-end"><?= number_format(max(0, $r['restant']), 0, ',', ' ') ?></td>
                    <td><span class="badge bg-warning text-dark">Reliquat</span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rowsRel): ?><tr>
                    <td colspan="5">Aucun reliquat à facturer</td>
                </tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>