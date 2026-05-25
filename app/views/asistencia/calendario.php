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
$selectedDateHasAttendance = !empty($selectedDateHasAttendance);
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

<?php if ($currentPeriod === null): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para continuar.</div>
<?php else: ?>
    <section class="security-assignment-block">
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
                <div class="calendar-month-title">
                    <?php if (!empty($availableMonths)): ?>
                        <details class="calendar-month-picker">
                            <summary><?= htmlspecialchars($monthTitle, ENT_QUOTES, 'UTF-8'); ?></summary>
                            <div class="calendar-month-menu">
                                <?php foreach ($availableMonths as $monthOption): ?>
                                    <?php
                                    $monthOptionTimestamp = strtotime((string) $monthOption . '-01');
                                    $monthOptionLabel = $monthNames[(int) date('n', $monthOptionTimestamp)] . ' ' . (string) date('Y', $monthOptionTimestamp);
                                    ?>
                                    <a
                                        href="<?= htmlspecialchars(baseUrl('asistencia/calendario?mes=' . (string) $monthOption . '#calendario-mes'), ENT_QUOTES, 'UTF-8'); ?>"
                                        class="<?= (string) $monthOption === (string) $selectedMonth ? 'is-active' : ''; ?>"
                                    >
                                        <?= htmlspecialchars($monthOptionLabel, ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php else: ?>
                        <h3><?= htmlspecialchars($monthTitle, ENT_QUOTES, 'UTF-8'); ?></h3>
                    <?php endif; ?>
                </div>
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
            <div class="calendar-bulk-actions">
                <button class="btn-secondary btn-inline" type="button" data-calendar-bulk-open>Configurar seleccionados</button>
                <span class="cell-subtitle" data-calendar-selection-count>0 fechas seleccionadas</span>
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
                        $day = $calendarMonthDays[$date] ?? null;
                        $type = is_array($day) ? (string) $day['catipo_jornada'] : 'SUSPENDIDA';
                        $enabled = is_array($day) && !empty($day['cahabilitado']);
                        $hasAttendance = is_array($day) && (int) ($day['asistencia_registrada'] ?? 0) > 0;
                        $isWeekend = (int) date('N', strtotime($date)) >= 6;
                        $insideClassRange = $classRangeStart === '' || $classRangeEnd === '' || ($date >= $classRangeStart && $date <= $classRangeEnd);
                        $typeClass = 'is-type-' . strtolower($enabled ? $type : 'SUSPENDIDA');
                        $dayUrl = baseUrl('asistencia/calendario?mes=' . (string) $selectedMonth . '&fecha=' . $date . '#jornada-dialog');
                        $canEditDay = $insideClassRange && !$hasAttendance;
                        ?>
                        <div
                            class="calendar-day <?= $enabled ? 'is-enabled' : 'is-suspended'; ?> <?= $canEditDay ? 'is-editable' : ''; ?> <?= $isWeekend ? 'is-weekend' : ''; ?> <?= !$insideClassRange ? 'is-outside-range' : ''; ?> <?= $hasAttendance ? 'is-locked' : ''; ?> <?= htmlspecialchars($typeClass, ENT_QUOTES, 'UTF-8'); ?> <?= $date === (string) $selectedDate ? 'is-selected' : ''; ?>"
                        >
                            <?php if ($insideClassRange): ?>
                                <label class="calendar-day-checkbox" title="<?= $hasAttendance ? 'No se puede seleccionar: ya tiene asistencia registrada' : 'Seleccionar fecha'; ?>">
                                    <input
                                        type="checkbox"
                                        value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-calendar-day-select
                                        <?= $hasAttendance ? 'disabled' : ''; ?>
                                    >
                                    <span class="sr-only">Seleccionar <?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?></span>
                                </label>
                            <?php endif; ?>
                            <?php if ($canEditDay): ?>
                                <a class="calendar-day-edit" href="<?= htmlspecialchars($dayUrl, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Editar configuracion de <?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?>">
                                    <strong><?= $dayNumber; ?></strong>
                                </a>
                            <?php else: ?>
                                <strong><?= $dayNumber; ?></strong>
                            <?php endif; ?>
                            <?php if ($enabled): ?>
                                <span><?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                            <?php if ($hasAttendance): ?>
                                <small>Con asistencia</small>
                            <?php endif; ?>
                        </div>
                        <?php $dayNumber++; ?>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        </div>
        <p class="module-note">
            Los dias sin configuracion quedan suspendidos por defecto. Marque varias fechas para aplicar una configuracion masiva o haga clic en un dia para editarlo individualmente.
            <?php if ($classRangeStart !== '' && $classRangeEnd !== ''): ?>
                Rango de clases: <strong><?= htmlspecialchars($classRangeStart, ENT_QUOTES, 'UTF-8'); ?></strong> a <strong><?= htmlspecialchars($classRangeEnd, ENT_QUOTES, 'UTF-8'); ?></strong>.
                <?php if (!$classRangeConfigured): ?>
                    Este rango usa temporalmente las fechas del periodo; puede ajustarlo en configuracion de asistencia.
                <?php endif; ?>
            <?php endif; ?>
        </p>
    </section>

    <?php if ($selectedDateInsideClassRange): ?>
    <dialog class="calendar-dialog" id="jornada-dialog">
        <form
            id="calendar-save-form"
            method="POST"
            action="<?= htmlspecialchars(baseUrl('asistencia/calendario'), ENT_QUOTES, 'UTF-8'); ?>"
        >
            <?= csrfField(); ?>
            <input type="hidden" name="cafecha" value="<?= htmlspecialchars((string) $selectedDate, ENT_QUOTES, 'UTF-8'); ?>" data-calendar-primary-date>
            <div data-calendar-selected-date-inputs></div>
            <header class="security-assignment-header">
                <div>
                    <h3 data-calendar-dialog-title>Configurar <?= htmlspecialchars((string) $selectedDate, ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p data-calendar-dialog-help>Seleccione el tipo de jornada que se habilitara para este dia.</p>
                </div>
                <button class="btn-secondary btn-auto" type="button" data-calendar-dialog-close>Cerrar</button>
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
                                    <th>
                                        <span class="sr-only">Curso</span>
                                    </th>
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
                                        <td>
                                            <input
                                                type="checkbox"
                                                data-course-master="<?= $courseId; ?>"
                                                aria-label="Seleccionar todas las horas de <?= htmlspecialchars((string) $course['granombre'] . ' ' . $course['prlnombre'], ENT_QUOTES, 'UTF-8'); ?>"
                                            >
                                        </td>
                                        <td><?= htmlspecialchars((string) $course['granombre'] . ' ' . $course['prlnombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <?php foreach ($hours as $hour): ?>
                                            <?php $checked = !empty($calendarDetails[$courseId][$hour]['cadhabilitado']); ?>
                                            <td>
                                                <input
                                                    type="checkbox"
                                                    name="detalle[<?= $courseId; ?>][]"
                                                    value="<?= $hour; ?>"
                                                    data-hour-checkbox="<?= $hour; ?>"
                                                    data-course-checkbox="<?= $courseId; ?>"
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
                <button
                    class="btn-primary btn-inline"
                    type="button"
                    data-calendar-save-open
                >
                    Guardar jornada
                </button>
            </div>
        </form>
    </dialog>
    <dialog class="calendar-dialog attendance-save-dialog" id="calendar-save-dialog">
        <header class="security-assignment-header">
            <div>
                <h3>Guardar jornada</h3>
                <p>Esta seguro de guardar la configuracion de asistencia para la jornada seleccionada?</p>
            </div>
        </header>
        <div class="actions-row">
            <button class="btn-secondary btn-inline" type="button" data-calendar-save-close>Cancelar</button>
            <button class="btn-primary btn-inline" type="submit" form="calendar-save-form">Guardar jornada</button>
        </div>
    </dialog>
    <?php endif; ?>

    <script>
        (function () {
            var dialog = document.getElementById('jornada-dialog');
            var saveDialog = document.getElementById('calendar-save-dialog');
            var selectedDateHasAttendance = <?= $selectedDateHasAttendance ? 'true' : 'false'; ?>;

            if (dialog && window.location.hash === '#jornada-dialog' && !selectedDateHasAttendance && typeof dialog.showModal === 'function') {
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
            var courseMasters = Array.prototype.slice.call(dialog.querySelectorAll('[data-course-master]'));
            var modeNote = dialog.querySelector('[data-calendar-mode-note]');
            var closeButton = dialog.querySelector('[data-calendar-dialog-close]');
            var saveOpenButton = dialog.querySelector('[data-calendar-save-open]');
            var saveCloseButton = saveDialog ? saveDialog.querySelector('[data-calendar-save-close]') : null;
            var bulkOpenButton = document.querySelector('[data-calendar-bulk-open]');
            var calendarDays = Array.prototype.slice.call(document.querySelectorAll('.calendar-day.is-editable'));
            var daySelectionCount = document.querySelector('[data-calendar-selection-count]');
            var daySelectionCheckboxes = Array.prototype.slice.call(document.querySelectorAll('[data-calendar-day-select]'));
            var primaryDateInput = dialog.querySelector('[data-calendar-primary-date]');
            var selectedDateInputs = dialog.querySelector('[data-calendar-selected-date-inputs]');
            var dialogTitle = dialog.querySelector('[data-calendar-dialog-title]');
            var dialogHelp = dialog.querySelector('[data-calendar-dialog-help]');

            function closeDialog() {
                if (typeof dialog.close === 'function') {
                    dialog.close('cancel');
                }

                if (window.location.hash === '#jornada-dialog' && window.history && typeof window.history.replaceState === 'function') {
                    window.history.replaceState(null, document.title, window.location.pathname + window.location.search);
                }
            }

            function selectedDates() {
                return daySelectionCheckboxes.filter(function (checkbox) {
                    return checkbox.checked && !checkbox.disabled;
                }).map(function (checkbox) {
                    return checkbox.value;
                });
            }

            function refreshSelectionCount() {
                var count = selectedDates().length;

                if (daySelectionCount) {
                    daySelectionCount.textContent = count === 1 ? '1 fecha seleccionada' : String(count) + ' fechas seleccionadas';
                }
            }

            function setDialogDates(dates) {
                if (!primaryDateInput || !selectedDateInputs || dates.length === 0) {
                    return;
                }

                primaryDateInput.value = dates[0];
                selectedDateInputs.innerHTML = '';

                dates.forEach(function (date) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'fechas[]';
                    input.value = date;
                    selectedDateInputs.appendChild(input);
                });

                if (dialogTitle) {
                    dialogTitle.textContent = dates.length === 1 ? 'Configurar ' + dates[0] : 'Configurar ' + String(dates.length) + ' fechas';
                }

                if (dialogHelp) {
                    dialogHelp.textContent = dates.length === 1
                        ? 'Seleccione el tipo de jornada que se habilitara para este dia.'
                        : 'Seleccione el tipo de jornada que se aplicara a las fechas seleccionadas.';
                }
            }

            calendarDays.forEach(function (day) {
                day.addEventListener('click', function (event) {
                    if (event.target.closest('.calendar-day-checkbox')) {
                        return;
                    }

                    var link = day.querySelector('.calendar-day-edit');

                    if (link) {
                        window.location.href = link.href;
                    }
                });
            });

            function setCheckboxesForHour(hour, checked) {
                detailCheckboxes.forEach(function (checkbox) {
                    if (checkbox.getAttribute('data-hour-checkbox') === String(hour)) {
                        checkbox.checked = checked;
                    }
                });
            }

            function setCheckboxesForCourse(courseId, checked) {
                detailCheckboxes.forEach(function (checkbox) {
                    if (checkbox.getAttribute('data-course-checkbox') === String(courseId)) {
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

            function refreshCourseMasters() {
                courseMasters.forEach(function (master) {
                    var courseId = master.getAttribute('data-course-master');
                    var checkboxes = detailCheckboxes.filter(function (checkbox) {
                        return checkbox.getAttribute('data-course-checkbox') === courseId;
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

                courseMasters.forEach(function (master) {
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
                refreshCourseMasters();
            }

            hourMasters.forEach(function (master) {
                master.addEventListener('change', function () {
                    setCheckboxesForHour(master.getAttribute('data-hour-master'), master.checked);
                    refreshHourMasters();
                    refreshCourseMasters();
                });
            });

            courseMasters.forEach(function (master) {
                master.addEventListener('change', function () {
                    setCheckboxesForCourse(master.getAttribute('data-course-master'), master.checked);
                    refreshHourMasters();
                    refreshCourseMasters();
                });
            });

            detailCheckboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    refreshHourMasters();
                    refreshCourseMasters();
                });
            });

            if (typeSelect) {
                typeSelect.addEventListener('change', applyJourneyMode);
            }

            if (limitSelect) {
                limitSelect.addEventListener('change', applyJourneyMode);
            }

            if (closeButton) {
                closeButton.addEventListener('click', closeDialog);
            }

            if (saveOpenButton && saveDialog) {
                saveOpenButton.addEventListener('click', function () {
                    if (typeof saveDialog.showModal === 'function') {
                        saveDialog.showModal();
                    }
                });
            }

            if (saveCloseButton && saveDialog) {
                saveCloseButton.addEventListener('click', function () {
                    if (typeof saveDialog.close === 'function') {
                        saveDialog.close('cancel');
                    }
                });
            }

            daySelectionCheckboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', refreshSelectionCount);
            });

            if (bulkOpenButton) {
                bulkOpenButton.addEventListener('click', function () {
                    var dates = selectedDates();

                    if (dates.length === 0) {
                        window.alert('Seleccione al menos una fecha sin asistencia registrada.');
                        return;
                    }

                    setDialogDates(dates);

                    if (typeof dialog.showModal === 'function') {
                        dialog.showModal();
                    }
                });
            }

            setDialogDates([<?= json_encode((string) $selectedDate); ?>]);
            refreshSelectionCount();
            applyJourneyMode();
        }());
    </script>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
