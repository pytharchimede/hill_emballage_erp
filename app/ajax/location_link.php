<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauth']);
    exit;
}
// Autoriser livreur (deliveries_update) et admin (sales_update)
if (!(hasPermission('deliveries_update') || hasPermission('sales_update'))) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'bad_request']);
    exit;
}
// Optionnel: vérifier que la vente existe
try {
    $st = $db->prepare('SELECT id FROM ventes WHERE id=?');
    $st->execute([$id]);
    if (!$st->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'not_found']);
        exit;
    }
} catch (Exception $e) { /* ignore */
}
$sig = hash_hmac('sha256', (string)$id, LINK_SIGN_SECRET);
$url = SITE_URL . '/app/public/client_locate.php?vente=' . $id . '&sig=' . $sig;
echo json_encode(['ok' => true, 'url' => $url]);
