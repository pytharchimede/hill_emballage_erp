<?php
require_once __DIR__ . '/../includes/config.php';
if (!isLoggedIn()) {
    http_response_code(403);
    exit;
}
$entity = strtolower(trim($_GET['entity'] ?? ''));
$id = (int)($_GET['id'] ?? 0);
if (!$entity || $id <= 0) {
    http_response_code(400);
    exit('');
}
$st = $db->prepare('SELECT a.*, u.full_name FROM attachments a LEFT JOIN users u ON a.uploaded_by=u.id WHERE a.entity=? AND a.entity_id=? ORDER BY a.created_at DESC');
$st->execute([$entity, $id]);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
log_action('ATTACH_LIST', $entity, $id);
if (!$rows) {
    echo '<div>Aucune pièce jointe.</div>';
    exit;
}
echo '<ul class="list-group">';
foreach ($rows as $r) {
    echo '<li class="list-group-item d-flex justify-content-between align-items-center">'
        . '<a href="' . htmlspecialchars($r['path']) . '" target="_blank">' . basename(parse_url($r['path'], PHP_URL_PATH)) . '</a>'
        . '<span class="small">par ' . htmlspecialchars($r['full_name'] ?? '') . '</span>'
        . (hasPermission($entity === 'ventes' ? 'sales_update' : ($entity === 'payments' ? 'payments_update' : 'write')) ? '<button class="btn btn-danger btn-sm" data-delete-attachment="' . (int)$r['id'] . '"><i class="fas fa-trash"></i></button>' : '')
        . '</li>';
}
echo '</ul>';
exit;
