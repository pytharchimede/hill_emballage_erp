<?php
require_once __DIR__ . '/../app/includes/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

function paymentJsonBody(): array
{
    $data = json_decode(file_get_contents('php://input') ?: '{}', true);
    return is_array($data) ? $data : [];
}

try {
    global $db;
    $body = paymentJsonBody();
    $orderNumber = trim((string)($body['order_number'] ?? ''));
    if ($orderNumber === '') {
        http_response_code(422);
        echo json_encode(['error' => 'order_number_required']);
        exit;
    }

    $merchantId = trim((string)getenv('HILL_PAIEMENTPRO_MERCHANT_ID'));
    $publicBaseUrl = rtrim(trim((string)getenv('HILL_PUBLIC_BASE_URL')), '/');
    $callbackToken = trim((string)getenv('HILL_PAIEMENTPRO_CALLBACK_TOKEN'));
    $environment = strtolower(trim((string)(getenv('HILL_PAIEMENTPRO_ENV') ?: 'sandbox')));

    if ($merchantId === '' || $publicBaseUrl === '' || $callbackToken === '') {
        http_response_code(503);
        echo json_encode(['error' => 'payment_not_configured']);
        exit;
    }

    $stmt = $db->prepare('SELECT * FROM storefront_orders WHERE order_number = ? LIMIT 1');
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'order_not_found']);
        exit;
    }

    if ($order['payment_method'] !== 'paiement_pro') {
        http_response_code(409);
        echo json_encode(['error' => 'online_payment_not_required']);
        exit;
    }
    if ($order['payment_status'] === 'paid') {
        http_response_code(409);
        echo json_encode(['error' => 'already_paid']);
        exit;
    }

    $channel = trim((string)$order['payment_channel']);
    $allowedChannels = array_values(array_filter(array_map('trim', explode(',', (string)(getenv('HILL_PAIEMENTPRO_CHANNELS') ?: 'CARD,OMCIV2,MOMOCI,WAVECI')))));
    if ($channel === '' || !in_array($channel, $allowedChannels, true)) {
        http_response_code(422);
        echo json_encode(['error' => 'unsupported_payment_channel']);
        exit;
    }

    $endpoint = $environment === 'production'
        ? 'https://www.paiementpro.net/webservice/onlinepayment/init/curl-init.php'
        : 'https://sandbox.paiementpro.net/webservice/onlinepayment/init/curl-init.php';

    $notificationUrl = $publicBaseUrl . '/storefront_api/payment_callback.php?token=' . rawurlencode($callbackToken);
    $returnUrl = $publicBaseUrl . '/storefront_api/payment_return.php?order=' . rawurlencode($orderNumber);

    $payload = [
        'merchantId' => $merchantId,
        'amount' => (int)$order['total_amount'],
        'description' => 'Commande Hill Emballage ' . $orderNumber,
        'channel' => $channel,
        'countryCurrencyCode' => (string)$order['currency_code'],
        'referenceNumber' => (string)$order['payment_reference'],
        'customerEmail' => (string)$order['customer_email'],
        'customerFirstName' => (string)$order['customer_first_name'],
        'customerLastname' => (string)$order['customer_last_name'],
        'customerPhoneNumber' => (string)$order['customer_phone'],
        'notificationURL' => $notificationUrl,
        'returnURL' => $returnUrl,
        'returnContext' => json_encode(['order_number' => $orderNumber], JSON_UNESCAPED_SLASHES),
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $raw = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false || $curlError !== '') {
        http_response_code(502);
        echo json_encode(['error' => 'payment_gateway_unreachable']);
        exit;
    }

    $response = json_decode($raw, true);
    if (!is_array($response) || empty($response['success']) || empty($response['url'])) {
        http_response_code(502);
        echo json_encode([
            'error' => 'payment_initialization_failed',
            'message' => is_array($response) ? (string)($response['message'] ?? '') : '',
            'gateway_http_status' => $httpCode,
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'order_number' => $orderNumber,
        'payment_reference' => $order['payment_reference'],
        'payment_channel' => $channel,
        'payment_url' => (string)$response['url'],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
}
