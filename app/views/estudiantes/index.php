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
    <div class="student-filter-bar">
        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Buscar</span>
                <input
                    type="search"
                    placeholder="Cedula, nombres, apellidos o curso"
                    data-student-search
                    data-student-search-url="<?= htmlspecialchars(baseUrl('estudiantes/buscar'), ENT_QUOTES, 'UTF-8'); ?>"
                >
            </div>
        </div>
        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Curso</span>
                <select data-student-course-filter>
                    <option value="">Todos</option>
                    <?php foreach (($courses ?? []) as $course): ?>
                        <option value="<?= htmlspecialchars((string) $course['curid'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?= htmlspecialchars((string) ($course['granombre'] . ' ' . $course['prlnombre']), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <span class="table-status" data-student-search-status><?= count($students); ?> registro(s)</span>
    </div>

    <div class="table-wrap" data-student-table-wrapper>
        <table class="data-table">
            <thead>
                <tr>
                    <th><button class="table-sort-button" type="button" data-student-sort="cedula">Cedula <i class="fa fa-sort" aria-hidden="true"></i></button></th>
                    <th><button class="table-sort-button" type="button" data-student-sort="nombres">Nombres <i class="fa fa-sort" aria-hidden="true"></i></button></th>
                    <th><button class="table-sort-button is-active" type="button" data-student-sort="apellidos" data-direction="asc">Apellidos <i class="fa fa-sort-asc" aria-hidden="true"></i></button></th>
                    <th><button class="table-sort-button" type="button" data-student-sort="curso">Curso <i class="fa fa-sort" aria-hidden="true"></i></button></th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody data-student-table-body>
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
    <div data-student-list-wrapper hidden></div>
<?php endif; ?>
<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
