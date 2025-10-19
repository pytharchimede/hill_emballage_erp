<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/pdf.php';
if (!isLoggedIn()) {
    http_response_code(403);
    exit('Accès refusé');
}
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
if (!$isAdmin) {
    http_response_code(403);
    exit('Admin uniquement');
}
$u = (int)($_GET['user'] ?? 0);
$act = trim($_GET['action'] ?? '');
$d1 = $_GET['d1'] ?? '';
$d2 = $_GET['d2'] ?? '';
$w = 'WHERE 1=1';
$p = [];
if ($u) {
    $w .= ' AND a.user_id=?';
    $p[] = $u;
}
if ($act !== '') {
    $w .= ' AND a.action=?';
    $p[] = $act;
}
if ($d1) {
    $w .= ' AND a.created_at>=?';
    $p[] = $d1;
}
if ($d2) {
    $w .= ' AND a.created_at<=?';
    $p[] = $d2;
}
$st = $db->prepare("SELECT a.*, u.username, u.full_name FROM audit_logs a LEFT JOIN users u ON a.user_id=u.id $w ORDER BY a.created_at DESC");
$st->execute($p);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
log_action('EXPORT_PDF', 'activity');
$pdf = new PdfDoc('L', 'mm', 'A4');
$pdf->titleText = 'Historique des actions';
$pdf->subTitle = 'Export du ' . date('d/m/Y H:i');
$pdf->AddPage();
$pdf->tableHeader([[30, 'Date'], [25, 'Utilisateur'], [30, 'Action'], [35, 'Entité'], [25, 'ID'], [100, 'Détails'], [40, 'IP/Port']]);
foreach ($rows as $r) {
    $pdf->tableRow([[30, $r['created_at']], [25, ($r['full_name'] ?: $r['username'])], [30, $r['action']], [35, $r['entity']], [25, (string)$r['entity_id']], [100, substr((string)$r['details'], 0, 120)], [40, ($r['ip'] . ':' . $r['port'])]]);
}
pdf_output_headers('activity.pdf');
echo $pdf->Output('I');
exit;
