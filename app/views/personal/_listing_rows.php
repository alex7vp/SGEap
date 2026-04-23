<?php foreach ($staffMembers as $staff): ?>
    <tr>
        <td><?= htmlspecialchars((string) $staff['percedula'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
            <span class="cell-title"><?= htmlspecialchars((string) $staff['perapellidos'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="cell-subtitle"><?= htmlspecialchars((string) $staff['pernombres'], ENT_QUOTES, 'UTF-8'); ?></span>
        </td>
        <td>
            <span class="cell-title"><?= htmlspecialchars((string) $staff['tipos_personal'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="cell-subtitle"><?= htmlspecialchars((string) (($staff['percorreo'] ?? '') !== '' ? $staff['percorreo'] : 'Sin correo registrado'), ENT_QUOTES, 'UTF-8'); ?></span>
        </td>
        <td>
            <span class="cell-title"><?= htmlspecialchars((string) ($staff['psnfechacontratacion'] ?? 'Sin fecha'), ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="cell-subtitle"><?= htmlspecialchars((string) (($staff['psnfechasalida'] ?? '') !== '' ? 'Salida: ' . $staff['psnfechasalida'] : 'Sin fecha de salida'), ENT_QUOTES, 'UTF-8'); ?></span>
        </td>
        <td>
            <span class="state-pill <?= !empty($staff['psnestado']) ? 'state-pill-active' : 'state-pill-inactive'; ?>">
                <?= !empty($staff['psnestado']) ? 'Activo' : 'Inactivo'; ?>
            </span>
        </td>
        <td>
            <a
                class="btn-secondary btn-auto btn-icon-only btn-icon-small"
                href="<?= htmlspecialchars(baseUrl('personal/editar') . '?id=' . urlencode((string) $staff['psnid']), ENT_QUOTES, 'UTF-8'); ?>"
                title="Editar personal"
                aria-label="Editar personal"
            >
                <i class="fa fa-pencil" aria-hidden="true"></i>
            </a>
        </td>
    </tr>
<?php endforeach; ?>
