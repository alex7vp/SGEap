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
    <form class="data-form" method="POST" action="<?= htmlspecialchars(baseUrl('estudiantes'), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="form-grid">
            <div class="form-group form-group-full">
                <label for="perid">Persona</label>
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

            <div class="form-group">
                <label for="estlugarnacimiento">Lugar de nacimiento</label>
                <input id="estlugarnacimiento" name="estlugarnacimiento" value="<?= htmlspecialchars((string) ($old['estlugarnacimiento'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="estparroquia">Parroquia</label>
                <input id="estparroquia" name="estparroquia" value="<?= htmlspecialchars((string) ($old['estparroquia'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group form-group-full">
                <label for="estdireccion">Direccion</label>
                <input id="estdireccion" name="estdireccion" value="<?= htmlspecialchars((string) ($old['estdireccion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="estestado">Estado</label>
                <select id="estestado" name="estestado">
                    <option value="1" <?= ($old['estestado'] ?? '1') === '1' ? 'selected' : ''; ?>>Activo</option>
                    <option value="0" <?= ($old['estestado'] ?? '1') === '0' ? 'selected' : ''; ?>>Inactivo</option>
                </select>
            </div>
        </div>

        <div class="actions-row">
            <button class="btn-primary btn-auto" type="submit">Guardar estudiante</button>
        </div>
    </form>
<?php endif; ?>
<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
