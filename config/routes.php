<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Core\Router;

return static function (Router $router): void {
    $router->get('/', [AuthController::class, 'index']);
    $router->get('/login', [AuthController::class, 'index']);
    $router->post('/login', [AuthController::class, 'authenticate']);
    $router->get('/dashboard', [AuthController::class, 'dashboard']);
    $router->post('/logout', [AuthController::class, 'logout']);
};
