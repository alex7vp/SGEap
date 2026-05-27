<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$formatBytes = static function (int $bytes): string {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    }

    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    }

    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }

    return $bytes . ' B';
};
?>

<p class="module-note">Genera respaldos descargables de la base de datos y de los archivos cargados por la institucion.</p>

<section class="summary-card">
    <div class="institution-form-heading">
        <div>
            <span class="summary-label">Respaldo manual</span>
            <strong>Crear backup del sistema</strong>
            <p>El archivo se almacena en storage/backups e incluye database.sql, metadata.json y recursos de public/assets/docs, photos e images.</p>
        </div>
        <form method="POST" action="<?= htmlspecialchars(baseUrl('configuracion/backups'), ENT_QUOTES, 'UTF-8'); ?>">
            <?= csrfField(); ?>
            <button class="btn-primary btn-auto" type="submit">
                <i class="fa fa-database" aria-hidden="true"></i>
                Generar backup
            </button>
        </form>
    </div>
</section>

<section class="summary-card">
    <div class="institution-form-heading">
        <div>
            <span class="summary-label">Historial</span>
            <strong>Backups disponibles</strong>
            <p>Descarga o elimina respaldos generados previamente.</p>
        </div>
    </div>

    <?php if (empty($backups)): ?>
        <div class="empty-state">No existen backups generados.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Archivo</th>
                        <th>Fecha</th>
                        <th>Tamano</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td>
                                <span class="cell-title"><?= htmlspecialchars((string) $backup['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td><?= htmlspecialchars(date('Y-m-d H:i:s', (int) $backup['created_at']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars($formatBytes((int) $backup['size']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <div class="actions-group">
                                    <a class="btn-secondary btn-auto" href="<?= htmlspecialchars(baseUrl('configuracion/backups/descargar') . '?file=' . rawurlencode((string) $backup['name']), ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="fa fa-download" aria-hidden="true"></i>
                                        Descargar
                                    </a>
                                    <form method="POST" action="<?= htmlspecialchars(baseUrl('configuracion/backups/eliminar'), ENT_QUOTES, 'UTF-8'); ?>" onsubmit="return confirm('Eliminar este backup?');">
                                        <?= csrfField(); ?>
                                        <input type="hidden" name="file" value="<?= htmlspecialchars((string) $backup['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <button class="btn-secondary btn-auto" type="submit">
                                            <i class="fa fa-trash" aria-hidden="true"></i>
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
