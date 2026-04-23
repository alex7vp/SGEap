<?php foreach ($staffMembers as $staff): ?>
    <?php
    $staffId = (int) $staff['psnid'];
    $staffAssignedTypes = $assignedTypes[$staffId] ?? [];
    ?>
    <tr id="staff-<?= htmlspecialchars((string) $staffId, ENT_QUOTES, 'UTF-8'); ?>">
        <td>
            <span class="person-name-inline">
                <strong><?= htmlspecialchars((string) $staff['perapellidos'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <span><?= htmlspecialchars((string) $staff['pernombres'], ENT_QUOTES, 'UTF-8'); ?></span>
            </span>
        </td>
        <td><?= htmlspecialchars((string) $staff['percedula'], ENT_QUOTES, 'UTF-8'); ?></td>
        <?php foreach ($staffTypes as $type): ?>
            <?php $typeId = (int) $type['tpid']; ?>
            <td class="role-matrix-cell">
                <label class="role-toggle">
                    <input
                        type="checkbox"
                        name="type_ids[]"
                        value="<?= htmlspecialchars((string) $typeId, ENT_QUOTES, 'UTF-8'); ?>"
                        form="staff-type-form-<?= htmlspecialchars((string) $staffId, ENT_QUOTES, 'UTF-8'); ?>"
                        <?= in_array($typeId, $staffAssignedTypes, true) ? 'checked' : ''; ?>
                    >
                </label>
            </td>
        <?php endforeach; ?>
        <td>
            <form id="staff-type-form-<?= htmlspecialchars((string) $staffId, ENT_QUOTES, 'UTF-8'); ?>" method="POST" action="<?= htmlspecialchars(baseUrl('personal/tipos'), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="staff_id" value="<?= htmlspecialchars((string) $staffId, ENT_QUOTES, 'UTF-8'); ?>">
            </form>
            <button class="btn-primary btn-auto btn-icon-only btn-icon-small" type="submit" form="staff-type-form-<?= htmlspecialchars((string) $staffId, ENT_QUOTES, 'UTF-8'); ?>" title="Guardar tipos" aria-label="Guardar tipos">
                <i class="fa fa-save" aria-hidden="true"></i>
            </button>
        </td>
    </tr>
<?php endforeach; ?>
