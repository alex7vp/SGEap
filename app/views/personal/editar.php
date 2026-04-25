<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';
?>
<div class="toolbar">
    <p>Actualiza los datos operativos del personal institucional seleccionado.</p>
    <a class="text-link" href="<?= htmlspecialchars(baseUrl('personal/consulta'), ENT_QUOTES, 'UTF-8'); ?>">Volver a consulta de personal</a>
</div>

<p class="module-note">
    Persona vinculada:
    <strong><?= htmlspecialchars((string) (($staff['perapellidos'] ?? '') . ' ' . ($staff['pernombres'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></strong>
    | Cédula:
    <strong><?= htmlspecialchars((string) ($staff['percedula'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
</p>

<form class="data-form" method="POST" action="<?= htmlspecialchars(baseUrl('personal/actualizar'), ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="psnid" value="<?= htmlspecialchars((string) ($old['psnid'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="perid" value="<?= htmlspecialchars((string) ($old['perid'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

    <div class="form-grid">
        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Cedula</span>
                <input
                    id="percedula"
                    name="percedula"
                    maxlength="10"
                    required
                    value="<?= htmlspecialchars((string) ($old['percedula'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                >
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Sexo</span>
                <select id="persexo" name="persexo">
                    <option value="">Seleccione una opcion</option>
                    <option value="Masculino" <?= ($old['persexo'] ?? '') === 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                    <option value="Femenino" <?= ($old['persexo'] ?? '') === 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Nombres</span>
                <input
                    id="pernombres"
                    name="pernombres"
                    required
                    value="<?= htmlspecialchars((string) ($old['pernombres'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                >
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Apellidos</span>
                <input
                    id="perapellidos"
                    name="perapellidos"
                    required
                    value="<?= htmlspecialchars((string) ($old['perapellidos'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                >
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Celular</span>
                <input
                    id="pertelefono1"
                    name="pertelefono1"
                    value="<?= htmlspecialchars((string) ($old['pertelefono1'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                >
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Fijo</span>
                <input
                    id="pertelefono2"
                    name="pertelefono2"
                    value="<?= htmlspecialchars((string) ($old['pertelefono2'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                >
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">E-mail</span>
                <input
                    id="percorreo"
                    name="percorreo"
                    type="email"
                    value="<?= htmlspecialchars((string) ($old['percorreo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                >
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Fecha de Nacimiento</span>
                <input
                    id="perfechanacimiento"
                    name="perfechanacimiento"
                    type="date"
                    value="<?= htmlspecialchars((string) ($old['perfechanacimiento'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                >
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Estado civil</span>
                <select id="eciid" name="eciid">
                    <option value="">Seleccione una opcion</option>
                    <?php foreach (($civilStatuses ?? []) as $civilStatus): ?>
                        <option value="<?= htmlspecialchars((string) $civilStatus['eciid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (string) ($old['eciid'] ?? '') === (string) $civilStatus['eciid'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars((string) $civilStatus['ecinombre'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Instruccion</span>
                <select id="istid" name="istid">
                    <option value="">Seleccione una opcion</option>
                    <?php foreach (($instructionLevels ?? []) as $instructionLevel): ?>
                        <option value="<?= htmlspecialchars((string) $instructionLevel['istid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (string) ($old['istid'] ?? '') === (string) $instructionLevel['istid'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars((string) $instructionLevel['istnombre'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Profesion</span>
                <input
                    id="perprofesion"
                    name="perprofesion"
                    value="<?= htmlspecialchars((string) ($old['perprofesion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                >
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Ocupacion</span>
                <input
                    id="perocupacion"
                    name="perocupacion"
                    value="<?= htmlspecialchars((string) ($old['perocupacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                >
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Contratacion</span>
                <input
                    id="psnfechacontratacion"
                    name="psnfechacontratacion"
                    type="date"
                    required
                    value="<?= htmlspecialchars((string) ($old['psnfechacontratacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                >
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Salida</span>
                <input
                    id="psnfechasalida"
                    name="psnfechasalida"
                    type="date"
                    value="<?= htmlspecialchars((string) ($old['psnfechasalida'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                >
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Estado</span>
                <select id="psnestado" name="psnestado">
                    <option value="1" <?= ($old['psnestado'] ?? '1') === '1' ? 'selected' : ''; ?>>Activo</option>
                    <option value="0" <?= ($old['psnestado'] ?? '1') === '0' ? 'selected' : ''; ?>>Inactivo</option>
                </select>
            </div>
        </div>

        <div class="form-group form-group-full">
            <div class="input-group">
                <span class="input-addon">Observacion</span>
                <input
                    id="psnobservacion"
                    name="psnobservacion"
                    placeholder="Ingrese una observacion"
                    value="<?= htmlspecialchars((string) ($old['psnobservacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                >
            </div>
        </div>

        <div class="form-group form-group-full">
            <label class="switch-card">
                <span>Habla ingles</span>
                <input type="checkbox" name="perhablaingles" value="1" <?= !empty($old['perhablaingles']) ? 'checked' : ''; ?>>
            </label>
        </div>
    </div>

    <div class="actions-row">
        <button class="btn-secondary btn-auto btn-icon-only btn-icon-small" type="reset" title="Limpiar formulario" aria-label="Limpiar formulario" hidden>
            <i class="fa fa-eraser" aria-hidden="true"></i>
        </button>
        <button class="btn-primary btn-auto" type="submit">Actualizar personal</button>
    </div>
</form>
<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
