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
    $vendeur_id = isset($input['vendeur_id']) ? (int)$input['vendeur_id'] : 0;
    $cashPaid = isset($input['cash_paid']) ? (float)$input['cash_paid'] : 0;
    $notes = isset($input['notes']) ? trim((string)$input['notes']) : null;
    $receiver_id = (int)($_SESSION['user_id'] ?? 0);

    if ($assignment_id <= 0 || $vendeur_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'missing_parameters']);
        exit;
    }

    $service = new VendorAssignment($db);
    $ok = $service->settleAndClose($assignment_id, $vendeur_id, $receiver_id, $cashPaid, $notes);
    log_action('UPDATE', 'vendor_assignment_settle', $assignment_id, ['cash_paid' => $cashPaid]);
    echo json_encode(['ok' => (bool)$ok]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error', 'message' => $e->getMessage()]);
}
