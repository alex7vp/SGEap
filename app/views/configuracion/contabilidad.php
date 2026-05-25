<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$old = is_array($old ?? null) ? $old : [];
$settings = is_array($settings ?? null) ? $settings : [];
$periods = is_array($periods ?? null) ? $periods : [];
$levels = is_array($levels ?? null) ? $levels : [];
$grades = is_array($grades ?? null) ? $grades : [];
$courses = is_array($courses ?? null) ? $courses : [];
$editingId = (int) ($old['cfoid'] ?? 0);
$selectedMode = $editingId > 0 || !empty($feedback) || $settings === [] ? 'form' : ((string) ($_GET['panel'] ?? '') === 'list' ? 'list' : 'form');
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

    if ($scope === 'CURSO') {
        return trim((string) (($row['curso_nednombre'] ?? '') . ' | ' . ($row['curso_granombre'] ?? '') . ' ' . ($row['prlnombre'] ?? '')));
    }

    return 'Sin alcance';
};
?>

<nav class="module-subnav" aria-label="Submodulos de configuracion contable">
    <a href="<?= $h(baseUrl('configuracion/academica')); ?>">Configuracion academica</a>
    <a class="is-active" href="<?= $h(baseUrl('configuracion/contable')); ?>">Configuracion contable</a>
</nav>

<p class="module-note">Administra los valores oficiales y reglas base que luego usara Gestion Contable para asignar valores a estudiantes y generar obligaciones.</p>

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
        </div>
    </section>

    <section class="security-assignment-block" data-option-view-panel="form" <?= $selectedMode === 'form' ? '' : 'hidden'; ?>>
        <header class="security-assignment-header">
            <div>
                <h3><?= $editingId > 0 ? 'Editar configuracion contable' : 'Nueva configuracion contable'; ?></h3>
                <p>Define el valor oficial y las reglas base por institucion, nivel, grado o curso.</p>
            </div>
        </header>

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
                            <span class="input-addon">Tipo</span>
                            <select name="cfotipo" required>
                                <option value="PENSION" <?= (string) ($old['cfotipo'] ?? 'PENSION') === 'PENSION' ? 'selected' : ''; ?>>Pension</option>
                                <option value="MATRICULA" <?= (string) ($old['cfotipo'] ?? '') === 'MATRICULA' ? 'selected' : ''; ?>>Matricula</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-addon">Alcance</span>
                            <select name="cfoalcance" required>
                                <option value="INSTITUCION" <?= (string) ($old['cfoalcance'] ?? '') === 'INSTITUCION' ? 'selected' : ''; ?>>Institucion</option>
                                <option value="NIVEL" <?= (string) ($old['cfoalcance'] ?? 'NIVEL') === 'NIVEL' ? 'selected' : ''; ?>>Nivel</option>
                                <option value="GRADO" <?= (string) ($old['cfoalcance'] ?? '') === 'GRADO' ? 'selected' : ''; ?>>Grado</option>
                                <option value="CURSO" <?= (string) ($old['cfoalcance'] ?? '') === 'CURSO' ? 'selected' : ''; ?>>Curso</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
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

                    <div class="form-group">
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
                            <span class="input-addon">Curso</span>
                            <select name="curid">
                                <option value="">Seleccione</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= $h($course['curid'] ?? ''); ?>" <?= (string) ($old['curid'] ?? '') === (string) ($course['curid'] ?? '') ? 'selected' : ''; ?>>
                                        <?= $h(($course['nednombre'] ?? '') . ' | ' . ($course['granombre'] ?? '') . ' ' . ($course['prlnombre'] ?? '')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-addon">Valor oficial</span>
                            <input type="number" name="cfovalor_oficial" step="0.01" min="0" value="<?= $h($old['cfovalor_oficial'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-addon">Cantidad pensiones</span>
                            <input type="number" name="cfocantidad_pensiones" min="1" max="12" value="<?= $h($old['cfocantidad_pensiones'] ?? '10'); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-addon">Mes inicial</span>
                            <select name="cfomes_inicio">
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

                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-addon">Mora</span>
                            <select name="cfogenera_mora">
                                <option value="0" <?= (string) ($old['cfogenera_mora'] ?? '0') === '0' ? 'selected' : ''; ?>>No genera</option>
                                <option value="1" <?= (string) ($old['cfogenera_mora'] ?? '') === '1' ? 'selected' : ''; ?>>Genera</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-addon">Tipo mora</span>
                            <select name="cfomora_tipo">
                                <option value="VALOR_FIJO" <?= (string) ($old['cfomora_tipo'] ?? 'VALOR_FIJO') === 'VALOR_FIJO' ? 'selected' : ''; ?>>Valor fijo</option>
                                <option value="PORCENTAJE" <?= (string) ($old['cfomora_tipo'] ?? '') === 'PORCENTAJE' ? 'selected' : ''; ?>>Porcentaje</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
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

    <section class="security-assignment-block" id="configuraciones-contables" data-option-view-panel="list" <?= $selectedMode === 'list' ? '' : 'hidden'; ?>>
        <header class="security-assignment-header">
            <div>
                <h3>Configuraciones registradas</h3>
                <p>Valores base que se aplicaran como referencia antes de asignar valores individuales.</p>
            </div>
        </header>

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

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
