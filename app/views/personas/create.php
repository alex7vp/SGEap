<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';
?>
<div class="toolbar">
    <p>Registro base de persona para usuarios, estudiantes y representantes.</p>
    <a class="text-link" href="<?= htmlspecialchars(baseUrl('personas'), ENT_QUOTES, 'UTF-8'); ?>">Volver al listado</a>
</div>

<form class="data-form" method="POST" action="<?= htmlspecialchars((string) ($formAction ?? baseUrl('personas')), ENT_QUOTES, 'UTF-8'); ?>">
    <?php if (!empty($old['perid'])): ?>
        <input type="hidden" name="perid" value="<?= htmlspecialchars((string) $old['perid'], ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>

    <div class="form-grid">
        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Cédula</span>
                <input id="percedula" name="percedula" maxlength="10" required value="<?= htmlspecialchars((string) ($old['percedula'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
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
                <input id="pernombres" name="pernombres" required value="<?= htmlspecialchars((string) ($old['pernombres'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Apellidos</span>
                <input id="perapellidos" name="perapellidos" required value="<?= htmlspecialchars((string) ($old['perapellidos'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Celular</span>
                <input id="pertelefono1" name="pertelefono1" value="<?= htmlspecialchars((string) ($old['pertelefono1'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-addon">Fijo</span>
                <input id="pertelefono2" name="pertelefono2" value="<?= htmlspecialchars((string) ($old['pertelefono2'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <div class="form-group form-group-full">
            <div class="input-group">
                <span class="input-addon">E-mail</span>
                <input id="percorreo" name="percorreo" type="email" value="<?= htmlspecialchars((string) ($old['percorreo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>
    </div>

    <div class="actions-row">
        <button class="btn-primary btn-auto" type="submit"><?= htmlspecialchars((string) ($submitLabel ?? 'Guardar persona'), ENT_QUOTES, 'UTF-8'); ?></button>
    </div>
</form>
<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
