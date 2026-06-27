<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\RolePermissionModel;
use App\Models\UserModel;

abstract class Controller
{
    protected const MATRICULATION_MANAGEMENT_ROLE_NAMES = [
        'Administrador',
        'Rector',
        'Vicerrector',
        'Coordinador',
        'Inspector',
        'Secretaria',
    ];

    protected function requireAuth(): array
    {
        if (empty($_SESSION['auth'])) {
            sessionFlash('error', 'Debe iniciar sesion para continuar.');
            $this->redirect('/login');
        }

        if (authenticatedSessionExpired()) {
            expireAuthenticatedSession();
            sessionFlash('error', 'La sesion expiro por inactividad. Inicie sesion nuevamente.');
            $this->redirect('/login');
        }

        refreshAuthenticatedSessionActivity();

        $user = $_SESSION['auth'];

        $rolePermissionModel = new RolePermissionModel();
        $user['permissions'] = $rolePermissionModel->permissionCodesByUser((int) ($user['usuid'] ?? 0));
        $_SESSION['auth'] = $user;

        $currentPath = currentPath();
        $requiredPermission = $this->permissionForCurrentPath($currentPath);

        if ($requiredPermission !== null && !$this->hasPermission($requiredPermission, $user)) {
            $this->denyAccess();
        }

        if ($this->isAdministrativeMatriculationPath($currentPath)
            && !$this->userHasAnyRoleName($user, self::MATRICULATION_MANAGEMENT_ROLE_NAMES)
        ) {
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

    protected function userHasAnyRoleName(array $user, array $roleNames): bool
    {
        return (new UserModel())->hasAnyRoleName((int) ($user['usuid'] ?? 0), $roleNames);
    }

    private function isAdministrativeMatriculationPath(string $path): bool
    {
        $path = '/' . trim($path, '/');

        if ($path === '/matriculas') {
            return true;
        }

        if (in_array($path, ['/matriculas/ficha', '/matriculas/certificado'], true)) {
            return false;
        }

        return str_starts_with($path, '/matriculas/');
    }

    private function permissionForCurrentPath(string $path): ?string
    {
        $path = '/' . trim($path, '/');
        $path = $path === '/' ? '/dashboard' : $path;

        $exact = [
            '/dashboard' => 'dashboard.ver|asistencia.registrar|novedades.registrar|calificaciones.registrar|calificaciones.editar',
            '/matricula-temporal' => 'matricula_temporal.ver|representante.matricula_nueva|representante.estudiantes',
            '/matricula-temporal/persona' => 'matricula_temporal.editar|representante.matricula_nueva',
            '/mi-matricula' => 'estudiante.mi_matricula',
            '/representante/estudiante' => 'representante.estudiantes',
            '/representante/estudiante/modulo' => 'representante.estudiantes',
            '/representante/contabilidad' => 'contabilidad.representante.obligaciones.ver|contabilidad.representante.pagos.ver|contabilidad.representante.comprobantes.subir|contabilidad.representante.rubros.ver',
            '/academico' => 'estudiantes.gestionar|personas.gestionar|matriculas.gestionar|asistencia.calendario.gestionar|asistencia.registrar|asistencia.supervisar|justificaciones.gestionar|asistencia.ver_propia|asistencia.representante.ver|novedades.registrar|novedades.supervisar|novedades.ver_propia|novedades.representante.ver|calificaciones.registrar|calificaciones.editar|calificaciones.configurar|calificaciones.validar|calificaciones.publicar|calificaciones.auditoria.ver',
            '/docente/cursos' => 'asistencia.registrar|novedades.registrar|calificaciones.registrar|calificaciones.editar',
            '/docente/curso' => 'asistencia.registrar|novedades.registrar|calificaciones.registrar|calificaciones.editar',
            '/docente/curso/lista' => 'asistencia.registrar|novedades.registrar|calificaciones.registrar|calificaciones.editar',
            '/calificaciones/registro' => 'asistencia.registrar|calificaciones.registrar|calificaciones.editar|calificaciones.configurar|calificaciones.validar|calificaciones.publicar|calificaciones.auditoria.ver',
            '/calificaciones/actividad' => 'asistencia.registrar|calificaciones.registrar|calificaciones.editar|calificaciones.configurar',
            '/calificaciones/notas' => 'asistencia.registrar|calificaciones.registrar|calificaciones.editar|calificaciones.configurar',
            '/calificaciones/habilitar-subperiodo' => 'calificaciones.configurar|calificaciones.validar|calificaciones.publicar|calificaciones.editar',
            '/asistencia' => 'asistencia.calendario.gestionar|asistencia.registrar|asistencia.supervisar|justificaciones.gestionar|asistencia.ver_propia|asistencia.representante.ver|novedades.registrar|novedades.supervisar|novedades.ver_propia|novedades.representante.ver',
            '/novedades' => 'novedades.registrar|novedades.supervisar|novedades.ver_propia|novedades.representante.ver',
            '/novedades/registro' => 'novedades.registrar|novedades.supervisar',
            '/novedades/supervision' => 'novedades.supervisar',
            '/novedades/anular' => 'novedades.supervisar',
            '/novedades/mis-novedades' => 'novedades.ver_propia',
            '/novedades/representante' => 'novedades.representante.ver',
            '/comunicados' => 'comunicados.gestionar',
            '/comunicados/nuevo' => 'comunicados.gestionar',
            '/comunicados/editar' => 'comunicados.gestionar',
            '/comunicados/ver' => 'comunicados.gestionar',
            '/comunicados/buscar-estudiantes' => 'comunicados.gestionar',
            '/comunicados/buscar-representantes' => 'comunicados.gestionar',
            '/comunicados/buscar-personal' => 'comunicados.gestionar',
            '/comunicados/anular' => 'comunicados.gestionar',
            '/comunicados/eliminar' => 'comunicados.gestionar',
            '/mis-comunicados' => 'comunicados.ver_propios|comunicados.gestionar',
            '/mis-comunicados/leer' => 'comunicados.ver_propios|comunicados.gestionar',
            '/configuracion' => 'configuracion.gestionar|catalogos.gestionar|cursos.gestionar|matriculas.documentos|asistencia.calendario.gestionar|calificaciones.configurar|calificaciones.plantillas.gestionar',
            '/configuracion/academica' => 'configuracion.gestionar|catalogos.gestionar|cursos.gestionar|matriculas.documentos|asistencia.calendario.gestionar|calificaciones.configurar|calificaciones.plantillas.gestionar',
            '/configuracion/contable' => 'contabilidad.configurar',
            '/configuracion/contable/servicios' => 'contabilidad.configurar',
            '/configuracion/backups' => 'backups.gestionar|configuracion.gestionar',
            '/configuracion/backups/descargar' => 'backups.gestionar|configuracion.gestionar',
            '/configuracion/backups/eliminar' => 'backups.gestionar|configuracion.gestionar',
            '/reportes' => 'dashboard.ver|asistencia.supervisar|calificaciones.validar|calificaciones.configurar|calificaciones.registrar|calificaciones.editar|calificaciones.publicar',
            '/reportes/asistencia' => 'asistencia.supervisar',
            '/reportes/libreta' => 'calificaciones.validar|calificaciones.configurar|calificaciones.registrar|calificaciones.editar|calificaciones.publicar',
            '/reportes/cuadro-final' => 'calificaciones.validar|calificaciones.configurar|calificaciones.registrar|calificaciones.editar',
            '/contabilidad' => 'contabilidad.ver',
            '/contabilidad/exportar' => 'contabilidad.reportes.exportar',
            '/contabilidad/reportes' => 'contabilidad.reportes.ver|contabilidad.reportes.exportar',
            '/contabilidad/obligaciones' => 'contabilidad.obligaciones.ver',
            '/contabilidad/obligaciones/detalle' => 'contabilidad.obligaciones.ver',
            '/contabilidad/obligaciones/generar' => 'contabilidad.obligaciones.generar',
            '/contabilidad/obligaciones/actualizar' => 'contabilidad.obligaciones.editar',
            '/contabilidad/obligaciones/anular' => 'contabilidad.obligaciones.editar',
            '/contabilidad/comprobantes' => 'contabilidad.comprobantes.revisar',
            '/contabilidad/comprobantes/aprobar' => 'contabilidad.comprobantes.aprobar',
            '/contabilidad/comprobantes/rechazar' => 'contabilidad.comprobantes.rechazar',
            '/contabilidad/pagos/reversar' => 'contabilidad.pagos.reversar',
            '/contabilidad/rubros' => 'contabilidad.rubros.ver|contabilidad.rubros.crear|contabilidad.rubros.editar',
            '/contabilidad/rubros/cerrar' => 'contabilidad.rubros.editar|contabilidad.pagos.registrar',
            '/contabilidad/rubros/conceptos' => 'contabilidad.rubros.crear|contabilidad.rubros.editar',
            '/contabilidad/rubros/conceptos/actualizar' => 'contabilidad.rubros.editar',
            '/contabilidad/rubros/conceptos/eliminar' => 'contabilidad.rubros.editar',
            '/contabilidad/auditoria' => 'contabilidad.auditoria.ver',
            '/representante/contabilidad/comprobante' => 'contabilidad.representante.comprobantes.subir',
            '/seguridad' => 'seguridad.usuarios|seguridad.roles_permisos|usuarios_temporales.gestionar',
            '/personas' => 'personas.gestionar',
            '/personal' => 'personas.gestionar',
            '/matriculas' => 'matriculas.gestionar',
            '/matriculas/ficha' => 'matriculas.gestionar|representante.estudiantes',
            '/matriculas/certificado' => 'matriculas.gestionar|representante.estudiantes',
            '/matriculas/reporte/pdf' => 'matriculas.gestionar',
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
            '/seguridad/roles-permisos/buscar' => 'seguridad.roles_permisos',
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
            '/representante/contabilidad/' => 'contabilidad.representante.obligaciones.ver|contabilidad.representante.pagos.ver|contabilidad.representante.comprobantes.subir',
            '/personal/' => 'personas.gestionar',
            '/matriculas/' => 'matriculas.gestionar',
            '/estudiantes/' => 'estudiantes.gestionar',
            '/grados/' => 'catalogos.gestionar',
            '/cursos/' => 'cursos.gestionar',
            '/asistencia/' => 'asistencia.calendario.gestionar',
            '/novedades/' => 'novedades.registrar|novedades.supervisar|novedades.ver_propia|novedades.representante.ver',
            '/comunicados/' => 'comunicados.gestionar',
            '/mis-comunicados/' => 'comunicados.ver_propios|comunicados.gestionar',
            '/configuracion/catalogos' => 'catalogos.gestionar',
            '/configuracion/institucion' => 'configuracion.gestionar',
            '/configuracion/periodos' => 'configuracion.gestionar',
            '/configuracion/academica/calificaciones' => 'calificaciones.configurar|calificaciones.plantillas.gestionar',
            '/configuracion/academica/calificaciones/' => 'calificaciones.configurar|calificaciones.plantillas.gestionar',
            '/configuracion/academica/' => 'asistencia.calendario.gestionar',
            '/configuracion/matricula/documentos' => 'matriculas.documentos',
            '/configuracion/matricula' => 'configuracion.gestionar',
            '/configuracion/contable/' => 'contabilidad.configurar',
            '/configuracion/backups/' => 'backups.gestionar|configuracion.gestionar',
            '/contabilidad/obligaciones/' => 'contabilidad.obligaciones.ver|contabilidad.obligaciones.generar|contabilidad.obligaciones.editar',
            '/contabilidad/comprobantes/' => 'contabilidad.comprobantes.revisar|contabilidad.comprobantes.aprobar|contabilidad.comprobantes.rechazar',
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

    protected function denyAccess(): void
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
