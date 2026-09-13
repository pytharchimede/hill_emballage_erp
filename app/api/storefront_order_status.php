<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'authentication_required']);
    exit;
}

$role = $_SESSION['user_role'] ?? '';
if (!in_array($role, ['admin', 'livreur', 'vendeur'], true)) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

$payload = json_decode(file_get_contents('php://input') ?: '{}', true);
$orderNumber = trim((string)($payload['order_number'] ?? ''));
$status = trim((string)($payload['status'] ?? ''));
$allowed = ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered', 'cancelled'];

if ($orderNumber === '' || !in_array($status, $allowed, true)) {
    http_response_code(422);
    echo json_encode(['error' => 'invalid_status_update']);
    exit;
}

try {
    global $db;
    $stmt = $db->prepare('UPDATE storefront_orders SET order_status = ?, updated_at = NOW() WHERE order_number = ?');
    $stmt->execute([$status, $orderNumber]);
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'order_not_found']);
        exit;
    }
    echo json_encode(['ok' => true, 'order_number' => $orderNumber, 'order_status' => $status]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
}
