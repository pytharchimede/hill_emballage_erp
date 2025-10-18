<?php
require_once __DIR__ . '/../app/includes/config.php';
requireLogin();
if (!hasPermission('manage_users')) {
    header('Location: ' . BASE_URL . '/web_admin/dashboard.php');
    exit();
}
$pageTitle = 'Utilisateurs';
include __DIR__ . '/../app/includes/header.php';
?>
<div class="page-header">
    <h1>Utilisateurs</h1>
    <div class="breadcrumb">Gestion des utilisateurs et rôles</div>
</div>
<div class="card">
    <p>CRUD des utilisateurs à intégrer ici.</p>
</div>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>