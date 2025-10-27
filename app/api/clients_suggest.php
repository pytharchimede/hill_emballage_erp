<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn() || !hasPermission('clients_read')) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

$q = trim($_GET['q'] ?? '');
$limit = max(1, min(20, (int)($_GET['limit'] ?? 10)));

if ($q === '') {
    echo json_encode([]);
    exit;
}

try {
    global $db;
    $like = "%$q%";
    $stmt = $db->prepare("SELECT id, nom, prenoms, telephone, code_client FROM clients WHERE (nom LIKE ? OR prenoms LIKE ? OR telephone LIKE ? OR code_client LIKE ?) ORDER BY nom LIMIT ?");
    $stmt->bindValue(1, $like, PDO::PARAM_STR);
    $stmt->bindValue(2, $like, PDO::PARAM_STR);
    $stmt->bindValue(3, $like, PDO::PARAM_STR);
    $stmt->bindValue(4, $like, PDO::PARAM_STR);
    $stmt->bindValue(5, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(array_map(function ($r) {
        return [
            'id' => (int)$r['id'],
            'label' => trim(($r['code_client'] ? $r['code_client'] . ' - ' : '') . ($r['nom'] ?? '') . ' ' . ($r['prenoms'] ?? '')),
            'telephone' => $r['telephone'] ?? ''
        ];
    }, $rows));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
}
