<?php

declare(strict_types=1);

use App\Models\InstitutionModel;

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
    'reportes' => [
        'label' => 'Reportes',
        'url' => baseUrl('reportes'),
        'icon' => 'fa-bar-chart',
    ],
    'seguridad' => [
        'label' => 'Seguridad',
        'url' => baseUrl('seguridad'),
        'icon' => 'fa-shield',
    ],
];

$sectionModuleMap = [
    'dashboard' => 'inicio',
    'academico_home' => 'academico',
    'personas' => 'academico',
    'estudiantes' => 'academico',
    'configuracion_home' => 'configuracion',
    'grados' => 'configuracion',
    'institucion' => 'configuracion',
    'periodos' => 'configuracion',
    'configuracion_matricula' => 'configuracion',
    'configuracion_matricula_documentos' => 'configuracion',
    'cursos' => 'configuracion',
    'reportes_home' => 'reportes',
    'seguridad_home' => 'seguridad',
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
                'icon' => 'fa-home',
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
                'icon' => 'fa-users',
            ],
            [
                'key' => 'estudiantes',
                'label' => 'Estudiantes',
                'url' => baseUrl('estudiantes'),
                'icon' => 'fa-graduation-cap',
            ],
            [
                'key' => 'docentes',
                'label' => 'Docentes',
                'url' => '#',
                'icon' => 'fa-user-circle',
            ],
            [
                'key' => 'administrativos',
                'label' => 'Administrativos',
                'url' => '#',
                'icon' => 'fa-briefcase',
            ],
            [
                'key' => 'matriculas',
                'label' => 'Matriculas',
                'url' => baseUrl('matriculas'),
                'icon' => 'fa-address-card',
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
                        'icon' => 'fa-list-alt',
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
                        'icon' => 'fa-university',
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
                        'icon' => 'fa-calendar',
                    ],
                    [
                        'key' => 'configuracion_matricula',
                        'label' => 'Configuracion de matricula',
                        'url' => baseUrl('configuracion/matricula'),
                        'icon' => 'fa-wpforms',
                    ],
                    [
                        'key' => 'configuracion_matricula_documentos',
                        'label' => 'Documentos de matricula',
                        'url' => baseUrl('configuracion/matricula/documentos'),
                        'icon' => 'fa-file-pdf-o',
                    ],
                    [
                        'key' => 'grados',
                        'label' => 'Grados',
                        'url' => baseUrl('grados'),
                        'icon' => 'fa-sitemap',
                    ],
                    [
                        'key' => 'cursos',
                        'label' => 'Cursos por periodo',
                        'url' => baseUrl('cursos'),
                        'icon' => 'fa-book',
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
                'icon' => 'fa-file-text-o',
            ],
            [
                'key' => 'reportes_personal',
                'label' => 'Reporte de personal',
                'url' => '#',
                'icon' => 'fa-file-text',
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
                'key' => 'seguridad_roles_permisos',
                'label' => 'Roles y permisos',
                'url' => baseUrl('seguridad/roles-permisos'),
                'icon' => 'fa-key',
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

$currentModule = $currentModule ?? ($sectionModuleMap[$currentSection ?? ''] ?? 'inicio');
$activeSidebar = $sidebarModules[$currentModule] ?? $sidebarModules['inicio'];
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
                            <a
                                class="<?= ($currentSection ?? '') === $item['key'] ? 'is-active' : ''; ?>"
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
