<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';
?>
<div class="toolbar">
    <p>Estudiantes vinculados a personas existentes dentro del sistema.</p>
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
                    <th>Estudiante</th>
                    <th>Ubicacion</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $student['percedula'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <strong><?= htmlspecialchars((string) $student['perapellidos'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                            <?= htmlspecialchars((string) $student['pernombres'], ENT_QUOTES, 'UTF-8'); ?>
                        </td>
                        <td>
                            <?= htmlspecialchars((string) ($student['estdireccion'] ?: 'Sin direccion'), ENT_QUOTES, 'UTF-8'); ?><br>
                            <?= htmlspecialchars((string) ($student['estparroquia'] ?: 'Sin parroquia'), ENT_QUOTES, 'UTF-8'); ?>
                        </td>
                        <td><?= !empty($student['estestado']) ? 'Activo' : 'Inactivo'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
