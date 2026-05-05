<?php

$formatDateTime = static function (?string $value, string $format): string {
    if ($value === null || $value === '') {
        return '';
    }

    $timestamp = strtotime($value);

    return $timestamp === false ? '' : date($format, $timestamp);
};

$stateClass = static function (string $state): string {
    return match ($state) {
        'ACTIVO' => 'state-pill-active',
        'EXPIRADO' => 'state-pill-inactive',
        default => 'state-pill-inactive',
    };
};
?>
<?php foreach ($temporaryUsers as $account): ?>
    <?php
    $temporaryState = (string) ($account['utestado'] ?? '');
    $canManage = in_array($temporaryState, ['ACTIVO', 'EXPIRADO'], true);
    $expirationValue = $formatDateTime((string) ($account['utfecha_expiracion'] ?? ''), 'Y-m-d\TH:i');
    ?>
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
            <span class="state-pill <?= $stateClass($temporaryState); ?>">
                <?= htmlspecialchars(ucfirst(strtolower($temporaryState)), ENT_QUOTES, 'UTF-8'); ?>
            </span>
        </td>
        <td><?= htmlspecialchars($formatDateTime((string) ($account['utfecha_expiracion'] ?? ''), 'd/m/Y H:i'), ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
            <?php if ($canManage): ?>
                <div class="actions-group temporary-user-actions">
                    <form method="POST" action="<?= htmlspecialchars(baseUrl('seguridad/usuarios-temporales/clave'), ENT_QUOTES, 'UTF-8'); ?>" class="security-password-reset-form">
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
                        <button class="btn-secondary btn-auto btn-icon-only btn-icon-small" type="submit" title="Restablecer clave" aria-label="Restablecer clave">
                            <i class="fa fa-key" aria-hidden="true"></i>
                        </button>
                    </form>
                    <form method="POST" action="<?= htmlspecialchars(baseUrl('seguridad/usuarios-temporales/extender'), ENT_QUOTES, 'UTF-8'); ?>" class="security-password-reset-form">
                        <input type="hidden" name="usuid" value="<?= htmlspecialchars((string) $account['usuid'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input
                            class="temporary-user-expiration-input"
                            type="datetime-local"
                            name="utfecha_expiracion"
                            value="<?= htmlspecialchars($expirationValue, ENT_QUOTES, 'UTF-8'); ?>"
                            aria-label="Nueva fecha de expiracion para <?= htmlspecialchars((string) $account['usunombre'], ENT_QUOTES, 'UTF-8'); ?>"
                            required
                        >
                        <button class="btn-secondary btn-auto btn-icon-only btn-icon-small" type="submit" title="Extender vigencia" aria-label="Extender vigencia">
                            <i class="fa fa-clock-o" aria-hidden="true"></i>
                        </button>
                    </form>
                    <form method="POST" action="<?= htmlspecialchars(baseUrl('seguridad/usuarios-temporales/eliminar'), ENT_QUOTES, 'UTF-8'); ?>" class="security-password-reset-form">
                        <input type="hidden" name="usuid" value="<?= htmlspecialchars((string) $account['usuid'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input
                            class="temporary-user-reason-input"
                            type="text"
                            name="utmotivo_eliminacion"
                            placeholder="Motivo"
                            aria-label="Motivo para anular <?= htmlspecialchars((string) $account['usunombre'], ENT_QUOTES, 'UTF-8'); ?>"
                        >
                        <button class="btn-secondary btn-auto btn-icon-only btn-icon-small" type="submit" title="Anular acceso temporal" aria-label="Anular acceso temporal">
                            <i class="fa fa-times" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <span class="muted-text">
                    <?= htmlspecialchars((string) ($account['utmotivo_eliminacion'] ?? 'Sin acciones disponibles'), ENT_QUOTES, 'UTF-8'); ?>
                </span>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>
