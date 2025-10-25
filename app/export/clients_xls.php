<?php
require_once __DIR__ . '/../includes/config.php';
if (!isLoggedIn() || !hasPermission('clients_read')) {
    http_response_code(403);
    exit('Accès refusé');
}
$search = $_GET['search'] ?? '';
$affect = $_GET['affect'] ?? 'all';
$livreurFilter = isset($_GET['livreur']) ? (int)$_GET['livreur'] : 0;
$w = 'WHERE 1=1';
$p = [];
if ($search) {
    $w .= ' AND (c.nom LIKE ? OR c.email LIKE ? OR c.telephone LIKE ? OR c.entreprise LIKE ?)';
    $q = "%$search%";
    $p = [$q, $q, $q, $q];
}
// Détection colonnes
$hasClientLivreur = (bool)$db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='clients' AND COLUMN_NAME='livreur_id'")->fetchColumn();
$hasClientDepot = (bool)$db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='clients' AND COLUMN_NAME='depot_id'")->fetchColumn();

// Filtre 'Sans livreur'
if ($affect === 'none' && $hasClientLivreur) {
    $w .= ' AND c.livreur_id IS NULL';
}
// Filtre par livreur précis
if ($livreurFilter > 0 && $hasClientLivreur) {
    $w .= ' AND c.livreur_id = ?';
    $p[] = $livreurFilter;
}

// Scoping par dépôt pour vendeur/comptable (sauf dépôt principal)
$userRole = $_SESSION['user_role'] ?? '';
if (in_array($userRole, ['vendeur', 'comptable'], true)) {
    $depotId = (int)($_SESSION['depot_id'] ?? 0);
    if ($depotId > 0 && !isMainDepot($depotId)) {
        if ($hasClientLivreur) {
            if ($hasClientDepot) {
                $w .= ' AND (c.livreur_id IN (SELECT id FROM users WHERE depot_id = ?) OR (c.livreur_id IS NULL AND c.depot_id = ?))';
                array_push($p, $depotId, $depotId);
            } else {
                $w .= ' AND (c.livreur_id IN (SELECT id FROM users WHERE depot_id = ?) OR c.livreur_id IS NULL)';
                $p[] = $depotId;
            }
        } elseif ($hasClientDepot) {
            $w .= ' AND c.depot_id = ?';
            $p[] = $depotId;
        }
    }
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
