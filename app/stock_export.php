<?php
require_once __DIR__ . '/includes/config.php';
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
                      FROM stock_transfers st
                      JOIN produits p ON st.produit_id=p.id
                      JOIN depots ds ON st.depot_source=ds.id
                      JOIN depots dd ON st.depot_destination=dd.id
                      JOIN users u ON st.user_id=u.id
                      $tw ORDER BY st.date_transfer DESC");
$stmt->execute($tp);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=transferts.csv');
$out = fopen('php://output', 'w');
fputcsv($out, ['Date', 'Produit', 'Qté', 'De', 'Vers', 'Motif', 'Par']);
foreach ($rows as $r) {
    fputcsv($out, [$r['date_transfer'], $r['produit_nom'], $r['quantite'], $r['depot_src'], $r['depot_dst'], $r['motif'], $r['user_nom']]);
}
fclose($out);
exit;
