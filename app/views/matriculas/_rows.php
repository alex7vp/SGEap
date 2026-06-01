<?php foreach ($matriculas as $matricula): ?>
    <?php
    $representativeAuthState = (string) ($matricula['rep_rhmestado'] ?? '');
    $representativeAuthActive = $representativeAuthState === 'ACTIVO';
    $representativeUserId = (int) ($matricula['rep_usuid'] ?? 0);
    $representativePersonId = (int) ($matricula['rep_perid'] ?? 0);
    $representativeUserActive = !empty($matricula['rep_usuestado']);
    $representativeToggleTitle = !$canToggleRepresentativeMatriculation
        ? 'No hay periodo de matricula para el proximo periodo lectivo habilitado'
        : ($representativeAuthActive
            ? 'Deshabilitar matricula para el proximo periodo lectivo'
            : 'Habilitar matricula para el proximo periodo lectivo');
    $canToggleRepresentative = !empty($canToggleRepresentativeMatriculation)
        && $representativeUserId > 0
        && $representativePersonId > 0
        && ($representativeUserActive || $representativeAuthActive);
    ?>
    <tr>
        <td><span class="cell-title"><?= htmlspecialchars((string) $matricula['percedula'], ENT_QUOTES, 'UTF-8'); ?></span><span class="cell-subtitle"><strong><?= htmlspecialchars((string) $matricula['perapellidos'], ENT_QUOTES, 'UTF-8'); ?></strong> <?= htmlspecialchars((string) $matricula['pernombres'], ENT_QUOTES, 'UTF-8'); ?></span></td>
        <td><?= htmlspecialchars(trim((string) (($matricula['granombre'] ?? '') . ' ' . ($matricula['prlnombre'] ?? ''))), ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
            <span class="cell-title"><?= htmlspecialchars(trim((string) (($matricula['rep_apellidos'] ?? '') . ' ' . ($matricula['rep_nombres'] ?? '')) . (($matricula['rep_parentesco'] ?? '') !== '' ? ' (' . $matricula['rep_parentesco'] . ')' : '')), ENT_QUOTES, 'UTF-8'); ?></span>
            <?php if ($representativeUserId > 0): ?>
                <form method="POST" action="<?= htmlspecialchars(baseUrl('matriculas/representante/rematricula'), ENT_QUOTES, 'UTF-8'); ?>" class="status-switch-form representative-matriculation-switch-form">
                    <?= csrfField(); ?>
                    <input type="hidden" name="rep_usuid" value="<?= htmlspecialchars((string) $representativeUserId, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="rep_perid" value="<?= htmlspecialchars((string) $representativePersonId, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="redirect_to" value="/matriculas?panel=gestion#matriculas-registradas">
                    <button
                        class="status-switch <?= $representativeAuthActive ? 'is-active' : ''; ?>"
                        type="submit"
                        title="<?= htmlspecialchars($representativeToggleTitle, ENT_QUOTES, 'UTF-8'); ?>"
                        aria-label="<?= htmlspecialchars($representativeToggleTitle, ENT_QUOTES, 'UTF-8'); ?>"
                        <?= $canToggleRepresentative ? '' : 'disabled'; ?>
                    >
                        <span class="status-switch-track">
                            <span class="status-switch-thumb"></span>
                        </span>
                    </button>
                </form>
                <?php if (!$representativeUserActive): ?>
                    <span class="cell-subtitle">Usuario inactivo</span>
                <?php endif; ?>
            <?php else: ?>
                <span class="cell-subtitle">Sin usuario representante</span>
            <?php endif; ?>
        </td>
        <td><?= htmlspecialchars((string) $matricula['emdnombre'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
            <?php if (empty($matricula['student_usuid'])): ?>
                <span class="state-pill state-pill-inactive">Sin usuario</span>
            <?php else: ?>
                <span class="state-pill <?= !empty($matricula['student_usuestado']) ? 'state-pill-active' : 'state-pill-inactive'; ?>">
                    <?= !empty($matricula['student_usuestado']) ? 'Usuario activo' : 'Usuario inactivo'; ?>
                </span>
                <span class="cell-subtitle"><?= htmlspecialchars((string) $matricula['student_usunombre'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
        </td>
        <td>
            <div class="actions-group matricula-actions">
                <?php if (!empty($canEditMatriculas)): ?>
                    <a
                        class="icon-button icon-button-view"
                        href="<?= htmlspecialchars(baseUrl('matriculas/ficha?id=' . (string) $matricula['matid']), ENT_QUOTES, 'UTF-8'); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        title="Ficha de matricula PDF"
                        aria-label="Ficha de matricula PDF"
                    >
                        <i class="fa fa-file-pdf-o" aria-hidden="true"></i>
                    </a>
                    <a
                        class="icon-button icon-button-edit"
                        href="<?= htmlspecialchars(baseUrl('matriculas/editar?id=' . (string) $matricula['matid']), ENT_QUOTES, 'UTF-8'); ?>"
                        title="Editar matricula"
                        aria-label="Editar matricula"
                    >
                        <i class="fa fa-pencil" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
                <form method="POST" action="<?= htmlspecialchars(baseUrl('matriculas/estado'), ENT_QUOTES, 'UTF-8'); ?>">
                    <?= csrfField(); ?>
                    <input type="hidden" name="matid" value="<?= htmlspecialchars((string) $matricula['matid'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="redirect_to" value="/matriculas?panel=gestion#matriculas-registradas">
                    <button
                        class="icon-button <?= !empty($matricula['estestado']) ? 'icon-button-delete' : 'icon-button-view'; ?>"
                        type="submit"
                        title="<?= !empty($matricula['estestado']) ? 'Inhabilitar matricula' : 'Habilitar matricula'; ?>"
                        aria-label="<?= !empty($matricula['estestado']) ? 'Inhabilitar matricula' : 'Habilitar matricula'; ?>"
                    >
                        <i class="fa <?= !empty($matricula['estestado']) ? 'fa-ban' : 'fa-check'; ?>" aria-hidden="true"></i>
                    </button>
                </form>
                <?php if (!empty($matricula['estestado']) && (empty($matricula['student_usuid']) || empty($matricula['student_usuestado']))): ?>
                    <form
                        method="POST"
                        action="<?= htmlspecialchars(baseUrl('matriculas/sincronizar-accesos/matricula'), ENT_QUOTES, 'UTF-8'); ?>"
                        onsubmit="return confirm('Se creara o reactivara el usuario de esta matricula. Desea continuar?');"
                    >
                        <?= csrfField(); ?>
                        <input type="hidden" name="matid" value="<?= htmlspecialchars((string) $matricula['matid'], ENT_QUOTES, 'UTF-8'); ?>">
                        <button
                            class="icon-button icon-button-view"
                            type="submit"
                            title="Sincronizar usuario"
                            aria-label="Sincronizar usuario"
                        >
                            <i class="fa fa-refresh" aria-hidden="true"></i>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
