<?php
require_once 'includes/config.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}
if (!hasPermission('payments_read')) {
    setFlashMessage('warning', "Accès refusé à Paiements.");
    header('Location: dashboard.php');
    exit();
}

$msg = '';
$msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create' && hasPermission('payments_create')) {
            $vente_id = (int)($_POST['vente_id'] ?? 0);
            $montant = (float)($_POST['montant'] ?? 0);
            $date_payment = $_POST['date_payment'] ?? date('Y-m-d');
            $mode = $_POST['mode_payment'] ?? 'espece';
            $ref = trim($_POST['reference'] ?? '');
            if ($vente_id <= 0 || $montant <= 0) throw new Exception('Vente ou montant invalide');
            // generate receipt number
            $numero = 'RC-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
            $st = $db->prepare("INSERT INTO payments (vente_id, numero_recu, date_payment, montant, mode_payment, statut, reference) VALUES (?,?,?,?,?,'valide',?)");
            $st->execute([$vente_id, $numero, $date_payment, $montant, $mode, $ref]);
            // update sale paid amount
            $db->prepare("UPDATE ventes SET montant_paye = montant_paye + ? WHERE id=?")->execute([$montant, $vente_id]);
            $db->prepare("UPDATE ventes SET statut = CASE WHEN montant_paye >= montant_total THEN 'validee' ELSE statut END WHERE id=?")->execute([$vente_id]);
            $msg = 'Paiement enregistré';
            $msgType = 'success';
        }
    } catch (Exception $e) {
        $msg = 'Erreur: ' . $e->getMessage();
        $msgType = 'error';
    }
}

// Filters
$search = $_GET['search'] ?? '';
$d1 = $_GET['d1'] ?? '';
$d2 = $_GET['d2'] ?? '';
$mode = $_GET['mode'] ?? 'all';
$statut = $_GET['statut'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where = 'WHERE 1=1';
$params = [];
if ($search) {
    $where .= " AND (p.numero_recu LIKE ? OR v.numero_vente LIKE ? OR c.nom LIKE ?)";
    $q = "%$search%";
    array_push($params, $q, $q, $q);
}
if ($mode !== 'all') {
    $where .= ' AND p.mode_payment=?';
    $params[] = $mode;
}
if ($statut !== 'all') {
    $where .= ' AND p.statut=?';
    $params[] = $statut;
}
if ($d1) {
    $where .= ' AND p.date_payment>=?';
    $params[] = $d1;
}
if ($d2) {
    $where .= ' AND p.date_payment<=?';
    $params[] = $d2;
}

$countSql = "SELECT COUNT(*) t FROM payments p JOIN ventes v ON p.vente_id=v.id JOIN clients c ON v.client_id=c.id $where";
$cs = $db->prepare($countSql);
$cs->execute($params);
$total = (int)($cs->fetch(PDO::FETCH_ASSOC)['t'] ?? 0);

$sql = "SELECT p.*, v.numero_vente, c.nom as client_nom FROM payments p
        JOIN ventes v ON p.vente_id=v.id JOIN clients c ON v.client_id=c.id
        $where ORDER BY p.created_at DESC LIMIT $limit OFFSET $offset";
$st = $db->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
$pages = max(1, (int)ceil($total / $limit));

// ventes ouvertes pour encaissement
$open = $db->query("SELECT id, numero_vente, (montant_total - montant_paye) as restant FROM ventes WHERE (montant_total - montant_paye) > 0 ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Paiements';
include 'includes/header.php';
?>
<div class="page-header">
    <h1><i class="fas fa-credit-card"></i> Paiements</h1>
    <?php if (hasPermission('payments_create')): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPaymentModal"><i class="fas fa-plus"></i> Nouveau paiement</button>
    <?php endif; ?>
</div>
<?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="card">
    <form class="row g-2" method="get">
        <div class="col-md-3"><input class="form-control" name="search" placeholder="Rechercher (reçu/vente/client)" value="<?= htmlspecialchars($search) ?>" /></div>
        <div class="col-md-2"><input class="form-control" type="date" name="d1" value="<?= htmlspecialchars($d1) ?>" /></div>
        <div class="col-md-2"><input class="form-control" type="date" name="d2" value="<?= htmlspecialchars($d2) ?>" /></div>
        <div class="col-md-2">
            <select class="form-select" name="mode">
                <option value="all" <?= $mode === 'all' ? 'selected' : '' ?>>Tous modes</option>
                <?php foreach (['espece', 'cheque', 'virement', 'mobile'] as $m): ?>
                    <option value="<?= $m ?>" <?= $mode === $m ? 'selected' : '' ?>><?= ucfirst($m) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="statut">
                <option value="all" <?= $statut === 'all' ? 'selected' : '' ?>>Tous statuts</option>
                <?php foreach (['valide', 'attente', 'rejete'] as $s): ?>
                    <option value="<?= $s ?>" <?= $statut === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1"><button class="btn w-100">Filtrer</button></div>
    </form>

    <table class="table" style="margin-top:1rem;">
        <thead>
            <tr>
                <th>Reçu</th>
                <th>Date</th>
                <th>Vente</th>
                <th>Client</th>
                <th>Montant</th>
                <th>Mode</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['numero_recu']) ?></td>
                    <td><?= htmlspecialchars($p['date_payment']) ?></td>
                    <td><?= htmlspecialchars($p['numero_vente']) ?></td>
                    <td><?= htmlspecialchars($p['client_nom']) ?></td>
                    <td><?= number_format($p['montant'], 0, ',', ' ') ?></td>
                    <td><?= htmlspecialchars($p['mode_payment']) ?></td>
                    <td><?= htmlspecialchars($p['statut']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr>
                    <td colspan="7">Aucun paiement</td>
                </tr><?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top:1rem;">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a class="btn <?= $i === $page ? 'btn-success' : '' ?>" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&d1=<?= urlencode($d1) ?>&d2=<?= urlencode($d2) ?>&mode=<?= urlencode($mode) ?>&statut=<?= urlencode($statut) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>

<?php if (hasPermission('payments_create')): ?>
    <div class="modal fade" id="addPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Nouveau paiement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <input type="hidden" name="action" value="create" />
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Vente</label>
                            <select class="form-select" name="vente_id" required>
                                <option value="">-- choisir --</option>
                                <?php foreach ($open as $o): ?>
                                    <option value="<?= (int)$o['id'] ?>"><?= htmlspecialchars($o['numero_vente']) ?> — Reste <?= number_format($o['restant'], 0, ',', ' ') ?> FCFA</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6"><label class="form-label">Date</label><input class="form-control" type="date" name="date_payment" value="<?= date('Y-m-d') ?>" /></div>
                            <div class="col-md-6"><label class="form-label">Montant</label><input class="form-control" type="number" step="0.01" name="montant" required /></div>
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
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button class="btn btn-primary" type="submit">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>