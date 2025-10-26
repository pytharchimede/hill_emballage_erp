<?php
require_once 'includes/config.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}
if (!hasPermission('stock_read')) {
    setFlashMessage('warning', 'Accès refusé au Stock.');
    header('Location: dashboard.php');
    exit();
}

$userRole = $_SESSION['user_role'] ?? '';
$userDepotId = (int)($_SESSION['depot_id'] ?? 0);
$isRestricted = in_array($userRole, ['vendeur', 'comptable', 'livreur'], true) && $userDepotId > 0 && !isMainDepot($userDepotId);

$msg = '';
$msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'transfer' && hasPermission('stock_update')) {
            $prod = (int)($_POST['produit_id'] ?? 0);
            $src = (int)($_POST['depot_source'] ?? 0);
            $dst = (int)($_POST['depot_destination'] ?? 0);
            $qty = (float)($_POST['quantite'] ?? 0);
            $motif = trim($_POST['motif'] ?? '');
            if (!$prod || !$src || !$dst || $src === $dst || $qty <= 0) throw new Exception('Paramètres transfert invalides');
            // décrémenter src si dispo, incrémenter dst
            $db->beginTransaction();
            // lock rows
            $sel = $db->prepare('SELECT id, quantite FROM stock WHERE produit_id=? AND depot_id=? FOR UPDATE');
            $sel->execute([$prod, $src]);
            $rowSrc = $sel->fetch(PDO::FETCH_ASSOC);
            if (!$rowSrc || (float)$rowSrc['quantite'] < $qty) {
                throw new Exception('Stock source insuffisant');
            }
            $db->prepare('UPDATE stock SET quantite=quantite-? WHERE id=?')->execute([$qty, (int)$rowSrc['id']]);
            // upsert destination
            $sel = $db->prepare('SELECT id FROM stock WHERE produit_id=? AND depot_id=? FOR UPDATE');
            $sel->execute([$prod, $dst]);
            $rowDst = $sel->fetch(PDO::FETCH_ASSOC);
            if ($rowDst) {
                $db->prepare('UPDATE stock SET quantite=quantite+? WHERE id=?')->execute([$qty, (int)$rowDst['id']]);
            } else {
                $db->prepare('INSERT INTO stock (produit_id,depot_id,quantite) VALUES (?,?,?)')->execute([$prod, $dst, $qty]);
            }
            // historiser
            $db->prepare('INSERT INTO stock_transfers (produit_id,depot_source,depot_destination,quantite,motif,user_id) VALUES (?,?,?,?,?,?)')
                ->execute([$prod, $src, $dst, $qty, $motif, $_SESSION['user_id']]);
            $db->commit();
            $msg = 'Transfert effectué';
            $msgType = 'success';
        }
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $msg = 'Erreur: ' . $e->getMessage();
        $msgType = 'error';
    }
}

// Filtres
$search = $_GET['search'] ?? '';
$depot = (int)($_GET['depot'] ?? 0);
// Scoping par dépôt: vendeur/comptable/livreur (sauf dépôt principal)
if ($isRestricted) {
    $depot = $userDepotId;
}
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$where = 'WHERE 1=1';
$params = [];
if ($depot > 0) {
    $where .= ' AND s.depot_id=?';
    $params[] = $depot;
}
if ($search) {
    $where .= ' AND (p.nom LIKE ? OR p.code_produit LIKE ?)';
    $q = "%$search%";
    array_push($params, $q, $q);
}

$count = $db->prepare("SELECT COUNT(*) t FROM stock s JOIN products p ON s.produit_id=p.id JOIN depots d ON s.depot_id=d.id $where");
$count->execute($params);
$total = (int)($count->fetch(PDO::FETCH_ASSOC)['t'] ?? 0);
$st = $db->prepare("SELECT s.*, p.nom, p.code_produit, p.unite, p.is_active as prod_active, d.nom as depot_nom
                    FROM stock s JOIN products p ON s.produit_id=p.id JOIN depots d ON s.depot_id=d.id
                    $where ORDER BY d.nom, p.nom LIMIT $limit OFFSET $offset");
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
$pages = max(1, (int)ceil($total / $limit));

// Restreindre la liste des dépôts pour vendeurs/comptables/livreurs (hors dépôt principal)
if ($isRestricted) {
    $ds = $db->prepare('SELECT id, nom FROM depots WHERE is_active=1 AND id=? ORDER BY nom');
    $ds->execute([$userDepotId]);
    $depots = $ds->fetchAll(PDO::FETCH_ASSOC);
} else {
    $depots = $db->query('SELECT id, nom FROM depots WHERE is_active=1 ORDER BY nom')->fetchAll(PDO::FETCH_ASSOC);
}
$products = $db->query('SELECT id, nom FROM products WHERE is_active=1 ORDER BY nom')->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Stock';
include 'includes/header.php';
?>
<div class="page-header">
    <h1><i class="fas fa-warehouse"></i> Stock</h1>
    <?php if (hasPermission('stock_update')): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#transferModal"><i class="fas fa-exchange-alt"></i> Transfert</button>
    <?php endif; ?>
</div>
<?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="card">
    <form class="row g-2" method="get">
        <div class="col-md-4"><input class="form-control" name="search" placeholder="Rechercher produit/code" value="<?= htmlspecialchars($search) ?>" /></div>
        <div class="col-md-3">
            <select class="form-select" name="depot">
                <?php if (!$isRestricted): ?>
                    <option value="0">Tous dépôts</option>
                <?php endif; ?>
                <?php foreach ($depots as $d): ?><option value="<?= (int)$d['id'] ?>" <?= $depot === (int)$d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['nom']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button class="btn w-100">Filtrer</button></div>
        <div class="col-md-3">
            <a class="btn" href="<?= BASE_URL ?>/app/export/stock_xls.php?td1=<?= urlencode($td1 ?? '') ?>&td2=<?= urlencode($td2 ?? '') ?>&tdepot=<?= (int)($tdepot ?? 0) ?>&tprod=<?= urlencode($tprod ?? '') ?>">Export XLS transferts</a>
            <a class="btn" href="<?= BASE_URL ?>/app/export/stock_pdf.php?td1=<?= urlencode($td1 ?? '') ?>&td2=<?= urlencode($td2 ?? '') ?>&tdepot=<?= (int)($tdepot ?? 0) ?>&tprod=<?= urlencode($tprod ?? '') ?>">Export PDF</a>
        </div>
    </form>

    <table class="table" style="margin-top:1rem;">
        <thead>
            <tr>
                <th>Dépôt</th>
                <th>Code</th>
                <th>Produit</th>
                <th>Qté</th>
                <th>Réservée</th>
                <th>Seuil</th>
                <th>Maj</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr class="<?= ($r['quantite'] <= $r['seuill_alerte']) ? 'table-warning' : '' ?>">
                    <td><?= htmlspecialchars($r['depot_nom']) ?></td>
                    <td><?= htmlspecialchars($r['code_produit']) ?></td>
                    <td><?= htmlspecialchars($r['nom']) ?></td>
                    <td><?= (float)$r['quantite'] ?></td>
                    <td><?= (float)$r['quantite_reservee'] ?></td>
                    <td><?= (float)$r['seuill_alerte'] ?></td>
                    <td><?= htmlspecialchars($r['last_update']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr>
                    <td colspan="7">Aucune ligne</td>
                </tr><?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top:1rem;">
        <?php for ($i = 1; $i <= $pages; $i++): ?><a class="btn <?= $i === $page ? 'btn-success' : '' ?>" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&depot=<?= (int)$depot ?>"><?= $i ?></a><?php endfor; ?>
    </div>
</div>

<?php if (hasPermission('stock_update')): ?>
    <div class="modal fade" id="transferModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exchange-alt"></i> Transfert de stock</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <input type="hidden" name="action" value="transfer" />
                    <div class="modal-body">
                        <div class="mb-2"><label class="form-label">Produit</label><select class="form-select" name="produit_id" required>
                                <option value="">—</option><?php foreach ($products as $p): ?><option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['nom']) ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="row g-2">
                            <div class="col-md-6"><label class="form-label">Dépôt source</label><select class="form-select" name="depot_source" required>
                                    <option value="">—</option><?php foreach ($depots as $d): ?><option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['nom']) ?></option><?php endforeach; ?>
                                </select></div>
                            <div class="col-md-6"><label class="form-label">Dépôt destination</label><select class="form-select" name="depot_destination" required>
                                    <option value="">—</option><?php foreach ($depots as $d): ?><option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['nom']) ?></option><?php endforeach; ?>
                                </select></div>
                        </div>
                        <div class="row g-2 mt-2">
                            <div class="col-md-6"><label class="form-label">Quantité</label><input class="form-control" type="number" step="0.01" name="quantite" required /></div>
                            <div class="col-md-6"><label class="form-label">Motif</label><input class="form-control" name="motif" /></div>
                        </div>
                    </div>
                    <div class="modal-footer"><button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Annuler</button><button class="btn btn-primary" type="submit">Transférer</button></div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card" id="journal">
    <h2>Journal des transferts</h2>
    <?php
    // Filtres journal + pagination
    $td1 = $_GET['td1'] ?? '';
    $td2 = $_GET['td2'] ?? '';
    $tdepot = (int)($_GET['tdepot'] ?? 0);
    if ($isRestricted) {
        $tdepot = $userDepotId;
    }
    $tprod = trim($_GET['tprod'] ?? '');
    $jpage = max(1, (int)($_GET['jpage'] ?? 1));
    $jlimit = max(5, min(100, (int)($_GET['jlimit'] ?? 20))); // borne 5..100
    $joffset = ($jpage - 1) * $jlimit;

    $tw = 'WHERE 1=1';
    $tp = [];
    if ($td1) {
        $tw .= ' AND st.date_transfer>=?';
        $tp[] = $td1;
    }
    if ($td2) {
        $tw .= ' AND st.date_transfer<=?';
        $tp[] = $td2;
    }
    if ($tdepot) {
        $tw .= ' AND (st.depot_source=? OR st.depot_destination=?)';
        array_push($tp, $tdepot, $tdepot);
    }
    if ($tprod !== '') {
        $tw .= ' AND (p.nom LIKE ? OR p.code_produit LIKE ?)';
        $like = "%$tprod%";
        array_push($tp, $like, $like);
    }

    // total pour pagination
    $tjCount = $db->prepare("SELECT COUNT(*) AS t
                             FROM stock_transfers st
                             JOIN products p ON st.produit_id=p.id
                             JOIN depots ds ON st.depot_source=ds.id
                             JOIN depots dd ON st.depot_destination=dd.id
                             JOIN users u ON st.user_id=u.id
                             $tw");
    $tjCount->execute($tp);
    $logTotal = (int)($tjCount->fetch(PDO::FETCH_ASSOC)['t'] ?? 0);
    $jpages = max(1, (int)ceil($logTotal / $jlimit));

    // liste page courante
    $tj = $db->prepare("SELECT st.*, p.nom as produit_nom, ds.nom as depot_src, dd.nom as depot_dst, u.full_name as user_nom
                        FROM stock_transfers st
                        JOIN products p ON st.produit_id=p.id
                        JOIN depots ds ON st.depot_source=ds.id
                        JOIN depots dd ON st.depot_destination=dd.id
                        JOIN users u ON st.user_id=u.id
                        $tw ORDER BY st.date_transfer DESC LIMIT $jlimit OFFSET $joffset");
    $tj->execute($tp);
    $logRows = $tj->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <form class="row g-2" method="get">
        <div class="col-md-3"><input class="form-control" type="date" name="td1" value="<?= htmlspecialchars($td1) ?>" /></div>
        <div class="col-md-3"><input class="form-control" type="date" name="td2" value="<?= htmlspecialchars($td2) ?>" /></div>
        <div class="col-md-3">
            <select class="form-select" name="tdepot">
                <?php if (!$isRestricted): ?>
                    <option value="0">Tous dépôts</option>
                <?php endif; ?>
                <?php foreach ($depots as $d): ?><option value="<?= (int)$d['id'] ?>" <?= $tdepot === (int)$d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['nom']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <input class="form-control" name="tprod" list="productsList" placeholder="Produit ou code" value="<?= htmlspecialchars($tprod) ?>" />
            <datalist id="productsList">
                <?php foreach ($products as $p): ?>
                    <option value="<?= htmlspecialchars($p['nom']) ?>"></option>
                <?php endforeach; ?>
                <?php
                // ajouter aussi les codes products distincts
                $prodCodes = $db->query('SELECT code_produit FROM products WHERE is_active=1 ORDER BY code_produit')->fetchAll(PDO::FETCH_COLUMN);
                foreach ($prodCodes as $pc): ?>
                    <option value="<?= htmlspecialchars($pc) ?>"></option>
                <?php endforeach; ?>
            </datalist>
        </div>
        <div class="col-md-2"><button class="btn w-100">Filtrer</button></div>
        <div class="col-md-1">
            <a class="btn btn-secondary w-100" href="<?= BASE_URL ?>/app/stock_export.php?td1=<?= urlencode($td1) ?>&td2=<?= urlencode($td2) ?>&tdepot=<?= (int)$tdepot ?>&tprod=<?= urlencode($tprod) ?>">CSV</a>
        </div>
    </form>

    <table class="table" style="margin-top:1rem;">
        <thead>
            <tr>
                <th>Date</th>
                <th>Produit</th>
                <th>Qté</th>
                <th>De</th>
                <th>Vers</th>
                <th>Motif</th>
                <th>Par</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logRows as $lr): ?>
                <tr>
                    <td><?= htmlspecialchars($lr['date_transfer']) ?></td>
                    <td><?= htmlspecialchars($lr['produit_nom']) ?></td>
                    <td><?= (float)$lr['quantite'] ?></td>
                    <td><?= htmlspecialchars($lr['depot_src']) ?></td>
                    <td><?= htmlspecialchars($lr['depot_dst']) ?></td>
                    <td><?= htmlspecialchars($lr['motif']) ?></td>
                    <td><?= htmlspecialchars($lr['user_nom']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$logRows): ?><tr>
                    <td colspan="7">Aucun mouvement</td>
                </tr><?php endif; ?>
        </tbody>
    </table>
    <div style="margin-top:1rem;">
        <?php for ($j = 1; $j <= $jpages; $j++): ?>
            <a class="btn <?= $j === $jpage ? 'btn-success' : '' ?>" href="?td1=<?= urlencode($td1) ?>&td2=<?= urlencode($td2) ?>&tdepot=<?= (int)$tdepot ?>&tprod=<?= urlencode($tprod) ?>&jpage=<?= $j ?>&jlimit=<?= $jlimit ?>#journal">
                <?= $j ?>
            </a>
        <?php endfor; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>