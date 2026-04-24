<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';
?>
<div class="toolbar">
    <p>Registro base de persona para usuarios, estudiantes y representantes.</p>
    <a class="text-link" href="<?= htmlspecialchars(baseUrl('personas'), ENT_QUOTES, 'UTF-8'); ?>">Volver al listado</a>
</div>

<p class="module-note">Este formato queda como base visual para formularios de registro en los siguientes modulos.</p>

<form class="data-form" method="POST" action="<?= htmlspecialchars((string) ($formAction ?? baseUrl('personas')), ENT_QUOTES, 'UTF-8'); ?>">
    <?php if (!empty($old['perid'])): ?>
        <input type="hidden" name="perid" value="<?= htmlspecialchars((string) $old['perid'], ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>

    <div class="form-grid">
        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Cédula</span>
                <input id="percedula" name="percedula" maxlength="10" placeholder="Ingrese la cedula" required value="<?= htmlspecialchars((string) ($old['percedula'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
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
                <span class="input-addon">Nacimiento</span>
                <input id="perfechanacimiento" name="perfechanacimiento" type="date" value="<?= htmlspecialchars((string) ($old['perfechanacimiento'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Nombres</span>
                <input id="pernombres" name="pernombres" placeholder="Ingrese los nombres" required value="<?= htmlspecialchars((string) ($old['pernombres'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Apellidos</span>
                <input id="perapellidos" name="perapellidos" placeholder="Ingrese los apellidos" required value="<?= htmlspecialchars((string) ($old['perapellidos'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Celular</span>
                <input id="pertelefono1" name="pertelefono1" placeholder="Ingrese telefono celular" value="<?= htmlspecialchars((string) ($old['pertelefono1'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Fijo</span>
                <input id="pertelefono2" name="pertelefono2" placeholder="Ingrese telefono fijo" value="<?= htmlspecialchars((string) ($old['pertelefono2'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
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
                <input id="perprofesion" name="perprofesion" placeholder="Ingrese la profesion" value="<?= htmlspecialchars((string) ($old['perprofesion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Ocupacion</span>
                <input id="perocupacion" name="perocupacion" placeholder="Ingrese la ocupacion" value="<?= htmlspecialchars((string) ($old['perocupacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <div class="form-group form-group-full">
            <div class="input-group">
                <span class="input-addon">E-mail</span>
                <input id="percorreo" name="percorreo" type="email" placeholder="Ingrese el correo electronico" value="<?= htmlspecialchars((string) ($old['percorreo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
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
        <button class="btn-primary btn-auto" type="submit"><?= htmlspecialchars((string) ($submitLabel ?? 'Guardar persona'), ENT_QUOTES, 'UTF-8'); ?></button>
    </div>
</form>
<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
