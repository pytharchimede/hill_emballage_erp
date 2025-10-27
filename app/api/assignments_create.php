<?php
require_once __DIR__ . '/../includes/config.php';
require_once dirname(__DIR__, 2) . '/backend/models/VendorAssignment.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

// Autorisé: admin, gerant
$role = $_SESSION['user_role'] ?? '';
if (!in_array($role, ['admin', 'gerant'], true) && !hasPermission('assignments_manage')) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

try {
    $input = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;

    // JSON body support
    if (empty($input)) {
        $raw = file_get_contents('php://input');
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) $input = $decoded;
        }
    }

    $depot_id   = isset($input['depot_id']) ? (int)$input['depot_id'] : 0;
    $vendeur_id = isset($input['vendeur_id']) ? (int)$input['vendeur_id'] : 0;
    $notes      = isset($input['notes']) ? trim((string)$input['notes']) : null;
    $assigner_id = (int)($_SESSION['user_id'] ?? 0);
    $details    = isset($input['details']) ? $input['details'] : [];

    if ($depot_id <= 0 || $vendeur_id <= 0 || !is_array($details) || count($details) === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'missing_parameters', 'hint' => 'depot_id, vendeur_id, details[] required']);
        exit;
    }

    // normaliser les détails
    $norm = [];
    foreach ($details as $d) {
        if (!isset($d['product_id'], $d['quantite'])) continue;
        $norm[] = [
            'product_id' => (int)$d['product_id'],
            'quantite'   => (float)$d['quantite'],
            'unit_price' => isset($d['unit_price']) ? (float)$d['unit_price'] : null,
        ];
    }
    if (empty($norm)) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_details']);
        exit;
    }

    $service = new VendorAssignment($db);
    $assignment_id = $service->create($depot_id, $vendeur_id, $assigner_id, $norm, $notes);

    log_action('CREATE', 'vendor_assignment', $assignment_id, ['depot_id' => $depot_id, 'vendeur_id' => $vendeur_id]);
    echo json_encode(['ok' => true, 'assignment_id' => $assignment_id]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error', 'message' => $e->getMessage()]);
}
