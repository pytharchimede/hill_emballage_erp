<?php
require_once __DIR__ . '/../app/includes/config.php';
requireLogin();
if (!hasPermission('payments_read')) {
    header('Location: ' . BASE_URL . '/web_admin/dashboard.php');
    exit();
}
$pageTitle = 'Paiements';
include __DIR__ . '/../app/includes/header.php';
?>
<div class="page-header">
    <h1>Paiements</h1>
    <div class="breadcrumb">Encaissements et règlements</div>
</div>
<div class="card">
    <p>Gestion des paiements à intégrer ici.</p>
</div>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>