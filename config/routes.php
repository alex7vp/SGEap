<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\PersonController;
use App\Controllers\StudentController;
use App\Core\Router;

return static function (Router $router): void {
    $router->get('/', [AuthController::class, 'index']);
    $router->get('/login', [AuthController::class, 'index']);
    $router->post('/login', [AuthController::class, 'authenticate']);
    $router->get('/dashboard', [AuthController::class, 'dashboard']);
    $router->post('/logout', [AuthController::class, 'logout']);

    $router->get('/personas', [PersonController::class, 'index']);
    $router->get('/personas/crear', [PersonController::class, 'create']);
    $router->get('/personas/editar', [PersonController::class, 'edit']);
    $router->get('/personas/buscar', [PersonController::class, 'search']);
    $router->post('/personas', [PersonController::class, 'store']);
    $router->post('/personas/actualizar', [PersonController::class, 'update']);
    $router->post('/personas/eliminar', [PersonController::class, 'destroy']);

    $router->get('/estudiantes', [StudentController::class, 'index']);
    $router->get('/estudiantes/crear', [StudentController::class, 'create']);
    $router->post('/estudiantes', [StudentController::class, 'store']);
};
