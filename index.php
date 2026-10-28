<?php

/**
 * Surat Digital - TVRI Jawa Timur
 * Main Application Entry Point
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Configs
    require_once __DIR__ . '/config/app.php';
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/config/security.php';

    // Session & Security
    require_once __DIR__ . '/helpers/Session.php';
    Session::init();

    require_once __DIR__ . '/helpers/Security.php';
    Security::setSecurityHeaders();

    // Database & Autoloader
    require_once __DIR__ . '/helpers/Database.php';
    require_once __DIR__ . '/helpers/Autoloader.php';

    // MVC Core Classes
    require_once __DIR__ . '/helpers/functions.php';
    require_once __DIR__ . '/helpers/Validator.php';
    require_once __DIR__ . '/helpers/Router.php';
    require_once __DIR__ . '/helpers/Controller.php';
    require_once __DIR__ . '/helpers/Model.php';

    // Routes
    require_once __DIR__ . '/routes/web.php';

    // Dispatch router
    Router::dispatch();

} catch (Throwable $e) {
    http_response_code(500);
    if (function_exists('ob_get_level') && ob_get_level() > 0) {
        ob_end_clean();
    }
    echo '<!DOCTYPE html><html><head><title>Surat Digital - Error</title>';
    echo '<style>body{font-family:system-ui,sans-serif;background:#1a1d29;color:#e4e7ed;padding:40px;max-width:800px;margin:0 auto;}';
    echo 'h1{color:#ef4444;}pre{background:#232639;padding:16px;border-radius:8px;overflow:auto;font-size:13px;}</style></head><body>';
    echo '<h1>🔴 ' . get_class($e) . '</h1>';
    echo '<p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><strong>File:</strong> ' . $e->getFile() . ':' . $e->getLine() . '</p>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</body></html>';
}
