<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$summary = is_array($summary ?? null) ? $summary : [];
$charts = is_array($charts ?? null) ? $charts : [];
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

    <section class="accounting-dashboard-grid">
        <article class="accounting-chart-card">
            <header>
                <div>
                    <h3>Pagado vs pendiente</h3>
                    <span>Pensiones por mes</span>
                </div>
                <select class="accounting-chart-select" data-month-payment-select>
                    <option value="">Todos los meses</option>
                    <?php foreach (($charts['payment_months'] ?? []) as $month): ?>
                        <option value="<?= $h($month['value'] ?? ''); ?>"><?= $h($month['label'] ?? $month['value'] ?? ''); ?></option>
                    <?php endforeach; ?>
                </select>
            </header>
            <div class="accounting-chart-wrap">
                <canvas data-accounting-chart="monthStatus" data-chart-payload="<?= $h(json_encode($charts['month_payment_status'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>"></canvas>
            </div>
        </article>
        <article class="accounting-chart-card">
            <header>
                <h3>Comprobantes por estado</h3>
                <span>Distribucion del periodo</span>
            </header>
            <div class="accounting-chart-wrap">
                <canvas data-accounting-chart="doughnut" data-chart-payload="<?= $h(json_encode($charts['receipts_status'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>"></canvas>
            </div>
        </article>
        <article class="accounting-chart-card">
            <header>
                <h3>Obligaciones por estado</h3>
                <span>Cantidad de registros</span>
            </header>
            <div class="accounting-chart-wrap">
                <canvas data-accounting-chart="bar" data-chart-payload="<?= $h(json_encode($charts['obligations_status'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>"></canvas>
            </div>
        </article>
        <article class="accounting-chart-card">
            <header>
                <h3>Pagos aprobados</h3>
                <span>Ultimos meses</span>
            </header>
            <div class="accounting-chart-wrap">
                <canvas data-accounting-chart="line" data-chart-payload="<?= $h(json_encode($charts['payments_monthly'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>"></canvas>
            </div>
        </article>
        <article class="accounting-chart-card">
            <header>
                <h3>Saldos por curso</h3>
                <span>Mayores valores pendientes</span>
            </header>
            <div class="accounting-chart-wrap">
                <canvas data-accounting-chart="horizontalBar" data-chart-payload="<?= $h(json_encode($charts['pending_by_course'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>"></canvas>
            </div>
        </article>
    </section>

    <section class="dashboard-grid dashboard-metrics-grid">
        <?php foreach ($metricCards as $card): ?>
            <article class="summary-card">
                <span class="summary-label"><?= $h($card['label']); ?></span>
                <strong class="dashboard-metric-value"><?= $h($card['value']); ?></strong>
                <p><?= $h($card['detail']); ?></p>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="security-assignment-block">
        <div class="actions-row">
            <a class="btn-secondary btn-auto" href="<?= $h(baseUrl('contabilidad/exportar?tipo=obligaciones-pendientes')); ?>">
                <i class="fa fa-download" aria-hidden="true"></i>
                Obligaciones pendientes CSV
            </a>
            <a class="btn-secondary btn-auto" href="<?= $h(baseUrl('contabilidad/exportar?tipo=pagos')); ?>">
                <i class="fa fa-download" aria-hidden="true"></i>
                Pagos CSV
            </a>
            <a class="btn-secondary btn-auto" href="<?= $h(baseUrl('contabilidad/exportar?tipo=rubros')); ?>">
                <i class="fa fa-download" aria-hidden="true"></i>
                Rubros CSV
            </a>
        </div>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') {
        return;
    }

    const statusLabels = {
        EN_REVISION: 'En revision',
        APROBADO: 'Aprobado',
        RECHAZADO: 'Rechazado',
        PENDIENTE: 'Pendiente',
        PAGO_PARCIAL: 'Pago parcial',
        PAGADO: 'Pagado',
        VENCIDO: 'Vencido',
        ANULADO: 'Anulado',
        REVERSADO: 'Reversado',
    };
    const palette = ['#f97316', '#a855f7', '#ec4899', '#14b8a6', '#ef4444', '#eab308', '#22c55e', '#3b82f6'];
    const softPalette = ['rgba(249, 115, 22, 0.84)', 'rgba(168, 85, 247, 0.84)', 'rgba(236, 72, 153, 0.84)', 'rgba(20, 184, 166, 0.84)', 'rgba(239, 68, 68, 0.84)', 'rgba(234, 179, 8, 0.84)', 'rgba(34, 197, 94, 0.84)', 'rgba(59, 130, 246, 0.84)'];
    const charts = new Map();
    const money = function (value) {
        return '$' + Number(value || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    };

    const chartConfig = function (canvas, rows) {
        const labels = rows.map(function (row) {
            return statusLabels[row.label] || row.label || 'Sin datos';
        });
        const values = rows.map(function (row) {
            return Number(row.value || 0);
        });
        const chartKind = canvas.getAttribute('data-accounting-chart') || 'bar';
        const isMoney = chartKind === 'line' || chartKind === 'horizontalBar' || chartKind === 'monthStatus';

        return {
            type: chartKind === 'horizontalBar' ? 'bar' : (chartKind === 'monthStatus' ? 'bar' : chartKind),
            data: {
                labels,
                datasets: [{
                    data: values,
                    borderColor: chartKind === 'line' ? '#f97316' : '#ffffff',
                    backgroundColor: chartKind === 'line' ? 'rgba(249, 115, 22, 0.14)' : softPalette,
                    borderWidth: chartKind === 'doughnut' ? 3 : 1,
                    borderRadius: chartKind === 'bar' || chartKind === 'horizontalBar' || chartKind === 'monthStatus' ? 5 : 0,
                    hoverBackgroundColor: palette,
                    hoverBorderColor: '#ffffff',
                    fill: chartKind === 'line',
                    tension: 0.28,
                    pointBackgroundColor: '#f97316',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: chartKind === 'line' ? 4 : 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: chartKind === 'horizontalBar' ? 'y' : 'x',
                plugins: {
                    legend: {display: chartKind === 'doughnut'},
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const value = context.parsed.y ?? context.parsed.x ?? context.parsed;
                                return isMoney ? money(value) : String(value);
                            },
                        },
                    },
                },
                scales: chartKind === 'doughnut' ? {} : {
                    x: {ticks: {color: '#52677a'}, grid: {display: false}},
                    y: {
                        beginAtZero: true,
                        grid: {color: 'rgba(148, 163, 184, 0.22)'},
                        ticks: {
                            color: '#52677a',
                            callback: function (value) {
                                return isMoney ? money(value) : value;
                            },
                        },
                    },
                },
            },
        };
    };

    document.querySelectorAll('[data-accounting-chart]').forEach(function (canvas) {
        let rows = [];

        try {
            rows = JSON.parse(canvas.getAttribute('data-chart-payload') || '[]');
        } catch (error) {
            rows = [];
        }

        if (rows.length === 0) {
            const container = canvas.closest('.accounting-chart-wrap');
            if (container) {
                container.innerHTML = '<div class="empty-state">Sin datos para graficar.</div>';
            }
            return;
        }

        charts.set(canvas, new Chart(canvas, chartConfig(canvas, rows)));
    });

    const monthSelect = document.querySelector('[data-month-payment-select]');
    const monthCanvas = document.querySelector('[data-accounting-chart="monthStatus"]');

    if (monthSelect && monthCanvas) {
        monthSelect.addEventListener('change', async function () {
            const url = new URL(<?= json_encode(baseUrl('contabilidad'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>, window.location.origin);
            url.searchParams.set('chart', 'month-payment-status');
            url.searchParams.set('month', monthSelect.value);
            const response = await fetch(url.toString(), {headers: {'Accept': 'application/json'}});
            const payload = await response.json();
            const rows = Array.isArray(payload.rows) ? payload.rows : [];
            const chart = charts.get(monthCanvas);

            if (!chart) {
                return;
            }

            chart.data.labels = rows.map(function (row) {
                return row.label || 'Sin datos';
            });
            chart.data.datasets[0].data = rows.map(function (row) {
                return Number(row.value || 0);
            });
            chart.update();
        });
    }
});
</script>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
