<?php
require_once __DIR__ . '/../app/includes/config.php';
require_once __DIR__ . '/_auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

try {
    global $db;
    $customer = storefrontCurrentCustomer($db);
    $stmt = $db->prepare('SELECT order_number, total_amount, payment_method, payment_channel, payment_status, order_status, created_at FROM storefront_orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 100');
    $stmt->execute([(int)$customer['id']]);
    $orders = array_map(static function (array $row): array {
        return [
            'order_number' => $row['order_number'],
            'total' => (int)$row['total_amount'],
            'payment_method' => $row['payment_method'],
            'payment_channel' => $row['payment_channel'],
            'payment_status' => $row['payment_status'],
            'order_status' => $row['order_status'],
            'created_at' => $row['created_at'],
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    echo json_encode(['data' => $orders], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
}
