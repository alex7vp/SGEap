<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$selectedCatalogTable = (string) ($catalogFeedback['table'] ?? ($catalogs[0]['table'] ?? ''));
?>

<?php if (!empty($catalogs)): ?>
    <section class="security-assignment-block catalog-selector-block" data-base-catalog-selector>
        <div class="grade-profile-mode-selector catalog-selector" role="radiogroup" aria-label="Seleccion de catalogo base">
            <?php foreach ($catalogs as $catalog): ?>
                <?php $catalogTable = (string) $catalog['table']; ?>
                <label class="grade-profile-mode-option">
                    <input
                        type="radio"
                        name="base_catalog_table"
                        value="<?= htmlspecialchars($catalogTable, ENT_QUOTES, 'UTF-8'); ?>"
                        <?= $selectedCatalogTable === $catalogTable ? 'checked' : ''; ?>
                        data-base-catalog-radio
                    >
                    <span><?= htmlspecialchars((string) $catalog['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="catalog-grid catalog-single-view">
    <?php foreach ($catalogs as $catalog): ?>
        <?php $catalogAnchor = 'catalog-' . $catalog['table']; ?>
        <?php
        $isFeedbackCatalog = !empty($catalogFeedback) && ($catalogFeedback['table'] ?? '') === $catalog['table'];
        $catalogTable = (string) $catalog['table'];
        $isSelectedCatalog = $selectedCatalogTable === $catalogTable;
        ?>
        <article
            class="catalog-card"
            id="<?= htmlspecialchars($catalogAnchor, ENT_QUOTES, 'UTF-8'); ?>"
            data-base-catalog-panel="<?= htmlspecialchars($catalogTable, ENT_QUOTES, 'UTF-8'); ?>"
            <?= $isSelectedCatalog ? '' : 'hidden'; ?>
        >
            <header class="catalog-card-header">
                <div>
                    <h3><?= htmlspecialchars((string) $catalog['label'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p><?= htmlspecialchars((string) $catalog['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <span class="catalog-count"><?= count($catalog['rows']); ?> item(s)</span>
            </header>

            <form class="catalog-inline-form catalog-inline-create catalog-create-panel" method="POST" action="<?= htmlspecialchars(baseUrl('configuracion/catalogos'), ENT_QUOTES, 'UTF-8'); ?>">
                <?= csrfField(); ?>
                <input type="hidden" name="catalog_table" value="<?= htmlspecialchars((string) $catalog['table'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="redirect_anchor" value="<?= htmlspecialchars($catalogAnchor, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="input-group">
                    <span class="input-addon">Nombre</span>
                    <input type="text" name="catalog_name" placeholder="Agregar nuevo item al catalogo" required>
                </div>
                <button class="btn-primary btn-auto btn-icon-only btn-icon-small" type="submit" title="Guardar" aria-label="Guardar">
                    <i class="fa fa-save" aria-hidden="true"></i>
                </button>
            </form>

            <?php if (empty($catalog['rows'])): ?>
                <div class="empty-state">No hay registros cargados en este catalogo.</div>
            <?php else: ?>
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
                                        <?= csrfField(); ?>
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

                                        <form method="POST" action="<?= htmlspecialchars(baseUrl('configuracion/catalogos/eliminar'), ENT_QUOTES, 'UTF-8'); ?>" data-catalog-delete-form>
                                            <?= csrfField(); ?>
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
                    </tbody>
                </table>
                </div>
            <?php endif; ?>

            <?php if ($isFeedbackCatalog): ?>
                <footer class="catalog-feedback catalog-feedback-footer">
                    <div class="alert <?= ($catalogFeedback['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success'; ?> alert-dismissible" data-alert>
                        <span><?= htmlspecialchars((string) ($catalogFeedback['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                        <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                            <i class="fa fa-times" aria-hidden="true"></i>
                        </button>
                    </div>
                </footer>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</section>

<p class="module-note catalog-module-footer">Este modulo centraliza los catalogos operativos. Por ahora queda en modo visualizacion para validar estructura y datos cargados.</p>

<dialog class="calendar-dialog catalog-delete-dialog" data-catalog-delete-dialog>
    <header class="security-assignment-header">
        <div>
            <h3>Eliminar registro</h3>
            <p>Confirma que desea eliminar este registro del catalogo.</p>
        </div>
    </header>
    <p class="module-note">Si el registro esta siendo usado por otros modulos, el sistema bloqueara la eliminacion.</p>
    <div class="actions-row">
        <button class="btn-secondary btn-auto" type="button" data-catalog-delete-cancel>Cancelar</button>
        <button class="btn-primary btn-inline" type="button" data-catalog-delete-confirm>Eliminar</button>
    </div>
</dialog>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
