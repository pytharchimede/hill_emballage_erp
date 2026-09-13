<?php

function storefrontBearerToken(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($header === '' && function_exists('getallheaders')) {
        $headers = getallheaders();
        $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }
    if (!preg_match('/^Bearer\s+(.+)$/i', trim($header), $matches)) return null;
    return trim($matches[1]);
}

function storefrontCurrentCustomer(PDO $db, bool $required = true): ?array
{
    $token = storefrontBearerToken();
    if (!$token) {
        if ($required) {
            http_response_code(401);
            echo json_encode(['error' => 'authentication_required']);
            exit;
        }
        return null;
    }

    $hash = hash('sha256', $token);
    $stmt = $db->prepare('SELECT c.id, c.first_name, c.last_name, c.email, c.phone, c.erp_client_id, s.id AS session_id FROM storefront_customer_sessions s JOIN storefront_customers c ON c.id = s.customer_id WHERE s.token_hash = ? AND s.expires_at > NOW() AND c.is_active = 1 LIMIT 1');
    $stmt->execute([$hash]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        if ($required) {
            http_response_code(401);
            echo json_encode(['error' => 'invalid_or_expired_session']);
            exit;
        }
        return null;
    }

    $db->prepare('UPDATE storefront_customer_sessions SET last_used_at = NOW() WHERE id = ?')->execute([(int)$customer['session_id']]);
    return $customer;
}

function storefrontIssueCustomerToken(PDO $db, int $customerId): string
{
    $plain = bin2hex(random_bytes(32));
    $hash = hash('sha256', $plain);
    $expiresAt = date('Y-m-d H:i:s', time() + (60 * 60 * 24 * 30));
    $stmt = $db->prepare('INSERT INTO storefront_customer_sessions (customer_id, token_hash, expires_at) VALUES (?, ?, ?)');
    $stmt->execute([$customerId, $hash, $expiresAt]);
    return $plain;
}
