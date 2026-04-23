<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$staffAssignments = is_array($staffAssignments ?? null) ? $staffAssignments : [];
$staffTypes = is_array($staffTypes ?? null) ? $staffTypes : [];
$assignedTypes = is_array($assignedTypes ?? null) ? $assignedTypes : [];
?>
<p class="module-note">Asigna uno o varios tipos al personal institucional. Un mismo registro puede pertenecer a Docente, Inspeccion, DECE u otras categorias al mismo tiempo.</p>

<section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3>Asignacion de personal</h3>
            <p>Marca los tipos aplicables para cada persona registrada como personal institucional.</p>
        </div>
    </header>

    <?php if (empty($staffAssignments)): ?>
        <div class="empty-state">No existe personal registrado todavia para asignar tipos.</div>
    <?php elseif (empty($staffTypes)): ?>
        <div class="empty-state">No existen tipos de personal activos. Registralos primero en Configuracion &gt; Catalogos.</div>
    <?php else: ?>
        <?php if (!empty($staffTypeFeedback)): ?>
            <div class="catalog-feedback security-feedback-global">
                <div class="alert <?= ($staffTypeFeedback['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success'; ?> alert-dismissible" data-alert>
                    <span><?= htmlspecialchars((string) ($staffTypeFeedback['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                    <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                        <i class="fa fa-times" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <div class="toolbar toolbar-filter">
            <div class="filter-box">
                <label class="sr-only" for="staff-type-search">Buscar personal</label>
                <input
                    id="staff-type-search"
                    type="search"
                    placeholder="Filtrar por cedula, nombres o apellidos"
                    data-staff-type-search
                    data-staff-type-search-url="<?= htmlspecialchars(baseUrl('personal/asignacion/buscar'), ENT_QUOTES, 'UTF-8'); ?>"
                    autocomplete="off"
                >
            </div>
            <span class="filter-status" data-staff-type-status><?= count($staffAssignments); ?> registro(s)</span>
        </div>

        <div data-staff-type-empty-wrapper hidden>
            <div class="empty-state">No se encontro personal con ese filtro.</div>
        </div>

        <div class="table-wrap" data-staff-type-table-wrapper>
            <table class="data-table role-matrix-table">
                <thead>
                    <tr>
                        <th>Personal</th>
                        <th>Cedula</th>
                        <?php foreach ($staffTypes as $type): ?>
                            <th class="role-matrix-head" title="<?= htmlspecialchars((string) (($type['tpdescripcion'] ?? '') !== '' ? $type['tpdescripcion'] : $type['tpnombre']), ENT_QUOTES, 'UTF-8'); ?>">
                                <?= htmlspecialchars((string) $type['tpnombre'], ENT_QUOTES, 'UTF-8'); ?>
                            </th>
                        <?php endforeach; ?>
                        <th>Guardar</th>
                    </tr>
                </thead>
                <tbody data-staff-type-table-body>
                    <?php $staffMembers = $staffAssignments; ?>
                    <?php require BASE_PATH . '/app/views/personal/_staff_type_rows.php'; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
