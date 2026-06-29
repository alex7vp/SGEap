<?php

declare(strict_types=1);

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$currentPeriod = is_array($currentPeriod ?? null) ? $currentPeriod : [];
$session = is_array($session ?? null) ? $session : [];
$students = is_array($students ?? null) ? $students : [];
$attendance = is_array($attendance ?? null) ? $attendance : [];
$statusLabels = [
    'ASISTENCIA' => 'Asistencia',
    'ATRASO' => 'Atraso',
    'FALTA_JUSTIFICADA' => 'Falta justificada',
    'FALTA_INJUSTIFICADA' => 'Falta injustificada',
];
$counts = [
    'ASISTENCIA' => 0,
    'ATRASO' => 0,
    'FALTA_JUSTIFICADA' => 0,
    'FALTA_INJUSTIFICADA' => 0,
];

foreach ($students as $student) {
    $studentId = (int) ($student['estid'] ?? 0);
    $status = (string) ($attendance[$studentId]['aesestado'] ?? 'ASISTENCIA');
    $counts[$status] = ($counts[$status] ?? 0) + 1;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 26px 32px; }
        body { color: #1f2933; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        h1, p { margin: 0; }
        .header { border-bottom: 1px solid #9fb3c8; margin-bottom: 12px; padding-bottom: 9px; }
        .school { font-size: 16px; font-weight: 700; text-transform: uppercase; }
        .title { font-size: 13px; font-weight: 700; margin-top: 7px; }
        .meta { color: #52606d; font-size: 8px; margin-top: 3px; }
        table { width: 100%; border-collapse: collapse; }
        .legend { margin-bottom: 10px; }
        .legend td { border: 1px solid #d9e2ec; padding: 5px 7px; }
        .legend strong { color: #52606d; display: block; font-size: 7px; text-transform: uppercase; }
        .summary { margin-bottom: 10px; }
        .summary td { border: 1px solid #d9e2ec; padding: 5px 7px; text-align: center; }
        .summary strong { display: block; font-size: 12px; }
        th { background: #e6eef6; color: #243b53; font-weight: 700; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 5px; vertical-align: top; }
        td.center, th.center { text-align: center; }
        .empty { border: 1px solid #d9e2ec; padding: 16px; text-align: center; color: #52606d; }
    </style>
</head>
<body>
    <header class="header">
        <p class="school"><?= $h($appName ?? 'SGEap'); ?></p>
        <p class="title">Registro de asistencia</p>
        <p class="meta">Generado: <?= $h(date('Y-m-d H:i')); ?></p>
    </header>

    <table class="legend">
        <tr>
            <td><strong>Periodo</strong><?= $h($currentPeriod['pledescripcion'] ?? 'Sin periodo'); ?></td>
            <td><strong>Fecha</strong><?= $h($session['cafecha'] ?? ''); ?></td>
            <td><strong>Hora</strong><?= $h((string) ($session['sclnumero_hora'] ?? '')); ?></td>
        </tr>
        <tr>
            <td><strong>Materia</strong><?= $h($session['mtcnombre_mostrar'] ?? ''); ?></td>
            <td><strong>Curso</strong><?= $h(trim((string) (($session['granombre'] ?? '') . ' ' . ($session['prlnombre'] ?? '')))); ?></td>
            <td><strong>Estado</strong><?= $h($session['sclestado'] ?? ''); ?></td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td><strong><?= count($students); ?></strong>Total</td>
            <td><strong><?= (int) ($counts['ASISTENCIA'] ?? 0); ?></strong>Asistencias</td>
            <td><strong><?= (int) ($counts['ATRASO'] ?? 0); ?></strong>Atrasos</td>
            <td><strong><?= (int) ($counts['FALTA_JUSTIFICADA'] ?? 0); ?></strong>F. justificadas</td>
            <td><strong><?= (int) ($counts['FALTA_INJUSTIFICADA'] ?? 0); ?></strong>F. injustificadas</td>
        </tr>
    </table>

    <?php if ($students === []): ?>
        <div class="empty">No hay estudiantes activos para esta sesion.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th class="center" style="width: 34px;">#</th>
                    <th>Estudiante</th>
                    <th style="width: 95px;">Cedula</th>
                    <th style="width: 120px;">Estado</th>
                    <th>Observacion</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $index => $student): ?>
                    <?php
                    $studentId = (int) ($student['estid'] ?? 0);
                    $saved = $attendance[$studentId] ?? [];
                    $status = (string) ($saved['aesestado'] ?? 'ASISTENCIA');
                    ?>
                    <tr>
                        <td class="center"><?= $index + 1; ?></td>
                        <td><?= $h(trim((string) (($student['perapellidos'] ?? '') . ' ' . ($student['pernombres'] ?? '')))); ?></td>
                        <td><?= $h($student['percedula'] ?? ''); ?></td>
                        <td><?= $h($statusLabels[$status] ?? $status); ?></td>
                        <td><?= $h($saved['aesobservacion'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
