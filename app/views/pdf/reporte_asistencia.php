<?php

declare(strict_types=1);

$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$reportRows = is_array($reportRows ?? null) ? $reportRows : [];
$reportDates = is_array($reportDates ?? null) ? $reportDates : [];
$studentHourlyMatrix = is_array($studentHourlyMatrix ?? null) ? $studentHourlyMatrix : [];
$courses = is_array($courses ?? null) ? $courses : [];
$students = is_array($students ?? null) ? $students : [];
$courseSubjects = is_array($courseSubjects ?? null) ? $courseSubjects : [];
$teachers = is_array($teachers ?? null) ? $teachers : [];
$selectedCourseId = (int) ($selectedCourseId ?? 0);
$selectedStudentId = (int) ($selectedStudentId ?? 0);
$selectedCourseSubjectId = (int) ($selectedCourseSubjectId ?? 0);
$selectedTeacherPersonId = (int) ($selectedTeacherPersonId ?? 0);
$selectedCourse = null;
$selectedStudent = null;
$selectedSubject = null;
$selectedTeacher = null;
$monthNames = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
$weekdayLabels = [1 => 'L', 2 => 'Ma', 3 => 'Mi', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'];

foreach ($courses as $course) {
    if ((int) ($course['curid'] ?? 0) === $selectedCourseId) {
        $selectedCourse = $course;
        break;
    }
}
foreach ($students as $student) {
    if ((int) ($student['estid'] ?? 0) === $selectedStudentId) {
        $selectedStudent = $student;
        break;
    }
}
foreach ($courseSubjects as $subject) {
    if ((int) ($subject['mtcid'] ?? 0) === $selectedCourseSubjectId) {
        $selectedSubject = $subject;
        break;
    }
}
foreach ($teachers as $teacher) {
    if ((int) ($teacher['perid'] ?? 0) === $selectedTeacherPersonId) {
        $selectedTeacher = $teacher;
        break;
    }
}

$selectedMonth = (string) ($selectedMonth ?? date('Y-m'));
$selectedMonthTimestamp = strtotime($selectedMonth . '-01');
$customRange = !empty($customRange);
$matrixTitle = $customRange ? 'RANGO' : strtoupper($monthNames[(int) date('n', $selectedMonthTimestamp)] ?? $selectedMonth);
$legendItems = [
    'Periodo' => (string) ($currentPeriod['plenombre'] ?? $currentPeriod['pledescripcion'] ?? ''),
    'Mes' => $customRange ? 'Rango manual' : ($monthNames[(int) date('n', $selectedMonthTimestamp)] ?? $selectedMonth) . ' ' . date('Y', $selectedMonthTimestamp),
    'Rango' => (string) $startDate . ' a ' . (string) $endDate,
];
if (is_array($selectedCourse)) {
    $legendItems['Curso'] = trim((string) ($selectedCourse['granombre'] ?? '') . ' ' . (string) ($selectedCourse['prlnombre'] ?? '')) . ', ' . (string) ($selectedCourse['nednombre'] ?? '');
}
if (is_array($selectedSubject)) {
    $legendItems['Materia'] = (string) ($selectedSubject['mtcnombre_mostrar'] ?? '');
}
if (is_array($selectedTeacher)) {
    $legendItems['Docente'] = trim((string) ($selectedTeacher['perapellidos'] ?? '') . ' ' . (string) ($selectedTeacher['pernombres'] ?? ''));
}
if (is_array($selectedStudent)) {
    $legendItems['Estudiante'] = trim((string) ($selectedStudent['perapellidos'] ?? '') . ' ' . (string) ($selectedStudent['pernombres'] ?? ''));
}
$showCourseColumn = $selectedCourseId <= 0 && $selectedStudentId <= 0;
$isStudentReport = $selectedStudentId > 0;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 20px 24px; }
        body { color: #1f2933; font-family: DejaVu Sans, sans-serif; font-size: 8px; }
        h1, h2, p { margin: 0; }
        .header { border-bottom: 1px solid #9fb3c8; margin-bottom: 8px; padding-bottom: 8px; }
        .school { font-size: 15px; font-weight: 700; text-transform: uppercase; }
        .title { font-size: 12px; font-weight: 700; margin-top: 4px; }
        .legend { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .legend td { border: 1px solid #d9e2ec; padding: 4px 6px; }
        .legend strong { color: #52606d; display: block; font-size: 7px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #e6eef6; color: #243b53; font-weight: 700; }
        th, td { border: 1px solid #cbd5e1; padding: 3px; text-align: center; vertical-align: middle; }
        td.name, th.name { text-align: left; }
        .note { color: #52606d; margin-top: 6px; }
        .empty { border: 1px solid #d9e2ec; padding: 14px; text-align: center; }
        .summary { width: 190px; margin-top: 6px; }
    </style>
</head>
<body>
    <header class="header">
        <p class="school"><?= $h($appName ?? 'SGEap'); ?></p>
        <p class="title">Reporte de asistencia</p>
    </header>

    <table class="legend">
        <tr>
            <?php foreach ($legendItems as $label => $value): ?>
                <?php if ((string) $value !== ''): ?>
                    <td><strong><?= $h($label); ?></strong><?= $h($value); ?></td>
                <?php endif; ?>
            <?php endforeach; ?>
        </tr>
    </table>

    <?php if ($isStudentReport && $studentHourlyMatrix === []): ?>
        <div class="empty">No hay registros de asistencia por horas para el estudiante seleccionado.</div>
    <?php elseif ($isStudentReport): ?>
        <?php foreach ($studentHourlyMatrix as $monthBlock): ?>
            <?php $summary = is_array($monthBlock['summary'] ?? null) ? $monthBlock['summary'] : []; ?>
            <table>
                <thead>
                    <tr><th></th><th colspan="7">Horas clases</th></tr>
                    <tr><th><?= $h((string) ($monthBlock['month'] ?? '')); ?></th><?php foreach (range(1, 7) as $hour): ?><th><?= $hour; ?></th><?php endforeach; ?></tr>
                </thead>
                <tbody>
                    <?php foreach (($monthBlock['dates'] ?? []) as $dateRow): ?>
                        <tr>
                            <td><?= $h(date('j', strtotime((string) $dateRow['date']))); ?></td>
                            <?php foreach (range(1, 7) as $hour): ?><td><?= $h($dateRow['hours'][$hour] ?? ''); ?></td><?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <table class="summary">
                <tr><th>Resumen</th><th>Total</th></tr>
                <tr><td>Asistidos</td><td><?= (int) ($summary['asistidos'] ?? 0); ?></td></tr>
                <tr><td>Faltas justificadas</td><td><?= (int) ($summary['faltas_justificadas'] ?? 0); ?></td></tr>
                <tr><td>Faltas injustificadas</td><td><?= (int) ($summary['faltas_injustificadas'] ?? 0); ?></td></tr>
                <tr><td>Atrasos</td><td><?= (int) ($summary['atrasos'] ?? 0); ?></td></tr>
            </table>
        <?php endforeach; ?>
        <p class="note">As= Asistencia | At= Atraso | FJ= Falta Justificada | FI= Falta Injustificada</p>
    <?php elseif ($reportRows === [] || $reportDates === []): ?>
        <div class="empty">No hay dias habilitados o estudiantes activos para los filtros seleccionados.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th class="name"></th>
                    <?php if ($showCourseColumn): ?><th></th><?php endif; ?>
                    <th colspan="<?= count($reportDates); ?>"><?= $h($matrixTitle); ?></th>
                    <th colspan="4">Consolidado</th>
                </tr>
                <tr>
                    <th class="name"></th>
                    <?php if ($showCourseColumn): ?><th></th><?php endif; ?>
                    <?php foreach ($reportDates as $date): ?><th><?= $h($weekdayLabels[(int) date('N', strtotime((string) $date))] ?? ''); ?></th><?php endforeach; ?>
                    <th>As</th><th>A</th><th>FJ</th><th>FI</th>
                </tr>
                <tr>
                    <th class="name">Nombres</th>
                    <?php if ($showCourseColumn): ?><th>Curso</th><?php endif; ?>
                    <?php foreach ($reportDates as $date): ?><th><?= $h(date('j', strtotime((string) $date))); ?></th><?php endforeach; ?>
                    <th></th><th></th><th></th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportRows as $row): ?>
                    <tr>
                        <td class="name"><?= $h((string) ($row['perapellidos'] ?? '') . ' ' . (string) ($row['pernombres'] ?? '')); ?></td>
                        <?php if ($showCourseColumn): ?><td><?= $h((string) ($row['granombre'] ?? '') . ' ' . (string) ($row['prlnombre'] ?? '')); ?></td><?php endif; ?>
                        <?php foreach ($reportDates as $date): ?><td><?= $h($row['dias'][$date] ?? ''); ?></td><?php endforeach; ?>
                        <td><?= (int) ($row['total_asistencias'] ?? 0); ?></td>
                        <td><?= (int) ($row['total_atrasos'] ?? 0); ?></td>
                        <td><?= (int) ($row['total_faltas_justificadas'] ?? 0); ?></td>
                        <td><?= (int) ($row['total_faltas_injustificadas'] ?? 0); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="note">As= Asistido | A= Atraso | FJ= Falta Justificada | FI= Falta Injustificada</p>
    <?php endif; ?>
</body>
</html>
