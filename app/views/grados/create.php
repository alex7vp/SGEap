<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';
?>
<div class="toolbar">
    <p>Registro de grados vinculados a un nivel educativo.</p>
    <a class="text-link" href="<?= htmlspecialchars(baseUrl('grados'), ENT_QUOTES, 'UTF-8'); ?>">Volver al listado</a>
</div>

<p class="module-note">Este catalogo academico se utiliza despues en la construccion de cursos por periodo lectivo.</p>

<?php if (empty($levels)): ?>
    <div class="empty-state">No existen niveles educativos disponibles. Registra primero los niveles en Configuracion &gt; Catalogos.</div>
<?php else: ?>
    <section id="grado-form">
    <?php if (!empty($gradeFormFeedback)): ?>
        <div class="catalog-feedback security-feedback-global">
            <div class="alert <?= ($gradeFormFeedback['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success'; ?> alert-dismissible" data-alert>
                <span><?= htmlspecialchars((string) ($gradeFormFeedback['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                    <i class="fa fa-times" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <form class="data-form" method="POST" action="<?= htmlspecialchars((string) ($formAction ?? baseUrl('grados')), ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (!empty($old['graid'])): ?>
            <input type="hidden" name="graid" value="<?= htmlspecialchars((string) $old['graid'], ENT_QUOTES, 'UTF-8'); ?>">
        <?php endif; ?>

        <div class="form-grid">
            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Nivel</span>
                    <select id="nedid" name="nedid" required>
                        <option value="">Seleccione una opcion</option>
                        <?php foreach ($levels as $level): ?>
                            <?php $levelId = (string) $level['nedid']; ?>
                            <option value="<?= htmlspecialchars($levelId, ENT_QUOTES, 'UTF-8'); ?>" <?= ($old['nedid'] ?? '') === $levelId ? 'selected' : ''; ?>>
                                <?= htmlspecialchars((string) $level['nednombre'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Grado</span>
                    <input id="granombre" name="granombre" placeholder="Ingrese el nombre del grado" required value="<?= htmlspecialchars((string) ($old['granombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
        </div>

        <div class="actions-row">
            <button class="btn-primary btn-auto" type="submit"><?= htmlspecialchars((string) ($submitLabel ?? 'Guardar grado'), ENT_QUOTES, 'UTF-8'); ?></button>
        </div>
    </form>
    </section>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
