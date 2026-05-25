<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static fn (mixed $value): string => '$' . number_format((float) $value, 2, '.', ',');
$obligations = is_array($obligations ?? null) ? $obligations : [];
$payableStatuses = ['PENDIENTE', 'PAGO_PARCIAL', 'VENCIDO'];
?>

<?php if (empty($currentPeriod)): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado para consultar pagos.</div>
<?php else: ?>
    <p class="module-note">Consulta obligaciones, pagos registrados y comprobantes pendientes del periodo <?= $h($currentPeriod['pledescripcion'] ?? ''); ?>.</p>

    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Obligaciones y comprobantes</h3>
                <p>Seleccione la obligacion pendiente y adjunte el comprobante en PDF, JPG o PNG. Tamano maximo 2 MB.</p>
            </div>
        </header>

        <?php if ($obligations === []): ?>
            <div class="empty-state">No existen obligaciones generadas para los estudiantes vinculados.</div>
        <?php else: ?>
            <table class="data-table accounting-representative-table">
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Obligacion</th>
                        <th>Valor</th>
                        <th>Pagado</th>
                        <th>Saldo</th>
                        <th>Estado</th>
                        <th>Comprobante</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($obligations as $row): ?>
                        <?php
                        $studentName = trim((string) (($row['perapellidos'] ?? '') . ' ' . ($row['pernombres'] ?? '')));
                        $status = (string) ($row['cobestado'] ?? '');
                        $paymentStatus = (string) ($row['cpagestado'] ?? '');
                        $canUpload = in_array($status, $payableStatuses, true) && $paymentStatus !== 'EN_REVISION';
                        ?>
                        <tr>
                            <td>
                                <strong><?= $h($studentName !== '' ? $studentName : 'Estudiante'); ?></strong>
                                <span class="cell-subtitle"><?= $h(trim((string) (($row['granombre'] ?? '') . ' ' . ($row['prlnombre'] ?? '')))); ?></span>
                            </td>
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
                                        <input type="hidden" name="estudiante" value="<?= $h($studentName); ?>">
                                        <label class="file-inline-control">
                                            <span class="sr-only">Comprobante</span>
                                            <input type="file" name="comprobante" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
                                        </label>
                                        <button class="icon-button icon-button-save" type="submit" title="Enviar comprobante" aria-label="Enviar comprobante">
                                            <i class="fa fa-upload" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                <?php elseif ($status === 'PAGADO'): ?>
                                    <span class="state-pill state-pill-active">Pagado</span>
                                <?php else: ?>
                                    <span class="state-pill">En revision</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
