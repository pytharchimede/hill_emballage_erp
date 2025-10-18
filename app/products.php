<?php
require_once 'includes/config.php';

// Vérifier la connexion
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Vérifier les permissions
if (!hasPermission('products_read')) {
    setFlashMessage('warning', "Accès refusé à Produits.");
    header('Location: dashboard.php');
    exit();
}

// Helpers de compatibilité
define('HAS_PRODUCTS_TABLE', true);
function colExists(PDO $db, $table, $column)
{
    try {
        $s = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $s->execute([$table, $column]);
        return (bool)$s->fetchColumn();
    } catch (Exception $e) {
        return false;
    }
}

// Normaliser table/colonnes
$tbl = colExists($db, 'products', 'id') ? 'products' : (colExists($db, 'produits', 'id') ? 'produits' : null);
if (!$tbl) {
    include 'includes/header.php';
    echo "<div class='alert alert-error'>Table produits introuvable.</div>";
    include 'includes/footer.php';
    exit;
}

// Actions
$message = '';
$messageType = '';
$action = $_POST['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($action === 'create' && hasPermission('products_create')) {
            $name = trim($_POST['name'] ?? '');
            $code = trim($_POST['code'] ?? '');
            $unit = trim($_POST['unit'] ?? 'pièce');
            $price = floatval($_POST['price'] ?? 0);
            $price_credit = $_POST['price_credit'] !== '' ? floatval($_POST['price_credit']) : null;
            $points = intval($_POST['points'] ?? 1);
            $desc = trim($_POST['description'] ?? '');

            if ($tbl === 'products') {
                $sql = "INSERT INTO products (nom, code_produit, description, unite, prix_unitaire, prix_credit, points_fidelite, is_active) VALUES (?,?,?,?,?,?,?,1)";
                $st = $db->prepare($sql);
                $st->execute([$name, $code, $desc, $unit, $price, $price_credit, $points]);
            } else {
                $sql = "INSERT INTO produits (nom, code_produit, description, unite, prix_unitaire, prix_credit, points_fidelite, is_active) VALUES (?,?,?,?,?,?,?,1)";
                $st = $db->prepare($sql);
                $st->execute([$name, $code, $desc, $unit, $price, $price_credit, $points]);
            }
            $message = 'Produit créé avec succès';
            $messageType = 'success';
        }
        if ($action === 'update' && hasPermission('products_update')) {
            $id = intval($_POST['id']);
            $name = trim($_POST['name'] ?? '');
            $code = trim($_POST['code'] ?? '');
            $unit = trim($_POST['unit'] ?? 'pièce');
            $price = floatval($_POST['price'] ?? 0);
            $price_credit = $_POST['price_credit'] !== '' ? floatval($_POST['price_credit']) : null;
            $points = intval($_POST['points'] ?? 1);
            $desc = trim($_POST['description'] ?? '');

            $sql = "UPDATE $tbl SET nom=?, code_produit=?, description=?, unite=?, prix_unitaire=?, prix_credit=?, points_fidelite=?, updated_at=NOW() WHERE id=?";
            $st = $db->prepare($sql);
            $st->execute([$name, $code, $desc, $unit, $price, $price_credit, $points, $id]);
            $message = 'Produit mis à jour';
            $messageType = 'success';
        }
        if ($action === 'delete' && hasPermission('products_delete')) {
            $id = intval($_POST['id']);
            if (colExists($db, $tbl, 'is_active')) {
                $sql = "UPDATE $tbl SET is_active=0 WHERE id=?";
                $st = $db->prepare($sql);
                $st->execute([$id]);
            } else {
                $sql = "DELETE FROM $tbl WHERE id=?";
                $st = $db->prepare($sql);
                $st->execute([$id]);
            }
            $message = 'Produit supprimé';
            $messageType = 'success';
        }
    } catch (Exception $e) {
        $message = 'Erreur: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Liste + filtres
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where = colExists($db, $tbl, 'is_active') ? "WHERE is_active=1" : "WHERE 1=1";
$params = [];
if ($search) {
    $where .= " AND (nom LIKE ? OR code_produit LIKE ?)";
    $q = "%$search%";
    $params[] = $q;
    $params[] = $q;
}

$countSql = "SELECT COUNT(*) as total FROM $tbl $where";
$cs = $db->prepare($countSql);
$cs->execute($params);
$total = intval(($cs->fetch(PDO::FETCH_ASSOC)['total']) ?? 0);

$sql = "SELECT * FROM $tbl $where ORDER BY updated_at DESC, created_at DESC LIMIT $limit OFFSET $offset";
$st = $db->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
$pages = max(1, (int)ceil($total / $limit));

$pageTitle = 'Gestion des Produits';
include 'includes/header.php';
?>
<div class="page-header">
    <h1><i class="fas fa-box"></i> Produits</h1>
    <?php if (hasPermission('products_create')): ?>
        <button class="btn" onclick="openModal('addProductModal')"><i class="fas fa-plus"></i> Nouveau produit</button>
    <?php endif; ?>
</div>
<?php if ($message): ?><div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<div class="card">
    <form method="get">
        <input type="text" name="search" placeholder="Rechercher (nom/code)" value="<?= htmlspecialchars($search) ?>" />
        <button class="btn" type="submit"><i class="fas fa-search"></i> Rechercher</button>
    </form>

    <table class="table" style="margin-top:1rem;">
        <thead>
            <tr>
                <th>Code</th>
                <th>Nom</th>
                <th>Unité</th>
                <th>Prix (FCFA)</th>
                <th>Prix crédit</th>
                <th>Points</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['code_produit']) ?></td>
                    <td><?= htmlspecialchars($p['nom']) ?></td>
                    <td><?= htmlspecialchars($p['unite']) ?></td>
                    <td><?= number_format($p['prix_unitaire'], 0, ',', ' ') ?></td>
                    <td><?= $p['prix_credit'] !== null ? number_format($p['prix_credit'], 0, ',', ' ') : '-' ?></td>
                    <td><?= (int)$p['points_fidelite'] ?></td>
                    <td>
                        <?php if (hasPermission('products_update')): ?>
                            <button class="btn" onclick="fillEdit(<?= (int)$p['id'] ?>)"><i class="fas fa-edit"></i></button>
                        <?php endif; ?>
                        <?php if (hasPermission('products_delete')): ?>
                            <form method="post" style="display:inline" onsubmit="return confirm('Supprimer ce produit ?');">
                                <input type="hidden" name="action" value="delete" />
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>" />
                                <button class="btn btn-danger" type="submit"><i class="fas fa-trash"></i></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
                <tr>
                    <td colspan="7">Aucun produit</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top:1rem;">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a class="btn <?= $i === $page ? 'btn-success' : '' ?>" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>

<?php if (hasPermission('products_create')): ?>
    <div class="card" id="addProductModal">
        <h2>Ajouter un produit</h2>
        <form method="post">
            <input type="hidden" name="action" value="create" />
            <div class="form-group"><label>Nom</label><input name="name" required /></div>
            <div class="form-group"><label>Code</label><input name="code" required /></div>
            <div class="form-group"><label>Unité</label><input name="unit" value="pièce" /></div>
            <div class="form-group"><label>Prix unitaire</label><input name="price" type="number" step="0.01" required /></div>
            <div class="form-group"><label>Prix crédit</label><input name="price_credit" type="number" step="0.01" /></div>
            <div class="form-group"><label>Points fidélité</label><input name="points" type="number" value="1" /></div>
            <div class="form-group"><label>Description</label><textarea name="description"></textarea></div>
            <button class="btn btn-success" type="submit">Enregistrer</button>
        </form>
    </div>
<?php endif; ?>

<?php if (hasPermission('products_update')): ?>
    <div class="card" id="editProductModal">
        <h2>Modifier le produit</h2>
        <form method="post">
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="id" id="edit_id" />
            <div class="form-group"><label>Nom</label><input name="name" id="edit_name" required /></div>
            <div class="form-group"><label>Code</label><input name="code" id="edit_code" required /></div>
            <div class="form-group"><label>Unité</label><input name="unit" id="edit_unit" /></div>
            <div class="form-group"><label>Prix unitaire</label><input name="price" id="edit_price" type="number" step="0.01" required /></div>
            <div class="form-group"><label>Prix crédit</label><input name="price_credit" id="edit_price_credit" type="number" step="0.01" /></div>
            <div class="form-group"><label>Points fidélité</label><input name="points" id="edit_points" type="number" /></div>
            <div class="form-group"><label>Description</label><textarea name="description" id="edit_description"></textarea></div>
            <button class="btn btn-success" type="submit">Mettre à jour</button>
        </form>
    </div>
    <script>
        function fillEdit(id) {
            const row = [...document.querySelectorAll('table tbody tr')].find(tr => tr.querySelector('button.btn') && tr.querySelector('button.btn').getAttribute('onclick') === `fillEdit(${id})`);
            if (!row) return;
            const tds = row.querySelectorAll('td');
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_code').value = tds[0].innerText.trim();
            document.getElementById('edit_name').value = tds[1].innerText.trim();
            document.getElementById('edit_unit').value = tds[2].innerText.trim();
            document.getElementById('edit_price').value = tds[3].innerText.replace(/\s/g, '');
            document.getElementById('edit_price_credit').value = tds[4].innerText === '-' ? '' : tds[4].innerText.replace(/\s/g, '');
            document.getElementById('edit_points').value = tds[5].innerText.trim();
        }
    </script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>