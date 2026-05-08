<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$hours = range(1, 7);
$session = is_array($session ?? null) ? $session : false;
$attendance = is_array($attendance ?? null) ? $attendance : [];
$isClosed = $session !== false && ($session['sclestado'] ?? '') === 'CERRADA';
?>
<p class="module-note">El docente registra asistencia por materia asignada y hora de clase. Primero seleccione la fecha; si el dia esta suspendido o fuera de horario, el sistema no permitira abrir la lista.</p>

<?php if ($currentPeriod === null): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para continuar.</div>
<?php else: ?>
    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Nueva sesion de asistencia</h3>
                <p>Seleccione una materia asignada y la hora correspondiente.</p>
            </div>
        </header>

        <?php if (empty($teacherSubjects)): ?>
            <div class="empty-state">No tienes materias activas asignadas para la fecha seleccionada. Revise que exista asignacion docente vigente y que el calendario del dia este habilitado.</div>
        <?php endif; ?>

        <form class="data-form" method="GET" action="<?= htmlspecialchars(baseUrl('asistencia/registro'), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-grid">
                <div>
                    <div class="input-group">
                        <span class="input-addon">Fecha</span>
                        <input type="date" name="fecha" value="<?= htmlspecialchars((string) $selectedDate, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
            </div>
            <div class="actions-row">
                <button class="btn-secondary btn-inline" type="submit">Cambiar fecha</button>
            </div>
        </form>

        <?php if (!empty($teacherSubjects)): ?>
            <form class="data-form" method="POST" action="<?= htmlspecialchars(baseUrl('asistencia/sesiones'), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-grid">
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Materia</span>
                            <select name="asignacion" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($teacherSubjects as $subject): ?>
                                    <?php $value = (string) $subject['mcdid'] . '|' . (string) $subject['mtcid']; ?>
                                    <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= htmlspecialchars((string) $subject['mtcnombre_mostrar'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Hora</span>
                            <select name="sclnumero_hora" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($hours as $hour): ?>
                                    <option value="<?= $hour; ?>" <?= (string) $selectedHour === (string) $hour ? 'selected' : ''; ?>>
                                        <?= $hour; ?> hora
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Fecha</span>
                            <input type="date" name="cafecha" value="<?= htmlspecialchars((string) $selectedDate, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                        </div>
                    </div>
                </div>
                <div class="actions-row">
                    <button class="btn-primary btn-inline" type="submit">Abrir lista</button>
                </div>
            </form>
        <?php endif; ?>
    </section>

    <?php if ($session !== false): ?>
        <section class="security-assignment-block" id="registro">
            <header class="security-assignment-header">
                <div>
                    <h3><?= htmlspecialchars((string) $session['mtcnombre_mostrar'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p>
                        <?= htmlspecialchars((string) $session['cafecha'], ENT_QUOTES, 'UTF-8'); ?>
                        | <?= htmlspecialchars((string) $session['sclnumero_hora'], ENT_QUOTES, 'UTF-8'); ?> hora
                        | <?= htmlspecialchars((string) $session['sclestado'], ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                </div>
            </header>

            <?php if (empty($students)): ?>
                <div class="empty-state">No hay estudiantes activos para registrar en esta sesion. Revise matriculas activas del curso en la fecha de la clase.</div>
            <?php else: ?>
                <form method="POST" action="<?= htmlspecialchars(baseUrl('asistencia/registros'), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="sclid" value="<?= htmlspecialchars((string) $session['sclid'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Cedula</th>
                                    <th>Estado</th>
                                    <th>Observacion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <?php
                                    $studentId = (int) $student['estid'];
                                    $saved = $attendance[$studentId] ?? [];
                                    $status = (string) ($saved['aesestado'] ?? 'ASISTENCIA');
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) $student['perapellidos'] . ' ' . $student['pernombres'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars((string) $student['percedula'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <select name="estado[<?= $studentId; ?>]" <?= $isClosed ? 'disabled' : ''; ?>>
                                                <option value="ASISTENCIA" <?= $status === 'ASISTENCIA' ? 'selected' : ''; ?>>Asistencia</option>
                                                <option value="ATRASO" <?= $status === 'ATRASO' ? 'selected' : ''; ?>>Atraso</option>
                                                <option value="FALTA_INJUSTIFICADA" <?= $status === 'FALTA_INJUSTIFICADA' ? 'selected' : ''; ?>>Falta</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input
                                                type="text"
                                                name="observacion[<?= $studentId; ?>]"
                                                maxlength="250"
                                                value="<?= htmlspecialchars((string) ($saved['aesobservacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                <?= $isClosed ? 'disabled' : ''; ?>
                                            >
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (!$isClosed): ?>
                        <div class="actions-row">
                            <button class="btn-primary btn-inline" type="submit">Guardar asistencia</button>
                        </div>
                    <?php endif; ?>
                </form>
                <?php if (!$isClosed && !empty($attendance)): ?>
                    <form
                        method="POST"
                        action="<?= htmlspecialchars(baseUrl('asistencia/sesiones/cerrar'), ENT_QUOTES, 'UTF-8'); ?>"
                        class="data-form"
                        onsubmit="return confirm('Confirma que desea cerrar esta sesion? Luego el docente ya no podra modificarla.');"
                    >
                        <input type="hidden" name="sclid" value="<?= htmlspecialchars((string) $session['sclid'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="actions-row">
                            <button class="btn-secondary btn-inline" type="submit">Cerrar sesion</button>
                        </div>
                    </form>
                <?php elseif ($isClosed): ?>
                    <div class="empty-state">La sesion esta cerrada. Para corregirla, debe intervenir un usuario con permiso de supervision.</div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    <?php elseif ((int) ($_GET['sclid'] ?? 0) > 0): ?>
        <div class="empty-state">La sesion solicitada no existe o no pertenece a sus asignaciones activas.</div>
    <?php endif; ?>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
