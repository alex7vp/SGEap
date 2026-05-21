<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$roles = is_array($roles ?? null) ? $roles : [];
$permissions = is_array($permissions ?? null) ? $permissions : [];
$assignedPermissions = is_array($assignedPermissions ?? null) ? $assignedPermissions : [];
$selectedRoleId = (int) ($selectedRoleId ?? 0);
$selectedRole = null;

foreach ($roles as $roleOption) {
    if ((int) ($roleOption['rolid'] ?? 0) === $selectedRoleId) {
        $selectedRole = $roleOption;
        break;
    }
}
?>
<p class="module-note">Este modulo concentra la asignacion de permisos funcionales a cada rol del sistema.</p>

<section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3>Permisos por rol</h3>
            <p>Selecciona un rol para consultar y modificar solo sus permisos funcionales.</p>
        </div>
    </header>

    <?php if (empty($roles)): ?>
        <div class="empty-state">No existen roles creados todavia. Primero registra roles en el catalogo de seguridad.</div>
    <?php elseif (empty($permissions)): ?>
        <div class="empty-state">No existen permisos creados todavia. Primero registra permisos en el catalogo de seguridad.</div>
    <?php else: ?>
        <form class="filters-bar role-permission-filter-bar" method="GET" action="<?= $h(baseUrl('seguridad/roles-permisos')); ?>" data-role-permission-filter>
            <div class="input-group role-permission-filter-group">
                <span class="input-addon">Rol</span>
                <select
                    name="rolid"
                    data-role-permission-select
                    data-role-permission-url="<?= $h(baseUrl('seguridad/roles-permisos/buscar')); ?>"
                >
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $h($role['rolid']); ?>" <?= (int) $role['rolid'] === $selectedRoleId ? 'selected' : ''; ?>>
                            <?= $h($role['rolnombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn-secondary btn-auto role-permission-filter-button" type="submit">
                    <i class="fa fa-filter" aria-hidden="true"></i>
                    Filtrar
                </button>
            </div>
            <span class="cell-subtitle" data-role-permission-status>
                <?= $selectedRole !== null ? 'Mostrando permisos de ' . $h($selectedRole['rolnombre']) : 'Seleccione un rol'; ?>
            </span>
        </form>

        <section class="permission-grid permission-grid-single" data-role-permission-panel>
            <?php if ($selectedRole === null): ?>
                <div class="empty-state">Seleccione un rol valido para consultar permisos.</div>
            <?php else: ?>
                <?php
                $role = $selectedRole;
                require BASE_PATH . '/app/views/seguridad/_role_permission_panel.php';
                ?>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</section>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
