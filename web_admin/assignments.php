<?php
require_once __DIR__ . '/../app/includes/config.php';
requireLogin();
// Lecture/écriture protégées par les API; ici, on exige au moins la vue
if (!hasPermission('assignments_view') && !in_array(($_SESSION['user_role'] ?? ''), ['admin', 'gerant'], true)) {
    header('Location: ' . BASE_URL . '/web_admin/dashboard.php');
    exit();
}
require_once __DIR__ . '/../app/assignments.php';
