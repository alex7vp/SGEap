<?php foreach ($matriculas as $matricula): ?>
    <tr>
        <td><span class="cell-title"><?= htmlspecialchars((string) $matricula['percedula'], ENT_QUOTES, 'UTF-8'); ?></span><span class="cell-subtitle"><strong><?= htmlspecialchars((string) $matricula['perapellidos'], ENT_QUOTES, 'UTF-8'); ?></strong> <?= htmlspecialchars((string) $matricula['pernombres'], ENT_QUOTES, 'UTF-8'); ?></span></td>
        <td><?= htmlspecialchars(trim((string) (($matricula['granombre'] ?? '') . ' ' . ($matricula['prlnombre'] ?? ''))), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?= htmlspecialchars(trim((string) (($matricula['rep_apellidos'] ?? '') . ' ' . ($matricula['rep_nombres'] ?? '')) . (($matricula['rep_parentesco'] ?? '') !== '' ? ' (' . $matricula['rep_parentesco'] . ')' : '')), ENT_QUOTES, 'UTF-8'); ?></td>
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
                        class="icon-button icon-button-edit"
                        href="<?= htmlspecialchars(baseUrl('matriculas/editar?id=' . (string) $matricula['matid']), ENT_QUOTES, 'UTF-8'); ?>"
                        title="Editar matricula"
                        aria-label="Editar matricula"
                    >
                        <i class="fa fa-pencil" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
                <form method="POST" action="<?= htmlspecialchars(baseUrl('matriculas/estado'), ENT_QUOTES, 'UTF-8'); ?>">
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
