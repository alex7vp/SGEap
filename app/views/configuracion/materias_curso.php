<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$defaultStartDate = (string) ($currentPeriod['plefechainicio'] ?? date('Y-m-d'));
$assignedSubjectsByCourse = [];

foreach (($courseSubjects ?? []) as $courseSubject) {
    if (!empty($courseSubject['mtcestado'])) {
        $assignedSubjectsByCourse[(int) $courseSubject['curid']][] = (int) $courseSubject['asgid'];
    }
}

$defaultCourseId = 0;
foreach (($courses ?? []) as $course) {
    if (mb_strtoupper((string) ($course['prlnombre'] ?? '')) === 'A') {
        $defaultCourseId = (int) $course['curid'];
        break;
    }
}
if ($defaultCourseId === 0 && !empty($courses)) {
    $defaultCourseId = (int) $courses[0]['curid'];
}
?>
<p class="module-note">Relaciona asignaturas con los cursos activos del periodo visualizado.</p>

<?php if ($currentPeriod === null): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para continuar.</div>
<?php else: ?>
    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Nueva materia por curso</h3>
                <p>Periodo: <strong><?= $h($currentPeriod['pledescripcion']); ?></strong>.</p>
            </div>
        </header>

        <?php if (empty($courses)): ?>
            <div class="empty-state">No existen cursos activos para el periodo actual.</div>
        <?php elseif (empty($subjects)): ?>
            <div class="empty-state">No existen asignaturas activas disponibles.</div>
        <?php else: ?>
            <form class="data-form" method="POST" action="<?= $h(baseUrl('configuracion/academica/materias-curso')); ?>" data-course-subject-bulk-form>
                <div class="form-grid">
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Curso</span>
                            <select name="curid" required data-course-subject-course>
                                <option value="">Seleccione</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= $h($course['curid']); ?>" <?= $defaultCourseId === (int) $course['curid'] ? 'selected' : ''; ?>>
                                        <?= $h($course['nednombre'] . ' | ' . $course['granombre'] . ' ' . $course['prlnombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Inicio</span>
                            <input type="date" name="mtcfecha_inicio" value="<?= $h($defaultStartDate); ?>" required>
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Orden</span>
                            <input type="number" name="mtcorden" min="1" max="99">
                        </div>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Asignar</th>
                                <th>Area</th>
                                <th>Asignatura</th>
                                <th>Estado en curso</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subjects as $subject): ?>
                                <?php $assignedCourses = []; ?>
                                <?php foreach ($assignedSubjectsByCourse as $courseId => $assignedSubjectIds): ?>
                                    <?php if (in_array((int) $subject['asgid'], $assignedSubjectIds, true)) {
                                        $assignedCourses[] = $courseId;
                                    } ?>
                                <?php endforeach; ?>
                                <tr data-course-subject-row data-assigned-courses="<?= $h(implode(',', $assignedCourses)); ?>">
                                    <td>
                                        <input type="checkbox" name="asgid[]" value="<?= $h($subject['asgid']); ?>" data-course-subject-checkbox>
                                    </td>
                                    <td><?= $h($subject['areanombre']); ?></td>
                                    <td><span class="cell-title"><?= $h($subject['asgnombre']); ?></span></td>
                                    <td><span class="cell-subtitle" data-course-subject-status>Disponible</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="actions-row">
                    <button class="btn-primary btn-inline" type="submit">Guardar materias seleccionadas</button>
                </div>
            </form>
        <?php endif; ?>
    </section>

    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Materias registradas</h3>
                <p>Materias configuradas para los cursos del periodo actual.</p>
            </div>
        </header>

        <?php if (empty($courseSubjects)): ?>
            <div class="empty-state">Todavia no existen materias asignadas a cursos en este periodo.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Curso</th>
                            <th>Area</th>
                            <th>Asignatura</th>
                            <th>Inicio</th>
                            <th>Orden</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courseSubjects as $subject): ?>
                            <tr>
                                <td><?= $h($subject['granombre'] . ' ' . $subject['prlnombre']); ?></td>
                                <td><?= $h($subject['areanombre']); ?></td>
                                <td><?= $h($subject['asgnombre']); ?></td>
                                <td><?= $h($subject['mtcfecha_inicio']); ?></td>
                                <td><?= $h($subject['mtcorden'] ?? ''); ?></td>
                                <td>
                                    <form method="POST" action="<?= $h(baseUrl('configuracion/academica/materias-curso/estado')); ?>" class="status-switch-form">
                                        <input type="hidden" name="mtcid" value="<?= $h($subject['mtcid']); ?>">
                                        <input type="hidden" name="mtcestado" value="<?= !empty($subject['mtcestado']) ? '0' : '1'; ?>">
                                        <button class="status-switch <?= !empty($subject['mtcestado']) ? 'is-active' : ''; ?>" type="submit" title="<?= !empty($subject['mtcestado']) ? 'Inactivar materia' : 'Activar materia'; ?>" aria-label="<?= !empty($subject['mtcestado']) ? 'Inactivar materia' : 'Activar materia'; ?>">
                                            <span class="status-switch-track"><span class="status-switch-thumb"></span></span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
