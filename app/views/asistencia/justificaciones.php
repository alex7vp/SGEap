<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$today = date('Y-m-d');
?>
<p class="module-note">Registre y revise justificaciones de faltas. Al aprobar una justificacion se aplica sobre faltas existentes del rango indicado.</p>

<?php if ($currentPeriod === null): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para continuar.</div>
<?php else: ?>
    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Nueva justificacion</h3>
                <p>La solicitud puede registrarse aunque haya llegado por llamada, mensaje o documento fisico.</p>
            </div>
        </header>

        <?php if (empty($students)): ?>
            <div class="empty-state">No hay estudiantes matriculados en el periodo actual.</div>
        <?php else: ?>
            <form class="data-form" method="POST" action="<?= htmlspecialchars(baseUrl('asistencia/justificaciones'), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-grid">
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Estudiante</span>
                            <select name="estudiante" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($students as $student): ?>
                                    <?php $value = (string) $student['estid'] . '|' . (string) $student['matid']; ?>
                                    <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= htmlspecialchars((string) $student['perapellidos'] . ' ' . $student['pernombres'] . ' | ' . $student['curso'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Desde</span>
                            <input type="date" name="jafecha_inicio" value="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Hasta</span>
                            <input type="date" name="jafecha_fin" value="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Motivo</span>
                            <input type="text" name="jamotivo" maxlength="250" required>
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Observacion</span>
                            <input type="text" name="jaobservacion" maxlength="250">
                        </div>
                    </div>
                </div>
                <div class="actions-row">
                    <button class="btn-primary btn-inline" type="submit">Registrar justificacion</button>
                </div>
            </form>
        <?php endif; ?>
    </section>

    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Justificaciones registradas</h3>
                <p>Listado reciente del periodo lectivo actual.</p>
            </div>
        </header>

        <?php if (empty($justifications)): ?>
            <div class="empty-state">Todavia no hay justificaciones registradas para este periodo.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Estudiante</th>
                            <th>Fechas</th>
                            <th>Motivo</th>
                            <th>Estado</th>
                            <th>Revision</th>
                            <th>Anulacion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($justifications as $justification): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars((string) $justification['perapellidos'] . ' ' . $justification['pernombres'], ENT_QUOTES, 'UTF-8'); ?>
                                    <span class="cell-subtitle"><?= htmlspecialchars((string) $justification['percedula'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td>
                                    <?= htmlspecialchars((string) $justification['jafecha_inicio'], ENT_QUOTES, 'UTF-8'); ?>
                                    -
                                    <?= htmlspecialchars((string) $justification['jafecha_fin'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td><?= htmlspecialchars((string) $justification['jamotivo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) $justification['jaestado'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php if (($justification['jaestado'] ?? '') === 'ANULADA'): ?>
                                        <span class="muted">Anulada</span>
                                    <?php else: ?>
                                        <form
                                            method="POST"
                                            action="<?= htmlspecialchars(baseUrl('asistencia/justificaciones/revisar'), ENT_QUOTES, 'UTF-8'); ?>"
                                            class="status-switch-form"
                                            onsubmit="return confirm('Confirma que desea guardar esta revision de justificacion?');"
                                        >
                                            <input type="hidden" name="jaid" value="<?= htmlspecialchars((string) $justification['jaid'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <select name="jaestado">
                                                <option value="APROBADA" <?= ($justification['jaestado'] ?? '') === 'APROBADA' ? 'selected' : ''; ?>>Aprobar</option>
                                                <option value="RECHAZADA" <?= ($justification['jaestado'] ?? '') === 'RECHAZADA' ? 'selected' : ''; ?>>Rechazar</option>
                                            </select>
                                            <input type="text" name="jaobservacion_revision" maxlength="250" placeholder="Observacion">
                                            <button class="btn-secondary btn-auto" type="submit">Guardar</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (($justification['jaestado'] ?? '') === 'ANULADA'): ?>
                                        <span class="muted">Sin accion</span>
                                    <?php else: ?>
                                        <form
                                            method="POST"
                                            action="<?= htmlspecialchars(baseUrl('asistencia/justificaciones/anular'), ENT_QUOTES, 'UTF-8'); ?>"
                                            class="status-switch-form"
                                            onsubmit="return confirm('Confirma que desea anular esta justificacion? Las faltas asociadas volveran a injustificadas.');"
                                        >
                                            <input type="hidden" name="jaid" value="<?= htmlspecialchars((string) $justification['jaid'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="text" name="jamotivo_anulacion" maxlength="250" placeholder="Motivo" required>
                                            <button class="btn-secondary btn-auto" type="submit">Anular</button>
                                        </form>
                                    <?php endif; ?>
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
