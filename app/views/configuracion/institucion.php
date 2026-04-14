<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$displayName = (string) (($institution['insnombre'] ?? '') !== '' ? $institution['insnombre'] : 'Sin registro');
$displayBusinessName = (string) (($institution['insrazonsocial'] ?? '') !== '' ? $institution['insrazonsocial'] : 'No existe razon social registrada.');
$fieldErrors = $fieldErrors ?? [];
$showInstitutionForm = $institution === false || !empty($error) || !empty($fieldErrors);
?>
<p class="module-note">Este modulo concentra la informacion institucional principal usada por el sistema, reportes y configuraciones generales.</p>

<section class="summary-card">
    <span class="summary-label">Institucion actual</span>
    <strong><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></strong>
    <p><?= htmlspecialchars($displayBusinessName, ENT_QUOTES, 'UTF-8'); ?></p>

    <div class="meta-grid institution-meta-grid">
        <div class="meta-item">
            <strong>AMIE</strong>
            <span><?= htmlspecialchars((string) (($institution['inscodigoamie'] ?? '') !== '' ? $institution['inscodigoamie'] : 'No registrado'), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="meta-item">
            <strong>RUC</strong>
            <span><?= htmlspecialchars((string) (($institution['insruc'] ?? '') !== '' ? $institution['insruc'] : 'No registrado'), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="meta-item">
            <strong>Telefono</strong>
            <span><?= htmlspecialchars((string) (($institution['instelefono'] ?? '') !== '' ? $institution['instelefono'] : 'No registrado'), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="meta-item">
            <strong>Correo</strong>
            <span><?= htmlspecialchars((string) (($institution['inscorreoelectronico'] ?? '') !== '' ? $institution['inscorreoelectronico'] : 'No registrado'), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="meta-item">
            <strong>Direccion</strong>
            <span><?= htmlspecialchars((string) (($institution['insdireccion'] ?? '') !== '' ? $institution['insdireccion'] : 'No registrado'), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="meta-item">
            <strong>Representante</strong>
            <span><?= htmlspecialchars((string) (($institution['insrepresentantelegal'] ?? '') !== '' ? $institution['insrepresentantelegal'] : 'No registrado'), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    </div>

    <div class="institution-form-heading">
        <div>
            <span class="summary-label"><?= !empty($old['insid']) ? 'Edicion' : 'Registro'; ?></span>
            <strong><?= !empty($old['insid']) ? 'Actualizar institucion' : 'Registrar institucion'; ?></strong>
            <p>La informacion se mantiene como ficha unica, por lo que este formulario crea o actualiza el mismo registro institucional.</p>
        </div>
        <?php if ($institution !== false): ?>
            <div class="actions-group">
                <button class="btn-primary btn-auto" type="button" data-institution-edit <?= $showInstitutionForm ? 'hidden' : ''; ?>>Editar ficha</button>
                <button class="btn-secondary btn-auto" type="button" data-institution-cancel <?= $showInstitutionForm ? '' : 'hidden'; ?>>Cancelar</button>
            </div>
        <?php endif; ?>
    </div>

    <form class="data-form institution-form-card" method="POST" action="<?= htmlspecialchars(baseUrl('configuracion/institucion'), ENT_QUOTES, 'UTF-8'); ?>" data-institution-form <?= $showInstitutionForm ? '' : 'hidden'; ?>>
        <div class="form-grid">
            <div class="form-group-full" id="institution-field-insnombre">
                <?php if (!empty($fieldErrors['insnombre'])): ?>
                    <div class="alert alert-error form-field-alert">
                        <span><?= htmlspecialchars((string) $fieldErrors['insnombre'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                <?php endif; ?>
                <div class="input-group">
                    <span class="input-addon">Nombre</span>
                    <input class="<?= !empty($fieldErrors['insnombre']) ? 'is-invalid' : ''; ?>" type="text" name="insnombre" value="<?= htmlspecialchars((string) ($old['insnombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
            </div>
            <div class="form-group-full">
                <div class="input-group">
                    <span class="input-addon">Razon social</span>
                    <input type="text" name="insrazonsocial" value="<?= htmlspecialchars((string) ($old['insrazonsocial'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
            <div id="institution-field-insruc">
                <?php if (!empty($fieldErrors['insruc'])): ?>
                    <div class="alert alert-error form-field-alert">
                        <span><?= htmlspecialchars((string) $fieldErrors['insruc'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                <?php endif; ?>
                <div class="input-group">
                    <span class="input-addon">RUC</span>
                    <input
                        class="<?= !empty($fieldErrors['insruc']) ? 'is-invalid' : ''; ?>"
                        type="text"
                        name="insruc"
                        value="<?= htmlspecialchars((string) ($old['insruc'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        maxlength="13"
                        minlength="13"
                        pattern="\d{13}"
                        inputmode="numeric"
                        placeholder="Ej: 1790012345001"
                        title="El RUC debe contener 13 digitos numericos"
                        autocomplete="off"
                    >
                </div>
            </div>
            <div id="institution-field-inscodigoamie">
                <?php if (!empty($fieldErrors['inscodigoamie'])): ?>
                    <div class="alert alert-error form-field-alert">
                        <span><?= htmlspecialchars((string) $fieldErrors['inscodigoamie'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                <?php endif; ?>
                <div class="input-group">
                    <span class="input-addon">AMIE</span>
                    <input
                        class="<?= !empty($fieldErrors['inscodigoamie']) ? 'is-invalid' : ''; ?>"
                        type="text"
                        name="inscodigoamie"
                        value="<?= htmlspecialchars((string) ($old['inscodigoamie'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        maxlength="8"
                        pattern="\d{2}[A-Z]{1}\d{5}"
                        placeholder="Ej: 17H01234"
                        title="Formato AMIE: 2 digitos, 1 letra mayuscula y 5 digitos"
                        autocomplete="off"
                    >
                </div>
            </div>
            <div id="institution-field-instelefono">
                <?php if (!empty($fieldErrors['instelefono'])): ?>
                    <div class="alert alert-error form-field-alert">
                        <span><?= htmlspecialchars((string) $fieldErrors['instelefono'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                <?php endif; ?>
                <div class="input-group">
                    <span class="input-addon">Telefono</span>
                    <input
                        class="<?= !empty($fieldErrors['instelefono']) ? 'is-invalid' : ''; ?>"
                        type="text"
                        name="instelefono"
                        value="<?= htmlspecialchars((string) ($old['instelefono'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="(09) 9894 5698"
                        maxlength="14"
                        inputmode="numeric"
                        autocomplete="off"
                        data-phone-mask
                    >
                </div>
            </div>
            <div id="institution-field-inscorreoelectronico">
                <?php if (!empty($fieldErrors['inscorreoelectronico'])): ?>
                    <div class="alert alert-error form-field-alert">
                        <span><?= htmlspecialchars((string) $fieldErrors['inscorreoelectronico'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                <?php endif; ?>
                <div class="input-group">
                    <span class="input-addon">Correo</span>
                    <input
                        class="<?= !empty($fieldErrors['inscorreoelectronico']) ? 'is-invalid' : ''; ?>"
                        type="email"
                        name="inscorreoelectronico"
                        value="<?= htmlspecialchars((string) ($old['inscorreoelectronico'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="correo@institucion.edu.ec"
                        autocomplete="off"
                    >
                </div>
            </div>
            <div class="form-group-full">
                <div class="input-group">
                    <span class="input-addon">Direccion</span>
                    <input type="text" name="insdireccion" value="<?= htmlspecialchars((string) ($old['insdireccion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
            <div class="form-group-full">
                <div class="input-group">
                    <span class="input-addon">Representante</span>
                    <input type="text" name="insrepresentantelegal" value="<?= htmlspecialchars((string) ($old['insrepresentantelegal'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
        </div>
        <div class="actions-row">
            <button class="btn-primary btn-inline" type="submit"><?= $institution !== false ? 'Actualizar institucion' : 'Guardar institucion'; ?></button>
        </div>
    </form>
</section>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
