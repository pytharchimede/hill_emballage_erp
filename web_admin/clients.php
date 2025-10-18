<?php
require_once __DIR__ . '/../app/includes/config.php';
requireLogin();
// Autoriser tous rôles ayant clients_read
if (!hasPermission('clients_read')) {
    header('Location: ' . BASE_URL . '/web_admin/dashboard.php');
    exit();
}
// Inclure la page app correspondante
require_once __DIR__ . '/../app/clients.php';
