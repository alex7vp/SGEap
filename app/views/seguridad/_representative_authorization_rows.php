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
