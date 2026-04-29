<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';
?>
<div class="toolbar">
    <p>Actualiza los datos registrados del estudiante y su curso de matricula.</p>
    <a class="text-link" href="<?= htmlspecialchars(baseUrl('estudiantes'), ENT_QUOTES, 'UTF-8'); ?>">Volver al listado</a>
</div>

<p class="module-note">Periodo visualizado: <?= htmlspecialchars((string) ($currentPeriod['pledescripcion'] ?? 'Sin periodo activo'), ENT_QUOTES, 'UTF-8'); ?>.</p>

<form class="data-form matricula-form" method="POST" action="<?= htmlspecialchars(baseUrl('estudiantes/actualizar'), ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="estid" value="<?= htmlspecialchars((string) $student['estid'], ENT_QUOTES, 'UTF-8'); ?>">

    <section class="wizard-panel">
        <div class="form-grid">
            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Cedula</span>
                    <input name="percedula" maxlength="10" inputmode="numeric" required value="<?= htmlspecialchars((string) ($student['percedula'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Sexo</span>
                    <select name="persexo">
                        <option value="">Seleccione</option>
                        <option value="Masculino" <?= ($student['persexo'] ?? '') === 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                        <option value="Femenino" <?= ($student['persexo'] ?? '') === 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Nombres</span>
                    <input name="pernombres" required value="<?= htmlspecialchars((string) ($student['pernombres'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Apellidos</span>
                    <input name="perapellidos" required value="<?= htmlspecialchars((string) ($student['perapellidos'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Nacimiento</span>
                    <input name="perfechanacimiento" type="date" value="<?= htmlspecialchars((string) ($student['perfechanacimiento'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Celular</span>
                    <input name="pertelefono1" value="<?= htmlspecialchars((string) ($student['pertelefono1'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Fijo</span>
                    <input name="pertelefono2" value="<?= htmlspecialchars((string) ($student['pertelefono2'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">E-mail</span>
                    <input name="percorreo" type="email" value="<?= htmlspecialchars((string) ($student['percorreo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Profesion</span>
                    <input name="perprofesion" value="<?= htmlspecialchars((string) ($student['perprofesion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Ocupacion</span>
                    <input name="perocupacion" value="<?= htmlspecialchars((string) ($student['perocupacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Origen</span>
                    <input name="estlugarnacimiento" value="<?= htmlspecialchars((string) ($student['estlugarnacimiento'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Parroquia</span>
                    <input name="estparroquia" value="<?= htmlspecialchars((string) ($student['estparroquia'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="form-group form-group-full">
                <div class="input-group">
                    <span class="input-addon">Direccion</span>
                    <input name="estdireccion" value="<?= htmlspecialchars((string) ($student['estdireccion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Estado</span>
                    <select name="estestado">
                        <option value="1" <?= !empty($student['estestado']) ? 'selected' : ''; ?>>Activo</option>
                        <option value="0" <?= empty($student['estestado']) ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
            </div>

            <div class="form-group form-group-full">
                <div class="input-group">
                    <span class="input-addon">Curso</span>
                    <?php if (!empty($student['matid'])): ?>
                        <select name="curid">
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= htmlspecialchars((string) $course['curid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($student['curid'] ?? 0) === (int) $course['curid'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars((string) ($course['granombre'] . ' ' . $course['prlnombre']), ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input value="Sin matricula en el periodo actual" disabled>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="actions-row">
            <a class="btn-secondary btn-auto" href="<?= htmlspecialchars(baseUrl('estudiantes'), ENT_QUOTES, 'UTF-8'); ?>">Cancelar</a>
            <button class="btn-primary btn-inline" type="submit">Actualizar estudiante</button>
        </div>
    </section>
</form>
<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
