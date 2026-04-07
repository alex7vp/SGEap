<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';
?>
<div class="toolbar">
    <p>Convierte una persona registrada en estudiante para continuar luego con matricula.</p>
    <a class="text-link" href="<?= htmlspecialchars(baseUrl('estudiantes'), ENT_QUOTES, 'UTF-8'); ?>">Volver al listado</a>
</div>

<?php if (empty($persons)): ?>
    <div class="empty-state">
        No hay personas disponibles para registrar como estudiantes.
        <a class="text-link" href="<?= htmlspecialchars(baseUrl('personas/crear'), ENT_QUOTES, 'UTF-8'); ?>">Crear una persona</a>
    </div>
<?php else: ?>
    <p class="module-note">Este formulario adopta la misma estructura compacta de personas para mantener consistencia entre modulos.</p>

    <form class="data-form" method="POST" action="<?= htmlspecialchars(baseUrl('estudiantes'), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="form-grid">
            <div class="form-group form-group-full">
                <div class="input-group">
                    <span class="input-addon">Persona</span>
                    <select id="perid" name="perid" required>
                        <option value="">Seleccione una persona</option>
                        <?php foreach ($persons as $person): ?>
                            <option value="<?= htmlspecialchars((string) $person['perid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (string) ($old['perid'] ?? '') === (string) $person['perid'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars((string) $person['percedula'], ENT_QUOTES, 'UTF-8'); ?> -
                                <?= htmlspecialchars((string) $person['perapellidos'], ENT_QUOTES, 'UTF-8'); ?>,
                                <?= htmlspecialchars((string) $person['pernombres'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Origen</span>
                    <input id="estlugarnacimiento" name="estlugarnacimiento" value="<?= htmlspecialchars((string) ($old['estlugarnacimiento'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Parroquia</span>
                    <input id="estparroquia" name="estparroquia" value="<?= htmlspecialchars((string) ($old['estparroquia'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="form-group form-group-full">
                <div class="input-group">
                    <span class="input-addon">Direccion</span>
                    <input id="estdireccion" name="estdireccion" value="<?= htmlspecialchars((string) ($old['estdireccion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Estado</span>
                    <select id="estestado" name="estestado">
                        <option value="1" <?= ($old['estestado'] ?? '1') === '1' ? 'selected' : ''; ?>>Activo</option>
                        <option value="0" <?= ($old['estestado'] ?? '1') === '0' ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="actions-row">
            <button class="btn-primary btn-auto" type="submit">Guardar estudiante</button>
        </div>
    </form>
<?php endif; ?>
<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
