<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$attendanceConfiguration = is_array($attendanceConfiguration ?? null) ? $attendanceConfiguration : false;
$classStartDate = (string) ($attendanceConfiguration['coafecha_inicio_clases'] ?? ($currentPeriod['plefechainicio'] ?? ''));
$classEndDate = (string) ($attendanceConfiguration['coafecha_fin_clases'] ?? ($currentPeriod['plefechafin'] ?? ''));
$configurationNote = (string) ($attendanceConfiguration['coaobservacion'] ?? '');
?>
<p class="module-note">Configura el rango real de clases usado por asistencia. Las materias, cursos y docentes pertenecen a la gestion academica.</p>

<?php if ($currentPeriod === null): ?>
    <section class="security-assignment-block">
        <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para configurar asistencia.</div>
    </section>
<?php else: ?>
    <section class="security-assignment-block" id="rango-clases">
        <header class="security-assignment-header">
            <div>
                <h3>Rango de clases</h3>
                <p>
                    Periodo lectivo:
                    <strong><?= htmlspecialchars((string) $currentPeriod['pledescripcion'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    |
                    <?= htmlspecialchars((string) $currentPeriod['plefechainicio'], ENT_QUOTES, 'UTF-8'); ?>
                    a
                    <?= htmlspecialchars((string) $currentPeriod['plefechafin'], ENT_QUOTES, 'UTF-8'); ?>
                </p>
            </div>
        </header>

        <form class="data-form" method="POST" action="<?= htmlspecialchars(baseUrl('asistencia/configuracion'), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-grid">
                <div>
                    <div class="input-group">
                        <span class="input-addon">Inicio clases</span>
                        <input
                            type="date"
                            name="coafecha_inicio_clases"
                            min="<?= htmlspecialchars((string) $currentPeriod['plefechainicio'], ENT_QUOTES, 'UTF-8'); ?>"
                            max="<?= htmlspecialchars((string) $currentPeriod['plefechafin'], ENT_QUOTES, 'UTF-8'); ?>"
                            value="<?= htmlspecialchars($classStartDate, ENT_QUOTES, 'UTF-8'); ?>"
                            required
                        >
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Fin clases</span>
                        <input
                            type="date"
                            name="coafecha_fin_clases"
                            min="<?= htmlspecialchars((string) $currentPeriod['plefechainicio'], ENT_QUOTES, 'UTF-8'); ?>"
                            max="<?= htmlspecialchars((string) $currentPeriod['plefechafin'], ENT_QUOTES, 'UTF-8'); ?>"
                            value="<?= htmlspecialchars($classEndDate, ENT_QUOTES, 'UTF-8'); ?>"
                            required
                        >
                    </div>
                </div>
                <div class="form-group-full">
                    <div class="input-group">
                        <span class="input-addon">Observacion</span>
                        <input
                            type="text"
                            name="coaobservacion"
                            maxlength="250"
                            value="<?= htmlspecialchars($configurationNote, ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    </div>
                </div>
            </div>
            <div class="actions-row">
                <button class="btn-primary btn-inline" type="submit">Guardar configuracion</button>
                <a class="btn-secondary btn-inline" href="<?= htmlspecialchars(baseUrl('asistencia/calendario#calendario-mes'), ENT_QUOTES, 'UTF-8'); ?>">Ver calendario</a>
            </div>
        </form>
    </section>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
