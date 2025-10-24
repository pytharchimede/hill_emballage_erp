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

// Dépôt principal = celui qui n'est pas destination d'un parent? Si non explicit, on affiche tous les dépôts
$depots = $db->query("SELECT id, nom FROM depots WHERE is_active=1 ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);

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

$pageTitle = 'Etat général du système';
include 'includes/header.php';
?>
<div class="page-header">
    <h1><i class="fas fa-sitemap"></i> Etat général</h1>
    <form method="get" class="row g-2">
        <div class="col-md-3"><input class="form-control" type="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>" /></div>
        <div class="col-md-2"><button class="btn w-100">Voir</button></div>
    </form>
</div>

<?php foreach ($depots as $d): $depotId = (int)$d['id']; ?>
    <div class="card">
        <h2><i class="fas fa-warehouse"></i> <?= htmlspecialchars($d['nom']) ?></h2>
        <?php
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
            $livreurs = $db->prepare("SELECT id, full_name FROM users WHERE $roleCol IN ('livreur','commercial') AND depot_id=? ORDER BY full_name");
            $livreurs->execute([$depotId]);
        } else {
            $livreurs = $db->prepare("SELECT id, full_name FROM users WHERE depot_id=? ORDER BY full_name");
            $livreurs->execute([$depotId]);
        }
        $livreurs = $livreurs->fetchAll(PDO::FETCH_ASSOC);

        $sumDepotPanier = 0;
        $sumDepotLivre = 0;
        $sumDepotVendu = 0;
        $sumDepotCredit = 0;
        $sumDepotRemis = 0;
        ?>
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
                <?php foreach ($livreurs as $l): $lid = (int)$l['id'];
                    $panier = scalar($db, "SELECT COALESCE(SUM(i.quantite),0) FROM livreur_load_items i JOIN livreur_loads l ON i.load_id=l.id WHERE l.livreur_id=? AND l.date_load=?", [$lid, $selectedDate]);
                    $livre  = scalar($db, "SELECT COALESCE(SUM(v.montant_total),0) FROM ventes v WHERE v.livreur_id=? AND $livreDateExpr=?", [$lid, $selectedDate]);
                    $vendu  = scalar($db, "SELECT COALESCE(SUM(v.montant_total),0) FROM ventes v WHERE v.livreur_id=? AND $venteDateExpr=? AND v.montant_paye >= v.montant_total", [$lid, $selectedDate]);
                    $credit = scalar($db, "SELECT COALESCE(SUM(v.montant_total - v.montant_paye),0) FROM ventes v WHERE v.livreur_id=? AND $venteDateExpr=? AND v.montant_paye < v.montant_total", [$lid, $selectedDate]);
                    $remis  = scalar($db, "SELECT COALESCE(SUM(amount),0) FROM livreur_remittances WHERE livreur_id=? AND date_remit=?", [$lid, $selectedDate]);
                    $sumDepotPanier += $panier;
                    $sumDepotLivre += $livre;
                    $sumDepotVendu += $vendu;
                    $sumDepotCredit += $credit;
                    $sumDepotRemis += $remis; ?>
                    <tr>
                        <td><?= htmlspecialchars($l['full_name']) ?></td>
                        <td class="text-end"><?= number_format($panier, 0, ',', ' ') ?></td>
                        <td class="text-end"><?= number_format($livre, 0, ',', ' ') ?></td>
                        <td class="text-end"><?= number_format($vendu, 0, ',', ' ') ?></td>
                        <td class="text-end"><?= number_format($credit, 0, ',', ' ') ?></td>
                        <td class="text-end"><?= number_format($remis, 0, ',', ' ') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$livreurs): ?><tr>
                        <td colspan="6">—</td>
                    </tr><?php endif; ?>
            </tbody>
        </table>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon"><i class="fas fa-shopping-basket"></i></div>
                <div class="value"><?= number_format($sumDepotPanier, 0, ',', ' ') ?></div>
                <div class="label">Panier total</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-truck"></i></div>
                <div class="value"><?= number_format($sumDepotLivre, 0, ',', ' ') ?></div>
                <div class="label">Livré</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-check-circle"></i></div>
                <div class="value"><?= number_format($sumDepotVendu, 0, ',', ' ') ?></div>
                <div class="label">Vendu</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <div class="value"><?= number_format($sumDepotCredit, 0, ',', ' ') ?></div>
                <div class="label">Crédit</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
                <div class="value"><?= number_format($sumDepotRemis, 0, ',', ' ') ?></div>
                <div class="label">Versements</div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php include 'includes/footer.php';
