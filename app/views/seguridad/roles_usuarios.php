<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';
?>
<p class="module-note">Esta pagina concentra la asignacion de roles a usuarios del sistema para controlar su acceso operativo.</p>

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
