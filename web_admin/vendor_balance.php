<?php
require_once __DIR__ . '/../app/includes/config.php';
requireLogin();
// Vue accessible aux rôles disposant de lecture générale ou vendeurs
require_once __DIR__ . '/../app/vendor_balance.php';
