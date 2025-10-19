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
