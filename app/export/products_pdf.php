<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/pdf.php';
if (!isLoggedIn() || !hasPermission('products_read')) {
    http_response_code(403);
    exit('Accès refusé');
}
$search = $_GET['search'] ?? '';
$w = 'WHERE 1=1';
$p = [];
if ($search) {
    $w .= ' AND (nom LIKE ? OR code_produit LIKE ?)';
    $q = "%$search%";
    $p = [$q, $q];
}
// Détecter table
$tbl = 'produits';
try {
    $chk = $db->query("SELECT 1 FROM products LIMIT 1");
    if ($chk) {
        $tbl = 'products';
    }
} catch (Exception $e) {
}
$st = $db->prepare("SELECT * FROM $tbl $w ORDER BY updated_at DESC, created_at DESC");
$st->execute($p);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
log_action('EXPORT_PDF', 'produits');
$pdf = new PdfDoc('P', 'mm', 'A4');
$pdf->titleText = 'Produits';
$pdf->subTitle = 'Export du ' . date('d/m/Y H:i');
$pdf->AddPage();
$pdf->tableHeader([[30, 'Code'], [70, 'Nom'], [25, 'Unité'], [30, 'Prix'], [30, 'Crédit']]);
foreach ($rows as $r) {
    $pdf->tableRow([[30, $r['code_produit']], [70, $r['nom']], [25, $r['unite']], [30, number_format($r['prix_unitaire'], 0, ',', ' ')], [30, ($r['prix_credit'] !== null ? number_format($r['prix_credit'], 0, ',', ' ') : '-')]]);
}
pdf_output_headers('produits.pdf');
echo $pdf->Output('I');
exit;
