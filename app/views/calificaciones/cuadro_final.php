<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$courses = is_array($courses ?? null) ? $courses : [];
$subjects = is_array($subjects ?? null) ? $subjects : [];
$rows = is_array($rows ?? null) ? $rows : [];
$selectedCourse = is_array($selectedCourse ?? null) ? $selectedCourse : false;
?>

<p class="module-note">Cuadro final por curso con promedios de materias, promedio general y estado academico.</p>

<section class="security-assignment-block print-hidden">
    <header class="security-assignment-header">
        <div>
            <h3>Filtros</h3>
            <p>Periodo: <strong><?= $h($currentPeriod['pledescripcion'] ?? 'Sin periodo'); ?></strong></p>
        </div>
    </header>

    <form class="data-form" method="GET" action="<?= $h(baseUrl('reportes/cuadro-final')); ?>">
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
        </div>
        <div class="actions-row">
            <button class="btn-secondary btn-inline" type="button" onclick="window.print()">
                <i class="fa fa-file-pdf-o" aria-hidden="true"></i>
                Imprimir / PDF
            </button>
        </div>
    </form>
</section>

<?php if ($selectedCourse === false): ?>
    <div class="empty-state">Selecciona un curso del periodo actual para generar el cuadro final.</div>
<?php else: ?>
    <section class="security-assignment-block final-chart-report-block report-print-landscape">
        <header class="security-assignment-header">
            <div>
                <h3>Cuadro final</h3>
                <p><?= $h($selectedCourse['granombre'] . ' ' . $selectedCourse['prlnombre']); ?></p>
            </div>
        </header>

        <?php if (empty($subjects)): ?>
            <div class="empty-state">Este curso no tiene materias visibles en libreta con perfil activo.</div>
        <?php else: ?>
            <div class="final-chart-heading">
                <strong><?= $h($appName ?? 'SGEap'); ?></strong>
                <span>Periodo educativo: <?= $h($currentPeriod['pledescripcion'] ?? ''); ?></span>
                <span>Curso: <?= $h($selectedCourse['granombre']); ?> | Paralelo: <?= $h($selectedCourse['prlnombre']); ?></span>
            </div>

            <div class="table-wrap gradebook-table-wrap final-chart-wrap">
                <table class="data-table final-chart-table">
                    <thead>
                        <tr>
                            <th rowspan="2" class="final-chart-order-col">ORD</th>
                            <th rowspan="2" class="final-chart-student-col">APELLIDOS Y NOMBRES</th>
                            <?php foreach ($subjects as $subject): ?>
                                <th colspan="3" class="final-chart-subject-head"><?= $h($subject['name']); ?></th>
                            <?php endforeach; ?>
                            <th rowspan="2" class="final-chart-average-col">PROM.</th>
                            <th rowspan="2" class="final-chart-status-col">ESTADO</th>
                        </tr>
                        <tr>
                            <?php foreach ($subjects as $_subject): ?>
                                <th>PP</th>
                                <th>ES</th>
                                <th>PF</th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $index => $row): ?>
                            <tr>
                                <td class="final-chart-order-col"><?= $h($index + 1); ?></td>
                                <td class="final-chart-student-col">
                                    <?= $h(($row['student']['perapellidos'] ?? '') . ' ' . ($row['student']['pernombres'] ?? '')); ?>
                                </td>
                                <?php foreach ($subjects as $subject): ?>
                                    <?php $cell = $row['subjects'][$subject['key']] ?? ['partial' => null, 'extra' => null, 'final' => null]; ?>
                                    <td><?= $cell['partial'] !== null ? $h(number_format((float) $cell['partial'], 2, ',', '')) : ''; ?></td>
                                    <td><?= $cell['extra'] !== null ? $h(number_format((float) $cell['extra'], 2, ',', '')) : ''; ?></td>
                                    <td class="<?= $cell['final'] !== null && (float) $cell['final'] < 7 ? 'is-low-average' : ''; ?>">
                                        <?= $cell['final'] !== null ? $h(number_format((float) $cell['final'], 2, ',', '')) : ''; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="final-chart-average-col <?= $row['average'] !== null && (float) $row['average'] < 7 ? 'is-low-average' : 'is-approved-average'; ?>">
                                    <?= $row['average'] !== null ? $h(number_format((float) $row['average'], 2, ',', '')) : ''; ?>
                                </td>
                                <td class="final-chart-status-col"><?= $h($row['status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="99">No hay estudiantes activos en este curso.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
