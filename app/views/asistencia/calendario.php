<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$hours = range(1, 7);
$calendarDay = is_array($calendarDay ?? null) ? $calendarDay : false;
$calendarDetails = is_array($calendarDetails ?? null) ? $calendarDetails : [];
$calendarMonthDays = is_array($calendarMonthDays ?? null) ? $calendarMonthDays : [];
$selectedType = (string) ($calendarDay['catipo_jornada'] ?? 'NORMAL');
$selectedLimit = (string) ($calendarDay['cahora_limite'] ?? '');
$monthStartTimestamp = strtotime((string) $monthStart);
$monthNames = [
    1 => 'Enero',
    2 => 'Febrero',
    3 => 'Marzo',
    4 => 'Abril',
    5 => 'Mayo',
    6 => 'Junio',
    7 => 'Julio',
    8 => 'Agosto',
    9 => 'Septiembre',
    10 => 'Octubre',
    11 => 'Noviembre',
    12 => 'Diciembre',
];
$monthTitle = strtoupper($monthNames[(int) date('n', $monthStartTimestamp)] . ' ' . (string) date('Y', $monthStartTimestamp));
$previousMonth = date('Y-m', strtotime('-1 month', $monthStartTimestamp));
$nextMonth = date('Y-m', strtotime('+1 month', $monthStartTimestamp));
$firstWeekday = (int) date('N', $monthStartTimestamp);
$daysInMonth = (int) date('t', $monthStartTimestamp);
$dayNumber = 1;
?>
<p class="module-note">Los dias sin configuracion quedan suspendidos por defecto. Haga clic en una fecha para habilitarla como jornada normal, reducida o especial.</p>

<?php if ($currentPeriod === null): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para continuar.</div>
<?php else: ?>
    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Calendario de asistencia</h3>
                <p>Periodo actual: <strong><?= htmlspecialchars((string) $currentPeriod['pledescripcion'], ENT_QUOTES, 'UTF-8'); ?></strong>.</p>
            </div>
            <div class="actions-group">
                <a class="btn-secondary btn-auto" href="<?= htmlspecialchars(baseUrl('asistencia/calendario?mes=' . $previousMonth), ENT_QUOTES, 'UTF-8'); ?>">Anterior</a>
                <a class="btn-secondary btn-auto" href="<?= htmlspecialchars(baseUrl('asistencia/calendario?mes=' . $nextMonth), ENT_QUOTES, 'UTF-8'); ?>">Siguiente</a>
            </div>
        </header>

        <div class="calendar-month">
            <h3><?= htmlspecialchars($monthTitle, ENT_QUOTES, 'UTF-8'); ?></h3>
            <div class="calendar-grid calendar-grid-header">
                <?php foreach (['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'] as $weekday): ?>
                    <div><?= htmlspecialchars($weekday, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endforeach; ?>
            </div>
            <div class="calendar-grid">
                <?php for ($cell = 1; $cell <= 42; $cell++): ?>
                    <?php if ($cell < $firstWeekday || $dayNumber > $daysInMonth): ?>
                        <div class="calendar-day is-empty"></div>
                    <?php else: ?>
                        <?php
                        $date = (string) $selectedMonth . '-' . str_pad((string) $dayNumber, 2, '0', STR_PAD_LEFT);
                        $day = $calendarMonthDays[$date] ?? null;
                        $type = is_array($day) ? (string) $day['catipo_jornada'] : 'SUSPENDIDA';
                        $enabled = is_array($day) && !empty($day['cahabilitado']);
                        $dayUrl = baseUrl('asistencia/calendario?mes=' . (string) $selectedMonth . '&fecha=' . $date . '#jornada-dialog');
                        ?>
                        <a
                            class="calendar-day <?= $enabled ? 'is-enabled' : 'is-suspended'; ?> <?= $date === (string) $selectedDate ? 'is-selected' : ''; ?>"
                            href="<?= htmlspecialchars($dayUrl, ENT_QUOTES, 'UTF-8'); ?>"
                        >
                            <strong><?= $dayNumber; ?></strong>
                            <span><?= htmlspecialchars($enabled ? $type : 'SUSPENDIDA', ENT_QUOTES, 'UTF-8'); ?></span>
                        </a>
                        <?php $dayNumber++; ?>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        </div>
    </section>

    <dialog class="calendar-dialog" id="jornada-dialog">
        <form
            method="POST"
            action="<?= htmlspecialchars(baseUrl('asistencia/calendario'), ENT_QUOTES, 'UTF-8'); ?>"
            onsubmit="return confirm('Confirma que desea guardar la configuracion de esta jornada?');"
        >
            <input type="hidden" name="cafecha" value="<?= htmlspecialchars((string) $selectedDate, ENT_QUOTES, 'UTF-8'); ?>">
            <header class="security-assignment-header">
                <div>
                    <h3>Configurar <?= htmlspecialchars((string) $selectedDate, ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p>Seleccione el tipo de jornada que se habilitara para este dia. Para dejarlo suspendido, no lo configure.</p>
                </div>
                <button class="btn-secondary btn-auto" value="cancel" formmethod="dialog" type="submit">Cerrar</button>
            </header>

            <div class="form-grid">
                <div>
                    <div class="input-group">
                        <span class="input-addon">Jornada</span>
                        <select name="catipo_jornada" required>
                            <?php foreach (['NORMAL', 'REDUCIDA', 'ESPECIAL'] as $type): ?>
                                <option value="<?= $type; ?>" <?= $selectedType === $type ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Hora limite</span>
                        <select name="cahora_limite">
                            <option value="">Sin limite especial</option>
                            <?php foreach ($hours as $hour): ?>
                                <option value="<?= $hour; ?>" <?= $selectedLimit === (string) $hour ? 'selected' : ''; ?>>
                                    Hasta <?= $hour; ?> hora
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Observacion</span>
                        <input
                            type="text"
                            name="caobservacion"
                            maxlength="250"
                            value="<?= htmlspecialchars((string) ($calendarDay['caobservacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    </div>
                </div>
            </div>

            <p class="module-note">En NORMAL se habilitan las 7 horas. En REDUCIDA se habilita hasta la hora limite. En ESPECIAL se usan solo los cursos y horas marcados abajo.</p>

            <?php if (empty($courses)): ?>
                <div class="empty-state">No hay cursos activos en el periodo actual.</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Curso</th>
                                <?php foreach ($hours as $hour): ?>
                                    <th><?= $hour; ?> hora</th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                                <?php $courseId = (int) $course['curid']; ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) $course['nednombre'] . ' | ' . $course['granombre'] . ' ' . $course['prlnombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <?php foreach ($hours as $hour): ?>
                                        <?php $checked = !empty($calendarDetails[$courseId][$hour]['cadhabilitado']); ?>
                                        <td>
                                            <input
                                                type="checkbox"
                                                name="detalle[<?= $courseId; ?>][]"
                                                value="<?= $hour; ?>"
                                                <?= $checked ? 'checked' : ''; ?>
                                            >
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="actions-row">
                <button class="btn-primary btn-inline" type="submit">Guardar jornada</button>
            </div>
        </form>
    </dialog>

    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Ultimas jornadas configuradas</h3>
                <p>Listado reciente del periodo lectivo actual.</p>
            </div>
        </header>

        <?php if (empty($calendarDays)): ?>
            <div class="empty-state">Todavia no hay jornadas habilitadas para este periodo.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Hora limite</th>
                            <th>Observacion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($calendarDays as $day): ?>
                            <tr>
                                <td>
                                    <a href="<?= htmlspecialchars(baseUrl('asistencia/calendario?mes=' . substr((string) $day['cafecha'], 0, 7) . '&fecha=' . (string) $day['cafecha'] . '#jornada-dialog'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= htmlspecialchars((string) $day['cafecha'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars((string) $day['catipo_jornada'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) ($day['cahora_limite'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) ($day['caobservacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <script>
        (function () {
            var dialog = document.getElementById('jornada-dialog');

            if (dialog && window.location.hash === '#jornada-dialog' && typeof dialog.showModal === 'function') {
                dialog.showModal();
            }
        }());
    </script>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
