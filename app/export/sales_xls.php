<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
if (!hasPermission('sales_read')) {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="ventes_' . date('Ymd_His') . '.xls"');
echo "\xEF\xBB\xBF";

$search = $_GET['search'] ?? '';
$statut = $_GET['statut'] ?? 'all';
$type = $_GET['type'] ?? 'all';
$d1 = $_GET['d1'] ?? '';
$d2 = $_GET['d2'] ?? '';

$where = 'WHERE 1=1';
$params = [];
if ($search) {
    $q = "%$search%";
    $where .= " AND (v.numero_vente LIKE ? OR c.nom LIKE ? OR c.entreprise LIKE ? OR c.email LIKE ?)";
    array_push($params, $q, $q, $q, $q);
}
if ($statut !== 'all') {
    $where .= ' AND v.statut=?';
    $params[] = $statut;
}
if ($type !== 'all') {
    $where .= ' AND v.type_vente=?';
    $params[] = $type;
}
if ($d1) {
    $where .= ' AND v.date_vente>=?';
    $params[] = $d1;
}
if ($d2) {
    $where .= ' AND v.date_vente<=?';
    $params[] = $d2;
}

// Scoping par dépôt pour vendeur/comptable/livreur (sauf dépôt principal)
$userRole = $_SESSION['user_role'] ?? '';
if (in_array($userRole, ['vendeur', 'comptable', 'livreur'], true)) {
    $depotId = (int)($_SESSION['depot_id'] ?? 0);
    if ($depotId > 0 && !isMainDepot($depotId)) {
        $hasVenteDepot   = (bool)$db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='ventes' AND COLUMN_NAME='depot_id'")->fetchColumn();
        $hasVenteLivreur = (bool)$db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='ventes' AND COLUMN_NAME='livreur_id'")->fetchColumn();
        $hasVenteUser    = (bool)$db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='ventes' AND COLUMN_NAME='user_id'")->fetchColumn();
        $hasVenteClient  = (bool)$db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='ventes' AND COLUMN_NAME='client_id'")->fetchColumn();
        $hasClientDepot  = (bool)$db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='clients' AND COLUMN_NAME='depot_id'")->fetchColumn();

        if ($hasVenteDepot) {
            $where .= ' AND v.depot_id = ?';
            $params[] = $depotId;
        } elseif ($hasVenteLivreur) {
            $where .= ' AND v.livreur_id IN (SELECT id FROM users WHERE depot_id = ?)';
            $params[] = $depotId;
        } elseif ($hasVenteUser) {
            $where .= ' AND v.user_id IN (SELECT id FROM users WHERE depot_id = ?)';
            $params[] = $depotId;
        } elseif ($hasVenteClient && $hasClientDepot) {
            $where .= ' AND c.depot_id = ?';
            $params[] = $depotId;
        }
    }
}

$sql = "SELECT v.numero_vente, v.date_vente, c.nom as client_nom, v.type_vente, v.statut, v.montant_total, v.montant_paye, (v.montant_total - v.montant_paye) as restant
        FROM ventes v LEFT JOIN clients c ON v.client_id=c.id $where ORDER BY v.created_at DESC";
$st = $db->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
?>
<table border="1">
    <tr style="background:#FFD700;">
        <th>N°</th>
        <th>Date</th>
        <th>Client</th>
        <th>Type</th>
        <th>Statut</th>
        <th>Total</th>
        <th>Payé</th>
        <th>Reste</th>
    </tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['numero_vente']) ?></td>
            <td><?= htmlspecialchars($r['date_vente']) ?></td>
            <td><?= htmlspecialchars($r['client_nom']) ?></td>
            <td><?= htmlspecialchars($r['type_vente']) ?></td>
            <td><?= htmlspecialchars($r['statut']) ?></td>
            <td><?= number_format($r['montant_total'], 2, ',', ' ') ?></td>
            <td><?= number_format($r['montant_paye'], 2, ',', ' ') ?></td>
            <td><?= number_format($r['restant'], 2, ',', ' ') ?></td>
        </tr>
    <?php endforeach; ?>
</table>
<?php log_action('EXPORT_XLS', 'ventes');
