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
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        // Nominatim recommande d'envoyer un User-Agent permettant le contact
        'User-Agent: Hill-Emballage-ERP/1.0 (+contact@hill-emballage.local)'
    ],
]);
$resp = curl_exec($ch);
$err  = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($resp === false || $code >= 400) {
    echo json_encode([]);
    exit;
}

echo $resp;
