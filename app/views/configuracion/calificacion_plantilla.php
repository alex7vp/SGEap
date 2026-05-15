<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$template = $detail['template'] ?? [];
$subperiods = $detail['subperiods'] ?? [];
$components = $detail['components'] ?? [];
$scales = $detail['scales'] ?? [];
$ambits = $detail['ambits'] ?? [];
$skills = $detail['skills'] ?? [];
$promotionTramos = $detail['promotionTramos'] ?? [];
$extraordinaryInstances = $detail['extraordinaryInstances'] ?? [];
?>
<p class="module-note">Revisa la estructura completa de la plantilla antes de copiarla a un periodo lectivo.</p>

<section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3><?= $h($template['pclnombre'] ?? 'Plantilla'); ?></h3>
            <p><?= $h($template['pcldescripcion'] ?? ''); ?></p>
        </div>
        <a class="btn-secondary btn-auto" href="<?= $h(baseUrl('configuracion/academica/calificaciones')); ?>">Volver</a>
    </header>

    <div class="table-wrap">
        <table class="data-table">
            <tbody>
                <tr>
                    <th>Tipo base</th>
                    <td><?= $h($template['pcltipo_base'] ?? ''); ?></td>
                    <th>Escala</th>
                    <td><?= $h(($template['pclminima'] ?? '') . (($template['pclmaxima'] ?? '') !== '' ? ' - ' . $template['pclmaxima'] : '')); ?></td>
                </tr>
                <tr>
                    <th>Aprobacion</th>
                    <td><?= $h($template['pclaprobacion'] ?? 'No aplica'); ?></td>
                    <th>Decimales</th>
                    <td><?= $h(($template['pcldecimales'] ?? '') . ' | ' . ($template['pclmetodo_decimal'] ?? '')); ?></td>
                </tr>
                <tr>
                    <th>Promedia final</th>
                    <td><?= !empty($template['pclpromedia_final']) ? 'Si' : 'No'; ?></td>
                    <th>Aplica promocion</th>
                    <td><?= !empty($template['pclaplica_promocion']) ? 'Si' : 'No'; ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3>Subperiodos y componentes</h3>
            <p>Estructura que se copiara al perfil del periodo.</p>
        </div>
    </header>

    <?php if (empty($subperiods)): ?>
        <div class="empty-state">Esta plantilla no tiene subperiodos configurados.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Subperiodo</th>
                        <th>Participa final</th>
                        <th>Peso final</th>
                        <th>Componentes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subperiods as $subperiod): ?>
                        <?php $items = $components[(int) $subperiod['psuid']] ?? []; ?>
                        <tr>
                            <td><span class="cell-title"><?= $h($subperiod['psuorden'] . '. ' . $subperiod['psunombre']); ?></span></td>
                            <td><?= !empty($subperiod['psuparticipa_final']) ? 'Si' : 'No'; ?></td>
                            <td><?= $h($subperiod['psupeso_final'] ?? ''); ?></td>
                            <td>
                                <?php if ($items === []): ?>
                                    Sin componentes.
                                <?php else: ?>
                                    <?php foreach ($items as $component): ?>
                                        <div><?= $h($component['pcoorden'] . '. ' . $component['pconombre'] . (($component['pcopeso'] ?? '') !== '' ? ' (' . $component['pcopeso'] . ')' : '')); ?></div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
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
            <p>Equivalencias que se copiaran al perfil.</p>
        </div>
    </header>

    <?php if (empty($scales)): ?>
        <div class="empty-state">Esta plantilla no tiene escala cualitativa.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Nombre</th>
                        <th>Rango</th>
                        <th>Descripcion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($scales as $scale): ?>
                        <tr>
                            <td><?= $h($scale['peccodigo']); ?></td>
                            <td><span class="cell-title"><?= $h($scale['pecnombre']); ?></span></td>
                            <td><?= $h(($scale['pecvalor_minimo'] ?? '') . (($scale['pecvalor_maximo'] ?? '') !== '' ? ' - ' . $scale['pecvalor_maximo'] : '')); ?></td>
                            <td><?= $h($scale['pecdescripcion'] ?? ''); ?></td>
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
            <h3>Ambitos y destrezas</h3>
            <p>Usado por plantillas cualitativas de Inicial y Preparatoria.</p>
        </div>
    </header>

    <?php if (empty($ambits)): ?>
        <div class="empty-state">Esta plantilla no tiene ambitos configurados.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ambito</th>
                        <th>Destrezas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ambits as $ambit): ?>
                        <?php $items = $skills[(int) $ambit['pambid']] ?? []; ?>
                        <tr>
                            <td>
                                <span class="cell-title"><?= $h($ambit['pamborden'] . '. ' . $ambit['pambnombre']); ?></span>
                                <div><?= $h($ambit['pambdescripcion'] ?? ''); ?></div>
                            </td>
                            <td>
                                <?php if ($items === []): ?>
                                    Sin destrezas.
                                <?php else: ?>
                                    <?php foreach ($items as $skill): ?>
                                        <div><?= $h($skill['pdesorden'] . '. ' . ($skill['pdescodigo'] ?? '') . ' ' . $skill['pdesnombre']); ?></div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
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
            <p>Reglas e instancias extraordinarias incluidas en la plantilla.</p>
        </div>
    </header>

    <?php if (empty($promotionTramos) && empty($extraordinaryInstances)): ?>
        <div class="empty-state">Esta plantilla no tiene reglas de promocion.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Nombre / resultado</th>
                        <th>Rango</th>
                        <th>Condicion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($promotionTramos as $tramo): ?>
                        <tr>
                            <td>Tramo</td>
                            <td><span class="cell-title"><?= $h($tramo['pptresultado']); ?></span></td>
                            <td><?= $h($tramo['pptnota_minima'] . ' - ' . $tramo['pptnota_maxima']); ?></td>
                            <td><?= !empty($tramo['ppthabilita_extraordinaria']) ? 'Habilita extraordinaria' : ''; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php foreach ($extraordinaryInstances as $instance): ?>
                        <tr>
                            <td>Extraordinaria</td>
                            <td><span class="cell-title"><?= $h($instance['pienombre']); ?></span></td>
                            <td><?= $h($instance['pienota_habilita_minima'] . ' - ' . $instance['pienota_habilita_maxima']); ?></td>
                            <td><?= $h('Aprueba con ' . $instance['pienota_minima_aprobar'] . (($instance['pienota_final_aprobado'] ?? '') !== '' ? ' | final ' . $instance['pienota_final_aprobado'] : '')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
