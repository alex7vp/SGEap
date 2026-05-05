<?php $staffManagedRoleNames = is_array($staffManagedRoleNames ?? null) ? $staffManagedRoleNames : []; ?>
<?php foreach ($users as $account): ?>
    <?php
    $userId = (int) $account['usuid'];
    $userAssignedRoles = $assignedRoles[$userId] ?? [];
    ?>
    <tr id="user-<?= htmlspecialchars((string) $userId, ENT_QUOTES, 'UTF-8'); ?>">
        <td class="role-user-sticky role-user-sticky-username">
            <span class="cell-title"><?= htmlspecialchars((string) $account['usunombre'], ENT_QUOTES, 'UTF-8'); ?></span>
        </td>
        <td class="role-user-sticky role-user-sticky-person">
            <span class="person-name-inline">
                <strong><?= htmlspecialchars((string) $account['perapellidos'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <span><?= htmlspecialchars((string) $account['pernombres'], ENT_QUOTES, 'UTF-8'); ?></span>
            </span>
        </td>
        <td class="role-user-sticky role-user-sticky-id"><?= htmlspecialchars((string) $account['percedula'], ENT_QUOTES, 'UTF-8'); ?></td>
        <?php foreach ($roles as $role): ?>
            <?php
            $roleId = (int) $role['rolid'];
            $isStaffManagedRole = in_array((string) $role['rolnombre'], $staffManagedRoleNames, true);
            ?>
            <td class="role-matrix-cell <?= $isStaffManagedRole ? 'is-readonly-role' : ''; ?>">
                <label class="role-toggle" title="<?= $isStaffManagedRole ? 'Rol sincronizado desde Asignacion de personal' : 'Rol editable desde seguridad'; ?>">
                    <input
                        type="checkbox"
                        name="role_ids[]"
                        value="<?= htmlspecialchars((string) $roleId, ENT_QUOTES, 'UTF-8'); ?>"
                        form="user-role-form-<?= htmlspecialchars((string) $userId, ENT_QUOTES, 'UTF-8'); ?>"
                        <?= in_array($roleId, $userAssignedRoles, true) ? 'checked' : ''; ?>
                        <?= $isStaffManagedRole ? 'disabled' : ''; ?>
                    >
                </label>
            </td>
        <?php endforeach; ?>
        <td>
            <form id="user-role-form-<?= htmlspecialchars((string) $userId, ENT_QUOTES, 'UTF-8'); ?>" method="POST" action="<?= htmlspecialchars(baseUrl('seguridad/usuarios-roles'), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars((string) $userId, ENT_QUOTES, 'UTF-8'); ?>">
            </form>
            <button class="btn-primary btn-auto btn-icon-only btn-icon-small" type="submit" form="user-role-form-<?= htmlspecialchars((string) $userId, ENT_QUOTES, 'UTF-8'); ?>" title="Guardar roles" aria-label="Guardar roles">
                <i class="fa fa-save" aria-hidden="true"></i>
            </button>
        </td>
    </tr>
<?php endforeach; ?>
