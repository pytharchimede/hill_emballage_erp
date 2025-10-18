<?php
require_once __DIR__ . '/../app/includes/config.php';
requireLogin();
if (!in_array($_SESSION['user_role'], ['livreur', 'admin'])) {
    header('Location: ' . BASE_URL . '/web_admin/dashboard.php');
    exit();
}
$pageTitle = 'Livraisons';
include __DIR__ . '/../app/includes/header.php';
?>
<div class="page-header">
    <h1>Livraisons</h1>
    <div class="breadcrumb">Tâches de livraison</div>
</div>
<div class="card">
    <p>Gestion des livraisons à intégrer ici.</p>
</div>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>