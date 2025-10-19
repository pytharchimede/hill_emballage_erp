<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
if (!hasPermission('manage_users')) {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="activite_' . date('Ymd_His') . '.xls"');
echo "\xEF\xBB\xBF";

$u = isset($_GET['user']) ? (int)$_GET['user'] : 0;
$action = $_GET['action'] ?? '';
$d1 = $_GET['d1'] ?? '';
$d2 = $_GET['d2'] ?? '';

$where = 'WHERE 1=1';
$params = [];
if ($u > 0) {
    $where .= ' AND a.user_id=?';
    $params[] = $u;
}
if ($action !== '') {
    $where .= ' AND a.action=?';
    $params[] = $action;
}
if ($d1) {
    $where .= ' AND a.created_at>=?';
    $params[] = $d1;
}
if ($d2) {
    $where .= ' AND a.created_at<=?';
    $params[] = $d2;
}

$sql = "SELECT a.created_at, u.full_name, a.action, a.entity, a.entity_id, a.details, a.ip, a.user_agent
        FROM audit_logs a LEFT JOIN users u ON a.user_id=u.id $where ORDER BY a.created_at DESC";
$st = $db->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
?>
<table border="1">
    <tr style="background:#FFD700;">
        <th>Date</th>
        <th>Utilisateur</th>
        <th>Action</th>
        <th>Entité</th>
        <th>Entité ID</th>
        <th>Détails</th>
        <th>IP</th>
        <th>User-Agent</th>
    </tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['created_at']) ?></td>
            <td><?= htmlspecialchars($r['full_name'] ?? '—') ?></td>
            <td><?= htmlspecialchars($r['action']) ?></td>
            <td><?= htmlspecialchars($r['entity'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['entity_id'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['details'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['ip'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['user_agent'] ?? '') ?></td>
        </tr>
    <?php endforeach; ?>
</table>
<?php log_action('EXPORT_XLS', 'audit_logs');
