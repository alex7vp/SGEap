<?php

declare(strict_types=1);

$topModules = [
    'inicio' => [
        'label' => 'Inicio',
        'url' => baseUrl('dashboard'),
    ],
    'academico' => [
        'label' => 'Gestion academica',
        'url' => baseUrl('personas'),
    ],
    'configuracion' => [
        'label' => 'Configuracion',
        'url' => baseUrl('configuracion/catalogos'),
    ],
    'reportes' => [
        'label' => 'Reportes',
        'url' => '#',
    ],
    'seguridad' => [
        'label' => 'Seguridad',
        'url' => baseUrl('seguridad/catalogos'),
    ],
];

$sectionModuleMap = [
    'dashboard' => 'inicio',
    'personas' => 'academico',
    'estudiantes' => 'academico',
    'grados' => 'academico',
    'institucion' => 'configuracion',
    'periodos' => 'configuracion',
    'cursos' => 'configuracion',
    'seguridad_catalogos' => 'seguridad',
    'seguridad_usuarios' => 'seguridad',
    'seguridad_roles_permisos' => 'seguridad',
];

$sidebarModules = [
    'inicio' => [
        'title' => 'Panel principal',
        'items' => [
            [
                'key' => 'dashboard',
                'label' => 'Dashboard',
                'url' => baseUrl('dashboard'),
            ],
        ],
    ],
    'academico' => [
        'title' => 'Gestion academica',
        'items' => [
            [
                'key' => 'personas',
                'label' => 'Personas',
                'url' => baseUrl('personas'),
            ],
            [
                'key' => 'estudiantes',
                'label' => 'Estudiantes',
                'url' => baseUrl('estudiantes'),
            ],
            [
                'key' => 'grados',
                'label' => 'Grados',
                'url' => baseUrl('grados'),
            ],
            [
                'key' => 'docentes',
                'label' => 'Docentes',
                'url' => '#',
            ],
            [
                'key' => 'administrativos',
                'label' => 'Administrativos',
                'url' => '#',
            ],
            [
                'key' => 'matriculas',
                'label' => 'Matriculas',
                'url' => baseUrl('matriculas'),
            ],
        ],
    ],
    'configuracion' => [
        'title' => 'Configuracion',
        'groups' => [
            [
                'title' => 'Catalogos',
                'items' => [
                    [
                        'key' => 'catalogos',
                        'label' => 'Catalogos base',
                        'url' => baseUrl('configuracion/catalogos'),
                    ],
                ],
            ],
            [
                'title' => 'Institucion',
                'items' => [
                    [
                        'key' => 'institucion',
                        'label' => 'Datos institucionales',
                        'url' => baseUrl('configuracion/institucion'),
                    ],
                ],
            ],
            [
                'title' => 'Periodo lectivo',
                'items' => [
                    [
                        'key' => 'periodos',
                        'label' => 'Periodos lectivos',
                        'url' => baseUrl('configuracion/periodos'),
                    ],
                    [
                        'key' => 'cursos',
                        'label' => 'Cursos por periodo',
                        'url' => baseUrl('cursos'),
                    ],
                ],
            ],
        ],
    ],
    'reportes' => [
        'title' => 'Reportes',
        'items' => [
            [
                'key' => 'reportes_estudiantes',
                'label' => 'Reporte de estudiantes',
                'url' => '#',
            ],
            [
                'key' => 'reportes_personal',
                'label' => 'Reporte de personal',
                'url' => '#',
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
            ],
            [
                'key' => 'seguridad_usuarios',
                'label' => 'Usuarios',
                'url' => baseUrl('seguridad/usuarios'),
            ],
            [
                'key' => 'seguridad_roles_permisos',
                'label' => 'Roles y permisos',
                'url' => baseUrl('seguridad/roles-permisos'),
            ],
            [
                'key' => 'auditoria',
                'label' => 'Auditoria',
                'url' => '#',
            ],
        ],
    ],
];

$currentModule = $currentModule ?? ($sectionModuleMap[$currentSection ?? ''] ?? 'inicio');
$activeSidebar = $sidebarModules[$currentModule] ?? $sidebarModules['inicio'];
$activeSidebarGroups = $activeSidebar['groups'] ?? [[
    'title' => $activeSidebar['title'],
    'items' => $activeSidebar['items'] ?? [],
]];
$currentPeriod = currentAcademicPeriod();
$availablePeriods = availableAcademicPeriods();
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
                    <strong><?= htmlspecialchars($appName ?? 'SGEap', ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span>Sistema institucional</span>
                </div>
            </div>

            <nav class="topbar-nav" aria-label="Navegacion principal">
                <?php foreach ($topModules as $moduleKey => $module): ?>
                    <a
                        class="<?= $currentModule === $moduleKey ? 'is-active' : ''; ?>"
                        href="<?= htmlspecialchars($module['url'], ENT_QUOTES, 'UTF-8'); ?>"
                    >
                        <?= htmlspecialchars($module['label'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="topbar-end">
                <details class="topbar-period-picker">
                    <summary class="topbar-chip">
                        <span class="topbar-chip-label">Periodo</span>
                        <strong><?= htmlspecialchars((string) ($currentPeriod['pledescripcion'] ?? 'Sin periodo'), ENT_QUOTES, 'UTF-8'); ?></strong>
                    </summary>
                    <div class="topbar-period-menu">
                        <?php if (empty($availablePeriods)): ?>
                            <div class="empty-state">No existen periodos lectivos registrados.</div>
                        <?php else: ?>
                            <form method="POST" action="<?= htmlspecialchars(baseUrl('configuracion/periodo-actual'), ENT_QUOTES, 'UTF-8'); ?>" class="topbar-period-form">
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
                    <span><?= htmlspecialchars((string) ($user['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
        </div>
    </header>

    <main class="shell">
        <aside class="sidebar-card">
            <div class="sidebar-brand">
                <h1><?= htmlspecialchars($appName ?? 'SGEap', ENT_QUOTES, 'UTF-8'); ?></h1>
                <p><?= htmlspecialchars($activeSidebar['title'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <nav class="sidebar-nav">
                <?php foreach ($activeSidebarGroups as $group): ?>
                    <div class="sidebar-group">
                        <span class="sidebar-group-title"><?= htmlspecialchars((string) ($group['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php foreach (($group['items'] ?? []) as $item): ?>
                            <a
                                class="<?= ($currentSection ?? '') === $item['key'] ? 'is-active' : ''; ?>"
                                href="<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </nav>

            <div class="sidebar-user">
                <strong><?= htmlspecialchars((string) ($user['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                <span>ID persona <?= htmlspecialchars((string) ($user['perid'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>

            <form method="POST" action="<?= htmlspecialchars(baseUrl('logout'), ENT_QUOTES, 'UTF-8'); ?>">
                <button class="btn-secondary" type="submit">Cerrar sesion</button>
            </form>
        </aside>

        <section class="content-card">
            <header class="content-header">
                <div>
                    <p class="eyebrow">Modulo actual</p>
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
