<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$stats = is_array($stats ?? null) ? $stats : [];
$representativeStudents = is_array($representativeStudents ?? null) ? $representativeStudents : [];
$currentPeriod = is_array($currentPeriod ?? null) ? $currentPeriod : null;
$canCreateMatricula = !empty($canCreateMatricula);
$newMatriculaLabel = (string) ($newMatriculaLabel ?? 'Nueva matricula');
$userPermissions = (array) ($user['permissions'] ?? []);
$can = static fn (string $permission): bool => in_array($permission, $userPermissions, true);
$canMatriculas = $can('matriculas.gestionar');
$canPersonas = $can('personas.gestionar');
$canEstudiantes = $can('estudiantes.gestionar');
$canCursos = $can('cursos.gestionar');
$canConfiguracion = $can('configuracion.gestionar');
$canCatalogos = $can('catalogos.gestionar');
$canDocumentos = $can('matriculas.documentos');
$canUsuarios = $can('seguridad.usuarios');
$canRolesPermisos = $can('seguridad.roles_permisos');
$canOwnMatriculation = $can('estudiante.mi_matricula');
$canRepresentativeStudents = $can('representante.estudiantes');
$canOwnAttendance = $can('asistencia.ver_propia');
$canRepresentativeAttendance = $can('asistencia.representante.ver');
$canOwnNovelties = $can('novedades.ver_propia');
$canRepresentativeNovelties = $can('novedades.representante.ver');
$canNoveltiesModule = $can('novedades.registrar')
    || $can('novedades.supervisar')
    || $canOwnNovelties
    || $canRepresentativeNovelties;
$canAttendanceModule = $can('asistencia.calendario.gestionar')
    || $can('asistencia.registrar')
    || $can('asistencia.supervisar')
    || $can('justificaciones.gestionar')
    || $canOwnAttendance
    || $canRepresentativeAttendance
    || $canNoveltiesModule;

$metricCards = [
    [
        'visible' => $canPersonas,
        'label' => 'Personal',
        'value' => $stats['personal'] ?? 0,
        'detail' => 'Activos: ' . (string) ($stats['personal_activo'] ?? 0),
        'url' => baseUrl('personal'),
        'link' => 'Ir a personal',
    ],
    [
        'visible' => $canEstudiantes,
        'label' => 'Estudiantes',
        'value' => $stats['estudiantes'] ?? 0,
        'detail' => 'Activos: ' . (string) ($stats['estudiantes_activos'] ?? 0),
        'url' => baseUrl('estudiantes'),
        'link' => 'Ir a estudiantes',
    ],
    [
        'visible' => $canCursos,
        'label' => 'Cursos del periodo',
        'value' => $stats['cursos_periodo'] ?? 0,
        'detail' => 'Activos: ' . (string) ($stats['cursos_activos'] ?? 0),
        'url' => baseUrl('cursos'),
        'link' => 'Ir a cursos',
    ],
    [
        'visible' => $canMatriculas,
        'label' => 'Matriculas del periodo',
        'value' => $stats['matriculas_periodo'] ?? 0,
        'detail' => 'Control central de inscripciones y habilitacion de estudiantes en el periodo activo.',
        'url' => baseUrl('matriculas?panel=gestion'),
        'link' => 'Gestionar matriculas',
    ],
];
$metricCards = array_values(array_filter($metricCards, static fn (array $card): bool => !empty($card['visible'])));

$quickLinks = [
    ['visible' => $canAttendanceModule, 'label' => 'Novedades y asistencia', 'url' => baseUrl('asistencia')],
    ['visible' => $canOwnMatriculation, 'label' => 'Mi matricula', 'url' => baseUrl('mi-matricula')],
    ['visible' => $canOwnAttendance || $canOwnNovelties, 'label' => 'Mi asistencia y novedades', 'url' => baseUrl('asistencia/mi-asistencia')],
    ['visible' => $canRepresentativeStudents, 'label' => 'Mis estudiantes', 'url' => baseUrl('dashboard')],
    ['visible' => $canRepresentativeAttendance || $canRepresentativeNovelties, 'label' => 'Asistencia y novedades representados', 'url' => baseUrl('asistencia/representante')],
    ['visible' => $canPersonas, 'label' => 'Ver personal', 'url' => baseUrl('personal')],
    ['visible' => $canEstudiantes, 'label' => 'Registrar estudiante', 'url' => baseUrl('estudiantes/crear')],
    ['visible' => $canMatriculas && $canCreateMatricula, 'label' => $newMatriculaLabel, 'url' => baseUrl('matriculas?panel=nueva')],
    ['visible' => $canCatalogos, 'label' => 'Configurar catalogos', 'url' => baseUrl('configuracion/catalogos')],
    ['visible' => $canConfiguracion, 'label' => 'Gestionar periodos', 'url' => baseUrl('configuracion/periodos')],
    ['visible' => $canDocumentos, 'label' => 'Documentos de matricula', 'url' => baseUrl('configuracion/matricula/documentos')],
    ['visible' => $canUsuarios, 'label' => 'Usuarios del sistema', 'url' => baseUrl('seguridad/usuarios')],
    ['visible' => $canRolesPermisos, 'label' => 'Roles y permisos', 'url' => baseUrl('seguridad/roles-permisos')],
];
$quickLinks = array_values(array_filter($quickLinks, static fn (array $link): bool => !empty($link['visible'])));
?>
<section class="dashboard-hero">
    <div>
        <span class="summary-label">Periodo actual</span>
        <h3><?= htmlspecialchars((string) ($stats['periodo_actual'] ?? 'Sin periodo activo'), ENT_QUOTES, 'UTF-8'); ?></h3>
        <p>
            <?= $currentPeriod !== null
                ? 'Vigencia desde '
                    . htmlspecialchars((string) ($currentPeriod['plefechainicio'] ?? ''), ENT_QUOTES, 'UTF-8')
                    . ' hasta '
                    . htmlspecialchars((string) ($currentPeriod['plefechafin'] ?? ''), ENT_QUOTES, 'UTF-8')
                : 'Activa un periodo lectivo para habilitar la operacion academica del sistema.'; ?>
        </p>
    </div>
    <div class="dashboard-hero-actions">
        <?php if ($canMatriculas && $canCreateMatricula): ?>
            <a class="btn-primary btn-auto" href="<?= htmlspecialchars(baseUrl('matriculas'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($newMatriculaLabel, ENT_QUOTES, 'UTF-8'); ?></a>
        <?php endif; ?>
        <?php if ($canConfiguracion): ?>
            <a class="btn-secondary btn-auto dashboard-periods-button" href="<?= htmlspecialchars(baseUrl('configuracion/periodos'), ENT_QUOTES, 'UTF-8'); ?>">Gestionar periodos</a>
        <?php endif; ?>
    </div>
</section>

<?php if ($metricCards !== []): ?>
    <section class="dashboard-grid dashboard-metrics-grid">
        <?php foreach ($metricCards as $card): ?>
            <article class="summary-card">
                <span class="summary-label"><?= htmlspecialchars((string) $card['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                <strong class="dashboard-metric-value"><?= htmlspecialchars((string) $card['value'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <p><?= htmlspecialchars((string) $card['detail'], ENT_QUOTES, 'UTF-8'); ?></p>
                <a class="text-link" href="<?= htmlspecialchars((string) $card['url'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars((string) $card['link'], ENT_QUOTES, 'UTF-8'); ?></a>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php if ($canRepresentativeStudents): ?>
    <section class="dashboard-grid dashboard-metrics-grid">
        <?php if ($representativeStudents === []): ?>
            <article class="summary-card">
                <span class="summary-label">Mis estudiantes</span>
                <strong>Sin estudiantes vinculados</strong>
                <p>No existen estudiantes asociados a tu usuario representante en el periodo actual.</p>
            </article>
        <?php else: ?>
            <?php foreach ($representativeStudents as $student): ?>
                <?php
                $studentName = trim((string) (($student['perapellidos'] ?? '') . ' ' . ($student['pernombres'] ?? '')));
                $studentUrl = baseUrl('representante/estudiante?id=' . (int) ($student['estid'] ?? 0));
                ?>
                <a class="summary-card student-card-link student-compact-card" href="<?= htmlspecialchars($studentUrl, ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="summary-label">Estudiante</span>
                    <strong><?= htmlspecialchars($studentName !== '' ? $studentName : 'Estudiante sin nombre', ENT_QUOTES, 'UTF-8'); ?></strong>
                    <p>
                        <?= htmlspecialchars((string) ($student['curso'] ?? 'Sin curso'), ENT_QUOTES, 'UTF-8'); ?>
                        | <?= !empty($student['estestado']) ? 'Activo' : 'Inactivo'; ?>
                    </p>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="dashboard-grid">
    <?php if ($quickLinks !== []): ?>
        <article class="summary-card">
            <span class="summary-label">Accesos rapidos</span>
            <strong>Operaciones disponibles</strong>
            <div class="dashboard-link-list">
                <?php foreach ($quickLinks as $link): ?>
                    <a class="text-link" href="<?= htmlspecialchars((string) $link['url'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars((string) $link['label'], ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endforeach; ?>
            </div>
        </article>
    <?php endif; ?>

    <article class="summary-card">
        <span class="summary-label">Estado del sistema</span>
        <strong>Sesion operativa</strong>
        <p>El panel muestra unicamente las secciones habilitadas para tu usuario.</p>
        <div class="dashboard-status-list">
            <span class="permission-option-state is-active">Login activo</span>
            <span class="permission-option-state is-active">Periodo seleccionado</span>
            <?php if ($canMatriculas && $canCreateMatricula): ?>
                <span class="permission-option-state is-active">Matriculacion operativa</span>
            <?php endif; ?>
        </div>
    </article>
</section>
<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
