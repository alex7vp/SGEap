<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$summary = is_array($summary ?? null) ? $summary : [];
$pendingReceipts = is_array($pendingReceipts ?? null) ? $pendingReceipts : [];
$money = static fn (mixed $value): string => '$' . number_format((float) $value, 2, '.', ',');
$metricCards = [
    [
        'label' => 'Comprobantes pendientes',
        'value' => (string) ($summary['comprobantes_pendientes'] ?? 0),
        'detail' => 'Registros enviados por representantes',
    ],
    [
        'label' => 'Pagos aprobados del mes',
        'value' => $money($summary['pagos_aprobados_mes'] ?? 0),
        'detail' => 'Segun fecha de aprobacion',
    ],
    [
        'label' => 'Obligaciones vencidas',
        'value' => (string) ($summary['obligaciones_vencidas'] ?? 0),
        'detail' => 'Matricula y pensiones pendientes',
    ],
    [
        'label' => 'Pendiente por pensiones',
        'value' => $money($summary['valor_pendiente_pensiones'] ?? 0),
        'detail' => 'Saldo interno de obligaciones',
    ],
    [
        'label' => 'Rubros pendientes',
        'value' => (string) ($summary['rubros_pendientes'] ?? 0),
        'detail' => 'Rubros adicionales activos',
    ],
    [
        'label' => 'Rubros vencidos',
        'value' => (string) ($summary['rubros_vencidos'] ?? 0),
        'detail' => 'Con fecha limite superada',
    ],
    [
        'label' => 'Rechazados recientes',
        'value' => (string) ($summary['pagos_rechazados_recientes'] ?? 0),
        'detail' => 'Ultimos 30 dias',
    ],
];
?>

<?php if (empty($currentPeriod)): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para consultar Gestion Contable.</div>
<?php else: ?>
    <p class="module-note">Resumen operativo de cobros internos, comprobantes y obligaciones para el periodo <?= $h($currentPeriod['pledescripcion'] ?? ''); ?>.</p>

    <section class="dashboard-grid dashboard-metrics-grid">
        <?php foreach ($metricCards as $card): ?>
            <article class="summary-card">
                <span class="summary-label"><?= $h($card['label']); ?></span>
                <strong class="dashboard-metric-value"><?= $h($card['value']); ?></strong>
                <p><?= $h($card['detail']); ?></p>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="module-home-grid">
        <article class="module-home-card is-disabled">
            <span class="module-home-card-icon"><i class="fa fa-sliders" aria-hidden="true"></i></span>
            <strong>Configuracion de valores</strong>
            <p>Matricula, pensiones, descuentos, vencimientos y reglas por nivel educativo.</p>
            <span class="cell-subtitle">Siguiente fase</span>
        </article>
        <article class="module-home-card is-disabled">
            <span class="module-home-card-icon"><i class="fa fa-list-alt" aria-hidden="true"></i></span>
            <strong>Obligaciones</strong>
            <p>Generacion y administracion de matricula y pensiones del periodo.</p>
            <span class="cell-subtitle">Siguiente fase</span>
        </article>
        <article class="module-home-card is-disabled">
            <span class="module-home-card-icon"><i class="fa fa-file-text-o" aria-hidden="true"></i></span>
            <strong>Comprobantes</strong>
            <p>Revision, aprobacion, rechazo y aplicacion de comprobantes.</p>
            <span class="cell-subtitle">Siguiente fase</span>
        </article>
        <article class="module-home-card is-disabled">
            <span class="module-home-card-icon"><i class="fa fa-plus-square" aria-hidden="true"></i></span>
            <strong>Rubros adicionales</strong>
            <p>Salidas pedagogicas, carnet, materiales, eventos y reposiciones.</p>
            <span class="cell-subtitle">Siguiente fase</span>
        </article>
    </section>

    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Comprobantes pendientes de revision</h3>
                <p>Ultimos comprobantes enviados por representantes para matricula o pensiones.</p>
            </div>
        </header>

        <?php if ($pendingReceipts === []): ?>
            <div class="empty-state">No hay comprobantes pendientes de revision en el periodo seleccionado.</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Curso</th>
                        <th>Obligacion</th>
                        <th>Valor reportado</th>
                        <th>Registro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingReceipts as $receipt): ?>
                        <tr>
                            <td><?= $h(trim((string) (($receipt['perapellidos'] ?? '') . ' ' . ($receipt['pernombres'] ?? '')))); ?></td>
                            <td><?= $h(trim((string) (($receipt['granombre'] ?? '') . ' ' . ($receipt['prlnombre'] ?? '')))); ?></td>
                            <td><?= $h($receipt['cobdescripcion'] ?? 'Obligacion'); ?></td>
                            <td><?= $h($money($receipt['cpagvalor_reportado'] ?? 0)); ?></td>
                            <td><?= $h($receipt['cpagfecha_registro'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
