<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AttendanceModel;
use App\Models\CourseModel;
use App\Models\GradeModel;

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
                    'label' => 'Novedades y asistencia',
                    'description' => 'Centraliza registro, supervision, justificaciones, reportes y novedades.',
                    'url' => baseUrl('asistencia'),
                    'icon' => 'fa-calendar-check-o',
                    'permission' => 'asistencia.registrar|asistencia.supervisar|justificaciones.gestionar|asistencia.ver_propia|asistencia.representante.ver|novedades.registrar|novedades.supervisar|novedades.ver_propia|novedades.representante.ver',
                ],
                [
                    'label' => 'Calificaciones',
                    'description' => 'Permite registrar o consultar notas por curso, materia, subperiodo y componente.',
                    'url' => baseUrl('calificaciones/registro'),
                    'icon' => 'fa-check-square',
                    'permission' => 'asistencia.registrar|calificaciones.registrar|calificaciones.editar|calificaciones.configurar|calificaciones.validar|calificaciones.publicar|calificaciones.auditoria.ver',
                ],
                [
                    'label' => 'Reportes',
                    'description' => 'Agrupa reportes academicos de asistencia, calificaciones y novedades.',
                    'url' => baseUrl('reportes'),
                    'icon' => 'fa-bar-chart',
                    'permission' => 'asistencia.supervisar|calificaciones.validar|calificaciones.configurar|calificaciones.registrar|calificaciones.editar|calificaciones.publicar',
                ],
            ]
        );
    }

    public function attendance(): void
    {
        $this->renderModuleHome(
            'academico',
            'asistencia_home',
            'Novedades y asistencia',
            'Agrupa registro, supervision, justificaciones, reportes y consultas de novedades y asistencia.',
            [
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
                    'label' => 'Supervision de novedades',
                    'description' => 'Consulta y anula novedades registradas durante la jornada.',
                    'url' => baseUrl('novedades/supervision'),
                    'icon' => 'fa-exclamation-circle',
                    'permission' => 'novedades.supervisar',
                ],
                [
                    'label' => 'Registro de asistencia y novedades',
                    'description' => 'Registra asistencia por materia asignada y novedades del estudiante desde un solo calendario.',
                    'url' => baseUrl('asistencia/registro'),
                    'icon' => 'fa-check-square-o',
                    'permission' => 'asistencia.registrar',
                ],
                [
                    'label' => 'Mi asistencia y novedades',
                    'description' => 'Consulta el resumen mensual y el detalle de asistencia y novedades del estudiante.',
                    'url' => baseUrl('asistencia/mi-asistencia'),
                    'icon' => 'fa-calendar-check-o',
                    'permission' => 'asistencia.ver_propia|novedades.ver_propia',
                ],
                [
                    'label' => 'Asistencia y novedades representados',
                    'description' => 'Consulta asistencia y novedades de los estudiantes vinculados al representante.',
                    'url' => baseUrl('asistencia/representante'),
                    'icon' => 'fa-calendar-o',
                    'permission' => 'asistencia.representante.ver|novedades.representante.ver',
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
                [
                    'label' => 'Configuracion contable',
                    'description' => 'Define valores oficiales, alcances, meses de pension y reglas base para Gestion Contable.',
                    'url' => baseUrl('configuracion/contable'),
                    'icon' => 'fa-usd',
                    'permission' => 'contabilidad.configurar',
                ],
            ]
        );
    }

    public function academicConfiguration(): void
    {
        $user = $this->requireAuth();
        $gradeModel = new GradeModel();
        $courseModel = new CourseModel();
        $attendanceModel = new AttendanceModel();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) $period['pleid'] : 0;
        $grades = $gradeModel->allOrdered();
        $courses = $periodId > 0 ? $courseModel->allByPeriod($periodId) : [];
        $areas = $attendanceModel->areas();
        $subjects = $attendanceModel->subjects();
        $courseSubjects = $periodId > 0 ? $attendanceModel->courseSubjectsByPeriod($periodId) : [];
        $activeCourseSubjects = array_values(array_filter(
            $courseSubjects,
            static fn (array $subject): bool => !empty($subject['mtcestado'])
        ));
        $views = array_values(array_filter([
            [
                'key' => 'areas',
                'label' => 'Areas academicas',
                'permission' => 'asistencia.calendario.gestionar',
            ],
            [
                'key' => 'asignaturas',
                'label' => 'Asignaturas',
                'permission' => 'asistencia.calendario.gestionar',
            ],
            [
                'key' => 'grados',
                'label' => 'Grados',
                'permission' => 'catalogos.gestionar',
            ],
            [
                'key' => 'cursos',
                'label' => 'Cursos',
                'permission' => 'cursos.gestionar',
            ],
            [
                'key' => 'materias',
                'label' => 'Materias por curso',
                'permission' => 'asistencia.calendario.gestionar',
            ],
            [
                'key' => 'docentes',
                'label' => 'Asignacion de docentes',
                'permission' => 'asistencia.calendario.gestionar',
            ],
        ], fn (array $view): bool => $this->hasPermission((string) $view['permission'], $user)));
        $selectedView = (string) ($_GET['view'] ?? ($views[0]['key'] ?? ''));

        if (!in_array($selectedView, array_column($views, 'key'), true)) {
            $selectedView = (string) ($views[0]['key'] ?? '');
        }

        $this->view('configuracion.academica', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Configuracion academica',
            'currentModule' => 'configuracion',
            'currentSection' => 'configuracion_academica',
            'user' => $user,
            'academicViews' => $views,
            'selectedAcademicView' => $selectedView,
            'currentPeriod' => $period,
            'grades' => $grades,
            'levels' => $gradeModel->allLevels(),
            'parallels' => $courseModel->allParallels(),
            'courses' => $courses,
            'activeCourses' => array_values(array_filter($courses, static fn (array $course): bool => !empty($course['curestado']))),
            'areas' => $areas,
            'activeAreas' => $attendanceModel->activeAreas(),
            'subjects' => $subjects,
            'activeSubjects' => $attendanceModel->activeSubjects(),
            'courseSubjects' => $courseSubjects,
            'activeCourseSubjects' => $activeCourseSubjects,
            'teachers' => $attendanceModel->activeTeachers(),
            'teacherAssignments' => $periodId > 0 ? $attendanceModel->activeTeacherAssignmentsByCourseSubject($periodId) : [],
            'gradeFormFeedback' => $this->gradeFormFeedback(),
            'gradeListFeedback' => $this->gradeListFeedback(),
            'courseListFeedback' => $this->courseListFeedback(),
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
            'gradeOld' => [
                'graid' => '',
                'nedid' => sessionFlash('old_nedid') ?? '',
                'granombre' => sessionFlash('old_granombre') ?? '',
            ],
            'old' => [
                'graid' => sessionFlash('old_course_grade') ?? '',
                'prlid' => sessionFlash('old_course_parallel') ?? '',
                'curestado' => sessionFlash('old_course_status') ?? '1',
            ],
        ]);
    }

    private function gradeFormFeedback(): ?array
    {
        $type = sessionFlash('grade_form_feedback_type');
        $message = sessionFlash('grade_form_feedback_message');

        if ($type === null || $message === null) {
            return null;
        }

        return [
            'type' => $type,
            'message' => $message,
        ];
    }

    private function gradeListFeedback(): ?array
    {
        $type = sessionFlash('grade_list_feedback_type');
        $message = sessionFlash('grade_list_feedback_message');

        if ($type === null || $message === null) {
            return null;
        }

        return [
            'type' => $type,
            'message' => $message,
        ];
    }

    private function courseListFeedback(): ?array
    {
        $type = sessionFlash('course_list_feedback_type');
        $message = sessionFlash('course_list_feedback_message');

        if ($type === null || $message === null) {
            return null;
        }

        return [
            'type' => $type,
            'message' => $message,
        ];
    }

    public function reports(): void
    {
        $this->renderModuleHome(
            'academico',
            'reportes_home',
            'Reportes academicos',
            'Consolida reportes de asistencia, calificaciones y novedades vinculados a la gestion academica.',
            [
                [
                    'label' => 'Reporte de asistencia',
                    'description' => 'Consolida asistencias, atrasos y faltas por rango, curso o estudiante.',
                    'url' => baseUrl('reportes/asistencia'),
                    'icon' => 'fa-calendar-check-o',
                    'permission' => 'asistencia.supervisar',
                ],
                [
                    'label' => 'Cuadro final',
                    'description' => 'Consolida promedios finales por estudiante, materia y curso.',
                    'url' => baseUrl('reportes/cuadro-final'),
                    'icon' => 'fa-table',
                    'permission' => 'calificaciones.validar|calificaciones.configurar|calificaciones.registrar|calificaciones.editar',
                ],
                [
                    'label' => 'Libreta de calificaciones',
                    'description' => 'Genera la libreta parcial por curso, estudiante y trimestre.',
                    'url' => baseUrl('reportes/libreta'),
                    'icon' => 'fa-file-text-o',
                    'permission' => 'calificaciones.validar|calificaciones.configurar|calificaciones.registrar|calificaciones.editar|calificaciones.publicar',
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
