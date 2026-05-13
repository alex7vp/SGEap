<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$contexts = is_array($contexts ?? null) ? $contexts : [];
$types = is_array($types ?? null) ? $types : [];
$students = is_array($students ?? null) ? $students : [];
$sessions = is_array($sessions ?? null) ? $sessions : [];
$recentNovelties = is_array($recentNovelties ?? null) ? $recentNovelties : [];
$calendarDays = is_array($calendarDays ?? null) ? $calendarDays : [];
$availableMonths = is_array($availableMonths ?? null) ? $availableMonths : [];
$selectedDate = (string) ($selectedDate ?? date('Y-m-d'));
$selectedMonth = (string) ($selectedMonth ?? substr($selectedDate, 0, 7));
$monthStartTimestamp = strtotime((string) ($monthStart ?? ($selectedMonth . '-01')));
$previousMonth = date('Y-m', strtotime('-1 month', $monthStartTimestamp));
$nextMonth = date('Y-m', strtotime('+1 month', $monthStartTimestamp));
$canNavigatePrevious = $availableMonths === [] || in_array($previousMonth, $availableMonths, true);
$canNavigateNext = $availableMonths === [] || in_array($nextMonth, $availableMonths, true);
$firstWeekday = (int) date('N', $monthStartTimestamp);
$daysInMonth = (int) date('t', $monthStartTimestamp);
$dayNumber = 1;
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
<p class="module-note">Registre novedades por estudiante. Si ocurre en hora clase seleccione una sesion; si ocurre fuera de clase use el contexto correspondiente.</p>

<?php if ($currentPeriod === null): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para continuar.</div>
<?php else: ?>
    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Calendario de novedades</h3>
                <p>Seleccione el dia de la jornada donde ocurrio la novedad.</p>
            </div>
        </header>

        <div class="calendar-month teacher-register-calendar">
            <div class="calendar-month-heading">
                <?php if ($canNavigatePrevious): ?>
                    <a
                        class="calendar-nav-button"
                        href="<?= htmlspecialchars(baseUrl('novedades/registro?mes=' . $previousMonth . '#calendario-novedades'), ENT_QUOTES, 'UTF-8'); ?>"
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
                <h3 id="calendario-novedades"><?= htmlspecialchars($monthTitle, ENT_QUOTES, 'UTF-8'); ?></h3>
                <?php if ($canNavigateNext): ?>
                    <a
                        class="calendar-nav-button"
                        href="<?= htmlspecialchars(baseUrl('novedades/registro?mes=' . $nextMonth . '#calendario-novedades'), ENT_QUOTES, 'UTF-8'); ?>"
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
                        $day = $calendarDays[$date] ?? null;
                        $enabled = is_array($day) && !empty($day['cahabilitado']);
                        $isWeekend = (int) date('N', strtotime($date)) >= 6;
                        $detailUrl = baseUrl('novedades/registro?mes=' . (string) $selectedMonth . '&fecha=' . $date . '#nueva-novedad');
                        $dayTag = $enabled ? 'a' : 'div';
                        $dayHref = $enabled ? ' href="' . htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') . '"' : '';
                        ?>
                        <<?= $dayTag; ?>
                            class="calendar-day <?= $enabled ? 'is-enabled is-type-normal' : 'is-suspended'; ?> <?= $isWeekend ? 'is-weekend' : ''; ?> <?= $date === $selectedDate ? 'is-selected' : ''; ?>"
                            <?= $dayHref; ?>
                        >
                            <strong><?= $dayNumber; ?></strong>
                            <?php if ($enabled): ?>
                                <span class="student-calendar-status">
                                    <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
                                    <?= htmlspecialchars((string) ($day['catipo_jornada'] ?? 'NORMAL'), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <?php if (!empty($day['hours'])): ?>
                                    <div class="teacher-calendar-hours" aria-label="Horas habilitadas">
                                        <?php foreach ((array) $day['hours'] as $hour): ?>
                                            <span class="teacher-calendar-hour"><span><?= htmlspecialchars((string) $hour, ENT_QUOTES, 'UTF-8'); ?></span></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </<?= $dayTag; ?>>
                        <?php $dayNumber++; ?>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        </div>

        <?php if ($calendarDays === []): ?>
            <div class="empty-state">No hay dias habilitados en el calendario de asistencia para este mes.</div>
        <?php endif; ?>
    </section>

    <section class="security-assignment-block" id="nueva-novedad">
        <header class="security-assignment-header">
            <div>
                <h3>Nueva novedad</h3>
                <p>
                    Fecha seleccionada:
                    <?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8'); ?>.
                    Use Hora clase solo cuando exista una sesion registrada para esa hora.
                </p>
            </div>
        </header>

        <?php if (empty($calendarDays[$selectedDate]['cahabilitado'])): ?>
            <div class="empty-state">Seleccione un dia habilitado del calendario para registrar novedades.</div>
        <?php elseif ($students === []): ?>
            <div class="empty-state">No hay estudiantes disponibles para registrar novedades.</div>
        <?php else: ?>
            <form class="data-form" method="POST" action="<?= htmlspecialchars(baseUrl('novedades/registro'), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="noefecha" value="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-grid">
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Contexto</span>
                            <select name="noetipo_contexto" required>
                                <?php foreach ($contexts as $context): ?>
                                    <option value="<?= htmlspecialchars((string) $context, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= htmlspecialchars($contextLabels[(string) $context] ?? (string) $context, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Hora</span>
                            <input type="time" name="noehora">
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Sesion</span>
                            <select name="sclid">
                                <option value="">Fuera de hora clase</option>
                                <?php foreach ($sessions as $session): ?>
                                    <option value="<?= htmlspecialchars((string) $session['sclid'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= htmlspecialchars((string) $session['sclnumero_hora'] . ' hora | ' . $session['granombre'] . ' ' . $session['prlnombre'] . ' | ' . $session['mtcnombre_mostrar'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Estudiante</span>
                            <select name="matid" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= htmlspecialchars((string) $student['matid'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= htmlspecialchars((string) $student['perapellidos'] . ' ' . $student['pernombres'] . ' | ' . $student['curso'], ENT_QUOTES, 'UTF-8'); ?>
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
                                <?php foreach ($types as $type): ?>
                                    <option value="<?= htmlspecialchars((string) $type['tnoid'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= htmlspecialchars((string) $type['tnonombre'] . ' | ' . $type['tnogravedad'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Ubicacion</span>
                            <input type="text" name="noeubicacion" maxlength="120" placeholder="Patio, bar, pasillo...">
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
            </form>
        <?php endif; ?>
    </section>

    <section class="security-assignment-block">
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
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
