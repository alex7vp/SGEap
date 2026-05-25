<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static fn (mixed $value): string => '$' . number_format((float) $value, 2, '.', ',');
$receipts = is_array($receipts ?? null) ? $receipts : [];
$status = in_array((string) ($status ?? 'EN_REVISION'), ['EN_REVISION', 'APROBADO', 'RECHAZADO'], true) ? (string) $status : 'EN_REVISION';
$statusLabels = [
    'EN_REVISION' => 'En revision',
    'APROBADO' => 'Aprobados',
    'RECHAZADO' => 'Rechazados',
];
?>

<?php if (empty($currentPeriod)): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado para revisar comprobantes.</div>
<?php else: ?>
    <p class="module-note">Revision de comprobantes enviados por representantes para obligaciones de matricula y pensiones.</p>

    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Comprobantes</h3>
                <p>Aprueba el valor real aplicado, registra opcionalmente el numero de factura externa o rechaza con motivo.</p>
            </div>
        </header>

        <form class="toolbar toolbar-filter" method="GET" action="<?= $h(baseUrl('contabilidad/comprobantes')); ?>">
            <div class="filter-box filter-box-compact">
                <label class="sr-only" for="receipt-status">Estado</label>
                <select id="receipt-status" name="estado">
                    <?php foreach ($statusLabels as $key => $label): ?>
                        <option value="<?= $h($key); ?>" <?= $status === $key ? 'selected' : ''; ?>><?= $h($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn-secondary btn-auto" type="submit">
                <i class="fa fa-filter" aria-hidden="true"></i>
                Filtrar
            </button>
            <span class="table-status"><?= count($receipts); ?> registro(s)</span>
        </form>

        <?php if ($receipts === []): ?>
            <div class="empty-state">No existen comprobantes con el estado seleccionado.</div>
        <?php else: ?>
            <table class="data-table accounting-receipts-table">
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Obligacion</th>
                        <th>Saldo</th>
                        <th>Comprobante</th>
                        <th>Revision</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($receipts as $receipt): ?>
                        <?php
                        $studentName = trim((string) (($receipt['perapellidos'] ?? '') . ' ' . ($receipt['pernombres'] ?? '')));
                        $fileUrl = !empty($receipt['cpagarchivo_ruta']) ? asset((string) $receipt['cpagarchivo_ruta']) : '';
                        $duplicateId = (int) ($receipt['duplicado_cpagid'] ?? 0);
                        ?>
                        <tr>
                            <td>
                                <strong><?= $h($studentName !== '' ? $studentName : 'Estudiante'); ?></strong>
                                <span class="cell-subtitle"><?= $h(trim((string) (($receipt['granombre'] ?? '') . ' ' . ($receipt['prlnombre'] ?? '')))); ?></span>
                            </td>
                            <td>
                                <?= $h($receipt['cobdescripcion'] ?? ''); ?>
                                <span class="cell-subtitle">Registrado: <?= $h($receipt['cpagfecha_registro'] ?? ''); ?></span>
                                <?php if ($duplicateId > 0): ?>
                                    <span class="cell-subtitle">Posible duplicado: #<?= $h($duplicateId); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= $h($money($receipt['cobsaldo_pendiente'] ?? 0)); ?></strong>
                                <span class="cell-subtitle">Valor sugerido: <?= $h($money($receipt['cpagvalor_reportado'] ?? 0)); ?></span>
                            </td>
                            <td>
                                <?php if ($fileUrl !== ''): ?>
                                    <a class="text-link" href="<?= $h($fileUrl); ?>" target="_blank" rel="noopener">Ver archivo</a>
                                    <span class="cell-subtitle"><?= $h($receipt['cpagarchivo_nombre'] ?? ''); ?></span>
                                <?php else: ?>
                                    <span class="state-pill">Sin archivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($status === 'EN_REVISION'): ?>
                                    <div class="accounting-review-actions">
                                        <form class="accounting-review-form" method="POST" action="<?= $h(baseUrl('contabilidad/comprobantes/aprobar')); ?>" data-confirm-message="Se aprobara y aplicara el comprobante seleccionado.">
                                            <?= csrfField(); ?>
                                            <input type="hidden" name="cpagid" value="<?= $h($receipt['cpagid'] ?? ''); ?>">
                                            <label>
                                                <span>Valor</span>
                                                <input class="table-input accounting-review-value-input" type="number" name="valor_aprobado" min="0.01" step="0.01" value="<?= $h(number_format((float) ($receipt['cobsaldo_pendiente'] ?? $receipt['cpagvalor_reportado'] ?? 0), 2, '.', '')); ?>" required>
                                            </label>
                                            <label>
                                                <span>Factura</span>
                                                <input class="table-input accounting-review-invoice-input" type="text" name="factura" maxlength="80" placeholder="Opcional">
                                            </label>
                                            <label class="accounting-review-observation">
                                                <span>Observacion</span>
                                                <input class="table-input" type="text" name="observacion" maxlength="250" <?= $duplicateId > 0 ? 'required' : ''; ?>>
                                            </label>
                                            <?php if ($duplicateId > 0): ?>
                                                <label class="checkbox-inline">
                                                    <input type="checkbox" name="confirmar_duplicado" value="1" required>
                                                    <span>Continuar pese al duplicado</span>
                                                </label>
                                            <?php endif; ?>
                                            <button class="icon-button icon-button-save" type="submit" title="Aprobar" aria-label="Aprobar">
                                                <i class="fa fa-check" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                        <form class="accounting-review-form accounting-review-reject-form" method="POST" action="<?= $h(baseUrl('contabilidad/comprobantes/rechazar')); ?>" data-confirm-message="Se rechazara el comprobante y el representante podra volver a registrarlo.">
                                            <?= csrfField(); ?>
                                            <input type="hidden" name="cpagid" value="<?= $h($receipt['cpagid'] ?? ''); ?>">
                                            <label class="accounting-review-observation">
                                                <span>Motivo</span>
                                                <input class="table-input" type="text" name="motivo" maxlength="250" required>
                                            </label>
                                            <button class="icon-button icon-button-delete" type="submit" title="Rechazar" aria-label="Rechazar">
                                                <i class="fa fa-times" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="state-pill <?= $status === 'APROBADO' ? 'state-pill-active' : 'state-pill-inactive'; ?>"><?= $h($statusLabels[$status]); ?></span>
                                    <?php if (!empty($receipt['cpagmotivo_rechazo'])): ?>
                                        <span class="cell-subtitle"><?= $h($receipt['cpagmotivo_rechazo']); ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
<?php endif; ?>

<div class="modal-backdrop" data-accounting-review-confirm hidden>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="accounting-review-confirm-title">
        <div class="modal-header">
            <h3 id="accounting-review-confirm-title">Confirmar revision</h3>
            <button class="modal-close" type="button" aria-label="Cerrar" data-accounting-review-cancel>
                <i class="fa fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <div class="modal-body">
            <p data-accounting-review-confirm-message></p>
        </div>
        <div class="modal-actions">
            <button class="btn-secondary btn-auto" type="button" data-accounting-review-cancel>Cancelar</button>
            <button class="icon-button icon-button-save" type="button" title="Confirmar" aria-label="Confirmar" data-accounting-review-accept>
                <i class="fa fa-check" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.querySelector('[data-accounting-review-confirm]');
    const message = document.querySelector('[data-accounting-review-confirm-message]');
    const accept = document.querySelector('[data-accounting-review-accept]');
    const cancelButtons = document.querySelectorAll('[data-accounting-review-cancel]');
    let pendingForm = null;

    document.addEventListener('submit', function (event) {
        const form = event.target.closest('[data-confirm-message]');

        if (!form || form.dataset.confirmed === '1') {
            return;
        }

        event.preventDefault();
        pendingForm = form;

        if (message) {
            message.textContent = form.getAttribute('data-confirm-message') || 'Confirme la operacion.';
        }

        if (modal) {
            modal.hidden = false;
        }
    });

    if (accept) {
        accept.addEventListener('click', function () {
            if (!pendingForm) {
                return;
            }

            pendingForm.dataset.confirmed = '1';
            pendingForm.submit();
        });
    }

    cancelButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            pendingForm = null;

            if (modal) {
                modal.hidden = true;
            }
        });
    });
});
</script>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
