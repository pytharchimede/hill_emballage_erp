<?php
require_once 'includes/config.php';

// Auth
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}
if (!hasPermission('depots_read')) {
    setFlashMessage('warning', "Accès refusé à Dépôts.");
    header('Location: dashboard.php');
    exit();
}

// Helpers
function colExistsDepots(PDO $db, $column)
{
    try {
        $s = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'depots' AND COLUMN_NAME = ?");
        $s->execute([$column]);
        return (bool)$s->fetchColumn();
    } catch (Exception $e) {
        return false;
    }
}

$hasEmail = colExistsDepots($db, 'email');
$hasHoraires = colExistsDepots($db, 'horaires');
$hasLat = colExistsDepots($db, 'latitude');
$hasLng = colExistsDepots($db, 'longitude');
$hasActive = colExistsDepots($db, 'is_active');

$message = '';
$messageType = '';

// Actions CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create' && hasPermission('depots_create')) {
            $nom = trim($_POST['nom'] ?? '');
            $adresse = trim($_POST['adresse'] ?? '');
            $responsable = trim($_POST['responsable'] ?? '');
            $telephone = trim($_POST['telephone'] ?? '');
            $email = $hasEmail ? trim($_POST['email'] ?? '') : null;
            $horaires = $hasHoraires ? trim($_POST['horaires'] ?? '') : null;
            $lat = $hasLat && $_POST['latitude'] !== '' ? floatval($_POST['latitude']) : null;
            $lng = $hasLng && $_POST['longitude'] !== '' ? floatval($_POST['longitude']) : null;

            $cols = ['nom', 'adresse', 'responsable', 'telephone'];
            $vals = [$nom, $adresse, $responsable, $telephone];
            if ($hasEmail) {
                $cols[] = 'email';
                $vals[] = $email;
            }
            if ($hasHoraires) {
                $cols[] = 'horaires';
                $vals[] = $horaires;
            }
            if ($hasLat) {
                $cols[] = 'latitude';
                $vals[] = $lat;
            }
            if ($hasLng) {
                $cols[] = 'longitude';
                $vals[] = $lng;
            }
            if ($hasActive) {
                $cols[] = 'is_active';
                $vals[] = 1;
            }

            $sql = "INSERT INTO depots (" . implode(',', $cols) . ") VALUES (" . rtrim(str_repeat('?,', count($cols)), ',') . ")";
            $st = $db->prepare($sql);
            $st->execute($vals);
            $newId = (int)$db->lastInsertId();
            log_action('CREATE', 'depots', $newId, ['nom' => $nom]);
            $message = 'Dépôt créé avec succès';
            $messageType = 'success';
        }
        if ($action === 'update' && hasPermission('depots_update')) {
            $id = (int)($_POST['id'] ?? 0);
            $nom = trim($_POST['nom'] ?? '');
            $adresse = trim($_POST['adresse'] ?? '');
            $responsable = trim($_POST['responsable'] ?? '');
            $telephone = trim($_POST['telephone'] ?? '');
            $email = $hasEmail ? trim($_POST['email'] ?? '') : null;
            $horaires = $hasHoraires ? trim($_POST['horaires'] ?? '') : null;
            $lat = $hasLat && $_POST['latitude'] !== '' ? floatval($_POST['latitude']) : null;
            $lng = $hasLng && $_POST['longitude'] !== '' ? floatval($_POST['longitude']) : null;

            $sets = ['nom=?', 'adresse=?', 'responsable=?', 'telephone=?'];
            $vals = [$nom, $adresse, $responsable, $telephone];
            if ($hasEmail) {
                $sets[] = 'email=?';
                $vals[] = $email;
            }
            if ($hasHoraires) {
                $sets[] = 'horaires=?';
                $vals[] = $horaires;
            }
            if ($hasLat) {
                $sets[] = 'latitude=?';
                $vals[] = $lat;
            }
            if ($hasLng) {
                $sets[] = 'longitude=?';
                $vals[] = $lng;
            }
            $vals[] = $id;

            $sql = "UPDATE depots SET " . implode(',', $sets) . " WHERE id=?";
            $st = $db->prepare($sql);
            $st->execute($vals);
            log_action('UPDATE', 'depots', $id, ['nom' => $nom]);
            $message = 'Dépôt mis à jour';
            $messageType = 'success';
        }
        if ($action === 'delete' && hasPermission('depots_delete')) {
            $id = (int)($_POST['id'] ?? 0);
            if ($hasActive) {
                $db->prepare("UPDATE depots SET is_active=0 WHERE id=?")->execute([$id]);
            } else {
                $db->prepare("DELETE FROM depots WHERE id=?")->execute([$id]);
            }
            log_action('DELETE', 'depots', $id);
            $message = 'Dépôt supprimé';
            $messageType = 'success';
        }
    } catch (Exception $e) {
        $message = 'Erreur: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Liste & filtres
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$where = $hasActive ? 'WHERE is_active=1' : 'WHERE 1=1';
$params = [];
if ($search !== '') {
    $where .= " AND (nom LIKE ? OR adresse LIKE ? OR responsable LIKE ? OR telephone LIKE ?" .
        ($hasEmail ? " OR email LIKE ?" : "") . ")";
    $q = "%$search%";
    $params = [$q, $q, $q, $q];
    if ($hasEmail) $params[] = $q;
}

$cnt = $db->prepare("SELECT COUNT(*) FROM depots $where");
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();
$pages = max(1, (int)ceil($total / $limit));

$sql = "SELECT * FROM depots $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$st = $db->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Gestion des Dépôts';
log_action('VIEW', 'depots');
include 'includes/header.php';
?>
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="fas fa-warehouse"></i> Dépôts</h1>
        <div class="breadcrumb">Administration / Dépôts</div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn" href="<?= BASE_URL ?>/web_admin/depots_map.php"><i class="fas fa-map-location-dot"></i> Voir la carte</a>
        <?php if (hasPermission('depots_create')): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepotModal"><i class="fas fa-plus"></i> Nouveau dépôt</button>
        <?php endif; ?>
    </div>
</div>
<?php if ($message): ?><div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if (!$hasLat || !$hasLng): ?>
    <div class="alert alert-info">Astuce: vous pouvez activer la géolocalisation des dépôts (carte, coordonnées) en ajoutant les colonnes latitude/longitude via la migration dédiée:
        <a href="<?= BASE_URL ?>/migrations/alter_depots_add_geo.php" target="_blank">ajouter les colonnes</a>.
    </div>
<?php endif; ?>

<div class="card">
    <form method="get" class="row g-2">
        <div class="col-md-8"><input class="form-control" type="text" name="search" placeholder="Rechercher (nom/adresse/responsable/téléphone)" value="<?= htmlspecialchars($search) ?>" /></div>
        <div class="col-md-2"><button class="btn w-100" type="submit"><i class="fas fa-search"></i> Rechercher</button></div>
        <div class="col-md-2"><a class="btn w-100" href="<?= BASE_URL ?>/web_admin/depots_map.php">Carte</a></div>
    </form>

    <table class="table" style="margin-top:1rem;">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Adresse</th>
                <th>Responsable</th>
                <th>Téléphone</th>
                <?php if ($hasEmail): ?><th>Email</th><?php endif; ?>
                <?php if ($hasLat && $hasLng): ?><th>Coordonnées</th><?php endif; ?>
                <?php if ($hasActive): ?><th>Statut</th><?php endif; ?>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['nom']) ?></td>
                    <td><?= htmlspecialchars($d['adresse']) ?></td>
                    <td><?= htmlspecialchars($d['responsable']) ?></td>
                    <td><?= htmlspecialchars($d['telephone']) ?></td>
                    <?php if ($hasEmail): ?><td><?= htmlspecialchars($d['email'] ?? '') ?></td><?php endif; ?>
                    <?php if ($hasLat && $hasLng): ?>
                        <td>
                            <?php if (!empty($d['latitude']) && !empty($d['longitude'])): ?>
                                <?= htmlspecialchars($d['latitude']) ?>, <?= htmlspecialchars($d['longitude']) ?>
                            <?php else: ?>
                                <span class="text-warning">Non défini</span>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                    <?php if ($hasActive): ?>
                        <td>
                            <?php if ((int)($d['is_active'] ?? 1) === 1): ?>
                                <span class="badge badge-success">Actif</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactif</span>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                    <td>
                        <?php if (hasPermission('depots_update')): ?>
                            <button class="btn btn-sm btn-edit-depot" type="button"
                                data-id="<?= (int)$d['id'] ?>"
                                data-nom="<?= htmlspecialchars($d['nom']) ?>"
                                data-adresse="<?= htmlspecialchars($d['adresse']) ?>"
                                data-responsable="<?= htmlspecialchars($d['responsable']) ?>"
                                data-telephone="<?= htmlspecialchars($d['telephone']) ?>"
                                <?php if ($hasEmail): ?>data-email="<?= htmlspecialchars($d['email'] ?? '') ?>" <?php endif; ?>
                                <?php if ($hasLat && $hasLng): ?>data-latitude="<?= htmlspecialchars($d['latitude'] ?? '') ?>" data-longitude="<?= htmlspecialchars($d['longitude'] ?? '') ?>" <?php endif; ?>
                                data-bs-toggle="modal" data-bs-target="#editDepotModal">
                                <i class="fas fa-edit"></i>
                            </button>
                        <?php endif; ?>
                        <?php if (hasPermission('depots_delete')): ?>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="action" value="delete" />
                                <input type="hidden" name="id" value="<?= (int)$d['id'] ?>" />
                                <button class="btn btn-sm btn-danger" type="submit"><i class="fas fa-trash"></i></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
                <tr>
                    <td colspan="8">Aucun dépôt</td>
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

<?php if (hasPermission('depots_create')): ?>
    <div class="modal fade" id="addDepotModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Ajouter un dépôt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <input type="hidden" name="action" value="create" />
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6 form-group"><label>Nom</label><input class="form-control" name="nom" required /></div>
                            <div class="col-md-6 form-group"><label>Responsable</label><input class="form-control" name="responsable" /></div>
                            <div class="col-md-6 form-group"><label>Téléphone</label><input class="form-control" name="telephone" /></div>
                            <?php if ($hasEmail): ?><div class="col-md-6 form-group"><label>Email</label><input class="form-control" type="email" name="email" /></div><?php endif; ?>
                            <?php if ($hasHoraires): ?><div class="col-md-12 form-group"><label>Horaires</label><input class="form-control" name="horaires" placeholder="Ex: Lun-Sam 08:00-18:00" /></div><?php endif; ?>
                            <div class="col-md-12 form-group position-relative">
                                <label>Localisation (adresse)</label>
                                <div class="input-group">
                                    <input class="form-control" name="adresse" id="create_adresse" autocomplete="off" placeholder="Tapez un lieu, une adresse, un quartier..." />
                                    <button class="btn btn-outline-secondary" type="button" id="create_locate_btn" title="Ma position"><i class="fas fa-location-crosshairs"></i></button>
                                </div>
                                <div id="create_address_suggestions" class="list-group" style="position:absolute; z-index:1080; width:100%; max-height:220px; overflow:auto; display:none;"></div>
                            </div>
                            <?php if ($hasLat && $hasLng): ?>
                                <input type="hidden" name="latitude" id="create_latitude" />
                                <input type="hidden" name="longitude" id="create_longitude" />
                                <div class="col-12">
                                    <div id="create_map" style="width:100%; height:300px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.08);"></div>
                                </div>
                            <?php endif; ?>
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

<?php if (hasPermission('depots_update')): ?>
    <div class="modal fade" id="editDepotModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier un dépôt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>div>
                <form method="post">
                    <input type="hidden" name="action" value="update" />
                    <input type="hidden" name="id" id="edit_id" />
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6 form-group"><label>Nom</label><input class="form-control" name="nom" id="edit_nom" required /></div>
                            <div class="col-md-6 form-group"><label>Responsable</label><input class="form-control" name="responsable" id="edit_responsable" /></div>
                            <div class="col-md-6 form-group"><label>Téléphone</label><input class="form-control" name="telephone" id="edit_telephone" /></div>
                            <?php if ($hasEmail): ?><div class="col-md-6 form-group"><label>Email</label><input class="form-control" type="email" name="email" id="edit_email" /></div><?php endif; ?>
                            <?php if ($hasHoraires): ?><div class="col-md-12 form-group"><label>Horaires</label><input class="form-control" name="horaires" id="edit_horaires" /></div><?php endif; ?>
                            <div class="col-md-12 form-group position-relative">
                                <label>Localisation (adresse)</label>
                                <div class="input-group">
                                    <input class="form-control" name="adresse" id="edit_adresse" autocomplete="off" placeholder="Tapez un lieu, une adresse, un quartier..." />
                                    <button class="btn btn-outline-secondary" type="button" id="edit_locate_btn" title="Ma position"><i class="fas fa-location-crosshairs"></i></button>
                                </div>
                                <div id="edit_address_suggestions" class="list-group" style="position:absolute; z-index:1080; width:100%; max-height:220px; overflow:auto; display:none;"></div>
                            </div>
                            <?php if ($hasLat && $hasLng): ?>
                                <input type="hidden" name="latitude" id="edit_latitude" />
                                <input type="hidden" name="longitude" id="edit_longitude" />
                                <div class="col-12">
                                    <div id="edit_map" style="width:100%; height:300px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.08);"></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button class="btn btn-primary" type="submit">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Carte & Autocomplete: assets globaux pour création/édition (CSP-friendly) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" crossorigin="anonymous" />
    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js" crossorigin="anonymous"></script>
    <script src="<?= ASSETS_URL ?>/js/depots_geo.js"></script>

    <?php include 'includes/footer.php'; ?>