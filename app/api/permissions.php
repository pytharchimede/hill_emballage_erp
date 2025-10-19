<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

// Auth & authorization
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}
if (!hasPermission('manage_users')) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function json_input()
{
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

try {
    // Ensure table exists (idempotent)
    global $db;
    $db->exec("CREATE TABLE IF NOT EXISTS user_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        permission VARCHAR(100) NOT NULL,
        allowed TINYINT(1) NOT NULL DEFAULT 1,
        UNIQUE KEY uq_user_perm (user_id, permission)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    switch ($method) {
        case 'GET': {
                $userId = (int)($_GET['user_id'] ?? 0);
                if ($userId <= 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'missing user_id']);
                    break;
                }
                $stmt = $db->prepare('SELECT permission, allowed FROM user_permissions WHERE user_id=?');
                $stmt->execute([$userId]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['user_id' => $userId, 'overrides' => $items]);
                break;
            }
        case 'POST': {
                $data = json_input();
                $userId = (int)($data['user_id'] ?? 0);
                $overrides = $data['overrides'] ?? null; // array of {permission, allowed}
                if ($userId <= 0 || !is_array($overrides)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'invalid payload']);
                    break;
                }
                $db->beginTransaction();
                $up = $db->prepare('INSERT INTO user_permissions(user_id, permission, allowed) VALUES (?,?,?) ON DUPLICATE KEY UPDATE allowed=VALUES(allowed)');
                foreach ($overrides as $ov) {
                    $perm = trim((string)($ov['permission'] ?? ''));
                    $allowed = isset($ov['allowed']) ? (int)!!$ov['allowed'] : 1;
                    if ($perm === '') continue;
                    $up->execute([$userId, $perm, $allowed]);
                }
                $db->commit();
                echo json_encode(['status' => 'ok']);
                break;
            }
        case 'DELETE': {
                $userId = (int)($_GET['user_id'] ?? 0);
                $permission = isset($_GET['permission']) ? trim((string)$_GET['permission']) : '';
                if ($userId <= 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'missing user_id']);
                    break;
                }
                if ($permission === '') {
                    // delete all overrides for user
                    $stmt = $db->prepare('DELETE FROM user_permissions WHERE user_id=?');
                    $stmt->execute([$userId]);
                } else {
                    $stmt = $db->prepare('DELETE FROM user_permissions WHERE user_id=? AND permission=?');
                    $stmt->execute([$userId, $permission]);
                }
                echo json_encode(['status' => 'ok']);
                break;
            }
        default:
            http_response_code(405);
            echo json_encode(['error' => 'method_not_allowed']);
    }
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'server_error', 'message' => $e->getMessage()]);
}
