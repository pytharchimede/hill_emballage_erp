<?php
require_once __DIR__ . '/../app/includes/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$expectedToken = trim((string)getenv('HILL_PAIEMENTPRO_CALLBACK_TOKEN'));
$providedToken = trim((string)($_GET['token'] ?? ''));
if ($expectedToken === '' || $providedToken === '' || !hash_equals($expectedToken, $providedToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

function paymentCallbackPayload(): array
{
    $raw = file_get_contents('php://input');
    $json = json_decode($raw ?: '{}', true);
    if (is_array($json) && $json) return $json;
    if ($_POST) return $_POST;
    return $_GET;
}

try {
    global $db;
    $payload = paymentCallbackPayload();

    $merchantId = trim((string)($payload['merchantId'] ?? ''));
    $reference = trim((string)($payload['referenceNumber'] ?? ''));
    $responseCode = (string)($payload['responsecode'] ?? '');
    $amount = (int)round((float)($payload['amount'] ?? 0));
    $configuredMerchantId = trim((string)getenv('HILL_PAIEMENTPRO_MERCHANT_ID'));

    if ($configuredMerchantId === '' || $merchantId === '' || !hash_equals($configuredMerchantId, $merchantId) || $reference === '') {
        http_response_code(422);
        echo json_encode(['error' => 'invalid_notification']);
        exit;
    }

    $stmt = $db->prepare('SELECT * FROM storefront_orders WHERE payment_reference = ? LIMIT 1');
    $stmt->execute([$reference]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    $orderId = $order ? (int)$order['id'] : null;
    $eventStmt = $db->prepare('INSERT INTO storefront_payment_events (order_id, provider, reference_number, response_code, amount, payload_json) VALUES (?, ?, ?, ?, ?, ?)');
    $eventStmt->execute([
        $orderId,
        'paiement_pro',
        $reference,
        $responseCode,
        $amount,
        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'order_not_found']);
        exit;
    }

    if ($amount !== (int)$order['total_amount']) {
        http_response_code(409);
        echo json_encode(['error' => 'amount_mismatch']);
        exit;
    }

    if ($responseCode === '0') {
        $update = $db->prepare("UPDATE storefront_orders SET payment_status = 'paid', order_status = CASE WHEN order_status = 'pending' THEN 'confirmed' ELSE order_status END, paid_at = COALESCE(paid_at, NOW()) WHERE id = ?");
        $update->execute([(int)$order['id']]);
    } elseif ($responseCode === '-1') {
        $update = $db->prepare("UPDATE storefront_orders SET payment_status = CASE WHEN payment_status = 'paid' THEN payment_status ELSE 'failed' END WHERE id = ?");
        $update->execute([(int)$order['id']]);
    }

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
}
