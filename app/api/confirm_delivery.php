<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'unauth']);
    exit;
}
// Autoriser livreur (deliveries_update) ou admin (sales_update)
$allowed = hasPermission('deliveries_update') || hasPermission('sales_update');
if (!$allowed) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'bad_request']);
    exit;
}

try {
    // Restreindre si livreur: seulement ses ventes
    $restrict = '';
    $params = ['livree', $id];
    if (!hasPermission('sales_update') && hasPermission('deliveries_update')) {
        // livreur uniquement
        // si la colonne livreur_id existe, limiter
        $q = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ventes' AND COLUMN_NAME = 'livreur_id'");
        $q->execute();
        if ($q->fetchColumn()) {
            $restrict = ' AND livreur_id = ?';
            $params[] = (int)$_SESSION['user_id'];
        }
    }
    $sql = "UPDATE ventes SET statut=?, updated_at=NOW() WHERE id=?" . $restrict;
    $st = $db->prepare($sql);
    $st->execute($params);
    if ($st->rowCount() === 0) {
        throw new Exception('Aucune ligne mise à jour');
    }
    log_action('DELIVERY_CONFIRM', 'ventes', $id);
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
