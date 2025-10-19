<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/pdf.php';
if (!isLoggedIn() || !hasPermission('sales_read')) {
    http_response_code(403);
    exit('Accès refusé');
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('Vente introuvable');
}

$sql = "SELECT v.*, c.nom as client_nom, c.entreprise, c.adresse, c.telephone, u.full_name as vendeur_nom
        FROM ventes v
        LEFT JOIN clients c ON v.client_id=c.id
        LEFT JOIN users u ON v.user_id=u.id
        WHERE v.id=?";
$st = $db->prepare($sql);
$st->execute([$id]);
$vente = $st->fetch(PDO::FETCH_ASSOC);
if (!$vente) {
    http_response_code(404);
    exit('Vente introuvable');
}
$items = $db->prepare("SELECT * FROM vente_items WHERE vente_id=?");
$items->execute([$id]);
$rows = $items->fetchAll(PDO::FETCH_ASSOC);

log_action('EXPORT_PDF', 'ventes', $id, ['type' => 'invoice']);

$pdf = new PdfDoc('P', 'mm', 'A4');
$pdf->titleText = 'FACTURE';
$pdf->subTitle = 'N° ' . $vente['numero_vente'] . ' — ' . date('d/m/Y', strtotime($vente['date_vente']));
$pdf->company = APP_NAME;
$pdf->companyInfo = 'www.hill-emballage.local';
$pdf->AddPage();

// Client
$pdf->sectionTitle('Client');
$nomClient = trim(($vente['client_nom'] ?? 'Client') . ($vente['entreprise'] ? ' — ' . $vente['entreprise'] : ''));
$pdf->kv('Nom', $nomClient);
$pdf->kv('Adresse', (string)($vente['adresse'] ?? ''));
$pdf->kv('Téléphone', (string)($vente['telephone'] ?? ''));
$pdf->Ln(2);

// Détails facture
$pdf->sectionTitle('Détails');
$pdf->kv('Vendeur', (string)($vente['vendeur_nom'] ?? ''));
$pdf->kv('Type', ucfirst((string)$vente['type_vente']));
$pdf->kv('Statut', ucfirst((string)$vente['statut']));
$pdf->Ln(2);

// Tableau articles
$headers = [[80, 'Produit'], [25, 'Qté'], [35, 'PU'], [35, 'Montant']];
$pdf->tableHeader($headers);
$total = 0.0;
foreach ($rows as $r) {
    $m = (float)$r['montant'];
    $total += $m;
    $pdf->tableRow([
        [80, (string)$r['nom_produit']],
        [25, (string)$r['quantite']],
        [35, number_format((float)$r['prix_unitaire'], 0, ',', ' ') . ' FCFA'],
        [35, number_format($m, 0, ',', ' ') . ' FCFA'],
    ]);
}
$pdf->Ln(4);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(20, 20, 20);
$pdf->Cell(0, 8, 'Total: ' . number_format((float)$vente['montant_total'], 0, ',', ' ') . ' FCFA', 0, 1, 'R');
$reste = ((float)$vente['montant_total']) - ((float)$vente['montant_paye']);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Payé: ' . number_format((float)$vente['montant_paye'], 0, ',', ' ') . ' FCFA', 0, 1, 'R');
$pdf->Cell(0, 6, 'Reste: ' . number_format($reste, 0, ',', ' ') . ' FCFA', 0, 1, 'R');

pdf_output_headers('facture_' . $vente['numero_vente'] . '.pdf');
echo $pdf->Output('I');
exit;
