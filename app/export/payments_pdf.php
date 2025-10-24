<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/pdf.php';
if (!isLoggedIn() || !hasPermission('payments_read')) {
    http_response_code(403);
    exit('Accès refusé');
}
$search = $_GET['search'] ?? '';
$mode = $_GET['mode'] ?? 'all';
$statut = $_GET['statut'] ?? 'all';
$d1 = $_GET['d1'] ?? '';
$d2 = $_GET['d2'] ?? '';
$w = 'WHERE 1=1';
$p = [];
if ($search) {
    $w .= ' AND (p.numero_recu LIKE ? OR v.numero_vente LIKE ? OR c.nom LIKE ?)';
    $q = "%$search%";
    array_push($p, $q, $q, $q);
}
if ($mode !== 'all') {
    $w .= ' AND p.mode_payment=?';
    $p[] = $mode;
}
if ($statut !== 'all') {
    $w .= ' AND p.statut=?';
    $p[] = $statut;
}
if ($d1) {
    $w .= ' AND p.date_payment>=?';
    $p[] = $d1;
}
if ($d2) {
    $w .= ' AND p.date_payment<=?';
    $p[] = $d2;
}
$isVendor = (($_SESSION['user_role'] ?? '') === 'vendeur');
if ($isVendor) {
    // Restreindre aux données du dépôt du vendeur, en suivant la même priorité que les pages Ventes
    $current = getCurrentUser();
    $depotId = (int)($current['depot_id'] ?? 0);
    $w .= ' AND (v.depot_id = ? OR (v.livreur_id IS NOT NULL AND lvr.depot_id = ?) OR (v.user_id IS NOT NULL AND uu.depot_id = ?) OR c.depot_id = ?)';
    array_push($p, $depotId, $depotId, $depotId, $depotId);
}

$st = $db->prepare("SELECT p.*, v.numero_vente, c.nom as client_nom 
                    FROM payments p 
                    JOIN ventes v ON p.vente_id=v.id 
                    JOIN clients c ON v.client_id=c.id 
                    LEFT JOIN users uu ON v.user_id = uu.id 
                    LEFT JOIN users lvr ON v.livreur_id = lvr.id 
                    $w 
                    ORDER BY p.created_at DESC");
$st->execute($p);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
log_action('EXPORT_PDF', 'payments');
$pdf = new PdfDoc('P', 'mm', 'A4');
$pdf->titleText = 'Paiements';
$pdf->subTitle = 'Export du ' . date('d/m/Y H:i');
$pdf->AddPage();
$pdf->tableHeader([[30, 'Reçu'], [22, 'Date'], [25, 'Vente'], [40, 'Client'], [28, 'Montant'], [20, 'Mode'], [20, 'Statut']]);
foreach ($rows as $r) {
    $pdf->tableRow([[30, $r['numero_recu']], [22, $r['date_payment']], [25, $r['numero_vente']], [40, $r['client_nom']], [28, number_format($r['montant'], 0, ',', ' ')], [20, $r['mode_payment']], [20, $r['statut']]]);
}
pdf_output_headers('paiements.pdf');
echo $pdf->Output('I');
exit;
