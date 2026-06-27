<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$pagination = is_array($pagination ?? null) ? $pagination : ['rows' => [], 'total' => 0, 'page' => 1, 'limit' => 25];
$communications = (array) ($pagination['rows'] ?? []);
$stateLabels = [
    'BORRADOR' => 'BORRADOR',
    'PUBLICADO' => 'ENVIADO',
    'ANULADO' => 'ANULADO',
];
?>
<p class="module-note">Gestiona comunicados institucionales. Los borradores no son visibles hasta que se envian.</p>

<section class="summary-card">
    <div class="institution-form-heading">
        <div>
            <span class="summary-label">Gestion</span>
            <strong>Comunicados</strong>
            <p>Secretaria y personal autorizado pueden preparar borradores o enviarlos a estudiantes, representantes, docentes y administrativos.</p>
        </div>
        <a class="btn-primary btn-auto" href="<?= htmlspecialchars(baseUrl('comunicados/nuevo'), ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fa fa-plus" aria-hidden="true"></i>
            Nuevo comunicado
        </a>
    </div>
</section>

<section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3>Historial de comunicados</h3>
            <p>Los enviados no se editan; si tienen errores se crea uno nuevo.</p>
        </div>
    </header>

    <?php if ($communications === []): ?>
        <div class="empty-state">No existen comunicados registrados.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Titulo</th>
                        <th>Estado</th>
                        <th>Destinatarios</th>
                        <th>Lecturas</th>
                        <th>Mensajeria</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($communications as $communication): ?>
                        <?php
                        $state = (string) ($communication['comestado'] ?? '');
                        $stateLabel = $stateLabels[$state] ?? $state;
                        $totalRecipients = (int) ($communication['total_destinatarios'] ?? 0);
                        $readCount = (int) ($communication['total_leidos'] ?? 0);
                        ?>
                        <tr>
                            <td>
                                <span class="cell-title"><?= htmlspecialchars((string) ($communication['comtitulo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                <small><?= htmlspecialchars((string) ($communication['comdestino_resumen'] ?? 'Sin destinatarios'), ENT_QUOTES, 'UTF-8'); ?></small>
                            </td>
                            <td><span class="permission-option-state <?= $state === 'PUBLICADO' ? 'is-active' : ''; ?>"><?= htmlspecialchars($stateLabel, ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?= htmlspecialchars((string) $totalRecipients, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars((string) $readCount, ENT_QUOTES, 'UTF-8'); ?> / <?= htmlspecialchars((string) $totalRecipients, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <small>
                                    Email P/O/F:
                                    <?= htmlspecialchars((string) ($communication['emails_pendientes'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> /
                                    <?= htmlspecialchars((string) ($communication['emails_omitidos'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> /
                                    <?= htmlspecialchars((string) ($communication['emails_fallidos'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>
                                </small>
                                <small>
                                    WhatsApp P/O/F:
                                    <?= htmlspecialchars((string) ($communication['whatsapp_pendientes'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> /
                                    <?= htmlspecialchars((string) ($communication['whatsapp_omitidos'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> /
                                    <?= htmlspecialchars((string) ($communication['whatsapp_fallidos'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>
                                </small>
                            </td>
                            <td>
                                <div class="actions-group">
                                    <a class="btn-secondary btn-auto" href="<?= htmlspecialchars(baseUrl('comunicados/ver?id=' . (int) $communication['comid']), ENT_QUOTES, 'UTF-8'); ?>">Ver</a>
                                    <?php if ($state === 'BORRADOR'): ?>
                                        <a class="btn-secondary btn-auto" href="<?= htmlspecialchars(baseUrl('comunicados/editar?id=' . (int) $communication['comid']), ENT_QUOTES, 'UTF-8'); ?>">Editar</a>
                                    <?php endif; ?>
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
