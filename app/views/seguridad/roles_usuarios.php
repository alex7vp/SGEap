<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$staffManagedRoleNames = is_array($staffManagedRoleNames ?? null) ? $staffManagedRoleNames : [];
$selectedRole = trim((string) ($selectedRole ?? ''));
$roleNoteText = 'Esta pagina concentra roles especiales de seguridad. Los roles institucionales se sincronizan desde Asignacion de personal. ';
$roleNoteText .= $selectedRole !== ''
    ? 'Mostrando usuarios con rol: ' . $selectedRole . '.'
    : 'Mostrando usuarios activos de todos los roles.';
?>
<p class="module-note" data-security-user-role-note><?= htmlspecialchars($roleNoteText, ENT_QUOTES, 'UTF-8'); ?></p>

<section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3>Roles por usuario</h3>
            <p>La cuenta hereda acceso por medio de los roles seleccionados para cada usuario.</p>
        </div>
    </header>

    <?php if (empty($users)): ?>
        <div class="empty-state">No existen usuarios creados todavia. Cuando registres usuarios podras asignarles roles desde aqui.</div>
    <?php elseif (empty($roles)): ?>
        <div class="empty-state">No hay roles disponibles para asignar a los usuarios.</div>
    <?php else: ?>
        <?php if (!empty($userRoleFeedback)): ?>
            <div class="catalog-feedback security-feedback-global">
                <div class="alert <?= ($userRoleFeedback['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success'; ?> alert-dismissible" data-alert>
                    <span><?= htmlspecialchars((string) ($userRoleFeedback['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                    <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                        <i class="fa fa-times" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <div class="alert alert-success security-feedback-global">
            <span>Los roles Rector, Vicerrector, Secretaria, Coordinador, Docente, DECE e Inspector son de solo lectura aqui y se administran desde Gestion academica &gt; Personal &gt; Asignacion del personal.</span>
        </div>

        <div class="toolbar toolbar-filter">
            <div
                class="dashboard-link-list personal-type-radio-group"
                data-security-user-role-filter
            >
                <label class="role-toggle">
                    <input
                        type="radio"
                        name="security_user_role_filter"
                        value=""
                        <?= $selectedRole === '' ? 'checked' : ''; ?>
                    >
                    <span>Todos</span>
                </label>
                <?php foreach ($roles as $role): ?>
                    <?php $roleName = (string) ($role['rolnombre'] ?? ''); ?>
                    <label class="role-toggle">
                        <input
                            type="radio"
                            name="security_user_role_filter"
                            value="<?= htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8'); ?>"
                            <?= $selectedRole === $roleName ? 'checked' : ''; ?>
                        >
                        <span><?= htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8'); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="toolbar toolbar-filter">
            <div class="filter-box">
                <label class="sr-only" for="security-user-role-search">Buscar usuarios</label>
                <input
                    id="security-user-role-search"
                    type="search"
                    placeholder="Filtrar por usuario, cedula, nombres o apellidos"
                    data-security-user-role-search
                    data-security-user-role-search-url="<?= htmlspecialchars(baseUrl('seguridad/usuarios-roles/buscar'), ENT_QUOTES, 'UTF-8'); ?>"
                    autocomplete="off"
                >
            </div>
            <span class="filter-status" data-security-user-role-status><?= count($users); ?> registro(s)</span>
        </div>

        <div data-security-user-role-empty-wrapper <?= empty($users) ? '' : 'hidden'; ?>>
            <div class="empty-state">No se encontraron usuarios para asignar roles.</div>
        </div>

        <div class="table-wrap role-user-matrix-wrap" data-security-user-role-table-wrapper <?= empty($users) ? 'hidden' : ''; ?>>
            <table class="data-table role-matrix-table role-user-matrix-table">
                <thead>
                    <tr>
                        <th class="role-user-sticky role-user-sticky-username">Usuario</th>
                        <th class="role-user-sticky role-user-sticky-person">Persona</th>
                        <th class="role-user-sticky role-user-sticky-id">Cedula</th>
                        <?php foreach ($roles as $role): ?>
                            <?php $isStaffManagedRole = in_array((string) $role['rolnombre'], $staffManagedRoleNames, true); ?>
                            <th
                                class="role-matrix-head role-matrix-head-vertical <?= $isStaffManagedRole ? 'is-readonly-role' : ''; ?>"
                                title="<?= htmlspecialchars($isStaffManagedRole ? 'Rol sincronizado desde Asignacion de personal' : (string) ($role['roldescripcion'] ?: $role['rolnombre']), ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                <span><?= htmlspecialchars((string) $role['rolnombre'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </th>
                        <?php endforeach; ?>
                        <th>Guardar</th>
                    </tr>
                </thead>
                <tbody data-security-user-role-table-body>
                    <?php require BASE_PATH . '/app/views/seguridad/_user_role_rows.php'; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
