<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$communications = is_array($communications ?? null) ? $communications : [];
?>
<p class="module-note">Consulta los comunicados institucionales recibidos. Los comunicados mostrados al iniciar sesion se marcan como leidos automaticamente.</p>

<?php if ($communications === []): ?>
    <div class="empty-state">No tienes comunicados recibidos.</div>
<?php else: ?>
    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Comunicados recibidos</h3>
                <p>Seleccione un comunicado para revisar su contenido.</p>
            </div>
        </header>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Titulo</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Enviado por</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($communications as $communication): ?>
                        <?php
                        $communicationId = (int) ($communication['comid'] ?? 0);
                        $readState = (string) ($communication['cdeestado_lectura'] ?? '');
                        $communicationState = (string) ($communication['comestado'] ?? '');
                        $senderRole = (string) ($communication['enviado_por_rol'] ?? 'Institucion');
                        $readIcon = $readState === 'PENDIENTE' ? 'fa-envelope' : 'fa-envelope-open';
                        $readLabel = $readState === 'PENDIENTE' ? 'Pendiente' : 'Leido';
                        ?>
                        <tr class="communication-row <?= $readState === 'PENDIENTE' ? 'is-unread' : ''; ?>"
                            tabindex="0"
                            role="button"
                            data-communication-message-row
                            data-communication-id="<?= htmlspecialchars((string) $communicationId, ENT_QUOTES, 'UTF-8'); ?>"
                            data-communication-read-url="<?= htmlspecialchars(baseUrl('mis-comunicados/leer?id=' . $communicationId), ENT_QUOTES, 'UTF-8'); ?>"
                            data-communication-title="<?= htmlspecialchars((string) ($communication['comtitulo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            data-communication-message="<?= htmlspecialchars((string) ($communication['commensaje'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            data-communication-date="<?= htmlspecialchars((string) ($communication['comfecha_publicacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            data-communication-state="<?= htmlspecialchars($communicationState === 'ANULADO' ? 'ANULADO' : '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-communication-read-state="<?= htmlspecialchars($readState, ENT_QUOTES, 'UTF-8'); ?>">
                            <td><span class="cell-title"><?= htmlspecialchars((string) ($communication['comtitulo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td>
                                <span class="communication-read-state <?= $readState === 'PENDIENTE' ? 'is-unread' : 'is-read'; ?>"
                                    data-communication-row-read-state
                                    title="<?= htmlspecialchars($readLabel, ENT_QUOTES, 'UTF-8'); ?>"
                                    aria-label="<?= htmlspecialchars($readLabel, ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="fa <?= htmlspecialchars($readIcon, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                                </span>
                            </td>
                            <td><?= htmlspecialchars((string) ($communication['comfecha_publicacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars($senderRole, ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <dialog class="calendar-dialog communication-message-dialog" data-communication-message-dialog>
        <header class="security-assignment-header">
            <div>
                <span class="summary-label" data-communication-message-state></span>
                <h3 data-communication-message-title></h3>
                <small data-communication-message-date></small>
            </div>
        </header>
        <div class="communication-message-body" data-communication-message-body></div>
        <div class="actions-row">
            <button class="btn-primary btn-inline" type="button" data-communication-message-close>Aceptar</button>
        </div>
    </dialog>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
