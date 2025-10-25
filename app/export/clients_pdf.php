<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/pdf.php';
if (!isLoggedIn() || !hasPermission('clients_read')) {
    http_response_code(403);
    exit('Accès refusé');
}
$search = $_GET['search'] ?? '';
$affect = $_GET['affect'] ?? 'all';
$livreurFilter = isset($_GET['livreur']) ? (int)$_GET['livreur'] : 0;
$w = 'WHERE 1=1';
$p = [];
if ($search) {
    $w .= ' AND (c.nom LIKE ? OR c.email LIKE ? OR c.telephone LIKE ? OR c.entreprise LIKE ?)';
    $q = "%$search%";
    $p = [$q, $q, $q, $q];
}
// Détection colonnes
$hasClientLivreur = (bool)$db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='clients' AND COLUMN_NAME='livreur_id'")->fetchColumn();
$hasClientDepot = (bool)$db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='clients' AND COLUMN_NAME='depot_id'")->fetchColumn();

// Filtre 'Sans livreur'
if ($affect === 'none' && $hasClientLivreur) {
    $w .= ' AND c.livreur_id IS NULL';
}
// Filtre par livreur précis
if ($livreurFilter > 0 && $hasClientLivreur) {
    $w .= ' AND c.livreur_id = ?';
    $p[] = $livreurFilter;
}

// Scoping par dépôt pour vendeur/comptable (sauf dépôt principal)
$userRole = $_SESSION['user_role'] ?? '';
if (in_array($userRole, ['vendeur', 'comptable'], true)) {
    $depotId = (int)($_SESSION['depot_id'] ?? 0);
    if ($depotId > 0 && !isMainDepot($depotId)) {
        if ($hasClientLivreur) {
            if ($hasClientDepot) {
                $w .= ' AND (c.livreur_id IN (SELECT id FROM users WHERE depot_id = ?) OR (c.livreur_id IS NULL AND c.depot_id = ?))';
                array_push($p, $depotId, $depotId);
            } else {
                $w .= ' AND (c.livreur_id IN (SELECT id FROM users WHERE depot_id = ?) OR c.livreur_id IS NULL)';
                $p[] = $depotId;
            }
        } elseif ($hasClientDepot) {
            $w .= ' AND c.depot_id = ?';
            $p[] = $depotId;
        }
    }
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
