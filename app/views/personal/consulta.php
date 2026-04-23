<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$staffMembers = is_array($staffMembers ?? null) ? $staffMembers : [];
$staffTypes = is_array($staffTypes ?? null) ? $staffTypes : [];
$selectedType = trim((string) ($selectedType ?? ''));
?>
<div class="toolbar">
    <p>Consulta el personal institucional y filtra por tipo segun su asignacion activa.</p>
</div>

<div class="toolbar toolbar-filter">
    <div
        class="dashboard-link-list personal-type-radio-group"
        data-staff-listing-type-filter
        data-staff-listing-filter-url="<?= htmlspecialchars(baseUrl('personal/consulta/buscar'), ENT_QUOTES, 'UTF-8'); ?>"
    >
        <label class="role-toggle">
            <input
                type="radio"
                name="staff_listing_type"
                value=""
                <?= $selectedType === '' ? 'checked' : ''; ?>
            >
            <span>Todos</span>
        </label>
        <?php foreach ($staffTypes as $type): ?>
            <?php $typeName = (string) ($type['tpnombre'] ?? ''); ?>
            <label class="role-toggle">
                <input
                    type="radio"
                    name="staff_listing_type"
                    value="<?= htmlspecialchars($typeName, ENT_QUOTES, 'UTF-8'); ?>"
                    <?= $selectedType === $typeName ? 'checked' : ''; ?>
                >
                <span><?= htmlspecialchars($typeName, ENT_QUOTES, 'UTF-8'); ?></span>
            </label>
        <?php endforeach; ?>
    </div>
    <span class="filter-status" data-staff-listing-status><?= count($staffMembers); ?> registro(s)</span>
</div>

<p class="module-note" data-staff-listing-note>
    <?= $selectedType !== ''
        ? 'Mostrando personal del tipo: ' . htmlspecialchars($selectedType, ENT_QUOTES, 'UTF-8') . '.'
        : 'Mostrando todo el personal institucional registrado.'; ?>
</p>

<div data-staff-listing-empty-wrapper <?= empty($staffMembers) ? '' : 'hidden'; ?>>
    <div class="empty-state">No existen registros de personal para el filtro seleccionado.</div>
</div>

<div class="table-wrap" data-staff-listing-table-wrapper <?= empty($staffMembers) ? 'hidden' : ''; ?>>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Cedula</th>
                    <th>Persona</th>
                    <th>Tipos de personal</th>
                    <th>Contratacion</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody data-staff-listing-table-body>
                <?php require BASE_PATH . '/app/views/personal/_listing_rows.php'; ?>
            </tbody>
        </table>
    </div>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
