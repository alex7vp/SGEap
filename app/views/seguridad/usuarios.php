<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$selectedPersonLabel = '';

if (!empty($old['perid'])) {
    foreach ($availablePersons as $person) {
        if ((string) $person['perid'] === (string) $old['perid']) {
            $selectedPersonLabel = (string) $person['percedula'] . ' | ' . $person['perapellidos'] . ' ' . $person['pernombres'];
            break;
        }
    }
}
?>
<p class="module-note">Este modulo permite asignar cuentas de acceso a personas registradas y controlar si el usuario se mantiene activo o inactivo.</p>

<section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3>Asignar usuario</h3>
            <p>Selecciona una persona sin cuenta, define el nombre de usuario y la clave inicial.</p>
        </div>
    </header>

    <?php if (empty($availablePersons)): ?>
        <div class="empty-state">Todas las personas registradas ya tienen una cuenta asignada.</div>
    <?php else: ?>
        <form class="data-form" method="POST" action="<?= htmlspecialchars(baseUrl('seguridad/usuarios'), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="toolbar toolbar-filter security-picker-toolbar">
                <div class="filter-box">
                    <label class="sr-only" for="security-person-search">Buscar personas disponibles</label>
                    <input
                        id="security-person-search"
                        type="search"
                        placeholder="Filtrar personas disponibles por cedula, nombres o apellidos"
                        data-person-picker-search
                        data-person-picker-url="<?= htmlspecialchars(baseUrl('seguridad/personas-disponibles/buscar'), ENT_QUOTES, 'UTF-8'); ?>"
                        autocomplete="off"
                    >
                </div>
                <span class="filter-status" data-person-picker-status>Escriba al menos 2 caracteres</span>
            </div>

            <div class="table-wrap security-picker-results-wrap" data-person-picker-results-wrap>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Cedula</th>
                            <th>Persona</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody data-person-picker-results>
                        <tr>
                            <td colspan="3" class="security-picker-empty">Escriba al menos 2 caracteres para buscar personas disponibles.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="form-grid">
                <div class="form-group-full">
                    <input
                        type="hidden"
                        name="perid"
                        value="<?= htmlspecialchars((string) ($old['perid'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        data-person-picker-value
                        required
                    >
                    <div class="input-group">
                        <span class="input-addon">Persona</span>
                        <input
                            type="text"
                            value="<?= htmlspecialchars($selectedPersonLabel, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Seleccione una persona desde el filtro inferior"
                            readonly
                            data-person-picker-selected-input
                        >
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Usuario</span>
                        <input type="text" name="usunombre" value="<?= htmlspecialchars((string) ($old['usunombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required data-user-username-input>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Clave</span>
                        <input type="text" name="usuclave" value="<?= htmlspecialchars((string) ($old['usuclave'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required data-user-password-input>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Estado</span>
                        <select name="usuestado">
                            <option value="1" <?= ($old['usuestado'] ?? '1') === '1' ? 'selected' : ''; ?>>Activo</option>
                            <option value="0" <?= ($old['usuestado'] ?? '1') === '0' ? 'selected' : ''; ?>>Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="actions-row">
                <button class="btn-secondary btn-auto btn-icon-only btn-icon-small" type="reset" title="Limpiar formulario" aria-label="Limpiar formulario" hidden>
                    <i class="fa fa-eraser" aria-hidden="true"></i>
                </button>
                <button class="btn-primary btn-inline" type="submit">Guardar usuario</button>
            </div>
        </form>
    <?php endif; ?>
</section>

<section class="security-assignment-block" id="usuarios-asignados">
    <header class="security-assignment-header">
        <div>
            <h3>Usuarios asignados</h3>
            <p>Consulta las cuentas ya creadas y cambia su estado desde el mismo listado.</p>
        </div>
    </header>

    <?php if (!empty($userListFeedback)): ?>
        <div class="catalog-feedback security-feedback-global">
            <div class="alert <?= ($userListFeedback['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success'; ?> alert-dismissible" data-alert>
                <span><?= htmlspecialchars((string) ($userListFeedback['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                    <i class="fa fa-times" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <div class="toolbar toolbar-filter">
        <div class="filter-box">
            <label class="sr-only" for="security-user-search">Buscar usuarios</label>
            <input
                id="security-user-search"
                type="search"
                placeholder="Filtrar por usuario, cedula, nombres o apellidos"
                data-security-user-search
                data-security-user-search-url="<?= htmlspecialchars(baseUrl('seguridad/usuarios/buscar'), ENT_QUOTES, 'UTF-8'); ?>"
                autocomplete="off"
            >
        </div>
        <span class="filter-status" data-security-user-search-status><?= count($users); ?> registro(s)</span>
    </div>

    <div data-security-user-list-wrapper <?= empty($users) ? '' : 'hidden'; ?>>
        <div class="empty-state">Todavia no hay usuarios asignados.</div>
    </div>

    <div class="table-wrap" data-security-user-table-wrapper <?= empty($users) ? 'hidden' : ''; ?>>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Cedula</th>
                    <th>Persona</th>
                    <th>Usuario</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody data-security-user-table-body>
                <?php require BASE_PATH . '/app/views/seguridad/_users_rows.php'; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
