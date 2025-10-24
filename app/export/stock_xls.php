<?php
require_once __DIR__ . '/../includes/config.php';
if (!isLoggedIn() || !hasPermission('stock_read')) {
    http_response_code(403);
    exit('Accès refusé');
}
$td1 = $_GET['td1'] ?? '';
$td2 = $_GET['td2'] ?? '';
$tdepot = (int)($_GET['tdepot'] ?? 0);
$tprod = trim($_GET['tprod'] ?? '');
$tw = 'WHERE 1=1';
$tp = [];
// Scoping vendeur: forcer le dépôt
$userRole = $_SESSION['user_role'] ?? '';
if ($userRole === 'vendeur') {
    $depotId = (int)($_SESSION['depot_id'] ?? 0);
    if ($depotId > 0) {
        $tdepot = $depotId;
    }
}
if ($td1) {
    $tw .= ' AND st.date_transfer>=?';
    $tp[] = $td1;
}
if ($td2) {
    $tw .= ' AND st.date_transfer<=?';
    $tp[] = $td2;
}
if ($tdepot) {
    $tw .= ' AND (st.depot_source=? OR st.depot_destination=?)';
    array_push($tp, $tdepot, $tdepot);
}
if ($tprod !== '') {
    $tw .= ' AND (p.nom LIKE ? OR p.code_produit LIKE ?)';
    $like = "%$tprod%";
    array_push($tp, $like, $like);
}
$stmt = $db->prepare("SELECT st.*, p.nom as produit_nom, ds.nom as depot_src, dd.nom as depot_dst, u.full_name as user_nom
FROM stock_transfers st JOIN products p ON st.produit_id=p.id JOIN depots ds ON st.depot_source=ds.id JOIN depots dd ON st.depot_destination=dd.id JOIN users u ON st.user_id=u.id $tw ORDER BY st.date_transfer DESC");
$stmt->execute($tp);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
log_action('EXPORT_XLS', 'stock');
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename=stock_transferts.xls');
echo "\xEF\xBB\xBF";
echo '<table><tr><th>Date</th><th>Produit</th><th>Qté</th><th>De</th><th>Vers</th><th>Motif</th><th>Par</th></tr>';
foreach ($rows as $r) {
    echo '<tr><td>' . htmlspecialchars($r['date_transfer']) . '</td><td>' . htmlspecialchars($r['produit_nom']) . '</td><td>' . (float)$r['quantite'] . '</td><td>' . htmlspecialchars($r['depot_src']) . '</td><td>' . htmlspecialchars($r['depot_dst']) . '</td><td>' . htmlspecialchars($r['motif']) . '</td><td>' . htmlspecialchars($r['user_nom']) . '</td></tr>';
}
echo '</table>';
exit;
