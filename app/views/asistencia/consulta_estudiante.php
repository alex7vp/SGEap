<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$availableStudents = is_array($availableStudents ?? null) ? $availableStudents : [];
$summaryDays = is_array($summaryDays ?? null) ? $summaryDays : [];
$attendanceDetail = is_array($attendanceDetail ?? null) ? $attendanceDetail : [];
$selectedStudentId = (int) ($selectedStudentId ?? 0);
$selectedDate = (string) ($selectedDate ?? '');
$selectedMonth = (string) ($selectedMonth ?? date('Y-m'));
$previousMonth = date('Y-m', strtotime($selectedMonth . '-01 -1 month'));
$nextMonth = date('Y-m', strtotime($selectedMonth . '-01 +1 month'));
$statusLabels = [
    'OK' => 'OK',
    'ALERTA' => 'Alerta',
    'ASISTENCIA' => 'Asistencia',
    'ATRASO' => 'Atraso',
    'FALTA_JUSTIFICADA' => 'Falta justificada',
    'FALTA_INJUSTIFICADA' => 'Falta injustificada',
];
$basePath = (string) (($currentSection ?? '') === 'asistencia_representante'
    ? 'asistencia/representante'
    : 'asistencia/mi-asistencia');
$studentQuery = $selectedStudentId > 0 && $availableStudents !== [] ? '&estid=' . $selectedStudentId : '';
?>
<p class="module-note">La vista diaria muestra solo el resumen. El detalle por hora y materia aparece al abrir una fecha.</p>

<?php if ($currentPeriod === null): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para continuar.</div>
<?php elseif ($selectedStudentId <= 0): ?>
    <div class="empty-state">No existen estudiantes disponibles para consultar asistencia.</div>
<?php else: ?>
    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Resumen diario</h3>
                <p>Consulta por mes; los dias sin registro no se muestran como novedad.</p>
            </div>
        </header>

        <form class="data-form" method="GET" action="<?= htmlspecialchars(baseUrl($basePath), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-grid">
                <?php if ($availableStudents !== []): ?>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Estudiante</span>
                            <select name="estid" required>
                                <?php foreach ($availableStudents as $student): ?>
                                    <option value="<?= htmlspecialchars((string) $student['estid'], ENT_QUOTES, 'UTF-8'); ?>" <?= $selectedStudentId === (int) $student['estid'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars((string) $student['perapellidos'] . ' ' . $student['pernombres'] . ' - ' . ($student['curso'] ?? 'Sin curso'), ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Mes</span>
                        <input type="month" name="mes" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
            </div>
            <div class="actions-row">
                <button class="btn-primary btn-inline" type="submit">Consultar</button>
                <a class="btn-secondary btn-inline" href="<?= htmlspecialchars(baseUrl($basePath . '?mes=' . $previousMonth . $studentQuery), ENT_QUOTES, 'UTF-8'); ?>">Anterior</a>
                <a class="btn-secondary btn-inline" href="<?= htmlspecialchars(baseUrl($basePath . '?mes=' . $nextMonth . $studentQuery), ENT_QUOTES, 'UTF-8'); ?>">Siguiente</a>
            </div>
        </form>
    </section>

    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Mes <?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8'); ?></h3>
                <p>OK indica que no hay atrasos ni faltas registradas ese dia.</p>
            </div>
        </header>

        <?php if ($summaryDays === []): ?>
            <div class="empty-state">No existen registros de asistencia para el mes seleccionado.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Resumen</th>
                            <th>Asistencias</th>
                            <th>Atrasos</th>
                            <th>F. justificadas</th>
                            <th>F. injustificadas</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($summaryDays as $day): ?>
                            <?php
                            $date = (string) $day['cafecha'];
                            $detailUrl = baseUrl($basePath . '?mes=' . $selectedMonth . $studentQuery . '&fecha=' . $date . '#detalle');
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($statusLabels[(string) $day['resumen_estado']] ?? (string) $day['resumen_estado'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= (int) $day['total_asistencias']; ?></td>
                                <td><?= (int) $day['total_atrasos']; ?></td>
                                <td><?= (int) $day['total_faltas_justificadas']; ?></td>
                                <td><?= (int) $day['total_faltas_injustificadas']; ?></td>
                                <td><a class="btn-secondary btn-inline" href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8'); ?>">Ver</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($selectedDate !== ''): ?>
        <section class="security-assignment-block" id="detalle">
            <header class="security-assignment-header">
                <div>
                    <h3>Detalle del <?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p>Registro por hora, materia y docente.</p>
                </div>
            </header>

            <?php if ($attendanceDetail === []): ?>
                <div class="empty-state">No hay detalle registrado para la fecha seleccionada.</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Materia</th>
                                <th>Docente</th>
                                <th>Estado</th>
                                <th>Observacion</th>
                                <th>Justificacion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendanceDetail as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) $row['sclnumero_hora'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) $row['mtcnombre_mostrar'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) $row['docente_apellidos'] . ' ' . $row['docente_nombres'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($statusLabels[(string) $row['aesestado']] ?? (string) $row['aesestado'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) ($row['aesobservacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) ($row['justificacion_motivo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
