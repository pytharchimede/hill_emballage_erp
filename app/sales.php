<?php
require_once 'includes/config.php';

// Auth + permissions
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}
if (!hasPermission('sales_read')) {
    setFlashMessage('warning', "Accès refusé aux Ventes.");
    header('Location: dashboard.php');
    exit();
}

// Helper
function colExistsSales(PDO $db, $table, $column)
{
    try {
        $s = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $s->execute([$table, $column]);
        return (bool)$s->fetchColumn();
    } catch (Exception $e) {
        return false;
    }
}

// Create / Update / Delete sale
$msg = '';
$msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create' && hasPermission('sales_create')) {
            // Basic sale creation with items
            $client_id = (int)($_POST['client_id'] ?? 0);
            $type_vente = $_POST['type_vente'] ?? 'comptant';
            $date_vente = $_POST['date_vente'] ?? date('Y-m-d');
            $date_echeance = $_POST['date_echeance'] ?: null;
            $commentaire = trim($_POST['commentaire'] ?? '');
            $items = $_POST['items'] ?? [];

            if ($client_id <= 0 || empty($items)) {
                throw new Exception("Client ou articles manquants");
            }

            // Compute total
            $total = 0.0;
            foreach ($items as $it) {
                $q = (float)($it['qty'] ?? 0);
                $p = (float)($it['price'] ?? 0);
                if ($q > 0 && $p >= 0) $total += $q * $p;
            }
            if ($total <= 0) {
                throw new Exception("Montant total invalide");
            }

            // numero_vente
            $numero = 'V-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(2)), 0, 4);

            $db->beginTransaction();
            $st = $db->prepare("INSERT INTO ventes (client_id, user_id, numero_vente, date_vente, type_vente, montant_total, montant_paye, statut, date_echeance, commentaire) VALUES (?,?,?,?,?,?,0,'en_attente',?,?)");
            $st->execute([$client_id, $_SESSION['user_id'], $numero, $date_vente, $type_vente, $total, $date_echeance, $commentaire]);
            $vente_id = (int)$db->lastInsertId();

            $sti = $db->prepare("INSERT INTO vente_items (vente_id, produit_id, nom_produit, quantite, prix_unitaire, montant) VALUES (?,?,?,?,?,?)");
            foreach ($items as $it) {
                $pid = (int)$it['product_id'];
                $q = (float)$it['qty'];
                $p = (float)$it['price'];
                if ($pid && $q > 0) {
                    // fetch product name quickly
                    $name = '';
                    $ps = $db->prepare("SELECT nom FROM produits WHERE id=?");
                    $ps->execute([$pid]);
                    $name = ($ps->fetch(PDO::FETCH_ASSOC)['nom'] ?? 'Produit');
                    $sti->execute([$vente_id, $pid, $name, $q, $p, $q * $p]);
                }
            }
            $db->commit();
            log_action('CREATE', 'ventes', $vente_id, ['numero' => $numero, 'client_id' => $client_id, 'total' => $total]);
            $msg = 'Vente enregistrée';
            $msgType = 'success';
        }
        if ($action === 'update_status' && hasPermission('sales_update')) {
            $id = (int)($_POST['id'] ?? 0);
            $statut = $_POST['statut'] ?? 'en_attente';
            $st = $db->prepare("UPDATE ventes SET statut=?, updated_at=NOW() WHERE id=?");
            $st->execute([$statut, $id]);
            log_action('UPDATE', 'ventes', $id, ['statut' => $statut]);
            $msg = 'Statut mis à jour';
            $msgType = 'success';
        }
        if ($action === 'delete' && hasPermission('sales_delete')) {
            $id = (int)($_POST['id'] ?? 0);
            // delete items then sale
            $db->beginTransaction();
            $db->prepare("DELETE FROM vente_items WHERE vente_id=?")->execute([$id]);
            $db->prepare("DELETE FROM ventes WHERE id=?")->execute([$id]);
            $db->commit();
            log_action('DELETE', 'ventes', $id);
            $msg = 'Vente supprimée';
            $msgType = 'success';
        }
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $msg = 'Erreur: ' . $e->getMessage();
        $msgType = 'error';
    }
}

// Filters
$search = $_GET['search'] ?? '';
$statut = $_GET['statut'] ?? 'all';
$type = $_GET['type'] ?? 'all';
$d1 = $_GET['d1'] ?? '';
$d2 = $_GET['d2'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where = 'WHERE 1=1';
$params = [];
if ($search) {
    $where .= " AND (v.numero_vente LIKE ? OR c.nom LIKE ? OR c.entreprise LIKE ? OR c.email LIKE ?)";
    $q = "%$search%";
    array_push($params, $q, $q, $q, $q);
}
if ($statut !== 'all') {
    $where .= " AND v.statut=?";
    $params[] = $statut;
}
if ($type !== 'all') {
    $where .= " AND v.type_vente=?";
    $params[] = $type;
}
if ($d1) {
    $where .= " AND v.date_vente>=?";
    $params[] = $d1;
}
if ($d2) {
    $where .= " AND v.date_vente<=?";
    $params[] = $d2;
}

$countSql = "SELECT COUNT(*) as t FROM ventes v LEFT JOIN clients c ON v.client_id=c.id $where";
$cs = $db->prepare($countSql);
$cs->execute($params);
$total = (int)($cs->fetch(PDO::FETCH_ASSOC)['t'] ?? 0);

$sql = "SELECT v.*, c.nom as client_nom, c.entreprise, u.full_name as vendeur_nom FROM ventes v
        LEFT JOIN clients c ON v.client_id=c.id
        LEFT JOIN users u ON v.user_id=u.id
        $where ORDER BY v.created_at DESC LIMIT $limit OFFSET $offset";
$st = $db->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
$pages = max(1, (int)ceil($total / $limit));

// Data for forms
$clients = $db->query("SELECT id, COALESCE(CONCAT(nom, ' ', IFNULL(prenom,'')), nom) as label FROM clients WHERE is_active=1 ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
$products = $db->query("SELECT id, nom, prix_unitaire, prix_credit FROM produits WHERE is_active=1 ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Ventes';
log_action('VIEW', 'ventes');
include 'includes/header.php';
?>
<div class="page-header">
    <h1><i class="fas fa-shopping-cart"></i> Ventes</h1>
    <?php if (hasPermission('sales_create')): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSaleModal"><i class="fas fa-plus"></i> Nouvelle vente</button>
    <?php endif; ?>
</div>
<?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="card">
    <form method="get" class="row g-2">
        <div class="col-md-3"><input class="form-control" type="text" name="search" placeholder="Rechercher (N°/Client)" value="<?= htmlspecialchars($search) ?>"></div>
        <div class="col-md-2">
            <select class="form-select" name="statut">
                <option value="all" <?= $statut === 'all' ? 'selected' : '' ?>>Tous statuts</option>
                <option value="en_attente" <?= $statut === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                <option value="validee" <?= $statut === 'validee' ? 'selected' : '' ?>>Validée</option>
                <option value="livree" <?= $statut === 'livree' ? 'selected' : '' ?>>Livrée</option>
                <option value="annulee" <?= $statut === 'annulee' ? 'selected' : '' ?>>Annulée</option>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="type">
                <option value="all" <?= $type === 'all' ? 'selected' : '' ?>>Tous types</option>
                <option value="comptant" <?= $type === 'comptant' ? 'selected' : '' ?>>Comptant</option>
                <option value="credit" <?= $type === 'credit' ? 'selected' : '' ?>>Crédit</option>
            </select>
        </div>
        <div class="col-md-2"><input class="form-control" type="date" name="d1" value="<?= htmlspecialchars($d1) ?>" /></div>
        <div class="col-md-2"><input class="form-control" type="date" name="d2" value="<?= htmlspecialchars($d2) ?>" /></div>
        <div class="col-md-1"><button class="btn w-100">Filtrer</button></div>
        <div class="col-md-2">
            <a class="btn w-100" href="<?= BASE_URL ?>/app/export/sales_xls.php?search=<?= urlencode($search) ?>&statut=<?= urlencode($statut) ?>&type=<?= urlencode($type) ?>&d1=<?= urlencode($d1) ?>&d2=<?= urlencode($d2) ?>">Export XLS</a>
        </div>
        <div class="col-md-2">
            <a class="btn w-100" href="<?= BASE_URL ?>/app/export/sales_pdf.php?search=<?= urlencode($search) ?>&statut=<?= urlencode($statut) ?>&type=<?= urlencode($type) ?>&d1=<?= urlencode($d1) ?>&d2=<?= urlencode($d2) ?>">Export PDF</a>
        </div>
    </form>

    <table class="table" data-type="sales" style="margin-top:1rem;">
        <thead>
            <tr>
                <th>N°</th>
                <th>Date</th>
                <th>Client</th>
                <th>Type</th>
                <th>Statut</th>
                <th>Total</th>
                <th>Payé</th>
                <th>Reste</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $v): $reste = (float)$v['montant_total'] - (float)$v['montant_paye']; ?>
                <tr>
                    <td><?= htmlspecialchars($v['numero_vente']) ?></td>
                    <td><?= htmlspecialchars($v['date_vente']) ?></td>
                    <td><?= htmlspecialchars(trim(($v['client_nom'] ?? '') . ' ' . ($v['entreprise'] ? ('(' . $v['entreprise'] . ')') : ''))) ?></td>
                    <td><?= htmlspecialchars($v['type_vente']) ?></td>
                    <td><?= htmlspecialchars($v['statut']) ?></td>
                    <td><?= number_format($v['montant_total'], 0, ',', ' ') ?></td>
                    <td><?= number_format($v['montant_paye'], 0, ',', ' ') ?></td>
                    <td><?= number_format($reste, 0, ',', ' ') ?></td>
                    <td>
                        <?php if (hasPermission('sales_update')): ?>
                            <form method="post" style="display:inline-block;">
                                <input type="hidden" name="action" value="update_status" />
                                <input type="hidden" name="id" value="<?= (int)$v['id'] ?>" />
                                <select name="statut" class="form-select" style="display:inline-block;width:auto;">
                                    <?php foreach (['en_attente', 'validee', 'livree', 'annulee'] as $st): ?>
                                        <option value="<?= $st ?>" <?= $v['statut'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-success" type="submit">OK</button>
                            </form>
                        <?php endif; ?>
                        <?php if (hasPermission('sales_delete')): ?>
                            <form method="post" style="display:inline-block;" onsubmit="return confirm('Supprimer cette vente ?');">
                                <input type="hidden" name="action" value="delete" />
                                <input type="hidden" name="id" value="<?= (int)$v['id'] ?>" />
                                <button class="btn btn-danger" type="submit"><i class="fas fa-trash"></i></button>
                            </form>
                        <?php endif; ?>
                        <a class="btn" title="Facture PDF" href="<?= BASE_URL ?>/app/export/invoice_pdf.php?id=<?= (int)$v['id'] ?>"><i class="fas fa-file-pdf"></i></a>
                        <?php if (hasPermission('sales_update')): ?>
                            <button class="btn" title="Joindre" onclick="showAttach('ventes', <?= (int)$v['id'] ?>)"><i class="fas fa-paperclip"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr>
                    <td colspan="9">Aucune vente</td>
                </tr><?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top:1rem;">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a class="btn <?= $i === $page ? 'btn-success' : '' ?>" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&statut=<?= urlencode($statut) ?>&type=<?= urlencode($type) ?>&d1=<?= urlencode($d1) ?>&d2=<?= urlencode($d2) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>

<?php if (hasPermission('sales_create')): ?>
    <div class="modal fade" id="addSaleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Nouvelle vente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" id="saleForm">
                    <input type="hidden" name="action" value="create" />
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Client</label>
                                <select class="form-select" name="client_id" required>
                                    <option value="">-- choisir --</option>
                                    <?php foreach ($clients as $c): ?>
                                        <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['label']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Date</label>
                                <input class="form-control" type="date" name="date_vente" value="<?= date('Y-m-d') ?>" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Type</label>
                                <select class="form-select" name="type_vente">
                                    <option value="comptant">Comptant</option>
                                    <option value="credit">Crédit</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Échéance (si crédit)</label>
                                <input class="form-control" type="date" name="date_echeance" />
                            </div>
                        </div>

                        <hr />
                        <div>
                            <table class="table" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th>Produit</th>
                                        <th>Prix</th>
                                        <th>Qté</th>
                                        <th>Montant</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                            <button type="button" class="btn btn-secondary" onclick="addItemRow()"><i class="fas fa-plus"></i> Ajouter un article</button>
                            <div class="text-end" style="margin-top:1rem;">
                                <strong>Total:</strong> <span id="saleTotal">0</span> FCFA
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Commentaire</label>
                            <textarea class="form-control" name="commentaire"></textarea>
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
    <script>
        const PRODUCTS = <?= json_encode($products) ?>;

        function addItemRow() {
            const tbody = document.querySelector('#itemsTable tbody');
            const tr = document.createElement('tr');
            tr.innerHTML = `
    <td>
      <select class="form-select prod" onchange="syncPrice(this)">
        <option value="">-- produit --</option>
        ${PRODUCTS.map(p=>`<option value="${p.id}" data-price="${p.prix_unitaire}">${p.nom}</option>`).join('')}
      </select>
      <input type="hidden" name="items[][product_id]" class="hid-prod" />
      <input type="hidden" name="items[][price]" class="hid-price" />
      <input type="hidden" name="items[][qty]" class="hid-qty" />
    </td>
    <td><input class="form-control price" type="number" step="0.01" value="0" oninput="recalcRow(this)"/></td>
    <td><input class="form-control qty" type="number" step="0.01" value="1" oninput="recalcRow(this)"/></td>
    <td class="montant">0</td>
    <td><button type="button" class="btn btn-danger" onclick="this.closest('tr').remove();recalcTotal();">&times;</button></td>
  `;
            tbody.appendChild(tr);
        }

        function syncPrice(sel) {
            const opt = sel.selectedOptions[0];
            const price = parseFloat(opt?.dataset?.price || '0');
            const tr = sel.closest('tr');
            tr.querySelector('.price').value = price.toString();
            recalcRow(tr.querySelector('.qty'));
        }

        function recalcRow(input) {
            const tr = input.closest('tr');
            const price = parseFloat(tr.querySelector('.price').value || '0');
            const qty = parseFloat(tr.querySelector('.qty').value || '0');
            const m = (price * qty) || 0;
            tr.querySelector('.montant').innerText = m.toFixed(0);
            // populate hiddens
            const prod = tr.querySelector('.prod').value;
            tr.querySelector('.hid-prod').value = prod;
            tr.querySelector('.hid-price').value = price;
            tr.querySelector('.hid-qty').value = qty;
            recalcTotal();
        }

        function recalcTotal() {
            let t = 0;
            document.querySelectorAll('#itemsTable tbody tr').forEach(tr => {
                t += parseFloat(tr.querySelector('.montant').innerText || '0');
            });
            document.getElementById('saleTotal').innerText = t.toLocaleString('fr-FR');
        }
    </script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

<div class="modal fade" id="attachModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-paperclip"></i> Joindre un document</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="<?= BASE_URL ?>/app/api/upload_attachment.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="entity" id="att_entity" value="" />
                    <input type="hidden" name="entity_id" id="att_entity_id" value="" />
                    <input type="hidden" name="redirect" value="<?= BASE_URL ?>/web_admin/sales.php" />
                    <div class="mb-3"><input class="form-control" type="file" name="file" required /></div>
                    <div id="att_list" class="small"></div>
                </div>
                <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Fermer</button><button class="btn btn-primary" type="submit">Envoyer</button></div>
            </form>
        </div>
    </div>
</div>
<script>
    function showAttach(entity, id) {
        document.getElementById('att_entity').value = entity;
        document.getElementById('att_entity_id').value = id;
        loadAttachments(entity, id);
        const el = document.getElementById('attachModal');
        if (window.bootstrap && window.bootstrap.Modal) new bootstrap.Modal(el).show();
        else if (window.__fallbackShowModal) window.__fallbackShowModal('attachModal');
    }
    async function loadAttachments(entity, id) {
        try {
            const resp = await fetch('<?= BASE_URL ?>/app/export/attachments_list.php?entity=' + encodeURIComponent(entity) + '&id=' + id);
            const html = await resp.text();
            document.getElementById('att_list').innerHTML = html;
        } catch (e) {
            document.getElementById('att_list').innerHTML = '<em>Erreur de chargement.</em>';
        }
    }
    async function deleteAttachment(attId) {
        if (!confirm('Supprimer cette pièce ?')) return;
        const resp = await fetch('<?= BASE_URL ?>/app/export/attachments_delete.php?id=' + attId, {
            method: 'POST'
        });
        location.reload();
    }
</script>