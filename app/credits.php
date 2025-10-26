<?php
require_once 'includes/config.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}
if (!hasPermission('read_credits') && !hasPermission('payments_read')) {
    setFlashMessage('warning', "Accès refusé à Crédits.");
    header('Location: dashboard.php');
    exit();
}

$msg = '';
$msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create_payment' && hasPermission('payments_create')) {
            $vente_id = (int)($_POST['vente_id'] ?? 0);
            $montant = (float)($_POST['montant'] ?? 0);
            $date_payment = $_POST['date_payment'] ?? date('Y-m-d');
            $mode = $_POST['mode_payment'] ?? 'espece';
            $ref = trim($_POST['reference'] ?? '');
            if ($vente_id <= 0 || $montant <= 0) throw new Exception('Vente ou montant invalide');

            // Scoping: vérifier que la vente appartient au périmètre du dépôt de l'utilisateur (sauf dépôt principal)
            $userDepotId = (int)($_SESSION['depot_id'] ?? 0);
            $isMain = $userDepotId > 0 && isMainDepot($userDepotId);
            if (!$isMain) {
                $stScope = $db->prepare("SELECT COUNT(*)
                    FROM ventes v
                    JOIN clients c ON v.client_id=c.id
                    LEFT JOIN users uu ON v.user_id=uu.id
                    LEFT JOIN users lvr ON v.livreur_id=lvr.id
                    WHERE v.id=? AND (
                        v.depot_id = ? OR
                        (v.livreur_id IS NOT NULL AND lvr.depot_id = ?) OR
                        (v.user_id IS NOT NULL AND uu.depot_id = ?) OR
                        c.depot_id = ?
                    )");
                $stScope->execute([$vente_id, $userDepotId, $userDepotId, $userDepotId, $userDepotId]);
                if ((int)$stScope->fetchColumn() === 0) throw new Exception('Vente hors de votre dépôt');
            }

            // Valider le restant dû de la vente et plafonner le paiement
            $stChk = $db->prepare("SELECT montant_total, montant_paye FROM ventes WHERE id=?");
            $stChk->execute([$vente_id]);
            $vente = $stChk->fetch(PDO::FETCH_ASSOC);
            if (!$vente) throw new Exception("Vente introuvable");
            $restant = (float)$vente['montant_total'] - (float)$vente['montant_paye'];
            if ($restant <= 0) throw new Exception("Cette vente est déjà soldée");
            if ($montant > $restant) throw new Exception("Le montant saisi dépasse le restant dû (" . number_format($restant, 0, ',', ' ') . " FCFA)");

            // Début transaction
            $db->beginTransaction();
            try {
                $numero = 'RC-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
                $st = $db->prepare("INSERT INTO payments (vente_id, numero_recu, date_payment, montant, mode_payment, statut, reference) VALUES (?,?,?,?,?,'valide',?)");
                $st->execute([$vente_id, $numero, $date_payment, $montant, $mode, $ref]);

                // Mettre à jour la vente
                $db->prepare("UPDATE ventes SET montant_paye = montant_paye + ? WHERE id=?")->execute([$montant, $vente_id]);
                $db->prepare("UPDATE ventes SET statut = CASE WHEN montant_paye >= montant_total THEN 'validee' ELSE statut END WHERE id=?")->execute([$vente_id]);

                $db->commit();
                log_action('CREATE', 'payments', (int)$db->lastInsertId(), ['vente_id' => $vente_id, 'montant' => $montant, 'mode' => $mode]);
                $msg = 'Paiement enregistré';
                $msgType = 'success';
            } catch (Exception $txe) {
                $db->rollBack();
                throw $txe;
            }
        }
    } catch (Exception $e) {
        $msg = 'Erreur: ' . $e->getMessage();
        $msgType = 'error';
    }
}

$d1 = $_GET['d1'] ?? date('Y-m-01');
$d2 = $_GET['d2'] ?? date('Y-m-d');
$scope = $_GET['scope'] ?? 'global'; // 'global' par défaut (encours sur l'ensemble des ventes) ou 'period'
$search = $_GET['search'] ?? '';

function colExists2(PDO $db, $table, $col)
{
    $s = $db->prepare("SELECT COUNT(*) c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $s->execute([$table, $col]);
    return (int)$s->fetch(PDO::FETCH_ASSOC)['c'] > 0;
}
$hasDateVente = colExists2($db, 'ventes', 'date_vente');
$dateExpr = $hasDateVente ? 'COALESCE(v.date_vente, DATE(v.created_at))' : 'DATE(v.created_at)';

// Scoping dépôt
$userRole = $_SESSION['user_role'] ?? '';
$userDepotId = (int)($_SESSION['depot_id'] ?? 0);
$isMain = $userDepotId > 0 && isMainDepot($userDepotId);

$joinsScope = " LEFT JOIN users uu ON v.user_id=uu.id LEFT JOIN users lvr ON v.livreur_id=lvr.id ";
$scopeSql = '';
$params = [];
if (!$isMain) {
    $scopeSql = " AND (v.depot_id = ? OR (v.livreur_id IS NOT NULL AND lvr.depot_id = ?) OR (v.user_id IS NOT NULL AND uu.depot_id = ?) OR c.depot_id = ?)";
}

if ($scope === 'period') {
    $where = "WHERE $dateExpr BETWEEN ? AND ? AND (v.montant_paye < v.montant_total)" . ($scopeSql ? $scopeSql : '');
    $params = [$d1, $d2];
    if ($scopeSql) {
        array_push($params, $userDepotId, $userDepotId, $userDepotId, $userDepotId);
    }
} else {
    $where = "WHERE (v.montant_paye < v.montant_total)" . ($scopeSql ? $scopeSql : '');
    if ($scopeSql) {
        array_push($params, $userDepotId, $userDepotId, $userDepotId, $userDepotId);
    }
}
if ($search) {
    $where .= " AND (v.numero_vente LIKE ? OR c.nom LIKE ?)";
    $q = "%$search%";
    array_push($params, $q, $q);
}

$sql = "SELECT v.id, v.numero_vente, $dateExpr as d, v.montant_total, v.montant_paye, (v.montant_total - v.montant_paye) as restant, c.nom as client
        FROM ventes v JOIN clients c ON v.client_id=c.id $joinsScope
        $where ORDER BY d DESC, v.id DESC";
$st = $db->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

$kpiDue = 0;
foreach ($rows as $r) {
    $kpiDue += max(0, (float)$r['restant']);
}

$pageTitle = 'Crédits';
include 'includes/header.php';
?>
<div class="page-header">
    <h1><i class="fas fa-clock"></i> Crédits</h1>
    <form method="get" class="row g-2">
        <div class="col-md-3"><input class="form-control" type="date" name="d1" value="<?= htmlspecialchars($d1) ?>" /></div>
        <div class="col-md-3"><input class="form-control" type="date" name="d2" value="<?= htmlspecialchars($d2) ?>" /></div>
        <div class="col-md-3">
            <select class="form-select" name="scope">
                <option value="global" <?= $scope === 'global' ? 'selected' : '' ?>>Encours global</option>
                <option value="period" <?= $scope === 'period' ? 'selected' : '' ?>>Encours sur la période</option>
            </select>
        </div>
        <div class="col-md-3"><input class="form-control" name="search" placeholder="Recherche (facture/client)" value="<?= htmlspecialchars($search) ?>" /></div>
        <div class="col-md-2 mt-2 mt-md-0"><button class="btn w-100">Filtrer</button></div>
    </form>
</div>
<?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="icon"><i class="fas fa-wallet"></i></div>
        <div class="value"><?= number_format($kpiDue, 0, ',', ' ') ?></div>
        <div class="label">Encours à Recouvrer <?= $scope === 'global' ? '(Global)' : "(Période)" ?></div>
    </div>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Facture</th>
                <th>Date</th>
                <th>Client</th>
                <th class="text-end">Total</th>
                <th class="text-end">Payé</th>
                <th class="text-end">Restant</th>
                <th>Action</th>
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
                    <td>
                        <?php if (hasPermission('payments_create')): ?>
                            <button class="btn btn-sm btn-primary btn-encaisser"
                                data-vente-id="<?= (int)$r['id'] ?>"
                                data-numero="<?= htmlspecialchars($r['numero_vente'], ENT_QUOTES) ?>"
                                data-restant="<?= (int)max(0, $r['restant']) ?>">
                                <i class="fas fa-plus"></i> Encaisser
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr>
                    <td colspan="7">Aucun crédit</td>
                </tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (hasPermission('payments_create')): ?>
    <div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-receipt"></i> Enregistrer un paiement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <input type="hidden" name="action" value="create_payment" />
                    <input type="hidden" name="vente_id" id="pm_vente_id" value="" />
                    <div class="modal-body">
                        <div class="mb-2"><label class="form-label">Facture</label>
                            <div id="pm_invoice"></div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6"><label class="form-label">Date</label><input class="form-control" type="date" name="date_payment" value="<?= date('Y-m-d') ?>" required /></div>
                            <div class="col-md-6"><label class="form-label">Montant</label><input class="form-control" type="number" step="0.01" name="montant" id="pm_montant" required /></div>
                        </div>
                        <div class="row g-2 mt-2">
                            <div class="col-md-6">
                                <label class="form-label">Mode</label>
                                <select class="form-select" name="mode_payment">
                                    <?php foreach (['espece', 'cheque', 'virement', 'mobile'] as $m): ?><option value="<?= $m ?>"><?= ucfirst($m) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6"><label class="form-label">Référence</label><input class="form-control" name="reference" /></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Annuler</button>
                        <button class="btn btn-primary" type="submit">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="<?= ASSETS_URL ?>/js/credits.js" defer></script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>