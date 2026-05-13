<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<p class="module-note">Catalogo de areas academicas usadas para agrupar asignaturas institucionales.</p>

<section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3>Nueva area academica</h3>
            <p>Registra el nombre del area curricular.</p>
        </div>
    </header>

    <form class="data-form" method="POST" action="<?= $h(baseUrl('configuracion/academica/areas')); ?>">
        <div class="form-grid">
            <div class="form-group-full">
                <div class="input-group">
                    <span class="input-addon">Nombre</span>
                    <input type="text" name="areanombre" maxlength="100" required>
                </div>
            </div>
        </div>
        <div class="actions-row">
            <button class="btn-primary btn-inline" type="submit">Guardar area</button>
        </div>
    </form>
</section>

<section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3>Areas registradas</h3>
            <p>Actualiza nombres y controla si el area esta disponible para nuevas asignaturas.</p>
        </div>
    </header>

    <?php if (empty($areas)): ?>
        <div class="empty-state">Todavia no hay areas academicas registradas.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table security-table">
                <thead>
                    <tr>
                        <th>Area</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($areas as $area): ?>
                        <tr data-security-row>
                            <td>
                                <form id="area-form-<?= $h($area['areaid']); ?>" method="POST" action="<?= $h(baseUrl('configuracion/academica/areas/actualizar')); ?>" data-security-edit-form>
                                    <input type="hidden" name="areaid" value="<?= $h($area['areaid']); ?>">
                                    <input class="security-inline-input" type="text" name="areanombre" maxlength="100" value="<?= $h($area['areanombre']); ?>" readonly required data-security-input>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="<?= $h(baseUrl('configuracion/academica/areas/estado')); ?>" class="status-switch-form">
                                    <input type="hidden" name="areaid" value="<?= $h($area['areaid']); ?>">
                                    <input type="hidden" name="areaestado" value="<?= !empty($area['areaestado']) ? '0' : '1'; ?>">
                                    <button class="status-switch <?= !empty($area['areaestado']) ? 'is-active' : ''; ?>" type="submit" title="<?= !empty($area['areaestado']) ? 'Inactivar area' : 'Activar area'; ?>" aria-label="<?= !empty($area['areaestado']) ? 'Inactivar area' : 'Activar area'; ?>">
                                        <span class="status-switch-track"><span class="status-switch-thumb"></span></span>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <div class="actions-group" data-security-actions>
                                    <button class="icon-button icon-button-edit" type="button" title="Editar" aria-label="Editar" data-security-edit>
                                        <i class="fa fa-pencil" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div class="actions-group" hidden data-security-edit-actions>
                                    <button class="icon-button icon-button-save" type="submit" form="area-form-<?= $h($area['areaid']); ?>" title="Guardar" aria-label="Guardar">
                                        <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                    </button>
                                    <button class="icon-button icon-button-cancel" type="button" title="Cancelar" aria-label="Cancelar" data-security-cancel>
                                        <i class="fa fa-times" aria-hidden="true"></i>
                                    </button>
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
