<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/pdf.php';
if (!isLoggedIn() || !hasPermission('payments_read')) {
    http_response_code(403);
    exit('Accès refusé');
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('Paiement introuvable');
}

$sql = "SELECT p.*, v.numero_vente, v.client_id, c.nom as client_nom, c.entreprise
        FROM payments p JOIN ventes v ON p.vente_id=v.id
        LEFT JOIN clients c ON v.client_id=c.id WHERE p.id=?";
$st = $db->prepare($sql);
$st->execute([$id]);
$p = $st->fetch(PDO::FETCH_ASSOC);
if (!$p) {
    http_response_code(404);
    exit('Paiement introuvable');
}

log_action('EXPORT_PDF', 'payments', $id, ['type' => 'receipt']);

$pdf = new PdfDoc('P', 'mm', 'A4');
$pdf->titleText = 'REÇU DE PAIEMENT';
$pdf->subTitle = 'N° ' . $p['numero_recu'] . ' — ' . date('d/m/Y', strtotime($p['date_payment']));
$pdf->company = APP_NAME;
$pdf->companyInfo = 'www.hill-emballage.local';
$pdf->AddPage();

$pdf->sectionTitle('Paiement');
$pdf->kv('Vente', $p['numero_vente']);
$client = trim(($p['client_nom'] ?? 'Client') . ($p['entreprise'] ? ' — ' . $p['entreprise'] : ''));
$pdf->kv('Client', $client);
$pdf->kv('Montant', number_format((float)$p['montant'], 0, ',', ' ') . ' FCFA');
$pdf->kv('Mode', ucfirst((string)$p['mode_payment']));
$pdf->kv('Référence', (string)($p['reference'] ?? '—'));

pdf_output_headers('recu_' . $p['numero_recu'] . '.pdf');
echo $pdf->Output('I');
exit;
