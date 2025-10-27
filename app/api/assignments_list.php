<?php
require_once __DIR__ . '/../includes/config.php';
require_once dirname(__DIR__, 2) . '/backend/models/VendorAssignment.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

try {
    $service = new VendorAssignment($db);
    $role = $_SESSION['user_role'] ?? '';
    $userId = (int)($_SESSION['user_id'] ?? 0);

    // Admin/Gérant: peuvent voir toutes les distributions ouvertes, ou filtrer par vendeur_id si fourni
    if (in_array($role, ['admin', 'gerant'], true)) {
        $vendeurId = isset($_GET['vendeur_id']) ? (int)$_GET['vendeur_id'] : 0;
        if ($vendeurId > 0) {
            $stmt = $service->getOpenByVendeur($vendeurId);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Nom dépôt: nom/name ; Nom vendeur: full_name ou nom+prenoms
            $sql = "SELECT a.*, 
                    COALESCE(d.nom, d.name) AS depot_nom,
                    COALESCE(u.full_name, CONCAT(COALESCE(u.nom,''),' ',COALESCE(u.prenoms,''))) AS vendeur_nom
                    FROM vendor_assignments a
                    LEFT JOIN depots d ON d.id = a.depot_id
                    LEFT JOIN users u ON u.id = a.vendeur_id
                    WHERE a.status = 'open'
                    ORDER BY a.created_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        // Commercial: uniquement ses propres distributions ouvertes, avec noms enrichis
        $sql = "SELECT a.*, 
                COALESCE(d.nom, d.name) AS depot_nom,
                COALESCE(u.full_name, CONCAT(COALESCE(u.nom,''),' ',COALESCE(u.prenoms,''))) AS vendeur_nom
                FROM vendor_assignments a
                LEFT JOIN depots d ON d.id = a.depot_id
                LEFT JOIN users u ON u.id = a.vendeur_id
                WHERE a.status = 'open' AND a.vendeur_id = ?
                ORDER BY a.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode(['ok' => true, 'items' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error', 'message' => $e->getMessage()]);
}
