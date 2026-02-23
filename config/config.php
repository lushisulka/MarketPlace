<?php
session_start();

define('SITE_URL', 'http://localhost/marketplace');
define('SITE_NAME', 'FrutaMarket');
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/marketplace/uploads/');

// Auto-load database
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';