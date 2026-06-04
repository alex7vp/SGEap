<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="toolbar">
    <p>Actualiza tus datos personales de contacto y perfil institucional.</p>
    <a class="text-link" href="<?= $h(baseUrl('dashboard')); ?>">Volver al inicio</a>
</div>

<form class="data-form profile-form" method="POST" action="<?= $h(baseUrl('perfil')); ?>">
    <?= csrfField(); ?>

    <div class="form-grid">
        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Cedula</span>
                <input name="percedula" maxlength="10" required value="<?= $h($old['percedula'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Sexo</span>
                <select name="persexo">
                    <option value="">Seleccione una opcion</option>
                    <option value="Masculino" <?= ($old['persexo'] ?? '') === 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                    <option value="Femenino" <?= ($old['persexo'] ?? '') === 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Nacimiento</span>
                <input name="perfechanacimiento" type="date" value="<?= $h($old['perfechanacimiento'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Estado civil</span>
                <select name="eciid">
                    <option value="">Seleccione una opcion</option>
                    <?php foreach (($civilStatuses ?? []) as $civilStatus): ?>
                        <option value="<?= $h($civilStatus['eciid'] ?? ''); ?>" <?= (string) ($old['eciid'] ?? '') === (string) ($civilStatus['eciid'] ?? '') ? 'selected' : ''; ?>>
                            <?= $h($civilStatus['ecinombre'] ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Nombres</span>
                <input name="pernombres" required value="<?= $h($old['pernombres'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Apellidos</span>
                <input name="perapellidos" required value="<?= $h($old['perapellidos'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Celular</span>
                <input name="pertelefono1" value="<?= $h($old['pertelefono1'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Fijo</span>
                <input name="pertelefono2" value="<?= $h($old['pertelefono2'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Instruccion</span>
                <select name="istid">
                    <option value="">Seleccione una opcion</option>
                    <?php foreach (($instructionLevels ?? []) as $instructionLevel): ?>
                        <option value="<?= $h($instructionLevel['istid'] ?? ''); ?>" <?= (string) ($old['istid'] ?? '') === (string) ($instructionLevel['istid'] ?? '') ? 'selected' : ''; ?>>
                            <?= $h($instructionLevel['istnombre'] ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Profesion</span>
                <input name="perprofesion" value="<?= $h($old['perprofesion'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Ocupacion</span>
                <input name="perocupacion" value="<?= $h($old['perocupacion'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Trabajo</span>
                <input name="perlugardetrabajo" value="<?= $h($old['perlugardetrabajo'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group form-group-full">
            <div class="input-group">
                <span class="input-addon">E-mail</span>
                <input name="percorreo" type="email" value="<?= $h($old['percorreo'] ?? ''); ?>">
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
        <button class="btn-primary btn-auto" type="submit">Guardar perfil</button>
    </div>
</form>
<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
