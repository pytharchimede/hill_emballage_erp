<?php
require_once __DIR__ . '/../app/includes/config.php';
requireLogin();
if (!hasPermission('stock_read')) {
    header('Location: ' . BASE_URL . '/web_admin/dashboard.php');
    exit();
}
require_once __DIR__ . '/../app/stock.php';
