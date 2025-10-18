<?php
// Redirection dynamique de web_admin vers dashboard/login
require_once __DIR__ . '/../app/includes/config.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}
header('Location: login.php');
exit();
