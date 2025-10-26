<?php
require_once 'includes/config.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}
if (!hasPermission('stock_update')) {
    setFlashMessage('warning', 'Accès refusé.');
    header('Location: dashboard.php');
    exit();
}

$msg = '';
$msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'adjust_create') {
            $produit_id = (int)($_POST['produit_id'] ?? 0);
            $depot_id   = (int)($_POST['depot_id'] ?? 0);
            $delta      = (float)($_POST['delta'] ?? 0);
            $motif      = $_POST['motif'] ?? 'inventaire';
            $notes      = trim($_POST['notes'] ?? '');
            if (!$produit_id || !$depot_id || $delta == 0) throw new Exception('Champs invalides');
            // Sécurité dépôt: admin principal -> tous, sinon uniquement son dépôt
            $uRole = $_SESSION['user_role'] ?? '';
            $uDepot = (int)($_SESSION['depot_id'] ?? 0);
            $uIsMain = $uDepot > 0 && isMainDepot($uDepot);
            $okDepot = ($uRole === 'admin' && $uIsMain) ? true : ($depot_id === $uDepot);
            if (!$okDepot) throw new Exception('Dépôt non autorisé');
            $db->beginTransaction();
            $db->prepare('INSERT INTO stock_adjustments (produit_id,depot_id,delta,motif,notes,created_by) VALUES (?,?,?,?,?,?)')
                ->execute([$produit_id, $depot_id, $delta, $motif, $notes, $_SESSION['user_id']]);
            // upsert + delta
            $sel = $db->prepare('SELECT id FROM stock WHERE produit_id=? AND depot_id=? FOR UPDATE');
            $sel->execute([$produit_id, $depot_id]);
            $row = $sel->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $db->prepare('UPDATE stock SET quantite=quantite+? WHERE id=?')->execute([$delta, (int)$row['id']]);
            } else {
                $db->prepare('INSERT INTO stock (produit_id,depot_id,quantite) VALUES (?,?,?)')->execute([$produit_id, $depot_id, $delta]);
            }
            $db->commit();
            log_action('CREATE', 'stock_adjustments', null, ['produit_id' => $produit_id, 'depot_id' => $depot_id, 'delta' => $delta, 'motif' => $motif]);
            $msg = 'Ajustement enregistré';
            $msgType = 'success';
        }
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $msg = 'Erreur: ' . $e->getMessage();
        $msgType = 'error';
    }
}

// Helper pour détecter la table produits canonique (préférer 'products')
$colExists = function (PDO $db, $table, $col) {
    try {
        $s = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $s->execute([$table, $col]);
        return (bool)$s->fetchColumn();
    } catch (Exception $e) {
        return false;
    }
};
$prodTbl = $colExists($db, 'products', 'id') ? 'products' : ($colExists($db, 'produits', 'id') ? 'produits' : null);

$userRole = $_SESSION['user_role'] ?? '';
$userDepotId = (int)($_SESSION['depot_id'] ?? 0);
$isMain = $userDepotId > 0 && isMainDepot($userDepotId);
if ($userRole === 'admin' && $isMain) {
    $depots = $db->query('SELECT id, nom FROM depots WHERE is_active=1 ORDER BY nom')->fetchAll(PDO::FETCH_ASSOC);
} else {
    $st = $db->prepare('SELECT id, nom FROM depots WHERE is_active=1 AND id=? ORDER BY nom');
    $st->execute([$userDepotId]);
    $depots = $st->fetchAll(PDO::FETCH_ASSOC);
}
$products = $prodTbl ? $db->query("SELECT id, nom FROM `$prodTbl` WHERE is_active=1 ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC) : [];

$pageTitle = 'Ajustements d\'inventaire';
log_action('VIEW', 'stock_adjustments');
include 'includes/header.php';
?>
<div class="page-header">
    <h1><i class="fas fa-clipboard-check"></i> Ajustements d'inventaire</h1>
</div>
<?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="card">
    <form method="post" class="row g-3">
        <input type="hidden" name="action" value="adjust_create" />
        <div class="col-md-4"><label class="form-label">Produit</label><select class="form-select" name="produit_id" required>
                <option value="">—</option>
                <?php foreach ($products as $p): ?><option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['nom']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="col-md-4"><label class="form-label">Dépôt</label><select class="form-select" name="depot_id" required>
                <option value="">—</option>
                <?php foreach ($depots as $d): ?><option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['nom']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="col-md-4"><label class="form-label">Delta (positif ou négatif)</label><input class="form-control" type="number" step="0.01" name="delta" required /></div>
        <div class="col-md-4"><label class="form-label">Motif</label><select class="form-select" name="motif">
                <option value="inventaire">Inventaire</option>
                <option value="correction">Correction</option>
                <option value="casse">Casse</option>
                <option value="perte">Perte</option>
                <option value="autre">Autre</option>
            </select></div>
        <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes"></textarea></div>
        <div class="col-12"><button class="btn" type="submit">Enregistrer</button></div>
    </form>
</div>

<?php include 'includes/footer.php';
