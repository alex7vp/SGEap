<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$oldScope = (string) ($old['pasalcance'] ?? '');
$oldTarget = (string) ($old['target_id'] ?? '');
$scratchScope = (string) ($old['scratch_pasalcance'] ?? '');
$scratchTarget = (string) ($old['scratch_target_id'] ?? '');
$selectedCreationMode = (
    (string) ($old['scratch_pcanombre'] ?? '') !== ''
    || (string) ($old['scratch_pleid'] ?? '') !== ''
) ? 'scratch' : 'template';
?>
<p class="module-note">Crea perfiles de calificacion para un periodo lectivo desde una plantilla base o desde cero. Cada perfil conserva su propia version historica.</p>

<?php if (!empty($feedback)): ?>
    <dialog class="calendar-dialog grade-config-feedback-dialog <?= ($feedback['type'] ?? '') === 'error' ? 'is-error' : 'is-success'; ?>" data-grade-config-feedback-dialog>
        <header class="security-assignment-header">
            <div>
                <h3><?= ($feedback['type'] ?? '') === 'error' ? 'No se pudo completar' : 'Operacion completada'; ?></h3>
                <p><?= $h($feedback['message'] ?? ''); ?></p>
            </div>
        </header>
        <div class="actions-row">
            <button class="btn-primary btn-inline" type="button" data-grade-config-feedback-close>Aceptar</button>
        </div>
    </dialog>
<?php endif; ?>

<section class="security-assignment-block grade-profile-creation-block" data-grade-profile-create-mode>
    <header class="security-assignment-header">
        <div>
            <h3>Crear perfil</h3>
            <p>Seleccione si desea partir de una plantilla o construir la configuracion manualmente.</p>
        </div>
    </header>

    <div class="grade-profile-mode-selector" role="radiogroup" aria-label="Tipo de creacion de perfil">
        <label class="grade-profile-mode-option">
            <input type="radio" name="grade_profile_create_mode" value="template" <?= $selectedCreationMode === 'template' ? 'checked' : ''; ?> data-grade-profile-create-radio>
            <span>Desde plantilla</span>
        </label>
        <label class="grade-profile-mode-option">
            <input type="radio" name="grade_profile_create_mode" value="scratch" <?= $selectedCreationMode === 'scratch' ? 'checked' : ''; ?> data-grade-profile-create-radio>
            <span>Desde cero</span>
        </label>
    </div>

    <div data-grade-profile-create-panel="template">
        <header class="grade-profile-panel-header">
            <h4>Crear perfil desde plantilla</h4>
            <p>La copia crea subperiodos, componentes, escalas, ambitos, destrezas y reglas de promocion configuradas en la plantilla.</p>
        </header>

    <?php if (empty($templates)): ?>
        <div class="empty-state">No existen plantillas de calificacion activas.</div>
    <?php elseif (empty($periods)): ?>
        <div class="empty-state">No existen periodos lectivos registrados.</div>
    <?php else: ?>
        <form class="data-form" method="POST" action="<?= $h(baseUrl('configuracion/academica/calificaciones/copiar')); ?>">
            <div class="form-grid">
                <div>
                    <div class="input-group">
                        <span class="input-addon">Plantilla</span>
                        <select name="pclid" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($templates as $template): ?>
                                <option value="<?= $h($template['pclid']); ?>" <?= (string) $template['pclid'] === (string) ($old['pclid'] ?? '') ? 'selected' : ''; ?>>
                                    <?= $h($template['pclnombre'] . ' | ' . $template['pcltipo_base']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Periodo</span>
                        <select name="pleid" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($periods as $period): ?>
                                <option value="<?= $h($period['pleid']); ?>" <?= (string) $period['pleid'] === (string) ($old['pleid'] ?? '') ? 'selected' : ''; ?>>
                                    <?= $h($period['pledescripcion']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Nombre</span>
                        <input type="text" name="pcanombre" value="<?= $h($old['pcanombre'] ?? ''); ?>" maxlength="120" required>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Estado</span>
                        <select name="pcaestado">
                            <option value="BORRADOR" <?= (string) ($old['pcaestado'] ?? 'BORRADOR') === 'BORRADOR' ? 'selected' : ''; ?>>Borrador</option>
                            <option value="ACTIVA" <?= (string) ($old['pcaestado'] ?? '') === 'ACTIVA' ? 'selected' : ''; ?>>Activa</option>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Vigencia desde</span>
                        <input type="date" name="pcavigencia_desde" value="<?= $h($old['pcavigencia_desde'] ?? ''); ?>" required>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Vigencia hasta</span>
                        <input type="date" name="pcavigencia_hasta" value="<?= $h($old['pcavigencia_hasta'] ?? ''); ?>">
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Alcance</span>
                        <select name="pasalcance">
                            <option value="" <?= $oldScope === '' ? 'selected' : ''; ?>>Sin asignar</option>
                            <option value="NIVEL" <?= $oldScope === 'NIVEL' ? 'selected' : ''; ?>>Nivel educativo</option>
                            <option value="GRADO" <?= $oldScope === 'GRADO' ? 'selected' : ''; ?>>Grado</option>
                            <option value="CURSO" <?= $oldScope === 'CURSO' ? 'selected' : ''; ?>>Curso</option>
                            <option value="MATERIA" <?= $oldScope === 'MATERIA' ? 'selected' : ''; ?>>Materia del curso</option>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Destino</span>
                        <select name="target_id">
                            <option value="">Seleccione si asigna alcance</option>
                            <optgroup label="Niveles">
                                <?php foreach ($levels as $level): ?>
                                    <option value="<?= $h($level['nedid']); ?>" <?= $oldScope === 'NIVEL' && $oldTarget === (string) $level['nedid'] ? 'selected' : ''; ?>>
                                        <?= $h($level['nednombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Grados">
                                <?php foreach ($grades as $grade): ?>
                                    <option value="<?= $h($grade['graid']); ?>" <?= $oldScope === 'GRADO' && $oldTarget === (string) $grade['graid'] ? 'selected' : ''; ?>>
                                        <?= $h($grade['nednombre'] . ' | ' . $grade['granombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Cursos">
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= $h($course['curid']); ?>" <?= $oldScope === 'CURSO' && $oldTarget === (string) $course['curid'] ? 'selected' : ''; ?>>
                                        <?= $h($course['pledescripcion'] . ' | ' . $course['granombre'] . ' ' . $course['prlnombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Materias">
                                <?php foreach ($courseSubjects as $subject): ?>
                                    <option value="<?= $h($subject['mtcid']); ?>" <?= $oldScope === 'MATERIA' && $oldTarget === (string) $subject['mtcid'] ? 'selected' : ''; ?>>
                                        <?= $h($subject['pledescripcion'] . ' | ' . $subject['granombre'] . ' ' . $subject['prlnombre'] . ' | ' . $subject['asgnombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                </div>
            </div>
            <div class="actions-row">
                <button class="btn-primary btn-inline" type="submit">Crear perfil</button>
            </div>
        </form>
    <?php endif; ?>
    </div>

    <div id="crear-perfil-desde-cero" data-grade-profile-create-panel="scratch" hidden>
        <header class="grade-profile-panel-header">
            <h4>Crear perfil desde cero</h4>
            <p>Crea un perfil en borrador sin subperiodos ni componentes para configurarlo manualmente en el detalle.</p>
        </header>

    <?php if (empty($periods)): ?>
        <div class="empty-state">No existen periodos lectivos registrados.</div>
    <?php else: ?>
        <form class="data-form" method="POST" action="<?= $h(baseUrl('configuracion/academica/calificaciones/perfil/crear')); ?>">
            <div class="form-grid">
                <div>
                    <div class="input-group">
                        <span class="input-addon">Periodo</span>
                        <select name="pleid" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($periods as $period): ?>
                                <option value="<?= $h($period['pleid']); ?>" <?= (string) $period['pleid'] === (string) ($old['scratch_pleid'] ?? '') ? 'selected' : ''; ?>>
                                    <?= $h($period['pledescripcion']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Nombre</span>
                        <input type="text" name="pcanombre" value="<?= $h($old['scratch_pcanombre'] ?? ''); ?>" maxlength="120" required>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Tipo</span>
                        <select name="pcatipo_base" data-grade-profile-base-type>
                            <option value="CUANTITATIVO" <?= (string) ($old['scratch_pcatipo_base'] ?? 'CUANTITATIVO') === 'CUANTITATIVO' ? 'selected' : ''; ?>>Cuantitativo</option>
                            <option value="CUALITATIVO" <?= (string) ($old['scratch_pcatipo_base'] ?? '') === 'CUALITATIVO' ? 'selected' : ''; ?>>Cualitativo</option>
                            <option value="AMBITOS_DESTREZAS" <?= (string) ($old['scratch_pcatipo_base'] ?? '') === 'AMBITOS_DESTREZAS' ? 'selected' : ''; ?>>Ambitos y destrezas</option>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Vigencia desde</span>
                        <input type="date" name="pcavigencia_desde" value="<?= $h($old['scratch_pcavigencia_desde'] ?? ''); ?>" required>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Vigencia hasta</span>
                        <input type="date" name="pcavigencia_hasta" value="<?= $h($old['scratch_pcavigencia_hasta'] ?? ''); ?>">
                    </div>
                </div>
                <div data-grade-profile-numeric-scale>
                    <div class="input-group">
                        <span class="input-addon">Minima</span>
                        <input type="number" step="0.01" name="pcaminima" value="<?= $h($old['scratch_pcaminima'] ?? '0'); ?>">
                    </div>
                </div>
                <div data-grade-profile-numeric-scale>
                    <div class="input-group">
                        <span class="input-addon">Maxima</span>
                        <input type="number" step="0.01" name="pcamaxima" value="<?= $h($old['scratch_pcamaxima'] ?? '10'); ?>">
                    </div>
                </div>
                <div data-grade-profile-numeric-scale>
                    <div class="input-group">
                        <span class="input-addon">Aprobacion</span>
                        <input type="number" step="0.01" name="pcaaprobacion" value="<?= $h($old['scratch_pcaaprobacion'] ?? '7'); ?>">
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Decimales</span>
                        <input type="number" name="pcadecimales" min="0" max="4" value="<?= $h($old['scratch_pcadecimales'] ?? '2'); ?>">
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Decimal</span>
                        <select name="pcametodo_decimal">
                            <option value="REDONDEO" <?= (string) ($old['scratch_pcametodo_decimal'] ?? 'REDONDEO') === 'REDONDEO' ? 'selected' : ''; ?>>Redondeo</option>
                            <option value="TRUNCAMIENTO" <?= (string) ($old['scratch_pcametodo_decimal'] ?? '') === 'TRUNCAMIENTO' ? 'selected' : ''; ?>>Truncamiento</option>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Alcance</span>
                        <select name="pasalcance">
                            <option value="" <?= $scratchScope === '' ? 'selected' : ''; ?>>Sin asignar</option>
                            <option value="NIVEL" <?= $scratchScope === 'NIVEL' ? 'selected' : ''; ?>>Nivel educativo</option>
                            <option value="GRADO" <?= $scratchScope === 'GRADO' ? 'selected' : ''; ?>>Grado</option>
                            <option value="CURSO" <?= $scratchScope === 'CURSO' ? 'selected' : ''; ?>>Curso</option>
                            <option value="MATERIA" <?= $scratchScope === 'MATERIA' ? 'selected' : ''; ?>>Materia del curso</option>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Destino</span>
                        <select name="target_id">
                            <option value="">Seleccione si asigna alcance</option>
                            <optgroup label="Niveles">
                                <?php foreach ($levels as $level): ?>
                                    <option value="<?= $h($level['nedid']); ?>" <?= $scratchScope === 'NIVEL' && $scratchTarget === (string) $level['nedid'] ? 'selected' : ''; ?>>
                                        <?= $h($level['nednombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Grados">
                                <?php foreach ($grades as $grade): ?>
                                    <option value="<?= $h($grade['graid']); ?>" <?= $scratchScope === 'GRADO' && $scratchTarget === (string) $grade['graid'] ? 'selected' : ''; ?>>
                                        <?= $h($grade['nednombre'] . ' | ' . $grade['granombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Cursos">
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= $h($course['curid']); ?>" <?= $scratchScope === 'CURSO' && $scratchTarget === (string) $course['curid'] ? 'selected' : ''; ?>>
                                        <?= $h($course['pledescripcion'] . ' | ' . $course['granombre'] . ' ' . $course['prlnombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Materias">
                                <?php foreach ($courseSubjects as $subject): ?>
                                    <option value="<?= $h($subject['mtcid']); ?>" <?= $scratchScope === 'MATERIA' && $scratchTarget === (string) $subject['mtcid'] ? 'selected' : ''; ?>>
                                        <?= $h($subject['pledescripcion'] . ' | ' . $subject['granombre'] . ' ' . $subject['prlnombre'] . ' | ' . $subject['asgnombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Descripcion</span>
                        <input type="text" name="pcadescripcion" value="<?= $h($old['scratch_pcadescripcion'] ?? ''); ?>" maxlength="250">
                    </div>
                </div>
                <div class="checkbox-stack">
                    <label class="checkbox-inline">
                        <input type="checkbox" name="pcapromedia_final" value="1" <?= (string) ($old['scratch_pcapromedia_final'] ?? '1') === '1' ? 'checked' : ''; ?>>
                        Promedia final
                    </label>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="pcaaplica_promocion" value="1" <?= (string) ($old['scratch_pcaaplica_promocion'] ?? '1') === '1' ? 'checked' : ''; ?>>
                        Aplica promocion
                    </label>
                </div>
            </div>
            <div class="actions-row">
                <button class="btn-primary btn-inline" type="submit">Crear perfil en blanco</button>
            </div>
        </form>
    <?php endif; ?>
    </div>
</section>

<section class="grade-profile-accordion-shell">
    <details class="grade-profile-accordion" id="perfiles-creados">
        <summary>
            <span>Plantillas disponibles</span>
            <i class="fa fa-chevron-down" aria-hidden="true"></i>
        </summary>
        <div class="grade-profile-accordion-body">

    <?php if (empty($templates)): ?>
        <div class="empty-state">No existen plantillas registradas.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Plantilla</th>
                        <th>Tipo</th>
                        <th>Subperiodos</th>
                        <th>Componentes</th>
                        <th>Escalas</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($templates as $template): ?>
                        <tr>
                            <td><span class="cell-title"><?= $h($template['pclnombre']); ?></span></td>
                            <td><?= $h($template['pcltipo_base']); ?></td>
                            <td><?= $h($template['total_subperiodos']); ?></td>
                            <td><?= $h($template['total_componentes']); ?></td>
                            <td><?= $h($template['total_escalas']); ?></td>
                            <td>
                                <a
                                    class="icon-button icon-button-edit"
                                    href="<?= $h(baseUrl('configuracion/academica/calificaciones/plantilla?id=' . (string) $template['pclid'])); ?>"
                                    title="Ver detalle"
                                    aria-label="Ver detalle"
                                >
                                    <i class="fa fa-eye" aria-hidden="true"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
        </div>
    </details>

    <details class="grade-profile-accordion">
        <summary>
            <span>Perfiles creados</span>
            <i class="fa fa-chevron-down" aria-hidden="true"></i>
        </summary>
        <div class="grade-profile-accordion-body">

    <?php if (empty($profiles)): ?>
        <div class="empty-state">Todavia no existen perfiles de calificacion creados.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Periodo</th>
                        <th>Perfil</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Vigencia</th>
                        <th>Subperiodos</th>
                        <th>Asignaciones</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($profiles as $profile): ?>
                        <tr>
                            <td><?= $h($profile['pledescripcion']); ?></td>
                            <td><span class="cell-title"><?= $h($profile['pcanombre']); ?></span></td>
                            <td><?= $h($profile['pcatipo_base']); ?></td>
                            <td><?= $h($profile['pcaestado']); ?></td>
                            <td><?= $h($profile['pcavigencia_desde'] . (($profile['pcavigencia_hasta'] ?? '') !== '' ? ' / ' . $profile['pcavigencia_hasta'] : '')); ?></td>
                            <td><?= $h($profile['total_subperiodos']); ?></td>
                            <td><?= $h($profile['total_asignaciones']); ?></td>
                            <td>
                                <a
                                    class="icon-button icon-button-edit"
                                    href="<?= $h(baseUrl('configuracion/academica/calificaciones/perfil?id=' . (string) $profile['pcaid'])); ?>"
                                    title="Ver perfil"
                                    aria-label="Ver perfil"
                                >
                                    <i class="fa fa-eye" aria-hidden="true"></i>
                                </a>
                                <form
                                    class="inline-delete-form"
                                    method="POST"
                                    action="<?= $h(baseUrl('configuracion/academica/calificaciones/perfil/eliminar')); ?>"
                                    data-grade-profile-delete-form
                                >
                                    <input type="hidden" name="pcaid" value="<?= $h($profile['pcaid']); ?>">
                                    <button class="icon-button icon-button-delete" type="submit" title="Eliminar perfil" aria-label="Eliminar perfil">
                                        <i class="fa fa-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
        </div>
    </details>
</section>

<dialog class="calendar-dialog grade-profile-delete-dialog" data-grade-profile-delete-dialog>
    <header class="security-assignment-header">
        <div>
            <h3>Eliminar perfil</h3>
            <p>Esta accion solo continuara si el perfil no tiene registros de notas asociados.</p>
        </div>
    </header>
    <p class="module-note">Si el perfil ya fue usado en actividades, calificaciones, resultados o promocion, el sistema bloqueara la eliminacion.</p>
    <div class="actions-row">
        <button class="btn-secondary btn-auto" type="button" data-grade-profile-delete-cancel>Cancelar</button>
        <button class="btn-primary btn-inline" type="button" data-grade-profile-delete-confirm>Eliminar perfil</button>
    </div>
</dialog>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
