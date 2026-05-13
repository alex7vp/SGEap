<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$availableStudents = is_array($availableStudents ?? null) ? $availableStudents : [];
$summaryDays = is_array($summaryDays ?? null) ? $summaryDays : [];
$attendanceDetail = is_array($attendanceDetail ?? null) ? $attendanceDetail : [];
$classDateRange = is_array($classDateRange ?? null) ? $classDateRange : null;
$availableMonths = is_array($availableMonths ?? null) ? $availableMonths : [];
$selectedStudentId = (int) ($selectedStudentId ?? 0);
$selectedDate = (string) ($selectedDate ?? '');
$selectedMonth = (string) ($selectedMonth ?? date('Y-m'));
$monthStartTimestamp = strtotime((string) $monthStart);
$previousMonth = date('Y-m', strtotime('-1 month', $monthStartTimestamp));
$nextMonth = date('Y-m', strtotime('+1 month', $monthStartTimestamp));
$canNavigatePrevious = $availableMonths === [] || in_array($previousMonth, $availableMonths, true);
$canNavigateNext = $availableMonths === [] || in_array($nextMonth, $availableMonths, true);
$firstWeekday = (int) date('N', $monthStartTimestamp);
$daysInMonth = (int) date('t', $monthStartTimestamp);
$dayNumber = 1;
$summaryDaysByDate = [];
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
$monthTitle = strtoupper($monthNames[(int) date('n', $monthStartTimestamp)] . ' ' . date('Y', $monthStartTimestamp));

foreach ($summaryDays as $day) {
    $summaryDaysByDate[(string) $day['cafecha']] = $day;
}

$monthlyTotals = [
    'asistencias' => 0,
    'atrasos' => 0,
    'faltas_justificadas' => 0,
    'faltas_injustificadas' => 0,
];

foreach ($summaryDays as $day) {
    $monthlyTotals['asistencias'] += ((int) ($day['total_asistencias'] ?? 0) > 0 || (int) ($day['total_atrasos'] ?? 0) > 0) ? 1 : 0;
    $monthlyTotals['atrasos'] += (int) ($day['total_atrasos'] ?? 0) > 0 ? 1 : 0;
    $monthlyTotals['faltas_justificadas'] += (int) ($day['total_faltas_justificadas'] ?? 0) > 0 ? 1 : 0;
    $monthlyTotals['faltas_injustificadas'] += (int) ($day['total_faltas_injustificadas'] ?? 0) > 0 ? 1 : 0;
}

$statusLabels = [
    'OK' => 'OK',
    'ALERTA' => 'Alerta',
    'ASISTENCIA' => 'Asistencia',
    'ATRASO' => 'Atraso',
    'FALTA_JUSTIFICADA' => 'Falta justificada',
    'FALTA_INJUSTIFICADA' => 'Falta injustificada',
];
$basePath = (string) (($currentSection ?? '') === 'asistencia_representante'
    ? 'asistencia/representante'
    : 'asistencia/mi-asistencia');
$studentQuery = $selectedStudentId > 0 && $availableStudents !== [] ? '&estid=' . $selectedStudentId : '';
?>
<p class="module-note">Seleccione un dia con registro en el calendario para ver el detalle por hora y materia.</p>

<?php if ($currentPeriod === null): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para continuar.</div>
<?php elseif ($selectedStudentId <= 0): ?>
    <div class="empty-state">No existen estudiantes disponibles para consultar asistencia.</div>
<?php else: ?>
    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Resumen diario</h3>
                <p>Consulta por mes; los dias sin registro no se muestran como novedad.</p>
            </div>
        </header>

        <form class="data-form" method="GET" action="<?= htmlspecialchars(baseUrl($basePath), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-grid">
                <?php if ($availableStudents !== []): ?>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Estudiante</span>
                            <select name="estid" required>
                                <?php foreach ($availableStudents as $student): ?>
                                    <option value="<?= htmlspecialchars((string) $student['estid'], ENT_QUOTES, 'UTF-8'); ?>" <?= $selectedStudentId === (int) $student['estid'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars((string) $student['perapellidos'] . ' ' . $student['pernombres'] . ' - ' . ($student['curso'] ?? 'Sin curso'), ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($availableStudents !== []): ?>
                <div class="actions-row">
                    <button class="btn-primary btn-inline" type="submit">Consultar</button>
                </div>
            <?php endif; ?>
        </form>
    </section>

    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Calendario de asistencia</h3>
                <p>OK indica que no hay atrasos ni faltas registradas ese dia.</p>
            </div>
        </header>

        <div class="calendar-month student-attendance-calendar">
            <div class="calendar-month-heading">
                <?php if ($canNavigatePrevious): ?>
                    <a
                        class="calendar-nav-button"
                        href="<?= htmlspecialchars(baseUrl($basePath . '?mes=' . $previousMonth . $studentQuery . '#calendario-asistencia'), ENT_QUOTES, 'UTF-8'); ?>"
                        title="Mes anterior"
                        aria-label="Mes anterior"
                    >
                        <i class="fa fa-chevron-left" aria-hidden="true"></i>
                        <span class="sr-only">Mes anterior</span>
                    </a>
                <?php else: ?>
                    <span class="calendar-nav-button is-disabled" title="No hay meses anteriores habilitados" aria-label="No hay meses anteriores habilitados">
                        <i class="fa fa-chevron-left" aria-hidden="true"></i>
                    </span>
                <?php endif; ?>
                <h3 id="calendario-asistencia"><?= htmlspecialchars($monthTitle, ENT_QUOTES, 'UTF-8'); ?></h3>
                <?php if ($canNavigateNext): ?>
                    <a
                        class="calendar-nav-button"
                        href="<?= htmlspecialchars(baseUrl($basePath . '?mes=' . $nextMonth . $studentQuery . '#calendario-asistencia'), ENT_QUOTES, 'UTF-8'); ?>"
                        title="Mes siguiente"
                        aria-label="Mes siguiente"
                    >
                        <i class="fa fa-chevron-right" aria-hidden="true"></i>
                        <span class="sr-only">Mes siguiente</span>
                    </a>
                <?php else: ?>
                    <span class="calendar-nav-button is-disabled" title="No hay meses siguientes habilitados" aria-label="No hay meses siguientes habilitados">
                        <i class="fa fa-chevron-right" aria-hidden="true"></i>
                    </span>
                <?php endif; ?>
            </div>
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
                        $day = $summaryDaysByDate[$date] ?? null;
                        $enabled = is_array($day);
                        $hasAlert = $enabled
                            && ((int) ($day['total_atrasos'] ?? 0) > 0
                                || (int) ($day['total_faltas_justificadas'] ?? 0) > 0
                                || (int) ($day['total_faltas_injustificadas'] ?? 0) > 0);
                        $isWeekend = (int) date('N', strtotime($date)) >= 6;
                        $detailUrl = baseUrl($basePath . '?mes=' . $selectedMonth . $studentQuery . '&fecha=' . $date . '#detalle');
                        $dayTag = $enabled ? 'a' : 'div';
                        $dayHref = $enabled ? ' href="' . htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') . '"' : '';
                        ?>
                        <<?= $dayTag; ?>
                            class="calendar-day <?= $enabled ? 'is-enabled' : 'is-suspended'; ?> <?= $hasAlert ? 'is-attendance-alert' : 'is-attendance-ok'; ?> <?= $isWeekend ? 'is-weekend' : ''; ?> <?= $date === $selectedDate ? 'is-selected' : ''; ?>"
                            <?= $dayHref; ?>
                        >
                            <strong><?= $dayNumber; ?></strong>
                            <?php if ($enabled): ?>
                                <span class="student-calendar-status">
                                    <i class="fa <?= $hasAlert ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?>" aria-hidden="true"></i>
                                    <?= htmlspecialchars($hasAlert ? 'Alerta' : 'OK', ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            <?php endif; ?>
                        </<?= $dayTag; ?>>
                        <?php $dayNumber++; ?>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        </div>

        <?php if ($summaryDays === []): ?>
            <div class="empty-state">No existen registros de asistencia para el mes seleccionado.</div>
        <?php else: ?>
            <div class="attendance-month-summary">
                <div>
                    <span>Asistencias</span>
                    <strong><?= (int) $monthlyTotals['asistencias']; ?></strong>
                </div>
                <div>
                    <span>Atrasos</span>
                    <strong><?= (int) $monthlyTotals['atrasos']; ?></strong>
                </div>
                <div>
                    <span>F. justificadas</span>
                    <strong><?= (int) $monthlyTotals['faltas_justificadas']; ?></strong>
                </div>
                <div>
                    <span>F. injustificadas</span>
                    <strong><?= (int) $monthlyTotals['faltas_injustificadas']; ?></strong>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($selectedDate !== ''): ?>
        <dialog class="calendar-dialog attendance-detail-dialog" id="detalle">
            <header class="security-assignment-header">
                <div>
                    <h3>Detalle del <?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p>Registro por hora, materia y docente.</p>
                </div>
                <button class="btn-secondary btn-auto" type="button" data-attendance-detail-close>Cerrar</button>
            </header>

            <?php if ($attendanceDetail === []): ?>
                <div class="empty-state">No hay detalle registrado para la fecha seleccionada.</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Materia</th>
                                <th>Docente</th>
                                <th>Estado</th>
                                <th>Observacion</th>
                                <th>Justificacion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendanceDetail as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) $row['sclnumero_hora'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) $row['mtcnombre_mostrar'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) $row['docente_apellidos'] . ' ' . $row['docente_nombres'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($statusLabels[(string) $row['aesestado']] ?? (string) $row['aesestado'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) ($row['aesobservacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) ($row['justificacion_motivo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </dialog>
    <?php endif; ?>

    <script>
        (function () {
            var dialog = document.getElementById('detalle');
            var closeButton = document.querySelector('[data-attendance-detail-close]');

            if (!dialog) {
                return;
            }

            if (typeof dialog.showModal === 'function') {
                dialog.showModal();
            }

            if (closeButton) {
                closeButton.addEventListener('click', function () {
                    if (typeof dialog.close === 'function') {
                        dialog.close('cancel');
                    }
                });
            }
        }());
    </script>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
