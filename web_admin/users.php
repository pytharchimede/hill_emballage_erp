<?php
require_once __DIR__ . '/../app/includes/config.php';
requireLogin();
if (!hasPermission('manage_users')) {
    header('Location: ' . BASE_URL . '/web_admin/dashboard.php');
    exit();
}
require_once __DIR__ . '/../app/users.php';
