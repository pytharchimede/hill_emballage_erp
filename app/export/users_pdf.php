<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/pdf.php';
if (!isLoggedIn() || !hasPermission('manage_users')) {
    http_response_code(403);
    exit('Accès refusé');
}
$rows = $db->query("SELECT username, full_name, email, role, is_active, created_at FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
log_action('EXPORT_PDF', 'users');
$pdf = new PdfDoc('P', 'mm', 'A4');
$pdf->titleText = 'Utilisateurs';
$pdf->subTitle = 'Export du ' . date('d/m/Y H:i');
$pdf->AddPage();
$pdf->tableHeader([[30, 'Login'], [50, 'Nom'], [55, 'Email'], [20, 'Rôle'], [15, 'Actif']]);
foreach ($rows as $u) {
    $pdf->tableRow([[30, $u['username']], [50, $u['full_name']], [55, $u['email']], [20, $u['role']], [15, $u['is_active'] ? 'Oui' : 'Non']]);
}
pdf_output_headers('users.pdf');
echo $pdf->Output('I');
exit;
