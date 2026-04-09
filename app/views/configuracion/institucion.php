<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$displayName = (string) (($institution['insnombre'] ?? '') !== '' ? $institution['insnombre'] : 'Sin registro');
$displayBusinessName = (string) (($institution['insrazonsocial'] ?? '') !== '' ? $institution['insrazonsocial'] : 'No existe razon social registrada.');
$showInstitutionForm = $institution === false || !empty($error);
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
            <div class="form-group-full">
                <div class="input-group">
                    <span class="input-addon">Nombre</span>
                    <input type="text" name="insnombre" value="<?= htmlspecialchars((string) ($old['insnombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
            </div>
            <div class="form-group-full">
                <div class="input-group">
                    <span class="input-addon">Razon social</span>
                    <input type="text" name="insrazonsocial" value="<?= htmlspecialchars((string) ($old['insrazonsocial'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
            <div>
                <div class="input-group">
                    <span class="input-addon">RUC</span>
                    <input type="text" name="insruc" value="<?= htmlspecialchars((string) ($old['insruc'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
            <div>
                <div class="input-group">
                    <span class="input-addon">AMIE</span>
                    <input type="text" name="inscodigoamie" value="<?= htmlspecialchars((string) ($old['inscodigoamie'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
            <div>
                <div class="input-group">
                    <span class="input-addon">Telefono</span>
                    <input type="text" name="instelefono" value="<?= htmlspecialchars((string) ($old['instelefono'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
            <div>
                <div class="input-group">
                    <span class="input-addon">Correo</span>
                    <input type="email" name="inscorreoelectronico" value="<?= htmlspecialchars((string) ($old['inscorreoelectronico'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
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
