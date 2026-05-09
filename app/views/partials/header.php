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
    'academico_home' => 'academico',
    'personas' => 'academico',
    'estudiantes' => 'academico',
    'matriculas' => 'academico',
    'personal' => 'academico',
    'personal_register' => 'academico',
    'personal_assignment' => 'academico',
    'personal_listing' => 'academico',
    'asistencia_home' => 'academico',
    'asistencia_configuracion' => 'academico',
    'asistencia_calendario' => 'academico',
    'asistencia_justificaciones' => 'academico',
    'asistencia_supervision' => 'academico',
    'asistencia_registro' => 'academico',
    'asistencia_propia' => 'academico',
    'asistencia_representante' => 'academico',
    'configuracion_home' => 'configuracion',
    'configuracion_academica' => 'configuracion',
    'catalogos' => 'configuracion',
    'grados' => 'configuracion',
    'institucion' => 'configuracion',
    'periodos' => 'configuracion',
    'configuracion_matricula' => 'configuracion',
    'configuracion_matricula_documentos' => 'configuracion',
    'cursos' => 'configuracion',
    'reportes_home' => 'reportes',
    'reporte_asistencia' => 'academico',
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
                        'label' => 'Asistencia',
                        'url' => baseUrl('asistencia'),
                        'icon' => 'fa-calendar-check-o',
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
    'representante_estudiantes' => 'representante.estudiantes',
    'asistencia_propia' => 'asistencia.ver_propia',
    'asistencia_representante' => 'asistencia.representante.ver',
    'academico_home' => ['estudiantes.gestionar', 'personas.gestionar', 'matriculas.gestionar', 'asistencia.calendario.gestionar', 'asistencia.registrar', 'asistencia.supervisar', 'justificaciones.gestionar', 'asistencia.ver_propia', 'asistencia.representante.ver'],
    'estudiantes' => 'estudiantes.gestionar',
    'personal' => 'personas.gestionar',
    'personal_register' => 'personas.gestionar',
    'personal_assignment' => 'personas.gestionar',
    'personal_listing' => 'personas.gestionar',
    'matriculas' => 'matriculas.gestionar',
    'asistencia_home' => ['asistencia.calendario.gestionar', 'asistencia.registrar', 'asistencia.supervisar', 'justificaciones.gestionar', 'asistencia.ver_propia', 'asistencia.representante.ver'],
    'asistencia_configuracion' => 'asistencia.calendario.gestionar',
    'asistencia_calendario' => 'asistencia.calendario.gestionar',
    'asistencia_justificaciones' => 'justificaciones.gestionar',
    'asistencia_supervision' => 'asistencia.supervisar',
    'asistencia_registro' => 'asistencia.registrar',
    'configuracion_home' => ['configuracion.gestionar', 'catalogos.gestionar', 'cursos.gestionar', 'matriculas.documentos', 'asistencia.calendario.gestionar'],
    'configuracion_academica' => ['configuracion.gestionar', 'catalogos.gestionar', 'cursos.gestionar', 'matriculas.documentos', 'asistencia.calendario.gestionar'],
    'catalogos' => 'catalogos.gestionar',
    'institucion' => 'configuracion.gestionar',
    'periodos' => 'configuracion.gestionar',
    'configuracion_matricula' => 'configuracion.gestionar',
    'configuracion_matricula_documentos' => 'matriculas.documentos',
    'grados' => 'catalogos.gestionar',
    'cursos' => 'cursos.gestionar',
    'reporte_asistencia' => 'asistencia.supervisar',
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
    'inicio' => ['dashboard.ver', 'matricula_temporal.ver', 'representante.matricula_nueva', 'estudiante.mi_matricula', 'representante.estudiantes', 'asistencia.ver_propia', 'asistencia.representante.ver'],
    'academico' => ['estudiantes.gestionar', 'personas.gestionar', 'matriculas.gestionar', 'asistencia.calendario.gestionar', 'asistencia.registrar', 'asistencia.supervisar', 'justificaciones.gestionar', 'asistencia.ver_propia', 'asistencia.representante.ver'],
    'configuracion' => ['configuracion.gestionar', 'catalogos.gestionar', 'cursos.gestionar', 'matriculas.documentos', 'asistencia.calendario.gestionar'],
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
            $items = array_values(array_filter($group['items'] ?? [], static fn (array $item): bool => $canAccess((string) ($item['key'] ?? ''))));

            if ($items === []) {
                unset($sidebarModules[$moduleKey]['groups'][$groupIndex]);
                continue;
            }

            $sidebarModules[$moduleKey]['groups'][$groupIndex]['items'] = $items;
        }

        $sidebarModules[$moduleKey]['groups'] = array_values($sidebarModules[$moduleKey]['groups']);
    } elseif (isset($module['items'])) {
        $sidebarModules[$moduleKey]['items'] = array_values(array_filter($module['items'], static fn (array $item): bool => $canAccess((string) ($item['key'] ?? ''))));
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

    <main class="shell">
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
                                || ($itemKey === 'asistencia_home' && ($currentSection ?? '') === 'reporte_asistencia')
                                || (
                                    $itemKey === 'configuracion_academica'
                                    && in_array((string) ($currentSection ?? ''), [
                                        'configuracion_academica',
                                        'periodos',
                                        'configuracion_matricula',
                                        'configuracion_matricula_documentos',
                                        'grados',
                                        'cursos',
                                        'asistencia_configuracion',
                                    ], true)
                                );
                            ?>
                            <a
                                class="<?= $isActiveSidebarItem ? 'is-active' : ''; ?>"
                                href="<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                <i class="fa <?= htmlspecialchars((string) ($item['icon'] ?? 'fa-circle'), ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                                <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </nav>
            <form method="POST" action="<?= htmlspecialchars(baseUrl('logout'), ENT_QUOTES, 'UTF-8'); ?>">
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
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error alert-dismissible" data-alert>
                        <span><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></span>
                        <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                            <i class="fa fa-times" aria-hidden="true"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible" data-alert>
                        <span><?= htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></span>
                        <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                            <i class="fa fa-times" aria-hidden="true"></i>
                        </button>
                    </div>
                <?php endif; ?>
