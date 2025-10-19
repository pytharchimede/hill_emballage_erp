<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/pdf.php';
if (!isLoggedIn() || !hasPermission('clients_read')) {
    http_response_code(403);
    exit('Accès refusé');
}
$search = $_GET['search'] ?? '';
$w = 'WHERE 1=1';
$p = [];
if ($search) {
    $w .= ' AND (c.nom LIKE ? OR c.email LIKE ? OR c.telephone LIKE ? OR c.entreprise LIKE ?)';
    $q = "%$search%";
    $p = [$q, $q, $q, $q];
}
$st = $db->prepare("SELECT c.* FROM clients c $w ORDER BY c.created_at DESC");
$st->execute($p);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
log_action('EXPORT_PDF', 'clients');
$pdf = new PdfDoc('P', 'mm', 'A4');
$pdf->titleText = 'Listing Clients';
$pdf->subTitle = 'Export du ' . date('d/m/Y H:i');
$pdf->AddPage();
$pdf->tableHeader([[60, 'Nom'], [50, 'Email'], [35, 'Téléphone'], [45, 'Entreprise']]);
foreach ($rows as $r) {
    $nom = $r['name'] ?? ($r['nom'] ?? '');
    $email = $r['email'] ?? '';
    $tel = $r['phone'] ?? ($r['telephone'] ?? '');
    $ent = $r['company'] ?? ($r['entreprise'] ?? '');
    $pdf->tableRow([[60, $nom], [50, $email], [35, $tel], [45, $ent]]);
}
pdf_output_headers('clients.pdf');
echo $pdf->Output('I');
exit;
