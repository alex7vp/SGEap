<?php foreach ($users as $account): ?>
    <tr>
        <td><?= htmlspecialchars((string) $account['percedula'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
            <span class="person-name-inline">
                <strong><?= htmlspecialchars((string) $account['perapellidos'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <span><?= htmlspecialchars((string) $account['pernombres'], ENT_QUOTES, 'UTF-8'); ?></span>
            </span>
        </td>
        <td><?= htmlspecialchars((string) $account['usunombre'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
            <span class="state-pill <?= !empty($account['usuestado']) ? 'state-pill-active' : 'state-pill-inactive'; ?>">
                <?= !empty($account['usuestado']) ? 'Activo' : 'Inactivo'; ?>
            </span>
        </td>
        <td>
            <div class="actions-group">
                <form method="POST" action="<?= htmlspecialchars(baseUrl('seguridad/usuarios/clave'), ENT_QUOTES, 'UTF-8'); ?>" class="security-password-reset-form">
                    <input type="hidden" name="usuid" value="<?= htmlspecialchars((string) $account['usuid'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input
                        class="security-password-reset-input"
                        type="password"
                        name="usuclave_temporal"
                        minlength="6"
                        placeholder="Clave temporal"
                        aria-label="Nueva clave temporal para <?= htmlspecialchars((string) $account['usunombre'], ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    >
                    <button
                        class="btn-secondary btn-auto btn-icon-only btn-icon-small"
                        type="submit"
                        title="Restablecer clave"
                        aria-label="Restablecer clave"
                    >
                        <i class="fa fa-key" aria-hidden="true"></i>
                    </button>
                </form>
                <form method="POST" action="<?= htmlspecialchars(baseUrl('seguridad/usuarios/estado'), ENT_QUOTES, 'UTF-8'); ?>" class="status-switch-form">
                    <input type="hidden" name="usuid" value="<?= htmlspecialchars((string) $account['usuid'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="usuestado" value="<?= !empty($account['usuestado']) ? '0' : '1'; ?>">
                    <button
                        class="status-switch <?= !empty($account['usuestado']) ? 'is-active' : ''; ?>"
                        type="submit"
                        title="<?= !empty($account['usuestado']) ? 'Inactivar usuario' : 'Activar usuario'; ?>"
                        aria-label="<?= !empty($account['usuestado']) ? 'Inactivar usuario' : 'Activar usuario'; ?>"
                    >
                        <span class="status-switch-track">
                            <span class="status-switch-thumb"></span>
                        </span>
                    </button>
                </form>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
