<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';
?>
<div class="toolbar">
    <p>Base de datos de personas registradas en el sistema.</p>
    <a class="btn-primary btn-auto btn-icon-only btn-icon-small" href="<?= htmlspecialchars(baseUrl('personas/crear'), ENT_QUOTES, 'UTF-8'); ?>" title="Nueva persona" aria-label="Nueva persona">
        <i class="fa fa-user-plus" aria-hidden="true"></i>
    </a>
</div>

<div class="toolbar toolbar-filter">
    <div class="filter-box">
        <label class="sr-only" for="person-search">Buscar personas</label>
        <input
            id="person-search"
            type="search"
            placeholder="Filtrar por cedula, nombres, apellidos, correo o telefono"
            data-person-search
            data-person-search-url="<?= htmlspecialchars(baseUrl('personas/buscar'), ENT_QUOTES, 'UTF-8'); ?>"
            autocomplete="off"
        >
    </div>
    <span class="filter-status" data-person-search-status><?= count($persons); ?> registro(s)</span>
</div>

<div data-person-list-wrapper <?= empty($persons) ? '' : 'hidden'; ?>>
    <div class="empty-state">Todavia no hay personas registradas.</div>
</div>

<div class="table-wrap" data-person-table-wrapper <?= empty($persons) ? 'hidden' : ''; ?>>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Cedula</th>
                    <th>Apellidos y nombres</th>
                    <th>Tel. Celular</th>
                    <th>Tel. Fijo</th>
                    <th>Correo</th>
                    <th>Sexo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody data-person-table-body>
                <?php require BASE_PATH . '/app/views/personas/_rows.php'; ?>
            </tbody>
        </table>
    </div>
<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
