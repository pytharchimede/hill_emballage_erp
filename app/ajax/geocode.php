<?php
require_once dirname(__DIR__) . '/includes/config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$q = trim($_GET['q'] ?? '');
if ($q === '' || mb_strlen($q) < 3) {
    echo json_encode([]);
    exit;
}

// Proxy Nominatim pour respecter CSP (connect-src 'self')
$url = 'https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&accept-language=fr&limit=8&q=' . urlencode($q);

// Utiliser cURL si disponible, sinon fallback file_get_contents
$resp = false;
$code = 0;
if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'User-Agent: Hill-Emballage-ERP/1.0 (+contact@hill-emballage.local)'
        ],
    ]);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
} else {
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'header' => [
                'Accept: application/json',
                'User-Agent: Hill-Emballage-ERP/1.0 (+contact@hill-emballage.local)'
            ]
        ]
    ]);
    $resp = @file_get_contents($url, false, $ctx);
    // Si headers dispo, essayer de détecter le code
    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $h) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) {
                $code = (int)$m[1];
                break;
            }
        }
    }
}

if ($resp === false || $code >= 400) {
    http_response_code(502);
    echo json_encode([]);
    exit;
}

echo $resp;
