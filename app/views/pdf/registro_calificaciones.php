<?php

declare(strict_types=1);

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$currentPeriod = is_array($currentPeriod ?? null) ? $currentPeriod : [];
$selectedSubject = is_array($selectedSubject ?? null) ? $selectedSubject : [];
$subperiods = is_array($subperiods ?? null) ? $subperiods : [];
$components = is_array($components ?? null) ? $components : [];
$activities = is_array($activities ?? null) ? $activities : [];
$grades = is_array($grades ?? null) ? $grades : [];
$students = is_array($students ?? null) ? $students : [];
$showFinalAverages = !empty($showFinalAverages);
$selectedSubperiodId = (int) ($selectedSubperiodId ?? 0);
$selectedSubperiod = null;

foreach ($subperiods as $subperiod) {
    if ((int) ($subperiod['spcid'] ?? 0) === $selectedSubperiodId) {
        $selectedSubperiod = $subperiod;
        break;
    }
}

$componentShortName = static function (array $component): string {
    $name = strtoupper((string) ($component['cpcnombre'] ?? ''));

    if (str_contains($name, 'SUM')) {
        return 'S';
    }

    if (str_contains($name, 'FORM')) {
        return 'F';
    }

    return strtoupper(substr(trim((string) ($component['cpcnombre'] ?? 'A')), 0, 1));
};

$subjectTitle = trim((string) (($selectedSubject['asgnombre'] ?? '') . ' | ' . ($selectedSubject['granombre'] ?? '') . ' ' . ($selectedSubject['prlnombre'] ?? '')));
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 22px 24px; }
        body { color: #1f2933; font-family: DejaVu Sans, sans-serif; font-size: 8px; }
        h1, p { margin: 0; }
        .header { border-bottom: 1px solid #9fb3c8; margin-bottom: 10px; padding-bottom: 8px; }
        .school { font-size: 15px; font-weight: 700; text-transform: uppercase; }
        .meta { color: #52606d; font-size: 8px; margin-top: 3px; }
        .title { font-size: 12px; font-weight: 700; margin-top: 7px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #e6eef6; color: #243b53; font-weight: 700; }
        th, td { border: 1px solid #cbd5e1; padding: 3px 4px; vertical-align: middle; }
        .student { width: 190px; text-align: left; }
        .center { text-align: center; }
        .result { background: #f8fafc; font-weight: 700; }
        .average-low { color: #b42318; font-weight: 700; }
        .average-ok { color: #0f766e; font-weight: 700; }
        .empty { border: 1px solid #d9e2ec; padding: 14px; text-align: center; color: #52606d; }
    </style>
</head>
<body>
    <header class="header">
        <p class="school"><?= $h($appName ?? 'SGEap'); ?></p>
        <p class="meta">Periodo lectivo: <?= $h($currentPeriod['pledescripcion'] ?? 'Sin periodo'); ?> | Generado: <?= $h(date('Y-m-d H:i')); ?></p>
        <p class="title"><?= $h($showFinalAverages ? 'Promedios finales' : 'Registro de calificaciones'); ?></p>
        <p class="meta">
            <?= $h($subjectTitle); ?>
            <?php if (!$showFinalAverages && is_array($selectedSubperiod)): ?>
                | <?= $h($selectedSubperiod['spcnombre'] ?? 'Subperiodo'); ?>
            <?php endif; ?>
        </p>
    </header>

    <?php if ($students === []): ?>
        <div class="empty">No hay estudiantes activos en el curso.</div>
    <?php elseif ($showFinalAverages): ?>
        <table>
            <thead>
                <tr>
                    <th rowspan="2" class="student">Apellidos y nombres</th>
                    <?php foreach ($subperiods as $subperiod): ?>
                        <?php $subperiodComponents = $components[(int) ($subperiod['spcid'] ?? 0)] ?? []; ?>
                        <th colspan="<?= $h((string) (count($subperiodComponents) + 1)); ?>" class="center"><?= $h($subperiod['spcnombre'] ?? ''); ?></th>
                    <?php endforeach; ?>
                    <th rowspan="2" class="center">FINAL</th>
                </tr>
                <tr>
                    <?php foreach ($subperiods as $subperiod): ?>
                        <?php foreach (($components[(int) ($subperiod['spcid'] ?? 0)] ?? []) as $component): ?>
                            <th class="center"><?= $h($componentShortName($component)); ?><?= !empty($component['cpcpeso']) ? ' ' . $h(number_format((float) $component['cpcpeso'], 0, ',', '')) . '%' : ''; ?></th>
                        <?php endforeach; ?>
                        <th class="center">PROM</th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <?php $finalAverageSum = 0.0; $finalAverageCount = 0; ?>
                    <tr>
                        <td><?= $h(trim((string) (($student['perapellidos'] ?? '') . ' ' . ($student['pernombres'] ?? '')))); ?></td>
                        <?php foreach ($subperiods as $subperiod): ?>
                            <?php $subperiodId = (int) ($subperiod['spcid'] ?? 0); $subperiodAverageParts = []; ?>
                            <?php foreach (($components[$subperiodId] ?? []) as $component): ?>
                                <?php
                                $componentId = (int) ($component['cpcid'] ?? 0);
                                $componentActivities = $activities[$subperiodId][$componentId] ?? [];
                                $componentResult = null;

                                if ($componentActivities !== []) {
                                    $componentSum = 0.0;

                                    foreach ($componentActivities as $activity) {
                                        $grade = $grades[(int) ($activity['aciid'] ?? 0)][(int) ($student['matid'] ?? 0)] ?? null;
                                        $componentSum += $grade !== null && ($grade['cesnota'] ?? null) !== null ? (float) $grade['cesnota'] : 0.0;
                                    }

                                    $componentAverage = round($componentSum / count($componentActivities), 2);
                                    $componentResult = !empty($component['cpcpeso'])
                                        ? round($componentAverage * ((float) $component['cpcpeso'] / 100), 2)
                                        : $componentAverage;
                                    $subperiodAverageParts[] = $componentResult;
                                }
                                ?>
                                <td class="center"><?= $componentResult !== null ? $h(number_format($componentResult, 2, ',', '')) : ''; ?></td>
                            <?php endforeach; ?>
                            <?php
                            $subperiodAverage = $subperiodAverageParts !== [] ? round(array_sum($subperiodAverageParts), 2) : null;
                            $participatesFinalValue = strtolower((string) ($subperiod['spcparticipa_final'] ?? '1'));
                            $participatesFinal = !in_array($participatesFinalValue, ['0', 'false', 'f', 'no'], true);

                            if ($participatesFinal) {
                                $finalAverageSum += $subperiodAverage ?? 0.0;
                                $finalAverageCount++;
                            }
                            ?>
                            <td class="center result <?= $subperiodAverage !== null && $subperiodAverage < 7 ? 'average-low' : 'average-ok'; ?>">
                                <?= $subperiodAverage !== null ? $h(number_format($subperiodAverage, 2, ',', '')) : ''; ?>
                            </td>
                        <?php endforeach; ?>
                        <?php $finalAverage = $finalAverageCount > 0 ? round($finalAverageSum / $finalAverageCount, 2) : null; ?>
                        <td class="center result <?= $finalAverage !== null && $finalAverage < 7 ? 'average-low' : 'average-ok'; ?>">
                            <?= $finalAverage !== null ? $h(number_format($finalAverage, 2, ',', '')) : ''; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <?php $subperiodComponents = $components[$selectedSubperiodId] ?? []; ?>
        <table>
            <thead>
                <tr>
                    <th rowspan="2" class="student">Apellidos y nombres</th>
                    <?php foreach ($subperiodComponents as $component): ?>
                        <?php $componentActivities = $activities[(int) ($component['cpcid'] ?? 0)] ?? []; ?>
                        <th colspan="<?= $h((string) (count($componentActivities) + 1)); ?>" class="center"><?= $h($component['cpcnombre'] ?? ''); ?></th>
                    <?php endforeach; ?>
                    <th rowspan="2" class="center">PROM</th>
                </tr>
                <tr>
                    <?php foreach ($subperiodComponents as $component): ?>
                        <?php $componentId = (int) ($component['cpcid'] ?? 0); ?>
                        <?php foreach (($activities[$componentId] ?? []) as $activityIndex => $activity): ?>
                            <th class="center"><?= $h($componentShortName($component) . (string) ($activityIndex + 1)); ?></th>
                        <?php endforeach; ?>
                        <th class="center"><?= !empty($component['cpcpeso']) ? $h(number_format((float) $component['cpcpeso'], 0, ',', '')) . '%' : 'Prom'; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <?php $subperiodAverageParts = []; ?>
                    <tr>
                        <td><?= $h(trim((string) (($student['perapellidos'] ?? '') . ' ' . ($student['pernombres'] ?? '')))); ?></td>
                        <?php foreach ($subperiodComponents as $component): ?>
                            <?php $componentId = (int) ($component['cpcid'] ?? 0); ?>
                            <?php foreach (($activities[$componentId] ?? []) as $activity): ?>
                                <?php $grade = $grades[(int) ($activity['aciid'] ?? 0)][(int) ($student['matid'] ?? 0)] ?? null; ?>
                                <td class="center"><?= $grade !== null && ($grade['cesnota'] ?? null) !== null ? $h(number_format((float) $grade['cesnota'], 2, ',', '')) : ''; ?></td>
                            <?php endforeach; ?>
                            <?php
                            $componentActivities = $activities[$componentId] ?? [];
                            $componentResult = null;

                            if ($componentActivities !== []) {
                                $componentSum = 0.0;

                                foreach ($componentActivities as $activity) {
                                    $grade = $grades[(int) ($activity['aciid'] ?? 0)][(int) ($student['matid'] ?? 0)] ?? null;
                                    $componentSum += $grade !== null && ($grade['cesnota'] ?? null) !== null ? (float) $grade['cesnota'] : 0.0;
                                }

                                $componentAverage = round($componentSum / count($componentActivities), 2);
                                $componentResult = !empty($component['cpcpeso'])
                                    ? round($componentAverage * ((float) $component['cpcpeso'] / 100), 2)
                                    : $componentAverage;
                                $subperiodAverageParts[] = $componentResult;
                            }
                            ?>
                            <td class="center result"><?= $componentResult !== null ? $h(number_format($componentResult, 2, ',', '')) : ''; ?></td>
                        <?php endforeach; ?>
                        <?php $subperiodAverage = $subperiodAverageParts !== [] ? round(array_sum($subperiodAverageParts), 2) : null; ?>
                        <td class="center result <?= $subperiodAverage !== null && $subperiodAverage < 7 ? 'average-low' : 'average-ok'; ?>">
                            <?= $subperiodAverage !== null ? $h(number_format($subperiodAverage, 2, ',', '')) : ''; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
