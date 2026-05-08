<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$session = is_array($session ?? null) ? $session : false;
$selectedCourseId = (int) ($selectedCourseId ?? 0);
$statusLabels = [
    'REGISTRADA' => 'Registrada',
    'CERRADA' => 'Cerrada',
    'ANULADA' => 'Anulada',
    'ASISTENCIA' => 'Asistencia',
    'ATRASO' => 'Atraso',
    'FALTA_JUSTIFICADA' => 'Falta justificada',
    'FALTA_INJUSTIFICADA' => 'Falta injustificada',
];
?>
<p class="module-note">Supervisa sesiones registradas por docentes y anula sesiones con motivo cuando corresponda.</p>

<?php if ($currentPeriod === null): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para continuar.</div>
<?php else: ?>
    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Filtro de sesiones</h3>
                <p>Consulta la asistencia registrada por fecha y curso.</p>
            </div>
        </header>

        <form class="data-form" method="GET" action="<?= htmlspecialchars(baseUrl('asistencia/supervision'), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-grid">
                <div>
                    <div class="input-group">
                        <span class="input-addon">Fecha</span>
                        <input type="date" name="fecha" value="<?= htmlspecialchars((string) $selectedDate, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Curso</span>
                        <select name="curid">
                            <option value="0">Todos</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= htmlspecialchars((string) $course['curid'], ENT_QUOTES, 'UTF-8'); ?>" <?= $selectedCourseId === (int) $course['curid'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars((string) $course['granombre'] . ' ' . $course['prlnombre'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="actions-row">
                <button class="btn-primary btn-inline" type="submit">Consultar</button>
            </div>
        </form>
    </section>

    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Sesiones registradas</h3>
                <p>Las sesiones anuladas se conservan para auditoria, pero no cuentan en los resumenes diarios.</p>
            </div>
        </header>

        <?php if (empty($sessions)): ?>
            <div class="empty-state">No hay sesiones registradas para el filtro seleccionado.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Curso</th>
                            <th>Materia</th>
                            <th>Docente</th>
                            <th>Hora</th>
                            <th>Estado</th>
                            <th>Resumen</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $row['cafecha'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) $row['granombre'] . ' ' . $row['prlnombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) $row['mtcnombre_mostrar'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) $row['docente_apellidos'] . ' ' . $row['docente_nombres'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) $row['sclnumero_hora'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($statusLabels[(string) $row['sclestado']] ?? (string) $row['sclestado'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    A: <?= (int) $row['total_asistencias']; ?> |
                                    T: <?= (int) $row['total_atrasos']; ?> |
                                    FJ: <?= (int) $row['total_faltas_justificadas']; ?> |
                                    FI: <?= (int) $row['total_faltas_injustificadas']; ?>
                                </td>
                                <td>
                                    <a class="btn-secondary btn-inline" href="<?= htmlspecialchars(baseUrl('asistencia/supervision?fecha=' . (string) $row['cafecha'] . '&curid=' . (string) $row['curid'] . '&sclid=' . (string) $row['sclid'] . '#detalle'), ENT_QUOTES, 'UTF-8'); ?>">
                                        Ver
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($session !== false): ?>
        <section class="security-assignment-block" id="detalle">
            <header class="security-assignment-header">
                <div>
                    <h3><?= htmlspecialchars((string) $session['mtcnombre_mostrar'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p>
                        <?= htmlspecialchars((string) $session['cafecha'], ENT_QUOTES, 'UTF-8'); ?>
                        | <?= htmlspecialchars((string) $session['granombre'] . ' ' . $session['prlnombre'], ENT_QUOTES, 'UTF-8'); ?>
                        | <?= htmlspecialchars((string) $session['sclnumero_hora'], ENT_QUOTES, 'UTF-8'); ?> hora
                        | <?= htmlspecialchars($statusLabels[(string) $session['sclestado']] ?? (string) $session['sclestado'], ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                </div>
            </header>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Estudiante</th>
                            <th>Cedula</th>
                            <th>Estado</th>
                            <th>Observacion</th>
                            <th>Justificacion</th>
                            <th>Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendanceDetail as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $row['perapellidos'] . ' ' . $row['pernombres'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) $row['percedula'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($statusLabels[(string) $row['aesestado']] ?? (string) $row['aesestado'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) ($row['aesobservacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) ($row['justificacion_motivo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?= htmlspecialchars((string) $row['usuario_registro'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if (!empty($row['usuario_modificacion'])): ?>
                                        / <?= htmlspecialchars((string) $row['usuario_modificacion'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (($session['sclestado'] ?? '') !== 'ANULADA'): ?>
                <form
                    class="data-form"
                    method="POST"
                    action="<?= htmlspecialchars(baseUrl('asistencia/sesiones/anular'), ENT_QUOTES, 'UTF-8'); ?>"
                    onsubmit="return confirm('Confirma que desea anular esta sesion? La sesion dejara de contar en resumenes y reportes.');"
                >
                    <input type="hidden" name="sclid" value="<?= htmlspecialchars((string) $session['sclid'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="fecha" value="<?= htmlspecialchars((string) $session['cafecha'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="form-grid">
                        <div>
                            <div class="input-group">
                                <span class="input-addon">Motivo anulacion</span>
                                <input type="text" name="sclmotivo_anulacion" maxlength="250" required>
                            </div>
                        </div>
                    </div>
                    <div class="actions-row">
                        <button class="btn-secondary btn-inline" type="submit">Anular sesion</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="empty-state">
                    Sesion anulada por <?= htmlspecialchars((string) ($session['usuario_anulacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>.
                    Motivo: <?= htmlspecialchars((string) ($session['sclmotivo_anulacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
        </section>
    <?php elseif ((int) ($_GET['sclid'] ?? 0) > 0): ?>
        <div class="empty-state">La sesion seleccionada no pertenece al periodo actual.</div>
    <?php endif; ?>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
