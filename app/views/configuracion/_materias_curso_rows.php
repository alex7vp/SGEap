<?php

declare(strict_types=1);

$h = $h ?? static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<?php foreach (($courseSubjects ?? []) as $subject): ?>
    <tr>
        <td><?= $h($subject['granombre'] . ' ' . $subject['prlnombre']); ?></td>
        <td><?= $h($subject['areanombre']); ?></td>
        <td><?= $h($subject['asgnombre']); ?></td>
        <td><?= $h($subject['mtcfecha_inicio']); ?></td>
        <td><?= $h($subject['mtcorden'] ?? ''); ?></td>
        <td>
            <form method="POST" action="<?= $h(baseUrl('configuracion/academica/materias-curso/estado')); ?>" class="status-switch-form">
                <?= csrfField(); ?>
                <input type="hidden" name="mtcid" value="<?= $h($subject['mtcid']); ?>">
                <input type="hidden" name="mtcestado" value="<?= !empty($subject['mtcestado']) ? '0' : '1'; ?>">
                <button class="status-switch <?= !empty($subject['mtcestado']) ? 'is-active' : ''; ?>" type="submit" title="<?= !empty($subject['mtcestado']) ? 'Inactivar materia' : 'Activar materia'; ?>" aria-label="<?= !empty($subject['mtcestado']) ? 'Inactivar materia' : 'Activar materia'; ?>">
                    <span class="status-switch-track"><span class="status-switch-thumb"></span></span>
                </button>
            </form>
        </td>
    </tr>
<?php endforeach; ?>
