<?php
require_once __DIR__ . '/../app/includes/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function storefrontJsonBody(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    return is_array($data) ? $data : [];
}

function storefrontProductTable(PDO $db): ?string
{
    foreach (['products', 'produits'] as $table) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
        $stmt->execute([$table]);
        if ((int)$stmt->fetchColumn() > 0) return $table;
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

try {
    global $db;
    $body = storefrontJsonBody();

    $firstName = trim((string)($body['first_name'] ?? ''));
    $lastName = trim((string)($body['last_name'] ?? ''));
    $email = trim((string)($body['email'] ?? ''));
    $phone = trim((string)($body['phone'] ?? ''));
    $address = trim((string)($body['address'] ?? ''));
    $city = trim((string)($body['city'] ?? ''));
    $note = trim((string)($body['delivery_note'] ?? ''));
    $paymentMethod = trim((string)($body['payment_method'] ?? ''));
    $paymentChannel = trim((string)($body['payment_channel'] ?? ''));
    $items = $body['items'] ?? [];

    if ($firstName === '' || $lastName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || $address === '' || $city === '') {
        http_response_code(422);
        echo json_encode(['error' => 'invalid_customer']);
        exit;
    }

    if (!in_array($paymentMethod, ['paiement_pro', 'cash_on_delivery'], true)) {
        http_response_code(422);
        echo json_encode(['error' => 'invalid_payment_method']);
        exit;
    }

    if ($paymentMethod === 'paiement_pro' && $paymentChannel === '') {
        http_response_code(422);
        echo json_encode(['error' => 'payment_channel_required']);
        exit;
    }

    if (!is_array($items) || count($items) === 0 || count($items) > 100) {
        http_response_code(422);
        echo json_encode(['error' => 'invalid_items']);
        exit;
    }

    $table = storefrontProductTable($db);
    if ($table === null) throw new RuntimeException('product_table_missing');

    $normalized = [];
    $subtotal = 0;
    $productStmt = $db->prepare("SELECT id, nom, prix_unitaire FROM {$table} WHERE id = ? LIMIT 1");

    foreach ($items as $item) {
        $productId = (int)($item['product_id'] ?? 0);
        $quantity = max(0, (int)($item['quantity'] ?? 0));
        if ($productId <= 0 || $quantity <= 0 || $quantity > 1000) {
            http_response_code(422);
            echo json_encode(['error' => 'invalid_item']);
            exit;
        }

        $productStmt->execute([$productId]);
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            http_response_code(422);
            echo json_encode(['error' => 'product_not_found', 'product_id' => $productId]);
            exit;
        }

        $unitPrice = (int)round((float)$product['prix_unitaire']);
        $lineTotal = $unitPrice * $quantity;
        $subtotal += $lineTotal;
        $normalized[] = [
            'product_id' => (int)$product['id'],
            'product_name' => (string)$product['nom'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
        ];
    }

    $deliveryFee = 0;
    $total = $subtotal + $deliveryFee;
    $orderNumber = 'HE-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $paymentReference = $paymentMethod === 'paiement_pro'
        ? 'PP-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(4)))
        : null;

    $db->beginTransaction();
    $stmt = $db->prepare(
        'INSERT INTO storefront_orders (order_number, customer_first_name, customer_last_name, customer_email, customer_phone, delivery_address, delivery_city, delivery_note, subtotal, delivery_fee, total_amount, currency_code, payment_method, payment_channel, payment_status, order_status, payment_reference, payment_provider) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $orderNumber, $firstName, $lastName, $email, $phone, $address, $city, $note,
        $subtotal, $deliveryFee, $total, '952', $paymentMethod,
        $paymentMethod === 'paiement_pro' ? $paymentChannel : null,
        'unpaid', 'pending', $paymentReference,
        $paymentMethod === 'paiement_pro' ? 'paiement_pro' : null,
    ]);
    $orderId = (int)$db->lastInsertId();

    $itemStmt = $db->prepare('INSERT INTO storefront_order_items (order_id, product_id, product_name, quantity, unit_price, line_total) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($normalized as $item) {
        $itemStmt->execute([$orderId, $item['product_id'], $item['product_name'], $item['quantity'], $item['unit_price'], $item['line_total']]);
    }
    $db->commit();

    http_response_code(201);
    echo json_encode([
        'order_number' => $orderNumber,
        'payment_method' => $paymentMethod,
        'payment_channel' => $paymentMethod === 'paiement_pro' ? $paymentChannel : null,
        'payment_reference' => $paymentReference,
        'payment_status' => 'unpaid',
        'order_status' => 'pending',
        'subtotal' => $subtotal,
        'delivery_fee' => $deliveryFee,
        'total' => $total,
        'currency_code' => '952',
        'requires_online_payment' => $paymentMethod === 'paiement_pro',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
}
