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
        'url' => '#',
    ],
];

$sectionModuleMap = [
    'dashboard' => 'inicio',
    'personas' => 'academico',
    'estudiantes' => 'academico',
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
                'url' => '#',
            ],
        ],
    ],
    'configuracion' => [
        'title' => 'Configuracion',
        'items' => [
            [
                'key' => 'catalogos',
                'label' => 'Catalogos',
                'url' => baseUrl('configuracion/catalogos'),
            ],
            [
                'key' => 'institucion',
                'label' => 'Institucion',
                'url' => '#',
            ],
            [
                'key' => 'periodos',
                'label' => 'Periodos lectivos',
                'url' => '#',
            ],
            [
                'key' => 'usuarios',
                'label' => 'Usuarios',
                'url' => '#',
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
                'key' => 'roles',
                'label' => 'Roles',
                'url' => '#',
            ],
            [
                'key' => 'permisos',
                'label' => 'Permisos',
                'url' => '#',
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
                <div class="topbar-chip">
                    <span class="topbar-chip-label">Periodo</span>
                    <strong>2026 - 2027</strong>
                </div>
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
                <div class="sidebar-group">
                    <span class="sidebar-group-title"><?= htmlspecialchars($activeSidebar['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php foreach ($activeSidebar['items'] as $item): ?>
                        <a
                            class="<?= ($currentSection ?? '') === $item['key'] ? 'is-active' : ''; ?>"
                            href="<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>"
                        >
                            <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
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
