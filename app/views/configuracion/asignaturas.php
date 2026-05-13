<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<p class="module-note">Administra las asignaturas base y su area academica.</p>

<section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3>Nueva asignatura</h3>
            <p>La asignatura debe pertenecer a un area academica activa.</p>
        </div>
    </header>

    <?php if (empty($activeAreas)): ?>
        <div class="empty-state">No existen areas activas. Registra o activa un area academica primero.</div>
    <?php else: ?>
        <form class="data-form" method="POST" action="<?= $h(baseUrl('configuracion/academica/asignaturas')); ?>">
            <div class="form-grid">
                <div>
                    <div class="input-group">
                        <span class="input-addon">Area</span>
                        <select name="areaid" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($activeAreas as $area): ?>
                                <option value="<?= $h($area['areaid']); ?>"><?= $h($area['areanombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Asignatura</span>
                        <input type="text" name="asgnombre" maxlength="120" required>
                    </div>
                </div>
            </div>
            <div class="actions-row">
                <button class="btn-primary btn-inline" type="submit">Guardar asignatura</button>
            </div>
        </form>
    <?php endif; ?>
</section>

<section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3>Asignaturas registradas</h3>
            <p>Edita el nombre, cambia el area o activa e inactiva la asignatura.</p>
        </div>
    </header>

    <?php if (empty($subjects)): ?>
        <div class="empty-state">Todavia no hay asignaturas registradas.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table security-table">
                <thead>
                    <tr>
                        <th>Area</th>
                        <th>Asignatura</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects as $subject): ?>
                        <tr data-security-row>
                            <td>
                                <form id="subject-form-<?= $h($subject['asgid']); ?>" method="POST" action="<?= $h(baseUrl('configuracion/academica/asignaturas/actualizar')); ?>" data-security-edit-form>
                                    <input type="hidden" name="asgid" value="<?= $h($subject['asgid']); ?>">
                                    <select class="security-inline-select" name="areaid" required disabled data-security-input>
                                        <?php foreach ($areas as $area): ?>
                                            <option value="<?= $h($area['areaid']); ?>" <?= (int) $area['areaid'] === (int) $subject['areaid'] ? 'selected' : ''; ?>>
                                                <?= $h($area['areanombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <?php if (empty($areas)): ?>
                                            <option value="<?= $h($subject['areaid']); ?>" selected><?= $h($subject['areanombre']); ?></option>
                                        <?php endif; ?>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <input class="security-inline-input" form="subject-form-<?= $h($subject['asgid']); ?>" type="text" name="asgnombre" maxlength="120" value="<?= $h($subject['asgnombre']); ?>" readonly required data-security-input>
                            </td>
                            <td>
                                <form method="POST" action="<?= $h(baseUrl('configuracion/academica/asignaturas/estado')); ?>" class="status-switch-form">
                                    <input type="hidden" name="asgid" value="<?= $h($subject['asgid']); ?>">
                                    <input type="hidden" name="asgestado" value="<?= !empty($subject['asgestado']) ? '0' : '1'; ?>">
                                    <button class="status-switch <?= !empty($subject['asgestado']) ? 'is-active' : ''; ?>" type="submit" title="<?= !empty($subject['asgestado']) ? 'Inactivar asignatura' : 'Activar asignatura'; ?>" aria-label="<?= !empty($subject['asgestado']) ? 'Inactivar asignatura' : 'Activar asignatura'; ?>">
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
                                    <button class="icon-button icon-button-save" type="submit" form="subject-form-<?= $h($subject['asgid']); ?>" title="Guardar" aria-label="Guardar">
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
