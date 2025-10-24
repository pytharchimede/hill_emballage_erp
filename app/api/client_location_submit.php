<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');
// Pas de session requise: validation via signature
$id = (int)($_POST['id'] ?? 0);
$sig = $_POST['sig'] ?? '';
$lat = isset($_POST['lat']) ? (float)$_POST['lat'] : null;
$lon = isset($_POST['lon']) ? (float)$_POST['lon'] : null;
if ($id <= 0 || !$sig || $lat === null || $lon === null) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'bad_request']);
    exit;
}
if (!hash_equals(hash_hmac('sha256', (string)$id, LINK_SIGN_SECRET), $sig)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'bad_sig']);
    exit;
}
try {
    // Mettre à jour les coordonnées de livraison
    $hasLat = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ventes' AND COLUMN_NAME = 'delivery_latitude'");
    $hasLat->execute();
    $okLat = (bool)$hasLat->fetchColumn();
    $hasLon = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ventes' AND COLUMN_NAME = 'delivery_longitude'");
    $hasLon->execute();
    $okLon = (bool)$hasLon->fetchColumn();
    $hasMode = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ventes' AND COLUMN_NAME = 'delivery_mode'");
    $hasMode->execute();
    $okMode = (bool)$hasMode->fetchColumn();
    if (!$okLat || !$okLon) {
        throw new Exception('delivery_coords_missing');
    }
    $sql = "UPDATE ventes SET delivery_latitude=?, delivery_longitude=?" . ($okMode ? ", delivery_mode='livraison'" : "") . ", updated_at=NOW() WHERE id=?";
    $st = $db->prepare($sql);
    $st->execute([$lat, $lon, $id]);
    if ($st->rowCount() === 0) throw new Exception('not_updated');
    log_action('CLIENT_LOCATE', 'ventes', $id, ['lat' => $lat, 'lon' => $lon]);
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
