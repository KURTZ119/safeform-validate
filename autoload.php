<?php
/**
 * @author Ibnu Yasir
 * @verse 1.0
 * 
 * Alternate for composer class loader
 * you can remove it and change to official
 * autoload in ./vendor/autoload.php
 */
declare(strict_types=1);
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    spl_autoload_register(function (string $class): void {
        $prefix = 'SFORM\\FormValidator\\';
        $baseDir = __DIR__ . '/src/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relative = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relative) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    });
    require_once __DIR__ . '/src/helpers.php';
}