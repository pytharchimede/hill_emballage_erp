<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

try {
    $assignment_id = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;
    if ($assignment_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'missing_parameters']);
        exit;
    }

    // Autorisation simple: admin/gerant tout; sinon, vérifier propriétaire
    $role = $_SESSION['user_role'] ?? '';
    $userId = (int)($_SESSION['user_id'] ?? 0);

    if (!in_array($role, ['admin', 'gerant'], true)) {
        $chk = $db->prepare("SELECT COUNT(*) FROM vendor_assignments WHERE id=? AND vendeur_id=?");
        $chk->execute([$assignment_id, $userId]);
        if ((int)$chk->fetchColumn() === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'forbidden']);
            exit;
        }
    }

    // Détection du schéma produits (produits/products) et colonnes (nom/name, code/code_produit)
    $colExists = function ($table, $col) use ($db) {
        $q = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
        $q->execute([$table, $col]);
        return ((int)$q->fetchColumn()) > 0;
    };
    $prodTable = 'produits';
    // bascule si table products existe
    $chk = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='products'");
    $chk->execute();
    if ((int)$chk->fetchColumn() > 0) {
        $prodTable = 'products';
    }

    $colName = $colExists($prodTable, 'nom') ? 'nom' : ($colExists($prodTable, 'name') ? 'name' : null);
    $colCode = $colExists($prodTable, 'code') ? 'code' : ($colExists($prodTable, 'code_produit') ? 'code_produit' : null);
    $nameExpr = $colName ? "p.$colName" : "''";
    $codeExpr = $colCode ? "p.$colCode" : "''";

    $sql = "SELECT d.product_id, $codeExpr AS code, $nameExpr AS nom, d.qty_assigned, d.qty_sold, d.qty_returned,
                   (d.qty_assigned - d.qty_sold - d.qty_returned) AS remaining,
                   d.unit_price
            FROM vendor_assignment_details d
            JOIN $prodTable p ON p.id = d.product_id
            WHERE d.assignment_id = ?
            ORDER BY $nameExpr";
    $st = $db->prepare($sql);
    $st->execute([$assignment_id]);
    $items = $st->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'items' => $items]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error', 'message' => $e->getMessage()]);
}
