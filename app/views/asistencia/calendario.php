<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$hours = range(1, 7);
$calendarDay = is_array($calendarDay ?? null) ? $calendarDay : false;
$calendarDetails = is_array($calendarDetails ?? null) ? $calendarDetails : [];
$calendarMonthDays = is_array($calendarMonthDays ?? null) ? $calendarMonthDays : [];
$classDateRange = is_array($classDateRange ?? null) ? $classDateRange : null;
$availableMonths = is_array($availableMonths ?? null) ? $availableMonths : [];
$classRangeStart = (string) ($classDateRange['start'] ?? '');
$classRangeEnd = (string) ($classDateRange['end'] ?? '');
$classRangeConfigured = !empty($classDateRange['configured']);
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
$canNavigatePrevious = $availableMonths === [] || in_array($previousMonth, $availableMonths, true);
$canNavigateNext = $availableMonths === [] || in_array($nextMonth, $availableMonths, true);
$firstWeekday = (int) date('N', $monthStartTimestamp);
$daysInMonth = (int) date('t', $monthStartTimestamp);
$dayNumber = 1;
$selectedDateInsideClassRange = $classRangeStart === ''
    || $classRangeEnd === ''
    || ((string) $selectedDate >= $classRangeStart && (string) $selectedDate <= $classRangeEnd);
?>
<p class="module-note">
    Los dias sin configuracion quedan suspendidos por defecto. Haga clic en una fecha dentro del rango de clases para habilitarla como jornada normal, reducida o especial.
    <?php if ($classRangeStart !== '' && $classRangeEnd !== ''): ?>
        Rango de clases: <strong><?= htmlspecialchars($classRangeStart, ENT_QUOTES, 'UTF-8'); ?></strong> a <strong><?= htmlspecialchars($classRangeEnd, ENT_QUOTES, 'UTF-8'); ?></strong>.
        <?php if (!$classRangeConfigured): ?>
            Este rango usa temporalmente las fechas del periodo; puede ajustarlo en configuracion de asistencia.
        <?php endif; ?>
    <?php endif; ?>
</p>

<?php if ($currentPeriod === null): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para continuar.</div>
<?php else: ?>
    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Calendario de asistencia</h3>
                <p>Periodo actual: <strong><?= htmlspecialchars((string) $currentPeriod['pledescripcion'], ENT_QUOTES, 'UTF-8'); ?></strong>.</p>
            </div>
        </header>

        <div class="calendar-month" id="calendario-mes">
            <div class="calendar-month-heading">
                <?php if ($canNavigatePrevious): ?>
                    <a
                        class="calendar-nav-button"
                        href="<?= htmlspecialchars(baseUrl('asistencia/calendario?mes=' . $previousMonth . '#calendario-mes'), ENT_QUOTES, 'UTF-8'); ?>"
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
                <h3><?= htmlspecialchars($monthTitle, ENT_QUOTES, 'UTF-8'); ?></h3>
                <?php if ($canNavigateNext): ?>
                    <a
                        class="calendar-nav-button"
                        href="<?= htmlspecialchars(baseUrl('asistencia/calendario?mes=' . $nextMonth . '#calendario-mes'), ENT_QUOTES, 'UTF-8'); ?>"
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
            <?php if (!empty($availableMonths)): ?>
                <form class="calendar-month-selector" method="GET" action="<?= htmlspecialchars(baseUrl('asistencia/calendario'), ENT_QUOTES, 'UTF-8'); ?>">
                    <label class="sr-only" for="calendar-month-select">Mes habilitado</label>
                    <select id="calendar-month-select" name="mes" onchange="this.form.submit()">
                        <?php foreach ($availableMonths as $monthOption): ?>
                            <?php $monthOptionTimestamp = strtotime((string) $monthOption . '-01'); ?>
                            <option value="<?= htmlspecialchars((string) $monthOption, ENT_QUOTES, 'UTF-8'); ?>" <?= (string) $monthOption === (string) $selectedMonth ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($monthNames[(int) date('n', $monthOptionTimestamp)] . ' ' . (string) date('Y', $monthOptionTimestamp), ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>
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
                        $isWeekend = (int) date('N', strtotime($date)) >= 6;
                        $insideClassRange = $classRangeStart === '' || $classRangeEnd === '' || ($date >= $classRangeStart && $date <= $classRangeEnd);
                        $typeClass = 'is-type-' . strtolower($enabled ? $type : 'SUSPENDIDA');
                        $dayUrl = baseUrl('asistencia/calendario?mes=' . (string) $selectedMonth . '&fecha=' . $date . '#jornada-dialog');
                        $dayTag = $insideClassRange ? 'a' : 'div';
                        $dayHref = $insideClassRange ? ' href="' . htmlspecialchars($dayUrl, ENT_QUOTES, 'UTF-8') . '"' : '';
                        ?>
                        <<?= $dayTag; ?>
                            class="calendar-day <?= $enabled ? 'is-enabled' : 'is-suspended'; ?> <?= $isWeekend ? 'is-weekend' : ''; ?> <?= !$insideClassRange ? 'is-outside-range' : ''; ?> <?= htmlspecialchars($typeClass, ENT_QUOTES, 'UTF-8'); ?> <?= $date === (string) $selectedDate ? 'is-selected' : ''; ?>"
                            <?= $dayHref; ?>
                        >
                            <strong><?= $dayNumber; ?></strong>
                            <?php if ($enabled): ?>
                                <span><?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </<?= $dayTag; ?>>
                        <?php $dayNumber++; ?>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        </div>
    </section>

    <?php if ($selectedDateInsideClassRange): ?>
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
                    <p>Seleccione el tipo de jornada que se habilitara para este dia.</p>
                </div>
                <button class="btn-secondary btn-auto" value="cancel" formmethod="dialog" type="submit">Cerrar</button>
            </header>

            <div class="form-grid">
                <div>
                    <div class="input-group">
                        <span class="input-addon">Jornada</span>
                        <select name="catipo_jornada" required>
                            <?php foreach (['NORMAL', 'REDUCIDA', 'ESPECIAL', 'SUSPENDIDA'] as $type): ?>
                                <option value="<?= $type; ?>" <?= $selectedType === $type ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div data-calendar-limit-field>
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

            <p class="module-note" data-calendar-mode-note>En NORMAL se habilitan todas las horas. En REDUCIDA se habilita hasta la hora limite. En ESPECIAL se usan solo los cursos y horas marcados. En SUSPENDIDA no se permite registrar asistencia.</p>

            <div data-calendar-detail-block>
                <?php if (empty($courses)): ?>
                    <div class="empty-state">No hay cursos activos en el periodo actual.</div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Curso</th>
                                    <?php foreach ($hours as $hour): ?>
                                        <th>
                                            <div><?= $hour; ?> hora</div>
                                            <input
                                                type="checkbox"
                                                value="<?= $hour; ?>"
                                                data-hour-master="<?= $hour; ?>"
                                                aria-label="Seleccionar todos los cursos en <?= $hour; ?> hora"
                                            >
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $course): ?>
                                    <?php $courseId = (int) $course['curid']; ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) $course['granombre'] . ' ' . $course['prlnombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <?php foreach ($hours as $hour): ?>
                                            <?php $checked = !empty($calendarDetails[$courseId][$hour]['cadhabilitado']); ?>
                                            <td>
                                                <input
                                                    type="checkbox"
                                                    name="detalle[<?= $courseId; ?>][]"
                                                    value="<?= $hour; ?>"
                                                    data-hour-checkbox="<?= $hour; ?>"
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
            </div>

            <div class="actions-row">
                <button class="btn-primary btn-inline" type="submit">Guardar jornada</button>
            </div>
        </form>
    </dialog>
    <?php endif; ?>

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
                                <td>
                                    <span class="calendar-status-pill <?= htmlspecialchars('is-type-' . strtolower((string) $day['catipo_jornada']), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= htmlspecialchars((string) $day['catipo_jornada'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
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

            if (!dialog) {
                return;
            }

            var typeSelect = dialog.querySelector('select[name="catipo_jornada"]');
            var limitSelect = dialog.querySelector('select[name="cahora_limite"]');
            var limitField = dialog.querySelector('[data-calendar-limit-field]');
            var detailBlock = dialog.querySelector('[data-calendar-detail-block]');
            var detailCheckboxes = Array.prototype.slice.call(dialog.querySelectorAll('[data-hour-checkbox]'));
            var hourMasters = Array.prototype.slice.call(dialog.querySelectorAll('[data-hour-master]'));
            var modeNote = dialog.querySelector('[data-calendar-mode-note]');

            function setCheckboxesForHour(hour, checked) {
                detailCheckboxes.forEach(function (checkbox) {
                    if (checkbox.getAttribute('data-hour-checkbox') === String(hour)) {
                        checkbox.checked = checked;
                    }
                });
            }

            function refreshHourMasters() {
                hourMasters.forEach(function (master) {
                    var hour = master.getAttribute('data-hour-master');
                    var checkboxes = detailCheckboxes.filter(function (checkbox) {
                        return checkbox.getAttribute('data-hour-checkbox') === hour;
                    });
                    var checkedCount = checkboxes.filter(function (checkbox) {
                        return checkbox.checked;
                    }).length;

                    master.checked = checkboxes.length > 0 && checkedCount === checkboxes.length;
                    master.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
                });
            }

            function applyJourneyMode() {
                var type = typeSelect ? typeSelect.value : 'NORMAL';
                var limit = limitSelect && limitSelect.value !== '' ? parseInt(limitSelect.value, 10) : 6;
                var isSpecial = type === 'ESPECIAL';
                var isReduced = type === 'REDUCIDA';
                var isSuspended = type === 'SUSPENDIDA';

                if (limitField) {
                    limitField.style.display = isReduced ? '' : 'none';
                }

                if (limitSelect) {
                    limitSelect.disabled = !isReduced;
                }

                if (detailBlock) {
                    detailBlock.style.display = isSpecial ? '' : 'none';
                }

                detailCheckboxes.forEach(function (checkbox) {
                    var hour = parseInt(checkbox.getAttribute('data-hour-checkbox') || '0', 10);

                    if (type === 'NORMAL') {
                        checkbox.checked = true;
                    } else if (type === 'REDUCIDA') {
                        checkbox.checked = hour <= limit;
                    } else if (isSuspended) {
                        checkbox.checked = false;
                    }

                    checkbox.disabled = !isSpecial;
                });

                hourMasters.forEach(function (master) {
                    master.disabled = !isSpecial;
                    master.indeterminate = false;
                });

                if (modeNote) {
                    if (type === 'NORMAL') {
                        modeNote.textContent = 'Jornada normal: se habilitan todas las horas para todos los cursos.';
                    } else if (isReduced) {
                        modeNote.textContent = 'Jornada reducida: se habilita asistencia hasta la hora limite seleccionada.';
                    } else if (isSpecial) {
                        modeNote.textContent = 'Jornada especial: marque solo los cursos y horas en los que se permitira registrar asistencia.';
                    } else if (isSuspended) {
                        modeNote.textContent = 'Jornada suspendida: no se permitira registrar asistencia para este dia.';
                    }
                }

                refreshHourMasters();
            }

            hourMasters.forEach(function (master) {
                master.addEventListener('change', function () {
                    setCheckboxesForHour(master.getAttribute('data-hour-master'), master.checked);
                    refreshHourMasters();
                });
            });

            detailCheckboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', refreshHourMasters);
            });

            if (typeSelect) {
                typeSelect.addEventListener('change', applyJourneyMode);
            }

            if (limitSelect) {
                limitSelect.addEventListener('change', applyJourneyMode);
            }

            applyJourneyMode();
        }());
    </script>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
