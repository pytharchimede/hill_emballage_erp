<?php
require_once __DIR__ . '/../app/includes/config.php';
requireLogin();
requireRole(['comptable']);
include __DIR__ . '/../app/dashboard.php';
