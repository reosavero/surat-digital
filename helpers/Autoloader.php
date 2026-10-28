<?php

/**
 * Autoloader Helper - Surat Digital
 */

spl_autoload_register(function ($className) {
    $paths = [
        CONTROLLERS_PATH . $className . '.php',
        MODELS_PATH . $className . '.php',
        HELPERS_PATH . $className . '.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});
