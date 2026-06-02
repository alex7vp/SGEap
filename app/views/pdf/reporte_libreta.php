<?php

declare(strict_types=1);

$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$rows = is_array($rows ?? null) ? $rows : [];
$subperiods = is_array($subperiods ?? null) ? $subperiods : [];
$visibleSubperiods = is_array($visibleSubperiods ?? null) ? $visibleSubperiods : [];
$componentColumns = is_array($componentColumns ?? null) ? $componentColumns : [];
$attendanceSummary = is_array($attendanceSummary ?? null) ? $attendanceSummary : [];
$selectedCourse = is_array($selectedCourse ?? null) ? $selectedCourse : false;
$selectedStudent = is_array($selectedStudent ?? null) ? $selectedStudent : false;
$selectedSubperiod = is_array($selectedSubperiod ?? null) ? $selectedSubperiod : false;
$selectedOrder = (int) ($selectedSubperiod['spcorden'] ?? 0);
$previousSubperiods = array_values(array_filter($visibleSubperiods, static fn (array $subperiod): bool => (int) ($subperiod['spcorden'] ?? 0) < $selectedOrder));
$showAnnualFinal = $selectedOrder > 0 && $selectedOrder >= count($subperiods);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 20px 24px; }
        body { color: #1f2933; font-family: DejaVu Sans, sans-serif; font-size: 8px; }
        h1, p { margin: 0; }
        .heading { text-align: center; border-bottom: 1px solid #9fb3c8; padding-bottom: 8px; margin-bottom: 10px; }
        .heading strong { display: block; font-size: 15px; text-transform: uppercase; }
        .heading span { display: block; margin-top: 3px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #e6eef6; color: #243b53; }
        th, td { border: 1px solid #cbd5e1; padding: 3px; text-align: center; vertical-align: middle; }
        td.subject, th.subject { text-align: left; }
        .low { color: #b42318; font-weight: 700; }
        .ok { color: #027a48; font-weight: 700; }
        .summary { width: 55%; margin-top: 10px; }
        .empty { border: 1px solid #d9e2ec; padding: 14px; text-align: center; }
    </style>
</head>
<body>
    <?php if ($selectedCourse === false || $selectedStudent === false || $selectedSubperiod === false): ?>
        <div class="empty">No existen datos suficientes para generar la libreta.</div>
    <?php else: ?>
        <div class="heading">
            <strong><?= $h($appName ?? 'SGEap'); ?></strong>
            <span><?= $h($currentPeriod['pledescripcion'] ?? ''); ?></span>
            <span><?= $h(($selectedCourse['granombre'] ?? '') . ' ' . ($selectedCourse['prlnombre'] ?? '')); ?> | <?= $h($selectedSubperiod['spcnombre'] ?? ''); ?></span>
            <span>Estudiante: <?= $h(($selectedStudent['perapellidos'] ?? '') . ' ' . ($selectedStudent['pernombres'] ?? '')); ?></span>
        </div>

        <table>
            <thead>
                <tr>
                    <th rowspan="2" class="subject">Asignatura</th>
                    <?php foreach ($previousSubperiods as $subperiod): ?><th rowspan="2"><?= $h($subperiod['spcnombre'] ?? ''); ?></th><?php endforeach; ?>
                    <?php foreach ($componentColumns as $component): ?><th colspan="2"><?= $h($component['name'] ?? ''); ?></th><?php endforeach; ?>
                    <th rowspan="2"><?= $h($selectedSubperiod['spcnombre'] ?? 'Prom.'); ?></th>
                    <?php if ($showAnnualFinal): ?><th rowspan="2">Prom. Final</th><?php endif; ?>
                    <th rowspan="2">Equiv.</th>
                </tr>
                <tr>
                    <?php foreach ($componentColumns as $component): ?>
                        <?php $weight = is_numeric($component['weight'] ?? null) ? number_format((float) $component['weight'], 0, ',', '') : ''; ?>
                        <th>Cuan</th><th><?= $weight !== '' ? $h($weight) . '%' : 'Pond.'; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="subject"><?= $h($row['name'] ?? ''); ?></td>
                        <?php foreach ($previousSubperiods as $subperiod): ?>
                            <?php $subperiodScore = $row['subperiods'][(int) $subperiod['spcid']] ?? 0.0; ?>
                            <td><?= $h(number_format((float) $subperiodScore, 2, ',', '')); ?></td>
                        <?php endforeach; ?>
                        <?php foreach ($componentColumns as $component): ?>
                            <?php $componentScore = $row['components'][(int) $component['id']] ?? ['average' => 0.0, 'result' => 0.0]; ?>
                            <td><?= $h(number_format((float) ($componentScore['average'] ?? 0.0), 2, ',', '')); ?></td>
                            <td><?= $h(number_format((float) ($componentScore['result'] ?? 0.0), 2, ',', '')); ?></td>
                        <?php endforeach; ?>
                        <?php $selectedScore = (float) ($row['selected_score'] ?? $row['score'] ?? 0); ?>
                        <td class="<?= $selectedScore < 7 ? 'low' : 'ok'; ?>"><?= $h(number_format($selectedScore, 2, ',', '')); ?></td>
                        <?php if ($showAnnualFinal): ?>
                            <?php $annualScore = (float) ($row['score'] ?? 0); ?>
                            <td class="<?= $annualScore < 7 ? 'low' : 'ok'; ?>"><?= $h(number_format($annualScore, 2, ',', '')); ?></td>
                        <?php endif; ?>
                        <td><?= $h($row['equivalence'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($rows === []): ?><tr><td colspan="99">No existen materias visibles para libreta.</td></tr><?php endif; ?>
            </tbody>
            <?php if (($generalAverage ?? null) !== null): ?>
                <tfoot>
                    <tr>
                        <th colspan="<?= $h(1 + count($previousSubperiods) + (count($componentColumns) * 2)); ?>">Promedio general</th>
                        <th><?= $h(number_format((float) $generalAverage, 2, ',', '')); ?></th>
                        <?php if ($showAnnualFinal): ?><th><?= ($annualGeneralAverage ?? null) !== null ? $h(number_format((float) $annualGeneralAverage, 2, ',', '')) : ''; ?></th><?php endif; ?>
                        <th></th>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>

        <table class="summary">
            <tr><th colspan="2">Asistencia acumulada</th></tr>
            <tr><td>Total asistencias</td><td><?= (int) ($attendanceSummary['total_asistencias'] ?? 0); ?></td></tr>
            <tr><td>Atrasos</td><td><?= (int) ($attendanceSummary['total_atrasos'] ?? 0); ?></td></tr>
            <tr><td>Faltas justificadas</td><td><?= (int) ($attendanceSummary['total_faltas_justificadas'] ?? 0); ?></td></tr>
            <tr><td>Faltas injustificadas</td><td><?= (int) ($attendanceSummary['total_faltas_injustificadas'] ?? 0); ?></td></tr>
        </table>
    <?php endif; ?>
</body>
</html>
