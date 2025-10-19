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
            $imgPath = null;

            // Upload image optionnel
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $f = $_FILES['image'];
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
                $mime = @mime_content_type($f['tmp_name']);
                if (isset($allowed[$mime])) {
                    $ext = $allowed[$mime];
                    $dirBase = __DIR__ . '/../web_admin/uploads/products';
                    if (!is_dir($dirBase)) {
                        @mkdir($dirBase, 0777, true);
                    }
                    $fileName = 'p_' . date('YmdHis') . '_' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $ext;
                    $dest = $dirBase . DIRECTORY_SEPARATOR . $fileName;
                    if (@move_uploaded_file($f['tmp_name'], $dest)) {
                        $imgPath = BASE_URL . '/web_admin/uploads/products/' . $fileName;
                    }
                }
            }

            if ($tbl === 'products') {
                $sql = "INSERT INTO products (nom, code_produit, description, unite, prix_unitaire, prix_credit, points_fidelite, image_path, is_active) VALUES (?,?,?,?,?,?,?,?,1)";
                $st = $db->prepare($sql);
                $st->execute([$name, $code, $desc, $unit, $price, $price_credit, $points, $imgPath]);
            } else {
                $sql = "INSERT INTO produits (nom, code_produit, description, unite, prix_unitaire, prix_credit, points_fidelite, image_path, is_active) VALUES (?,?,?,?,?,?,?,?,1)";
                $st = $db->prepare($sql);
                $st->execute([$name, $code, $desc, $unit, $price, $price_credit, $points, $imgPath]);
            }
            // log action
            $newId = (int)$db->lastInsertId();
            log_action('CREATE', 'produits', $newId, ['code' => $code, 'nom' => $name]);
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
            $imgSetSql = '';
            $imgVal = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $f = $_FILES['image'];
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
                $mime = @mime_content_type($f['tmp_name']);
                if (isset($allowed[$mime])) {
                    $ext = $allowed[$mime];
                    $dirBase = __DIR__ . '/../web_admin/uploads/products';
                    if (!is_dir($dirBase)) {
                        @mkdir($dirBase, 0777, true);
                    }
                    $fileName = 'p_' . date('YmdHis') . '_' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $ext;
                    $dest = $dirBase . DIRECTORY_SEPARATOR . $fileName;
                    if (@move_uploaded_file($f['tmp_name'], $dest)) {
                        $imgVal = BASE_URL . '/web_admin/uploads/products/' . $fileName;
                        $imgSetSql = ', image_path = ? ';
                    }
                }
            }

            $sql = "UPDATE $tbl SET nom=?, code_produit=?, description=?, unite=?, prix_unitaire=?, prix_credit=?, points_fidelite=?" . $imgSetSql . ", updated_at=NOW() WHERE id=?";
            $st = $db->prepare($sql);
            $paramsUpd = [$name, $code, $desc, $unit, $price, $price_credit, $points];
            if ($imgSetSql) {
                $paramsUpd[] = $imgVal;
            }
            $paramsUpd[] = $id;
            $st->execute($paramsUpd);
            log_action('UPDATE', 'produits', $id, ['code' => $code, 'nom' => $name]);
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
            log_action('DELETE', 'produits', $id);
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
// Log view list
log_action('VIEW', 'produits');
include 'includes/header.php';
?>
<div class="page-header">
    <h1><i class="fas fa-box"></i> Produits</h1>
    <?php if (hasPermission('products_create')): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal"><i class="fas fa-plus"></i> Nouveau produit</button>
    <?php endif; ?>
</div>
<?php if ($message): ?><div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<div class="card">
    <form method="get" class="row g-2">
        <div class="col-md-6"><input class="form-control" type="text" name="search" placeholder="Rechercher (nom/code)" value="<?= htmlspecialchars($search) ?>" /></div>
        <div class="col-md-2"><button class="btn w-100" type="submit"><i class="fas fa-search"></i> Rechercher</button></div>
        <div class="col-md-2">
            <a class="btn w-100" href="<?= BASE_URL ?>/app/export/products_xls.php?search=<?= urlencode($search) ?>">Export XLS</a>
        </div>
        <div class="col-md-2">
            <a class="btn w-100" href="<?= BASE_URL ?>/app/export/products_pdf.php?search=<?= urlencode($search) ?>">Export PDF</a>
        </div>
    </form>

    <table class="table" data-type="products" style="margin-top:1rem;">
        <thead>
            <tr>
                <th>Image</th>
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
                    <td>
                        <?php if (!empty($p['image_path'])): ?>
                            <img src="<?= htmlspecialchars($p['image_path']) ?>" alt="img" style="width:48px;height:48px;border-radius:6px;object-fit:cover;" />
                        <?php else: ?>
                            <div style="width:48px;height:48px;border-radius:6px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;"><i class="fas fa-box"></i></div>
                        <?php endif; ?>
                    </td>
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
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Ajouter un produit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create" />
                    <div class="modal-body">
                        <div class="form-group"><label>Nom</label><input class="form-control" name="name" required /></div>
                        <div class="form-group"><label>Code</label><input class="form-control" name="code" required /></div>
                        <div class="form-group"><label>Unité</label><input class="form-control" name="unit" value="pièce" /></div>
                        <div class="form-group"><label>Prix unitaire</label><input class="form-control" name="price" type="number" step="0.01" required /></div>
                        <div class="form-group"><label>Prix crédit</label><input class="form-control" name="price_credit" type="number" step="0.01" /></div>
                        <div class="form-group"><label>Points fidélité</label><input class="form-control" name="points" type="number" value="1" /></div>
                        <div class="form-group"><label>Description</label><textarea class="form-control" name="description"></textarea></div>
                        <div class="form-group"><label>Image (optionnel)</label><input class="form-control" name="image" type="file" accept="image/*" /></div>
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

<?php if (hasPermission('products_update')): ?>
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier le produit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update" />
                    <input type="hidden" name="id" id="edit_id" />
                    <div class="modal-body">
                        <div class="form-group"><label>Nom</label><input class="form-control" name="name" id="edit_name" required /></div>
                        <div class="form-group"><label>Code</label><input class="form-control" name="code" id="edit_code" required /></div>
                        <div class="form-group"><label>Unité</label><input class="form-control" name="unit" id="edit_unit" /></div>
                        <div class="form-group"><label>Prix unitaire</label><input class="form-control" name="price" id="edit_price" type="number" step="0.01" required /></div>
                        <div class="form-group"><label>Prix crédit</label><input class="form-control" name="price_credit" id="edit_price_credit" type="number" step="0.01" /></div>
                        <div class="form-group"><label>Points fidélité</label><input class="form-control" name="points" id="edit_points" type="number" /></div>
                        <div class="form-group"><label>Description</label><textarea class="form-control" name="description" id="edit_description"></textarea></div>
                        <div class="form-group"><label>Image (remplacer)</label><input class="form-control" name="image" type="file" accept="image/*" /></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button class="btn btn-primary" type="submit">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        function fillEdit(id) {
            const row = [...document.querySelectorAll('table tbody tr')].find(tr => tr.querySelector('button.btn') && tr.querySelector('button.btn').getAttribute('onclick') === `fillEdit(${id})`);
            if (!row) return;
            const tds = row.querySelectorAll('td');
            document.getElementById('edit_id').value = id;
            // With image column at index 0, shift indexes by +1
            document.getElementById('edit_code').value = tds[1].innerText.trim();
            document.getElementById('edit_name').value = tds[2].innerText.trim();
            document.getElementById('edit_unit').value = tds[3].innerText.trim();
            document.getElementById('edit_price').value = tds[4].innerText.replace(/\s/g, '');
            document.getElementById('edit_price_credit').value = tds[5].innerText === '-' ? '' : tds[5].innerText.replace(/\s/g, '');
            document.getElementById('edit_points').value = tds[6].innerText.trim();
            // Ouvrir le modal via Bootstrap ou fallback
            const el = document.getElementById('editProductModal');
            if (window.bootstrap && window.bootstrap.Modal) {
                new bootstrap.Modal(el).show();
            } else if (window.__fallbackShowModal) {
                window.__fallbackShowModal('editProductModal');
            } else {
                el.classList.add('show');
                el.style.display = 'block';
            }
        }
    </script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>