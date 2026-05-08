<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$studentName = trim((string) (($matricula['perapellidos'] ?? '') . ' ' . ($matricula['pernombres'] ?? '')));
$courseLabel = trim((string) (($matricula['nednombre'] ?? '') . ' | ' . ($matricula['granombre'] ?? '') . ' | ' . ($matricula['prlnombre'] ?? '')));
?>

<section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3>Editar matricula</h3>
            <p>Actualiza los datos administrativos de la matricula seleccionada.</p>
        </div>
        <a class="btn-secondary btn-auto" href="<?= $h(baseUrl('matriculas?panel=gestion#matriculas-registradas')); ?>">Volver</a>
    </header>

    <?php if (!empty($feedback)): ?>
        <div class="catalog-feedback security-feedback-global">
            <div class="alert <?= ($feedback['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success'; ?> alert-dismissible" data-alert>
                <span><?= $h($feedback['message'] ?? ''); ?></span>
                <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                    <i class="fa fa-times" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <div class="data-summary-grid">
        <div>
            <span class="cell-title">Estudiante</span>
            <span class="cell-subtitle"><?= $h($matricula['percedula'] ?? ''); ?> | <?= $h($studentName); ?></span>
        </div>
        <div>
            <span class="cell-title">Curso actual</span>
            <span class="cell-subtitle"><?= $h($courseLabel); ?></span>
        </div>
        <div>
            <span class="cell-title">Periodo</span>
            <span class="cell-subtitle"><?= $h($matricula['pledescripcion'] ?? $currentPeriod['pledescripcion'] ?? ''); ?></span>
        </div>
    </div>

    <form method="POST" action="<?= $h(baseUrl('matriculas/actualizar')); ?>">
        <input type="hidden" name="matid" value="<?= $h($matricula['matid'] ?? 0); ?>">

        <div class="form-grid">
            <div class="form-group form-group-full">
                <div class="input-group">
                    <span class="input-addon">Curso</span>
                    <select name="curid" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($courses as $course): ?>
                            <?php $label = (string) (($course['nednombre'] ?? '') . ' | ' . ($course['granombre'] ?? '') . ' | ' . ($course['prlnombre'] ?? '')); ?>
                            <option value="<?= $h($course['curid'] ?? 0); ?>" <?= (int) ($matricula['curid'] ?? 0) === (int) ($course['curid'] ?? 0) ? 'selected' : ''; ?>>
                                <?= $h($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Fecha</span>
                    <input type="date" name="matfecha" required value="<?= $h($matricula['matfecha'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Tipo</span>
                    <select name="tmaid" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($enrollmentTypes as $type): ?>
                            <option value="<?= $h($type['tmaid'] ?? 0); ?>" <?= (int) ($matricula['tmaid'] ?? 0) === (int) ($type['tmaid'] ?? 0) ? 'selected' : ''; ?>>
                                <?= $h($type['tmanombre'] ?? ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Estado</span>
                    <select name="emdid" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($enrollmentStatuses as $status): ?>
                            <option value="<?= $h($status['emdid'] ?? 0); ?>" <?= (int) ($matricula['emdid'] ?? 0) === (int) ($status['emdid'] ?? 0) ? 'selected' : ''; ?>>
                                <?= $h($status['emdnombre'] ?? ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Retiro</span>
                    <input type="date" name="matfecha_retiro" value="<?= $h($matricula['matfecha_retiro'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group form-group-full">
                <div class="input-group">
                    <span class="input-addon">Motivo retiro</span>
                    <input name="matmotivo_retiro" maxlength="250" value="<?= $h($matricula['matmotivo_retiro'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <div class="actions-row">
            <button class="btn-primary btn-inline" type="submit">Guardar cambios</button>
            <a class="btn-secondary btn-inline" href="<?= $h(baseUrl('matriculas?panel=gestion#matriculas-registradas')); ?>">Cancelar</a>
        </div>
    </form>
</section>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
