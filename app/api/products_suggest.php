<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn() || !hasPermission('stock_read')) {
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
    $stmt = $db->prepare("SELECT id, nom, code_produit FROM produits WHERE is_active=1 AND (nom LIKE ? OR code_produit LIKE ?) ORDER BY nom LIMIT ?");
    $stmt->bindValue(1, $like, PDO::PARAM_STR);
    $stmt->bindValue(2, $like, PDO::PARAM_STR);
    $stmt->bindValue(3, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(array_map(function ($r) {
        return [
            'id' => (int)$r['id'],
            'nom' => $r['nom'],
            'code' => $r['code_produit']
        ];
    }, $rows));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
}
