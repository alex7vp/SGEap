<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$entries = is_array($entries ?? null) ? $entries : [];
$filters = is_array($filters ?? null) ? $filters : [];
$pagination = is_array($pagination ?? null) ? $pagination : ['page' => 1, 'pages' => 1, 'total' => count($entries), 'limit' => 25];
$actions = [
    '' => 'Todas las acciones',
    'CREAR' => 'Crear',
    'EDITAR' => 'Editar',
    'ANULAR' => 'Anular',
    'APROBAR' => 'Aprobar',
    'RECHAZAR' => 'Rechazar',
    'REVERSAR' => 'Reversar',
    'DOCUMENTO_EXTERNO' => 'Documento externo',
    'DUPLICADO_ACEPTADO' => 'Duplicado aceptado',
];
$tables = [
    '' => 'Todas las entidades',
    'matricula' => 'Matricula',
    'contabilidad_obligacion' => 'Obligaciones',
    'contabilidad_pago' => 'Pagos',
    'contabilidad_rubro' => 'Rubros',
    'contabilidad_rubro_estudiante' => 'Rubros por estudiante',
];
$hasActiveFilters = trim((string) ($filters['q'] ?? '')) !== ''
    || trim((string) ($filters['tabla'] ?? '')) !== ''
    || trim((string) ($filters['accion'] ?? '')) !== ''
    || trim((string) ($filters['desde'] ?? '')) !== ''
    || trim((string) ($filters['hasta'] ?? '')) !== '';
$fieldLabels = [
    'cpagestado' => 'Estado',
    'cpagvalor_reportado' => 'Valor reportado',
    'cpagvalor_aprobado' => 'Valor aprobado',
    'cpagdocumento_externo_numero' => 'Factura',
    'documento_externo_numero' => 'Factura',
    'cobsaldo_pendiente' => 'Saldo pendiente',
    'saldo_favor_generado' => 'Saldo a favor',
    'motivo' => 'Motivo',
    'observacion' => 'Observacion',
    'matricula_creada' => 'Matricula creada',
    'pensiones_creadas' => 'Pensiones creadas',
    'beca_porcentaje' => 'Beca %',
    'beca_valor' => 'Beca valor',
    'cobvalor_final' => 'Valor final',
    'cobvalor_descuento' => 'Descuento',
    'cobestado' => 'Estado',
    'ccoid' => 'Concepto',
    'crunombre' => 'Rubro',
    'cruvalor' => 'Valor',
    'crufecha_limite' => 'Fecha limite',
    'craalcance' => 'Alcance',
    'creestado' => 'Estado',
    'cmpid' => 'Metodo',
    'referencia' => 'Referencia',
    'duplicado_cpagid' => 'Pago duplicado',
];
$formatAuditValue = static function (mixed $value): string {
    if ($value === null || $value === '') {
        return 'Sin dato';
    }

    if (is_bool($value)) {
        return $value ? 'Si' : 'No';
    }

    if (is_numeric($value)) {
        return str_contains((string) $value, '.') ? '$' . number_format((float) $value, 2, '.', ',') : (string) $value;
    }

    $labels = [
        'EN_REVISION' => 'En revision',
        'APROBADO' => 'Aprobado',
        'RECHAZADO' => 'Rechazado',
        'REVERSADO' => 'Reversado',
        'ANULADO' => 'Anulado',
        'ACTIVO' => 'Activo',
        'PAGADO' => 'Pagado',
        'PENDIENTE' => 'Pendiente',
        'PAGO_PARCIAL' => 'Pago parcial',
    ];

    return $labels[(string) $value] ?? (string) $value;
};
$renderAuditValues = static function (mixed $value) use ($h, $fieldLabels, $formatAuditValue): string {
    $value = trim((string) $value);

    if ($value === '') {
        return '<span class="audit-empty-value">Sin datos</span>';
    }

    $decoded = json_decode($value, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        return '<span class="audit-value-text">' . $h($value) . '</span>';
    }

    $items = [];

    foreach ($decoded as $key => $itemValue) {
        if ($itemValue === null || $itemValue === '') {
            continue;
        }

        if (is_array($itemValue)) {
            $itemValue = json_encode($itemValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $label = $fieldLabels[(string) $key] ?? ucwords(str_replace('_', ' ', (string) $key));
        $items[] = '<div class="audit-value-item"><span>' . $h($label) . '</span><strong>' . $h($formatAuditValue($itemValue)) . '</strong></div>';
    }

    if ($items === []) {
        return '<span class="audit-empty-value">Sin cambios visibles</span>';
    }

    return '<div class="audit-value-list">' . implode('', $items) . '</div>';
};
?>

<p class="module-note">Consulta los cambios registrados por Gestion Contable sobre obligaciones, pagos, rubros y procesos relacionados.</p>

<section class="security-assignment-block">
    <form class="toolbar toolbar-filter accounting-receipts-toolbar" method="GET" action="<?= $h(baseUrl('contabilidad/auditoria')); ?>">
        <div class="filter-box">
            <label class="sr-only" for="audit-search">Buscar</label>
            <input id="audit-search" type="search" name="q" value="<?= $h($filters['q'] ?? ''); ?>" placeholder="Buscar usuario, cedula, accion o observacion">
        </div>
        <div class="filter-box filter-box-compact">
            <label class="sr-only" for="audit-table">Entidad</label>
            <select id="audit-table" name="tabla">
                <?php foreach ($tables as $value => $label): ?>
                    <option value="<?= $h($value); ?>" <?= (string) ($filters['tabla'] ?? '') === (string) $value ? 'selected' : ''; ?>><?= $h($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-box filter-box-compact">
            <label class="sr-only" for="audit-action">Accion</label>
            <select id="audit-action" name="accion">
                <?php foreach ($actions as $value => $label): ?>
                    <option value="<?= $h($value); ?>" <?= (string) ($filters['accion'] ?? '') === (string) $value ? 'selected' : ''; ?>><?= $h($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-box filter-box-compact">
            <label class="sr-only" for="audit-from">Desde</label>
            <input id="audit-from" type="date" name="desde" value="<?= $h($filters['desde'] ?? ''); ?>">
        </div>
        <div class="filter-box filter-box-compact">
            <label class="sr-only" for="audit-to">Hasta</label>
            <input id="audit-to" type="date" name="hasta" value="<?= $h($filters['hasta'] ?? ''); ?>">
        </div>
        <button class="btn-secondary btn-auto" type="submit">
            <i class="fa fa-filter" aria-hidden="true"></i>
            Consultar
        </button>
        <a class="btn-secondary btn-auto" href="<?= $h(baseUrl('contabilidad/auditoria')); ?>">Limpiar</a>
        <span class="table-status"><?= $h($pagination['total'] ?? count($entries)); ?> registro(s)</span>
    </form>

    <?php if ($entries === []): ?>
        <?php if ($hasActiveFilters): ?>
            <div class="empty-state">No hay movimientos de auditoria con los filtros seleccionados.</div>
        <?php else: ?>
            <div class="empty-state">No hay movimientos de auditoria registrados todavia. Los registros apareceran cuando se generen, editen, anulen, aprueben, rechacen o reversen movimientos contables.</div>
        <?php endif; ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Entidad</th>
                        <th>Registro</th>
                        <th>Accion</th>
                        <th>Observacion</th>
                        <th>Antes</th>
                        <th>Despues</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry): ?>
                        <?php $userName = trim((string) (($entry['perapellidos'] ?? '') . ' ' . ($entry['pernombres'] ?? ''))) ?: (string) ($entry['usunombre'] ?? 'Usuario'); ?>
                        <tr>
                            <td><?= $h($entry['caufecha_creacion'] ?? ''); ?></td>
                            <td>
                                <strong><?= $h($userName); ?></strong>
                                <span class="cell-subtitle"><?= $h($entry['percedula'] ?? $entry['usunombre'] ?? ''); ?></span>
                            </td>
                            <td><?= $h($tables[(string) ($entry['cautabla'] ?? '')] ?? $entry['cautabla'] ?? ''); ?></td>
                            <td><?= $h($entry['cauregistro_id'] ?? ''); ?></td>
                            <td><span class="state-pill state-pill-active"><?= $h($actions[(string) ($entry['cauaccion'] ?? '')] ?? $entry['cauaccion'] ?? ''); ?></span></td>
                            <td><?= $h($entry['cauobservacion'] ?? ''); ?></td>
                            <td><?= $renderAuditValues($entry['cauvalor_anterior'] ?? ''); ?></td>
                            <td><?= $renderAuditValues($entry['cauvalor_nuevo'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="actions-row accounting-pagination">
            <?php
            $query = $_GET;
            $query['page'] = max(1, (int) ($pagination['page'] ?? 1) - 1);
            $prevUrl = baseUrl('contabilidad/auditoria') . '?' . http_build_query($query);
            $query['page'] = min((int) ($pagination['pages'] ?? 1), (int) ($pagination['page'] ?? 1) + 1);
            $nextUrl = baseUrl('contabilidad/auditoria') . '?' . http_build_query($query);
            ?>
            <a class="btn-secondary btn-auto <?= (int) ($pagination['page'] ?? 1) <= 1 ? 'is-disabled' : ''; ?>" href="<?= $h($prevUrl); ?>">
                <i class="fa fa-chevron-left" aria-hidden="true"></i>
                Anterior
            </a>
            <span class="table-status">Pagina <?= $h($pagination['page'] ?? 1); ?> de <?= $h($pagination['pages'] ?? 1); ?></span>
            <a class="btn-secondary btn-auto <?= (int) ($pagination['page'] ?? 1) >= (int) ($pagination['pages'] ?? 1) ? 'is-disabled' : ''; ?>" href="<?= $h($nextUrl); ?>">
                Siguiente
                <i class="fa fa-chevron-right" aria-hidden="true"></i>
            </a>
        </div>
    <?php endif; ?>
</section>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
