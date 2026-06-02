<?php

declare(strict_types=1);

$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$institution = is_array($institution ?? null) ? $institution : [];
$period = is_array($period ?? null) ? $period : [];
$rows = is_array($rows ?? null) ? $rows : [];
$filters = is_array($filters ?? null) ? $filters : [];
$type = (string) ($type ?? 'curso');
$title = (string) ($title ?? 'Reporte de matriculas');
$generatedAt = (string) ($generatedAt ?? date('Y-m-d H:i'));

$courseText = static function (array $row): string {
    return trim((string) (($row['nednombre'] ?? '') . ' | ' . ($row['granombre'] ?? '') . ' | ' . ($row['prlnombre'] ?? '')));
};

$studentText = static function (array $row): string {
    return trim((string) (($row['perapellidos'] ?? '') . ' ' . ($row['pernombres'] ?? '')));
};

$representativeText = static function (array $row): string {
    return trim((string) (($row['rep_apellidos'] ?? '') . ' ' . ($row['rep_nombres'] ?? '')));
};

$filterLabels = [];
if ((int) ($filters['curid'] ?? 0) > 0) {
    $filterLabels[] = 'Curso seleccionado';
}
if ((int) ($filters['nedid'] ?? 0) > 0) {
    $filterLabels[] = 'Nivel seleccionado';
}
if (trim((string) ($filters['sexo'] ?? '')) !== '') {
    $filterLabels[] = 'Sexo: ' . (string) $filters['sexo'];
}
if ((int) ($filters['edad_desde'] ?? 0) > 0 || (int) ($filters['edad_hasta'] ?? 0) > 0) {
    $filterLabels[] = 'Edad: ' . (string) ($filters['edad_desde'] ?? '') . ' - ' . (string) ($filters['edad_hasta'] ?? '');
}
if (trim((string) ($filters['representante'] ?? '')) !== '' && (string) $filters['representante'] !== 'todos') {
    $filterLabels[] = 'Representante: ' . (string) $filters['representante'];
}
if (trim((string) ($filters['documentos'] ?? '')) !== '' && (string) $filters['documentos'] !== 'todos') {
    $filterLabels[] = 'Documentos: ' . (string) $filters['documentos'];
}
if (trim((string) ($filters['discapacidad'] ?? '')) !== '' && (string) $filters['discapacidad'] !== 'todos') {
    $filterLabels[] = 'Discapacidad: ' . (string) $filters['discapacidad'];
}

$stats = [
    'total' => count($rows),
    'sexo' => [],
    'curso' => [],
];

foreach ($rows as $row) {
    $sex = trim((string) ($row['persexo'] ?? 'Sin registrar'));
    $sex = $sex !== '' ? $sex : 'Sin registrar';
    $stats['sexo'][$sex] = ($stats['sexo'][$sex] ?? 0) + 1;

    $course = $courseText($row);
    $course = $course !== '' ? $course : 'Sin curso';
    $stats['curso'][$course] = ($stats['curso'][$course] ?? 0) + 1;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px 28px; }
        body { color: #1f2933; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1, h2, h3, p { margin: 0; }
        .header { border-bottom: 1px solid #9fb3c8; padding-bottom: 10px; margin-bottom: 12px; }
        .school { font-size: 18px; font-weight: 700; text-transform: uppercase; }
        .meta { color: #52606d; font-size: 9px; margin-top: 3px; }
        .title { font-size: 14px; font-weight: 700; margin-top: 8px; }
        .summary { margin: 8px 0 10px; width: 100%; border-collapse: collapse; }
        .summary td { border: 1px solid #d9e2ec; padding: 5px 7px; }
        .summary strong { display: block; font-size: 12px; }
        .filters { color: #52606d; margin-bottom: 10px; }
        table.list { width: 100%; border-collapse: collapse; }
        table.list th { background: #e6eef6; color: #243b53; font-size: 8px; text-align: left; }
        table.list th, table.list td { border: 1px solid #cbd5e1; padding: 4px 5px; vertical-align: top; }
        table.list td { font-size: 8px; }
        .muted { color: #6b7c8f; }
        .empty { border: 1px solid #d9e2ec; padding: 16px; text-align: center; color: #52606d; }
        .stats-grid { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .stats-grid th, .stats-grid td { border: 1px solid #cbd5e1; padding: 5px 7px; }
        .stats-grid th { background: #e6eef6; text-align: left; }
    </style>
</head>
<body>
    <header class="header">
        <p class="school"><?= $h($institution['insnombre'] ?? $appName ?? 'SGEap'); ?></p>
        <p class="meta">Periodo lectivo: <?= $h($period['pledescripcion'] ?? 'Sin periodo'); ?> | Generado: <?= $h($generatedAt); ?></p>
        <p class="title"><?= $h($title); ?></p>
    </header>

    <table class="summary">
        <tr>
            <td><strong><?= $h((string) count($rows)); ?></strong><span class="muted">Registros activos encontrados</span></td>
        </tr>
    </table>

    <p class="filters">Filtros: <?= $h($filterLabels !== [] ? implode(' | ', $filterLabels) : 'Sin filtros adicionales'); ?></p>

    <?php if ($rows === []): ?>
        <div class="empty">No se encontraron resultados para los filtros seleccionados.</div>
    <?php elseif ($type === 'estadisticas'): ?>
        <h3>Resumen por sexo</h3>
        <table class="stats-grid">
            <thead><tr><th>Sexo</th><th>Total</th></tr></thead>
            <tbody>
                <?php foreach ($stats['sexo'] as $label => $count): ?>
                    <tr><td><?= $h($label); ?></td><td><?= $h((string) $count); ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3 style="margin-top: 12px;">Resumen por curso</h3>
        <table class="stats-grid">
            <thead><tr><th>Curso</th><th>Total</th></tr></thead>
            <tbody>
                <?php foreach ($stats['curso'] as $label => $count): ?>
                    <tr><td><?= $h($label); ?></td><td><?= $h((string) $count); ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <table class="list">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 13%;">Cedula</th>
                    <th>Estudiante</th>
                    <th>Curso</th>
                    <th style="width: 6%;">Edad</th>
                    <th style="width: 8%;">Sexo</th>
                    <?php if ($type === 'representantes'): ?><th>Representante</th><th>Contacto</th><?php endif; ?>
                    <?php if ($type === 'documentos'): ?><th style="width: 11%;">Documentos</th><?php endif; ?>
                    <?php if ($type === 'salud'): ?><th>Salud</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $index => $row): ?>
                    <tr>
                        <td><?= $h((string) ($index + 1)); ?></td>
                        <td><?= $h($row['percedula'] ?? ''); ?></td>
                        <td><?= $h($studentText($row)); ?></td>
                        <td><?= $h($courseText($row)); ?></td>
                        <td><?= $h($row['edad'] ?? ''); ?></td>
                        <td><?= $h($row['persexo'] ?? ''); ?></td>
                        <?php if ($type === 'representantes'): ?>
                            <td><?= $h($representativeText($row) !== '' ? $representativeText($row) : 'Sin representante'); ?><br><span class="muted"><?= $h($row['rep_parentesco'] ?? ''); ?></span></td>
                            <td><?= $h($row['rep_telefono'] ?? ''); ?><br><span class="muted"><?= $h($row['rep_correo'] ?? ''); ?></span></td>
                        <?php endif; ?>
                        <?php if ($type === 'documentos'): ?>
                            <td><?= $h((string) ($row['documentos_aceptados'] ?? 0)); ?>/<?= $h((string) ($row['total_documentos'] ?? 0)); ?></td>
                        <?php endif; ?>
                        <?php if ($type === 'salud'): ?>
                            <td><?= !empty($row['tiene_discapacidad']) ? 'Discapacidad' : 'Sin discapacidad'; ?><br><span class="muted"><?= $h($row['gsnombre'] ?? ''); ?> <?= $h($row['amnombre'] ?? ''); ?></span></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
