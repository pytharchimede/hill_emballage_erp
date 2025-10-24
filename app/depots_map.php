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
    <?php if (!$hasLat || !$hasLng): ?>
        <div class="alert alert-warning">Les colonnes latitude/longitude ne sont pas présentes dans la table <strong>depots</strong>.
            Veuillez les ajouter (DECIMAL) pour activer la carte. Vous pouvez aussi les ajouter via une migration dédiée.</div>
    <?php elseif (!$depots): ?>
        <div class="alert alert-info">Aucun dépôt géolocalisé pour le moment. Ajoutez des coordonnées depuis la page Dépôts.</div>
    <?php endif; ?>
    <div id="map" style="width:100%; height: 70vh; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,.1);"></div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    (function() {
        const hasGeo = <?= json_encode($hasLat && $hasLng) ?>;
        if (!hasGeo) return;
        const depots = <?= json_encode($depots, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        // Centre par défaut: Abidjan
        let center = [5.345317, -4.024429];
        if (depots.length) {
            // Moyenne simple
            let lat = 0,
                lng = 0,
                n = 0;
            for (const d of depots) {
                const la = parseFloat(d.latitude);
                const lo = parseFloat(d.longitude);
                if (!isNaN(la) && !isNaN(lo)) {
                    lat += la;
                    lng += lo;
                    n++;
                }
            }
            if (n > 0) center = [lat / n, lng / n];
        }
        const map = L.map('map').setView(center, depots.length ? 12 : 11);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        const icon = L.icon({
            iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
            iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        for (const d of depots) {
            if (d.latitude == null || d.longitude == null) continue;
            const la = parseFloat(d.latitude);
            const lo = parseFloat(d.longitude);
            if (isNaN(la) || isNaN(lo)) continue;
            const popup = `
      <div style="min-width:220px">
        <div style="font-weight:600;margin-bottom:4px;">${d.nom || ''}</div>
        <div style="color:#555">${d.adresse || ''}</div>
        <div style="margin-top:6px;">
          <div><i class="fas fa-user"></i> ${d.responsable || ''}</div>
          <div><i class="fas fa-phone"></i> ${d.telephone || ''}</div>
          ${d.email ? `<div><i class=\"fas fa-envelope\"></i> ${d.email}</div>` : ''}
          ${d.horaires ? `<div><i class=\"fas fa-clock\"></i> ${d.horaires}</div>` : ''}
        </div>
      </div>`;
            L.marker([la, lo], {
                icon
            }).addTo(map).bindPopup(popup);
        }
    })();
</script>

<?php include 'includes/footer.php'; ?>