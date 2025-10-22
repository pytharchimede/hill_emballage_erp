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
        if ($action === 'entry_create') {
            $produit_id = (int)($_POST['produit_id'] ?? 0);
            $depot_id   = (int)($_POST['depot_id'] ?? 0);
            $quantite   = (float)($_POST['quantite'] ?? 0);
            $cout       = $_POST['cout_unitaire'] !== '' ? (float)$_POST['cout_unitaire'] : null;
            $type_entry = $_POST['type_entry'] ?? 'achat';
            $reference  = trim($_POST['reference'] ?? '');
            $notes      = trim($_POST['notes'] ?? '');
            if (!$produit_id || !$depot_id || $quantite <= 0) throw new Exception('Champs invalides');
            $db->beginTransaction();
            // journaliser l'entrée
            $db->prepare('INSERT INTO stock_entries (produit_id,depot_id,quantite,cout_unitaire,reference,type_entry,notes,created_by) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$produit_id, $depot_id, $quantite, $cout, $reference, $type_entry, $notes, $_SESSION['user_id']]);
            // upsert stock
            $sel = $db->prepare('SELECT id FROM stock WHERE produit_id=? AND depot_id=? FOR UPDATE');
            $sel->execute([$produit_id, $depot_id]);
            $row = $sel->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $db->prepare('UPDATE stock SET quantite=quantite+? WHERE id=?')->execute([$quantite, (int)$row['id']]);
            } else {
                $db->prepare('INSERT INTO stock (produit_id,depot_id,quantite) VALUES (?,?,?)')->execute([$produit_id, $depot_id, $quantite]);
            }
            $db->commit();
            log_action('CREATE', 'stock_entries', null, ['produit_id' => $produit_id, 'depot_id' => $depot_id, 'q' => $quantite, 'type' => $type_entry]);
            $msg = 'Entrée de stock enregistrée';
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

$depots = $db->query('SELECT id, nom FROM depots WHERE is_active=1 ORDER BY nom')->fetchAll(PDO::FETCH_ASSOC);
$products = $prodTbl ? $db->query("SELECT id, nom FROM `$prodTbl` WHERE is_active=1 ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC) : [];

$pageTitle = 'Entrées de stock';
log_action('VIEW', 'stock_entries');
include 'includes/header.php';
?>
<div class="page-header">
    <h1><i class="fas fa-plus-square"></i> Entrées de stock</h1>
</div>
<?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="card">
    <form method="post" class="row g-3">
        <input type="hidden" name="action" value="entry_create" />
        <div class="col-md-4"><label class="form-label">Produit</label><select class="form-select" name="produit_id" required>
                <option value="">—</option>
                <?php foreach ($products as $p): ?><option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['nom']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="col-md-4"><label class="form-label">Dépôt</label><select class="form-select" name="depot_id" required>
                <option value="">—</option>
                <?php foreach ($depots as $d): ?><option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['nom']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="col-md-4"><label class="form-label">Quantité</label><input class="form-control" type="number" step="0.01" name="quantite" required /></div>
        <div class="col-md-4"><label class="form-label">Coût unitaire (optionnel)</label><input class="form-control" type="number" step="0.01" name="cout_unitaire" /></div>
        <div class="col-md-4"><label class="form-label">Type</label><select class="form-select" name="type_entry">
                <option value="achat">Achat</option>
                <option value="reception">Réception</option>
                <option value="retour_client">Retour client</option>
                <option value="autre">Autre</option>
            </select></div>
        <div class="col-md-4"><label class="form-label">Référence</label><input class="form-control" name="reference" /></div>
        <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes"></textarea></div>
        <div class="col-12"><button class="btn" type="submit">Enregistrer</button></div>
    </form>
</div>

<?php include 'includes/footer.php';
