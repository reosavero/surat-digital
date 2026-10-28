<?php

/**
 * App Configuration - Surat Digital
 */

define('APP_NAME', 'Surat Digital');

// Load local overrides if present (e.g. sensitive credentials)
if (file_exists(__DIR__ . '/app.local.php')) {
    require_once __DIR__ . '/app.local.php';
}

// Asset versioning based on asset modification time
$_assetFiles = array_merge(
    glob(__DIR__ . '/../assets/css/*.css') ?: [],
    glob(__DIR__ . '/../assets/js/*.js') ?: []
);
$_assetMtime = 0;
foreach ($_assetFiles as $_file) {
    $_m = @filemtime($_file);
    if ($_m > $_assetMtime) {
        $_assetMtime = $_m;
    }
}
define('APP_VERSION', $_assetMtime > 0 ? (string) $_assetMtime : '1.0.0');
unset($_assetFiles, $_assetMtime);

define('APP_ENV', 'development');

// Dynamic BASE_URL calculation - works anywhere under htdocs!
if (PHP_SAPI === 'cli') {
    $baseFolder = basename(dirname(__DIR__));
    $baseUrlVal = 'http://localhost/' . $baseFolder;
} else {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    $baseUrlVal = $scheme . '://' . $host . $scriptDir;
}

if (!defined('BASE_URL')) {
    define('BASE_URL', $baseUrlVal);
}
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

// Directory paths
define('CONFIG_PATH', BASE_PATH . 'config' . DIRECTORY_SEPARATOR);
define('CONTROLLERS_PATH', BASE_PATH . 'controllers' . DIRECTORY_SEPARATOR);
define('MODELS_PATH', BASE_PATH . 'models' . DIRECTORY_SEPARATOR);
define('VIEWS_PATH', BASE_PATH . 'views' . DIRECTORY_SEPARATOR);
define('HELPERS_PATH', BASE_PATH . 'helpers' . DIRECTORY_SEPARATOR);
define('ROUTES_PATH', BASE_PATH . 'routes' . DIRECTORY_SEPARATOR);
define('ASSETS_PATH', BASE_PATH . 'assets' . DIRECTORY_SEPARATOR);

date_default_timezone_set('Asia/Jakarta');

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Session cookie configs
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', 86400);

// SMTP Mail Configuration (Gmail)
if (!defined('SMTP_HOST')) define('SMTP_HOST', 'smtp.gmail.com');
if (!defined('SMTP_PORT')) define('SMTP_PORT', 587);
if (!defined('SMTP_USER')) define('SMTP_USER', 'medsostvrijatim@gmail.com');
if (!defined('SMTP_PASS')) define('SMTP_PASS', '');
if (!defined('SMTP_FROM')) define('SMTP_FROM', 'medsostvrijatim@gmail.com');
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', 'Surat Digital TVRI');
