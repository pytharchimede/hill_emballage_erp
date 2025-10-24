<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}
if (!hasPermission('depots_read')) {
    setFlashMessage('warning', "Accès refusé à la carte des dépôts.");
    header('Location: dashboard.php');
    exit();
}

// Endpoint AJAX pour mise à jour géoloc (drag marker)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'update_geo') {
    header('Content-Type: application/json; charset=utf-8');
    if (!hasPermission('depots_update')) {
        echo json_encode(['success' => false, 'error' => 'Permission refusée']);
        exit();
    }
    try {
        $id = (int)($_POST['id'] ?? 0);
        $lat = isset($_POST['lat']) ? (float)$_POST['lat'] : null;
        $lon = isset($_POST['lon']) ? (float)$_POST['lon'] : null;
        if ($id <= 0 || $lat === null || $lon === null) {
            echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
            exit();
        }
        // Vérifier que les colonnes existent
        $chk = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='depots' AND COLUMN_NAME IN ('latitude','longitude')");
        $chk->execute();
        // Mettre à jour
        $stmt = $db->prepare("UPDATE depots SET latitude = ?, longitude = ? WHERE id = ?");
        $stmt->execute([$lat, $lon, $id]);
        log_action('UPDATE', 'depots', $id, ['latitude' => $lat, 'longitude' => $lon, 'source' => 'map_drag']);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// Vérifier colonnes latitude/longitude
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
$hasLat = colExistsDepots($db, 'latitude');
$hasLng = colExistsDepots($db, 'longitude');
$hasActive = colExistsDepots($db, 'is_active');
$hasEmail = colExistsDepots($db, 'email');
$hasHoraires = colExistsDepots($db, 'horaires');

$depots = [];
if ($hasLat && $hasLng) {
    $where = $hasActive ? 'WHERE is_active=1 AND latitude IS NOT NULL AND longitude IS NOT NULL' : 'WHERE latitude IS NOT NULL AND longitude IS NOT NULL';
    $sql = "SELECT id, nom, adresse, responsable, telephone" .
        ($hasEmail ? ", email" : "") . ($hasHoraires ? ", horaires" : "") . ", latitude, longitude FROM depots $where";
    $st = $db->prepare($sql);
    $st->execute();
    $depots = $st->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = 'Carte des dépôts';
include 'includes/header.php';
?>
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="fas fa-map-location-dot"></i> Carte des dépôts</h1>
        <div class="breadcrumb">Administration / Carte</div>
    </div>
    <div>
        <a class="btn" href="<?= BASE_URL ?>/web_admin/depots.php"><i class="fas fa-warehouse"></i> Gérer les dépôts</a>
    </div>
</div>

<div class="card">
    <div class="row g-3" style="padding: 1rem 1rem 0;">
        <div class="col-12 col-md-6 position-relative">
            <label class="form-label">Rechercher une adresse</label>
            <input id="map_search_input" class="form-control" placeholder="Ex: Plateau, Abidjan" autocomplete="off" />
            <div id="map_search_suggestions" class="list-group" style="position:absolute; z-index:1080; width:100%; max-height:240px; overflow:auto; display:none;"></div>
        </div>
        <div class="col-12 col-md-6 d-flex align-items-end justify-content-md-end">
            <div class="text-muted" style="font-size:.9rem;">
                <i class="fas fa-hand-pointer"></i> Astuce: zoomez puis faites glisser un marqueur pour corriger sa position (si autorisé)
            </div>
        </div>
    </div>
    <?php if (!$hasLat || !$hasLng): ?>
        <div class="alert alert-warning">Les colonnes latitude/longitude ne sont pas présentes dans la table <strong>depots</strong>.
            Veuillez les ajouter (DECIMAL) pour activer la carte. Vous pouvez aussi les ajouter via une migration dédiée.</div>
    <?php elseif (!$depots): ?>
        <div class="alert alert-info">Aucun dépôt géolocalisé pour le moment. Ajoutez des coordonnées depuis la page Dépôts.</div>
    <?php endif; ?>
    <div id="map" data-can-edit="<?= hasPermission('depots_update') ? '1' : '0' ?>" style="width:100%; height: 70vh; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,.1);"></div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" crossorigin="anonymous" />
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js" crossorigin="anonymous"></script>
<!-- MarkerCluster plugin via jsDelivr -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" crossorigin="anonymous" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" crossorigin="anonymous" />
<script src="https://cdn.jsdelivr.net/npm/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js" crossorigin="anonymous"></script>

<!-- Toast container for map updates -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1080">
    <div id="mapToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="mapToastBody">Position enregistrée</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
    <div id="mapToastErr" class="toast align-items-center text-bg-danger border-0 mt-2" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="mapToastErrBody">Erreur de mise à jour</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script src="<?= ASSETS_URL ?>/js/depots_map.js"></script>

<?php include 'includes/footer.php'; ?>