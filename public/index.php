<?php

declare(strict_types=1);

use App\Core\Router;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/core/helpers.php';

$autoloadPath = BASE_PATH . '/vendor/autoload.php';

if (file_exists($autoloadPath)) {
    require $autoloadPath;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'App\\';

        if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
            return;
        }

        $relativeClass = substr($class, strlen($prefix));
        $parts = explode('\\', $relativeClass);
        $className = array_pop($parts);
        $directories = array_map('strtolower', $parts);
        $file = BASE_PATH . '/app/' . implode('/', $directories);

        if ($file !== BASE_PATH . '/app/') {
            $file .= '/';
        }

        $file .= $className . '.php';

        if (file_exists($file)) {
            require $file;
        }
    });
}

loadEnvironment(BASE_PATH . '/.env');

$appConfig = require BASE_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$router = new Router();
$routes = require BASE_PATH . '/config/routes.php';
$routes($router);
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', currentPath());
