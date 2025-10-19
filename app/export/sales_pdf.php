<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/pdf.php';
if (!isLoggedIn() || !hasPermission('sales_read')) {
    http_response_code(403);
    exit('Accès refusé');
}
$search = $_GET['search'] ?? '';
$statut = $_GET['statut'] ?? 'all';
$type = $_GET['type'] ?? 'all';
$d1 = $_GET['d1'] ?? '';
$d2 = $_GET['d2'] ?? '';
$w = 'WHERE 1=1';
$p = [];
if ($search) {
    $w .= ' AND (v.numero_vente LIKE ? OR c.nom LIKE ?)';
    $q = "%$search%";
    array_push($p, $q, $q);
}
if ($statut !== 'all') {
    $w .= ' AND v.statut=?';
    $p[] = $statut;
}
if ($type !== 'all') {
    $w .= ' AND v.type_vente=?';
    $p[] = $type;
}
if ($d1) {
    $w .= ' AND v.date_vente>=?';
    $p[] = $d1;
}
if ($d2) {
    $w .= ' AND v.date_vente<=?';
    $p[] = $d2;
}
$st = $db->prepare("SELECT v.*, c.nom as client_nom FROM ventes v LEFT JOIN clients c ON v.client_id=c.id $w ORDER BY v.created_at DESC");
$st->execute($p);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
log_action('EXPORT_PDF', 'ventes');
$pdf = new PdfDoc('P', 'mm', 'A4');
$pdf->titleText = 'Ventes';
$pdf->subTitle = 'Export du ' . date('d/m/Y H:i');
$pdf->AddPage();
$pdf->tableHeader([[28, 'N°'], [22, 'Date'], [50, 'Client'], [20, 'Type'], [20, 'Statut'], [25, 'Total'], [25, 'Payé']]);
foreach ($rows as $r) {
    $pdf->tableRow([[28, $r['numero_vente']], [22, $r['date_vente']], [50, $r['client_nom']], [20, $r['type_vente']], [20, $r['statut']], [25, number_format($r['montant_total'], 0, ',', ' ')], [25, number_format($r['montant_paye'], 0, ',', ' ')]]);
}
pdf_output_headers('ventes.pdf');
echo $pdf->Output('I');
exit;
