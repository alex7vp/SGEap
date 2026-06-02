<?php

declare(strict_types=1);

$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$subjects = is_array($subjects ?? null) ? $subjects : [];
$rows = is_array($rows ?? null) ? $rows : [];
$selectedCourse = is_array($selectedCourse ?? null) ? $selectedCourse : false;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 18px 20px; }
        body { color: #1f2933; font-family: DejaVu Sans, sans-serif; font-size: 7px; }
        .heading { border-bottom: 1px solid #9fb3c8; margin-bottom: 8px; padding-bottom: 8px; text-align: center; }
        .heading strong { display: block; font-size: 14px; text-transform: uppercase; }
        .heading span { display: block; margin-top: 3px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #e6eef6; color: #243b53; }
        th, td { border: 1px solid #cbd5e1; padding: 2px; text-align: center; vertical-align: middle; }
        .student { text-align: left; min-width: 130px; }
        .low { color: #b42318; font-weight: 700; }
        .ok { color: #027a48; font-weight: 700; }
        .empty { border: 1px solid #d9e2ec; padding: 14px; text-align: center; }
    </style>
</head>
<body>
    <?php if ($selectedCourse === false): ?>
        <div class="empty">Selecciona un curso del periodo actual para generar el cuadro final.</div>
    <?php elseif ($subjects === []): ?>
        <div class="empty">Este curso no tiene materias visibles en libreta con perfil activo.</div>
    <?php else: ?>
        <div class="heading">
            <strong><?= $h($appName ?? 'SGEap'); ?></strong>
            <span>Periodo educativo: <?= $h($currentPeriod['pledescripcion'] ?? ''); ?></span>
            <span>Curso: <?= $h($selectedCourse['granombre'] ?? ''); ?> | Paralelo: <?= $h($selectedCourse['prlnombre'] ?? ''); ?></span>
        </div>

        <table>
            <thead>
                <tr>
                    <th rowspan="2">ORD</th>
                    <th rowspan="2" class="student">APELLIDOS Y NOMBRES</th>
                    <?php foreach ($subjects as $subject): ?><th colspan="3"><?= $h($subject['name'] ?? ''); ?></th><?php endforeach; ?>
                    <th rowspan="2">PROM.</th>
                    <th rowspan="2">ESTADO</th>
                </tr>
                <tr>
                    <?php foreach ($subjects as $_subject): ?><th>PP</th><th>ES</th><th>PF</th><?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $index => $row): ?>
                    <tr>
                        <td><?= $h((string) ($index + 1)); ?></td>
                        <td class="student"><?= $h(($row['student']['perapellidos'] ?? '') . ' ' . ($row['student']['pernombres'] ?? '')); ?></td>
                        <?php foreach ($subjects as $subject): ?>
                            <?php $cell = $row['subjects'][$subject['key']] ?? ['partial' => null, 'extra' => null, 'final' => null]; ?>
                            <td><?= $cell['partial'] !== null ? $h(number_format((float) $cell['partial'], 2, ',', '')) : ''; ?></td>
                            <td><?= $cell['extra'] !== null ? $h(number_format((float) $cell['extra'], 2, ',', '')) : ''; ?></td>
                            <td class="<?= $cell['final'] !== null && (float) $cell['final'] < 7 ? 'low' : ''; ?>"><?= $cell['final'] !== null ? $h(number_format((float) $cell['final'], 2, ',', '')) : ''; ?></td>
                        <?php endforeach; ?>
                        <td class="<?= ($row['average'] ?? null) !== null && (float) $row['average'] < 7 ? 'low' : 'ok'; ?>"><?= ($row['average'] ?? null) !== null ? $h(number_format((float) $row['average'], 2, ',', '')) : ''; ?></td>
                        <td><?= $h($row['status'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($rows === []): ?><tr><td colspan="99">No hay estudiantes activos en este curso.</td></tr><?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
