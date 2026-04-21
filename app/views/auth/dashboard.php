<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$stats = is_array($stats ?? null) ? $stats : [];
$currentPeriod = is_array($currentPeriod ?? null) ? $currentPeriod : null;
$canCreateMatricula = !empty($canCreateMatricula);
$newMatriculaLabel = (string) ($newMatriculaLabel ?? 'Nueva matricula');
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
        <?php if ($canCreateMatricula): ?>
            <a class="btn-primary btn-auto" href="<?= htmlspecialchars(baseUrl('matriculas'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($newMatriculaLabel, ENT_QUOTES, 'UTF-8'); ?></a>
        <?php endif; ?>
        <a class="btn-secondary btn-auto dashboard-periods-button" href="<?= htmlspecialchars(baseUrl('configuracion/periodos'), ENT_QUOTES, 'UTF-8'); ?>">Gestionar periodos</a>
    </div>
</section>

<section class="dashboard-grid dashboard-metrics-grid">
    <article class="summary-card">
        <span class="summary-label">Personas</span>
        <strong class="dashboard-metric-value"><?= htmlspecialchars((string) ($stats['personas'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></strong>
        <p>Registros base disponibles para estudiantes, familiares, usuarios y personal.</p>
        <a class="text-link" href="<?= htmlspecialchars(baseUrl('personas'), ENT_QUOTES, 'UTF-8'); ?>">Administrar personas</a>
    </article>

    <article class="summary-card">
        <span class="summary-label">Estudiantes</span>
        <strong class="dashboard-metric-value"><?= htmlspecialchars((string) ($stats['estudiantes'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></strong>
        <p>
            Activos:
            <strong><?= htmlspecialchars((string) ($stats['estudiantes_activos'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></strong>
        </p>
        <a class="text-link" href="<?= htmlspecialchars(baseUrl('estudiantes'), ENT_QUOTES, 'UTF-8'); ?>">Ir a estudiantes</a>
    </article>

    <article class="summary-card">
        <span class="summary-label">Cursos del periodo</span>
        <strong class="dashboard-metric-value"><?= htmlspecialchars((string) ($stats['cursos_periodo'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></strong>
        <p>
            Activos:
            <strong><?= htmlspecialchars((string) ($stats['cursos_activos'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></strong>
        </p>
        <a class="text-link" href="<?= htmlspecialchars(baseUrl('cursos'), ENT_QUOTES, 'UTF-8'); ?>">Ir a cursos</a>
    </article>

    <article class="summary-card">
        <span class="summary-label">Matriculas del periodo</span>
        <strong class="dashboard-metric-value"><?= htmlspecialchars((string) ($stats['matriculas_periodo'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></strong>
        <p>Control central de inscripciones y habilitacion de estudiantes en el periodo activo.</p>
        <a class="text-link" href="<?= htmlspecialchars(baseUrl('matriculas?panel=gestion'), ENT_QUOTES, 'UTF-8'); ?>">Gestionar matriculas</a>
    </article>
</section>

<section class="dashboard-grid">
    <article class="summary-card">
        <span class="summary-label">Accesos rapidos</span>
        <strong>Operaciones frecuentes</strong>
        <div class="dashboard-link-list">
            <a class="text-link" href="<?= htmlspecialchars(baseUrl('personas/crear'), ENT_QUOTES, 'UTF-8'); ?>">Registrar persona</a>
            <a class="text-link" href="<?= htmlspecialchars(baseUrl('estudiantes/crear'), ENT_QUOTES, 'UTF-8'); ?>">Registrar estudiante</a>
            <?php if ($canCreateMatricula): ?>
                <a class="text-link" href="<?= htmlspecialchars(baseUrl('matriculas?panel=nueva'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($newMatriculaLabel, ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endif; ?>
            <a class="text-link" href="<?= htmlspecialchars(baseUrl('configuracion/catalogos'), ENT_QUOTES, 'UTF-8'); ?>">Configurar catalogos</a>
        </div>
    </article>

    <article class="summary-card">
        <span class="summary-label">Estado del sistema</span>
        <strong>Base operativa</strong>
        <p>Autenticacion, catalogos, personas, estudiantes, cursos y matriculas ya trabajan sobre PostgreSQL.</p>
        <div class="dashboard-status-list">
            <span class="permission-option-state is-active">Login activo</span>
            <span class="permission-option-state is-active">Periodo seleccionado</span>
            <span class="permission-option-state is-active">Matriculacion operativa</span>
        </div>
    </article>
</section>
<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
