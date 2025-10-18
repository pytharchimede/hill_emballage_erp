<?php
require_once __DIR__ . '/../app/includes/config.php';
requireLogin();
if (!in_array($_SESSION['user_role'], ['comptable', 'admin'])) {
    header('Location: ' . BASE_URL . '/web_admin/dashboard.php');
    exit();
}
$pageTitle = 'Crédits';
include __DIR__ . '/../app/includes/header.php';
?>
<div class="page-header">
    <h1>Crédits</h1>
    <div class="breadcrumb">Suivi des ventes à crédit</div>
</div>
<div class="card">
    <p>Gestion des crédits à intégrer ici.</p>
</div>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>