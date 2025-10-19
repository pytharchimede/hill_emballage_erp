<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('id');
}
$row = $db->prepare('SELECT * FROM attachments WHERE id=?');
$row->execute([$id]);
$att = $row->fetch(PDO::FETCH_ASSOC);
if (!$att) {
    http_response_code(404);
    exit('notfound');
}
$entity = $att['entity'];
$perm = ($entity === 'ventes') ? 'sales_update' : (($entity === 'payments') ? 'payments_update' : null);
if ($perm && !hasPermission($perm)) {
    http_response_code(403);
    exit('forbidden');
}
$db->prepare('DELETE FROM attachments WHERE id=?')->execute([$id]);
log_action('ATTACH_DELETE', $entity, (int)$att['entity_id'], ['attachment_id' => $id, 'path' => $att['path']]);
echo 'ok';
exit;
