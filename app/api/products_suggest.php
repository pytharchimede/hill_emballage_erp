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
$assignmentId = isset($_GET['assignment_id']) && $_GET['assignment_id'] !== '' ? (int)$_GET['assignment_id'] : 0;
$explicitDepotId = isset($_GET['depot_id']) && $_GET['depot_id'] !== '' ? (int)$_GET['depot_id'] : 0;

if ($q === '') {
    echo json_encode([]);
    exit;
}

try {
    global $db;
    $like = "%$q%";

    // Détection table produits: préférer 'products', fallback 'produits'
    $table = 'products';
    $check = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products'");
    $check->execute();
    if ((int)$check->fetchColumn() === 0) {
        $table = 'produits';
    }

    // Détection nom de colonne code (code_produit vs code)
    $codeCol = 'code_produit';
    $checkCol = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'code_produit'");
    $checkCol->execute([$table]);
    if ((int)$checkCol->fetchColumn() === 0) {
        // tenter 'code'
        $codeCol = 'code';
    }

    // Si une assignation est fournie, proposer uniquement les produits restants de l'assignation
    if ($assignmentId > 0) {
        // Vérifier l'existence des tables d'assignation
        $hasAssign = (function () use ($db) {
            $q = $db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('vendor_assignments','vendor_assignment_details')");
            return ((int)$q->fetchColumn()) >= 1; // au moins une table
        })();
        if ($hasAssign) {
            // Construire le filtre assignment + remaining > 0
            // On tente de vérifier vendeur_id si la colonne existe
            $hasVendeur = false;
            try {
                $chk = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='vendor_assignments' AND COLUMN_NAME='vendeur_id'");
                $chk->execute();
                $hasVendeur = ((int)$chk->fetchColumn()) > 0;
            } catch (Throwable $e) {
            }

            $userId = (int)($_SESSION['user_id'] ?? 0);
            $whereVend = $hasVendeur && $userId > 0 ? ' AND va.vendeur_id = ' . $userId . ' ' : '';

            $sql = "SELECT p.id, p.nom, p.$codeCol AS code,
                                                     (vad.qty_assigned - COALESCE(vad.qty_sold,0) - COALESCE(vad.qty_returned,0)) AS remaining,
                                                     COALESCE(vad.unit_price, p.prix_unitaire) AS pu
                    FROM vendor_assignment_details vad
                    JOIN vendor_assignments va ON va.id = vad.assignment_id
                    JOIN $table p ON p.id = vad.product_id
                    WHERE vad.assignment_id = ? $whereVend
                      AND (vad.qty_assigned - COALESCE(vad.qty_sold,0) - COALESCE(vad.qty_returned,0)) > 0
                      AND (p.nom LIKE ? OR p.$codeCol LIKE ?)
                    ORDER BY p.nom
                    LIMIT ?";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(1, $assignmentId, PDO::PARAM_INT);
            $stmt->bindValue(2, $like, PDO::PARAM_STR);
            $stmt->bindValue(3, $like, PDO::PARAM_STR);
            $stmt->bindValue(4, $limit, PDO::PARAM_INT);
        }
    }

    if (!isset($stmt)) {
        // Pas d'assignation ou tables absentes -> fallback: filtrer par dépôt s'il est connu
        $depotId = $explicitDepotId > 0 ? $explicitDepotId : (int)($_SESSION['depot_id'] ?? 0);
        if ($depotId > 0) {
            $sql = "SELECT p.id, p.nom, p.$codeCol AS code,
                           s.quantite AS remaining,
                           p.prix_unitaire AS pu
                    FROM $table p
                    JOIN stock s ON s.produit_id = p.id AND s.depot_id = ? AND s.quantite > 0
                    WHERE p.is_active = 1 AND (p.nom LIKE ? OR p.$codeCol LIKE ?)
                    ORDER BY p.nom
                    LIMIT ?";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(1, $depotId, PDO::PARAM_INT);
            $stmt->bindValue(2, $like, PDO::PARAM_STR);
            $stmt->bindValue(3, $like, PDO::PARAM_STR);
            $stmt->bindValue(4, $limit, PDO::PARAM_INT);
        } else {
            $sql = "SELECT p.id, p.nom, p.$codeCol AS code,
                           NULL AS remaining,
                           p.prix_unitaire AS pu
                    FROM $table p
                    WHERE p.is_active = 1 AND (p.nom LIKE ? OR p.$codeCol LIKE ?)
                    ORDER BY p.nom
                    LIMIT ?";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(1, $like, PDO::PARAM_STR);
            $stmt->bindValue(2, $like, PDO::PARAM_STR);
            $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        }
    }

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(array_map(function ($r) {
        return [
            'id' => (int)$r['id'],
            'nom' => $r['nom'],
            'code' => $r['code'] ?? '',
            'remaining' => isset($r['remaining']) ? (float)$r['remaining'] : null,
            'pu' => isset($r['pu']) ? (float)$r['pu'] : null
        ];
    }, $rows));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
}
