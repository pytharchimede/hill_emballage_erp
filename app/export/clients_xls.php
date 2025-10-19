<?php
require_once __DIR__ . '/../includes/config.php';
if (!isLoggedIn() || !hasPermission('clients_read')) {
    http_response_code(403);
    exit('Accès refusé');
}
$search = $_GET['search'] ?? '';
$w = 'WHERE 1=1';
$p = [];
if ($search) {
    $w .= ' AND (c.nom LIKE ? OR c.email LIKE ? OR c.telephone LIKE ? OR c.entreprise LIKE ?)';
    $q = "%$search%";
    $p = [$q, $q, $q, $q];
}
$st = $db->prepare("SELECT c.* FROM clients c $w ORDER BY c.created_at DESC");
$st->execute($p);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
log_action('EXPORT_XLS', 'clients');
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename=clients.xls');
echo "\xEF\xBB\xBF";
echo '<table><tr><th>Nom</th><th>Email</th><th>Téléphone</th><th>Entreprise</th><th>Créé le</th></tr>';
foreach ($rows as $r) {
    $nom = $r['name'] ?? ($r['nom'] ?? '');
    $email = $r['email'] ?? '';
    $tel = $r['phone'] ?? ($r['telephone'] ?? '');
    $ent = $r['company'] ?? ($r['entreprise'] ?? '');
    echo '<tr><td>' . htmlspecialchars($nom) . '</td><td>' . htmlspecialchars($email) . '</td><td>' . htmlspecialchars($tel) . '</td><td>' . htmlspecialchars($ent) . '</td><td>' . htmlspecialchars($r['created_at']) . '</td></tr>';
}
echo '</table>';
exit;
