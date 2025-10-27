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
    $raw = file_get_contents('php://input');
    $input = $_POST;
    if ($raw) {
        $dec = json_decode($raw, true);
        if (is_array($dec)) $input = $dec;
    }

    $assignment_id = isset($input['assignment_id']) ? (int)$input['assignment_id'] : 0;
    $depot_id = isset($input['depot_id']) ? (int)$input['depot_id'] : 0;
    $returns = isset($input['returns']) ? $input['returns'] : [];

    if ($assignment_id <= 0 || $depot_id <= 0 || !is_array($returns) || count($returns) === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'missing_parameters']);
        exit;
    }

    $norm = [];
    foreach ($returns as $r) {
        if (!isset($r['product_id'], $r['quantite'])) continue;
        $norm[] = [
            'product_id' => (int)$r['product_id'],
            'quantite'   => (float)$r['quantite'],
        ];
    }
    if (empty($norm)) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_returns']);
        exit;
    }

    $service = new VendorAssignment($db);
    $ok = $service->registerReturns($assignment_id, $depot_id, $norm);
    log_action('UPDATE', 'vendor_assignment_return', $assignment_id, ['count' => count($norm)]);
    echo json_encode(['ok' => (bool)$ok]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error', 'message' => $e->getMessage()]);
}
