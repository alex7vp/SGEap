<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';
?>
<p class="module-note">Este modulo administra los cursos del periodo lectivo seleccionado en la sesion actual.</p>

<?php if ($currentPeriod === null): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para continuar.</div>
<?php else: ?>
    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Nuevo curso</h3>
                <p>El curso se registra directamente en el periodo actual: <strong><?= htmlspecialchars((string) $currentPeriod['pledescripcion'], ENT_QUOTES, 'UTF-8'); ?></strong>.</p>
            </div>
        </header>

        <?php if (empty($grades) || empty($parallels)): ?>
            <div class="empty-state">Para registrar cursos necesitas tener grados y paralelos disponibles en la base de datos.</div>
        <?php else: ?>
            <form class="data-form" method="POST" action="<?= htmlspecialchars(baseUrl('cursos'), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-grid">
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Periodo</span>
                            <input type="text" value="<?= htmlspecialchars((string) $currentPeriod['pledescripcion'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Estado</span>
                            <select name="curestado">
                                <option value="1" <?= ($old['curestado'] ?? '1') === '1' ? 'selected' : ''; ?>>Activo</option>
                                <option value="0" <?= ($old['curestado'] ?? '1') === '0' ? 'selected' : ''; ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Grado</span>
                            <select name="graid" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($grades as $grade): ?>
                                    <?php $gradeId = (string) $grade['graid']; ?>
                                    <option value="<?= htmlspecialchars($gradeId, ENT_QUOTES, 'UTF-8'); ?>" <?= ($old['graid'] ?? '') === $gradeId ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars((string) $grade['nednombre'] . ' | ' . $grade['granombre'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Paralelo</span>
                            <select name="prlid" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($parallels as $parallel): ?>
                                    <?php $parallelId = (string) $parallel['prlid']; ?>
                                    <option value="<?= htmlspecialchars($parallelId, ENT_QUOTES, 'UTF-8'); ?>" <?= ($old['prlid'] ?? '') === $parallelId ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars((string) $parallel['prlnombre'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="actions-row">
                    <button class="btn-secondary btn-auto btn-icon-only btn-icon-small" type="reset" title="Limpiar formulario" aria-label="Limpiar formulario" hidden>
                        <i class="fa fa-eraser" aria-hidden="true"></i>
                    </button>
                    <button class="btn-primary btn-inline" type="submit">Guardar curso</button>
                </div>
            </form>
        <?php endif; ?>
    </section>

    <section class="security-assignment-block" id="cursos-registrados">
        <header class="security-assignment-header">
            <div>
                <h3>Cursos registrados</h3>
                <p>Listado de cursos correspondientes al periodo actual en la sesion.</p>
            </div>
        </header>

        <?php if (!empty($courseListFeedback)): ?>
            <div class="catalog-feedback security-feedback-global">
                <div class="alert <?= ($courseListFeedback['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success'; ?> alert-dismissible" data-alert>
                    <span><?= htmlspecialchars((string) ($courseListFeedback['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                    <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                        <i class="fa fa-times" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($courses)): ?>
            <div class="empty-state">Todavia no hay cursos registrados para este periodo.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nivel</th>
                            <th>Grado</th>
                            <th>Paralelo</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $course['nednombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) $course['granombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) $course['prlnombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <form method="POST" action="<?= htmlspecialchars(baseUrl('cursos/estado'), ENT_QUOTES, 'UTF-8'); ?>" class="status-switch-form">
                                        <input type="hidden" name="curid" value="<?= htmlspecialchars((string) $course['curid'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="curestado" value="<?= !empty($course['curestado']) ? '0' : '1'; ?>">
                                        <button
                                            class="status-switch <?= !empty($course['curestado']) ? 'is-active' : ''; ?>"
                                            type="submit"
                                            title="<?= !empty($course['curestado']) ? 'Inactivar curso' : 'Activar curso'; ?>"
                                            aria-label="<?= !empty($course['curestado']) ? 'Inactivar curso' : 'Activar curso'; ?>"
                                        >
                                            <span class="status-switch-track">
                                                <span class="status-switch-thumb"></span>
                                            </span>
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
