<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');
// Pas de session requise: validation via signature
$id = (int)($_POST['id'] ?? 0);
$sig = $_POST['sig'] ?? '';
$lat = isset($_POST['lat']) ? (float)$_POST['lat'] : null;
$lon = isset($_POST['lon']) ? (float)$_POST['lon'] : null;
$addrIn = isset($_POST['addr']) ? trim((string)$_POST['addr']) : '';
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
    $hasAddr = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ventes' AND COLUMN_NAME = 'delivery_address'");
    $hasAddr->execute();
    $okAddr = (bool)$hasAddr->fetchColumn();
    if (!$okLat || !$okLon) {
        throw new Exception('delivery_coords_missing');
    }
    // Déterminer l'adresse si possible
    $addr = '';
    if ($okAddr) {
        if ($addrIn !== '') {
            $addr = mb_substr($addrIn, 0, 255);
        } else {
            // Essayer un reverse geocode léger (sans dépendre d'une session)
            $revUrl = 'https://nominatim.openstreetmap.org/reverse?format=json&accept-language=fr&zoom=18&lat=' . rawurlencode((string)$lat) . '&lon=' . rawurlencode((string)$lon);
            $resp = false;
            $code = 0;
            $display = '';
            if (function_exists('curl_init')) {
                $ch = curl_init($revUrl);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT => 6,
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json',
                        'User-Agent: Hill-Emballage-ERP/1.0 (+contact@hill-emballage.local)'
                    ],
                ]);
                $resp = curl_exec($ch);
                $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
            } else {
                $ctx = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'timeout' => 6,
                        'header' => [
                            'Accept: application/json',
                            'User-Agent: Hill-Emballage-ERP/1.0 (+contact@hill-emballage.local)'
                        ]
                    ]
                ]);
                $resp = @file_get_contents($revUrl, false, $ctx);
            }
            if ($resp !== false && $code < 400) {
                $j = json_decode($resp, true);
                if (is_array($j) && !empty($j['display_name'])) {
                    $display = (string)$j['display_name'];
                }
            }
            if ($display !== '') $addr = mb_substr($display, 0, 255);
        }
    }

    // Construire la requête de mise à jour
    $fields = "delivery_latitude=?, delivery_longitude=?";
    $params = [$lat, $lon];
    if ($okAddr && $addr !== '') {
        $fields .= ", delivery_address=?";
        $params[] = $addr;
    }
    if ($okMode) {
        $fields .= ", delivery_mode='livraison'";
    }
    $sql = "UPDATE ventes SET $fields, updated_at=NOW() WHERE id=?";
    $params[] = $id;
    $st = $db->prepare($sql);
    $st->execute($params);
    // Même si rowCount==0 (valeurs identiques), considérer comme succès
    log_action('CLIENT_LOCATE', 'ventes', $id, ['lat' => $lat, 'lon' => $lon, 'addr' => $addr]);
    echo json_encode(['ok' => true, 'address' => $addr]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
