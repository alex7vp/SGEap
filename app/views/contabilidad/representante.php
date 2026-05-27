<?php

declare(strict_types=1);

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static fn (mixed $value): string => '$' . number_format((float) $value, 2, '.', ',');
$obligations = is_array($obligations ?? null) ? $obligations : [];
$additionalItems = is_array($additionalItems ?? null) ? $additionalItems : [];
$additionalItemsVisible = !empty($additionalItemsVisible);
$payableStatuses = ['PENDIENTE', 'PAGO_PARCIAL', 'VENCIDO'];
$receiptStatusLabels = [
    'EN_REVISION' => 'En revision',
    'APROBADO' => 'Aprobado',
    'RECHAZADO' => 'Rechazado',
    'ANULADO' => 'Anulado',
    'REVERSADO' => 'Reversado',
];
$receiptHistoryPayload = static function (array $receipts): string {
    foreach ($receipts as &$receipt) {
        $receipt['archivo_url'] = !empty($receipt['archivo_ruta']) ? asset((string) $receipt['archivo_ruta']) : '';
    }
    unset($receipt);

    return htmlspecialchars(json_encode($receipts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
};
$groups = [];

foreach ($obligations as $row) {
    $studentName = trim((string) (($row['perapellidos'] ?? '') . ' ' . ($row['pernombres'] ?? '')));
    $courseName = trim((string) (($row['granombre'] ?? '') . ' ' . ($row['prlnombre'] ?? '')));
    $groupKey = (string) ($row['matid'] ?? $studentName . '|' . $courseName);

    if (!isset($groups[$groupKey])) {
        $groups[$groupKey] = [
            'student' => $studentName !== '' ? $studentName : 'Estudiante',
            'course' => $courseName,
            'rows' => [],
        ];
    }

    $groups[$groupKey]['rows'][] = $row;
}

$additionalGroups = [];

foreach ($additionalItems as $row) {
    $studentName = trim((string) (($row['perapellidos'] ?? '') . ' ' . ($row['pernombres'] ?? '')));
    $courseName = trim((string) (($row['granombre'] ?? '') . ' ' . ($row['prlnombre'] ?? '')));
    $groupKey = (string) ($row['matid'] ?? $studentName . '|' . $courseName);

    if (!isset($additionalGroups[$groupKey])) {
        $additionalGroups[$groupKey] = [
            'student' => $studentName !== '' ? $studentName : 'Estudiante',
            'course' => $courseName,
            'rows' => [],
        ];
    }

    $additionalGroups[$groupKey]['rows'][] = $row;
}

if (count($groups) === 1 || ($groups === [] && count($additionalGroups) === 1)) {
    $firstGroup = $groups !== [] ? reset($groups) : reset($additionalGroups);
    $pageTitle = 'Mis pagos - ' . (string) ($firstGroup['student'] ?? 'Estudiante');
}

require BASE_PATH . '/app/views/partials/header.php';
?>

<?php if (empty($currentPeriod)): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado para consultar pagos.</div>
<?php else: ?>
    <p class="module-note">Consulta obligaciones, pagos registrados y comprobantes pendientes del periodo <?= $h($currentPeriod['pledescripcion'] ?? ''); ?>.</p>

    <?php if ($groups === []): ?>
        <section class="security-assignment-block">
            <div class="empty-state">No existen obligaciones generadas para los estudiantes vinculados.</div>
        </section>
    <?php else: ?>
        <?php foreach ($groups as $group): ?>
            <section class="security-assignment-block">
                <table class="data-table accounting-representative-table">
                    <thead>
                        <tr>
                            <th>Obligacion</th>
                            <th>Valor</th>
                            <th>Pagado</th>
                            <th>Saldo</th>
                            <th>Estado</th>
                            <th>Comprobante</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($group['rows'] as $row): ?>
                            <?php
                            $status = (string) ($row['cobestado'] ?? '');
                            $paymentStatus = (string) ($row['cpagestado'] ?? '');
                            $canUpload = in_array($status, $payableStatuses, true) && $paymentStatus !== 'EN_REVISION';
                            $fileInputId = 'receipt-file-' . (int) ($row['cobid'] ?? 0);
                            $receipts = json_decode((string) ($row['comprobantes'] ?? '[]'), true);
                            $receipts = is_array($receipts) ? $receipts : [];
                            ?>
                            <tr>
                                <td><?= $h($row['cobdescripcion'] ?? ''); ?></td>
                                <td><?= $h($money($row['cobvalor_final'] ?? 0)); ?></td>
                                <td><?= $h($money($row['cobvalor_pagado'] ?? 0)); ?></td>
                                <td><?= $h($money($row['cobsaldo_pendiente'] ?? 0)); ?></td>
                                <td>
                                    <span class="state-pill <?= $status === 'PAGADO' ? 'state-pill-active' : 'state-pill-inactive'; ?>"><?= $h($status); ?></span>
                                    <?php if ($paymentStatus === 'RECHAZADO'): ?>
                                        <span class="cell-subtitle">Pago rechazado: <?= $h($row['cpagmotivo_rechazo'] ?? 'Revise el comprobante.'); ?></span>
                                    <?php elseif ($paymentStatus === 'EN_REVISION'): ?>
                                        <span class="cell-subtitle">Comprobante enviado el <?= $h($row['cpagfecha_registro'] ?? ''); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($canUpload): ?>
                                        <form class="accounting-upload-form" method="POST" action="<?= $h(baseUrl('representante/contabilidad/comprobante')); ?>" enctype="multipart/form-data">
                                            <?= csrfField(); ?>
                                            <input type="hidden" name="cobid" value="<?= $h($row['cobid'] ?? ''); ?>">
                                            <input type="hidden" name="estudiante" value="<?= $h($group['student']); ?>">
                                            <label class="file-inline-control" for="<?= $h($fileInputId); ?>">
                                                <span class="sr-only">Comprobante</span>
                                                <input id="<?= $h($fileInputId); ?>" type="file" name="comprobante" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required data-accounting-file-input>
                                                <span class="file-inline-button">Seleccionar</span>
                                                <span class="file-inline-name" data-accounting-file-name>Sin archivo</span>
                                            </label>
                                            <button class="icon-button icon-button-save accounting-receipt-save-button" type="submit" title="Guardar comprobante" aria-label="Guardar comprobante">
                                                <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                            </button>
                                            <?php if ($receipts !== []): ?>
                                                <button class="icon-button icon-button-view" type="button" title="Ver comprobantes" aria-label="Ver comprobantes" data-representative-receipts data-receipts="<?= $receiptHistoryPayload($receipts); ?>">
                                                    <i class="fa fa-search" aria-hidden="true"></i>
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    <?php elseif ($status === 'PAGADO'): ?>
                                        <span class="state-pill state-pill-active">Pagado</span>
                                        <?php if ($receipts !== []): ?>
                                            <button class="icon-button icon-button-view" type="button" title="Ver comprobantes" aria-label="Ver comprobantes" data-representative-receipts data-receipts="<?= $receiptHistoryPayload($receipts); ?>">
                                                <i class="fa fa-search" aria-hidden="true"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="state-pill">En revision</span>
                                        <?php if ($receipts !== []): ?>
                                            <button class="icon-button icon-button-view" type="button" title="Ver comprobantes" aria-label="Ver comprobantes" data-representative-receipts data-receipts="<?= $receiptHistoryPayload($receipts); ?>">
                                                <i class="fa fa-search" aria-hidden="true"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="accounting-file-note">Adjunte el comprobante en PDF, JPG o PNG. Tamano maximo 2 MB.</p>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($additionalItemsVisible): ?>
        <section class="security-assignment-block">
            <header class="security-assignment-header">
                <div>
                    <h3>Rubros adicionales</h3>
                    <p>Consulta cobros eventuales registrados por la institucion.</p>
                </div>
            </header>

            <?php if ($additionalGroups === []): ?>
                <div class="empty-state">No existen rubros adicionales asignados para los estudiantes vinculados.</div>
            <?php else: ?>
                <?php foreach ($additionalGroups as $group): ?>
                    <div class="table-wrap">
                    <table class="data-table compact-data-table">
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Rubro</th>
                                <th>Concepto</th>
                                <th>Valor</th>
                                <th>Fecha limite</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($group['rows'] as $row): ?>
                                <?php $status = (string) ($row['creestado'] ?? ''); ?>
                                <tr>
                                    <td>
                                        <strong><?= $h($group['student']); ?></strong>
                                        <span class="cell-subtitle"><?= $h($group['course']); ?></span>
                                    </td>
                                    <td>
                                        <?= $h($row['crunombre'] ?? ''); ?>
                                        <?php if (!empty($row['crudescripcion'])): ?>
                                            <span class="cell-subtitle"><?= $h($row['crudescripcion']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $h($row['cconombre'] ?? ''); ?></td>
                                    <td><?= $h($money($row['crevalor'] ?? 0)); ?></td>
                                    <td><?= $h($row['crefecha_limite'] ?? 'Sin fecha'); ?></td>
                                    <td><span class="state-pill <?= $status === 'PAGADO' || $status === 'EXONERADO' ? 'state-pill-active' : 'state-pill-inactive'; ?>"><?= $h($status); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    <?php endif; ?>
<?php endif; ?>

<div class="modal-backdrop" data-representative-receipts-modal hidden>
    <div class="modal-card accounting-representative-receipts-modal" role="dialog" aria-modal="true" aria-labelledby="representative-receipts-title">
        <div class="modal-header">
            <h3 id="representative-receipts-title">Comprobantes registrados</h3>
            <button class="modal-close" type="button" aria-label="Cerrar" data-representative-receipts-close>
                <i class="fa fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="empty-state" data-representative-receipts-empty hidden>No existen comprobantes registrados.</div>
            <table class="data-table" data-representative-receipts-table>
                <thead>
                    <tr>
                        <th>Registro</th>
                        <th>Estado</th>
                        <th>Valor</th>
                        <th>Archivo</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody data-representative-receipts-rows></tbody>
            </table>
        </div>
        <div class="modal-actions">
            <button class="btn-secondary btn-auto" type="button" data-representative-receipts-close>Cancelar</button>
        </div>
    </div>
</div>

<script>
const representativeReceiptStatusLabels = <?= json_encode($receiptStatusLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

document.addEventListener('change', function (event) {
    const input = event.target.closest('[data-accounting-file-input]');

    if (!input) {
        return;
    }

    const label = input.closest('.file-inline-control');
    const fileName = label ? label.querySelector('[data-accounting-file-name]') : null;

    if (fileName) {
        fileName.textContent = input.files && input.files.length > 0 ? input.files[0].name : 'Sin archivo';
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.querySelector('[data-representative-receipts-modal]');
    const table = document.querySelector('[data-representative-receipts-table]');
    const rowsContainer = document.querySelector('[data-representative-receipts-rows]');
    const emptyState = document.querySelector('[data-representative-receipts-empty]');
    const closeButtons = document.querySelectorAll('[data-representative-receipts-close]');

    const escapeHtml = function (value) {
        return String(value ?? '').replace(/[&<>"']/g, function (char) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char];
        });
    };

    const money = function (value) {
        return '$' + Number(value || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    };

    document.addEventListener('click', function (event) {
        const button = event.target.closest('[data-representative-receipts]');

        if (!button) {
            return;
        }

        let receipts = [];

        try {
            receipts = JSON.parse(button.getAttribute('data-receipts') || '[]');
        } catch (error) {
            receipts = [];
        }

        if (rowsContainer) {
            rowsContainer.innerHTML = receipts.map(function (receipt) {
                const status = String(receipt.estado || '');
                const statusClass = status === 'APROBADO' ? 'state-pill-active' : 'state-pill-inactive';
                const value = receipt.valor_aprobado || receipt.valor_reportado || 0;
                const detail = receipt.motivo_rechazo ? `Rechazo: ${receipt.motivo_rechazo}` : '';

                return `
                    <tr>
                        <td>${escapeHtml(receipt.fecha_registro || '')}</td>
                        <td><span class="state-pill ${statusClass}">${escapeHtml(representativeReceiptStatusLabels[status] || status)}</span></td>
                        <td>${escapeHtml(money(value))}</td>
                        <td>${receipt.archivo_url ? `<a class="text-link" href="${escapeHtml(receipt.archivo_url)}" target="_blank" rel="noopener">Ver archivo</a>` : '<span class="state-pill">Sin archivo</span>'}</td>
                        <td>${escapeHtml(detail)}</td>
                    </tr>
                `;
            }).join('');
        }

        if (table) {
            table.hidden = receipts.length === 0;
        }

        if (emptyState) {
            emptyState.hidden = receipts.length > 0;
        }

        if (modal) {
            modal.hidden = false;
        }
    });

    closeButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            if (modal) {
                modal.hidden = true;
            }
        });
    });
});
</script>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
