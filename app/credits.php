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
            $numero = 'RC-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
            $st = $db->prepare("INSERT INTO payments (vente_id, numero_recu, date_payment, montant, mode_payment, statut, reference) VALUES (?,?,?,?,?,'valide',?)");
            $st->execute([$vente_id, $numero, $date_payment, $montant, $mode, $ref]);
            $db->prepare("UPDATE ventes SET montant_paye = montant_paye + ? WHERE id=?")->execute([$montant, $vente_id]);
            $db->prepare("UPDATE ventes SET statut = CASE WHEN montant_paye >= montant_total THEN 'validee' ELSE statut END WHERE id=?")->execute([$vente_id]);
            log_action('CREATE', 'payments', (int)$db->lastInsertId(), ['vente_id' => $vente_id, 'montant' => $montant, 'mode' => $mode]);
            $msg = 'Paiement enregistré';
            $msgType = 'success';
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

$params = [];
if ($scope === 'period') {
    $where = "WHERE $dateExpr BETWEEN ? AND ? AND (v.montant_paye < v.montant_total)";
    $params = [$d1, $d2];
} else {
    // Global: ne pas filtrer par dates, lister tous les encours
    $where = "WHERE (v.montant_paye < v.montant_total)";
}
if ($search) {
    $where .= " AND (v.numero_vente LIKE ? OR c.nom LIKE ?)";
    $q = "%$search%";
    array_push($params, $q, $q);
}

$sql = "SELECT v.id, v.numero_vente, $dateExpr as d, v.montant_total, v.montant_paye, (v.montant_total - v.montant_paye) as restant, c.nom as client
        FROM ventes v JOIN clients c ON v.client_id=c.id
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
                            <button class="btn btn-sm btn-primary" onclick="openPayModal(<?= (int)$r['id'] ?>, '<?= htmlspecialchars($r['numero_vente'], ENT_QUOTES) ?>', <?= (int)max(0, $r['restant']) ?>)"><i class="fas fa-plus"></i> Encaisser</button>
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
    <script>
        function openPayModal(venteId, numero, restant) {
            document.getElementById('pm_vente_id').value = venteId;
            document.getElementById('pm_invoice').textContent = numero + ' — Reste ' + new Intl.NumberFormat('fr-FR').format(restant) + ' FCFA';
            document.getElementById('pm_montant').value = restant;
            const el = document.getElementById('payModal');
            if (window.bootstrap && window.bootstrap.Modal) new bootstrap.Modal(el).show();
        }
    </script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>