<?php
/**
 * Kobo Choice - Class Autoloader
 */

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

spl_autoload_register(function (string $class): void {
    $paths = [
        ROOT_PATH . '/models/',
        ROOT_PATH . '/controllers/',
        ROOT_PATH . '/api/',
    ];

    $file = $class . '.php';

    foreach ($paths as $path) {
        $fullPath = $path . $file;
        if (file_exists($fullPath)) {
            require_once $fullPath;
            return;
        }
    }
});
