<?php

declare(strict_types=1);

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$institution = is_array($institution ?? null) ? $institution : [];
$period = is_array($period ?? null) ? $period : [];
$course = is_array($course ?? null) ? $course : [];
$students = is_array($students ?? null) ? $students : [];
$generatedAt = (string) ($generatedAt ?? date('Y-m-d H:i'));
$courseLabel = trim((string) (($course['granombre'] ?? '') . ' ' . ($course['prlnombre'] ?? '')));
$fullCourseLabel = trim((string) (($course['nednombre'] ?? '') . ' | ' . ($courseLabel !== '' ? $courseLabel : 'Curso')));
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px 34px; }
        body { color: #1f2933; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1, p { margin: 0; }
        .header { border-bottom: 1px solid #9fb3c8; margin-bottom: 14px; padding-bottom: 10px; }
        .school { font-size: 17px; font-weight: 700; text-transform: uppercase; }
        .meta { color: #52606d; font-size: 9px; margin-top: 3px; }
        .title { font-size: 14px; font-weight: 700; margin-top: 9px; }
        .summary { margin: 8px 0 12px; width: 100%; border-collapse: collapse; }
        .summary td { border: 1px solid #d9e2ec; padding: 6px 8px; }
        .summary strong { display: block; font-size: 12px; }
        .muted { color: #6b7c8f; }
        table.list { width: 100%; border-collapse: collapse; }
        table.list th { background: #e6eef6; color: #243b53; font-size: 9px; text-align: left; }
        table.list th, table.list td { border: 1px solid #cbd5e1; padding: 5px 6px; vertical-align: top; }
        table.list td { font-size: 9px; }
        .empty { border: 1px solid #d9e2ec; padding: 16px; text-align: center; color: #52606d; }
    </style>
</head>
<body>
    <header class="header">
        <p class="school"><?= $h($institution['insnombre'] ?? $appName ?? 'SGEap'); ?></p>
        <p class="meta">Periodo lectivo: <?= $h($period['pledescripcion'] ?? 'Sin periodo'); ?> | Generado: <?= $h($generatedAt); ?></p>
        <p class="title">Lista del curso</p>
    </header>

    <table class="summary">
        <tr>
            <td><strong><?= $h($fullCourseLabel); ?></strong><span class="muted">Curso</span></td>
            <td style="width: 120px;"><strong><?= $h((string) count($students)); ?></strong><span class="muted">Estudiantes activos</span></td>
        </tr>
    </table>

    <?php if ($students === []): ?>
        <div class="empty">No existen estudiantes activos para este curso.</div>
    <?php else: ?>
        <table class="list">
            <thead>
                <tr>
                    <th style="width: 42px;">#</th>
                    <th>Apellidos y nombres</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $index => $student): ?>
                    <?php $studentName = trim((string) (($student['perapellidos'] ?? '') . ' ' . ($student['pernombres'] ?? ''))); ?>
                    <tr>
                        <td><?= $h((string) ($index + 1)); ?></td>
                        <td><?= $h($studentName !== '' ? $studentName : 'Estudiante sin nombre'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
