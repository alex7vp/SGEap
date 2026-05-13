<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$hours = range(1, 7);
$session = is_array($session ?? null) ? $session : false;
$attendance = is_array($attendance ?? null) ? $attendance : [];
$isClosed = $session !== false && ($session['sclestado'] ?? '') === 'CERRADA';
$teacherCalendarDays = is_array($teacherCalendarDays ?? null) ? $teacherCalendarDays : [];
$teacherDayHourSubjects = is_array($teacherDayHourSubjects ?? null) ? $teacherDayHourSubjects : [];
$teacherSessionDays = is_array($teacherSessionDays ?? null) ? $teacherSessionDays : [];
$teacherDaySessions = is_array($teacherDaySessions ?? null) ? $teacherDaySessions : [];
$classDateRange = is_array($classDateRange ?? null) ? $classDateRange : null;
$availableMonths = is_array($availableMonths ?? null) ? $availableMonths : [];
$teacherSubjectHours = is_array($teacherSubjectHours ?? null) ? $teacherSubjectHours : [];
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
$canNavigatePrevious = $availableMonths === [] || in_array($previousMonth, $availableMonths, true);
$canNavigateNext = $availableMonths === [] || in_array($nextMonth, $availableMonths, true);
$firstWeekday = (int) date('N', $monthStartTimestamp);
$daysInMonth = (int) date('t', $monthStartTimestamp);
$dayNumber = 1;
?>
<p class="module-note">El docente registra asistencia por materia asignada y hora de clase. Seleccione una hora habilitada en el calendario; se abrira la ventana para escoger la materia disponible.</p>

<?php if ($currentPeriod === null): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para continuar.</div>
<?php else: ?>
    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Nueva sesion de asistencia</h3>
                <p>Seleccione una materia asignada y la hora correspondiente.</p>
            </div>
        </header>

        <div class="calendar-month teacher-register-calendar">
            <div class="calendar-month-heading">
                <?php if ($canNavigatePrevious): ?>
                    <a
                        class="calendar-nav-button"
                        href="<?= htmlspecialchars(baseUrl('asistencia/registro?mes=' . $previousMonth . '#calendario-docente'), ENT_QUOTES, 'UTF-8'); ?>"
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
                <h3 id="calendario-docente"><?= htmlspecialchars($monthTitle, ENT_QUOTES, 'UTF-8'); ?></h3>
                <?php if ($canNavigateNext): ?>
                    <a
                        class="calendar-nav-button"
                        href="<?= htmlspecialchars(baseUrl('asistencia/registro?mes=' . $nextMonth . '#calendario-docente'), ENT_QUOTES, 'UTF-8'); ?>"
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
                        $day = $teacherCalendarDays[$date] ?? null;
                        $sessionDay = $teacherSessionDays[$date] ?? null;
                        $enabled = is_array($day);
                        $hasSession = is_array($sessionDay);
                        $hasAlert = $hasSession && (int) ($sessionDay['total_alertas'] ?? 0) > 0;
                        $isWeekend = (int) date('N', strtotime($date)) >= 6;
                        $detailUrl = baseUrl('asistencia/registro?mes=' . (string) $selectedMonth . '&fecha=' . $date . '#detalle-docente');
                        ?>
                        <div
                            class="calendar-day <?= $enabled ? 'is-enabled is-type-normal' : 'is-suspended'; ?> <?= $hasSession ? ($hasAlert ? 'is-attendance-alert' : 'is-attendance-ok') : ''; ?> <?= $isWeekend ? 'is-weekend' : ''; ?> <?= $date === (string) $selectedDate ? 'is-selected' : ''; ?>"
                            <?= $hasSession ? 'data-day-detail-url="' . htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>
                        >
                            <strong><?= $dayNumber; ?></strong>
                            <?php if ($hasSession): ?>
                                <span class="student-calendar-status">
                                    <i class="fa <?= $hasAlert ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?>" aria-hidden="true"></i>
                                    <?= htmlspecialchars($hasAlert ? 'Alerta' : 'OK', ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($enabled): ?>
                                <div class="teacher-calendar-hours" role="radiogroup" aria-label="Horas habilitadas para <?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php foreach (($day['hours'] ?? []) as $hour): ?>
                                        <label class="teacher-calendar-hour">
                                            <input
                                                type="radio"
                                                name="teacher_calendar_slot"
                                                value="<?= htmlspecialchars($date . '|' . (string) $hour, ENT_QUOTES, 'UTF-8'); ?>"
                                                data-session-date="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?>"
                                                data-session-hour="<?= htmlspecialchars((string) $hour, ENT_QUOTES, 'UTF-8'); ?>"
                                            >
                                            <span><?= htmlspecialchars((string) $hour, ENT_QUOTES, 'UTF-8'); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php $dayNumber++; ?>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        </div>

        <?php if (empty($teacherCalendarDays)): ?>
            <div class="empty-state">No tienes fechas con horas habilitadas en este mes. Revise que exista asignacion docente vigente y que secretaria haya habilitado el dia, curso y hora.</div>
        <?php else: ?>
            <dialog class="calendar-dialog teacher-session-dialog" id="teacher-session-dialog">
                <form class="data-form" method="POST" action="<?= htmlspecialchars(baseUrl('asistencia/sesiones'), ENT_QUOTES, 'UTF-8'); ?>">
                    <header class="security-assignment-header">
                        <div>
                            <h3>Abrir lista</h3>
                            <p><span data-session-date-label></span> | <span data-session-hour-label></span> hora</p>
                        </div>
                        <button class="btn-secondary btn-auto" type="button" data-teacher-session-close>Cerrar</button>
                    </header>
                <div class="form-grid">
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Materia</span>
                            <select name="asignacion" required>
                                <option value="">Seleccione</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Hora</span>
                            <input type="text" name="sclnumero_hora" value="" readonly>
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Fecha</span>
                            <input type="text" name="cafecha" value="<?= htmlspecialchars((string) $selectedDate, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                        </div>
                    </div>
                </div>
                <div class="actions-row">
                    <button class="btn-primary btn-inline" type="submit">Abrir lista</button>
                </div>
                </form>
            </dialog>
        <?php endif; ?>
    </section>

    <?php if ($teacherDaySessions !== []): ?>
        <section class="security-assignment-block" id="detalle-docente">
            <header class="security-assignment-header">
                <div>
                    <h3>Detalle del <?= htmlspecialchars((string) $selectedDate, ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p>Sesiones registradas por hora, materia y curso.</p>
                </div>
            </header>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th>Materia</th>
                            <th>Curso</th>
                            <th>Estado</th>
                            <th>Registros</th>
                            <th>Asistencias</th>
                            <th>Atrasos</th>
                            <th>FJ</th>
                            <th>FI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teacherDaySessions as $daySession): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $daySession['sclnumero_hora'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) $daySession['mtcnombre_mostrar'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) $daySession['granombre'] . ' ' . (string) $daySession['prlnombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) $daySession['sclestado'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= (int) $daySession['total_registros']; ?></td>
                                <td><?= (int) $daySession['total_asistencias']; ?></td>
                                <td><?= (int) $daySession['total_atrasos']; ?></td>
                                <td><?= (int) $daySession['total_faltas_justificadas']; ?></td>
                                <td><?= (int) $daySession['total_faltas_injustificadas']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($session !== false): ?>
        <section class="security-assignment-block" id="registro">
            <header class="security-assignment-header">
                <div>
                    <h3><?= htmlspecialchars((string) $session['mtcnombre_mostrar'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p>
                        <?= htmlspecialchars((string) $session['cafecha'], ENT_QUOTES, 'UTF-8'); ?>
                        | <?= htmlspecialchars((string) $session['sclnumero_hora'], ENT_QUOTES, 'UTF-8'); ?> hora
                        | <?= htmlspecialchars((string) $session['sclestado'], ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                </div>
            </header>

            <?php if (empty($students)): ?>
                <div class="empty-state">No hay estudiantes activos para registrar en esta sesion. Revise matriculas activas del curso en la fecha de la clase.</div>
            <?php else: ?>
                <form id="attendance-save-form" method="POST" action="<?= htmlspecialchars(baseUrl('asistencia/registros'), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="sclid" value="<?= htmlspecialchars((string) $session['sclid'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Cedula</th>
                                    <th>Estado</th>
                                    <th>Observacion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <?php
                                    $studentId = (int) $student['estid'];
                                    $saved = $attendance[$studentId] ?? [];
                                    $status = (string) ($saved['aesestado'] ?? 'ASISTENCIA');
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) $student['perapellidos'] . ' ' . $student['pernombres'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars((string) $student['percedula'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <select name="estado[<?= $studentId; ?>]" <?= $isClosed ? 'disabled' : ''; ?>>
                                                <option value="ASISTENCIA" <?= $status === 'ASISTENCIA' ? 'selected' : ''; ?>>Asistencia</option>
                                                <option value="ATRASO" <?= $status === 'ATRASO' ? 'selected' : ''; ?>>Atraso</option>
                                                <option value="FALTA_INJUSTIFICADA" <?= $status === 'FALTA_INJUSTIFICADA' ? 'selected' : ''; ?>>Falta</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input
                                                type="text"
                                                name="observacion[<?= $studentId; ?>]"
                                                maxlength="250"
                                                value="<?= htmlspecialchars((string) ($saved['aesobservacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                <?= $isClosed ? 'disabled' : ''; ?>
                                            >
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (!$isClosed): ?>
                        <div class="actions-row">
                            <button class="btn-primary btn-inline" type="button" data-attendance-save-open>Guardar asistencia</button>
                        </div>
                    <?php endif; ?>
                </form>
                <?php if (!$isClosed): ?>
                    <dialog class="calendar-dialog attendance-save-dialog" id="attendance-save-dialog">
                        <header class="security-assignment-header">
                            <div>
                                <h3>Guardar asistencia</h3>
                                <p>Esta seguro de guardar la asistencia registrada para esta sesion?</p>
                            </div>
                        </header>
                        <div class="actions-row">
                            <button class="btn-secondary btn-inline" type="button" data-attendance-save-close>Cancelar</button>
                            <button class="btn-primary btn-inline" type="submit" form="attendance-save-form">Guardar asistencia</button>
                        </div>
                    </dialog>
                <?php endif; ?>
                <?php if (!$isClosed && !empty($attendance)): ?>
                    <form
                        method="POST"
                        action="<?= htmlspecialchars(baseUrl('asistencia/sesiones/cerrar'), ENT_QUOTES, 'UTF-8'); ?>"
                        class="data-form"
                        onsubmit="return confirm('Confirma que desea cerrar esta sesion? Luego el docente ya no podra modificarla.');"
                    >
                        <input type="hidden" name="sclid" value="<?= htmlspecialchars((string) $session['sclid'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="actions-row">
                            <button class="btn-secondary btn-inline" type="submit">Cerrar sesion</button>
                        </div>
                    </form>
                <?php elseif ($isClosed): ?>
                    <div class="empty-state">La sesion esta cerrada. Para corregirla, debe intervenir un usuario con permiso de supervision.</div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    <?php elseif ((int) ($_GET['sclid'] ?? 0) > 0): ?>
        <div class="empty-state">La sesion solicitada no existe o no pertenece a sus asignaciones activas.</div>
    <?php endif; ?>

    <script>
        (function () {
            var dialog = document.getElementById('attendance-save-dialog');
            var openButton = document.querySelector('[data-attendance-save-open]');
            var closeButton = document.querySelector('[data-attendance-save-close]');

            if (!dialog || !openButton) {
                return;
            }

            openButton.addEventListener('click', function () {
                if (typeof dialog.showModal === 'function') {
                    dialog.showModal();
                }
            });

            if (closeButton) {
                closeButton.addEventListener('click', function () {
                    if (typeof dialog.close === 'function') {
                        dialog.close('cancel');
                    }
                });
            }
        }());

        (function () {
            var availability = <?= json_encode($teacherDayHourSubjects, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
            var dialog = document.getElementById('teacher-session-dialog');
            var radios = Array.prototype.slice.call(document.querySelectorAll('[data-session-date][data-session-hour]'));
            var detailDays = Array.prototype.slice.call(document.querySelectorAll('[data-day-detail-url]'));

            if (!dialog || radios.length === 0) {
                return;
            }

            var subjectSelect = dialog.querySelector('select[name="asignacion"]');
            var hourInput = dialog.querySelector('input[name="sclnumero_hora"]');
            var dateInput = dialog.querySelector('input[name="cafecha"]');
            var dateLabel = dialog.querySelector('[data-session-date-label]');
            var hourLabel = dialog.querySelector('[data-session-hour-label]');
            var closeButton = dialog.querySelector('[data-teacher-session-close]');

            function replaceSubjectOptions(subjects) {
                subjectSelect.innerHTML = '';

                var placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = 'Seleccione';
                subjectSelect.appendChild(placeholder);

                subjects.forEach(function (subject) {
                    var option = document.createElement('option');
                    option.value = subject.value;
                    option.textContent = subject.label;
                    subjectSelect.appendChild(option);
                });
            }

            function openSessionDialog(date, hour) {
                var subjects = availability[date] && availability[date][hour] ? availability[date][hour] : [];

                replaceSubjectOptions(subjects);
                dateInput.value = date;
                hourInput.value = hour;
                dateLabel.textContent = date;
                hourLabel.textContent = hour;

                if (typeof dialog.showModal === 'function') {
                    dialog.showModal();
                }
            }

            radios.forEach(function (radio) {
                radio.addEventListener('click', function () {
                    openSessionDialog(radio.getAttribute('data-session-date'), radio.getAttribute('data-session-hour'));
                });
            });

            detailDays.forEach(function (day) {
                day.addEventListener('click', function (event) {
                    if (event.target.closest('.teacher-calendar-hour')) {
                        return;
                    }

                    window.location.href = day.getAttribute('data-day-detail-url');
                });
            });

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
