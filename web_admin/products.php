<?php
require_once __DIR__ . '/../app/includes/config.php';
requireLogin();
if (!hasPermission('products_read')) {
    setFlashMessage('warning', 'Accès refusé à Produits.');
    header('Location: ' . BASE_URL . '/web_admin/dashboard.php');
    exit();
}
require_once __DIR__ . '/../app/products.php';
