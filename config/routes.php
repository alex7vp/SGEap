<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\ConfigurationController;
use App\Controllers\CourseController;
use App\Controllers\GradeController;
use App\Controllers\MatriculationController;
use App\Controllers\PersonController;
use App\Controllers\SecurityController;
use App\Controllers\StudentController;
use App\Core\Router;

return static function (Router $router): void {
    $router->get('/', [AuthController::class, 'index']);
    $router->get('/login', [AuthController::class, 'index']);
    $router->post('/login', [AuthController::class, 'authenticate']);
    $router->get('/dashboard', [AuthController::class, 'dashboard']);
    $router->post('/logout', [AuthController::class, 'logout']);

    $router->get('/configuracion/catalogos', [ConfigurationController::class, 'catalogs']);
    $router->get('/configuracion/institucion', [ConfigurationController::class, 'institution']);
    $router->get('/configuracion/periodos', [ConfigurationController::class, 'periods']);
    $router->post('/configuracion/catalogos', [ConfigurationController::class, 'storeCatalogItem']);
    $router->post('/configuracion/institucion', [ConfigurationController::class, 'storeInstitution']);
    $router->post('/configuracion/catalogos/actualizar', [ConfigurationController::class, 'updateCatalogItem']);
    $router->post('/configuracion/catalogos/eliminar', [ConfigurationController::class, 'deleteCatalogItem']);
    $router->post('/configuracion/periodos', [ConfigurationController::class, 'storePeriod']);
    $router->post('/configuracion/periodos/actualizar', [ConfigurationController::class, 'updatePeriod']);
    $router->post('/configuracion/periodo-actual', [ConfigurationController::class, 'selectCurrentPeriod']);

    $router->get('/seguridad/catalogos', [SecurityController::class, 'catalogs']);
    $router->get('/seguridad/usuarios', [SecurityController::class, 'users']);
    $router->get('/seguridad/usuarios/buscar', [SecurityController::class, 'searchUsers']);
    $router->get('/seguridad/personas-disponibles/buscar', [SecurityController::class, 'searchAvailablePersons']);
    $router->post('/seguridad/catalogos', [SecurityController::class, 'storeCatalogItem']);
    $router->post('/seguridad/catalogos/actualizar', [SecurityController::class, 'updateCatalogItem']);
    $router->post('/seguridad/catalogos/eliminar', [SecurityController::class, 'deleteCatalogItem']);
    $router->get('/seguridad/roles-permisos', [SecurityController::class, 'rolePermissions']);
    $router->get('/seguridad/usuarios-roles/buscar', [SecurityController::class, 'searchUserRoles']);
    $router->post('/seguridad/usuarios', [SecurityController::class, 'storeUser']);
    $router->post('/seguridad/usuarios/estado', [SecurityController::class, 'toggleUserStatus']);
    $router->post('/seguridad/roles-permisos', [SecurityController::class, 'updateRolePermissions']);
    $router->post('/seguridad/usuarios-roles', [SecurityController::class, 'updateUserRoles']);

    $router->get('/personas', [PersonController::class, 'index']);
    $router->get('/grados', [GradeController::class, 'index']);
    $router->get('/matriculas', [MatriculationController::class, 'index']);
    $router->get('/cursos', [CourseController::class, 'index']);
    $router->get('/grados/crear', [GradeController::class, 'create']);
    $router->get('/grados/editar', [GradeController::class, 'edit']);
    $router->get('/personas/crear', [PersonController::class, 'create']);
    $router->get('/personas/editar', [PersonController::class, 'edit']);
    $router->get('/grados/buscar', [GradeController::class, 'search']);
    $router->get('/personas/buscar', [PersonController::class, 'search']);
    $router->post('/personas', [PersonController::class, 'store']);
    $router->post('/grados', [GradeController::class, 'store']);
    $router->post('/matriculas', [MatriculationController::class, 'store']);
    $router->post('/cursos', [CourseController::class, 'store']);
    $router->post('/cursos/estado', [CourseController::class, 'toggleStatus']);
    $router->post('/grados/actualizar', [GradeController::class, 'update']);
    $router->post('/grados/eliminar', [GradeController::class, 'destroy']);
    $router->post('/personas/actualizar', [PersonController::class, 'update']);
    $router->post('/personas/eliminar', [PersonController::class, 'destroy']);

    $router->get('/estudiantes', [StudentController::class, 'index']);
    $router->get('/estudiantes/crear', [StudentController::class, 'create']);
    $router->post('/estudiantes', [StudentController::class, 'store']);
};
