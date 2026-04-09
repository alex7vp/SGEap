<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';
?>
<p class="module-note">Este modulo concentra la asignacion de permisos a roles y la designacion de roles a usuarios del sistema.</p>

<section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3>Permisos por rol</h3>
            <p>Cada rol concentra sus permisos funcionales. Luego esos roles se asignan a los usuarios.</p>
        </div>
    </header>

    <?php if (empty($roles)): ?>
        <div class="empty-state">No existen roles creados todavia. Primero registra roles en el catalogo de seguridad.</div>
    <?php elseif (empty($permissions)): ?>
        <div class="empty-state">No existen permisos creados todavia. Primero registra permisos en el catalogo de seguridad.</div>
    <?php else: ?>
        <section class="permission-grid">
        <?php foreach ($roles as $role): ?>
            <?php
            $roleId = (int) $role['rolid'];
            $roleAnchor = 'role-' . $roleId;
            $assignedIds = $assignedPermissions[$roleId] ?? [];
            ?>
            <article class="permission-card" id="<?= htmlspecialchars($roleAnchor, ENT_QUOTES, 'UTF-8'); ?>">
                <header class="permission-card-header">
                    <div>
                        <h3><?= htmlspecialchars((string) $role['rolnombre'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p><?= htmlspecialchars((string) ($role['roldescripcion'] ?: 'Sin descripcion registrada.'), ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <span class="state-pill <?= !empty($role['rolestado']) ? 'state-pill-active' : 'state-pill-inactive'; ?>">
                        <?= !empty($role['rolestado']) ? 'Activo' : 'Inactivo'; ?>
                    </span>
                </header>

                <?php if (!empty($assignmentFeedback) && (int) ($assignmentFeedback['role_id'] ?? 0) === $roleId): ?>
                    <div class="catalog-feedback">
                        <div class="alert <?= ($assignmentFeedback['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success'; ?> alert-dismissible" data-alert>
                            <span><?= htmlspecialchars((string) ($assignmentFeedback['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                            <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                                <i class="fa fa-times" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= htmlspecialchars(baseUrl('seguridad/roles-permisos'), ENT_QUOTES, 'UTF-8'); ?>" class="permission-form">
                    <input type="hidden" name="role_id" value="<?= htmlspecialchars((string) $roleId, ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="permission-list">
                        <?php foreach ($permissions as $permission): ?>
                            <?php
                            $permissionId = (int) $permission['prmid'];
                            $isAssigned = in_array($permissionId, $assignedIds, true);
                            ?>
                            <label class="permission-option">
                                <input
                                    type="checkbox"
                                    name="permission_ids[]"
                                    value="<?= htmlspecialchars((string) $permissionId, ENT_QUOTES, 'UTF-8'); ?>"
                                    <?= $isAssigned ? 'checked' : ''; ?>
                                >
                                <span class="permission-option-body">
                                    <span class="permission-option-title">
                                        <strong><?= htmlspecialchars((string) $permission['prmnombre'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <code><?= htmlspecialchars((string) $permission['prmcodigo'], ENT_QUOTES, 'UTF-8'); ?></code>
                                    </span>
                                    <span class="permission-option-description">
                                        <?= htmlspecialchars((string) ($permission['prmdescripcion'] ?: 'Sin descripcion registrada.'), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </span>
                                <span class="permission-option-state <?= !empty($permission['prmestado']) ? 'is-active' : 'is-inactive'; ?>">
                                    <?= !empty($permission['prmestado']) ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="actions-row">
                        <button class="btn-primary btn-inline" type="submit">Guardar permisos</button>
                    </div>
                </form>
            </article>
        <?php endforeach; ?>
        </section>
    <?php endif; ?>
</section>

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

        <div class="table-wrap" data-security-user-role-table-wrapper <?= empty($users) ? 'hidden' : ''; ?>>
            <table class="data-table role-matrix-table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Persona</th>
                        <th>Cedula</th>
                        <?php foreach ($roles as $role): ?>
                            <th class="role-matrix-head" title="<?= htmlspecialchars((string) ($role['roldescripcion'] ?: $role['rolnombre']), ENT_QUOTES, 'UTF-8'); ?>">
                                <?= htmlspecialchars((string) $role['rolnombre'], ENT_QUOTES, 'UTF-8'); ?>
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
