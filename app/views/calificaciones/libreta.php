<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$courses = is_array($courses ?? null) ? $courses : [];
$students = is_array($students ?? null) ? $students : [];
$subperiods = is_array($subperiods ?? null) ? $subperiods : [];
$visibleSubperiods = is_array($visibleSubperiods ?? null) ? $visibleSubperiods : [];
$componentColumns = is_array($componentColumns ?? null) ? $componentColumns : [];
$termGeneralAverages = is_array($termGeneralAverages ?? null) ? $termGeneralAverages : [];
$rows = is_array($rows ?? null) ? $rows : [];
$scaleRows = is_array($scaleRows ?? null) ? $scaleRows : [];
$attendanceSummary = is_array($attendanceSummary ?? null) ? $attendanceSummary : [];
$attendanceBySubperiod = is_array($attendanceBySubperiod ?? null) ? $attendanceBySubperiod : [];
$annualGeneralAverage = $annualGeneralAverage ?? null;
$selectedCourse = is_array($selectedCourse ?? null) ? $selectedCourse : false;
$selectedStudent = is_array($selectedStudent ?? null) ? $selectedStudent : false;
$selectedSubperiod = is_array($selectedSubperiod ?? null) ? $selectedSubperiod : false;
$selectedOrder = (int) ($selectedSubperiod['spcorden'] ?? 0);
$previousSubperiods = array_values(array_filter(
    $visibleSubperiods,
    static fn (array $subperiod): bool => (int) ($subperiod['spcorden'] ?? 0) < $selectedOrder
));
$showAnnualFinal = $selectedOrder > 0 && $selectedOrder >= count($subperiods);
$chartSubperiods = $visibleSubperiods;
$chartPayload = [
    'labels' => array_map(static fn (array $row): string => (string) $row['name'], $rows),
    'datasets' => array_map(
        static fn (array $subperiod): array => [
            'label' => (string) $subperiod['spcnombre'],
            'data' => array_map(
                static fn (array $row): float => (float) ($row['subperiods'][(int) $subperiod['spcid']] ?? 0.0),
                $rows
            ),
        ],
        $chartSubperiods
    ),
];
?>

<p class="module-note">Libreta parcial por estudiante, curso y subperiodo. Los valores se calculan desde las actividades registradas.</p>

<section class="security-assignment-block print-hidden">
    <header class="security-assignment-header">
        <div>
            <h3>Filtros</h3>
            <p>Periodo: <strong><?= $h($currentPeriod['pledescripcion'] ?? 'Sin periodo'); ?></strong></p>
        </div>
    </header>

    <form class="data-form" method="GET" action="<?= $h(baseUrl('reportes/libreta')); ?>">
        <div class="form-grid">
            <div class="input-group">
                <span class="input-addon">Curso</span>
                <select name="curid" onchange="this.form.submit()">
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= $h($course['curid']); ?>" <?= (int) ($selectedCourseId ?? 0) === (int) $course['curid'] ? 'selected' : ''; ?>>
                            <?= $h($course['granombre'] . ' ' . $course['prlnombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-group">
                <span class="input-addon">Estudiante</span>
                <select name="matid" onchange="this.form.submit()">
                    <?php foreach ($students as $student): ?>
                        <option value="<?= $h($student['matid']); ?>" <?= (int) ($selectedMatriculationId ?? 0) === (int) $student['matid'] ? 'selected' : ''; ?>>
                            <?= $h($student['perapellidos'] . ' ' . $student['pernombres']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-group">
                <span class="input-addon">Trimestre</span>
                <select name="spcid" onchange="this.form.submit()">
                    <?php foreach ($subperiods as $subperiod): ?>
                        <option value="<?= $h($subperiod['spcid']); ?>" <?= (int) ($selectedSubperiodId ?? 0) === (int) $subperiod['spcid'] ? 'selected' : ''; ?>>
                            <?= $h($subperiod['spcnombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="actions-row">
            <button class="btn-secondary btn-inline" type="submit" name="pdf" value="1" formtarget="_blank">
                <i class="fa fa-file-pdf-o" aria-hidden="true"></i>
                Imprimir / PDF
            </button>
        </div>
    </form>
</section>

<?php if ($selectedCourse === false): ?>
    <div class="empty-state">Selecciona un curso para generar la libreta.</div>
<?php elseif ($selectedStudent === false): ?>
    <div class="empty-state">No hay estudiantes activos en el curso seleccionado.</div>
<?php elseif ($selectedSubperiod === false): ?>
    <div class="empty-state">No hay trimestres configurados para el perfil activo del curso.</div>
<?php else: ?>
    <section class="security-assignment-block report-card-block">
        <div class="report-card-heading">
            <strong><?= $h($institutionName ?? $appName ?? 'SGEap'); ?></strong>
            <span><?= $h($currentPeriod['pledescripcion'] ?? ''); ?></span>
            <span><?= $h($selectedCourse['granombre'] . ' ' . $selectedCourse['prlnombre']); ?> | <?= $h($selectedSubperiod['spcnombre']); ?></span>
            <span>Estudiante: <?= $h($selectedStudent['perapellidos'] . ' ' . $selectedStudent['pernombres']); ?></span>
        </div>

        <div class="report-card-grid">
            <div class="table-wrap gradebook-table-wrap">
                <table class="data-table report-card-table">
                    <thead>
                        <tr>
                            <th rowspan="2">Asignatura</th>
                            <?php foreach ($previousSubperiods as $subperiod): ?>
                                <th rowspan="2"><?= $h($subperiod['spcnombre']); ?></th>
                            <?php endforeach; ?>
                            <?php foreach ($componentColumns as $component): ?>
                                <th colspan="2" class="report-card-selected-term"><?= $h($component['name']); ?></th>
                            <?php endforeach; ?>
                            <th rowspan="2" class="report-card-selected-term"><?= $h($selectedSubperiod['spcnombre'] ?? 'Prom.'); ?></th>
                            <?php if ($showAnnualFinal): ?>
                                <th rowspan="2">Prom. Final</th>
                            <?php endif; ?>
                            <th rowspan="2">Equiv.</th>
                        </tr>
                        <tr>
                            <?php foreach ($componentColumns as $component): ?>
                                <?php
                                $weight = is_numeric($component['weight'] ?? null)
                                    ? number_format((float) $component['weight'], 0, ',', '')
                                    : '';
                                ?>
                                <th class="report-card-selected-term">Cuan</th>
                                <th class="report-card-selected-term"><?= $weight !== '' ? $h($weight) . '%' : 'Pond.'; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td class="report-card-subject"><?= $h($row['name']); ?></td>
                                <?php foreach ($previousSubperiods as $subperiod): ?>
                                    <?php $subperiodScore = $row['subperiods'][(int) $subperiod['spcid']] ?? 0.0; ?>
                                    <td><?= $h(number_format((float) $subperiodScore, 2, ',', '')); ?></td>
                                <?php endforeach; ?>
                                <?php foreach ($componentColumns as $component): ?>
                                    <?php $componentScore = $row['components'][(int) $component['id']] ?? ['average' => 0.0, 'result' => 0.0]; ?>
                                    <td class="report-card-selected-term"><?= $h(number_format((float) ($componentScore['average'] ?? 0.0), 2, ',', '')); ?></td>
                                    <td class="report-card-selected-term"><?= $h(number_format((float) ($componentScore['result'] ?? 0.0), 2, ',', '')); ?></td>
                                <?php endforeach; ?>
                                <td class="report-card-selected-term <?= (float) ($row['selected_score'] ?? $row['score']) < 7 ? 'is-low-average' : 'is-approved-average'; ?>">
                                    <?= $h(number_format((float) ($row['selected_score'] ?? $row['score']), 2, ',', '')); ?>
                                </td>
                                <?php if ($showAnnualFinal): ?>
                                    <td class="<?= (float) $row['score'] < 7 ? 'is-low-average' : 'is-approved-average'; ?>">
                                        <?= $h(number_format((float) $row['score'], 2, ',', '')); ?>
                                    </td>
                                <?php endif; ?>
                                <td><?= $h($row['equivalence']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="99">No existen materias visibles para libreta.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if ($generalAverage !== null): ?>
                        <tfoot>
                            <tr>
                                <th colspan="<?= $h(1 + count($previousSubperiods) + (count($componentColumns) * 2)); ?>">Promedio general</th>
                                <th><?= $h(number_format((float) $generalAverage, 2, ',', '')); ?></th>
                                <?php if ($showAnnualFinal): ?>
                                    <th><?= $annualGeneralAverage !== null ? $h(number_format((float) $annualGeneralAverage, 2, ',', '')) : ''; ?></th>
                                <?php endif; ?>
                                <th></th>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <div class="report-card-chart-panel">
            <canvas data-report-card-chart data-report-card='<?= $h(json_encode($chartPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>'></canvas>
        </div>

        <section class="report-card-attendance">
            <h3>Asistencia acumulada</h3>
            <div class="table-wrap">
                <table class="data-table report-card-attendance-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th></th>
                            <?php foreach ($visibleSubperiods as $subperiod): ?>
                                <th><?= $h($subperiod['spcnombre']); ?></th>
                            <?php endforeach; ?>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th rowspan="2">Asistencia</th>
                            <th>Dias asistidos</th>
                            <?php foreach ($visibleSubperiods as $subperiod): ?>
                                <?php $attendance = $attendanceBySubperiod[(int) $subperiod['spcid']] ?? []; ?>
                                <td><?= $h($attendance['dias_asistidos'] ?? 0); ?></td>
                            <?php endforeach; ?>
                            <td class="report-card-total-cell"><?= $h($attendanceSummary['dias_asistidos'] ?? 0); ?></td>
                        </tr>
                        <tr>
                            <th>Dias inasistidos</th>
                            <?php foreach ($visibleSubperiods as $subperiod): ?>
                                <?php $attendance = $attendanceBySubperiod[(int) $subperiod['spcid']] ?? []; ?>
                                <td><?= $h($attendance['dias_con_falta'] ?? 0); ?></td>
                            <?php endforeach; ?>
                            <td class="report-card-total-cell"><?= $h($attendanceSummary['dias_con_falta'] ?? 0); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="report-card-reference">
            <div class="table-wrap">
                <table class="data-table report-card-reference-table">
                    <thead>
                        <tr>
                            <th colspan="3">Descripcion Referencial</th>
                            <th>Equiv.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($scaleRows as $scale): ?>
                            <tr>
                                <td class="report-card-reference-score">
                                    <?= $h($scale['ecavalor_minimo'] !== null ? number_format((float) $scale['ecavalor_minimo'], 0, ',', '') : ''); ?>
                                </td>
                                <td class="report-card-reference-code"><?= $h($scale['ecacodigo']); ?></td>
                                <td><?= $h($scale['ecadescripcion'] ?? $scale['ecanombre'] ?? ''); ?></td>
                                <td class="report-card-reference-equivalence"><?= $h($scale['ecanombre']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($scaleRows)): ?>
                            <tr><td colspan="4">Este perfil no tiene descripcion referencial configurada.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
