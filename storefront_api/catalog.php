<?php
require_once __DIR__ . '/../app/includes/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');

function storefrontColumnExists(PDO $db, string $table, string $column): bool
{
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);
    return (bool)$stmt->fetchColumn();
}

try {
    global $db;

    $table = storefrontColumnExists($db, 'products', 'id')
        ? 'products'
        : (storefrontColumnExists($db, 'produits', 'id') ? 'produits' : null);

    if ($table === null) {
        http_response_code(503);
        echo json_encode(['error' => 'catalog_unavailable']);
        exit;
    }

    $limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));
    $offset = max(0, (int)($_GET['offset'] ?? 0));
    $search = trim((string)($_GET['q'] ?? ''));

    $hasActive = storefrontColumnExists($db, $table, 'is_active');
    $hasDescription = storefrontColumnExists($db, $table, 'description');
    $hasUnit = storefrontColumnExists($db, $table, 'unite');
    $hasImage = storefrontColumnExists($db, $table, 'image_url');
    $hasCategory = storefrontColumnExists($db, $table, 'categorie');

    $columns = ['id', 'nom', 'code_produit', 'prix_unitaire'];
    if ($hasDescription) $columns[] = 'description';
    if ($hasUnit) $columns[] = 'unite';
    if ($hasImage) $columns[] = 'image_url';
    if ($hasCategory) $columns[] = 'categorie';

    $where = $hasActive ? 'WHERE is_active = 1' : 'WHERE 1=1';
    $params = [];
    if ($search !== '') {
        $where .= ' AND (nom LIKE ? OR code_produit LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    $sql = 'SELECT ' . implode(', ', $columns) . " FROM {$table} {$where} ORDER BY nom ASC LIMIT ? OFFSET ?";
    $stmt = $db->prepare($sql);
    $position = 1;
    foreach ($params as $param) {
        $stmt->bindValue($position++, $param, PDO::PARAM_STR);
    }
    $stmt->bindValue($position++, $limit, PDO::PARAM_INT);
    $stmt->bindValue($position, $offset, PDO::PARAM_INT);
    $stmt->execute();

    $data = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $data[] = [
            'id' => (int)$row['id'],
            'name' => (string)$row['nom'],
            'code' => (string)($row['code_produit'] ?? ''),
            'description' => $hasDescription ? (string)($row['description'] ?? '') : '',
            'unit' => $hasUnit ? (string)($row['unite'] ?? 'pièce') : 'pièce',
            'price' => (int)round((float)($row['prix_unitaire'] ?? 0)),
            'image_url' => $hasImage ? (string)($row['image_url'] ?? '') : '',
            'category' => $hasCategory && !empty($row['categorie']) ? (string)$row['categorie'] : 'Catalogue',
        ];
    }

    echo json_encode(['data' => $data, 'count' => count($data)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
}
