<?php
require_once __DIR__ . '/../app/includes/config.php';
requireLogin();
if (!hasPermission('stock_read')) {
    header('Location: ' . BASE_URL . '/web_admin/dashboard.php');
    exit();
}
$pageTitle = 'Stock';
include __DIR__ . '/../app/includes/header.php';
?>
<div class="page-header">
    <h1>Stock</h1>
    <div class="breadcrumb">Suivi des niveaux de stock</div>
</div>
<div class="card">
    <p>Liste des stocks et mouvements à intégrer ici.</p>
</div>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>