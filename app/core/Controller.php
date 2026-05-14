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
            '/matricula-temporal' => 'matricula_temporal.ver|representante.matricula_nueva|representante.estudiantes',
            '/matricula-temporal/persona' => 'matricula_temporal.editar|representante.matricula_nueva',
            '/mi-matricula' => 'estudiante.mi_matricula',
            '/representante/estudiante' => 'representante.estudiantes',
            '/representante/estudiante/modulo' => 'representante.estudiantes',
            '/academico' => 'estudiantes.gestionar|personas.gestionar|matriculas.gestionar|asistencia.calendario.gestionar|asistencia.registrar|asistencia.supervisar|justificaciones.gestionar|asistencia.ver_propia|asistencia.representante.ver|novedades.registrar|novedades.supervisar|novedades.ver_propia|novedades.representante.ver',
            '/asistencia' => 'asistencia.calendario.gestionar|asistencia.registrar|asistencia.supervisar|justificaciones.gestionar|asistencia.ver_propia|asistencia.representante.ver|novedades.registrar|novedades.supervisar|novedades.ver_propia|novedades.representante.ver',
            '/novedades' => 'novedades.registrar|novedades.supervisar|novedades.ver_propia|novedades.representante.ver',
            '/novedades/registro' => 'novedades.registrar|novedades.supervisar',
            '/novedades/supervision' => 'novedades.supervisar',
            '/novedades/anular' => 'novedades.supervisar',
            '/novedades/mis-novedades' => 'novedades.ver_propia',
            '/novedades/representante' => 'novedades.representante.ver',
            '/configuracion' => 'configuracion.gestionar|catalogos.gestionar|cursos.gestionar|matriculas.documentos|asistencia.calendario.gestionar',
            '/configuracion/academica' => 'configuracion.gestionar|catalogos.gestionar|cursos.gestionar|matriculas.documentos|asistencia.calendario.gestionar',
            '/reportes' => 'dashboard.ver',
            '/reportes/asistencia' => 'asistencia.supervisar',
            '/seguridad' => 'seguridad.usuarios|seguridad.roles_permisos|usuarios_temporales.gestionar',
            '/personas' => 'personas.gestionar',
            '/personal' => 'personas.gestionar',
            '/matriculas' => 'matriculas.gestionar',
            '/estudiantes' => 'estudiantes.gestionar',
            '/grados' => 'catalogos.gestionar',
            '/cursos' => 'cursos.gestionar',
            '/asistencia/configuracion' => 'asistencia.calendario.gestionar',
            '/asistencia/calendario' => 'asistencia.calendario.gestionar',
            '/asistencia/justificaciones' => 'justificaciones.gestionar',
            '/asistencia/justificaciones/revisar' => 'justificaciones.gestionar',
            '/asistencia/justificaciones/confirmar' => 'justificaciones.gestionar',
            '/asistencia/justificaciones/anular' => 'justificaciones.gestionar',
            '/asistencia/supervision' => 'asistencia.supervisar',
            '/asistencia/mi-asistencia' => 'asistencia.ver_propia|novedades.ver_propia',
            '/asistencia/representante' => 'asistencia.representante.ver|novedades.representante.ver',
            '/asistencia/registro' => 'asistencia.registrar',
            '/asistencia/sesiones' => 'asistencia.registrar',
            '/asistencia/sesiones/anular' => 'asistencia.supervisar',
            '/asistencia/sesiones/cerrar' => 'asistencia.registrar',
            '/asistencia/registros' => 'asistencia.registrar',
            '/seguridad/catalogos' => 'seguridad.roles_permisos',
            '/seguridad/usuarios' => 'seguridad.usuarios',
            '/seguridad/usuarios-temporales' => 'usuarios_temporales.gestionar',
            '/seguridad/representantes/matricula-nueva' => 'usuarios_temporales.gestionar',
            '/seguridad/representantes/matricula-nueva/anular' => 'usuarios_temporales.gestionar',
            '/seguridad/representantes/matricula-nueva/buscar' => 'usuarios_temporales.gestionar',
            '/seguridad/usuarios/buscar' => 'seguridad.usuarios',
            '/seguridad/personas-disponibles/buscar' => 'seguridad.usuarios|usuarios_temporales.gestionar',
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
            '/matricula-temporal/' => 'matricula_temporal.editar|representante.matricula_nueva|representante.estudiantes',
            '/mi-matricula/' => 'estudiante.mi_matricula',
            '/representante/estudiante/' => 'representante.estudiantes',
            '/personal/' => 'personas.gestionar',
            '/matriculas/' => 'matriculas.gestionar',
            '/estudiantes/' => 'estudiantes.gestionar',
            '/grados/' => 'catalogos.gestionar',
            '/cursos/' => 'cursos.gestionar',
            '/asistencia/' => 'asistencia.calendario.gestionar',
            '/novedades/' => 'novedades.registrar|novedades.supervisar|novedades.ver_propia|novedades.representante.ver',
            '/configuracion/catalogos' => 'catalogos.gestionar',
            '/configuracion/institucion' => 'configuracion.gestionar',
            '/configuracion/periodos' => 'configuracion.gestionar',
            '/configuracion/academica/' => 'asistencia.calendario.gestionar',
            '/configuracion/matricula/documentos' => 'matriculas.documentos',
            '/configuracion/matricula' => 'configuracion.gestionar',
            '/seguridad/usuarios/clave' => 'seguridad.usuarios',
            '/seguridad/usuarios/estado' => 'seguridad.usuarios',
            '/seguridad/usuarios-temporales/' => 'usuarios_temporales.gestionar',
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
