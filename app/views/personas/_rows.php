<?php foreach ($persons as $person): ?>
    <tr>
        <td><?= htmlspecialchars((string) $person['percedula'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
            <strong><?= htmlspecialchars((string) $person['perapellidos'], ENT_QUOTES, 'UTF-8'); ?></strong>
            <?= htmlspecialchars((string) $person['pernombres'], ENT_QUOTES, 'UTF-8'); ?>
        </td>
        <td>
            <?= htmlspecialchars((string) ($person['pertelefono1'] ?: 'Sin telefono'), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?= htmlspecialchars((string) ($person['pertelefono2'] ?: 'Sin telefono'), ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
            <?= htmlspecialchars((string) ($person['percorreo'] ?: 'Sin correo'), ENT_QUOTES, 'UTF-8'); ?>
        </td>
        <td><?= htmlspecialchars((string) ($person['persexo'] ?: 'No definido'), ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
            <div class="actions-group">
                <a
                    class="icon-button icon-button-edit"
                    href="<?= htmlspecialchars(baseUrl('personas/editar?id=' . $person['perid']), ENT_QUOTES, 'UTF-8'); ?>"
                    title="Editar persona"
                    aria-label="Editar persona"
                >&#9998;</a>

                <form method="POST" action="<?= htmlspecialchars(baseUrl('personas/eliminar'), ENT_QUOTES, 'UTF-8'); ?>" onsubmit="return confirm('Confirma que desea eliminar esta persona?');">
                    <input type="hidden" name="perid" value="<?= htmlspecialchars((string) $person['perid'], ENT_QUOTES, 'UTF-8'); ?>">
                    <button
                        class="icon-button icon-button-delete"
                        type="submit"
                        title="Eliminar persona"
                        aria-label="Eliminar persona"
                    >&#128465;</button>
                </form>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
