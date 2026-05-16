<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$defaultStartDate = (string) ($currentPeriod['plefechainicio'] ?? date('Y-m-d'));
$visibleAssignments = [];
$assignedTeachersBySubject = [];

foreach (($courseSubjects ?? []) as $subject) {
    foreach (($teacherAssignments[(int) $subject['mtcid']] ?? []) as $assignment) {
        $assignedTeachersBySubject[(int) $subject['mtcid']][] = (int) $assignment['perid'];
        $visibleAssignments[] = [
            'subject' => $subject,
            'assignment' => $assignment,
        ];
    }
}
?>
<p class="module-note">Vincula docentes activos con las materias asignadas a cada curso.</p>

<?php if ($currentPeriod === null): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para continuar.</div>
<?php else: ?>
    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Nueva designacion</h3>
                <p>Periodo: <strong><?= $h($currentPeriod['pledescripcion']); ?></strong>.</p>
            </div>
        </header>

        <?php if (empty($courseSubjects)): ?>
            <div class="empty-state">No existen materias activas por curso. Configuralas primero en Materias por curso.</div>
        <?php elseif (empty($teachers)): ?>
            <div class="empty-state">No existen docentes activos. Registra personal con tipo Docente antes de continuar.</div>
        <?php else: ?>
            <form class="data-form" method="POST" action="<?= $h(baseUrl('configuracion/academica/docentes')); ?>" data-teacher-subject-bulk-form>
                <div class="form-grid">
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Docente</span>
                            <select name="perid" required data-teacher-subject-teacher>
                                <option value="">Seleccione</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?= $h($teacher['perid']); ?>">
                                        <?= $h($teacher['perapellidos'] . ' ' . $teacher['pernombres'] . ' | ' . $teacher['percedula']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Filtrar curso</span>
                            <select data-teacher-subject-course-filter>
                                <option value="">Todos los cursos</option>
                                <?php
                                $seenCourses = [];
                                foreach ($courseSubjects as $subject):
                                    $courseId = (int) $subject['curid'];
                                    if (isset($seenCourses[$courseId])) {
                                        continue;
                                    }
                                    $seenCourses[$courseId] = true;
                                ?>
                                    <option value="<?= $h($courseId); ?>"><?= $h($subject['granombre'] . ' ' . $subject['prlnombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Inicio</span>
                            <input type="date" name="mcdfecha_inicio" value="<?= $h($defaultStartDate); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Asignar</th>
                                <th>Curso</th>
                                <th>Area</th>
                                <th>Materia</th>
                                <th>Estado docente</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courseSubjects as $subject): ?>
                                <tr data-teacher-subject-row data-course-id="<?= $h($subject['curid']); ?>" data-assigned-teachers="<?= $h(implode(',', $assignedTeachersBySubject[(int) $subject['mtcid']] ?? [])); ?>">
                                    <td>
                                        <input type="checkbox" name="mtcid[]" value="<?= $h($subject['mtcid']); ?>" data-teacher-subject-checkbox>
                                    </td>
                                    <td><?= $h($subject['granombre'] . ' ' . $subject['prlnombre']); ?></td>
                                    <td><?= $h($subject['areanombre']); ?></td>
                                    <td><span class="cell-title"><?= $h($subject['asgnombre']); ?></span></td>
                                    <td><span class="cell-subtitle" data-teacher-subject-status>Disponible</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="actions-row">
                    <button class="btn-primary btn-inline" type="submit">Asignar materias seleccionadas</button>
                </div>
            </form>
        <?php endif; ?>
    </section>

    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Designaciones activas</h3>
                <p>Docentes vinculados actualmente a cada materia.</p>
            </div>
        </header>

        <?php if (empty($visibleAssignments)): ?>
            <div class="empty-state">Todavia no hay docentes designados en este periodo.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Curso</th>
                            <th>Materia</th>
                            <th>Docente</th>
                            <th>Inicio</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($visibleAssignments as $row): ?>
                            <?php $subject = $row['subject']; $assignment = $row['assignment']; ?>
                            <tr>
                                <td><?= $h($subject['granombre'] . ' ' . $subject['prlnombre']); ?></td>
                                <td><?= $h($subject['asgnombre']); ?></td>
                                <td><?= $h($assignment['perapellidos'] . ' ' . $assignment['pernombres']); ?></td>
                                <td><?= $h($assignment['mcdfecha_inicio']); ?></td>
                                <td>
                                    <form method="POST" action="<?= $h(baseUrl('configuracion/academica/docentes/retirar')); ?>">
                                        <input type="hidden" name="mcdid" value="<?= $h($assignment['mcdid']); ?>">
                                        <button class="btn-secondary btn-inline" type="submit">Retirar</button>
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
