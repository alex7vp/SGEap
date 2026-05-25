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
        <form class="role-permission-filter-bar" method="GET" action="<?= $h(baseUrl('seguridad/roles-permisos')); ?>" data-role-permission-filter>
            <div class="grade-profile-mode-selector role-permission-role-selector" role="radiogroup" aria-label="Seleccion de rol">
                <?php foreach ($roles as $role): ?>
                    <?php
                    $roleId = (int) ($role['rolid'] ?? 0);
                    $isSelectedRole = $roleId === $selectedRoleId;
                    ?>
                    <label class="grade-profile-mode-option">
                        <input
                            type="radio"
                            name="rolid"
                            value="<?= $h($roleId); ?>"
                            <?= $isSelectedRole ? 'checked' : ''; ?>
                            data-role-permission-radio
                            data-role-permission-url="<?= $h(baseUrl('seguridad/roles-permisos/buscar')); ?>"
                        >
                        <span><?= $h($role['rolnombre'] ?? 'Rol'); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
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
