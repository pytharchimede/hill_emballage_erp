<?php
require_once __DIR__ . '/../app/includes/config.php';
requireLogin();
// Carte visible si lecture dépôts autorisée (même permission)
if (!hasPermission('depots_read')) {
    setFlashMessage('warning', "Accès refusé à la carte des dépôts.");
    header('Location: ' . BASE_URL . '/web_admin/dashboard.php');
    exit();
}
require_once __DIR__ . '/../app/depots_map.php';
