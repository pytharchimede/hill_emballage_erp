<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

header('X-Content-Type-Options: nosniff');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$entity = strtolower(trim($_POST['entity'] ?? ''));
$entityId = (int)($_POST['entity_id'] ?? 0);
$redirect = $_POST['redirect'] ?? ($_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/web_admin/dashboard.php'));

// Permissions mapping
$permMap = [
    'ventes' => 'sales_update',
    'payments' => 'payments_update',
    'produits' => 'products_update',
    'clients' => 'clients_update',
];
if (!$entity || $entityId <= 0 || !isset($_FILES['file'])) {
    if ((isset($_GET['format']) && $_GET['format'] === 'json') || (isset($_POST['format']) && $_POST['format'] === 'json')) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => false,
            'error' => 'missing_parameters',
            'debug' => [
                'entity' => $entity,
                'entity_id' => $entityId,
                'has_file' => isset($_FILES['file']),
                'session_user' => $_SESSION['user_id'] ?? null
            ]
        ]);
        exit;
    }
    setFlashMessage('error', 'Paramètres manquants pour la pièce jointe.');
    header('Location: ' . $redirect);
    exit;
}

$perm = $permMap[$entity] ?? null;
// Cas particulier: permettre au livreur (deliveries_update) d'ajouter des pièces sur ventes
if ($entity === 'ventes') {
    $allowed = hasPermission('sales_update') || hasPermission('deliveries_update');
    if (!$allowed) {
        if ((isset($_GET['format']) && $_GET['format'] === 'json') || (isset($_POST['format']) && $_POST['format'] === 'json')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'ok' => false,
                'error' => 'forbidden',
                'debug' => [
                    'role' => $_SESSION['user_role'] ?? null,
                    'has_sales_update' => hasPermission('sales_update'),
                    'has_deliveries_update' => hasPermission('deliveries_update')
                ]
            ]);
            exit;
        }
        setFlashMessage('warning', "Vous n'avez pas le droit d'ajouter des pièces jointes sur ventes.");
        header('Location: ' . $redirect);
        exit;
    }
} elseif ($perm && !hasPermission($perm)) {
    if ((isset($_GET['format']) && $_GET['format'] === 'json') || (isset($_POST['format']) && $_POST['format'] === 'json')) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => false,
            'error' => 'forbidden',
            'debug' => [
                'role' => $_SESSION['user_role'] ?? null,
                'perm_required' => $perm,
                'has_perm' => hasPermission($perm)
            ]
        ]);
        exit;
    }
    setFlashMessage('warning', "Vous n'avez pas le droit d'ajouter des pièces jointes sur $entity.");
    header('Location: ' . $redirect);
    exit;
}

try {
    $f = $_FILES['file'];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new Exception('upload_invalid_code_' . $f['error']);
    $mime = @mime_content_type($f['tmp_name']);
    $allowed = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    ];
    $ext = $allowed[$mime] ?? pathinfo($f['name'], PATHINFO_EXTENSION);
    $base = __DIR__ . '/../../web_admin/uploads/docs/' . $entity . '/' . $entityId;
    if (!is_dir($base)) {
        @mkdir($base, 0777, true);
    }
    $name = $entity . '_' . $entityId . '_' . date('YmdHis') . '_' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $ext;
    $dest = $base . DIRECTORY_SEPARATOR . $name;
    if (!@move_uploaded_file($f['tmp_name'], $dest)) throw new Exception('move_failed');
    $publicPath = BASE_URL . '/web_admin/uploads/docs/' . $entity . '/' . $entityId . '/' . $name;
    $st = $db->prepare('INSERT INTO attachments (entity, entity_id, path, mime, uploaded_by) VALUES (?,?,?,?,?)');
    $st->execute([$entity, $entityId, $publicPath, $mime, $_SESSION['user_id']]);
    log_action('ATTACH', $entity, $entityId, ['path' => $publicPath, 'mime' => $mime]);

    // JSON or redirect
    if ((isset($_GET['format']) && $_GET['format'] === 'json') || (isset($_POST['format']) && $_POST['format'] === 'json')) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'path' => $publicPath]);
    } else {
        setFlashMessage('success', 'Pièce jointe ajoutée.');
        header('Location: ' . $redirect);
    }
} catch (Exception $e) {
    if ((isset($_GET['format']) && $_GET['format'] === 'json') || (isset($_POST['format']) && $_POST['format'] === 'json')) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => false,
            'error' => $e->getMessage(),
            'debug' => [
                'entity' => $entity,
                'entity_id' => $entityId,
                'original_name' => $_FILES['file']['name'] ?? null,
                'size' => $_FILES['file']['size'] ?? null,
                'mime_detected' => $mime ?? null,
                'session_user' => $_SESSION['user_id'] ?? null
            ]
        ]);
    } else {
        setFlashMessage('error', $e->getMessage());
        header('Location: ' . $redirect);
    }
}
