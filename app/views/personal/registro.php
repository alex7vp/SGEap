<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$staffTypes = is_array($staffTypes ?? null) ? $staffTypes : [];
$selectedTypeIds = array_map('intval', (array) ($old['type_ids'] ?? []));
?>
<div class="toolbar">
    <p>Registra una persona y conviertela en personal institucional desde una sola pantalla.</p>
    <a class="text-link" href="<?= htmlspecialchars(baseUrl('personal'), ENT_QUOTES, 'UTF-8'); ?>">Volver a personal</a>
</div>

<p class="module-note">Si la cedula ya existe como persona en el sistema, se reutiliza ese registro y se agrega el perfil de personal sin duplicar identidad.</p>

<form class="data-form" method="POST" action="<?= htmlspecialchars(baseUrl('personal/registro'), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="form-grid">
        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Cedula</span>
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

        <div class="form-group form-group-full">
            <div class="input-group">
                <span class="input-addon">E-mail</span>
                <input id="percorreo" name="percorreo" type="email" placeholder="Ingrese el correo electronico" value="<?= htmlspecialchars((string) ($old['percorreo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Contratacion</span>
                <input id="psnfechacontratacion" name="psnfechacontratacion" type="date" required value="<?= htmlspecialchars((string) ($old['psnfechacontratacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Salida</span>
                <input id="psnfechasalida" name="psnfechasalida" type="date" value="<?= htmlspecialchars((string) ($old['psnfechasalida'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
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
                <input id="psnobservacion" name="psnobservacion" placeholder="Ingrese una observacion" value="<?= htmlspecialchars((string) ($old['psnobservacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <div class="form-group form-group-full">
            <?php if (empty($staffTypes)): ?>
                <div class="empty-state">No existen tipos de personal activos. Registralos primero en Configuracion &gt; Catalogos.</div>
            <?php else: ?>
                <div class="resource-grid personal-type-grid">
                    <?php foreach ($staffTypes as $type): ?>
                        <?php $typeId = (int) ($type['tpid'] ?? 0); ?>
                        <label class="resource-option personal-type-option">
                            <span>
                                <strong><?= htmlspecialchars((string) ($type['tpnombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span class="cell-subtitle"><?= htmlspecialchars((string) (($type['tpdescripcion'] ?? '') !== '' ? $type['tpdescripcion'] : 'Sin descripcion'), ENT_QUOTES, 'UTF-8'); ?></span>
                            </span>
                            <input
                                type="checkbox"
                                name="type_ids[]"
                                value="<?= htmlspecialchars((string) $typeId, ENT_QUOTES, 'UTF-8'); ?>"
                                <?= in_array($typeId, $selectedTypeIds, true) ? 'checked' : ''; ?>
                            >
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="actions-row">
        <button class="btn-primary btn-auto" type="submit">Guardar personal</button>
    </div>
</form>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
