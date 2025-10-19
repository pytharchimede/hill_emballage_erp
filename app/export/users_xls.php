<?php
require_once __DIR__ . '/../includes/config.php';
if (!isLoggedIn() || !hasPermission('manage_users')) {
    http_response_code(403);
    exit('Accès refusé');
}
$rows = $db->query("SELECT id, username, full_name, email, role, is_active, created_at FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
log_action('EXPORT_XLS', 'users');
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename=users.xls');
echo "\xEF\xBB\xBF";
echo '<table><tr><th>Login</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Actif</th><th>Créé le</th></tr>';
foreach ($rows as $u) {
    echo '<tr><td>' . htmlspecialchars($u['username']) . '</td><td>' . htmlspecialchars($u['full_name']) . '</td><td>' . htmlspecialchars($u['email']) . '</td><td>' . htmlspecialchars($u['role']) . '</td><td>' . ($u['is_active'] ? 'Oui' : 'Non') . '</td><td>' . htmlspecialchars($u['created_at']) . '</td></tr>';
}
echo '</table>';
exit;
