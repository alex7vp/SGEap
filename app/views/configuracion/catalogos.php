<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';
?>

<p class="module-note">Este modulo centraliza los catalogos operativos. Por ahora queda en modo visualizacion para validar estructura y datos cargados.</p>

<section class="catalog-grid">
    <?php foreach ($catalogs as $catalog): ?>
        <?php $catalogAnchor = 'catalog-' . $catalog['table']; ?>
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
                <table class="data-table catalog-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                        <tbody>
                            <?php foreach ($catalog['rows'] as $row): ?>
                            <tr data-catalog-row>
                                <td><?= htmlspecialchars((string) $row['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <form class="catalog-inline-form" method="POST" action="<?= htmlspecialchars(baseUrl('configuracion/catalogos/actualizar'), ENT_QUOTES, 'UTF-8'); ?>" data-catalog-edit-form>
                                        <input type="hidden" name="catalog_table" value="<?= htmlspecialchars((string) $catalog['table'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="catalog_id" value="<?= htmlspecialchars((string) $row['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="redirect_anchor" value="<?= htmlspecialchars($catalogAnchor, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input class="catalog-readonly-input" type="text" name="catalog_name" value="<?= htmlspecialchars((string) $row['name'], ENT_QUOTES, 'UTF-8'); ?>" readonly required data-catalog-input>
                                        <div class="actions-group">
                                            <button class="icon-button icon-button-save" type="submit" title="Guardar" aria-label="Guardar" hidden data-catalog-save>
                                                <i class="fa fa-check" aria-hidden="true"></i>
                                            </button>
                                            <button class="icon-button icon-button-cancel" type="button" title="Cancelar" aria-label="Cancelar" hidden data-catalog-cancel>
                                                <i class="fa fa-times" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </form>
                                </td>
                                <td>
                                    <div class="actions-group" data-catalog-actions>
                                        <button class="icon-button icon-button-edit" type="button" title="Editar" aria-label="Editar" data-catalog-edit>
                                            <i class="fa fa-pencil" aria-hidden="true"></i>
                                        </button>

                                        <form method="POST" action="<?= htmlspecialchars(baseUrl('configuracion/catalogos/eliminar'), ENT_QUOTES, 'UTF-8'); ?>" onsubmit="return confirm('Confirma que desea eliminar este registro del catalogo?');">
                                            <input type="hidden" name="catalog_table" value="<?= htmlspecialchars((string) $catalog['table'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="catalog_id" value="<?= htmlspecialchars((string) $row['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="redirect_anchor" value="<?= htmlspecialchars($catalogAnchor, ENT_QUOTES, 'UTF-8'); ?>">
                                            <button class="icon-button icon-button-delete" type="submit" title="Eliminar" aria-label="Eliminar">
                                                <i class="fa fa-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="catalog-create-row">
                            <td>Nuevo</td>
                            <td colspan="2">
                                <form class="catalog-inline-form catalog-inline-create" method="POST" action="<?= htmlspecialchars(baseUrl('configuracion/catalogos'), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="catalog_table" value="<?= htmlspecialchars((string) $catalog['table'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="redirect_anchor" value="<?= htmlspecialchars($catalogAnchor, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="text" name="catalog_name" placeholder="Agregar nuevo item al catalogo" required>
                                    <button class="btn-primary btn-auto btn-icon-only btn-icon-small" type="submit" title="Guardar" aria-label="Guardar">
                                        <i class="fa fa-save" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </article>
    <?php endforeach; ?>
</section>
<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
