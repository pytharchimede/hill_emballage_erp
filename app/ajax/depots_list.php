<?php
require_once dirname(__DIR__) . '/includes/config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn() || !hasPermission('depots_read')) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

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

if (!$hasLat || !$hasLng) {
    echo json_encode([]);
    exit;
}

$where = $hasActive ? 'WHERE is_active=1 AND latitude IS NOT NULL AND longitude IS NOT NULL' : 'WHERE latitude IS NOT NULL AND longitude IS NOT NULL';
$sql = "SELECT id, nom, adresse, responsable, telephone"
    . ($hasEmail ? ", email" : "")
    . ($hasHoraires ? ", horaires" : "")
    . ", latitude, longitude FROM depots $where";
$st = $db->prepare($sql);
$st->execute();
$depots = $st->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($depots, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
