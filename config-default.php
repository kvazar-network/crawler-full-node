<?php

// Debug
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Application
define('STEP_BLOCK_LIMIT', 50);  // Blocks per query
define('CRAWLER_DEBUG', true);   // Debug output

// Database
define('DB_NAME', 'kvazar.sqlite');
define('DB_USERNAME', '');
define('DB_PASSWORD', '');

// Kevacoin wallet
define('KEVA_PROTOCOL', 'http');
define('KEVA_HOST', '127.0.0.1');
define('KEVA_PORT', '9992');
define('KEVA_USERNAME', '');
define('KEVA_PASSWORD', '');
