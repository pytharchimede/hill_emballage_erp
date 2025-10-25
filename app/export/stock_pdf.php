<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/pdf.php';
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
// Scoping dépôt: vendeur/comptable/livreur (sauf dépôt principal)
$userRole = $_SESSION['user_role'] ?? '';
if (in_array($userRole, ['vendeur', 'comptable', 'livreur'], true)) {
    $depotId = (int)($_SESSION['depot_id'] ?? 0);
    if ($depotId > 0 && !isMainDepot($depotId)) {
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
log_action('EXPORT_PDF', 'stock');
$pdf = new PdfDoc('P', 'mm', 'A4');
$pdf->titleText = 'Transferts de stock';
$pdf->subTitle = 'Export du ' . date('d/m/Y H:i');
$pdf->AddPage();
$pdf->tableHeader([[28, 'Date'], [52, 'Produit'], [18, 'Qté'], [28, 'De'], [28, 'Vers'], [36, 'Motif']]);
foreach ($rows as $r) {
    $pdf->tableRow([[28, $r['date_transfer']], [52, $r['produit_nom']], [18, (string)$r['quantite']], [28, $r['depot_src']], [28, $r['depot_dst']], [36, $r['motif']]]);
}
pdf_output_headers('stock_transferts.pdf');
echo $pdf->Output('I');
exit;
