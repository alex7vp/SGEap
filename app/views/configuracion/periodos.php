<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$editingPeriodId = (int) ($old['pleid'] ?? 0);
?>
<p class="module-note">Administra los periodos lectivos del sistema y define cual queda disponible como referencia para la sesion actual.</p>

<section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3><?= $editingPeriodId > 0 ? 'Editar periodo lectivo' : 'Nuevo periodo lectivo'; ?></h3>
            <p>El periodo activo podra quedar seleccionado automaticamente al ingresar al sistema.</p>
        </div>
    </header>

    <form class="data-form" method="POST" action="<?= htmlspecialchars(baseUrl($editingPeriodId > 0 ? 'configuracion/periodos/actualizar' : 'configuracion/periodos'), ENT_QUOTES, 'UTF-8'); ?>">
        <?php if ($editingPeriodId > 0): ?>
            <input type="hidden" name="pleid" value="<?= htmlspecialchars((string) $editingPeriodId, ENT_QUOTES, 'UTF-8'); ?>">
        <?php endif; ?>
        <div class="form-grid">
            <div>
                <div class="input-group">
                    <span class="input-addon">Descripcion</span>
                    <input type="text" name="pledescripcion" value="<?= htmlspecialchars((string) ($old['pledescripcion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
            </div>
            <div>
                <div class="input-group">
                    <span class="input-addon">Activo</span>
                    <?php if ($editingPeriodId > 0): ?>
                        <select name="pleactivo">
                            <option value="1" <?= ($old['pleactivo'] ?? '0') === '1' ? 'selected' : ''; ?>>Si</option>
                            <option value="0" <?= ($old['pleactivo'] ?? '0') === '0' ? 'selected' : ''; ?>>No</option>
                        </select>
                    <?php else: ?>
                        <input type="hidden" name="pleactivo" value="0">
                        <input type="text" value="No" readonly>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <div class="input-group">
                    <span class="input-addon">Inicio</span>
                    <input type="date" name="plefechainicio" value="<?= htmlspecialchars((string) ($old['plefechainicio'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
            </div>
            <div>
                <div class="input-group">
                    <span class="input-addon">Fin</span>
                    <input type="date" name="plefechafin" value="<?= htmlspecialchars((string) ($old['plefechafin'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
            </div>
        </div>
        <div class="actions-row">
            <button class="btn-secondary btn-auto btn-icon-only btn-icon-small" type="reset" title="Limpiar formulario" aria-label="Limpiar formulario" hidden>
                <i class="fa fa-eraser" aria-hidden="true"></i>
            </button>
            <button
                class="btn-primary btn-auto btn-icon-only btn-icon-small"
                type="submit"
                title="<?= htmlspecialchars($editingPeriodId > 0 ? 'Actualizar periodo' : 'Guardar periodo', ENT_QUOTES, 'UTF-8'); ?>"
                aria-label="<?= htmlspecialchars($editingPeriodId > 0 ? 'Actualizar periodo' : 'Guardar periodo', ENT_QUOTES, 'UTF-8'); ?>"
            >
                <i class="fa fa-save" aria-hidden="true"></i>
            </button>
        </div>
    </form>
</section>

<section class="security-assignment-block" id="periodos-registrados">
    <header class="security-assignment-header">
        <div>
            <h3>Periodos registrados</h3>
            <p>El sistema usa el periodo de la sesion actual desde el selector del navbar o el periodo activo configurado.</p>
        </div>
    </header>

    <?php if (!empty($periodListFeedback)): ?>
        <div class="catalog-feedback security-feedback-global">
            <div class="alert <?= ($periodListFeedback['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success'; ?> alert-dismissible" data-alert>
                <span><?= htmlspecialchars((string) ($periodListFeedback['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                    <i class="fa fa-times" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($periods)): ?>
        <div class="empty-state">Todavia no hay periodos lectivos registrados.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                        <tr>
                            <th>Descripcion</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($periods as $period): ?>
                        <tr>
                            <td>
                                <span class="cell-title"><?= htmlspecialchars((string) $period['pledescripcion'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td><?= htmlspecialchars((string) $period['plefechainicio'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars((string) $period['plefechafin'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <form method="POST" action="<?= htmlspecialchars(baseUrl('configuracion/periodo-actual'), ENT_QUOTES, 'UTF-8'); ?>" class="status-switch-form">
                                    <input type="hidden" name="pleid" value="<?= htmlspecialchars((string) $period['pleid'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="redirect_to" value="/configuracion/periodos">
                                    <button
                                        class="status-switch <?= !empty($period['pleactivo']) ? 'is-active' : ''; ?>"
                                        type="submit"
                                        title="<?= !empty($period['pleactivo']) ? 'Periodo activo' : 'Activar periodo'; ?>"
                                        aria-label="<?= !empty($period['pleactivo']) ? 'Periodo activo' : 'Activar periodo'; ?>"
                                    >
                                        <span class="status-switch-track">
                                            <span class="status-switch-thumb"></span>
                                        </span>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <div class="actions-group">
                                    <a class="icon-button icon-button-edit" href="<?= htmlspecialchars(baseUrl('configuracion/periodos') . '?edit=' . $period['pleid'], ENT_QUOTES, 'UTF-8'); ?>" title="Editar periodo" aria-label="Editar periodo">
                                        <i class="fa fa-pencil" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
