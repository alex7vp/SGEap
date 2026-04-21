<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$editingDocumentId = (int) ($old['domid'] ?? 0);
$documentSource = ($old['domsource'] ?? 'upload') === 'url' ? 'url' : 'upload';
$currentDocumentUrl = trim((string) ($old['domurl'] ?? ''));
$resolveDocumentUrl = static function (string $origin, string $url): string {
    $normalizedOrigin = mb_strtoupper(trim($origin));
    $normalizedUrl = trim($url);

    if ($normalizedUrl === '') {
        return '#';
    }

    if ($normalizedOrigin === 'ARCHIVO') {
        return asset(ltrim($normalizedUrl, '/'));
    }

    if (
        str_starts_with($normalizedUrl, 'http://')
        || str_starts_with($normalizedUrl, 'https://')
        || str_starts_with($normalizedUrl, '/')
    ) {
        return $normalizedUrl;
    }

    if (str_starts_with($normalizedUrl, 'assets/')) {
        return asset(substr($normalizedUrl, 7));
    }

    return baseUrl($normalizedUrl);
};
?>
<nav class="module-subnav" aria-label="Submodulos de configuracion de matricula">
    <a href="<?= htmlspecialchars(baseUrl('configuracion/matricula'), ENT_QUOTES, 'UTF-8'); ?>">Configuracion de matricula</a>
    <a class="is-active" href="<?= htmlspecialchars(baseUrl('configuracion/matricula/documentos'), ENT_QUOTES, 'UTF-8'); ?>">Documentos</a>
</nav>

<p class="module-note">Administra los documentos que se presentan en la pestaña Documentos durante la matricula. Si eliges archivo, el sistema genera y guarda la ruta. Si eliges URL, se usa el enlace que registres.</p>

<section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3><?= $editingDocumentId > 0 ? 'Editar documento de matricula' : 'Nuevo documento de matricula'; ?></h3>
            <p>Marca si el documento es obligatorio para habilitar el boton final de matricula.</p>
        </div>
    </header>

    <form class="data-form" method="POST" action="<?= htmlspecialchars(baseUrl($editingDocumentId > 0 ? 'configuracion/matricula/documentos/actualizar' : 'configuracion/matricula/documentos'), ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data">
        <?php if ($editingDocumentId > 0): ?>
            <input type="hidden" name="domid" value="<?= htmlspecialchars((string) $editingDocumentId, ENT_QUOTES, 'UTF-8'); ?>">
        <?php endif; ?>

        <div class="form-grid">
            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Nombre</span>
                    <input type="text" name="domnombre" required maxlength="150" value="<?= htmlspecialchars((string) ($old['domnombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Descripcion</span>
                    <input type="text" name="domdescripcion" maxlength="250" value="<?= htmlspecialchars((string) ($old['domdescripcion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="form-group form-group-full">
                <div class="document-source-row">
                    <span class="input-addon input-addon-block">Origen</span>
                    <div class="documents-source-grid" data-document-source-selector>
                        <label class="document-source-option" data-document-source-card>
                            <input type="radio" name="domsource" value="upload" <?= $documentSource === 'upload' ? 'checked' : ''; ?> data-document-source-option>
                            <span class="document-source-label">Archivo PDF</span>
                        </label>
                        <label class="document-source-option" data-document-source-card>
                            <input type="radio" name="domsource" value="url" <?= $documentSource === 'url' ? 'checked' : ''; ?> data-document-source-option>
                            <span class="document-source-label">URL del documento</span>
                        </label>
                    </div>
                </div>
                <small class="field-help">Selecciona si el documento se carga como archivo PDF o si se enlaza mediante una URL.</small>
            </div>

            <div class="form-group form-group-full" data-document-source-panel="upload" <?= $documentSource !== 'upload' ? 'hidden' : ''; ?>>
                <div class="input-group">
                    <span class="input-addon">Archivo PDF</span>
                    <div class="file-input-shell">
                        <label class="file-input-button" for="document-file-input">Elegir archivo</label>
                        <span class="file-input-name" data-file-input-name>No se eligió ningún archivo</span>
                        <input id="document-file-input" class="file-input-native" type="file" name="document_file" accept=".pdf,application/pdf" data-file-input>
                    </div>
                </div>
                <?php if ($editingDocumentId > 0 && $currentDocumentUrl !== '' && $documentSource === 'upload'): ?>
                    <small class="field-help">Archivo actual: <a class="text-link" href="<?= htmlspecialchars($resolveDocumentUrl('ARCHIVO', $currentDocumentUrl), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">abrir PDF actual</a>. Si cargas otro, se reemplaza.</small>
                <?php else: ?>
                    <small class="field-help">El sistema asigna el nombre de archivo y guarda la ruta automaticamente.</small>
                <?php endif; ?>
            </div>

            <div class="form-group form-group-full" data-document-source-panel="url" <?= $documentSource !== 'url' ? 'hidden' : ''; ?>>
                <div class="input-group">
                    <span class="input-addon">URL</span>
                    <input type="text" name="domurl" placeholder="https://servidor/documento.pdf" value="<?= htmlspecialchars($documentSource === 'url' ? $currentDocumentUrl : '', ENT_QUOTES, 'UTF-8'); ?>" data-document-url-input>
                </div>
                <small class="field-help">Usa esta opcion si el documento ya existe en otro sistema o repositorio.</small>
            </div>

            <div class="form-group">
                <label class="resource-option resource-option-switch">
                    <span>Obligatorio</span>
                    <span class="switch-control">
                        <input type="checkbox" name="domobligatorio" value="1" <?= !empty($old['domobligatorio']) ? 'checked' : ''; ?>>
                        <span class="switch-slider" aria-hidden="true"></span>
                    </span>
                </label>
            </div>

            <div class="form-group">
                <label class="resource-option resource-option-switch">
                    <span>Activo</span>
                    <span class="switch-control">
                        <input type="checkbox" name="domactivo" value="1" <?= !empty($old['domactivo']) ? 'checked' : ''; ?>>
                        <span class="switch-slider" aria-hidden="true"></span>
                    </span>
                </label>
            </div>
        </div>

        <div class="actions-row">
            <button class="btn-primary btn-auto" type="submit"><?= $editingDocumentId > 0 ? 'Actualizar documento' : 'Guardar documento'; ?></button>
        </div>
    </form>
</section>

<section class="security-assignment-block" id="documentos-matricula-registrados">
    <header class="security-assignment-header">
        <div>
            <h3>Documentos registrados</h3>
            <p>Los documentos activos aparecen en el proceso de matricula. Los obligatorios exigen aceptacion antes de guardar.</p>
        </div>
    </header>

    <?php if (!empty($documentsFeedback)): ?>
        <div class="catalog-feedback security-feedback-global">
            <div class="alert <?= ($documentsFeedback['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success'; ?> alert-dismissible" data-alert>
                <span><?= htmlspecialchars((string) ($documentsFeedback['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                    <i class="fa fa-times" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($documents)): ?>
        <div class="empty-state">Todavia no existen documentos registrados para matricula.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Documento</th>
                        <th>Enlace</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $document): ?>
                        <tr>
                            <td>
                                <span class="cell-title"><?= htmlspecialchars((string) $document['domnombre'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="cell-subtitle"><?= htmlspecialchars((string) ($document['domdescripcion'] ?? 'Sin descripcion'), ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td>
                                <a class="text-link" href="<?= htmlspecialchars($resolveDocumentUrl((string) ($document['domorigen'] ?? 'URL'), (string) ($document['domurl'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Abrir documento</a>
                                <div class="cell-subtitle"><?= htmlspecialchars((string) $document['domurl'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td>
                                <span class="permission-option-state <?= !empty($document['domactivo']) ? 'is-active' : 'is-inactive'; ?>">
                                    <?= !empty($document['domactivo']) ? 'Activo' : 'Inactivo'; ?>
                                </span>
                                <div class="cell-subtitle"><?= !empty($document['domobligatorio']) ? 'Obligatorio' : 'Opcional'; ?> | <?= htmlspecialchars((string) ($document['domorigen'] ?? 'URL'), ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td>
                                <div class="actions-group">
                                    <a class="icon-button icon-button-edit" href="<?= htmlspecialchars(baseUrl('configuracion/matricula/documentos') . '?edit=' . $document['domid'], ENT_QUOTES, 'UTF-8'); ?>" title="Editar documento" aria-label="Editar documento">
                                        <i class="fa fa-pencil" aria-hidden="true"></i>
                                    </a>
                                    <form method="POST" action="<?= htmlspecialchars(baseUrl('configuracion/matricula/documentos/eliminar'), ENT_QUOTES, 'UTF-8'); ?>" onsubmit="return confirm('Confirma que desea eliminar este documento de matricula?');">
                                        <input type="hidden" name="domid" value="<?= htmlspecialchars((string) $document['domid'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <button class="icon-button icon-button-delete" type="submit" title="Eliminar documento" aria-label="Eliminar documento">
                                            <i class="fa fa-trash" aria-hidden="true"></i>
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
