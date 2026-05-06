<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$selectedPersonLabel = '';
$formatDateTime = static function (?string $value, string $format): string {
    if ($value === null || $value === '') {
        return '';
    }

    $timestamp = strtotime($value);

    return $timestamp === false ? '' : date($format, $timestamp);
};

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

<section class="security-assignment-block" id="representantes-habilitacion">
    <header class="security-assignment-header">
        <div>
            <h3>Agregar alumno</h3>
            <p>Habilita a un representante formal para matricular un nuevo estudiante en el periodo activo.</p>
        </div>
    </header>

    <?php if (empty($enabledPeriod)): ?>
        <div class="empty-state">No existe un periodo lectivo con matricula habilitada.</div>
    <?php elseif (empty($representatives)): ?>
        <div class="empty-state">No existen representantes formales registrados.</div>
    <?php else: ?>
        <div class="table-wrap temporary-users-table-wrap">
            <table class="data-table security-table temporary-users-table">
                <thead>
                    <tr>
                        <th>Cedula</th>
                        <th>Representante</th>
                        <th>Usuario</th>
                        <th>Habilitacion</th>
                        <th>Expira</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($representatives as $representative): ?>
                        <?php
                        $authorizationState = (string) ($representative['rhmestado'] ?? '');
                        $authorizationActive = $authorizationState === 'ACTIVO';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $representative['percedula'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <span class="person-name-inline">
                                    <strong><?= htmlspecialchars((string) $representative['perapellidos'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span><?= htmlspecialchars((string) $representative['pernombres'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </span>
                            </td>
                            <td><?= htmlspecialchars((string) $representative['usunombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <?php if ($authorizationState !== ''): ?>
                                    <span class="state-pill <?= $authorizationActive ? 'state-pill-active' : 'state-pill-inactive'; ?>">
                                        <?= htmlspecialchars(ucfirst(strtolower($authorizationState)), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="muted-text">Sin habilitacion</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($formatDateTime((string) ($representative['rhmfecha_expiracion'] ?? ''), 'd/m/Y H:i'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <?php if ($authorizationActive): ?>
                                    <form method="POST" action="<?= htmlspecialchars(baseUrl('seguridad/representantes/matricula-nueva/anular'), ENT_QUOTES, 'UTF-8'); ?>" class="security-password-reset-form">
                                        <input type="hidden" name="rhmid" value="<?= htmlspecialchars((string) $representative['rhmid'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="perid" value="<?= htmlspecialchars((string) $representative['perid'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input
                                            class="temporary-user-reason-input"
                                            type="text"
                                            name="rhmmotivo_anulacion"
                                            placeholder="Motivo"
                                            aria-label="Motivo para anular habilitacion de <?= htmlspecialchars((string) $representative['usunombre'], ENT_QUOTES, 'UTF-8'); ?>"
                                        >
                                        <button class="btn-secondary btn-auto btn-icon-only btn-icon-small" type="submit" title="Anular habilitacion" aria-label="Anular habilitacion">
                                            <i class="fa fa-times" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="<?= htmlspecialchars(baseUrl('seguridad/representantes/matricula-nueva'), ENT_QUOTES, 'UTF-8'); ?>" class="security-password-reset-form">
                                        <input type="hidden" name="usuid" value="<?= htmlspecialchars((string) $representative['usuid'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input
                                            class="temporary-user-expiration-input"
                                            type="datetime-local"
                                            name="rhmfecha_expiracion"
                                            aria-label="Fecha de expiracion para <?= htmlspecialchars((string) $representative['usunombre'], ENT_QUOTES, 'UTF-8'); ?>"
                                        >
                                        <input
                                            class="temporary-user-reason-input"
                                            type="text"
                                            name="rhmobservacion"
                                            placeholder="Observacion"
                                            aria-label="Observacion para <?= htmlspecialchars((string) $representative['usunombre'], ENT_QUOTES, 'UTF-8'); ?>"
                                        >
                                        <button class="btn-secondary btn-auto btn-icon-only btn-icon-small" type="submit" title="Habilitar nuevo estudiante" aria-label="Habilitar nuevo estudiante">
                                            <i class="fa fa-plus" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
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
