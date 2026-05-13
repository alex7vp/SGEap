<?php

declare(strict_types=1);

$novelties = is_array($novelties ?? null) ? $novelties : [];
$showActions = !empty($showActions);
$contextLabels = [
    'CLASE' => 'Clase',
    'RECREO' => 'Recreo',
    'ENTRADA' => 'Entrada',
    'SALIDA' => 'Salida',
    'PATIO' => 'Patio',
    'BAR' => 'Bar',
    'EVENTO' => 'Evento',
    'OTRO' => 'Otro',
];
?>

<?php if ($novelties === []): ?>
    <div class="empty-state">No hay novedades registradas para los filtros seleccionados.</div>
<?php else: ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Estudiante</th>
                    <th>Contexto</th>
                    <th>Tipo</th>
                    <th>Descripcion</th>
                    <th>Registro</th>
                    <?php if ($showActions): ?>
                        <th>Anulacion</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($novelties as $novelty): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars((string) $novelty['noefecha'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php if (!empty($novelty['noehora'])): ?>
                                <span class="cell-subtitle"><?= htmlspecialchars(substr((string) $novelty['noehora'], 0, 5), ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars((string) $novelty['perapellidos'] . ' ' . $novelty['pernombres'], ENT_QUOTES, 'UTF-8'); ?>
                            <span class="cell-subtitle"><?= htmlspecialchars((string) $novelty['curso'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </td>
                        <td>
                            <?= htmlspecialchars($contextLabels[(string) $novelty['noetipo_contexto']] ?? (string) $novelty['noetipo_contexto'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php if (!empty($novelty['sclnumero_hora'])): ?>
                                <span class="cell-subtitle">
                                    <?= htmlspecialchars((string) $novelty['sclnumero_hora'] . ' hora | ' . (string) $novelty['mtcnombre_mostrar'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            <?php elseif (!empty($novelty['noeubicacion'])): ?>
                                <span class="cell-subtitle"><?= htmlspecialchars((string) $novelty['noeubicacion'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars((string) ($novelty['tnonombre'] ?? 'Sin tipo'), ENT_QUOTES, 'UTF-8'); ?>
                            <?php if (!empty($novelty['tnogravedad'])): ?>
                                <span class="cell-subtitle"><?= htmlspecialchars((string) $novelty['tnogravedad'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= nl2br(htmlspecialchars((string) $novelty['noedescripcion'], ENT_QUOTES, 'UTF-8')); ?></td>
                        <td>
                            <?= htmlspecialchars((string) $novelty['noeestado'], ENT_QUOTES, 'UTF-8'); ?>
                            <span class="cell-subtitle">
                                <?= htmlspecialchars((string) $novelty['usuario_registro'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                            <?php if (($novelty['noeestado'] ?? '') === 'ANULADA'): ?>
                                <span class="cell-subtitle">
                                    <?= htmlspecialchars((string) ($novelty['noemotivo_anulacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <?php if ($showActions): ?>
                            <td>
                                <?php if (($novelty['noeestado'] ?? '') === 'ANULADA'): ?>
                                    <span class="muted">Anulada</span>
                                <?php else: ?>
                                    <form
                                        method="POST"
                                        action="<?= htmlspecialchars(baseUrl('novedades/anular'), ENT_QUOTES, 'UTF-8'); ?>"
                                        class="status-switch-form"
                                        onsubmit="return confirm('Confirma que desea anular esta novedad?');"
                                    >
                                        <input type="hidden" name="noeid" value="<?= htmlspecialchars((string) $novelty['noeid'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="text" name="noemotivo_anulacion" maxlength="250" placeholder="Motivo" required>
                                        <button class="btn-secondary btn-auto" type="submit">Anular</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
