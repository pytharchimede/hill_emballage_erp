<?php
require_once __DIR__ . '/../app/includes/config.php';
requireLogin();
if (!hasPermission('view_reports')) {
    header('Location: ' . BASE_URL . '/web_admin/dashboard.php');
    exit();
}
$pageTitle = 'Rapports';
include __DIR__ . '/../app/includes/header.php';
?>
<div class="page-header">
    <h1>Rapports</h1>
    <div class="breadcrumb">Tableaux de bord et statistiques</div>
</div>
<div class="card">
    <p>Rapports détaillés à intégrer ici.</p>
</div>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>