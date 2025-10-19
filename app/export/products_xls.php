<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
if (!hasPermission('products_read')) {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="produits_' . date('Ymd_His') . '.xls"');
echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel

// Determine table
$tbl = 'produits';
$search = $_GET['search'] ?? '';
$where = colExists($db, $tbl, 'is_active') ? 'WHERE is_active=1' : 'WHERE 1=1';
$params = [];
if ($search) {
    $where .= ' AND (nom LIKE ? OR code_produit LIKE ?)';
    $q = "%$search%";
    $params = [$q, $q];
}
$sql = "SELECT code_produit, nom, unite, prix_unitaire, prix_credit, points_fidelite FROM $tbl $where ORDER BY nom";
$st = $db->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

?>
<table border="1">
    <tr style="background:#FFD700;">
        <th>Code</th>
        <th>Nom</th>
        <th>Unité</th>
        <th>Prix</th>
        <th>Prix crédit</th>
        <th>Points</th>
    </tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['code_produit']) ?></td>
            <td><?= htmlspecialchars($r['nom']) ?></td>
            <td><?= htmlspecialchars($r['unite']) ?></td>
            <td><?= number_format($r['prix_unitaire'], 2, ',', ' ') ?></td>
            <td><?= $r['prix_credit'] !== null ? number_format($r['prix_credit'], 2, ',', ' ') : '' ?></td>
            <td><?= (int)$r['points_fidelite'] ?></td>
        </tr>
    <?php endforeach; ?>
</table>
<?php log_action('EXPORT_XLS', 'produits');
