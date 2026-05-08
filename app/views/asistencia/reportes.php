<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$selectedCourseId = (int) ($selectedCourseId ?? 0);
$selectedStudentId = (int) ($selectedStudentId ?? 0);
$selectedCourseSubjectId = (int) ($selectedCourseSubjectId ?? 0);
$selectedTeacherPersonId = (int) ($selectedTeacherPersonId ?? 0);
$reportRows = is_array($reportRows ?? null) ? $reportRows : [];
$courseSubjects = is_array($courseSubjects ?? null) ? $courseSubjects : [];
$teachers = is_array($teachers ?? null) ? $teachers : [];
$totals = [
    'registros' => 0,
    'asistencias' => 0,
    'atrasos' => 0,
    'faltas_justificadas' => 0,
    'faltas_injustificadas' => 0,
];

foreach ($reportRows as $row) {
    $totals['registros'] += (int) $row['total_registros'];
    $totals['asistencias'] += (int) $row['total_asistencias'];
    $totals['atrasos'] += (int) $row['total_atrasos'];
    $totals['faltas_justificadas'] += (int) $row['total_faltas_justificadas'];
    $totals['faltas_injustificadas'] += (int) $row['total_faltas_injustificadas'];
}

$printedAt = date('Y-m-d H:i');
?>
<p class="module-note print-hidden">Reporte consolidado por estudiante. Use curso, materia o docente para revisar un grupo especifico. Los datos salen de sesiones cerradas o registradas y excluyen sesiones anuladas.</p>

<?php if ($currentPeriod === null): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para continuar.</div>
<?php else: ?>
    <section class="report-print-header print-only">
        <h1>Reporte de asistencia</h1>
        <p>
            Periodo: <?= htmlspecialchars((string) ($currentPeriod['plenombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
            | Rango: <?= htmlspecialchars((string) $startDate, ENT_QUOTES, 'UTF-8'); ?> a <?= htmlspecialchars((string) $endDate, ENT_QUOTES, 'UTF-8'); ?>
            | Generado: <?= htmlspecialchars($printedAt, ENT_QUOTES, 'UTF-8'); ?>
        </p>
    </section>

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
                        <span class="input-addon">Desde</span>
                        <input type="date" name="desde" value="<?= htmlspecialchars((string) $startDate, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Hasta</span>
                        <input type="date" name="hasta" value="<?= htmlspecialchars((string) $endDate, ENT_QUOTES, 'UTF-8'); ?>">
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
                <div>
                    <div class="input-group">
                        <span class="input-addon">Estudiante</span>
                        <select name="estid" data-report-student>
                            <option value="0">Todos</option>
                            <?php foreach ($students as $student): ?>
                                <option
                                    value="<?= htmlspecialchars((string) $student['estid'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-course="<?= htmlspecialchars((string) ($student['curid'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>"
                                    <?= $selectedStudentId === (int) $student['estid'] ? 'selected' : ''; ?>
                                >
                                    <?= htmlspecialchars((string) $student['perapellidos'] . ' ' . $student['pernombres'] . ' - ' . ($student['curso'] ?? 'Sin curso'), ENT_QUOTES, 'UTF-8'); ?>
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

    <section class="dashboard-grid dashboard-metrics-grid">
        <article class="summary-card">
            <span class="summary-label">Registros</span>
            <strong><?= (int) $totals['registros']; ?></strong>
            <p>Total de marcas de asistencia en el rango.</p>
        </article>
        <article class="summary-card">
            <span class="summary-label">Asistencias</span>
            <strong><?= (int) $totals['asistencias']; ?></strong>
            <p>Registros marcados como asistencia.</p>
        </article>
        <article class="summary-card">
            <span class="summary-label">Alertas</span>
            <strong><?= (int) ($totals['atrasos'] + $totals['faltas_justificadas'] + $totals['faltas_injustificadas']); ?></strong>
            <p>Atrasos y faltas justificadas o injustificadas.</p>
        </article>
    </section>

    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Consolidado</h3>
                <p><?= htmlspecialchars((string) $startDate, ENT_QUOTES, 'UTF-8'); ?> a <?= htmlspecialchars((string) $endDate, ENT_QUOTES, 'UTF-8'); ?>. Si combina filtros, el reporte muestra solo las asistencias que cumplen todos.</p>
            </div>
        </header>

        <?php if ($reportRows === []): ?>
            <div class="empty-state">No hay registros de asistencia para los filtros seleccionados.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Estudiante</th>
                            <th>Cedula</th>
                            <th>Curso</th>
                            <th>Registros</th>
                            <th>Asistencias</th>
                            <th>Atrasos</th>
                            <th>F. justificadas</th>
                            <th>F. injustificadas</th>
                            <th>Primera</th>
                            <th>Ultima</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportRows as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $row['perapellidos'] . ' ' . $row['pernombres'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) $row['percedula'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) $row['granombre'] . ' ' . $row['prlnombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= (int) $row['total_registros']; ?></td>
                                <td><?= (int) $row['total_asistencias']; ?></td>
                                <td><?= (int) $row['total_atrasos']; ?></td>
                                <td><?= (int) $row['total_faltas_justificadas']; ?></td>
                                <td><?= (int) $row['total_faltas_injustificadas']; ?></td>
                                <td><?= htmlspecialchars((string) $row['primera_fecha'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) $row['ultima_fecha'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <script>
        (function () {
            var course = document.querySelector('[data-report-course]');
            var subject = document.querySelector('[data-report-subject]');
            var student = document.querySelector('[data-report-student]');

            function filterByCourse(select) {
                if (!course || !select) {
                    return;
                }

                var courseId = course.value;

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
        }());
    </script>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
