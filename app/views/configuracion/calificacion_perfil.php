<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$profile = $detail['profile'] ?? [];
$subperiods = $detail['subperiods'] ?? [];
$components = $detail['components'] ?? [];
$scales = $detail['scales'] ?? [];
$ambits = $detail['ambits'] ?? [];
$assignments = $detail['assignments'] ?? [];
$promotionTramos = $detail['promotionTramos'] ?? [];
$extraordinaryInstances = $detail['extraordinaryInstances'] ?? [];
$profileId = (int) ($profile['pcaid'] ?? 0);
$isDraft = (string) ($profile['pcaestado'] ?? '') === 'BORRADOR';
?>
<p class="module-note">Revisa y ajusta la configuracion real del periodo antes de activar el perfil para registro de notas.</p>

<?php if (!empty($feedback)): ?>
    <div class="catalog-feedback security-feedback-global">
        <div class="alert <?= ($feedback['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success'; ?> alert-dismissible" data-alert>
            <span><?= $h($feedback['message'] ?? ''); ?></span>
            <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                <i class="fa fa-times" aria-hidden="true"></i>
            </button>
        </div>
    </div>
<?php endif; ?>

<section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3><?= $h($profile['pcanombre'] ?? 'Perfil'); ?></h3>
            <p>Periodo: <strong><?= $h($profile['pledescripcion'] ?? ''); ?></strong> | Estado: <strong><?= $h($profile['pcaestado'] ?? ''); ?></strong></p>
        </div>
        <a class="btn-secondary btn-auto" href="<?= $h(baseUrl('configuracion/academica/calificaciones')); ?>">Volver</a>
    </header>

    <div class="table-wrap">
        <table class="data-table">
            <tbody>
                <tr>
                    <th>Tipo</th>
                    <td><?= $h($profile['pcatipo_base'] ?? ''); ?></td>
                    <th>Version</th>
                    <td><?= $h($profile['pcaversion'] ?? ''); ?></td>
                </tr>
                <tr>
                    <th>Vigencia</th>
                    <td><?= $h(($profile['pcavigencia_desde'] ?? '') . (($profile['pcavigencia_hasta'] ?? '') !== '' ? ' / ' . $profile['pcavigencia_hasta'] : '')); ?></td>
                    <th>Escala</th>
                    <td><?= $h(($profile['pcaminima'] ?? '') . (($profile['pcamaxima'] ?? '') !== '' ? ' - ' . $profile['pcamaxima'] : '')); ?></td>
                </tr>
                <tr>
                    <th>Aprobacion</th>
                    <td><?= $h($profile['pcaaprobacion'] ?? 'No aplica'); ?></td>
                    <th>Decimales</th>
                    <td><?= $h(($profile['pcadecimales'] ?? '') . ' | ' . ($profile['pcametodo_decimal'] ?? '')); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <?php if (in_array((string) ($profile['pcaestado'] ?? ''), ['BORRADOR', 'EN_REVISION'], true)): ?>
        <form method="POST" action="<?= $h(baseUrl('configuracion/academica/calificaciones/perfil/activar')); ?>" class="actions-row" onsubmit="return confirm('Confirma activar este perfil de calificaciones?');">
            <input type="hidden" name="pcaid" value="<?= $h($profileId); ?>">
            <button class="btn-primary btn-inline" type="submit">Activar perfil</button>
        </form>
    <?php endif; ?>
</section>

<form method="POST" action="<?= $h(baseUrl('configuracion/academica/calificaciones/perfil')); ?>" class="grade-profile-edit-form">
    <input type="hidden" name="pcaid" value="<?= $h($profileId); ?>">

    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Subperiodos y componentes</h3>
                <p><?= $isDraft ? 'Pulsa Editar para ajustar nombres, fechas, pesos y componentes mientras el perfil este en borrador.' : 'Perfil en solo lectura porque ya no esta en borrador.'; ?></p>
            </div>
            <?php if ($isDraft): ?>
                <button class="btn-secondary btn-auto grade-profile-edit-button" type="button" data-grade-profile-edit>
                    <i class="fa fa-pencil" aria-hidden="true"></i>
                    Editar
                </button>
            <?php endif; ?>
        </header>

        <?php if (empty($subperiods)): ?>
            <div class="empty-state">El perfil no tiene subperiodos configurados.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Subperiodo</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Final</th>
                            <th>Peso final</th>
                            <th>Componentes</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody data-grade-subperiods-body>
                        <?php foreach ($subperiods as $subperiod): ?>
                            <?php $subperiodId = (int) $subperiod['spcid']; ?>
                            <tr data-grade-subperiod-row>
                                <td>
                                    <span class="cell-subtitle">Orden <?= $h($subperiod['spcorden']); ?></span>
                                    <input type="text" name="subperiods[<?= $h($subperiodId); ?>][spcnombre]" maxlength="80" value="<?= $h($subperiod['spcnombre']); ?>" disabled data-grade-profile-field>
                                    <input type="hidden" name="subperiods[<?= $h($subperiodId); ?>][delete]" value="0" data-grade-subperiod-delete-input>
                                </td>
                                <td>
                                    <input type="date" name="subperiods[<?= $h($subperiodId); ?>][spcfecha_inicio]" value="<?= $h($subperiod['spcfecha_inicio']); ?>" disabled data-grade-profile-field>
                                </td>
                                <td>
                                    <input type="date" name="subperiods[<?= $h($subperiodId); ?>][spcfecha_fin]" value="<?= $h($subperiod['spcfecha_fin']); ?>" disabled data-grade-profile-field>
                                </td>
                                <td>
                                    <input type="checkbox" name="subperiods[<?= $h($subperiodId); ?>][spcparticipa_final]" value="1" <?= !empty($subperiod['spcparticipa_final']) ? 'checked' : ''; ?> disabled data-grade-profile-field>
                                </td>
                                <td>
                                    <input type="number" step="0.001" min="0" name="subperiods[<?= $h($subperiodId); ?>][spcpeso_final]" value="<?= $h($subperiod['spcpeso_final'] ?? ''); ?>" disabled data-grade-profile-field>
                                </td>
                                <td>
                                    <div data-grade-components-list="<?= $h($subperiodId); ?>">
                                    <?php foreach (($components[$subperiodId] ?? []) as $component): ?>
                                        <?php $componentId = (int) $component['cpcid']; ?>
                                        <div class="input-group grade-component-row" style="margin-bottom: .5rem;" data-grade-component-row>
                                            <input type="text" name="components[<?= $h($componentId); ?>][cpcnombre]" maxlength="100" value="<?= $h($component['cpcnombre']); ?>" disabled data-grade-profile-field>
                                            <input type="number" step="0.001" min="0" name="components[<?= $h($componentId); ?>][cpcpeso]" value="<?= $h($component['cpcpeso'] ?? ''); ?>" disabled data-grade-profile-field>
                                            <select name="components[<?= $h($componentId); ?>][cpctipo_calculo]" disabled data-grade-profile-field>
                                                <?php foreach (['PROMEDIO_SIMPLE', 'PROMEDIO_PONDERADO', 'SUMA'] as $type): ?>
                                                    <option value="<?= $h($type); ?>" <?= (string) $component['cpctipo_calculo'] === $type ? 'selected' : ''; ?>><?= $h($type); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="components[<?= $h($componentId); ?>][cpcestado]" value="<?= !empty($component['cpcestado']) ? '1' : '0'; ?>">
                                            <input type="hidden" name="components[<?= $h($componentId); ?>][delete]" value="0" data-grade-component-delete-input>
                                            <?php if ($isDraft): ?>
                                                <button class="icon-button icon-button-delete" type="button" title="Borrar componente" aria-label="Borrar componente" hidden data-grade-profile-edit-control data-grade-component-delete>
                                                    <i class="fa fa-trash" aria-hidden="true"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    </div>
                                    <?php if ($isDraft): ?>
                                        <button class="btn-secondary btn-auto grade-component-add-button" type="button" hidden data-grade-profile-edit-control data-grade-component-add="<?= $h($subperiodId); ?>">
                                            <i class="fa fa-plus" aria-hidden="true"></i>
                                            Agregar componente
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isDraft): ?>
                                        <button class="icon-button icon-button-delete" type="button" title="Borrar subperiodo" aria-label="Borrar subperiodo" hidden data-grade-profile-edit-control data-grade-subperiod-delete>
                                            <i class="fa fa-trash" aria-hidden="true"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($isDraft): ?>
                <div class="actions-row grade-profile-actions" hidden data-grade-profile-save-actions>
                    <button class="btn-secondary btn-auto grade-subperiod-add-button" type="button" data-grade-subperiod-add>
                        <i class="fa fa-plus" aria-hidden="true"></i>
                        Agregar subperiodo
                    </button>
                    <button class="btn-primary btn-inline" type="submit">Guardar ajustes</button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</form>

<section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3>Asignaciones</h3>
            <p>Alcances donde aplica este perfil.</p>
        </div>
    </header>
    <?php if (empty($assignments)): ?>
        <div class="empty-state">Este perfil todavia no tiene asignaciones.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Alcance</th><th>Destino</th><th>Prioridad</th><th>Estado</th></tr></thead>
                <tbody>
                    <?php foreach ($assignments as $assignment): ?>
                        <tr>
                            <td><?= $h($assignment['pasalcance']); ?></td>
                            <td><span class="cell-title"><?= $h($assignment['destino'] ?? ''); ?></span></td>
                            <td><?= $h($assignment['pasprioridad']); ?></td>
                            <td><?= !empty($assignment['pasestado']) ? 'Activo' : 'Inactivo'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3>Escala cualitativa</h3>
            <p>Equivalencias del perfil.</p>
        </div>
    </header>
    <?php if (empty($scales)): ?>
        <div class="empty-state">Este perfil no tiene escala cualitativa.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Codigo</th><th>Nombre</th><th>Rango</th></tr></thead>
                <tbody>
                    <?php foreach ($scales as $scale): ?>
                        <tr>
                            <td><?= $h($scale['ecacodigo']); ?></td>
                            <td><span class="cell-title"><?= $h($scale['ecanombre']); ?></span></td>
                            <td><?= $h(($scale['ecavalor_minimo'] ?? '') . (($scale['ecavalor_maximo'] ?? '') !== '' ? ' - ' . $scale['ecavalor_maximo'] : '')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3>Promocion</h3>
            <p>Reglas copiadas desde la plantilla.</p>
        </div>
    </header>
    <?php if (empty($promotionTramos) && empty($extraordinaryInstances)): ?>
        <div class="empty-state">Este perfil no tiene reglas de promocion.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Tipo</th><th>Resultado / instancia</th><th>Rango</th><th>Condicion</th></tr></thead>
                <tbody>
                    <?php foreach ($promotionTramos as $tramo): ?>
                        <tr>
                            <td>Tramo</td>
                            <td><span class="cell-title"><?= $h($tramo['rptresultado']); ?></span></td>
                            <td><?= $h($tramo['rptnota_minima'] . ' - ' . $tramo['rptnota_maxima']); ?></td>
                            <td><?= !empty($tramo['rpthabilita_extraordinaria']) ? 'Habilita extraordinaria' : ''; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php foreach ($extraordinaryInstances as $instance): ?>
                        <tr>
                            <td>Extraordinaria</td>
                            <td><span class="cell-title"><?= $h($instance['iexnombre']); ?></span></td>
                            <td><?= $h($instance['iexnota_habilita_minima'] . ' - ' . $instance['iexnota_habilita_maxima']); ?></td>
                            <td><?= $h('Aprueba con ' . $instance['iexnota_minima_aprobar']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
