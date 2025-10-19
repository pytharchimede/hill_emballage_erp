<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'missing user_id']);
    exit;
}

// If requesting other user's permissions, require manage_users
if ($userId !== (int)$_SESSION['user_id'] && !hasPermission('manage_users')) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

try {
    global $db;
    // fetch user role
    $stmt = $db->prepare('SELECT id, role FROM users WHERE id=?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'user_not_found']);
        exit;
    }

    $role = $user['role'];
    $rolePerms = getRolePermissions($role);

    // fetch overrides
    $ov = $db->prepare('SELECT permission, allowed FROM user_permissions WHERE user_id=?');
    $ov->execute([$userId]);
    $overrides = [];
    while ($r = $ov->fetch(PDO::FETCH_ASSOC)) {
        $overrides[$r['permission']] = (int)$r['allowed'];
    }

    // compute effective set (override wins, else role default)
    $effective = [];
    $allKeys = array_unique(array_merge($rolePerms, array_keys($overrides)));
    foreach ($allKeys as $perm) {
        if (array_key_exists($perm, $overrides)) {
            if ($overrides[$perm] === 1) {
                $effective[] = $perm;
            }
        } else {
            if (in_array($perm, $rolePerms, true)) {
                $effective[] = $perm;
            }
        }
    }

    // derive updated_at from user row (simple baseline); can be extended to consider override changes
    $ud = $db->prepare('SELECT updated_at FROM users WHERE id=?');
    $ud->execute([$userId]);
    $updatedAt = ($ud->fetchColumn()) ?: date('Y-m-d H:i:s');

    $payload = [
        'user_id' => (int)$userId,
        'role' => $role,
        'role_permissions' => array_values($rolePerms),
        'overrides' => array_map(function ($k, $v) {
            return ['permission' => $k, 'allowed' => $v];
        }, array_keys($overrides), $overrides),
        'effective_permissions' => array_values($effective),
        'updated_at' => $updatedAt,
    ];
    $etag = base64_encode(sha1(json_encode($payload) . '|' . $userId, true));
    header('ETag: ' . $etag);
    echo json_encode($payload);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error', 'message' => $e->getMessage()]);
}
