<?php
// Proxy vers le dashboard dynamique principal
require_once __DIR__ . '/../app/includes/config.php';
requireLogin();
include __DIR__ . '/../app/dashboard.php';
