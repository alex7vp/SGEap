<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

class ModuleController extends Controller
{
    public function academic(): void
    {
        $this->renderModuleHome(
            'academico',
            'academico_home',
            'Gestion academica',
            'Centraliza los procesos operativos del ciclo estudiantil y la gestion diaria del area academica.',
            [
                [
                    'label' => 'Estudiantes',
                    'description' => 'Administra los registros estudiantiles vinculados a persona.',
                    'url' => baseUrl('estudiantes'),
                    'icon' => 'fa-graduation-cap',
                    'permission' => 'estudiantes.gestionar',
                ],
                [
                    'label' => 'Personal',
                    'description' => 'Centraliza el registro, la asignacion de tipos y la consulta del personal institucional.',
                    'url' => baseUrl('personal'),
                    'icon' => 'fa-id-badge',
                    'permission' => 'personas.gestionar',
                ],
                [
                    'label' => 'Matriculas',
                    'description' => 'Gestiona nuevas matriculas y el seguimiento del proceso por periodo.',
                    'url' => baseUrl('matriculas'),
                    'icon' => 'fa-address-card',
                    'permission' => 'matriculas.gestionar',
                ],
                [
                    'label' => 'Asistencia',
                    'description' => 'Centraliza configuracion, calendario, registro, supervision, justificaciones y reportes.',
                    'url' => baseUrl('asistencia'),
                    'icon' => 'fa-calendar-check-o',
                    'permission' => 'asistencia.calendario.gestionar|asistencia.registrar|asistencia.supervisar|justificaciones.gestionar|asistencia.ver_propia|asistencia.representante.ver',
                ],
            ]
        );
    }

    public function attendance(): void
    {
        $this->renderModuleHome(
            'academico',
            'asistencia_home',
            'Asistencia',
            'Agrupa la configuracion operativa, calendario, registro, supervision, justificaciones y consultas de asistencia.',
            [
                [
                    'label' => 'Configuracion de asistencia',
                    'description' => 'Define el rango real de inicio y fin de clases usado por el calendario.',
                    'url' => baseUrl('asistencia/configuracion'),
                    'icon' => 'fa-calendar-check-o',
                    'permission' => 'asistencia.calendario.gestionar',
                ],
                [
                    'label' => 'Calendario de asistencia',
                    'description' => 'Define jornadas normales, reducidas, suspendidas o especiales por fecha.',
                    'url' => baseUrl('asistencia/calendario'),
                    'icon' => 'fa-calendar',
                    'permission' => 'asistencia.calendario.gestionar',
                ],
                [
                    'label' => 'Justificaciones',
                    'description' => 'Registra, aprueba, rechaza y anula justificaciones de asistencia.',
                    'url' => baseUrl('asistencia/justificaciones'),
                    'icon' => 'fa-file-text-o',
                    'permission' => 'justificaciones.gestionar',
                ],
                [
                    'label' => 'Supervision de asistencia',
                    'description' => 'Revisa sesiones registradas, detalle por estudiante y anulaciones con motivo.',
                    'url' => baseUrl('asistencia/supervision'),
                    'icon' => 'fa-search',
                    'permission' => 'asistencia.supervisar',
                ],
                [
                    'label' => 'Registrar asistencia',
                    'description' => 'Registra asistencia por materia asignada y hora de clase.',
                    'url' => baseUrl('asistencia/registro'),
                    'icon' => 'fa-check-square-o',
                    'permission' => 'asistencia.registrar',
                ],
                [
                    'label' => 'Reporte de asistencia',
                    'description' => 'Consolida asistencias, atrasos y faltas por rango, curso o estudiante.',
                    'url' => baseUrl('reportes/asistencia'),
                    'icon' => 'fa-bar-chart',
                    'permission' => 'asistencia.supervisar',
                ],
                [
                    'label' => 'Mi asistencia',
                    'description' => 'Consulta el resumen mensual y el detalle de asistencia del estudiante.',
                    'url' => baseUrl('asistencia/mi-asistencia'),
                    'icon' => 'fa-calendar-check-o',
                    'permission' => 'asistencia.ver_propia',
                ],
                [
                    'label' => 'Asistencia representados',
                    'description' => 'Consulta asistencia de los estudiantes vinculados al representante.',
                    'url' => baseUrl('asistencia/representante'),
                    'icon' => 'fa-calendar-o',
                    'permission' => 'asistencia.representante.ver',
                ],
            ]
        );
    }

    public function configuration(): void
    {
        $this->renderModuleHome(
            'configuracion',
            'configuracion_home',
            'Configuracion',
            'Agrupa los catalogos, parametros institucionales y ventanas operativas que gobiernan el sistema.',
            [
                [
                    'label' => 'Catalogos base',
                    'description' => 'Administra catalogos generales reutilizados por todo el sistema.',
                    'url' => baseUrl('configuracion/catalogos'),
                    'icon' => 'fa-list-alt',
                    'permission' => 'catalogos.gestionar',
                ],
                [
                    'label' => 'Datos institucionales',
                    'description' => 'Actualiza la informacion principal de la institucion.',
                    'url' => baseUrl('configuracion/institucion'),
                    'icon' => 'fa-university',
                    'permission' => 'configuracion.gestionar',
                ],
                [
                    'label' => 'Configuracion academica',
                    'description' => 'Agrupa periodos, grados, cursos, matricula, materias, docentes y parametros academicos.',
                    'url' => baseUrl('configuracion/academica'),
                    'icon' => 'fa-sitemap',
                    'permission' => 'configuracion.gestionar|catalogos.gestionar|cursos.gestionar|matriculas.documentos|asistencia.calendario.gestionar',
                ],
            ]
        );
    }

    public function academicConfiguration(): void
    {
        $this->renderModuleHome(
            'configuracion',
            'configuracion_academica',
            'Configuracion academica',
            'Centraliza las configuraciones que intervienen en los procesos academicos, de matricula y asistencia.',
            [
                [
                    'label' => 'Periodos lectivos',
                    'description' => 'Define periodos, vigencias y el periodo activo oficial.',
                    'url' => baseUrl('configuracion/periodos'),
                    'icon' => 'fa-calendar',
                    'permission' => 'configuracion.gestionar',
                ],
                [
                    'label' => 'Configuracion de matricula',
                    'description' => 'Abre o cierra la matricula ordinaria y extraordinaria por periodo.',
                    'url' => baseUrl('configuracion/matricula'),
                    'icon' => 'fa-wpforms',
                    'permission' => 'configuracion.gestionar',
                ],
                [
                    'label' => 'Grados',
                    'description' => 'Gestiona la estructura de grados usada por cursos y matriculas.',
                    'url' => baseUrl('grados'),
                    'icon' => 'fa-sitemap',
                    'permission' => 'catalogos.gestionar',
                ],
                [
                    'label' => 'Cursos por periodo',
                    'description' => 'Relaciona niveles, grados y paralelos dentro de cada periodo.',
                    'url' => baseUrl('cursos'),
                    'icon' => 'fa-book',
                    'permission' => 'cursos.gestionar',
                ],
                [
                    'label' => 'Areas academicas',
                    'description' => 'Gestiona las areas usadas para organizar las asignaturas institucionales.',
                    'url' => null,
                    'icon' => 'fa-folder-open',
                    'permission' => 'asistencia.calendario.gestionar',
                ],
                [
                    'label' => 'Asignaturas',
                    'description' => 'Administra las materias base por area academica.',
                    'url' => null,
                    'icon' => 'fa-bookmark',
                    'permission' => 'asistencia.calendario.gestionar',
                ],
                [
                    'label' => 'Materias por curso',
                    'description' => 'Relaciona asignaturas con los cursos activos del periodo.',
                    'url' => null,
                    'icon' => 'fa-list-ol',
                    'permission' => 'asistencia.calendario.gestionar',
                ],
                [
                    'label' => 'Asignacion de docentes',
                    'description' => 'Vincula docentes con las materias que dictan en cada curso.',
                    'url' => null,
                    'icon' => 'fa-user-plus',
                    'permission' => 'asistencia.calendario.gestionar',
                ],
                [
                    'label' => 'Configuracion de asistencia',
                    'description' => 'Define el rango real de clases usado por el calendario de asistencia.',
                    'url' => baseUrl('asistencia/configuracion'),
                    'icon' => 'fa-calendar-check-o',
                    'permission' => 'asistencia.calendario.gestionar',
                ],
            ]
        );
    }

    public function reports(): void
    {
        $this->renderModuleHome(
            'reportes',
            'reportes_home',
            'Reportes',
            'Consolida salidas de informacion y consultas ejecutivas.',
            [
                [
                    'label' => 'Reporte de asistencia',
                    'description' => 'Consolida asistencias, atrasos y faltas por rango, curso o estudiante.',
                    'url' => baseUrl('reportes/asistencia'),
                    'icon' => 'fa-calendar-check-o',
                    'permission' => 'asistencia.supervisar',
                ],
            ]
        );
    }

    public function security(): void
    {
        $this->renderModuleHome(
            'seguridad',
            'seguridad_home',
            'Seguridad',
            'Centraliza catalogos de seguridad, usuarios, roles y asignacion de permisos.',
            [
                [
                    'label' => 'Catalogos',
                    'description' => 'Administra roles, permisos y catalogos base de seguridad.',
                    'url' => baseUrl('seguridad/catalogos'),
                    'icon' => 'fa-tags',
                    'permission' => 'seguridad.roles_permisos',
                ],
                [
                    'label' => 'Usuarios',
                    'description' => 'Gestiona usuarios del sistema y su estado de acceso.',
                    'url' => baseUrl('seguridad/usuarios'),
                    'icon' => 'fa-user',
                    'permission' => 'seguridad.usuarios',
                ],
                [
                    'label' => 'Usuarios temporales',
                    'description' => 'Crea y controla accesos temporales para representantes de alumnos nuevos.',
                    'url' => baseUrl('seguridad/usuarios-temporales'),
                    'icon' => 'fa-clock-o',
                    'permission' => 'usuarios_temporales.gestionar',
                ],
                [
                    'label' => 'Designacion de permisos',
                    'description' => 'Define permisos funcionales para cada rol del sistema.',
                    'url' => baseUrl('seguridad/roles-permisos'),
                    'icon' => 'fa-key',
                    'permission' => 'seguridad.roles_permisos',
                ],
                [
                    'label' => 'Roles por usuario',
                    'description' => 'Asigna uno o varios roles a cada cuenta de usuario.',
                    'url' => baseUrl('seguridad/usuarios-roles'),
                    'icon' => 'fa-users',
                    'permission' => 'seguridad.roles_permisos',
                ],
                [
                    'label' => 'Auditoria',
                    'description' => 'Espacio reservado para trazabilidad y bitacora del sistema.',
                    'url' => null,
                    'icon' => 'fa-search',
                ],
            ]
        );
    }

    private function renderModuleHome(
        string $module,
        string $section,
        string $title,
        string $description,
        array $cards
    ): void {
        $user = $this->requireAuth();
        $cards = array_values(array_filter(
            $cards,
            fn (array $card): bool => empty($card['permission']) || $this->hasPermission((string) $card['permission'], $user)
        ));

        $this->view('module.home', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => $title,
            'currentModule' => $module,
            'currentSection' => $section,
            'user' => $user,
            'moduleDescription' => $description,
            'moduleCards' => $cards,
        ]);
    }
}
