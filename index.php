<?php

declare(strict_types=1);

// Redirect to installer if not yet installed
if (!file_exists(__DIR__ . '/config/.installed')) {
    header('Location: /install.php');
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

// Load environment from PHP config (OPcache-friendly, no file parsing)
$env = require __DIR__ . '/config/env.php';
foreach ($env as $key => $value) {
    $_ENV[$key] = (string) $value;
}

$appConfig = require __DIR__ . '/config/app.php';
$dbConfig  = require __DIR__ . '/config/database.php';

// Secure session config (set before any session_start)
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}

session_start();

// Generate CSRF token once per session
if (empty($_SESSION['_csrf_token'])) {
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
}

$app = \TinyShop\App::create($appConfig, $dbConfig);
$app->run();
