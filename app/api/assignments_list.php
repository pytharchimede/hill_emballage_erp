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

    $vendeurId = null;
    // Gérants/Admins peuvent fournir un vendeur_id pour filtrer
    if (in_array($role, ['admin', 'gerant'], true)) {
        if (isset($_GET['vendeur_id'])) {
            $vendeurId = (int)$_GET['vendeur_id'];
        }
    }

    if ($vendeurId === null) {
        // Vendeur/Livreur/Commercial voient leurs assignations ouvertes
        $vendeurId = $userId;
    }

    $stmt = $service->getOpenByVendeur($vendeurId);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['ok' => true, 'items' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error', 'message' => $e->getMessage()]);
}
