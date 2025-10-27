<?php
require_once __DIR__ . '/../includes/config.php';
require_once dirname(__DIR__, 2) . '/backend/models/VendorAssignment.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$role = normalizeRole($_SESSION['user_role'] ?? '');

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

    // Autorisations
    $allowed = in_array($role, ['admin', 'gerant'], true) || hasPermission('assignments_manage');
    if (!$allowed) {
        if ($role === 'vendeur') {
            // Vendeur: contraintes spécifiques
            $sessionDepotId = (int)($_SESSION['depot_id'] ?? 0);
            if ($sessionDepotId <= 0 || $depot_id !== $sessionDepotId) {
                http_response_code(403);
                echo json_encode(['error' => 'invalid_depot']);
                exit;
            }
            // Vérifier que l'assigné est un livreur (même dépôt si colonne présente)
            $colExists = function ($col) use ($db) {
                $q = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME=?");
                $q->execute([$col]);
                return ((int)$q->fetchColumn()) > 0;
            };
            $roleCol = $colExists('user_role') ? 'user_role' : 'role';
            $hasDepotCol = $colExists('depot_id');
            $sql = "SELECT $roleCol AS role" . ($hasDepotCol ? ", depot_id" : "") . " FROM users WHERE id = ?";
            $st = $db->prepare($sql);
            $st->execute([$vendeur_id]);
            $assignee = $st->fetch(PDO::FETCH_ASSOC);
            if (!$assignee || ($assignee['role'] ?? '') !== 'livreur') {
                http_response_code(403);
                echo json_encode(['error' => 'invalid_assignee']);
                exit;
            }
            if ($hasDepotCol && (int)$assignee['depot_id'] !== $sessionDepotId) {
                http_response_code(403);
                echo json_encode(['error' => 'invalid_assignee_depot']);
                exit;
            }
            // OK, autoriser
            $allowed = true;
        }
    }

    if (!$allowed) {
        http_response_code(403);
        echo json_encode(['error' => 'forbidden']);
        exit;
    }

    // Enforcer que l'assigné est bien un commercial
    $colExists = function ($col) use ($db) {
        $q = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME=?");
        $q->execute([$col]);
        return ((int)$q->fetchColumn()) > 0;
    };
    $roleCol = $colExists('user_role') ? 'user_role' : 'role';
    $st = $db->prepare("SELECT $roleCol AS role FROM users WHERE id = ?");
    $st->execute([$vendeur_id]);
    $assignee = $st->fetch(PDO::FETCH_ASSOC);
    if (!$assignee || normalizeRole($assignee['role'] ?? '') !== 'commercial') {
        http_response_code(400);
        echo json_encode(['error' => 'assignee_must_be_commercial']);
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
