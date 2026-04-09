<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';
?>
<p class="module-note">Este modulo centraliza los catalogos de seguridad del sistema. Roles y permisos se administran desde aqui con edicion directa en tabla.</p>

<section class="catalog-grid security-grid">
    <?php foreach ($catalogs as $catalog): ?>
        <?php $catalogAnchor = 'security-catalog-' . $catalog['table']; ?>
        <article class="catalog-card" id="<?= htmlspecialchars($catalogAnchor, ENT_QUOTES, 'UTF-8'); ?>">
            <header class="catalog-card-header">
                <div>
                    <h3><?= htmlspecialchars((string) $catalog['label'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p><?= htmlspecialchars((string) $catalog['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <span class="catalog-count"><?= count($catalog['rows']); ?> item(s)</span>
            </header>

            <?php if (!empty($catalogFeedback) && ($catalogFeedback['table'] ?? '') === $catalog['table']): ?>
                <div class="catalog-feedback">
                    <div class="alert <?= ($catalogFeedback['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success'; ?> alert-dismissible" data-alert>
                        <span><?= htmlspecialchars((string) ($catalogFeedback['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                        <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                            <i class="fa fa-times" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($catalog['rows'])): ?>
                <div class="empty-state">No hay registros cargados en este catalogo. Puedes agregar el primero desde la fila inferior.</div>
            <?php endif; ?>

            <div class="table-wrap">
                <table class="data-table security-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <?php foreach ($catalog['fields'] as $field): ?>
                                <th><?= htmlspecialchars((string) $field['label'], ENT_QUOTES, 'UTF-8'); ?></th>
                            <?php endforeach; ?>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($catalog['rows'] as $row): ?>
                            <?php $formId = 'security-form-' . $catalog['table'] . '-' . $row['id']; ?>
                            <tr data-security-row>
                                <td><?= htmlspecialchars((string) $row['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <?php foreach ($catalog['fields'] as $field): ?>
                                    <td>
                                        <?php if ($field['type'] === 'bool'): ?>
                                            <select
                                                class="security-inline-input security-inline-select"
                                                name="<?= htmlspecialchars((string) $field['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                                form="<?= htmlspecialchars($formId, ENT_QUOTES, 'UTF-8'); ?>"
                                                disabled
                                                data-security-input
                                            >
                                                <option value="1" <?= !empty($row[$field['name']]) ? 'selected' : ''; ?>>Activo</option>
                                                <option value="0" <?= empty($row[$field['name']]) ? 'selected' : ''; ?>>Inactivo</option>
                                            </select>
                                        <?php else: ?>
                                            <input
                                                class="security-inline-input"
                                                type="text"
                                                name="<?= htmlspecialchars((string) $field['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                                value="<?= htmlspecialchars((string) ($row[$field['name']] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                form="<?= htmlspecialchars($formId, ENT_QUOTES, 'UTF-8'); ?>"
                                                readonly
                                                <?= ($field['required'] ?? false) ? 'required' : ''; ?>
                                                data-security-input
                                            >
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td>
                                    <form id="<?= htmlspecialchars($formId, ENT_QUOTES, 'UTF-8'); ?>" method="POST" action="<?= htmlspecialchars(baseUrl('seguridad/catalogos/actualizar'), ENT_QUOTES, 'UTF-8'); ?>" class="security-hidden-form" data-security-edit-form>
                                        <input type="hidden" name="catalog_table" value="<?= htmlspecialchars((string) $catalog['table'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="catalog_id" value="<?= htmlspecialchars((string) $row['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="redirect_anchor" value="<?= htmlspecialchars($catalogAnchor, ENT_QUOTES, 'UTF-8'); ?>">
                                    </form>

                                    <div class="actions-group" data-security-actions>
                                        <button class="icon-button icon-button-edit" type="button" title="Editar" aria-label="Editar" data-security-edit>
                                            <i class="fa fa-pencil" aria-hidden="true"></i>
                                        </button>

                                        <form method="POST" action="<?= htmlspecialchars(baseUrl('seguridad/catalogos/eliminar'), ENT_QUOTES, 'UTF-8'); ?>" onsubmit="return confirm('Confirma que desea eliminar este registro de seguridad?');">
                                            <input type="hidden" name="catalog_table" value="<?= htmlspecialchars((string) $catalog['table'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="catalog_id" value="<?= htmlspecialchars((string) $row['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="redirect_anchor" value="<?= htmlspecialchars($catalogAnchor, ENT_QUOTES, 'UTF-8'); ?>">
                                            <button class="icon-button icon-button-delete" type="submit" title="Eliminar" aria-label="Eliminar">
                                                <i class="fa fa-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div>

                                    <div class="actions-group" hidden data-security-edit-actions>
                                        <button class="icon-button icon-button-save" type="submit" form="<?= htmlspecialchars($formId, ENT_QUOTES, 'UTF-8'); ?>" title="Guardar" aria-label="Guardar">
                                            <i class="fa fa-check" aria-hidden="true"></i>
                                        </button>
                                        <button class="icon-button icon-button-cancel" type="button" title="Cancelar" aria-label="Cancelar" data-security-cancel>
                                            <i class="fa fa-times" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="catalog-create-row">
                            <td>Nuevo</td>
                            <?php foreach ($catalog['fields'] as $field): ?>
                                <td>
                                    <?php if ($field['type'] === 'bool'): ?>
                                        <select name="<?= htmlspecialchars((string) $field['name'], ENT_QUOTES, 'UTF-8'); ?>" form="create-<?= htmlspecialchars((string) $catalog['table'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <option value="1">Activo</option>
                                            <option value="0">Inactivo</option>
                                        </select>
                                    <?php else: ?>
                                        <input
                                            type="text"
                                            name="<?= htmlspecialchars((string) $field['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                            placeholder="<?= htmlspecialchars((string) $field['label'], ENT_QUOTES, 'UTF-8'); ?>"
                                            form="create-<?= htmlspecialchars((string) $catalog['table'], ENT_QUOTES, 'UTF-8'); ?>"
                                            <?= ($field['required'] ?? false) ? 'required' : ''; ?>
                                        >
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                            <td>
                                <form id="create-<?= htmlspecialchars((string) $catalog['table'], ENT_QUOTES, 'UTF-8'); ?>" method="POST" action="<?= htmlspecialchars(baseUrl('seguridad/catalogos'), ENT_QUOTES, 'UTF-8'); ?>" class="security-hidden-form">
                                    <input type="hidden" name="catalog_table" value="<?= htmlspecialchars((string) $catalog['table'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="redirect_anchor" value="<?= htmlspecialchars($catalogAnchor, ENT_QUOTES, 'UTF-8'); ?>">
                                </form>
                                <button class="btn-primary btn-auto btn-icon-only btn-icon-small" type="submit" form="create-<?= htmlspecialchars((string) $catalog['table'], ENT_QUOTES, 'UTF-8'); ?>" title="Guardar" aria-label="Guardar">
                                    <i class="fa fa-save" aria-hidden="true"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </article>
    <?php endforeach; ?>
</section>
<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
