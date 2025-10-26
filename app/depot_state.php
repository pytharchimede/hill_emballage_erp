<?php
require_once 'includes/config.php';
require_once 'includes/migrations.php';
requireLogin();
if (!hasPermission('view_reports') && $_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'comptable') {
    setFlashMessage('warning', 'Accès refusé.');
    header('Location: dashboard.php');
    exit();
}
ensureLivreurFlowTables($db);

$selectedDate = $_GET['date'] ?? date('Y-m-d');
$depotId = isset($_GET['depot_id']) ? (int)$_GET['depot_id'] : (int)($_SESSION['depot_id'] ?? 0);

// Restreindre la liste et la sélection de dépôts pour vendeur/comptable/livreur non principal
$role = $_SESSION['user_role'] ?? '';
$currentDepotId = (int)($_SESSION['depot_id'] ?? 0);
$restrictToOwnDepot = in_array($role, ['vendeur', 'comptable', 'livreur'], true) && $currentDepotId > 0 && !isMainDepot($currentDepotId);

$depotsSql = "SELECT id, nom FROM depots WHERE is_active=1";
if ($restrictToOwnDepot) {
    $depotsSql .= " AND id = " . (int)$currentDepotId;
}
$depotsSql .= " ORDER BY nom";
$depots = $db->query($depotsSql)->fetchAll(PDO::FETCH_ASSOC);

if ($restrictToOwnDepot) {
    $depotId = $currentDepotId; // forcer le filtre
}
$livreurs = [];
if ($depotId) {
    // Détection dynamique de la colonne rôle
    $roleCol = null;
    try {
        $cand = ['user_role', 'role', 'profil', 'type'];
        $sqlCols = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME IN ('" . implode("','", $cand) . "')";
        $rs = $db->query($sqlCols)->fetchAll(PDO::FETCH_COLUMN);
        if ($rs) {
            $roleCol = $rs[0];
        }
    } catch (Exception $e) {
    }
    if ($roleCol) {
        $st = $db->prepare("SELECT id, full_name FROM users WHERE $roleCol IN ('livreur','commercial') AND depot_id=? ORDER BY full_name");
        $st->execute([$depotId]);
    } else {
        // Fallback sans filtre rôle
        $st = $db->prepare("SELECT id, full_name FROM users WHERE depot_id=? ORDER BY full_name");
        $st->execute([$depotId]);
    }
    $livreurs = $st->fetchAll(PDO::FETCH_ASSOC);
}

function scalar(PDO $db, $sql, $p = [])
{
    $s = $db->prepare($sql);
    $s->execute($p);
    $r = $s->fetch(PDO::FETCH_NUM);
    return $r ? (float)$r[0] : 0;
}
$hasDeliveryDate = scalar($db, "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='ventes' AND COLUMN_NAME='delivery_date'") > 0;
$livreDateExpr = $hasDeliveryDate ? 'DATE(v.delivery_date)' : 'DATE(v.updated_at)';
$venteDateExpr = 'DATE(v.created_at)';

$kpiPanier = 0;
$kpiLivre = 0;
$kpiVendu = 0;
$kpiCredit = 0;
$kpiRemis = 0;
$perLivreur = [];
// Statut validé requis pour compter le panier ?
$hasLoadStatus = scalar($db, "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='livreur_loads' AND COLUMN_NAME='status'") > 0;
foreach ($livreurs as $l) {
    $lid = (int)$l['id'];
    $panierSql = "SELECT COALESCE(SUM(i.quantite),0) FROM livreur_load_items i JOIN livreur_loads l ON i.load_id=l.id WHERE l.livreur_id=? AND l.date_load=?";
    if ($hasLoadStatus) {
        $panierSql .= " AND l.status='validated'";
    }
    $panier = scalar($db, $panierSql, [$lid, $selectedDate]);
    $hasDraft = false;
    if ($hasLoadStatus) {
        $hasDraft = scalar($db, "SELECT COUNT(*) FROM livreur_loads WHERE livreur_id=? AND date_load=? AND status='draft'", [$lid, $selectedDate]) > 0;
    }
    $livre  = scalar($db, "SELECT COALESCE(SUM(v.montant_total),0) FROM ventes v WHERE v.livreur_id=? AND $livreDateExpr=?", [$lid, $selectedDate]);
    $vendu  = scalar($db, "SELECT COALESCE(SUM(v.montant_total),0) FROM ventes v WHERE v.livreur_id=? AND $venteDateExpr=? AND v.montant_paye >= v.montant_total", [$lid, $selectedDate]);
    $credit = scalar($db, "SELECT COALESCE(SUM(v.montant_total - v.montant_paye),0) FROM ventes v WHERE v.livreur_id=? AND $venteDateExpr=? AND v.montant_paye < v.montant_total", [$lid, $selectedDate]);
    $remis  = scalar($db, "SELECT COALESCE(SUM(amount),0) FROM livreur_remittances WHERE livreur_id=? AND date_remit=?", [$lid, $selectedDate]);
    $perLivreur[] = ['id' => $lid, 'nom' => $l['full_name'], 'panier' => $panier, 'livre' => $livre, 'vendu' => $vendu, 'credit' => $credit, 'remis' => $remis, 'has_draft' => $hasDraft];
    $kpiPanier += $panier;
    $kpiLivre += $livre;
    $kpiVendu += $vendu;
    $kpiCredit += $credit;
    $kpiRemis += $remis;
}

$pageTitle = 'Etat journalier Dépôt';
include 'includes/header.php';
?>
<div class="page-header">
    <h1><i class="fas fa-warehouse"></i> Etat journalier Dépôt</h1>
    <form method="get" class="row g-2">
        <div class="col-md-3"><input class="form-control" type="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>" /></div>
        <div class="col-md-5">
            <select class="form-select" name="depot_id" required>
                <option value="">— Sélectionner dépôt —</option>
                <?php foreach ($depots as $d): ?><option value="<?= (int)$d['id'] ?>" <?= $depotId === (int)$d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['nom']) ?></option><?php endforeach; ?>
            </select>
            <?php if ($restrictToOwnDepot && count($depots) === 1): $only = $depots[0]; ?>
                <div class="form-text mt-1"><span class="badge bg-warning-subtle border text-dark"><i class="fas fa-lock"></i> Dépôt actif: <?= htmlspecialchars($only['nom']) ?></span></div>
            <?php endif; ?>
        </div>
        <div class="col-md-2"><button class="btn w-100">Voir</button></div>
    </form>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="icon"><i class="fas fa-shopping-basket"></i></div>
        <div class="value"><?= number_format($kpiPanier, 0, ',', ' ') ?></div>
        <div class="label">Panier total (Qté)</div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fas fa-truck"></i></div>
        <div class="value"><?= number_format($kpiLivre, 0, ',', ' ') ?></div>
        <div class="label">Total livré (FCFA)</div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fas fa-check-circle"></i></div>
        <div class="value"><?= number_format($kpiVendu, 0, ',', ' ') ?></div>
        <div class="label">Vendu (soldé)</div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fas fa-clock"></i></div>
        <div class="value"><?= number_format($kpiCredit, 0, ',', ' ') ?></div>
        <div class="label">Crédit restant</div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
        <div class="value"><?= number_format($kpiRemis, 0, ',', ' ') ?></div>
        <div class="label">Versements</div>
    </div>
</div>

<div class="card">
    <h2>Détail par livreur</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Livreur</th>
                <th class="text-end">Panier</th>
                <th class="text-end">Livré</th>
                <th class="text-end">Vendu</th>
                <th class="text-end">Crédit</th>
                <th class="text-end">Versé</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($perLivreur as $r): ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($r['nom']) ?>
                        <?php if (!empty($r['has_draft'])): ?><span class="badge bg-warning ms-2">à valider</span><?php endif; ?>
                    </td>
                    <td class="text-end"><?= number_format($r['panier'], 0, ',', ' ') ?></td>
                    <td class="text-end"><?= number_format($r['livre'], 0, ',', ' ') ?></td>
                    <td class="text-end"><?= number_format($r['vendu'], 0, ',', ' ') ?></td>
                    <td class="text-end"><?= number_format($r['credit'], 0, ',', ' ') ?></td>
                    <td class="text-end"><?= number_format($r['remis'], 0, ',', ' ') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$perLivreur): ?><tr>
                    <td colspan="6">—</td>
                </tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php';
