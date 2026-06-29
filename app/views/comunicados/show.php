<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$communication = is_array($communication ?? null) ? $communication : [];
$recipients = is_array($recipients ?? null) ? $recipients : [];
$state = (string) ($communication['comestado'] ?? '');
$stateLabel = [
    'BORRADOR' => 'BORRADOR',
    'PUBLICADO' => 'ENVIADO',
    'ANULADO' => 'ANULADO',
][$state] ?? $state;
?>
<section class="summary-card">
    <div class="institution-form-heading">
        <div>
            <span class="summary-label"><?= htmlspecialchars($stateLabel, ENT_QUOTES, 'UTF-8'); ?></span>
            <strong><?= htmlspecialchars((string) ($communication['comtitulo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
            <p><?= nl2br(htmlspecialchars((string) ($communication['commensaje'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></p>
        </div>
        <div class="actions-group">
            <?php if ($state === 'BORRADOR'): ?>
                <a class="btn-secondary btn-auto" href="<?= htmlspecialchars(baseUrl('comunicados/editar?id=' . (int) $communication['comid']), ENT_QUOTES, 'UTF-8'); ?>">Editar</a>
                <form method="POST" action="<?= htmlspecialchars(baseUrl('comunicados/eliminar'), ENT_QUOTES, 'UTF-8'); ?>" onsubmit="return confirm('Eliminar este borrador?');">
                    <?= csrfField(); ?>
                    <input type="hidden" name="comid" value="<?= htmlspecialchars((string) $communication['comid'], ENT_QUOTES, 'UTF-8'); ?>">
                    <button class="btn-secondary btn-auto" type="submit">Eliminar</button>
                </form>
            <?php endif; ?>
            <a class="btn-secondary btn-auto" href="<?= htmlspecialchars(baseUrl('comunicados'), ENT_QUOTES, 'UTF-8'); ?>">Volver</a>
        </div>
    </div>
</section>

<section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3>Destinatarios y entregas</h3>
            <p>El correo y WhatsApp quedan pendientes si existe destino valido; si falta correo o celular quedan omitidos.</p>
        </div>
    </header>

    <?php if ($recipients === []): ?>
        <div class="empty-state">Este comunicado aun no tiene destinatarios generados.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Persona</th>
                        <th>Usuario</th>
                        <th>Lectura</th>
                        <th>Email</th>
                        <th>WhatsApp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recipients as $recipient): ?>
                        <tr>
                            <td>
                                <span class="cell-title"><?= htmlspecialchars(trim((string) (($recipient['perapellidos'] ?? '') . ' ' . ($recipient['pernombres'] ?? ''))), ENT_QUOTES, 'UTF-8'); ?></span>
                                <small><?= htmlspecialchars((string) ($recipient['cdetipo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
                            </td>
                            <td><?= htmlspecialchars((string) ($recipient['usunombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars((string) ($recipient['cdeestado_lectura'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <span><?= htmlspecialchars((string) ($recipient['entrega_email'] ?? 'SIN ENTREGA'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if (!empty($recipient['email_destino'])): ?>
                                    <small><?= htmlspecialchars((string) $recipient['email_destino'], ENT_QUOTES, 'UTF-8'); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span><?= htmlspecialchars((string) ($recipient['entrega_whatsapp'] ?? 'SIN ENTREGA'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if (!empty($recipient['whatsapp_destino'])): ?>
                                    <small><?= htmlspecialchars((string) $recipient['whatsapp_destino'], ENT_QUOTES, 'UTF-8'); ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
