<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'unauth']);
    exit;
}
// Permissions: livreur ou admin
$role = $_SESSION['user_role'] ?? '';
if (!in_array($role, ['livreur', 'admin']) && !hasPermission('deliveries_read')) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

function colExistsAjax(PDO $db, string $table, string $col): bool
{
    try {
        $q = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $q->execute([$table, $col]);
        return (bool)$q->fetchColumn();
    } catch (Exception $e) {
        return false;
    }
}

$scope = $_GET['scope'] ?? 'pending'; // pending|today|all
$userId = (int)($_SESSION['user_id'] ?? 0);
$depotId = (int)($_SESSION['depot_id'] ?? 0);
$hasLivreurId = colExistsAjax($db, 'ventes', 'livreur_id');
$hasDepotId = colExistsAjax($db, 'ventes', 'depot_id');
$hasLat = colExistsAjax($db, 'ventes', 'delivery_latitude');
$hasLng = colExistsAjax($db, 'ventes', 'delivery_longitude');
$hasAddr = colExistsAjax($db, 'ventes', 'delivery_address');
$hasDelivDate = colExistsAjax($db, 'ventes', 'delivery_date');
$hasMode = colExistsAjax($db, 'ventes', 'delivery_mode');

$where = [];
$params = [];
// Dépôt principal: pas de restriction globale
if (!($depotId && isMainDepot($depotId))) {
    if ($hasLivreurId) {
        $where[] = 'v.livreur_id = ?';
        $params[] = $userId;
    } elseif ($hasDepotId) {
        $where[] = 'v.depot_id = ?';
        $params[] = $depotId;
    }
}

// Restreindre aux ventes en mode livraison si la colonne existe
if ($hasMode) {
    $where[] = "v.delivery_mode = 'livraison'";
}

if ($scope === 'pending') {
    $where[] = "v.statut IN ('en_attente','validee')";
} elseif ($scope === 'today') {
    if ($hasDelivDate) {
        $where[] = 'v.delivery_date = CURDATE()';
    } else {
        $where[] = 'DATE(v.created_at) = CURDATE()';
    }
    // Inclure les livraisons planifiées/en cours/terminées du jour
    $where[] = "v.statut IN ('en_attente','validee','livree')";
}
// 'all' no extra filter

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$select = "SELECT v.id, v.numero_vente, v.statut, v.created_at, v.updated_at, v.montant_total,
                  " . ($hasDelivDate ? 'v.delivery_date' : 'NULL') . " as delivery_date,
                  " . ($hasAddr ? 'v.delivery_address' : 'NULL') . " as delivery_address,
                  " . ($hasLat ? 'v.delivery_latitude' : 'NULL') . " as lat,
                  " . ($hasLng ? 'v.delivery_longitude' : 'NULL') . " as lon,
                  c.nom as client_nom, c.prenom as client_prenom, c.entreprise
           FROM ventes v LEFT JOIN clients c ON v.client_id=c.id $whereSql
           ORDER BY (v.statut='validee') DESC, (v.statut='en_attente') DESC, " . ($hasDelivDate ? 'v.delivery_date' : 'v.created_at') . " ASC LIMIT 500";

try {
    $st = $db->prepare($select);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r['client_label'] = trim(($r['client_nom'] ?? '') . ' ' . ($r['client_prenom'] ?? '')) ?: ($r['entreprise'] ?? 'Client');
        $r['is_geo'] = ($r['lat'] !== null && $r['lon'] !== null);
    }
    echo json_encode(['ok' => true, 'items' => $rows]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
