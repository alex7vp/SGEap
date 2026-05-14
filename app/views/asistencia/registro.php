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
$noveltyTypes = is_array($noveltyTypes ?? null) ? $noveltyTypes : [];
$noveltyStudents = is_array($noveltyStudents ?? null) ? $noveltyStudents : [];
$noveltySessions = is_array($noveltySessions ?? null) ? $noveltySessions : [];
$recentNovelties = is_array($recentNovelties ?? null) ? $recentNovelties : [];
$userPermissions = (array) ($user['permissions'] ?? []);
$canRegisterNovelties = !empty($canRegisterNovelties)
    || in_array('novedades.registrar', $userPermissions, true)
    || in_array('novedades.supervisar', $userPermissions, true);
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
$selectedDay = $teacherCalendarDays[(string) $selectedDate] ?? null;
$selectedDayEnabled = is_array($selectedDay);
$selectedDayHours = $selectedDayEnabled ? (array) ($selectedDay['hours'] ?? []) : [];
$openNoveltyMode = (string) ($_GET['accion'] ?? '') === 'novedad';
$openNoveltyForSession = $session !== false && $openNoveltyMode;
$noveltyFormStudents = $openNoveltyForSession ? $students : $noveltyStudents;
$noveltyRedirect = $openNoveltyForSession
    ? '/asistencia/registro?sclid=' . (string) $session['sclid'] . '&accion=novedad#novedad-dialog'
    : '/asistencia/registro?mes=' . (string) $selectedMonth . '&fecha=' . (string) $selectedDate . '&accion=novedad#novedad-dialog';
$contextLabels = [
    'CLASE' => 'Hora clase',
    'RECREO' => 'Recreo',
    'ENTRADA' => 'Entrada',
    'SALIDA' => 'Salida',
    'PATIO' => 'Patio',
    'BAR' => 'Bar',
    'EVENTO' => 'Evento',
    'OTRO' => 'Otro',
];
?>
<p class="module-note">Seleccione un dia habilitado para registrar asistencia, registrar novedades o revisar el detalle del dia.</p>

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
                        $enabled = is_array($day);
                        $isWeekend = (int) date('N', strtotime($date)) >= 6;
                        $actionUrl = baseUrl('asistencia/registro?mes=' . (string) $selectedMonth . '&fecha=' . $date . '#acciones-dia');
                        ?>
                        <div
                            class="calendar-day <?= $enabled ? 'is-enabled is-type-normal' : 'is-suspended'; ?> <?= $isWeekend ? 'is-weekend' : ''; ?> <?= $date === (string) $selectedDate ? 'is-selected' : ''; ?>"
                            <?= $enabled ? 'data-day-actions-url="' . htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>
                        >
                            <strong><?= $dayNumber; ?></strong>
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
                    <input type="hidden" name="next_action" value="attendance">
                    <header class="security-assignment-header">
                        <div>
                            <h3 data-session-dialog-title>Abrir lista</h3>
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
                    <button class="btn-primary btn-inline" type="submit" data-session-submit-label>Abrir lista</button>
                </div>
                </form>
            </dialog>
        <?php endif; ?>

        <?php if ($selectedDayEnabled): ?>
            <dialog class="calendar-dialog teacher-day-actions-dialog" id="acciones-dia">
                <header class="security-assignment-header">
                    <div>
                        <h3><?= htmlspecialchars((string) $selectedDate, ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p>Acciones disponibles para el dia seleccionado.</p>
                    </div>
                    <button class="btn-secondary btn-auto" type="button" data-day-actions-close>Cerrar</button>
                </header>

                <?php if ($selectedDayHours !== []): ?>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Hora</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($selectedDayHours as $hour): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) $hour, ENT_QUOTES, 'UTF-8'); ?> hora</td>
                                        <td>
                                            <button
                                                class="btn-secondary btn-inline"
                                                type="button"
                                                data-action-session-date="<?= htmlspecialchars((string) $selectedDate, ENT_QUOTES, 'UTF-8'); ?>"
                                                data-action-session-hour="<?= htmlspecialchars((string) $hour, ENT_QUOTES, 'UTF-8'); ?>"
                                            >
                                                Tomar asistencia
                                            </button>
                                            <?php if ($canRegisterNovelties): ?>
                                                <button
                                                class="btn-primary btn-inline"
                                                type="button"
                                                data-action-novelty-date="<?= htmlspecialchars((string) $selectedDate, ENT_QUOTES, 'UTF-8'); ?>"
                                                data-action-novelty-hour="<?= htmlspecialchars((string) $hour, ENT_QUOTES, 'UTF-8'); ?>"
                                            >
                                                Registrar novedad
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($canRegisterNovelties): ?>
                                    <tr>
                                        <td>Fuera de clase</td>
                                        <td>
                                            <button
                                                class="btn-primary btn-inline"
                                                type="button"
                                                data-novelty-open
                                                data-novelty-context-value="OTRO"
                                                data-novelty-session-value=""
                                            >
                                                Registrar novedad
                                            </button>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <div class="actions-row">
                    <?php if ($teacherDaySessions !== []): ?>
                        <a class="btn-secondary btn-inline" href="<?= htmlspecialchars(baseUrl('asistencia/registro?mes=' . (string) $selectedMonth . '&fecha=' . (string) $selectedDate . '#detalle-docente'), ENT_QUOTES, 'UTF-8'); ?>">
                            Ver detalle
                        </a>
                    <?php endif; ?>
                </div>

            </dialog>
        <?php endif; ?>

        <?php if ($selectedDayEnabled && $canRegisterNovelties): ?>
            <dialog class="calendar-dialog teacher-novelty-dialog" id="novedad-dialog">
                <form class="data-form" method="POST" action="<?= htmlspecialchars(baseUrl('novedades/registro'), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="noefecha" value="<?= htmlspecialchars((string) $selectedDate, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($noveltyRedirect, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php if ($openNoveltyForSession): ?>
                        <input type="hidden" name="noetipo_contexto" value="CLASE">
                        <input type="hidden" name="sclid" value="<?= htmlspecialchars((string) $session['sclid'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php endif; ?>
                    <header class="security-assignment-header">
                        <div>
                            <h3>Nueva novedad</h3>
                            <p>
                                <?= htmlspecialchars((string) $selectedDate, ENT_QUOTES, 'UTF-8'); ?>
                                <?php if ($openNoveltyForSession): ?>
                                    | <?= htmlspecialchars((string) $session['sclnumero_hora'], ENT_QUOTES, 'UTF-8'); ?> hora
                                    | <?= htmlspecialchars((string) $session['mtcnombre_mostrar'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <button class="btn-secondary btn-auto" type="button" data-novelty-close>Cerrar</button>
                    </header>

                    <?php if ($noveltyFormStudents === []): ?>
                        <div class="empty-state">No hay estudiantes disponibles para registrar novedades.</div>
                    <?php else: ?>
                        <div class="form-grid">
                            <?php if ($openNoveltyForSession): ?>
                                <div>
                                    <div class="input-group">
                                        <span class="input-addon">Materia</span>
                                        <input type="text" value="<?= htmlspecialchars((string) $session['mtcnombre_mostrar'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                    </div>
                                </div>
                                <div>
                                    <div class="input-group">
                                        <span class="input-addon">Hora</span>
                                        <input type="text" value="<?= htmlspecialchars((string) $session['sclnumero_hora'], ENT_QUOTES, 'UTF-8'); ?> hora" readonly>
                                    </div>
                                </div>
                                <div>
                                    <div class="input-group">
                                        <span class="input-addon">Fecha</span>
                                        <input type="text" value="<?= htmlspecialchars((string) $session['cafecha'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div>
                                    <div class="input-group">
                                        <span class="input-addon">Contexto</span>
                                        <select name="noetipo_contexto" required data-novelty-context>
                                            <?php foreach (array_keys($contextLabels) as $context): ?>
                                                <option value="<?= htmlspecialchars($context, ENT_QUOTES, 'UTF-8'); ?>" <?= $context === 'OTRO' ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($contextLabels[$context], ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (!$openNoveltyForSession): ?>
                                <div>
                                    <div class="input-group">
                                        <span class="input-addon">Hora</span>
                                        <input type="time" name="noehora" data-novelty-outside-field>
                                    </div>
                                </div>
                                <div>
                                    <div class="input-group">
                                        <span class="input-addon">Sesion</span>
                                        <select name="sclid" data-novelty-session>
                                            <option value="">Fuera de hora clase</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <div class="input-group">
                                        <span class="input-addon">Ubicacion</span>
                                        <input type="text" name="noeubicacion" maxlength="120" placeholder="Patio, bar, pasillo..." data-novelty-outside-field>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div>
                                <div class="input-group">
                                    <span class="input-addon">Estudiante</span>
                                    <select name="matid" required>
                                        <option value="">Seleccione</option>
                                        <?php foreach ($noveltyFormStudents as $noveltyStudent): ?>
                                            <option value="<?= htmlspecialchars((string) $noveltyStudent['matid'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <?= htmlspecialchars(trim((string) $noveltyStudent['perapellidos'] . ' ' . $noveltyStudent['pernombres'] . (!empty($noveltyStudent['curso']) ? ' | ' . (string) $noveltyStudent['curso'] : '')), ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <div class="input-group">
                                    <span class="input-addon">Tipo</span>
                                    <select name="tnoid">
                                        <option value="">Sin tipo</option>
                                        <?php foreach ($noveltyTypes as $type): ?>
                                            <option value="<?= htmlspecialchars((string) $type['tnoid'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <?= htmlspecialchars((string) $type['tnonombre'] . ' | ' . $type['tnogravedad'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <div class="input-group">
                                    <span class="input-addon">Descripcion</span>
                                    <textarea name="noedescripcion" rows="4" required></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="actions-row">
                            <button class="btn-primary btn-inline" type="submit">Registrar novedad</button>
                        </div>
                    <?php endif; ?>
                </form>
                <section>
                    <header class="security-assignment-header">
                        <div>
                            <h3>Novedades del dia</h3>
                            <p>Listado reciente para la fecha seleccionada.</p>
                        </div>
                    </header>
                    <?php
                    $novelties = $recentNovelties;
                    $showActions = false;
                    require BASE_PATH . '/app/views/novedades/_tabla.php';
                    ?>
                </section>
            </dialog>
        <?php endif; ?>
    </section>

    <?php if ($teacherDaySessions !== [] && !$openNoveltyMode): ?>
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

    <?php if ($session !== false && !$openNoveltyForSession): ?>
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
    <?php elseif ($session === false && (int) ($_GET['sclid'] ?? 0) > 0): ?>
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
            var actionDays = Array.prototype.slice.call(document.querySelectorAll('[data-day-actions-url]'));
            var actionDialog = document.getElementById('acciones-dia');
            var actionCloseButton = document.querySelector('[data-day-actions-close]');
            var actionSessionButtons = Array.prototype.slice.call(document.querySelectorAll('[data-action-session-date][data-action-session-hour]'));
            var actionNoveltyButtons = Array.prototype.slice.call(document.querySelectorAll('[data-action-novelty-date][data-action-novelty-hour]'));
            var noveltyDialog = document.getElementById('novedad-dialog');
            var noveltyOpenButtons = Array.prototype.slice.call(document.querySelectorAll('[data-novelty-open]'));
            var noveltyCloseButton = document.querySelector('[data-novelty-close]');
            var noveltyContext = document.querySelector('[data-novelty-context]');
            var noveltySession = document.querySelector('[data-novelty-session]');
            var noveltyOutsideFields = Array.prototype.slice.call(document.querySelectorAll('[data-novelty-outside-field]'));
            var subjectSelect = dialog ? dialog.querySelector('select[name="asignacion"]') : null;
            var hourInput = dialog ? dialog.querySelector('input[name="sclnumero_hora"]') : null;
            var dateInput = dialog ? dialog.querySelector('input[name="cafecha"]') : null;
            var dateLabel = dialog ? dialog.querySelector('[data-session-date-label]') : null;
            var hourLabel = dialog ? dialog.querySelector('[data-session-hour-label]') : null;
            var closeButton = dialog ? dialog.querySelector('[data-teacher-session-close]') : null;
            var nextActionInput = dialog ? dialog.querySelector('input[name="next_action"]') : null;
            var sessionDialogTitle = dialog ? dialog.querySelector('[data-session-dialog-title]') : null;
            var sessionSubmitLabel = dialog ? dialog.querySelector('[data-session-submit-label]') : null;

            function replaceSubjectOptions(subjects) {
                if (!subjectSelect) {
                    return;
                }

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

            function openSessionDialog(date, hour, nextAction) {
                var subjects = availability[date] && availability[date][hour] ? availability[date][hour] : [];
                var action = nextAction === 'novelty' ? 'novelty' : 'attendance';

                replaceSubjectOptions(subjects);
                if (!dialog || !hourInput || !dateInput || !dateLabel || !hourLabel) {
                    return;
                }

                if (actionDialog && typeof actionDialog.close === 'function') {
                    actionDialog.close('session');
                }

                dateInput.value = date;
                hourInput.value = hour;
                dateLabel.textContent = date;
                hourLabel.textContent = hour;

                if (nextActionInput) {
                    nextActionInput.value = action;
                }

                if (sessionDialogTitle) {
                    sessionDialogTitle.textContent = action === 'novelty' ? 'Abrir lista de novedades' : 'Abrir lista';
                }

                if (sessionSubmitLabel) {
                    sessionSubmitLabel.textContent = action === 'novelty' ? 'Abrir lista de novedades' : 'Abrir lista';
                }

                if (typeof dialog.showModal === 'function') {
                    dialog.showModal();
                }
            }

            actionDays.forEach(function (day) {
                day.addEventListener('click', function (event) {
                    window.location.href = day.getAttribute('data-day-actions-url');
                });
            });

            actionSessionButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    openSessionDialog(button.getAttribute('data-action-session-date'), button.getAttribute('data-action-session-hour'), 'attendance');
                });
            });

            actionNoveltyButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    openSessionDialog(button.getAttribute('data-action-novelty-date'), button.getAttribute('data-action-novelty-hour'), 'novelty');
                });
            });

            if (actionDialog && window.location.hash === '#acciones-dia' && typeof actionDialog.showModal === 'function') {
                actionDialog.showModal();
            }

            if (actionCloseButton) {
                actionCloseButton.addEventListener('click', function () {
                    if (actionDialog && typeof actionDialog.close === 'function') {
                        actionDialog.close('cancel');
                    }
                });
            }

            noveltyOpenButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    if (noveltyContext && button.hasAttribute('data-novelty-context-value')) {
                        noveltyContext.value = button.getAttribute('data-novelty-context-value') || 'OTRO';
                    }

                    if (noveltySession && button.hasAttribute('data-novelty-session-value')) {
                        noveltySession.disabled = false;
                        noveltySession.value = button.getAttribute('data-novelty-session-value') || '';
                    }

                    refreshNoveltySessionRequirement();

                    if (actionDialog && typeof actionDialog.close === 'function') {
                        actionDialog.close('novelty');
                    }

                    if (noveltyDialog && typeof noveltyDialog.showModal === 'function') {
                        noveltyDialog.showModal();
                    }
                });
            });

            if (noveltyCloseButton) {
                noveltyCloseButton.addEventListener('click', function () {
                    if (noveltyDialog && typeof noveltyDialog.close === 'function') {
                        noveltyDialog.close('cancel');
                    }
                });
            }

            if (noveltyDialog && window.location.hash === '#novedad-dialog' && typeof noveltyDialog.showModal === 'function') {
                noveltyDialog.showModal();
            }

            function refreshNoveltySessionRequirement() {
                if (!noveltyContext || !noveltySession) {
                    return;
                }

                noveltySession.required = noveltyContext.value === 'CLASE';
                noveltySession.disabled = noveltyContext.value !== 'CLASE';
                noveltyOutsideFields.forEach(function (field) {
                    field.disabled = noveltyContext.value === 'CLASE';
                });

                if (noveltyContext.value !== 'CLASE') {
                    noveltySession.value = '';
                }
            }

            if (noveltyContext) {
                noveltyContext.addEventListener('change', refreshNoveltySessionRequirement);
                refreshNoveltySessionRequirement();
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
