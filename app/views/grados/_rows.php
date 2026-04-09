<?php foreach ($grades as $grade): ?>
    <tr>
        <td><?= htmlspecialchars((string) $grade['nednombre'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><strong><?= htmlspecialchars((string) $grade['granombre'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
        <td>
            <div class="actions-group">
                <a
                    class="icon-button icon-button-edit"
                    href="<?= htmlspecialchars(baseUrl('grados/editar?id=' . $grade['graid']), ENT_QUOTES, 'UTF-8'); ?>"
                    title="Editar grado"
                    aria-label="Editar grado"
                >&#9998;</a>

                <form method="POST" action="<?= htmlspecialchars(baseUrl('grados/eliminar'), ENT_QUOTES, 'UTF-8'); ?>" onsubmit="return confirm('Confirma que desea eliminar este grado?');">
                    <input type="hidden" name="graid" value="<?= htmlspecialchars((string) $grade['graid'], ENT_QUOTES, 'UTF-8'); ?>">
                    <button
                        class="icon-button icon-button-delete"
                        type="submit"
                        title="Eliminar grado"
                        aria-label="Eliminar grado"
                    >&#128465;</button>
                </form>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
