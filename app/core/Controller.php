<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\RolePermissionModel;

abstract class Controller
{
    protected function requireAuth(): array
    {
        if (empty($_SESSION['auth'])) {
            sessionFlash('error', 'Debe iniciar sesion para continuar.');
            $this->redirect('/login');
        }

        $user = $_SESSION['auth'];

        $rolePermissionModel = new RolePermissionModel();
        $user['permissions'] = $rolePermissionModel->permissionCodesByUser((int) ($user['usuid'] ?? 0));
        $_SESSION['auth'] = $user;

        $requiredPermission = $this->permissionForCurrentPath(currentPath());

        if ($requiredPermission !== null && !$this->hasPermission($requiredPermission, $user)) {
            $this->denyAccess();
        }

        return $user;
    }

    protected function hasPermission(string $permission, ?array $user = null): bool
    {
        if (str_contains($permission, '|')) {
            return $this->hasAnyPermission(explode('|', $permission), $user);
        }

        $user ??= $_SESSION['auth'] ?? [];
        $permissions = (array) ($user['permissions'] ?? []);

        return in_array($permission, $permissions, true);
    }

    protected function hasAnyPermission(array $permissions, ?array $user = null): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission((string) $permission, $user)) {
                return true;
            }
        }

        return false;
    }

    private function permissionForCurrentPath(string $path): ?string
    {
        $path = '/' . trim($path, '/');
        $path = $path === '/' ? '/dashboard' : $path;

        $exact = [
            '/dashboard' => 'dashboard.ver',
            '/mi-matricula' => 'estudiante.mi_matricula',
            '/academico' => 'estudiantes.gestionar|personas.gestionar|matriculas.gestionar',
            '/configuracion' => 'configuracion.gestionar|catalogos.gestionar|cursos.gestionar|matriculas.documentos',
            '/reportes' => 'dashboard.ver',
            '/seguridad' => 'seguridad.usuarios|seguridad.roles_permisos',
            '/personas' => 'personas.gestionar',
            '/personal' => 'personas.gestionar',
            '/matriculas' => 'matriculas.gestionar',
            '/estudiantes' => 'estudiantes.gestionar',
            '/grados' => 'catalogos.gestionar',
            '/cursos' => 'cursos.gestionar',
            '/seguridad/catalogos' => 'seguridad.roles_permisos',
            '/seguridad/usuarios' => 'seguridad.usuarios',
            '/seguridad/usuarios/buscar' => 'seguridad.usuarios',
            '/seguridad/personas-disponibles/buscar' => 'seguridad.usuarios',
            '/seguridad/roles-permisos' => 'seguridad.roles_permisos',
            '/seguridad/usuarios-roles' => 'seguridad.roles_permisos',
            '/seguridad/usuarios-roles/buscar' => 'seguridad.roles_permisos',
            '/configuracion/periodo-actual' => 'configuracion.gestionar',
        ];

        if (isset($exact[$path])) {
            return $exact[$path];
        }

        $prefixes = [
            '/personas/' => 'personas.gestionar',
            '/mi-matricula/' => 'estudiante.mi_matricula',
            '/personal/' => 'personas.gestionar',
            '/matriculas/' => 'matriculas.gestionar',
            '/estudiantes/' => 'estudiantes.gestionar',
            '/grados/' => 'catalogos.gestionar',
            '/cursos/' => 'cursos.gestionar',
            '/configuracion/catalogos' => 'catalogos.gestionar',
            '/configuracion/institucion' => 'configuracion.gestionar',
            '/configuracion/periodos' => 'configuracion.gestionar',
            '/configuracion/matricula/documentos' => 'matriculas.documentos',
            '/configuracion/matricula' => 'configuracion.gestionar',
            '/seguridad/usuarios/clave' => 'seguridad.usuarios',
            '/seguridad/usuarios/estado' => 'seguridad.usuarios',
            '/seguridad/catalogos/' => 'seguridad.roles_permisos',
        ];

        foreach ($prefixes as $prefix => $permission) {
            if (str_starts_with($path, $prefix)) {
                return $permission;
            }
        }

        return null;
    }

    private function denyAccess(): void
    {
        http_response_code(403);
        $this->view('errors.forbidden', [
            'pageTitle' => 'Acceso restringido',
            'currentSection' => 'dashboard',
            'user' => $_SESSION['auth'] ?? [],
            'requestedPath' => currentPath(),
        ]);
        exit;
    }

    protected function view(string $view, array $data = []): void
    {
        $viewPath = BASE_PATH . '/app/views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            http_response_code(500);
            echo 'La vista solicitada no existe.';
            return;
        }

        extract($data, EXTR_SKIP);
        require $viewPath;
    }

    protected function redirect(string $path): void
    {
        redirect($path);
    }
}
