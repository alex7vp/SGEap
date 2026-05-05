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
<p class="module-note">Este modulo permite crear y administrar accesos temporales para representantes de alumnos nuevos.</p>

<?php if (!empty($feedback)): ?>
    <div class="catalog-feedback security-feedback-global">
        <div class="alert <?= ($feedback['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success'; ?> alert-dismissible" data-alert>
            <span><?= htmlspecialchars((string) ($feedback['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
            <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                <i class="fa fa-times" aria-hidden="true"></i>
            </button>
        </div>
    </div>
<?php endif; ?>

<section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3>Crear usuario temporal</h3>
            <p>Selecciona una persona sin cuenta y define el acceso inicial para el representante.</p>
        </div>
    </header>

    <?php if (empty($availablePersons)): ?>
        <div class="empty-state">Todas las personas registradas ya tienen una cuenta asignada.</div>
    <?php else: ?>
        <form class="data-form" method="POST" action="<?= htmlspecialchars(baseUrl('seguridad/usuarios-temporales'), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="toolbar toolbar-filter security-picker-toolbar">
                <div class="filter-box">
                    <label class="sr-only" for="temporary-person-search">Buscar personas disponibles</label>
                    <input
                        id="temporary-person-search"
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
                            placeholder="Seleccione una persona desde el filtro superior"
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
                        <input type="password" name="usuclave" value="" minlength="6" required data-user-password-input>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Expira</span>
                        <input type="datetime-local" name="utfecha_expiracion" value="<?= htmlspecialchars((string) ($old['utfecha_expiracion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                </div>
            </div>
            <div class="actions-row">
                <button class="btn-primary btn-inline" type="submit">Crear acceso temporal</button>
            </div>
        </form>
    <?php endif; ?>
</section>

<section class="security-assignment-block" id="usuarios-temporales-listado">
    <header class="security-assignment-header">
        <div>
            <h3>Usuarios temporales</h3>
            <p>Extiende la vigencia, restablece claves o anula accesos temporales registrados.</p>
        </div>
    </header>

    <?php if (empty($temporaryUsers)): ?>
        <div class="empty-state">No existen usuarios temporales registrados.</div>
    <?php else: ?>
        <div class="table-wrap temporary-users-table-wrap">
            <table class="data-table security-table temporary-users-table">
                <thead>
                    <tr>
                        <th>Cedula</th>
                        <th>Persona</th>
                        <th>Usuario</th>
                        <th>Estado</th>
                        <th>Expira</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php require BASE_PATH . '/app/views/seguridad/_temporary_user_rows.php'; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
