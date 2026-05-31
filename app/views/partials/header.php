<?php

declare(strict_types=1);

use App\Models\InstitutionModel;
use App\Models\MatriculationConfigurationModel;
use App\Models\RepresentativeMatriculationAuthorizationModel;

$topModules = [
    'inicio' => [
        'label' => 'Inicio',
        'url' => baseUrl('dashboard'),
        'icon' => 'fa-home',
    ],
    'academico' => [
        'label' => 'Gestion academica',
        'url' => baseUrl('academico'),
        'icon' => 'fa-graduation-cap',
    ],
    'contabilidad' => [
        'label' => 'Gestion Contable',
        'url' => baseUrl('contabilidad'),
        'icon' => 'fa-usd',
    ],
    'configuracion' => [
        'label' => 'Configuracion',
        'url' => baseUrl('configuracion'),
        'icon' => 'fa-cogs',
    ],
    'seguridad' => [
        'label' => 'Seguridad',
        'url' => baseUrl('seguridad'),
        'icon' => 'fa-shield',
    ],
];

$sectionModuleMap = [
    'dashboard' => 'inicio',
    'matricula_temporal' => 'inicio',
    'mi_matricula' => 'inicio',
    'representante_pagos' => 'inicio',
    'academico_home' => 'academico',
    'personas' => 'academico',
    'estudiantes' => 'academico',
    'matriculas' => 'academico',
    'calificaciones_registro' => 'academico',
    'personal' => 'academico',
    'personal_register' => 'academico',
    'personal_assignment' => 'academico',
    'personal_listing' => 'academico',
    'asistencia_home' => 'academico',
    'asistencia_configuracion' => 'configuracion',
    'asistencia_calendario' => 'configuracion',
    'asistencia_justificaciones' => 'academico',
    'asistencia_supervision' => 'academico',
    'asistencia_registro' => 'academico',
    'asistencia_propia' => 'academico',
    'asistencia_representante' => 'academico',
    'novedades_home' => 'academico',
    'novedades_registro' => 'academico',
    'novedades_supervision' => 'academico',
    'novedades_propias' => 'academico',
    'novedades_representante' => 'academico',
    'configuracion_home' => 'configuracion',
    'configuracion_academica' => 'configuracion',
    'catalogos' => 'configuracion',
    'grados' => 'configuracion',
    'institucion' => 'configuracion',
    'periodos' => 'configuracion',
    'configuracion_matricula' => 'configuracion',
    'configuracion_matricula_documentos' => 'configuracion',
    'configuracion_contable' => 'configuracion',
    'backups' => 'configuracion',
    'cursos' => 'configuracion',
    'areas_academicas' => 'configuracion',
    'asignaturas' => 'configuracion',
    'materias_curso' => 'configuracion',
    'docentes_materias' => 'configuracion',
    'calificaciones' => 'configuracion',
    'reportes_home' => 'academico',
    'reporte_asistencia' => 'academico',
    'reporte_libreta' => 'academico',
    'reporte_cuadro_final' => 'academico',
    'contabilidad_dashboard' => 'contabilidad',
    'contabilidad_obligaciones' => 'contabilidad',
    'contabilidad_comprobantes' => 'contabilidad',
    'contabilidad_rubros' => 'contabilidad',
    'contabilidad_reportes' => 'contabilidad',
    'contabilidad_auditoria' => 'contabilidad',
    'seguridad_home' => 'seguridad',
    'seguridad_catalogos' => 'seguridad',
    'seguridad_usuarios' => 'seguridad',
    'seguridad_usuarios_temporales' => 'seguridad',
    'seguridad_roles_permisos' => 'seguridad',
    'seguridad_usuarios_roles' => 'seguridad',
];

$sidebarModules = [
    'inicio' => [
        'title' => 'Panel principal',
        'items' => [
            [
                'key' => 'dashboard',
                'label' => 'Dashboard',
                'url' => baseUrl('dashboard'),
                'icon' => 'fa-home',
            ],
            [
                'key' => 'matricula_temporal',
                'label' => 'Matricula alumno nuevo',
                'url' => baseUrl('matricula-temporal'),
                'icon' => 'fa-address-card',
            ],
            [
                'key' => 'mi_matricula',
                'label' => 'Mi matricula',
                'url' => baseUrl('mi-matricula'),
                'icon' => 'fa-address-card-o',
            ],
            [
                'key' => 'representante_pagos',
                'label' => 'Mis pagos',
                'url' => baseUrl('representante/contabilidad'),
                'icon' => 'fa-file-text-o',
            ],
        ],
    ],
    'academico' => [
        'title' => 'Gestion academica',
        'groups' => [
            [
                'title' => 'Operativo',
                'items' => [
                    [
                        'key' => 'estudiantes',
                        'label' => 'Estudiantes',
                        'url' => baseUrl('estudiantes'),
                        'icon' => 'fa-graduation-cap',
                    ],
                    [
                        'key' => 'personal',
                        'label' => 'Personal',
                        'url' => baseUrl('personal'),
                        'icon' => 'fa-id-badge',
                    ],
                    [
                        'key' => 'matriculas',
                        'label' => 'Matriculas',
                        'url' => baseUrl('matriculas'),
                        'icon' => 'fa-address-card',
                    ],
                    [
                        'key' => 'asistencia_home',
                        'label' => 'Novedades y asistencia',
                        'url' => baseUrl('asistencia'),
                        'icon' => 'fa-calendar-check-o',
                    ],
                    [
                        'key' => 'calificaciones_registro',
                        'label' => 'Calificaciones',
                        'url' => baseUrl('calificaciones/registro'),
                        'icon' => 'fa-check-square',
                    ],
                    [
                        'key' => 'reportes_home',
                        'label' => 'Reportes',
                        'url' => baseUrl('reportes'),
                        'icon' => 'fa-bar-chart',
                    ],
                ],
            ],
        ],
    ],
    'configuracion' => [
        'title' => 'Configuracion',
        'groups' => [
            [
                'title' => 'General',
                'items' => [
                    [
                        'key' => 'catalogos',
                        'label' => 'Catalogos base',
                        'url' => baseUrl('configuracion/catalogos'),
                        'icon' => 'fa-list-alt',
                    ],
                    [
                        'key' => 'institucion',
                        'label' => 'Datos institucionales',
                        'url' => baseUrl('configuracion/institucion'),
                        'icon' => 'fa-university',
                    ],
                    [
                        'key' => 'configuracion_academica',
                        'label' => 'Configuracion academica',
                        'url' => baseUrl('configuracion/academica'),
                        'icon' => 'fa-sitemap',
                        'children' => [
                            [
                                'key' => 'periodos',
                                'label' => 'Periodos lectivos',
                                'url' => baseUrl('configuracion/periodos'),
                                'icon' => 'fa-calendar',
                            ],
                            [
                                'key' => 'configuracion_matricula',
                                'label' => 'Matricula',
                                'url' => baseUrl('configuracion/matricula'),
                                'icon' => 'fa-wpforms',
                            ],
                            [
                                'key' => 'configuracion_matricula_documentos',
                                'label' => 'Documentos',
                                'url' => baseUrl('configuracion/matricula/documentos'),
                                'icon' => 'fa-file-text-o',
                            ],
                            [
                                'key' => 'academica_areas',
                                'label' => 'Areas academicas',
                                'url' => baseUrl('configuracion/academica') . '?view=areas',
                                'icon' => 'fa-book',
                            ],
                            [
                                'key' => 'academica_asignaturas',
                                'label' => 'Asignaturas',
                                'url' => baseUrl('configuracion/academica') . '?view=asignaturas',
                                'icon' => 'fa-list',
                            ],
                            [
                                'key' => 'academica_grados',
                                'label' => 'Grados',
                                'url' => baseUrl('configuracion/academica') . '?view=grados',
                                'icon' => 'fa-sort-numeric-asc',
                            ],
                            [
                                'key' => 'academica_cursos',
                                'label' => 'Cursos',
                                'url' => baseUrl('configuracion/academica') . '?view=cursos',
                                'icon' => 'fa-users',
                            ],
                            [
                                'key' => 'academica_materias',
                                'label' => 'Materias por curso',
                                'url' => baseUrl('configuracion/academica') . '?view=materias',
                                'icon' => 'fa-bookmark',
                            ],
                            [
                                'key' => 'academica_docentes',
                                'label' => 'Asignacion de docentes',
                                'url' => baseUrl('configuracion/academica') . '?view=docentes',
                                'icon' => 'fa-user-plus',
                            ],
                            [
                                'key' => 'calificaciones',
                                'label' => 'Calificaciones',
                                'url' => baseUrl('configuracion/academica/calificaciones'),
                                'icon' => 'fa-check-square',
                            ],
                            [
                                'key' => 'asistencia_configuracion',
                                'label' => 'Rango de clases',
                                'url' => baseUrl('asistencia/configuracion'),
                                'icon' => 'fa-calendar-check-o',
                            ],
                            [
                                'key' => 'asistencia_calendario',
                                'label' => 'Calendario institucional',
                                'url' => baseUrl('asistencia/calendario'),
                                'icon' => 'fa-calendar',
                            ],
                        ],
                    ],
                    [
                        'key' => 'configuracion_contable',
                        'label' => 'Configuracion contable',
                        'url' => baseUrl('configuracion/contable'),
                        'icon' => 'fa-usd',
                    ],
                    [
                        'key' => 'backups',
                        'label' => 'Backups',
                        'url' => baseUrl('configuracion/backups'),
                        'icon' => 'fa-database',
                    ],
                ],
            ],
        ],
    ],
    'contabilidad' => [
        'title' => 'Gestion Contable',
        'groups' => [
            [
                'title' => 'Operacion',
                'items' => [
                    [
                        'key' => 'contabilidad_dashboard',
                        'label' => 'Resumen',
                        'url' => baseUrl('contabilidad'),
                        'icon' => 'fa-dashboard',
                    ],
                    [
                        'key' => 'contabilidad_obligaciones',
                        'label' => 'Obligaciones',
                        'url' => baseUrl('contabilidad/obligaciones'),
                        'icon' => 'fa-list-alt',
                    ],
                    [
                        'key' => 'contabilidad_comprobantes',
                        'label' => 'Comprobantes',
                        'url' => baseUrl('contabilidad/comprobantes'),
                        'icon' => 'fa-file-text-o',
                    ],
                    [
                        'key' => 'contabilidad_rubros',
                        'label' => 'Rubros adicionales',
                        'url' => baseUrl('contabilidad/rubros'),
                        'icon' => 'fa-plus-square',
                    ],
                ],
            ],
            [
                'title' => 'Consulta',
                'items' => [
                    [
                        'key' => 'contabilidad_reportes',
                        'label' => 'Reportes',
                        'url' => baseUrl('contabilidad/reportes'),
                        'icon' => 'fa-bar-chart',
                    ],
                    [
                        'key' => 'contabilidad_auditoria',
                        'label' => 'Auditoria',
                        'url' => baseUrl('contabilidad/auditoria'),
                        'icon' => 'fa-search',
                    ],
                ],
            ],
        ],
    ],
    'seguridad' => [
        'title' => 'Seguridad',
        'items' => [
            [
                'key' => 'seguridad_catalogos',
                'label' => 'Catalogos',
                'url' => baseUrl('seguridad/catalogos'),
                'icon' => 'fa-tags',
            ],
            [
                'key' => 'seguridad_usuarios',
                'label' => 'Usuarios',
                'url' => baseUrl('seguridad/usuarios'),
                'icon' => 'fa-user',
            ],
            [
                'key' => 'seguridad_usuarios_temporales',
                'label' => 'Usuarios temporales',
                'url' => baseUrl('seguridad/usuarios-temporales'),
                'icon' => 'fa-clock-o',
            ],
            [
                'key' => 'seguridad_roles_permisos',
                'label' => 'Designacion de permisos',
                'url' => baseUrl('seguridad/roles-permisos'),
                'icon' => 'fa-key',
            ],
            [
                'key' => 'seguridad_usuarios_roles',
                'label' => 'Roles por usuario',
                'url' => baseUrl('seguridad/usuarios-roles'),
                'icon' => 'fa-users',
            ],
            [
                'key' => 'auditoria',
                'label' => 'Auditoria',
                'url' => '#',
                'icon' => 'fa-search',
            ],
        ],
    ],
];

if (in_array((string) ($currentSection ?? ''), ['personal', 'personal_register', 'personal_assignment', 'personal_listing'], true)) {
    $sidebarModules['academico']['groups'][] = [
        'title' => 'Personal',
        'items' => [
            [
                'key' => 'personal_register',
                'label' => 'Registro de personal',
                'url' => baseUrl('personal/registro'),
                'icon' => 'fa-user-plus',
            ],
            [
                'key' => 'personal_assignment',
                'label' => 'Asignacion del personal',
                'url' => baseUrl('personal/asignacion'),
                'icon' => 'fa-check-square-o',
            ],
            [
                'key' => 'personal_listing',
                'label' => 'Consulta de personal',
                'url' => baseUrl('personal/consulta'),
                'icon' => 'fa-list',
            ],
        ],
    ];
}

$userPermissions = (array) ($user['permissions'] ?? []);
$representativeNewStudentEnabled = false;

if (in_array('representante.matricula_nueva', $userPermissions, true)) {
    try {
        $enabledPeriod = (new MatriculationConfigurationModel())->findEnabledPeriod();

        if ($enabledPeriod !== false) {
            $representativeNewStudentEnabled = (new RepresentativeMatriculationAuthorizationModel())->activeByUserAndPeriod(
                (int) ($user['usuid'] ?? 0),
                (int) $enabledPeriod['pleid']
            ) !== false;
        }
    } catch (\Throwable) {
        $representativeNewStudentEnabled = false;
    }
}

$permissionMap = [
    'dashboard' => 'dashboard.ver',
    'matricula_temporal' => ['matricula_temporal.ver', 'representante.matricula_nueva'],
    'mi_matricula' => 'estudiante.mi_matricula',
    'representante_pagos' => ['contabilidad.representante.obligaciones.ver', 'contabilidad.representante.pagos.ver', 'contabilidad.representante.comprobantes.subir', 'contabilidad.representante.rubros.ver'],
    'representante_estudiantes' => 'representante.estudiantes',
    'asistencia_propia' => ['asistencia.ver_propia', 'novedades.ver_propia'],
    'asistencia_representante' => ['asistencia.representante.ver', 'novedades.representante.ver'],
    'novedades_registro' => ['novedades.registrar', 'novedades.supervisar'],
    'novedades_supervision' => 'novedades.supervisar',
    'novedades_propias' => 'novedades.ver_propia',
    'novedades_representante' => 'novedades.representante.ver',
    'academico_home' => ['estudiantes.gestionar', 'personas.gestionar', 'matriculas.gestionar', 'asistencia.registrar', 'asistencia.supervisar', 'justificaciones.gestionar', 'asistencia.ver_propia', 'asistencia.representante.ver', 'novedades.registrar', 'novedades.supervisar', 'novedades.ver_propia', 'novedades.representante.ver', 'calificaciones.registrar', 'calificaciones.editar', 'calificaciones.configurar', 'calificaciones.validar', 'calificaciones.publicar', 'calificaciones.auditoria.ver'],
    'estudiantes' => 'estudiantes.gestionar',
    'personal' => 'personas.gestionar',
    'personal_register' => 'personas.gestionar',
    'personal_assignment' => 'personas.gestionar',
    'personal_listing' => 'personas.gestionar',
    'matriculas' => 'matriculas.gestionar',
    'calificaciones_registro' => ['asistencia.registrar', 'calificaciones.registrar', 'calificaciones.editar', 'calificaciones.configurar', 'calificaciones.validar', 'calificaciones.publicar', 'calificaciones.auditoria.ver'],
    'asistencia_home' => ['asistencia.registrar', 'asistencia.supervisar', 'justificaciones.gestionar', 'asistencia.ver_propia', 'asistencia.representante.ver', 'novedades.registrar', 'novedades.supervisar', 'novedades.ver_propia', 'novedades.representante.ver'],
    'asistencia_configuracion' => 'asistencia.calendario.gestionar',
    'asistencia_calendario' => 'asistencia.calendario.gestionar',
    'asistencia_justificaciones' => 'justificaciones.gestionar',
    'asistencia_supervision' => 'asistencia.supervisar',
    'asistencia_registro' => 'asistencia.registrar',
    'configuracion_home' => ['configuracion.gestionar', 'catalogos.gestionar', 'cursos.gestionar', 'matriculas.documentos', 'asistencia.calendario.gestionar', 'calificaciones.configurar', 'calificaciones.plantillas.gestionar', 'backups.gestionar'],
    'configuracion_academica' => ['configuracion.gestionar', 'catalogos.gestionar', 'cursos.gestionar', 'matriculas.documentos', 'asistencia.calendario.gestionar', 'calificaciones.configurar', 'calificaciones.plantillas.gestionar'],
    'academica_configuracion' => ['catalogos.gestionar', 'cursos.gestionar', 'asistencia.calendario.gestionar'],
    'catalogos' => 'catalogos.gestionar',
    'institucion' => 'configuracion.gestionar',
    'periodos' => 'configuracion.gestionar',
    'configuracion_matricula' => 'configuracion.gestionar',
    'configuracion_matricula_documentos' => 'matriculas.documentos',
    'configuracion_contable' => 'contabilidad.configurar',
    'backups' => ['backups.gestionar', 'configuracion.gestionar'],
    'grados' => 'catalogos.gestionar',
    'cursos' => 'cursos.gestionar',
    'areas_academicas' => 'asistencia.calendario.gestionar',
    'asignaturas' => 'asistencia.calendario.gestionar',
    'materias_curso' => 'asistencia.calendario.gestionar',
    'docentes_materias' => 'asistencia.calendario.gestionar',
    'calificaciones' => ['calificaciones.configurar', 'calificaciones.plantillas.gestionar'],
    'reporte_asistencia' => 'asistencia.supervisar',
    'reporte_libreta' => ['calificaciones.validar', 'calificaciones.configurar', 'calificaciones.registrar', 'calificaciones.editar', 'calificaciones.publicar'],
    'reporte_cuadro_final' => ['calificaciones.validar', 'calificaciones.configurar', 'calificaciones.registrar', 'calificaciones.editar'],
    'reportes_home' => ['asistencia.supervisar', 'calificaciones.validar', 'calificaciones.configurar', 'calificaciones.registrar', 'calificaciones.editar', 'calificaciones.publicar'],
    'contabilidad_dashboard' => 'contabilidad.ver',
    'contabilidad_obligaciones' => ['contabilidad.obligaciones.ver', 'contabilidad.obligaciones.generar', 'contabilidad.obligaciones.editar'],
    'contabilidad_comprobantes' => ['contabilidad.comprobantes.revisar', 'contabilidad.comprobantes.aprobar', 'contabilidad.comprobantes.rechazar'],
    'contabilidad_rubros' => ['contabilidad.rubros.ver', 'contabilidad.rubros.crear', 'contabilidad.rubros.editar'],
    'contabilidad_reportes' => ['contabilidad.reportes.ver', 'contabilidad.reportes.exportar'],
    'contabilidad_auditoria' => 'contabilidad.auditoria.ver',
    'seguridad_home' => ['seguridad.usuarios', 'seguridad.roles_permisos', 'usuarios_temporales.gestionar'],
    'seguridad_catalogos' => 'seguridad.roles_permisos',
    'seguridad_usuarios' => 'seguridad.usuarios',
    'seguridad_usuarios_temporales' => 'usuarios_temporales.gestionar',
    'seguridad_roles_permisos' => 'seguridad.roles_permisos',
    'seguridad_usuarios_roles' => 'seguridad.roles_permisos',
];
$canAccess = static function (string $key) use ($permissionMap, $userPermissions): bool {
    $required = $permissionMap[$key] ?? null;

    if ($required === null) {
        return true;
    }

    foreach ((array) $required as $permission) {
        if (in_array((string) $permission, $userPermissions, true)) {
            return true;
        }
    }

    return false;
};
$modulePermissions = [
    'inicio' => ['dashboard.ver', 'matricula_temporal.ver', 'representante.matricula_nueva', 'estudiante.mi_matricula', 'representante.estudiantes', 'asistencia.ver_propia', 'asistencia.representante.ver', 'novedades.ver_propia', 'novedades.representante.ver', 'contabilidad.representante.obligaciones.ver', 'contabilidad.representante.pagos.ver', 'contabilidad.representante.comprobantes.subir'],
    'academico' => ['estudiantes.gestionar', 'personas.gestionar', 'matriculas.gestionar', 'asistencia.calendario.gestionar', 'asistencia.registrar', 'asistencia.supervisar', 'justificaciones.gestionar', 'asistencia.ver_propia', 'asistencia.representante.ver', 'novedades.registrar', 'novedades.supervisar', 'novedades.ver_propia', 'novedades.representante.ver', 'calificaciones.registrar', 'calificaciones.editar', 'calificaciones.configurar', 'calificaciones.validar', 'calificaciones.publicar', 'calificaciones.auditoria.ver'],
    'configuracion' => ['configuracion.gestionar', 'catalogos.gestionar', 'cursos.gestionar', 'matriculas.documentos', 'asistencia.calendario.gestionar', 'calificaciones.configurar', 'calificaciones.plantillas.gestionar', 'contabilidad.configurar', 'backups.gestionar'],
    'contabilidad' => ['contabilidad.ver', 'contabilidad.configurar', 'contabilidad.obligaciones.ver', 'contabilidad.rubros.ver', 'contabilidad.comprobantes.revisar', 'contabilidad.pagos.registrar', 'contabilidad.reportes.ver', 'contabilidad.auditoria.ver'],
    'seguridad' => ['seguridad.usuarios', 'seguridad.roles_permisos', 'usuarios_temporales.gestionar'],
];
$canAccessModule = static function (string $moduleKey) use ($modulePermissions, $userPermissions, $representativeNewStudentEnabled): bool {
    foreach (($modulePermissions[$moduleKey] ?? []) as $permission) {
        if ($permission === 'representante.matricula_nueva' && !$representativeNewStudentEnabled) {
            continue;
        }

        if (in_array((string) $permission, $userPermissions, true)) {
            return true;
        }
    }

    return false;
};

if (!in_array('dashboard.ver', $userPermissions, true) && in_array('estudiante.mi_matricula', $userPermissions, true)) {
    $topModules['inicio']['url'] = baseUrl('mi-matricula');
}

if (!in_array('dashboard.ver', $userPermissions, true) && in_array('representante.estudiantes', $userPermissions, true)) {
    $topModules['inicio']['url'] = baseUrl('dashboard');
}

if (!in_array('dashboard.ver', $userPermissions, true) && in_array('matricula_temporal.ver', $userPermissions, true)) {
    $topModules['inicio']['url'] = baseUrl('matricula-temporal');
}

if (!in_array('dashboard.ver', $userPermissions, true) && $representativeNewStudentEnabled) {
    $topModules['inicio']['url'] = baseUrl('matricula-temporal');
}

if (!$representativeNewStudentEnabled && !in_array('matricula_temporal.ver', $userPermissions, true)) {
    $sidebarModules['inicio']['items'] = array_values(array_filter(
        $sidebarModules['inicio']['items'],
        static fn (array $item): bool => ($item['key'] ?? '') !== 'matricula_temporal'
    ));
}

$topModules = array_filter($topModules, static fn (array $module, string $moduleKey): bool => $canAccessModule($moduleKey), ARRAY_FILTER_USE_BOTH);

foreach ($sidebarModules as $moduleKey => $module) {
    if (!$canAccessModule((string) $moduleKey)) {
        unset($sidebarModules[$moduleKey]);
        continue;
    }

    if (isset($module['groups'])) {
        foreach ($module['groups'] as $groupIndex => $group) {
            $items = [];

            foreach (($group['items'] ?? []) as $item) {
                if (isset($item['children']) && is_array($item['children'])) {
                    $item['children'] = array_values(array_filter(
                        $item['children'],
                        static fn (array $child): bool => $canAccess((string) ($child['key'] ?? ''))
                    ));
                }

                if ($canAccess((string) ($item['key'] ?? '')) || !empty($item['children'])) {
                    $items[] = $item;
                }
            }

            if ($items === []) {
                unset($sidebarModules[$moduleKey]['groups'][$groupIndex]);
                continue;
            }

            $sidebarModules[$moduleKey]['groups'][$groupIndex]['items'] = $items;
        }

        $sidebarModules[$moduleKey]['groups'] = array_values($sidebarModules[$moduleKey]['groups']);
    } elseif (isset($module['items'])) {
        $items = [];

        foreach ($module['items'] as $item) {
            if (isset($item['children']) && is_array($item['children'])) {
                $item['children'] = array_values(array_filter(
                    $item['children'],
                    static fn (array $child): bool => $canAccess((string) ($child['key'] ?? ''))
                ));
            }

            if ($canAccess((string) ($item['key'] ?? '')) || !empty($item['children'])) {
                $items[] = $item;
            }
        }

        $sidebarModules[$moduleKey]['items'] = $items;
    }
}

$currentModule = $currentModule ?? ($sectionModuleMap[$currentSection ?? ''] ?? 'inicio');
$activeSidebar = $sidebarModules[$currentModule] ?? ($sidebarModules['inicio'] ?? reset($sidebarModules));
$activeSidebarGroups = $activeSidebar['groups'] ?? [[
    'title' => $activeSidebar['title'],
    'items' => $activeSidebar['items'] ?? [],
]];
$currentPeriod = currentAcademicPeriod();
$availablePeriods = availableAcademicPeriods();
$displayUserName = trim((string) (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));

if ($displayUserName === '') {
    $displayUserName = (string) ($user['username'] ?? '');
}

$institutionName = (string) ($appName ?? 'SGEap');

try {
    $institutionModel = new InstitutionModel();
    $currentInstitution = $institutionModel->current();

    if ($currentInstitution !== false && trim((string) ($currentInstitution['insnombre'] ?? '')) !== '') {
        $institutionName = trim((string) $currentInstitution['insnombre']);
    }
} catch (\Throwable) {
}

$institutionInitials = strtoupper((string) preg_replace('/[^A-Z0-9]/', '', implode('', array_slice(preg_split('/\s+/', $institutionName) ?: [], 0, 2)))) ?: 'SG';
$institutionLogo = null;
$logoDirectory = BASE_PATH . '/public/assets/images';
$logoPatterns = [
    'institution-logo*',
    'logo-institucion*',
    'institucion-logo*',
];

foreach ($logoPatterns as $logoPattern) {
    $matches = glob($logoDirectory . '/' . $logoPattern);

    if (!empty($matches)) {
        $logoFile = basename((string) $matches[0]);
        $institutionLogo = 'images/' . $logoFile;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName ?? 'SGEap', ENT_QUOTES, 'UTF-8'); ?> | <?= htmlspecialchars($pageTitle ?? 'Panel', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset('css/app.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(asset('scss/icons/font-awesome/css/font-awesome.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="panel-page">
    <header class="topbar">
        <div class="topbar-inner">
            <div class="topbar-start">
                <button class="topbar-toggle" type="button" aria-label="Abrir menu" data-sidebar-toggle>
                    <i class="fa fa-bars" aria-hidden="true"></i>
                </button>
                <div class="topbar-brand">
                    <div class="topbar-brand-mark" aria-hidden="true">
                        <?php if ($institutionLogo !== null): ?>
                            <img src="<?= htmlspecialchars(asset($institutionLogo), ENT_QUOTES, 'UTF-8'); ?>" alt="">
                        <?php else: ?>
                            <span><?= htmlspecialchars($institutionInitials, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </div>
                    <strong><?= htmlspecialchars($appName ?? 'SGEap', ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span>Sistema institucional</span>
                </div>
            </div>

            <nav class="topbar-nav" aria-label="Navegacion principal">
                <?php foreach ($topModules as $moduleKey => $module): ?>
                    <a
                        class="<?= $currentModule === $moduleKey ? 'is-active' : ''; ?>"
                        href="<?= htmlspecialchars($module['url'], ENT_QUOTES, 'UTF-8'); ?>"
                        title="<?= htmlspecialchars($module['label'], ENT_QUOTES, 'UTF-8'); ?>"
                    >
                        <i class="fa <?= htmlspecialchars((string) ($module['icon'] ?? 'fa-circle'), ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                        <span class="topbar-nav-label"><?= htmlspecialchars($module['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="topbar-end">
                <details class="topbar-period-picker">
                    <summary class="topbar-chip">
                        <strong><?= htmlspecialchars((string) ($currentPeriod['pledescripcion'] ?? 'Sin periodo'), ENT_QUOTES, 'UTF-8'); ?></strong>
                    </summary>
                    <div class="topbar-period-menu">
                        <?php if (empty($availablePeriods)): ?>
                            <div class="empty-state">No existen periodos lectivos registrados.</div>
                        <?php else: ?>
                            <form method="POST" action="<?= htmlspecialchars(baseUrl('configuracion/periodo-visualizado'), ENT_QUOTES, 'UTF-8'); ?>" class="topbar-period-form">
                                <?= csrfField(); ?>
                                <input type="hidden" name="redirect_to" value="<?= htmlspecialchars(currentPath(), ENT_QUOTES, 'UTF-8'); ?>">
                                <label for="topbar-period-select">Periodo lectivo</label>
                                <select id="topbar-period-select" name="pleid">
                                    <?php foreach ($availablePeriods as $period): ?>
                                        <option value="<?= htmlspecialchars((string) $period['pleid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($currentPeriod['pleid'] ?? 0) === (int) $period['pleid'] ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars((string) $period['pledescripcion'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn-primary btn-auto" type="submit">Aplicar</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </details>
                <div class="topbar-user">
                    <span><?= htmlspecialchars($displayUserName, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
        </div>
    </header>

    <main class="shell <?= ($currentSection ?? '') === 'calificaciones_registro' ? 'shell-sidebar-collapsed' : ''; ?>">
        <aside class="sidebar-card">
            <nav class="sidebar-nav">
                <?php foreach ($activeSidebarGroups as $group): ?>
                    <div class="sidebar-group">
                        <span class="sidebar-group-title"><?= htmlspecialchars((string) ($group['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php foreach (($group['items'] ?? []) as $item): ?>
                            <?php
                            $itemKey = (string) ($item['key'] ?? '');
                            $isActiveSidebarItem = ($currentSection ?? '') === $itemKey
                                || ($itemKey === 'asistencia_home' && str_starts_with((string) ($currentSection ?? ''), 'asistencia_'))
                                || ($itemKey === 'asistencia_home' && str_starts_with((string) ($currentSection ?? ''), 'novedades_'))
                                || ($itemKey === 'reportes_home' && str_starts_with((string) ($currentSection ?? ''), 'reporte_'))
                                || (
                                    $itemKey === 'configuracion_academica'
                                    && in_array((string) ($currentSection ?? ''), [
                                        'configuracion_academica',
                                        'periodos',
                                        'configuracion_matricula',
                                        'configuracion_matricula_documentos',
                                        'grados',
                                        'cursos',
                                        'areas_academicas',
                                        'asignaturas',
                                        'materias_curso',
                                        'docentes_materias',
                                        'asistencia_configuracion',
                                        'asistencia_calendario',
                                        'calificaciones',
                                    ], true)
                                );
                            $sidebarChildren = isset($item['children']) && is_array($item['children']) ? $item['children'] : [];
                            ?>
                            <a
                                class="<?= $isActiveSidebarItem ? 'is-active' : ''; ?>"
                                href="<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                <i class="fa <?= htmlspecialchars((string) ($item['icon'] ?? 'fa-circle'), ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                                <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </a>
                            <?php if ($sidebarChildren !== [] && $isActiveSidebarItem): ?>
                                <div class="sidebar-subnav" aria-label="Submenu de <?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php foreach ($sidebarChildren as $child): ?>
                                        <?php
                                        $childKey = (string) ($child['key'] ?? '');
                                        $selectedAcademicConfigView = (string) ($_GET['view'] ?? 'areas');
                                        $academicChildViewMap = [
                                            'academica_areas' => 'areas',
                                            'academica_asignaturas' => 'asignaturas',
                                            'academica_grados' => 'grados',
                                            'academica_cursos' => 'cursos',
                                            'academica_materias' => 'materias',
                                            'academica_docentes' => 'docentes',
                                        ];
                                        $isActiveSidebarChild = ($currentSection ?? '') === $childKey
                                            || (
                                                isset($academicChildViewMap[$childKey])
                                                && ($currentSection ?? '') === 'configuracion_academica'
                                                && $selectedAcademicConfigView === $academicChildViewMap[$childKey]
                                            );
                                        ?>
                                        <a
                                            class="sidebar-subnav-link <?= $isActiveSidebarChild ? 'is-active' : ''; ?>"
                                            href="<?= htmlspecialchars($child['url'], ENT_QUOTES, 'UTF-8'); ?>"
                                        >
                                            <i class="fa <?= htmlspecialchars((string) ($child['icon'] ?? 'fa-circle'), ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                                            <span><?= htmlspecialchars((string) ($child['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </nav>
            <form method="POST" action="<?= htmlspecialchars(baseUrl('logout'), ENT_QUOTES, 'UTF-8'); ?>">
                <?= csrfField(); ?>
                <button class="btn-secondary sidebar-logout-button" type="submit">Cerrar sesion</button>
            </form>
        </aside>

        <section class="content-card">
            <header class="content-header">
                <div>
                    <h2><?= htmlspecialchars($pageTitle ?? 'Panel', ENT_QUOTES, 'UTF-8'); ?></h2>
                </div>
            </header>

            <div class="content-body">
                <?php if (($currentSection ?? '') === 'calificaciones_registro' && (!empty($error) || !empty($success))): ?>
                    <dialog class="calendar-dialog gradebook-feedback-dialog" data-gradebook-feedback-dialog>
                        <header class="security-assignment-header">
                            <div>
                                <h3><?= !empty($error) ? 'No se pudo completar la accion' : 'Accion completada'; ?></h3>
                                <p><?= htmlspecialchars((string) (!empty($error) ? $error : $success), ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </header>
                        <div class="actions-row">
                            <button class="btn-primary btn-inline" type="button" data-gradebook-feedback-close>Aceptar</button>
                        </div>
                    </dialog>
                <?php endif; ?>

                <?php if (($currentSection ?? '') !== 'calificaciones_registro' && !empty($error)): ?>
                    <div class="alert alert-error alert-dismissible" data-alert>
                        <span><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></span>
                        <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                            <i class="fa fa-times" aria-hidden="true"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (($currentSection ?? '') !== 'calificaciones_registro' && !empty($success)): ?>
                    <div class="alert alert-success alert-dismissible" data-alert>
                        <span><?= htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></span>
                        <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                            <i class="fa fa-times" aria-hidden="true"></i>
                        </button>
                    </div>
                <?php endif; ?>
