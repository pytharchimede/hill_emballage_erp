<?php
require_once __DIR__ . '/../app/includes/config.php';
requireLogin();
// Autoriser rôles ayant sales_read
if (!hasPermission('sales_read') && $_SESSION['user_role'] !== 'vendeur') {
    header('Location: ' . BASE_URL . '/web_admin/dashboard.php');
    exit();
}
$pageTitle = 'Ventes';
include __DIR__ . '/../app/includes/header.php';
?>
<div class="page-header">
    <h1>Ventes</h1>
    <div class="breadcrumb">Gestion et enregistrement des ventes</div>
</div>
<div class="card">
    <p>Formulaires et liste des ventes à intégrer ici.</p>
</div>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>