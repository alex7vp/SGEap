<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';
?>
<p class="module-note">Este modulo concentra la asignacion de permisos funcionales a cada rol del sistema.</p>

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

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
