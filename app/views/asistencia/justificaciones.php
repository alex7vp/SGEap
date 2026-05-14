<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$today = date('Y-m-d');
$courses = is_array($courses ?? null) ? $courses : [];
$students = is_array($students ?? null) ? $students : [];
$unjustifiedAbsences = is_array($unjustifiedAbsences ?? null) ? $unjustifiedAbsences : [];
$selectedCourseId = (int) ($selectedCourseId ?? 0);
$selectedStudentId = (int) ($selectedStudentId ?? 0);
$justifications = is_array($justifications ?? null) ? $justifications : [];
?>
<p class="module-note">Registre justificaciones para faltas ya registradas o para rangos anticipados. Al aprobar una justificacion se aplica sobre faltas existentes y futuras dentro del rango.</p>

<?php if ($currentPeriod === null): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para continuar.</div>
<?php else: ?>
    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Filtros</h3>
                <p>Seleccione un curso para consultar faltas injustificadas y registrar la justificacion.</p>
            </div>
        </header>

        <form class="data-form" method="GET" action="<?= htmlspecialchars(baseUrl('asistencia/justificaciones'), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-grid">
                <div>
                    <div class="input-group">
                        <span class="input-addon">Curso</span>
                        <select name="curid" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= htmlspecialchars((string) $course['curid'], ENT_QUOTES, 'UTF-8'); ?>" <?= $selectedCourseId === (int) $course['curid'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars((string) $course['granombre'] . ' ' . $course['prlnombre'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Estudiante</span>
                        <select name="estid" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= htmlspecialchars((string) $student['estid'], ENT_QUOTES, 'UTF-8'); ?>" <?= $selectedStudentId === (int) $student['estid'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars((string) $student['perapellidos'] . ' ' . $student['pernombres'] . ' | ' . $student['curso'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="actions-row">
                <button class="btn-primary btn-inline" type="submit">Consultar</button>
                <a class="btn-secondary btn-inline" href="<?= htmlspecialchars(baseUrl('asistencia/justificaciones'), ENT_QUOTES, 'UTF-8'); ?>">Limpiar</a>
            </div>
        </form>
    </section>

    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Justificar faltas registradas</h3>
                <p>Seleccione faltas injustificadas del mismo estudiante. La justificacion cubrira desde la primera hasta la ultima fecha seleccionada.</p>
            </div>
        </header>

        <?php if ($unjustifiedAbsences === []): ?>
            <div class="empty-state">No hay faltas injustificadas para los filtros seleccionados.</div>
        <?php else: ?>
            <form class="data-form" method="POST" action="<?= htmlspecialchars(baseUrl('asistencia/justificaciones'), ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data" data-registered-justification-form>
                <input type="hidden" name="modo_justificacion" value="posterior">
                <input type="hidden" name="curid" value="<?= htmlspecialchars((string) $selectedCourseId, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Sel.</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Estudiante</th>
                                <th>Materia</th>
                                <th>Curso</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unjustifiedAbsences as $absence): ?>
                                <tr>
                                    <td>
                                        <input
                                            type="checkbox"
                                            name="faltas[]"
                                            value="<?= htmlspecialchars((string) $absence['aesid'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-absence-student="<?= htmlspecialchars((string) $absence['estid'], ENT_QUOTES, 'UTF-8'); ?>"
                                        >
                                    </td>
                                    <td><?= htmlspecialchars((string) $absence['cafecha'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) $absence['sclnumero_hora'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?= htmlspecialchars((string) $absence['perapellidos'] . ' ' . $absence['pernombres'], ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td><?= htmlspecialchars((string) $absence['mtcnombre_mostrar'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) $absence['curso'], ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="form-grid">
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
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Documento</span>
                            <div class="file-input-shell">
                                <label class="file-input-button" for="registered-justification-file">Elegir archivo</label>
                                <span class="file-input-name" data-file-input-name>No se eligio ningun archivo</span>
                                <input id="registered-justification-file" class="file-input-native" type="file" name="jaarchivo" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" data-file-input>
                            </div>
                        </div>
                        <span class="cell-subtitle">Opcional. PDF, JPG o PNG. Maximo 2 MB.</span>
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
                <h3>Justificacion anticipada</h3>
                <p>Registre una justificacion para un rango futuro o aun no registrado.</p>
            </div>
        </header>

        <?php if (empty($students)): ?>
            <div class="empty-state">No hay estudiantes matriculados en el periodo actual.</div>
        <?php else: ?>
            <form class="data-form" method="POST" action="<?= htmlspecialchars(baseUrl('asistencia/justificaciones'), ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data">
                <input type="hidden" name="modo_justificacion" value="anticipada">
                <input type="hidden" name="curid" value="<?= htmlspecialchars((string) $selectedCourseId, ENT_QUOTES, 'UTF-8'); ?>">
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
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Documento</span>
                            <div class="file-input-shell">
                                <label class="file-input-button" for="advance-justification-file">Elegir archivo</label>
                                <span class="file-input-name" data-file-input-name>No se eligio ningun archivo</span>
                                <input id="advance-justification-file" class="file-input-native" type="file" name="jaarchivo" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" data-file-input>
                            </div>
                        </div>
                        <span class="cell-subtitle">Opcional. PDF, JPG o PNG. Maximo 2 MB.</span>
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
                            <th>Documento</th>
                            <th>Estado</th>
                            <th>Anulacion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($justifications as $justification): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars((string) $justification['perapellidos'] . ' ' . $justification['pernombres'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars((string) $justification['jafecha_inicio'], ENT_QUOTES, 'UTF-8'); ?>
                                    -
                                    <?= htmlspecialchars((string) $justification['jafecha_fin'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td><?= htmlspecialchars((string) $justification['jamotivo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php if (!empty($justification['jaarchivo'])): ?>
                                        <a href="<?= htmlspecialchars(asset((string) $justification['jaarchivo']), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Ver archivo</a>
                                    <?php else: ?>
                                        <span class="muted">Sin archivo</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars((string) $justification['jaestado'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php $isConfirmed = ($justification['jaobservacion_revision'] ?? '') === 'CONFIRMADA'; ?>
                                    <?php if (($justification['jaestado'] ?? '') === 'ANULADA' || $isConfirmed): ?>
                                        <span class="muted">Sin accion</span>
                                    <?php else: ?>
                                        <div class="justification-action-group">
                                            <div class="justification-annul-group">
                                                <form
                                                    method="POST"
                                                    action="<?= htmlspecialchars(baseUrl('asistencia/justificaciones/anular'), ENT_QUOTES, 'UTF-8'); ?>"
                                                    class="justification-annul-form"
                                                    data-justification-annul-form
                                                >
                                                    <input type="hidden" name="jaid" value="<?= htmlspecialchars((string) $justification['jaid'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="text" name="jamotivo_anulacion" maxlength="250" placeholder="Motivo" required>
                                                    <button class="btn-secondary btn-auto" type="submit">Anular</button>
                                                </form>
                                            </div>
                                            <form method="POST" action="<?= htmlspecialchars(baseUrl('asistencia/justificaciones/confirmar'), ENT_QUOTES, 'UTF-8'); ?>" data-justification-confirm-form>
                                                <input type="hidden" name="jaid" value="<?= htmlspecialchars((string) $justification['jaid'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <button class="btn-primary btn-auto" type="submit">Confirmar</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
    <dialog class="calendar-dialog attendance-save-dialog" id="justification-annul-dialog">
        <header class="security-assignment-header">
            <div>
                <h3>Anular justificacion</h3>
                <p>Esta seguro de anular esta justificacion? Las faltas asociadas volveran a injustificadas.</p>
            </div>
        </header>
        <div class="actions-row">
            <button class="btn-secondary btn-inline" type="button" data-justification-annul-cancel>Cancelar</button>
            <button class="btn-primary btn-inline" type="button" data-justification-annul-confirm>Anular justificacion</button>
        </div>
    </dialog>
    <dialog class="calendar-dialog attendance-save-dialog" id="justification-confirm-dialog">
        <header class="security-assignment-header">
            <div>
                <h3>Confirmar justificacion</h3>
                <p>Esta seguro de confirmar esta justificacion? Luego quedara sin acciones disponibles.</p>
            </div>
        </header>
        <div class="actions-row">
            <button class="btn-secondary btn-inline" type="button" data-justification-confirm-cancel>Cancelar</button>
            <button class="btn-primary btn-inline" type="button" data-justification-confirm-accept>Confirmar justificacion</button>
        </div>
    </dialog>
<?php endif; ?>

<script>
    (function () {
        var form = document.querySelector('[data-registered-justification-form]');

        if (!form) {
            return;
        }

        var checkboxes = Array.prototype.slice.call(form.querySelectorAll('[data-absence-student]'));

        function refreshDisabledRows() {
            var selected = checkboxes.filter(function (checkbox) {
                return checkbox.checked;
            });
            var selectedStudent = selected.length > 0 ? selected[0].getAttribute('data-absence-student') : '';

            checkboxes.forEach(function (checkbox) {
                checkbox.disabled = selectedStudent !== ''
                    && !checkbox.checked
                    && checkbox.getAttribute('data-absence-student') !== selectedStudent;
            });
        }

        checkboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', refreshDisabledRows);
        });

        form.addEventListener('submit', function (event) {
            var selectedCount = checkboxes.filter(function (checkbox) {
                return checkbox.checked;
            }).length;

            if (selectedCount === 0) {
                event.preventDefault();
                window.alert('Seleccione al menos una falta injustificada.');
            }
        });
    }());

    (function () {
        var dialog = document.getElementById('justification-annul-dialog');
        var forms = Array.prototype.slice.call(document.querySelectorAll('[data-justification-annul-form]'));
        var cancelButton = dialog ? dialog.querySelector('[data-justification-annul-cancel]') : null;
        var confirmButton = dialog ? dialog.querySelector('[data-justification-annul-confirm]') : null;
        var pendingForm = null;

        if (!dialog || forms.length === 0) {
            return;
        }

        forms.forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                pendingForm = form;

                if (typeof dialog.showModal === 'function') {
                    dialog.showModal();
                }
            });
        });

        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                pendingForm = null;

                if (typeof dialog.close === 'function') {
                    dialog.close('cancel');
                }
            });
        }

        if (confirmButton) {
            confirmButton.addEventListener('click', function () {
                if (!pendingForm) {
                    return;
                }

                pendingForm.submit();
            });
        }
    }());

    (function () {
        var dialog = document.getElementById('justification-confirm-dialog');
        var forms = Array.prototype.slice.call(document.querySelectorAll('[data-justification-confirm-form]'));
        var cancelButton = dialog ? dialog.querySelector('[data-justification-confirm-cancel]') : null;
        var confirmButton = dialog ? dialog.querySelector('[data-justification-confirm-accept]') : null;
        var pendingForm = null;

        if (!dialog || forms.length === 0) {
            return;
        }

        forms.forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                pendingForm = form;

                if (typeof dialog.showModal === 'function') {
                    dialog.showModal();
                }
            });
        });

        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                pendingForm = null;

                if (typeof dialog.close === 'function') {
                    dialog.close('cancel');
                }
            });
        }

        if (confirmButton) {
            confirmButton.addEventListener('click', function () {
                if (!pendingForm) {
                    return;
                }

                pendingForm.submit();
            });
        }
    }());
</script>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
