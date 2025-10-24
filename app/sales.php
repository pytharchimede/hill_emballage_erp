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
// Colonnes optionnelles pour la livraison
$hasDeliveryMode = colExistsSales($db, 'ventes', 'delivery_mode');
$hasLivreurId = colExistsSales($db, 'ventes', 'livreur_id');
$hasDeliveryAddress = colExistsSales($db, 'ventes', 'delivery_address');
$hasDeliveryLat = colExistsSales($db, 'ventes', 'delivery_latitude');
$hasDeliveryLng = colExistsSales($db, 'ventes', 'delivery_longitude');
$hasDeliveryDetails = colExistsSales($db, 'ventes', 'delivery_details');
$hasDeliveryDate = colExistsSales($db, 'ventes', 'delivery_date');
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
            // Mode de vente & livraison
            $mode_vente = $_POST['mode_vente'] ?? 'sur_place';
            $livreur_id = $hasLivreurId ? (int)($_POST['livreur_id'] ?? 0) : 0;
            $del_addr = $hasDeliveryAddress ? trim($_POST['delivery_address'] ?? '') : '';
            $del_lat = $hasDeliveryLat && $_POST['delivery_latitude'] !== '' ? (float)$_POST['delivery_latitude'] : null;
            $del_lng = $hasDeliveryLng && $_POST['delivery_longitude'] !== '' ? (float)$_POST['delivery_longitude'] : null;
            $del_details = $hasDeliveryDetails ? trim($_POST['delivery_details'] ?? '') : '';
            $del_date = $hasDeliveryDate ? ($_POST['delivery_date'] ?: null) : null;

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

            // Validation livraison
            if ($mode_vente === 'livraison') {
                if ($hasLivreurId && $livreur_id <= 0) {
                    throw new Exception("Veuillez choisir un livreur pour la livraison.");
                }
                if ($hasDeliveryAddress && $hasDeliveryLat && $hasDeliveryLng) {
                    if ($del_addr !== '' && ($del_lat === null || $del_lng === null)) {
                        throw new Exception("Veuillez sélectionner l'adresse de livraison dans la liste (coordonnées manquantes).");
                    }
                }
            }

            // numero_vente
            $numero = 'V-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(2)), 0, 4);

            $db->beginTransaction();
            // Construction dynamique des colonnes pour intégrer la livraison si dispo
            $cols = ['client_id', 'user_id', 'numero_vente', 'date_vente', 'type_vente', 'montant_total', 'montant_paye', 'statut'];
            $vals = [$client_id, $_SESSION['user_id'], $numero, $date_vente, $type_vente, $total, 0, 'en_attente'];
            if (!empty($date_echeance)) {
                $cols[] = 'date_echeance';
                $vals[] = $date_echeance;
            }
            $cols[] = 'commentaire';
            $vals[] = $commentaire;
            if ($hasDeliveryMode) {
                $cols[] = 'delivery_mode';
                $vals[] = $mode_vente;
            }
            if ($mode_vente === 'livraison') {
                if ($hasLivreurId) {
                    $cols[] = 'livreur_id';
                    $vals[] = $livreur_id ?: null;
                }
                if ($hasDeliveryAddress) {
                    $cols[] = 'delivery_address';
                    $vals[] = $del_addr ?: null;
                }
                if ($hasDeliveryLat) {
                    $cols[] = 'delivery_latitude';
                    $vals[] = $del_lat;
                }
                if ($hasDeliveryLng) {
                    $cols[] = 'delivery_longitude';
                    $vals[] = $del_lng;
                }
                if ($hasDeliveryDetails) {
                    $cols[] = 'delivery_details';
                    $vals[] = $del_details ?: null;
                }
                if ($hasDeliveryDate) {
                    $cols[] = 'delivery_date';
                    $vals[] = $del_date ?: null;
                }
            }
            $sql = "INSERT INTO ventes (" . implode(',', $cols) . ") VALUES (" . rtrim(str_repeat('?,', count($cols)), ',') . ")";
            $st = $db->prepare($sql);
            $st->execute($vals);
            $vente_id = (int)$db->lastInsertId();

            $sti = $db->prepare("INSERT INTO vente_items (vente_id, produit_id, nom_produit, quantite, prix_unitaire, montant) VALUES (?,?,?,?,?,?)");
            foreach ($items as $it) {
                $pid = (int)$it['product_id'];
                $q = (float)$it['qty'];
                $p = (float)$it['price'];
                if ($pid && $q > 0) {
                    // fetch product name quickly
                    $name = '';
                    $ps = $db->prepare("SELECT nom FROM products WHERE id=?");
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

// Scoping Vendeur: restreindre aux ventes déduites du dépôt du vendeur
$userRole = $_SESSION['user_role'] ?? '';
if ($userRole === 'vendeur') {
    $depotId = (int)($_SESSION['depot_id'] ?? 0);
    if ($depotId > 0) {
        $hasVenteDepot   = (bool)$db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='ventes' AND COLUMN_NAME='depot_id'")->fetchColumn();
        $hasVenteLivreur = (bool)$db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='ventes' AND COLUMN_NAME='livreur_id'")->fetchColumn();
        $hasVenteUser    = (bool)$db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='ventes' AND COLUMN_NAME='user_id'")->fetchColumn();
        $hasVenteClient  = (bool)$db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='ventes' AND COLUMN_NAME='client_id'")->fetchColumn();
        $hasClientDepot  = (bool)$db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='clients' AND COLUMN_NAME='depot_id'")->fetchColumn();

        if ($hasVenteDepot) {
            // 1) Vente porte son dépôt
            $where .= " AND v.depot_id = ?";
            $params[] = $depotId;
        } elseif ($hasVenteLivreur) {
            // 2) Déduire via livreur de la vente
            $where .= " AND v.livreur_id IN (SELECT id FROM users WHERE depot_id = ?)";
            $params[] = $depotId;
        } elseif ($hasVenteUser) {
            // 3) Déduire via vendeur (user_id) de la vente
            $where .= " AND v.user_id IN (SELECT id FROM users WHERE depot_id = ?)";
            $params[] = $depotId;
        } elseif ($hasVenteClient && $hasClientDepot) {
            // 4) Dernier recours: dépôt du client
            $where .= " AND c.depot_id = ?";
            $params[] = $depotId;
        }
    }
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
$clients = [];
try {
    $isActiveCol = (bool)$db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='clients' AND COLUMN_NAME='is_active'")->fetchColumn();
    $hasClientDepot = (bool)$db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='clients' AND COLUMN_NAME='depot_id'")->fetchColumn();
    $hasClientLivreur = (bool)$db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='clients' AND COLUMN_NAME='livreur_id'")->fetchColumn();
    $baseWhere = $isActiveCol ? 'WHERE is_active=1' : 'WHERE 1=1';
    $sqlC = "SELECT id, COALESCE(CONCAT(nom, ' ', IFNULL(prenom,'')), nom) as label FROM clients $baseWhere";
    $paramsC = [];
    if ($userRole === 'vendeur') {
        $depotId = (int)($_SESSION['depot_id'] ?? 0);
        if ($depotId > 0) {
            if ($hasClientDepot) {
                $sqlC .= " AND depot_id = ?";
                $paramsC[] = $depotId;
            } elseif ($hasClientLivreur) {
                $sqlC .= " AND (livreur_id IN (SELECT id FROM users WHERE depot_id = ?) OR livreur_id IS NULL)";
                $paramsC[] = $depotId;
            }
        }
    }
    $sqlC .= " ORDER BY nom";
    $stc = $db->prepare($sqlC);
    $stc->execute($paramsC);
    $clients = $stc->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $clients = [];
}
$products = $db->query("SELECT id, nom, prix_unitaire, prix_credit FROM products WHERE is_active=1 ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);

// Préparer liste des livreurs (priorité: même dépôt que le vendeur si disponible)
$livreurs = [];
try {
    $roleCol = colExistsSales($db, 'users', 'role') ? 'role' : (colExistsSales($db, 'users', 'user_role') ? 'user_role' : null);
    $hasUserActive = colExistsSales($db, 'users', 'is_active');
    $hasUserDepot = colExistsSales($db, 'users', 'depot_id');
    $depotSeller = null;
    if ($hasUserDepot) {
        $s = $db->prepare("SELECT depot_id FROM users WHERE id=?");
        $s->execute([$_SESSION['user_id']]);
        $depotSeller = (int)($s->fetch(PDO::FETCH_ASSOC)['depot_id'] ?? 0) ?: null;
    }
    if ($roleCol) {
        if ($depotSeller && $hasUserDepot) {
            // Vendeur: uniquement les livreurs de son dépôt
            $sqlL = "SELECT id, full_name FROM users WHERE $roleCol='livreur'" . ($hasUserActive ? " AND is_active=1" : "") . " AND depot_id=? ORDER BY full_name";
            $stL = $db->prepare($sqlL);
            $stL->execute([$depotSeller]);
            $livreurs = $stL->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $sqlL = "SELECT id, full_name FROM users WHERE $roleCol='livreur'" . ($hasUserActive ? " AND is_active=1" : "") . " ORDER BY full_name";
            $livreurs = $db->query($sqlL)->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Exception $e) {
    $livreurs = [];
}

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
                            <button class="btn btn-attach" title="Joindre" data-attach-entity="ventes" data-attach-id="<?= (int)$v['id'] ?>"><i class="fas fa-paperclip"></i></button>
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
                                <label class="form-label">Mode</label>
                                <select class="form-select" name="mode_vente" id="mode_vente">
                                    <option value="sur_place">Sur place</option>
                                    <option value="livraison">À la livraison</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Échéance (si crédit)</label>
                                <input class="form-control" type="date" name="date_echeance" />
                            </div>
                        </div>

                        <hr />
                        <div id="delivery_section" style="display:none;">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Livreur</label>
                                    <select class="form-select" name="livreur_id" id="livreur_id" <?= $hasLivreurId ? '' : 'disabled' ?>>
                                        <option value="">-- choisir --</option>
                                        <?php foreach ($livreurs as $lv): ?>
                                            <option value="<?= (int)$lv['id'] ?>"><?= htmlspecialchars($lv['full_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (!$hasLivreurId): ?><div class="small text-warning">Colonne livreur_id manquante (migration requise).</div><?php endif; ?>
                                </div>
                                <div class="col-md-6 position-relative">
                                    <label class="form-label">Adresse de livraison</label>
                                    <div class="input-group">
                                        <input class="form-control" name="delivery_address" id="delivery_adresse" autocomplete="off" placeholder="Tapez une adresse précise" <?= $hasDeliveryAddress ? '' : 'disabled' ?> />
                                        <button class="btn btn-outline-secondary" type="button" id="delivery_locate_btn" title="Ma position"><i class="fas fa-location-crosshairs"></i></button>
                                    </div>
                                    <div id="delivery_suggestions" class="list-group" style="position:absolute; z-index:1080; width:100%; max-height:220px; overflow:auto; display:none;"></div>
                                </div>
                                <?php if ($hasDeliveryLat && $hasDeliveryLng): ?>
                                    <input type="hidden" name="delivery_latitude" id="delivery_latitude" />
                                    <input type="hidden" name="delivery_longitude" id="delivery_longitude" />
                                    <div class="col-12">
                                        <div id="delivery_map" style="width:100%; height:260px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,.08);"></div>
                                    </div>
                                <?php endif; ?>
                                <div class="col-md-4">
                                    <label class="form-label">Date de livraison</label>
                                    <input class="form-control" type="date" name="delivery_date" id="delivery_date" <?= $hasDeliveryDate ? '' : 'disabled' ?> />
                                    <?php if (!$hasDeliveryDate): ?><div class="small text-warning">Colonne delivery_date manquante (migration requise).</div><?php endif; ?>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Détails de livraison</label>
                                    <textarea class="form-control" name="delivery_details" id="delivery_details" rows="2" <?= $hasDeliveryDetails ? '' : 'disabled' ?>></textarea>
                                </div>
                                <?php if (!$hasDeliveryAddress || !$hasDeliveryLat || !$hasDeliveryLng || !$hasDeliveryDetails || !$hasDeliveryMode || !$hasDeliveryDate): ?>
                                    <div class="col-12 small text-info">Pour activer pleinement la livraison, exécutez les migrations: <a href="<?= BASE_URL ?>/migrations/alter_ventes_add_delivery.php" target="_blank">colonnes livraison</a> et <a href="<?= BASE_URL ?>/migrations/alter_ventes_add_delivery_date.php" target="_blank">date de livraison</a>.</div>
                                <?php endif; ?>
                            </div>
                            <hr />
                        </div>
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
                            <button type="button" class="btn btn-secondary" id="addItemBtn"><i class="fas fa-plus"></i> Ajouter un article</button>
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
    <script type="application/json" id="products-data">
        <?= json_encode($products, JSON_UNESCAPED_UNICODE) ?>
    </script>
    <script src="<?= ASSETS_URL ?>/js/sales.js"></script>
    <!-- Livraison: Leaflet + géocodage -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" crossorigin="anonymous" />
    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js" crossorigin="anonymous"></script>
    <script src="<?= ASSETS_URL ?>/js/sales_delivery.js"></script>
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
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? (BASE_URL . '/app/sales.php')) ?>" />
                    <div class="mb-3"><input class="form-control" type="file" name="file" required /></div>
                    <div id="att_list" class="small"></div>
                </div>
                <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Fermer</button><button class="btn btn-primary" type="submit">Envoyer</button></div>
            </form>
        </div>
    </div>
</div>
<script src="<?= ASSETS_URL ?>/js/attachments.js"></script>