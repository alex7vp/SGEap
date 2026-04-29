<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';
?>
<div class="toolbar">
    <p>Estudiantes registrados<?= !empty($currentPeriod['pledescripcion']) ? ' en el periodo ' . htmlspecialchars((string) $currentPeriod['pledescripcion'], ENT_QUOTES, 'UTF-8') : ''; ?>.</p>
    <a class="btn-primary btn-auto" href="<?= htmlspecialchars(baseUrl('estudiantes/crear'), ENT_QUOTES, 'UTF-8'); ?>">Nuevo estudiante</a>
</div>

<?php if (empty($students)): ?>
    <div class="empty-state">Todavia no hay estudiantes registrados.</div>
<?php else: ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Cedula</th>
                    <th>Nombres</th>
                    <th>Apellidos</th>
                    <th>Curso</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $student['percedula'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $student['pernombres'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $student['perapellidos'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) ($student['curso'] ?? 'Sin matricula'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <a class="icon-button icon-button-view" href="<?= htmlspecialchars(baseUrl('estudiantes/ver?id=' . (int) $student['estid']), ENT_QUOTES, 'UTF-8'); ?>" title="Ver ficha" aria-label="Ver ficha">
                                <i class="fa fa-search" aria-hidden="true"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
