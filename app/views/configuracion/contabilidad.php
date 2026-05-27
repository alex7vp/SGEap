<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$old = is_array($old ?? null) ? $old : [];
$settings = is_array($settings ?? null) ? $settings : [];
$moduleSettings = is_array($moduleSettings ?? null) ? $moduleSettings : [];
$periods = is_array($periods ?? null) ? $periods : [];
$levels = is_array($levels ?? null) ? $levels : [];
$grades = is_array($grades ?? null) ? $grades : [];
$editingId = (int) ($old['cfoid'] ?? 0);
$requestedPanel = (string) ($_GET['panel'] ?? '');
$selectedMode = $editingId > 0 || (!empty($feedback) && $requestedPanel !== 'services') || $settings === []
    ? 'form'
    : ($requestedPanel === 'services' ? 'services' : ($requestedPanel === 'list' ? 'list' : 'form'));
$monthNames = [
    1 => 'Enero',
    2 => 'Febrero',
    3 => 'Marzo',
    4 => 'Abril',
    5 => 'Mayo',
    6 => 'Junio',
    7 => 'Julio',
    8 => 'Agosto',
    9 => 'Septiembre',
    10 => 'Octubre',
    11 => 'Noviembre',
    12 => 'Diciembre',
];
$scopeLabel = static function (array $row): string {
    $scope = (string) ($row['cfoalcance'] ?? '');

    if ($scope === 'INSTITUCION') {
        return 'Institucion';
    }

    if ($scope === 'NIVEL') {
        return (string) ($row['nednombre'] ?? 'Nivel');
    }

    if ($scope === 'GRADO') {
        return trim((string) (($row['grado_nednombre'] ?? '') . ' | ' . ($row['granombre'] ?? 'Grado')));
    }

    return 'Sin alcance';
};
?>

<?php if (!empty($feedback)): ?>
    <div class="alert <?= ($feedback['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success'; ?> alert-dismissible" data-alert>
        <span><?= $h($feedback['message'] ?? ''); ?></span>
        <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
            <i class="fa fa-times" aria-hidden="true"></i>
        </button>
    </div>
<?php endif; ?>

<section class="grade-config-view-stack">
    <section class="security-assignment-block grade-profile-creation-block" data-option-view-mode>
        <div class="grade-profile-mode-selector" role="radiogroup" aria-label="Vista de configuracion contable">
            <label class="grade-profile-mode-option">
                <input type="radio" name="accounting_config_mode" value="form" <?= $selectedMode === 'form' ? 'checked' : ''; ?> data-option-view-radio>
                <span><?= $editingId > 0 ? 'Editar configuracion' : 'Nueva configuracion'; ?></span>
            </label>
            <label class="grade-profile-mode-option">
                <input type="radio" name="accounting_config_mode" value="list" <?= $selectedMode === 'list' ? 'checked' : ''; ?> data-option-view-radio>
                <span>Configuraciones registradas</span>
            </label>
            <label class="grade-profile-mode-option">
                <input type="radio" name="accounting_config_mode" value="services" <?= $selectedMode === 'services' ? 'checked' : ''; ?> data-option-view-radio>
                <span>Servicios</span>
            </label>
        </div>
    </section>

    <section class="security-assignment-block" data-option-view-panel="form" <?= $selectedMode === 'form' ? '' : 'hidden'; ?>>
        <?php if ($periods === []): ?>
            <div class="empty-state">Debe registrar al menos un periodo lectivo antes de configurar valores contables.</div>
        <?php else: ?>
            <form class="data-form" method="POST" action="<?= $h(baseUrl($editingId > 0 ? 'configuracion/contable/actualizar' : 'configuracion/contable')); ?>">
                <?= csrfField(); ?>
                <?php if ($editingId > 0): ?>
                    <input type="hidden" name="cfoid" value="<?= $h($editingId); ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-addon">Periodo</span>
                            <select name="pleid" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($periods as $period): ?>
                                    <option value="<?= $h($period['pleid'] ?? ''); ?>" <?= (string) ($old['pleid'] ?? '') === (string) ($period['pleid'] ?? '') ? 'selected' : ''; ?>>
                                        <?= $h($period['pledescripcion'] ?? ''); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-addon">Alcance</span>
                            <select name="cfoalcance" required data-accounting-scope>
                                <option value="INSTITUCION" <?= (string) ($old['cfoalcance'] ?? '') === 'INSTITUCION' ? 'selected' : ''; ?>>Institucion</option>
                                <option value="NIVEL" <?= (string) ($old['cfoalcance'] ?? 'NIVEL') === 'NIVEL' ? 'selected' : ''; ?>>Nivel</option>
                                <option value="GRADO" <?= (string) ($old['cfoalcance'] ?? '') === 'GRADO' ? 'selected' : ''; ?>>Grado</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" data-accounting-scope-field="NIVEL">
                        <div class="input-group">
                            <span class="input-addon">Nivel</span>
                            <select name="nedid">
                                <option value="">Seleccione</option>
                                <?php foreach ($levels as $level): ?>
                                    <option value="<?= $h($level['nedid'] ?? ''); ?>" <?= (string) ($old['nedid'] ?? '') === (string) ($level['nedid'] ?? '') ? 'selected' : ''; ?>>
                                        <?= $h($level['nednombre'] ?? ''); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" data-accounting-scope-field="GRADO">
                        <div class="input-group">
                            <span class="input-addon">Grado</span>
                            <select name="graid">
                                <option value="">Seleccione</option>
                                <?php foreach ($grades as $grade): ?>
                                    <option value="<?= $h($grade['graid'] ?? ''); ?>" <?= (string) ($old['graid'] ?? '') === (string) ($grade['graid'] ?? '') ? 'selected' : ''; ?>>
                                        <?= $h(($grade['nednombre'] ?? '') . ' | ' . ($grade['granombre'] ?? '')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-addon">Valor pension</span>
                            <input type="number" name="cfovalor_pension" step="0.01" min="0" value="<?= $h($old['cfovalor_pension'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-addon">Valor matricula</span>
                            <input type="number" name="cfovalor_matricula" step="0.01" min="0" value="<?= $h($old['cfovalor_matricula'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-addon">Cantidad pensiones</span>
                            <input type="number" name="cfocantidad_pensiones" min="1" max="12" value="<?= $h($old['cfocantidad_pensiones'] ?? '10'); ?>" readonly data-accounting-months-count>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-addon">Mes inicial</span>
                            <select name="cfomes_inicio" data-accounting-start-month>
                                <?php foreach ($monthNames as $monthNumber => $monthName): ?>
                                    <option value="<?= $h($monthNumber); ?>" <?= (string) ($old['cfomes_inicio'] ?? '9') === (string) $monthNumber ? 'selected' : ''; ?>>
                                        <?= $h($monthName); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-addon">Mes final</span>
                            <select name="cfomes_fin" data-accounting-end-month>
                                <?php foreach ($monthNames as $monthNumber => $monthName): ?>
                                    <option value="<?= $h($monthNumber); ?>" <?= (string) ($old['cfomes_fin'] ?? '6') === (string) $monthNumber ? 'selected' : ''; ?>>
                                        <?= $h($monthName); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-addon">Año inicial</span>
                            <input type="number" name="cfoanio_inicio" min="2000" max="2100" value="<?= $h($old['cfoanio_inicio'] ?? date('Y')); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-addon">Vence dia</span>
                            <input type="number" name="cfodia_vencimiento" min="1" max="28" value="<?= $h($old['cfodia_vencimiento'] ?? '5'); ?>" required>
                        </div>
                    </div>

                    <div class="form-group" data-accounting-late-fee-field>
                        <div class="input-group">
                            <span class="input-addon">Mora</span>
                            <select name="cfogenera_mora" data-accounting-late-fee>
                                <option value="0" <?= (string) ($old['cfogenera_mora'] ?? '0') === '0' ? 'selected' : ''; ?>>No genera</option>
                                <option value="1" <?= (string) ($old['cfogenera_mora'] ?? '') === '1' ? 'selected' : ''; ?>>Genera</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" data-accounting-late-fee-field>
                        <div class="input-group">
                            <span class="input-addon">Tipo mora</span>
                            <select name="cfomora_tipo">
                                <option value="VALOR_FIJO" <?= (string) ($old['cfomora_tipo'] ?? 'VALOR_FIJO') === 'VALOR_FIJO' ? 'selected' : ''; ?>>Valor fijo</option>
                                <option value="PORCENTAJE" <?= (string) ($old['cfomora_tipo'] ?? '') === 'PORCENTAJE' ? 'selected' : ''; ?>>Porcentaje</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" data-accounting-late-fee-field>
                        <div class="input-group">
                            <span class="input-addon">Valor mora</span>
                            <input type="number" name="cfomora_valor" step="0.01" min="0" value="<?= $h($old['cfomora_valor'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-addon">Estado</span>
                            <select name="cfoestado">
                                <option value="1" <?= (string) ($old['cfoestado'] ?? '1') === '1' ? 'selected' : ''; ?>>Activa</option>
                                <option value="0" <?= (string) ($old['cfoestado'] ?? '') === '0' ? 'selected' : ''; ?>>Inactiva</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group form-group-full">
                        <div class="input-group">
                            <span class="input-addon">Observacion</span>
                            <input name="cfoobservacion" maxlength="250" value="<?= $h($old['cfoobservacion'] ?? ''); ?>" placeholder="Uso interno">
                        </div>
                    </div>
                </div>

                <div class="actions-row">
                    <button class="btn-primary btn-auto" type="submit"><?= $editingId > 0 ? 'Actualizar configuracion' : 'Guardar configuracion'; ?></button>
                    <?php if ($editingId > 0): ?>
                        <a class="btn-secondary btn-auto" href="<?= $h(baseUrl('configuracion/contable')); ?>">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    </section>

    <section class="security-assignment-block" id="servicios-contables" data-option-view-panel="services" <?= $selectedMode === 'services' ? '' : 'hidden'; ?>>
        <?php if ($moduleSettings === []): ?>
            <div class="empty-state">Debe registrar al menos un periodo lectivo antes de configurar servicios contables.</div>
        <?php else: ?>
            <div class="table-wrap">
            <table class="data-table compact-data-table">
                <thead>
                    <tr>
                        <th>Periodo</th>
                        <th>Rubros adicionales para representantes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($moduleSettings as $moduleSetting): ?>
                        <tr>
                            <td><strong><?= $h($moduleSetting['pledescripcion'] ?? ''); ?></strong></td>
                            <td>
                                <form class="accounting-actions-inline" id="module-setting-<?= $h($moduleSetting['pleid'] ?? ''); ?>" method="POST" action="<?= $h(baseUrl('configuracion/contable/servicios')); ?>">
                                    <?= csrfField(); ?>
                                    <input type="hidden" name="pleid" value="<?= $h($moduleSetting['pleid'] ?? ''); ?>">
                                    <input type="hidden" name="representante_rubros_visible" value="0">
                                    <label class="concept-status-switch accounting-service-switch">
                                        <span data-accounting-service-label><?= !empty($moduleSetting['representante_rubros_visible']) ? 'Activado' : 'Desactivado'; ?></span>
                                        <span class="switch-control switch-control-xsmall">
                                            <input type="checkbox" name="representante_rubros_visible" value="1" <?= !empty($moduleSetting['representante_rubros_visible']) ? 'checked' : ''; ?> data-accounting-service-switch>
                                            <span class="switch-slider switch-slider-xsmall" aria-hidden="true"></span>
                                        </span>
                                    </label>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <p class="module-note">El permiso del rol controla quien puede entrar. Esta opcion controla si el servicio se publica para representantes en el periodo seleccionado.</p>
        <?php endif; ?>
    </section>

    <section class="security-assignment-block" id="configuraciones-contables" data-option-view-panel="list" <?= $selectedMode === 'list' ? '' : 'hidden'; ?>>
        <?php if ($settings === []): ?>
            <div class="empty-state">Todavia no existen configuraciones contables registradas.</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Periodo</th>
                        <th>Tipo</th>
                        <th>Alcance</th>
                        <th>Valor</th>
                        <th>Reglas</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($settings as $setting): ?>
                        <tr>
                            <td><?= $h($setting['pledescripcion'] ?? ''); ?></td>
                            <td><?= $h($setting['cfotipo'] === 'PENSION' ? 'Pension' : 'Matricula'); ?></td>
                            <td><?= $h($scopeLabel($setting)); ?></td>
                            <td>$<?= $h(number_format((float) ($setting['cfovalor_oficial'] ?? 0), 2)); ?></td>
                            <td>
                                <?php if (($setting['cfotipo'] ?? '') === 'PENSION'): ?>
                                    <span class="cell-subtitle">
                                        <?= $h((string) ($setting['cfocantidad_pensiones'] ?? '')); ?> pensiones desde
                                        <?= $h($monthNames[(int) ($setting['cfomes_inicio'] ?? 0)] ?? ''); ?>
                                        hasta <?= $h($monthNames[(int) ($setting['cfomes_fin'] ?? 0)] ?? ''); ?>
                                        <?= $h($setting['cfoanio_inicio'] ?? ''); ?>, vence dia <?= $h($setting['cfodia_vencimiento'] ?? ''); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="cell-subtitle">Obligacion inicial del periodo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="state-pill <?= !empty($setting['cfoestado']) ? 'state-pill-active' : 'state-pill-inactive'; ?>">
                                    <?= !empty($setting['cfoestado']) ? 'Activa' : 'Inactiva'; ?>
                                </span>
                            </td>
                            <td>
                                <a class="icon-button icon-button-edit" href="<?= $h(baseUrl('configuracion/contable') . '?edit=' . (int) $setting['cfoid']); ?>" title="Editar configuracion" aria-label="Editar configuracion">
                                    <i class="fa fa-pencil" aria-hidden="true"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const scopeSelect = document.querySelector('[data-accounting-scope]');
    const scopeFields = document.querySelectorAll('[data-accounting-scope-field]');
    const startMonthSelect = document.querySelector('[data-accounting-start-month]');
    const endMonthSelect = document.querySelector('[data-accounting-end-month]');
    const monthsCountInput = document.querySelector('[data-accounting-months-count]');
    const lateFeeSelect = document.querySelector('[data-accounting-late-fee]');
    const lateFeeFields = document.querySelectorAll('[data-accounting-late-fee-field]');

    const updateScopeFields = function () {
        if (!scopeSelect || scopeFields.length === 0) {
            return;
        }

        const selectedScope = scopeSelect.value;

        scopeFields.forEach(function (field) {
            const isVisible = field.getAttribute('data-accounting-scope-field') === selectedScope;
            const inputs = field.querySelectorAll('select, input, textarea');

            field.hidden = !isVisible;
            inputs.forEach(function (input) {
                input.disabled = !isVisible;
                if (!isVisible) {
                    input.value = '';
                }
            });
        });
    };

    const updateMonthsCount = function () {
        if (!startMonthSelect || !endMonthSelect || !monthsCountInput) {
            return;
        }

        const startMonth = parseInt(startMonthSelect.value, 10);
        const endMonth = parseInt(endMonthSelect.value, 10);

        if (Number.isNaN(startMonth) || Number.isNaN(endMonth)) {
            monthsCountInput.value = '';
            return;
        }

        monthsCountInput.value = endMonth >= startMonth
            ? endMonth - startMonth + 1
            : (12 - startMonth + 1) + endMonth;
    };

    const updateLateFeeFields = function () {
        if (!lateFeeSelect || lateFeeFields.length === 0) {
            return;
        }

        const generatesLateFee = lateFeeSelect.value === '1';

        lateFeeFields.forEach(function (field) {
            const isControlField = field.contains(lateFeeSelect);
            const inputs = field.querySelectorAll('select, input, textarea');

            if (!isControlField) {
                field.hidden = !generatesLateFee;
            }

            inputs.forEach(function (input) {
                if (input === lateFeeSelect) {
                    return;
                }

                input.disabled = !generatesLateFee;
                if (!generatesLateFee) {
                    input.value = '';
                }
            });
        });
    };

    if (scopeSelect) {
        scopeSelect.addEventListener('change', updateScopeFields);
    }

    if (startMonthSelect) {
        startMonthSelect.addEventListener('change', updateMonthsCount);
    }

    if (endMonthSelect) {
        endMonthSelect.addEventListener('change', updateMonthsCount);
    }

    if (lateFeeSelect) {
        lateFeeSelect.addEventListener('change', updateLateFeeFields);
    }

    document.querySelectorAll('[data-accounting-service-switch]').forEach(function (switchInput) {
        const label = switchInput.closest('.accounting-service-switch')?.querySelector('[data-accounting-service-label]');
        const syncLabel = function () {
            if (label) {
                label.textContent = switchInput.checked ? 'Activado' : 'Desactivado';
            }
        };

        switchInput.addEventListener('change', syncLabel);
        switchInput.addEventListener('change', function () {
            const form = switchInput.form;

            if (form) {
                form.submit();
            }
        });
        syncLabel();
    });

    updateScopeFields();
    updateMonthsCount();
    updateLateFeeFields();
});
</script>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
