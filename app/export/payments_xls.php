<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
if (!hasPermission('payments_read')) {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="paiements_' . date('Ymd_His') . '.xls"');
echo "\xEF\xBB\xBF";

$search = $_GET['search'] ?? '';
$d1 = $_GET['d1'] ?? '';
$d2 = $_GET['d2'] ?? '';
$mode = $_GET['mode'] ?? 'all';
$statut = $_GET['statut'] ?? 'all';

$where = 'WHERE 1=1';
$params = [];
if ($search) {
    $q = "%$search%";
    $where .= ' AND (p.numero_recu LIKE ? OR v.numero_vente LIKE ? OR c.nom LIKE ?)';
    array_push($params, $q, $q, $q);
}
if ($mode !== 'all') {
    $where .= ' AND p.mode_payment=?';
    $params[] = $mode;
}
if ($statut !== 'all') {
    $where .= ' AND p.statut=?';
    $params[] = $statut;
}
if ($d1) {
    $where .= ' AND p.date_payment>=?';
    $params[] = $d1;
}
if ($d2) {
    $where .= ' AND p.date_payment<=?';
    $params[] = $d2;
}

$role = $_SESSION['user_role'] ?? '';
if (in_array($role, ['vendeur', 'comptable', 'livreur'], true)) {
    $current = getCurrentUser();
    $depotId = (int)($current['depot_id'] ?? 0);
    if ($depotId > 0 && !isMainDepot($depotId)) {
        // Restreindre aux données du dépôt, en suivant la même priorité que les pages Ventes
        $where .= ' AND (v.depot_id = ? OR (v.livreur_id IS NOT NULL AND lvr.depot_id = ?) OR (v.user_id IS NOT NULL AND uu.depot_id = ?) OR c.depot_id = ?)';
        array_push($params, $depotId, $depotId, $depotId, $depotId);
    }
}

$sql = "SELECT p.numero_recu, p.date_payment, v.numero_vente, c.nom as client_nom, p.montant, p.mode_payment, p.statut
    FROM payments p 
    JOIN ventes v ON p.vente_id=v.id 
    JOIN clients c ON v.client_id=c.id 
    LEFT JOIN users uu ON v.user_id = uu.id 
    LEFT JOIN users lvr ON v.livreur_id = lvr.id 
    $where 
    ORDER BY p.created_at DESC";
$st = $db->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
?>
<table border="1">
    <tr style="background:#FFD700;">
        <th>Reçu</th>
        <th>Date</th>
        <th>Vente</th>
        <th>Client</th>
        <th>Montant</th>
        <th>Mode</th>
        <th>Statut</th>
    </tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['numero_recu']) ?></td>
            <td><?= htmlspecialchars($r['date_payment']) ?></td>
            <td><?= htmlspecialchars($r['numero_vente']) ?></td>
            <td><?= htmlspecialchars($r['client_nom']) ?></td>
            <td><?= number_format($r['montant'], 2, ',', ' ') ?></td>
            <td><?= htmlspecialchars($r['mode_payment']) ?></td>
            <td><?= htmlspecialchars($r['statut']) ?></td>
        </tr>
    <?php endforeach; ?>
</table>
<?php log_action('EXPORT_XLS', 'payments');
