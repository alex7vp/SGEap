<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$editingConfigurationId = (int) ($old['cmid'] ?? 0);
?>
<p class="module-note">Administra la ventana ordinaria y extraordinaria de matricula para cada periodo lectivo sin alterar el estado activo del periodo.</p>

<section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3><?= $editingConfigurationId > 0 ? 'Editar configuracion de matricula' : 'Nueva configuracion de matricula'; ?></h3>
            <p>Define la habilitacion y el rango de fechas para matricula ordinaria y extraordinaria.</p>
        </div>
    </header>

    <form class="data-form matriculation-config-form" method="POST" action="<?= htmlspecialchars(baseUrl($editingConfigurationId > 0 ? 'configuracion/matricula/actualizar' : 'configuracion/matricula'), ENT_QUOTES, 'UTF-8'); ?>">
        <?php if ($editingConfigurationId > 0): ?>
            <input type="hidden" name="cmid" value="<?= htmlspecialchars((string) $editingConfigurationId, ENT_QUOTES, 'UTF-8'); ?>">
        <?php endif; ?>

        <div class="form-grid">
            <div class="form-group form-group-full">
                <div class="input-group">
                    <span class="input-addon">Periodo</span>
                    <select name="pleid" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($periods as $period): ?>
                            <option value="<?= htmlspecialchars((string) $period['pleid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($old['pleid'] ?? 0) === (int) $period['pleid'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars((string) $period['pledescripcion'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Ordinaria</span>
                    <select name="cmhabilitada">
                        <option value="1" <?= ($old['cmhabilitada'] ?? '0') === '1' ? 'selected' : ''; ?>>Habilitada</option>
                        <option value="0" <?= ($old['cmhabilitada'] ?? '0') === '0' ? 'selected' : ''; ?>>Cerrada</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Extraordinaria</span>
                    <select name="cmhabilitadaextraordinaria">
                        <option value="1" <?= ($old['cmhabilitadaextraordinaria'] ?? '0') === '1' ? 'selected' : ''; ?>>Habilitada</option>
                        <option value="0" <?= ($old['cmhabilitadaextraordinaria'] ?? '0') === '0' ? 'selected' : ''; ?>>Cerrada</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Inicio ordinaria</span>
                    <input type="date" name="cmfechainicio" value="<?= htmlspecialchars((string) ($old['cmfechainicio'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Fin ordinaria</span>
                    <input type="date" name="cmfechafin" value="<?= htmlspecialchars((string) ($old['cmfechafin'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Inicio extraordinaria</span>
                    <input type="date" name="cmfechainicioextraordinaria" value="<?= htmlspecialchars((string) ($old['cmfechainicioextraordinaria'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Fin extraordinaria</span>
                    <input type="date" name="cmfechafinextraordinaria" value="<?= htmlspecialchars((string) ($old['cmfechafinextraordinaria'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="form-group form-group-full">
                <div class="input-group">
                    <span class="input-addon">Observacion</span>
                    <input type="text" name="cmobservacion" value="<?= htmlspecialchars((string) ($old['cmobservacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
        </div>

        <div class="actions-row">
            <button class="btn-primary btn-auto btn-icon-only btn-icon-small" type="submit" title="<?= htmlspecialchars($editingConfigurationId > 0 ? 'Actualizar configuracion' : 'Guardar configuracion', ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?= htmlspecialchars($editingConfigurationId > 0 ? 'Actualizar configuracion' : 'Guardar configuracion', ENT_QUOTES, 'UTF-8'); ?>">
                <i class="fa fa-save" aria-hidden="true"></i>
            </button>
        </div>
    </form>
</section>

<section class="security-assignment-block" id="configuracion-matricula-registrada">
    <header class="security-assignment-header">
        <div>
            <h3>Configuraciones registradas</h3>
            <p>Cada periodo lectivo puede tener una sola configuracion de matricula con ventana ordinaria y extraordinaria.</p>
        </div>
    </header>

    <?php if (!empty($matriculationConfigFeedback)): ?>
        <div class="catalog-feedback security-feedback-global">
            <div class="alert <?= ($matriculationConfigFeedback['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success'; ?> alert-dismissible" data-alert>
                <span><?= htmlspecialchars((string) ($matriculationConfigFeedback['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                    <i class="fa fa-times" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($settings)): ?>
        <div class="empty-state">Todavia no existen periodos lectivos para configurar matricula.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Periodo</th>
                        <th>Ordinaria</th>
                        <th>Extraordinaria</th>
                        <th>Observacion</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($settings as $setting): ?>
                        <tr>
                            <td>
                                <span class="cell-title"><?= htmlspecialchars((string) $setting['pledescripcion'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="cell-subtitle"><?= htmlspecialchars((string) $setting['plefechainicio'], ENT_QUOTES, 'UTF-8'); ?> - <?= htmlspecialchars((string) $setting['plefechafin'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td>
                                <?php if (!empty($setting['cmid'])): ?>
                                    <form method="POST" action="<?= htmlspecialchars(baseUrl('configuracion/matricula/ordinaria'), ENT_QUOTES, 'UTF-8'); ?>" class="inline-toggle-form">
                                        <input type="hidden" name="cmid" value="<?= htmlspecialchars((string) $setting['cmid'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="enabled" value="<?= !empty($setting['cmhabilitada']) ? '0' : '1'; ?>">
                                        <button type="submit" class="permission-option-state permission-option-toggle <?= !empty($setting['cmhabilitada']) ? 'is-active' : 'is-inactive'; ?>" title="<?= !empty($setting['cmhabilitada']) ? 'Cerrar matricula ordinaria' : 'Abrir matricula ordinaria'; ?>">
                                            <?= !empty($setting['cmhabilitada']) ? 'Abierta' : 'Cerrada'; ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="permission-option-state is-inactive">Cerrada</span>
                                <?php endif; ?>
                                <div class="cell-subtitle">
                                    <?= !empty($setting['cmfechainicio']) || !empty($setting['cmfechafin'])
                                        ? htmlspecialchars((string) (($setting['cmfechainicio'] ?? '') . ' - ' . ($setting['cmfechafin'] ?? '')), ENT_QUOTES, 'UTF-8')
                                        : 'Sin rango'; ?>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($setting['cmid'])): ?>
                                    <form method="POST" action="<?= htmlspecialchars(baseUrl('configuracion/matricula/extraordinaria'), ENT_QUOTES, 'UTF-8'); ?>" class="inline-toggle-form">
                                        <input type="hidden" name="cmid" value="<?= htmlspecialchars((string) $setting['cmid'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="enabled" value="<?= !empty($setting['cmhabilitadaextraordinaria']) ? '0' : '1'; ?>">
                                        <button type="submit" class="permission-option-state permission-option-toggle <?= !empty($setting['cmhabilitadaextraordinaria']) ? 'is-active' : 'is-inactive'; ?>" title="<?= !empty($setting['cmhabilitadaextraordinaria']) ? 'Cerrar matricula extraordinaria' : 'Abrir matricula extraordinaria'; ?>">
                                            <?= !empty($setting['cmhabilitadaextraordinaria']) ? 'Abierta' : 'Cerrada'; ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="permission-option-state is-inactive">Cerrada</span>
                                <?php endif; ?>
                                <div class="cell-subtitle">
                                    <?= !empty($setting['cmfechainicioextraordinaria']) || !empty($setting['cmfechafinextraordinaria'])
                                        ? htmlspecialchars((string) (($setting['cmfechainicioextraordinaria'] ?? '') . ' - ' . ($setting['cmfechafinextraordinaria'] ?? '')), ENT_QUOTES, 'UTF-8')
                                        : 'Sin rango'; ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars((string) ($setting['cmobservacion'] ?? 'Sin observacion'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <?php if (!empty($setting['cmid'])): ?>
                                    <div class="actions-group">
                                        <a class="icon-button icon-button-edit" href="<?= htmlspecialchars(baseUrl('configuracion/matricula') . '?edit=' . $setting['pleid'], ENT_QUOTES, 'UTF-8'); ?>" title="Editar configuracion" aria-label="Editar configuracion">
                                            <i class="fa fa-pencil" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <span class="cell-subtitle">Sin configurar</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
