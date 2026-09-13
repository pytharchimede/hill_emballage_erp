<?php
require_once __DIR__ . '/../app/includes/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    global $db;
    $orderNumber = trim((string)($_GET['order'] ?? ''));
    if ($orderNumber === '') {
        http_response_code(422);
        echo json_encode(['error' => 'order_required']);
        exit;
    }

    $stmt = $db->prepare('SELECT order_number, payment_method, payment_channel, payment_status, order_status, total_amount, currency_code, paid_at, created_at, updated_at FROM storefront_orders WHERE order_number = ? LIMIT 1');
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'order_not_found']);
        exit;
    }

    echo json_encode([
        'order_number' => $order['order_number'],
        'payment_method' => $order['payment_method'],
        'payment_channel' => $order['payment_channel'],
        'payment_status' => $order['payment_status'],
        'order_status' => $order['order_status'],
        'total' => (int)$order['total_amount'],
        'currency_code' => $order['currency_code'],
        'paid_at' => $order['paid_at'],
        'created_at' => $order['created_at'],
        'updated_at' => $order['updated_at'],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
}
