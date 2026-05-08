<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$teacherAssignments = $teacherAssignments ?? [];
$today = date('Y-m-d');
$courseSubjectOptions = array_values(array_filter(
    $courseSubjects ?? [],
    static fn (array $courseSubject): bool => !empty($courseSubject['mtcestado'])
));
?>
<p class="module-note">Configura la estructura academica usada por asistencia en el periodo lectivo seleccionado.</p>

<section class="security-assignment-block" id="areas">
    <header class="security-assignment-header">
        <div>
            <h3>Areas academicas</h3>
            <p>Catalogo base para agrupar asignaturas.</p>
        </div>
    </header>

    <form class="data-form" method="POST" action="<?= htmlspecialchars(baseUrl('asistencia/areas'), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="form-grid">
            <div>
                <div class="input-group">
                    <span class="input-addon">Area</span>
                    <input type="text" name="areanombre" maxlength="100" required>
                </div>
            </div>
        </div>
        <div class="actions-row">
            <button class="btn-primary btn-inline" type="submit">Guardar area</button>
        </div>
    </form>

    <?php if (empty($areas)): ?>
        <div class="empty-state">Todavia no hay areas registradas.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Area</th>
                        <th>Actualizar</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($areas as $area): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $area['areanombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <form method="POST" action="<?= htmlspecialchars(baseUrl('asistencia/areas/actualizar'), ENT_QUOTES, 'UTF-8'); ?>" class="status-switch-form">
                                    <input type="hidden" name="areaid" value="<?= htmlspecialchars((string) $area['areaid'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="input-group">
                                        <span class="input-addon">Nombre</span>
                                        <input type="text" name="areanombre" value="<?= htmlspecialchars((string) $area['areanombre'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="100" required>
                                    </div>
                                    <button class="btn-secondary btn-auto" type="submit">Actualizar</button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="<?= htmlspecialchars(baseUrl('asistencia/areas/estado'), ENT_QUOTES, 'UTF-8'); ?>" class="status-switch-form">
                                    <input type="hidden" name="areaid" value="<?= htmlspecialchars((string) $area['areaid'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="areaestado" value="<?= !empty($area['areaestado']) ? '0' : '1'; ?>">
                                    <button
                                        class="status-switch <?= !empty($area['areaestado']) ? 'is-active' : ''; ?>"
                                        type="submit"
                                        title="<?= !empty($area['areaestado']) ? 'Inactivar area' : 'Activar area'; ?>"
                                        aria-label="<?= !empty($area['areaestado']) ? 'Inactivar area' : 'Activar area'; ?>"
                                    >
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

<section class="security-assignment-block" id="asignaturas">
    <header class="security-assignment-header">
        <div>
            <h3>Asignaturas</h3>
            <p>Define las asignaturas institucionales y su area academica.</p>
        </div>
    </header>

    <?php if (empty($activeAreas)): ?>
        <div class="empty-state">Registra al menos un area activa para crear asignaturas.</div>
    <?php else: ?>
        <form class="data-form" method="POST" action="<?= htmlspecialchars(baseUrl('asistencia/asignaturas'), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-grid">
                <div>
                    <div class="input-group">
                        <span class="input-addon">Area</span>
                        <select name="areaid" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($activeAreas as $area): ?>
                                <option value="<?= htmlspecialchars((string) $area['areaid'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?= htmlspecialchars((string) $area['areanombre'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Asignatura</span>
                        <input type="text" name="asgnombre" maxlength="100" required>
                    </div>
                </div>
            </div>
            <div class="actions-row">
                <button class="btn-primary btn-inline" type="submit">Guardar asignatura</button>
            </div>
        </form>
    <?php endif; ?>

    <?php if (empty($subjects)): ?>
        <div class="empty-state">Todavia no hay asignaturas registradas.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Area</th>
                        <th>Asignatura</th>
                        <th>Actualizar</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects as $subject): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $subject['areanombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars((string) $subject['asgnombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <form method="POST" action="<?= htmlspecialchars(baseUrl('asistencia/asignaturas/actualizar'), ENT_QUOTES, 'UTF-8'); ?>" class="status-switch-form">
                                    <input type="hidden" name="asgid" value="<?= htmlspecialchars((string) $subject['asgid'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="input-group">
                                        <span class="input-addon">Area</span>
                                        <select name="areaid" required>
                                            <?php foreach ($areas as $area): ?>
                                                <option value="<?= htmlspecialchars((string) $area['areaid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) $area['areaid'] === (int) $subject['areaid'] ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars((string) $area['areanombre'], ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="input-group">
                                        <span class="input-addon">Nombre</span>
                                        <input type="text" name="asgnombre" value="<?= htmlspecialchars((string) $subject['asgnombre'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="100" required>
                                    </div>
                                    <button class="btn-secondary btn-auto" type="submit">Actualizar</button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="<?= htmlspecialchars(baseUrl('asistencia/asignaturas/estado'), ENT_QUOTES, 'UTF-8'); ?>" class="status-switch-form">
                                    <input type="hidden" name="asgid" value="<?= htmlspecialchars((string) $subject['asgid'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="asgestado" value="<?= !empty($subject['asgestado']) ? '0' : '1'; ?>">
                                    <button
                                        class="status-switch <?= !empty($subject['asgestado']) ? 'is-active' : ''; ?>"
                                        type="submit"
                                        title="<?= !empty($subject['asgestado']) ? 'Inactivar asignatura' : 'Activar asignatura'; ?>"
                                        aria-label="<?= !empty($subject['asgestado']) ? 'Inactivar asignatura' : 'Activar asignatura'; ?>"
                                    >
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

<?php if ($currentPeriod === null): ?>
    <section class="security-assignment-block">
        <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para configurar materias.</div>
    </section>
<?php else: ?>
    <section class="security-assignment-block" id="materias">
        <header class="security-assignment-header">
            <div>
                <h3>Materias por curso</h3>
                <p>Registra manualmente las asignaturas que pertenecen a cada curso del periodo actual.</p>
            </div>
        </header>

        <?php if (empty($courses) || empty($activeSubjects)): ?>
            <div class="empty-state">Necesitas cursos activos y asignaturas activas para crear materias del periodo.</div>
        <?php else: ?>
            <form class="data-form" method="POST" action="<?= htmlspecialchars(baseUrl('asistencia/materias'), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-grid">
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Curso</span>
                            <select name="curid" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= htmlspecialchars((string) $course['curid'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= htmlspecialchars((string) $course['nednombre'] . ' | ' . $course['granombre'] . ' ' . $course['prlnombre'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Asignatura</span>
                            <select name="asgid" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($activeSubjects as $subject): ?>
                                    <option value="<?= htmlspecialchars((string) $subject['asgid'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= htmlspecialchars((string) $subject['areanombre'] . ' | ' . $subject['asgnombre'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Inicio</span>
                            <input type="date" name="mtcfecha_inicio" value="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Orden</span>
                            <input type="number" name="mtcorden" min="1" step="1">
                        </div>
                    </div>
                </div>
                <div class="actions-row">
                    <button class="btn-primary btn-inline" type="submit">Guardar materia</button>
                </div>
            </form>
        <?php endif; ?>

        <?php if (empty($courseSubjects)): ?>
            <div class="empty-state">Todavia no hay materias registradas para este periodo.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Materia</th>
                            <th>Area</th>
                            <th>Asignatura</th>
                            <th>Docentes activos</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courseSubjects as $courseSubject): ?>
                            <?php $assignments = $teacherAssignments[(int) $courseSubject['mtcid']] ?? []; ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $courseSubject['mtcnombre_mostrar'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) $courseSubject['areanombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) $courseSubject['asgnombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php if ($assignments === []): ?>
                                        <span class="muted">Sin docente</span>
                                    <?php else: ?>
                                        <?php foreach ($assignments as $assignment): ?>
                                            <form method="POST" action="<?= htmlspecialchars(baseUrl('asistencia/materias/docentes/retirar'), ENT_QUOTES, 'UTF-8'); ?>" class="status-switch-form">
                                                <input type="hidden" name="mcdid" value="<?= htmlspecialchars((string) $assignment['mcdid'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <span><?= htmlspecialchars((string) $assignment['perapellidos'] . ' ' . $assignment['pernombres'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                <button class="btn-secondary btn-auto" type="submit">Retirar</button>
                                            </form>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" action="<?= htmlspecialchars(baseUrl('asistencia/materias/estado'), ENT_QUOTES, 'UTF-8'); ?>" class="status-switch-form">
                                        <input type="hidden" name="mtcid" value="<?= htmlspecialchars((string) $courseSubject['mtcid'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="mtcestado" value="<?= !empty($courseSubject['mtcestado']) ? '0' : '1'; ?>">
                                        <button
                                            class="status-switch <?= !empty($courseSubject['mtcestado']) ? 'is-active' : ''; ?>"
                                            type="submit"
                                            title="<?= !empty($courseSubject['mtcestado']) ? 'Inactivar materia' : 'Activar materia'; ?>"
                                            aria-label="<?= !empty($courseSubject['mtcestado']) ? 'Inactivar materia' : 'Activar materia'; ?>"
                                        >
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

    <section class="security-assignment-block" id="docentes">
        <header class="security-assignment-header">
            <div>
                <h3>Asignacion docente</h3>
                <p>Un docente activo puede registrar asistencia mientras este asignado a la materia.</p>
            </div>
        </header>

        <?php if (empty($courseSubjectOptions) || empty($teachers)): ?>
            <div class="empty-state">Necesitas materias activas y docentes activos para registrar asignaciones.</div>
        <?php else: ?>
            <form class="data-form" method="POST" action="<?= htmlspecialchars(baseUrl('asistencia/materias/docentes'), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-grid">
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Materia</span>
                            <select name="mtcid" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($courseSubjectOptions as $courseSubject): ?>
                                    <option value="<?= htmlspecialchars((string) $courseSubject['mtcid'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= htmlspecialchars((string) $courseSubject['mtcnombre_mostrar'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Docente</span>
                            <select name="perid" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?= htmlspecialchars((string) $teacher['perid'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= htmlspecialchars((string) $teacher['perapellidos'] . ' ' . $teacher['pernombres'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Inicio</span>
                            <input type="date" name="mcdfecha_inicio" value="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                </div>
                <div class="actions-row">
                    <button class="btn-primary btn-inline" type="submit">Asignar docente</button>
                </div>
            </form>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
