<?php
require_once __DIR__ . '/../app/includes/config.php';
requireLogin();
if (!hasPermission('depots_read')) {
    setFlashMessage('warning', "Accès refusé à Dépôts.");
    header('Location: ' . BASE_URL . '/web_admin/dashboard.php');
    exit();
}
require_once __DIR__ . '/../app/depots.php';
