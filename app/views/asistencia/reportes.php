<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$selectedCourseId = (int) ($selectedCourseId ?? 0);
$selectedStudentId = (int) ($selectedStudentId ?? 0);
$selectedCourseSubjectId = (int) ($selectedCourseSubjectId ?? 0);
$selectedTeacherPersonId = (int) ($selectedTeacherPersonId ?? 0);
$selectedMonth = (string) ($selectedMonth ?? date('Y-m'));
$availableMonths = is_array($availableMonths ?? null) ? $availableMonths : [];
$classDateRange = is_array($classDateRange ?? null) ? $classDateRange : null;
$classRangeStart = (string) ($classDateRange['start'] ?? '');
$classRangeEnd = (string) ($classDateRange['end'] ?? '');
$customRange = !empty($customRange);
$reportRows = is_array($reportRows ?? null) ? $reportRows : [];
$reportDates = is_array($reportDates ?? null) ? $reportDates : [];
$studentHourlyMatrix = is_array($studentHourlyMatrix ?? null) ? $studentHourlyMatrix : [];
$courses = is_array($courses ?? null) ? $courses : [];
$students = is_array($students ?? null) ? $students : [];
$courseSubjects = is_array($courseSubjects ?? null) ? $courseSubjects : [];
$teachers = is_array($teachers ?? null) ? $teachers : [];
$selectedCourse = null;
$selectedStudent = null;
$selectedSubject = null;
$selectedTeacher = null;
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
$weekdayLabels = [
    1 => 'L',
    2 => 'Ma',
    3 => 'Mi',
    4 => 'J',
    5 => 'V',
    6 => 'S',
    7 => 'D',
];
$selectedMonthTimestamp = strtotime($selectedMonth . '-01');
$matrixTitle = $customRange
    ? 'RANGO'
    : strtoupper($monthNames[(int) date('n', $selectedMonthTimestamp)] ?? $selectedMonth);
$printOrientationClass = count($reportDates) > 12 ? 'report-print-landscape' : 'report-print-portrait';

foreach ($courses as $course) {
    if ((int) $course['curid'] === $selectedCourseId) {
        $selectedCourse = $course;
        break;
    }
}

foreach ($students as $student) {
    if ((int) $student['estid'] === $selectedStudentId) {
        $selectedStudent = $student;
        break;
    }
}

foreach ($courseSubjects as $subject) {
    if ((int) $subject['mtcid'] === $selectedCourseSubjectId) {
        $selectedSubject = $subject;
        break;
    }
}

foreach ($teachers as $teacher) {
    if ((int) $teacher['perid'] === $selectedTeacherPersonId) {
        $selectedTeacher = $teacher;
        break;
    }
}

$legendItems = [
    'Periodo' => (string) ($currentPeriod['plenombre'] ?? $currentPeriod['pledescripcion'] ?? ''),
    'Mes' => $customRange ? 'Rango manual' : ($monthNames[(int) date('n', $selectedMonthTimestamp)] ?? $selectedMonth) . ' ' . date('Y', $selectedMonthTimestamp),
    'Rango' => (string) $startDate . ' a ' . (string) $endDate,
];

if (is_array($selectedCourse)) {
    $legendItems['Curso'] = trim((string) $selectedCourse['granombre'] . ' ' . (string) $selectedCourse['prlnombre'])
        . ', ' . (string) ($selectedCourse['nednombre'] ?? '');
}

if (is_array($selectedSubject)) {
    $legendItems['Materia'] = (string) $selectedSubject['mtcnombre_mostrar'];
}

if (is_array($selectedTeacher)) {
    $legendItems['Docente'] = trim((string) $selectedTeacher['perapellidos'] . ' ' . (string) $selectedTeacher['pernombres']);
}

if (is_array($selectedStudent)) {
    $studentLegend = trim((string) $selectedStudent['perapellidos'] . ' ' . (string) $selectedStudent['pernombres']);
    $studentDetails = array_filter([
        (string) ($selectedStudent['percedula'] ?? ''),
        (string) ($selectedStudent['curso'] ?? ''),
    ], static fn (string $value): bool => $value !== '');

    if ($studentDetails !== []) {
        $studentLegend .= ' (' . implode(', ', $studentDetails) . ')';
    }

    $legendItems['Estudiante'] = $studentLegend;
}

$showCourseColumn = $selectedCourseId <= 0 && $selectedStudentId <= 0;
$isStudentReport = $selectedStudentId > 0;
?>

<?php if ($currentPeriod === null): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para continuar.</div>
<?php else: ?>
    <section class="security-assignment-block print-hidden">
        <header class="security-assignment-header">
            <div>
                <h3>Filtros del reporte</h3>
                <p>Seleccione rango, curso, materia, docente o estudiante para consolidar asistencia.</p>
            </div>
        </header>

        <form class="data-form" method="GET" action="<?= htmlspecialchars(baseUrl('reportes/asistencia'), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-grid">
                <div>
                    <div class="input-group">
                        <span class="input-addon">Mes</span>
                        <select name="mes" data-report-month>
                            <?php if ($availableMonths === []): ?>
                                <option value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php else: ?>
                                <?php foreach ($availableMonths as $monthOption): ?>
                                    <?php $monthOptionTimestamp = strtotime((string) $monthOption . '-01'); ?>
                                    <option value="<?= htmlspecialchars((string) $monthOption, ENT_QUOTES, 'UTF-8'); ?>" <?= (string) $monthOption === $selectedMonth ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars(($monthNames[(int) date('n', $monthOptionTimestamp)] ?? (string) $monthOption) . ' ' . date('Y', $monthOptionTimestamp), ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="permission-option">
                        <input type="checkbox" name="rango_manual" value="1" data-report-custom-range <?= $customRange ? 'checked' : ''; ?>>
                        <span>Usar fechas manuales</span>
                    </label>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Desde</span>
                        <input
                            type="date"
                            name="desde"
                            value="<?= htmlspecialchars((string) $startDate, ENT_QUOTES, 'UTF-8'); ?>"
                            <?= $classRangeStart !== '' ? 'min="' . htmlspecialchars($classRangeStart, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>
                            <?= $classRangeEnd !== '' ? 'max="' . htmlspecialchars($classRangeEnd, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>
                            data-report-start
                            <?= $customRange ? '' : 'disabled'; ?>
                        >
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Hasta</span>
                        <input
                            type="date"
                            name="hasta"
                            value="<?= htmlspecialchars((string) $endDate, ENT_QUOTES, 'UTF-8'); ?>"
                            <?= $classRangeStart !== '' ? 'min="' . htmlspecialchars($classRangeStart, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>
                            <?= $classRangeEnd !== '' ? 'max="' . htmlspecialchars($classRangeEnd, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>
                            data-report-end
                            <?= $customRange ? '' : 'disabled'; ?>
                        >
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Curso</span>
                        <select name="curid" data-report-course>
                            <option value="0">Todos</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= htmlspecialchars((string) $course['curid'], ENT_QUOTES, 'UTF-8'); ?>" <?= $selectedCourseId === (int) $course['curid'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars((string) $course['granombre'] . ' ' . $course['prlnombre'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Estudiante</span>
                        <select name="estid" data-report-student <?= $selectedCourseId > 0 ? '' : 'disabled'; ?>>
                            <option value="0">Seleccione curso</option>
                            <?php foreach ($students as $student): ?>
                                <option
                                    value="<?= htmlspecialchars((string) $student['estid'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-course="<?= htmlspecialchars((string) ($student['curid'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>"
                                    <?= $selectedStudentId === (int) $student['estid'] ? 'selected' : ''; ?>
                                >
                                    <?= htmlspecialchars((string) $student['perapellidos'] . ' ' . $student['pernombres'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Materia</span>
                        <select name="mtcid" data-report-subject>
                            <option value="0">Todas</option>
                            <?php foreach ($courseSubjects as $subject): ?>
                                <option
                                    value="<?= htmlspecialchars((string) $subject['mtcid'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-course="<?= htmlspecialchars((string) $subject['curid'], ENT_QUOTES, 'UTF-8'); ?>"
                                    <?= $selectedCourseSubjectId === (int) $subject['mtcid'] ? 'selected' : ''; ?>
                                >
                                    <?= htmlspecialchars((string) $subject['mtcnombre_mostrar'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Docente</span>
                        <select name="perid_docente">
                            <option value="0">Todos</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= htmlspecialchars((string) $teacher['perid'], ENT_QUOTES, 'UTF-8'); ?>" <?= $selectedTeacherPersonId === (int) $teacher['perid'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars((string) $teacher['perapellidos'] . ' ' . $teacher['pernombres'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="actions-row">
                <button class="btn-primary btn-inline" type="submit">Generar reporte</button>
                <button class="btn-secondary btn-inline" type="button" onclick="window.print()">Imprimir / PDF</button>
            </div>
        </form>
    </section>

    <section class="security-assignment-block report-results-block <?= htmlspecialchars($printOrientationClass, ENT_QUOTES, 'UTF-8'); ?>">
        <header class="security-assignment-header">
            <div>
                <h3>Consolidado</h3>
                <p>El reporte muestra solo las asistencias que cumplen los filtros aplicados.</p>
            </div>
        </header>

        <dl class="report-table-legend">
            <?php foreach ($legendItems as $label => $value): ?>
                <?php if ((string) $value !== ''): ?>
                    <div>
                        <dt><?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></dt>
                        <dd><?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?></dd>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </dl>

        <?php if ($isStudentReport && $studentHourlyMatrix === []): ?>
            <div class="empty-state">No hay registros de asistencia por horas para el estudiante seleccionado.</div>
        <?php elseif ($isStudentReport): ?>
            <?php foreach ($studentHourlyMatrix as $monthBlock): ?>
                <?php
                $monthKey = (string) ($monthBlock['month'] ?? '');
                $monthTimestamp = strtotime($monthKey . '-01');
                $monthLabel = $monthTimestamp !== false
                    ? ($monthNames[(int) date('n', $monthTimestamp)] ?? $monthKey)
                    : $monthKey;
                $summary = is_array($monthBlock['summary'] ?? null) ? $monthBlock['summary'] : [];
                ?>
                <div class="student-hourly-matrix-block">
                    <div class="table-wrap">
                        <table class="data-table student-hourly-matrix-table">
                            <thead>
                                <tr>
                                    <th class="matrix-date-column"></th>
                                    <th colspan="7">Horas Clases</th>
                                </tr>
                                <tr>
                                    <th><?= htmlspecialchars($monthLabel, ENT_QUOTES, 'UTF-8'); ?></th>
                                    <?php foreach (range(1, 7) as $hour): ?>
                                        <th><?= $hour; ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($monthBlock['dates'] ?? []) as $dateRow): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) date('j', strtotime((string) $dateRow['date'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <?php foreach (range(1, 7) as $hour): ?>
                                            <td><?= htmlspecialchars((string) ($dateRow['hours'][$hour] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <table class="student-hourly-summary-table">
                        <tbody>
                            <tr>
                                <th>Resumen</th>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Asistidos</td>
                                <td><?= (int) ($summary['asistidos'] ?? 0); ?></td>
                            </tr>
                            <tr>
                                <td>Faltas Justificadas</td>
                                <td><?= (int) ($summary['faltas_justificadas'] ?? 0); ?></td>
                            </tr>
                            <tr>
                                <td>Faltas Injustificadas</td>
                                <td><?= (int) ($summary['faltas_injustificadas'] ?? 0); ?></td>
                            </tr>
                            <tr>
                                <td>Atrasos</td>
                                <td><?= (int) ($summary['atrasos'] ?? 0); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
            <p class="report-matrix-note">As= Asistencia &nbsp;&nbsp; At= Atraso &nbsp;&nbsp; FJ= Falta Justificada &nbsp;&nbsp; FI= Falta Injustificada</p>
        <?php elseif ($reportRows === []): ?>
            <div class="empty-state">No hay registros de asistencia para los filtros seleccionados.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table attendance-matrix-table">
                    <thead>
                        <tr>
                            <th class="matrix-student-column"></th>
                            <?php if ($showCourseColumn): ?>
                                <th class="matrix-course-column"></th>
                            <?php endif; ?>
                            <th colspan="<?= count($reportDates); ?>"><?= htmlspecialchars($matrixTitle, ENT_QUOTES, 'UTF-8'); ?></th>
                            <th colspan="4">Consolidado</th>
                        </tr>
                        <tr>
                            <th class="matrix-student-column"></th>
                            <?php if ($showCourseColumn): ?>
                                <th class="matrix-course-column"></th>
                            <?php endif; ?>
                            <?php foreach ($reportDates as $date): ?>
                                <th><?= htmlspecialchars($weekdayLabels[(int) date('N', strtotime((string) $date))] ?? '', ENT_QUOTES, 'UTF-8'); ?></th>
                            <?php endforeach; ?>
                            <th>As</th>
                            <th>A</th>
                            <th>FJ</th>
                            <th>FI</th>
                        </tr>
                        <tr>
                            <th class="matrix-student-column">Nombres</th>
                            <?php if ($showCourseColumn): ?>
                                <th class="matrix-course-column">Curso</th>
                            <?php endif; ?>
                            <?php foreach ($reportDates as $date): ?>
                                <th><?= htmlspecialchars((string) date('j', strtotime((string) $date)), ENT_QUOTES, 'UTF-8'); ?></th>
                            <?php endforeach; ?>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportRows as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $row['perapellidos'] . ' ' . $row['pernombres'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <?php if ($showCourseColumn): ?>
                                    <td><?= htmlspecialchars((string) $row['granombre'] . ' ' . $row['prlnombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <?php endif; ?>
                                <?php foreach ($reportDates as $date): ?>
                                    <td class="matrix-day-cell"><?= htmlspecialchars((string) ($row['dias'][$date] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <?php endforeach; ?>
                                <td class="matrix-total-cell"><?= (int) $row['total_asistencias']; ?></td>
                                <td class="matrix-total-cell"><?= (int) $row['total_atrasos']; ?></td>
                                <td class="matrix-total-cell"><?= (int) $row['total_faltas_justificadas']; ?></td>
                                <td class="matrix-total-cell"><?= (int) $row['total_faltas_injustificadas']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="report-matrix-note">As= Asistido &nbsp;&nbsp; A= Atraso &nbsp;&nbsp; FJ= Falta Justificada &nbsp;&nbsp; FI= Falta Injustificada</p>
        <?php endif; ?>
    </section>

    <script>
        (function () {
            var course = document.querySelector('[data-report-course]');
            var subject = document.querySelector('[data-report-subject]');
            var student = document.querySelector('[data-report-student]');
            var month = document.querySelector('[data-report-month]');
            var customRange = document.querySelector('[data-report-custom-range]');
            var start = document.querySelector('[data-report-start]');
            var end = document.querySelector('[data-report-end]');

            function updateRangeFields() {
                if (!customRange || !start || !end) {
                    return;
                }

                start.disabled = !customRange.checked;
                end.disabled = !customRange.checked;

                if (!customRange.checked && month && month.value) {
                    start.value = month.value + '-01';
                    var parts = month.value.split('-');
                    var lastDay = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10), 0).getDate();
                    end.value = month.value + '-' + String(lastDay).padStart(2, '0');

                    if (start.min && start.value < start.min) {
                        start.value = start.min;
                    }

                    if (end.max && end.value > end.max) {
                        end.value = end.max;
                    }
                }
            }

            function filterByCourse(select) {
                if (!course || !select) {
                    return;
                }

                var courseId = course.value;
                var isStudentSelect = select.hasAttribute('data-report-student');

                if (isStudentSelect) {
                    select.disabled = courseId === '0';
                    if (select.options.length > 0) {
                        select.options[0].textContent = courseId === '0' ? 'Seleccione curso' : 'Todos';
                    }
                    if (courseId === '0') {
                        select.value = '0';
                    }
                }

                Array.prototype.forEach.call(select.options, function (option) {
                    var optionCourse = option.getAttribute('data-course');
                    var visible = option.value === '0' || courseId === '0' || optionCourse === courseId;

                    option.hidden = !visible;
                    option.disabled = !visible;
                });

                if (select.selectedOptions.length > 0 && select.selectedOptions[0].disabled) {
                    select.value = '0';
                }
            }

            if (course) {
                course.addEventListener('change', function () {
                    filterByCourse(subject);
                    filterByCourse(student);
                });
                filterByCourse(subject);
                filterByCourse(student);
            }

            if (customRange) {
                customRange.addEventListener('change', updateRangeFields);
            }

            if (month) {
                month.addEventListener('change', updateRangeFields);
            }

            updateRangeFields();
        }());
    </script>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
