<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static fn (mixed $value): string => '$' . number_format((float) $value, 2, '.', ',');
$summary = is_array($summary ?? null) ? $summary : [];
$charts = is_array($charts ?? null) ? $charts : [];
$courses = is_array($courses ?? null) ? $courses : [];
$filters = is_array($filters ?? null) ? $filters : [];
$reportRows = is_array($reportRows ?? null) ? $reportRows : [];
$shouldGenerate = !empty($shouldGenerate);
$canExport = !empty($canExport);
$activeView = (string) ($_GET['vista'] ?? ($shouldGenerate ? 'personalizados' : 'tarjetas'));
$activeView = in_array($activeView, ['tarjetas', 'personalizados'], true) ? $activeView : 'tarjetas';
$statusLabels = [
    'EN_REVISION' => 'En revision',
    'PENDIENTE' => 'Pendiente',
    'PAGO_PARCIAL' => 'Pago parcial',
    'PAGADO' => 'Pagado',
    'VENCIDO' => 'Vencido',
    'ANULADO' => 'Anulado',
];
$selectedCourseIds = array_map('intval', (array) ($filters['cursos'] ?? []));
$selectedMonthValues = array_map('strval', (array) ($filters['meses'] ?? []));
$selectedMonth = (string) ($filters['mes'] ?? '');
if ($selectedMonthValues === [] && $selectedMonth !== '') {
    $selectedMonthValues[] = $selectedMonth;
}
$monthOptions = is_array($charts['payment_months'] ?? null) ? $charts['payment_months'] : [];
$selectedStatuses = array_map('strval', (array) ($filters['estados'] ?? []));
$reportTotal = count($reportRows);
$reportBalance = array_sum(array_map(static fn (array $row): float => (float) ($row['cobsaldo_pendiente'] ?? 0), $reportRows));
$reportFinalValue = array_sum(array_map(static fn (array $row): float => (float) ($row['cobvalor_final'] ?? 0), $reportRows));
$reportQuery = array_merge($_GET, ['tipo' => 'cartera-filtrada', 'vista' => 'personalizados']);
unset($reportQuery['consultar']);
$filteredExportUrl = baseUrl('contabilidad/exportar') . '?' . http_build_query($reportQuery);
$reportCards = [
    [
        'label' => 'Obligaciones vencidas',
        'value' => (string) ($summary['obligaciones_vencidas'] ?? 0),
        'detail' => 'Registros con fecha de vencimiento superada',
    ],
    [
        'label' => 'Pendiente por pensiones',
        'value' => $money($summary['valor_pendiente_pensiones'] ?? 0),
        'detail' => 'Saldo pendiente interno por pensiones',
    ],
    [
        'label' => 'Pagos aprobados del mes',
        'value' => $money($summary['pagos_aprobados_mes'] ?? 0),
        'detail' => 'Segun fecha de aprobacion',
    ],
    [
        'label' => 'Comprobantes pendientes',
        'value' => (string) ($summary['comprobantes_pendientes'] ?? 0),
        'detail' => 'En revision administrativa',
    ],
    [
        'label' => 'Rubros pendientes',
        'value' => (string) ($summary['rubros_pendientes'] ?? 0),
        'detail' => 'Rubros adicionales activos por cobrar',
    ],
    [
        'label' => 'Rubros vencidos',
        'value' => (string) ($summary['rubros_vencidos'] ?? 0),
        'detail' => 'Con fecha limite superada',
    ],
];
$renderSimpleRows = static function (array $rows, bool $asMoney = false) use ($h, $money, $statusLabels): void {
    foreach ($rows as $row) {
        $label = (string) ($row['label'] ?? 'Sin datos');
        $value = $row['value'] ?? 0;
        ?>
        <tr>
            <td><?= $h($statusLabels[$label] ?? $label); ?></td>
            <td><?= $h($asMoney ? $money($value) : $value); ?></td>
        </tr>
        <?php
    }
};
?>

<?php if (empty($currentPeriod)): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado para consultar reportes contables.</div>
<?php else: ?>
    <p class="module-note">Resumen y exportacion de informacion contable del periodo <?= $h($currentPeriod['pledescripcion'] ?? ''); ?>.</p>

    <section class="grade-config-view-stack">
        <section class="security-assignment-block grade-profile-creation-block" data-option-view-mode>
            <div class="grade-profile-mode-selector" role="radiogroup" aria-label="Vista de reportes contables">
                <label class="grade-profile-mode-option">
                    <input type="radio" name="accounting_report_view_mode" value="tarjetas" <?= $activeView === 'tarjetas' ? 'checked' : ''; ?> data-option-view-radio>
                    <span>Resumen general</span>
                </label>
                <label class="grade-profile-mode-option">
                    <input type="radio" name="accounting_report_view_mode" value="personalizados" <?= $activeView === 'personalizados' ? 'checked' : ''; ?> data-option-view-radio>
                    <span>Reportes personalizados</span>
                </label>
            </div>
        </section>

        <section data-option-view-panel="tarjetas" <?= $activeView === 'tarjetas' ? '' : 'hidden'; ?>>
            <section class="dashboard-grid dashboard-metrics-grid">
                <?php foreach ($reportCards as $card): ?>
                    <article class="summary-card">
                        <span class="summary-label"><?= $h($card['label']); ?></span>
                        <strong class="dashboard-metric-value"><?= $h($card['value']); ?></strong>
                        <p><?= $h($card['detail']); ?></p>
                    </article>
                <?php endforeach; ?>
            </section>

            <section class="security-assignment-block">
                <header class="security-assignment-header">
                    <div>
                        <h3>Exportaciones</h3>
                        <p>Archivos CSV preparados para revision administrativa o respaldo externo.</p>
                    </div>
                </header>

                <?php if (!$canExport): ?>
                    <div class="empty-state">Su usuario puede consultar reportes, pero no tiene permiso para exportar archivos CSV.</div>
                <?php else: ?>
                    <div class="actions-row">
                        <a class="btn-secondary btn-auto" href="<?= $h(baseUrl('contabilidad/exportar?tipo=obligaciones-pendientes')); ?>">
                            <i class="fa fa-download" aria-hidden="true"></i>
                            Obligaciones pendientes
                        </a>
                        <a class="btn-secondary btn-auto" href="<?= $h(baseUrl('contabilidad/exportar?tipo=pagos')); ?>">
                            <i class="fa fa-download" aria-hidden="true"></i>
                            Pagos revisados
                        </a>
                        <a class="btn-secondary btn-auto" href="<?= $h(baseUrl('contabilidad/exportar?tipo=rubros')); ?>">
                            <i class="fa fa-download" aria-hidden="true"></i>
                            Rubros adicionales
                        </a>
                    </div>
                <?php endif; ?>
            </section>

            <section class="accounting-dashboard-grid">
                <article class="summary-card">
                    <span class="summary-label">Comprobantes</span>
                    <strong>Estados del periodo</strong>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Estado</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $renderSimpleRows($charts['receipts_status'] ?? []); ?>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="summary-card">
                    <span class="summary-label">Obligaciones</span>
                    <strong>Estados del periodo</strong>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Estado</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $renderSimpleRows($charts['obligations_status'] ?? []); ?>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="summary-card">
                    <span class="summary-label">Cursos</span>
                    <strong>Mayores saldos pendientes</strong>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Curso</th>
                                    <th>Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $renderSimpleRows($charts['pending_by_course'] ?? [], true); ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            </section>
        </section>

        <section class="security-assignment-block" data-option-view-panel="personalizados" <?= $activeView === 'personalizados' ? '' : 'hidden'; ?>>
            <header class="security-assignment-header">
                <div>
                    <h3>Generador de cartera</h3>
                    <p>Marque solo las opciones que necesita consultar y genere el reporte.</p>
                </div>
            </header>

            <form class="report-custom-form" method="GET" action="<?= $h(baseUrl('contabilidad/reportes')); ?>">
                <input type="hidden" name="vista" value="personalizados">
                <input type="hidden" name="consultar" value="1">

                <div class="report-filter-row">
                    <div class="filter-box">
                        <label class="sr-only" for="report-search">Estudiante</label>
                        <input id="report-search" type="search" name="q" value="<?= $h($filters['q'] ?? ''); ?>" placeholder="Estudiante, cedula u obligacion">
                    </div>
                    <div class="filter-box filter-box-compact">
                        <label class="sr-only" for="report-period">Periodo lectivo</label>
                        <input id="report-period" type="text" value="<?= $h($currentPeriod['pledescripcion'] ?? ''); ?>" readonly>
                    </div>
                    <div class="filter-box filter-box-compact">
                        <label class="sr-only" for="report-min">Saldo minimo</label>
                        <input id="report-min" type="number" step="0.01" min="0" name="valor_min" value="<?= (float) ($filters['valor_min'] ?? 0) > 0 ? $h($filters['valor_min']) : ''; ?>" placeholder="Saldo min.">
                    </div>
                    <div class="filter-box filter-box-compact">
                        <label class="sr-only" for="report-max">Saldo maximo</label>
                        <input id="report-max" type="number" step="0.01" min="0" name="valor_max" value="<?= (float) ($filters['valor_max'] ?? 0) > 0 ? $h($filters['valor_max']) : ''; ?>" placeholder="Saldo max.">
                    </div>
                    <label class="accounting-service-switch">
                        <input type="checkbox" name="solo_mora" value="1" <?= !empty($filters['solo_mora']) ? 'checked' : ''; ?>>
                        <span>Solo mora</span>
                    </label>
                </div>

                <div class="report-filter-groups">
                    <fieldset class="report-check-group">
                        <legend>Cursos</legend>
                        <div class="report-checkbox-grid report-checkbox-grid-courses">
                            <?php foreach ($courses as $course): ?>
                                <?php
                                $courseId = (int) ($course['curid'] ?? 0);
                                $courseLabel = trim((string) (($course['nednombre'] ?? '') . ' | ' . ($course['granombre'] ?? '') . ' ' . ($course['prlnombre'] ?? '')));
                                ?>
                                <label class="report-check-option">
                                    <input type="checkbox" name="cursos[]" value="<?= $h($courseId); ?>" <?= in_array($courseId, $selectedCourseIds, true) ? 'checked' : ''; ?>>
                                    <span><?= $h($courseLabel); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>

                    <fieldset class="report-check-group">
                        <legend>Meses</legend>
                        <?php if ($monthOptions === []): ?>
                            <div class="empty-state">Aun no existen obligaciones de pension generadas para este periodo.</div>
                        <?php else: ?>
                            <div class="report-checkbox-grid report-checkbox-grid-months">
                                <?php foreach ($monthOptions as $monthOption): ?>
                                    <?php
                                    $value = (string) ($monthOption['value'] ?? '');
                                    $label = (string) ($monthOption['label'] ?? $value);
                                    ?>
                                    <label class="report-check-option">
                                        <input type="checkbox" name="meses[]" value="<?= $h($value); ?>" <?= in_array($value, $selectedMonthValues, true) ? 'checked' : ''; ?>>
                                        <span><?= $h($label); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </fieldset>

                    <fieldset class="report-check-group">
                        <legend>Estados</legend>
                        <div class="report-checkbox-grid report-checkbox-grid-statuses">
                            <?php foreach ($statusLabels as $value => $label): ?>
                                <label class="report-check-option">
                                    <input type="checkbox" name="estados[]" value="<?= $h($value); ?>" <?= in_array((string) $value, $selectedStatuses, true) ? 'checked' : ''; ?>>
                                    <span><?= $h($label); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                </div>

                <div class="actions-row report-filter-actions">
                    <button class="btn-secondary btn-auto" type="submit">
                        <i class="fa fa-filter" aria-hidden="true"></i>
                        Generar consulta
                    </button>
                    <a class="btn-secondary btn-auto" href="<?= $h(baseUrl('contabilidad/reportes?vista=personalizados')); ?>">Limpiar</a>
                </div>
            </form>

            <?php if (!$shouldGenerate): ?>
                <div class="empty-state">Seleccione opciones y pulse Generar consulta para visualizar resultados.</div>
            <?php elseif ($reportRows === []): ?>
                <div class="empty-state">No existen obligaciones con los criterios seleccionados.</div>
            <?php else: ?>
                <div class="summary-card">
                    <div class="meta-grid">
                        <div class="meta-item">
                            <strong>Registros</strong>
                            <span><?= $h($reportTotal); ?></span>
                        </div>
                        <div class="meta-item">
                            <strong>Valor total</strong>
                            <span><?= $h($money($reportFinalValue)); ?></span>
                        </div>
                        <div class="meta-item">
                            <strong>Saldo pendiente</strong>
                            <span><?= $h($money($reportBalance)); ?></span>
                        </div>
                        <?php if ($canExport): ?>
                            <div class="meta-item">
                                <strong>Exportar</strong>
                                <span><a href="<?= $h($filteredExportUrl); ?>">CSV filtrado</a></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Curso</th>
                                <th>Obligacion</th>
                                <th>Mes</th>
                                <th>Vencimiento</th>
                                <th>Valor</th>
                                <th>Pagado</th>
                                <th>Saldo</th>
                                <th>Estado</th>
                                <th>Mora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportRows as $row): ?>
                                <?php $studentName = trim((string) (($row['perapellidos'] ?? '') . ' ' . ($row['pernombres'] ?? ''))); ?>
                                <tr>
                                    <td>
                                        <strong><?= $h($studentName); ?></strong>
                                        <span class="cell-subtitle"><?= $h($row['percedula'] ?? ''); ?></span>
                                    </td>
                                    <td><?= $h(trim((string) (($row['granombre'] ?? '') . ' ' . ($row['prlnombre'] ?? '')))); ?></td>
                                    <td><?= $h($row['cobdescripcion'] ?? ''); ?></td>
                                    <td><?= $h($row['mes_label'] ?? ''); ?></td>
                                    <td><?= $h($row['cobfecha_vencimiento'] ?? ''); ?></td>
                                    <td><?= $h($money($row['cobvalor_final'] ?? 0)); ?></td>
                                    <td><?= $h($money($row['cobvalor_pagado'] ?? 0)); ?></td>
                                    <td><?= $h($money($row['cobsaldo_pendiente'] ?? 0)); ?></td>
                                    <td><span class="state-pill <?= (string) ($row['cobestado'] ?? '') === 'PAGADO' ? 'state-pill-active' : 'state-pill-inactive'; ?>"><?= $h($statusLabels[(string) ($row['cobestado'] ?? '')] ?? $row['cobestado'] ?? ''); ?></span></td>
                                    <td><?= (int) ($row['dias_mora'] ?? 0) > 0 ? $h((string) $row['dias_mora'] . ' dia(s)') : 'No'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($reportTotal >= 300): ?>
                    <div class="empty-state">Se muestran los primeros 300 registros. Use filtros mas especificos o exporte el CSV filtrado para obtener el conjunto completo.</div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </section>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
