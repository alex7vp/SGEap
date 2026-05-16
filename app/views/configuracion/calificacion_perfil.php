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
$subjectConfigurations = $detail['subjectConfigurations'] ?? [];
$subjectGroups = $detail['subjectGroups'] ?? [];
$promotionTramos = $detail['promotionTramos'] ?? [];
$extraordinaryInstances = $detail['extraordinaryInstances'] ?? [];
$levels = $levels ?? [];
$grades = $grades ?? [];
$courses = $courses ?? [];
$courseSubjects = $courseSubjects ?? [];
$profileId = (int) ($profile['pcaid'] ?? 0);
$isDraft = (string) ($profile['pcaestado'] ?? '') === 'BORRADOR';
$promotionResults = ['PROMOVIDO', 'SUPLETORIO', 'RECUPERACION', 'EXAMEN_GRACIA', 'NO_PROMOVIDO'];
$instanceStates = ['BORRADOR', 'ACTIVA', 'CERRADA', 'ANULADA'];
$instanceScopes = ['MATERIA', 'PROMEDIO_GENERAL'];
$subjectRecordTypes = ['CUANTITATIVO', 'CUALITATIVO', 'AMBITOS_DESTREZAS'];
$subjectDisplayTypes = ['CUANTITATIVA', 'CUALITATIVA', 'MIXTA'];
$subjectGroupCalculationModes = ['PROMEDIO_SIMPLE', 'PROMEDIO_PONDERADO', 'SUMA'];
$subjectGroupDisplayModes = ['GRUPO', 'REPRESENTANTE'];
$subjectConfigurationAreas = [];
foreach ($subjectConfigurations as $subjectConfiguration) {
    $areaId = (int) ($subjectConfiguration['areaid'] ?? 0);
    if ($areaId > 0) {
        $subjectConfigurationAreas[$areaId] = (string) ($subjectConfiguration['areanombre'] ?? '');
    }
}
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

<form method="POST" action="<?= $h(baseUrl('configuracion/academica/calificaciones/perfil/asignaciones')); ?>" id="asignaciones" class="grade-profile-edit-form">
    <input type="hidden" name="pcaid" value="<?= $h($profileId); ?>">
    <section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3>Asignaciones</h3>
            <p><?= $isDraft ? 'Pulsa Editar para ajustar alcances, prioridades o retirar asignaciones.' : 'Perfil en solo lectura porque ya no esta en borrador.'; ?></p>
        </div>
        <?php if ($isDraft): ?>
            <button class="btn-secondary btn-auto grade-profile-edit-button" type="button" data-grade-profile-edit>
                <i class="fa fa-pencil" aria-hidden="true"></i>
                Editar
            </button>
        <?php endif; ?>
    </header>

    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Alcance</th><th>Destino</th><th>Prioridad</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php foreach ($assignments as $assignment): ?>
                    <?php $assignmentId = (int) $assignment['pasid']; ?>
                    <tr>
                        <td><?= $h($assignment['pasalcance']); ?></td>
                        <td><span class="cell-title"><?= $h($assignment['destino'] ?? ''); ?></span></td>
                        <td>
                            <input type="number" name="assignments[<?= $h($assignmentId); ?>][pasprioridad]" min="1" value="<?= $h($assignment['pasprioridad']); ?>" disabled data-grade-profile-field>
                        </td>
                        <td><?= !empty($assignment['pasestado']) ? 'Activo' : 'Inactivo'; ?></td>
                        <td>
                            <?php if ($isDraft): ?>
                                <label class="checkbox-inline" hidden data-grade-profile-edit-control>
                                    <input type="checkbox" name="assignments[<?= $h($assignmentId); ?>][delete]" value="1" disabled data-grade-profile-field>
                                    Retirar
                                </label>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($isDraft): ?>
                    <tr hidden data-grade-profile-edit-control>
                        <td>
                            <select name="new_assignments[0][pasalcance]" disabled data-grade-profile-field>
                                <option value="">Nueva asignacion</option>
                                <option value="NIVEL">Nivel educativo</option>
                                <option value="GRADO">Grado</option>
                                <option value="CURSO">Curso</option>
                                <option value="MATERIA">Materia del curso</option>
                            </select>
                        </td>
                        <td>
                            <select name="new_assignments[0][target_id]" disabled data-grade-profile-field>
                                <option value="">Seleccione destino</option>
                                <optgroup label="Niveles">
                                    <?php foreach ($levels as $level): ?>
                                        <option value="<?= $h($level['nedid']); ?>"><?= $h($level['nednombre']); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Grados">
                                    <?php foreach ($grades as $grade): ?>
                                        <option value="<?= $h($grade['graid']); ?>"><?= $h($grade['nednombre'] . ' | ' . $grade['granombre']); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Cursos">
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?= $h($course['curid']); ?>"><?= $h($course['pledescripcion'] . ' | ' . $course['granombre'] . ' ' . $course['prlnombre']); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Materias">
                                    <?php foreach ($courseSubjects as $subject): ?>
                                        <option value="<?= $h($subject['mtcid']); ?>"><?= $h($subject['pledescripcion'] . ' | ' . $subject['granombre'] . ' ' . $subject['prlnombre'] . ' | ' . $subject['asgnombre']); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </td>
                        <td><input type="number" name="new_assignments[0][pasprioridad]" min="1" value="1" disabled data-grade-profile-field></td>
                        <td>Nuevo</td>
                        <td></td>
                    </tr>
                <?php elseif (empty($assignments)): ?>
                    <tr><td colspan="5">Este perfil todavia no tiene asignaciones.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($isDraft): ?>
        <div class="actions-row" hidden data-grade-profile-save-actions>
            <button class="btn-primary btn-inline" type="submit">Guardar asignaciones</button>
        </div>
    <?php endif; ?>
    </section>
</form>

<form method="POST" action="<?= $h(baseUrl('configuracion/academica/calificaciones/perfil/materias')); ?>" id="materias" class="grade-profile-edit-form">
    <input type="hidden" name="pcaid" value="<?= $h($profileId); ?>">
    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Materias</h3>
                <p><?= $isDraft ? 'Pulsa Editar para definir como registra, visualiza y promedia cada materia del perfil.' : 'Perfil en solo lectura porque ya no esta en borrador.'; ?></p>
            </div>
            <?php if ($isDraft && !empty($subjectConfigurations)): ?>
                <button class="btn-secondary btn-auto grade-profile-edit-button" type="button" data-grade-profile-edit>
                    <i class="fa fa-pencil" aria-hidden="true"></i>
                    Editar
                </button>
            <?php endif; ?>
        </header>

        <?php if (empty($subjectConfigurations)): ?>
            <div class="empty-state">No hay materias aplicables. Agrega asignaciones al perfil para configurar materias.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Curso</th>
                            <th>Materia</th>
                            <th>Registro</th>
                            <th>Visualizacion</th>
                            <th>Promedia</th>
                            <th>Libreta</th>
                            <th>Equiv.</th>
                            <th>Activo</th>
                            <th>Observacion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjectConfigurations as $subject): ?>
                            <?php $courseSubjectId = (int) $subject['mtcid']; ?>
                            <tr>
                                <td>
                                    <span class="cell-title"><?= $h($subject['granombre'] . ' ' . $subject['prlnombre']); ?></span>
                                    <span class="cell-subtitle"><?= $h($subject['nednombre']); ?></span>
                                </td>
                                <td>
                                    <span class="cell-title"><?= $h($subject['asgnombre']); ?></span>
                                    <span class="cell-subtitle"><?= $h($subject['areanombre'] . ' | ' . $subject['pasalcance']); ?></span>
                                </td>
                                <td>
                                    <select name="subjects[<?= $h($courseSubjectId); ?>][mcctipo_registro]" disabled data-grade-profile-field>
                                        <?php foreach ($subjectRecordTypes as $type): ?>
                                            <option value="<?= $h($type); ?>" <?= (string) $subject['mcctipo_registro'] === $type ? 'selected' : ''; ?>><?= $h($type); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="subjects[<?= $h($courseSubjectId); ?>][mcctipo_visualizacion]" disabled data-grade-profile-field>
                                        <?php foreach ($subjectDisplayTypes as $type): ?>
                                            <option value="<?= $h($type); ?>" <?= (string) $subject['mcctipo_visualizacion'] === $type ? 'selected' : ''; ?>><?= $h($type); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="checkbox" name="subjects[<?= $h($courseSubjectId); ?>][mccpromediable]" value="1" <?= !empty($subject['mccpromediable']) ? 'checked' : ''; ?> disabled data-grade-profile-field></td>
                                <td><input type="checkbox" name="subjects[<?= $h($courseSubjectId); ?>][mccvisible_libreta]" value="1" <?= !empty($subject['mccvisible_libreta']) ? 'checked' : ''; ?> disabled data-grade-profile-field></td>
                                <td><input type="checkbox" name="subjects[<?= $h($courseSubjectId); ?>][mccusa_equivalencia]" value="1" <?= !empty($subject['mccusa_equivalencia']) ? 'checked' : ''; ?> disabled data-grade-profile-field></td>
                                <td><input type="checkbox" name="subjects[<?= $h($courseSubjectId); ?>][mccestado]" value="1" <?= !empty($subject['mccestado']) ? 'checked' : ''; ?> disabled data-grade-profile-field></td>
                                <td><input type="text" name="subjects[<?= $h($courseSubjectId); ?>][mccobservacion]" maxlength="250" value="<?= $h($subject['mccobservacion'] ?? ''); ?>" disabled data-grade-profile-field></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($isDraft): ?>
                <div class="actions-row" hidden data-grade-profile-save-actions>
                    <button class="btn-primary btn-inline" type="submit">Guardar materias</button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</form>

<form method="POST" action="<?= $h(baseUrl('configuracion/academica/calificaciones/perfil/grupos-materias')); ?>" id="grupos-materias" class="grade-profile-edit-form">
    <input type="hidden" name="pcaid" value="<?= $h($profileId); ?>">
    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Grupos de materias</h3>
                <p><?= $isDraft ? 'Agrupa materias que deben producir una sola nota academica, como Ingles, Science y Language.' : 'Perfil en solo lectura porque ya no esta en borrador.'; ?></p>
            </div>
            <?php if ($isDraft && !empty($subjectConfigurations)): ?>
                <button class="btn-secondary btn-auto grade-profile-edit-button" type="button" data-grade-profile-edit>
                    <i class="fa fa-pencil" aria-hidden="true"></i>
                    Editar
                </button>
            <?php endif; ?>
        </header>

        <?php if (empty($subjectConfigurations)): ?>
            <div class="empty-state">No hay materias aplicables. Configura primero las asignaciones del perfil.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Grupo</th>
                            <th>Area</th>
                            <th>Calculo</th>
                            <th>Visualizacion</th>
                            <th>Representante</th>
                            <th>Promedia</th>
                            <th>Libreta</th>
                            <th>Orden</th>
                            <th>Estado</th>
                            <th>Materias</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjectGroups as $group): ?>
                            <?php $groupId = (int) $group['gmcid']; ?>
                            <?php $groupSubjectIds = array_map(static fn (array $detail): int => (int) $detail['mtcid'], $group['details'] ?? []); ?>
                            <tr>
                                <td>
                                    <input type="text" name="groups[<?= $h($groupId); ?>][gmcnombre]" maxlength="120" value="<?= $h($group['gmcnombre']); ?>" disabled data-grade-profile-field>
                                    <input type="text" name="groups[<?= $h($groupId); ?>][gmcdescripcion]" maxlength="250" value="<?= $h($group['gmcdescripcion'] ?? ''); ?>" disabled data-grade-profile-field>
                                    <input type="hidden" name="groups[<?= $h($groupId); ?>][delete]" value="0" data-subject-group-delete-input>
                                </td>
                                <td>
                                    <select name="groups[<?= $h($groupId); ?>][areaid]" disabled data-grade-profile-field>
                                        <?php foreach ($subjectConfigurationAreas as $areaId => $areaName): ?>
                                            <option value="<?= $h($areaId); ?>" <?= (int) ($group['areaid'] ?? 0) === (int) $areaId ? 'selected' : ''; ?>><?= $h($areaName); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="groups[<?= $h($groupId); ?>][gmcmodo_calculo]" disabled data-grade-profile-field>
                                        <?php foreach ($subjectGroupCalculationModes as $mode): ?>
                                            <option value="<?= $h($mode); ?>" <?= (string) $group['gmcmodo_calculo'] === $mode ? 'selected' : ''; ?>><?= $h($mode); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="groups[<?= $h($groupId); ?>][gmcvisualizacion]" disabled data-grade-profile-field>
                                        <?php foreach ($subjectGroupDisplayModes as $mode): ?>
                                            <option value="<?= $h($mode); ?>" <?= (string) $group['gmcvisualizacion'] === $mode ? 'selected' : ''; ?>><?= $h($mode); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="groups[<?= $h($groupId); ?>][gmcmtcid_representante]" disabled data-grade-profile-field>
                                        <option value="">Sin representante</option>
                                        <?php foreach ($subjectConfigurations as $subject): ?>
                                            <option value="<?= $h($subject['mtcid']); ?>" <?= (int) ($group['gmcmtcid_representante'] ?? 0) === (int) $subject['mtcid'] ? 'selected' : ''; ?>>
                                                <?= $h($subject['granombre'] . ' ' . $subject['prlnombre'] . ' | ' . $subject['asgnombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="checkbox" name="groups[<?= $h($groupId); ?>][gmcpromediable]" value="1" <?= !empty($group['gmcpromediable']) ? 'checked' : ''; ?> disabled data-grade-profile-field></td>
                                <td><input type="checkbox" name="groups[<?= $h($groupId); ?>][gmcvisible_libreta]" value="1" <?= !empty($group['gmcvisible_libreta']) ? 'checked' : ''; ?> disabled data-grade-profile-field></td>
                                <td><input type="number" name="groups[<?= $h($groupId); ?>][gmcorden]" min="1" value="<?= $h($group['gmcorden']); ?>" disabled data-grade-profile-field></td>
                                <td><input type="checkbox" name="groups[<?= $h($groupId); ?>][gmcestado]" value="1" <?= !empty($group['gmcestado']) ? 'checked' : ''; ?> disabled data-grade-profile-field></td>
                                <td>
                                    <div data-subject-group-readonly-list>
                                        <?php foreach (($group['details'] ?? []) as $detail): ?>
                                            <span class="cell-subtitle"><?= $h($detail['asgnombre'] . ' | ' . $detail['granombre'] . ' ' . $detail['prlnombre']); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <div hidden data-grade-profile-edit-control>
                                    <?php foreach ($subjectConfigurations as $subject): ?>
                                        <?php $checked = in_array((int) $subject['mtcid'], $groupSubjectIds, true); ?>
                                        <label class="checkbox-inline">
                                            <input
                                                type="checkbox"
                                                name="groups[<?= $h($groupId); ?>][mtcid][]"
                                                value="<?= $h($subject['mtcid']); ?>"
                                                <?= $checked ? 'checked' : ''; ?>
                                                disabled
                                                data-grade-profile-field
                                            >
                                            <?= $h($subject['asgnombre'] . ' | ' . $subject['granombre'] . ' ' . $subject['prlnombre']); ?>
                                        </label>
                                    <?php endforeach; ?>
                                    </div>
                                    <?php if ($isDraft): ?>
                                        <button class="icon-button icon-button-delete" type="button" title="Borrar grupo" aria-label="Borrar grupo" hidden data-grade-profile-edit-control data-subject-group-delete>
                                            <i class="fa fa-trash" aria-hidden="true"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($isDraft): ?>
                            <tr hidden data-subject-group-new-row>
                                <td>
                                    <input type="text" name="new_groups[0][gmcnombre]" maxlength="120" placeholder="Nombre del grupo" disabled data-grade-profile-field>
                                    <input type="text" name="new_groups[0][gmcdescripcion]" maxlength="250" placeholder="Descripcion" disabled data-grade-profile-field>
                                </td>
                                <td>
                                    <select name="new_groups[0][areaid]" disabled data-grade-profile-field>
                                        <option value="">Seleccione</option>
                                        <?php foreach ($subjectConfigurationAreas as $areaId => $areaName): ?>
                                            <option value="<?= $h($areaId); ?>"><?= $h($areaName); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="new_groups[0][gmcmodo_calculo]" disabled data-grade-profile-field>
                                        <?php foreach ($subjectGroupCalculationModes as $mode): ?>
                                            <option value="<?= $h($mode); ?>"><?= $h($mode); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="new_groups[0][gmcvisualizacion]" disabled data-grade-profile-field>
                                        <?php foreach ($subjectGroupDisplayModes as $mode): ?>
                                            <option value="<?= $h($mode); ?>"><?= $h($mode); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="new_groups[0][gmcmtcid_representante]" disabled data-grade-profile-field>
                                        <option value="">Sin representante</option>
                                        <?php foreach ($subjectConfigurations as $subject): ?>
                                            <option value="<?= $h($subject['mtcid']); ?>"><?= $h($subject['granombre'] . ' ' . $subject['prlnombre'] . ' | ' . $subject['asgnombre']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="checkbox" name="new_groups[0][gmcpromediable]" value="1" checked disabled data-grade-profile-field></td>
                                <td><input type="checkbox" name="new_groups[0][gmcvisible_libreta]" value="1" checked disabled data-grade-profile-field></td>
                                <td><input type="number" name="new_groups[0][gmcorden]" min="1" value="<?= $h(count($subjectGroups) + 1); ?>" disabled data-grade-profile-field></td>
                                <td><input type="checkbox" name="new_groups[0][gmcestado]" value="1" checked disabled data-grade-profile-field></td>
                                <td>
                                    <?php foreach ($subjectConfigurations as $subject): ?>
                                        <label class="checkbox-inline">
                                            <input
                                                type="checkbox"
                                                name="new_groups[0][mtcid][]"
                                                value="<?= $h($subject['mtcid']); ?>"
                                                disabled
                                                data-grade-profile-field
                                            >
                                            <?= $h($subject['asgnombre'] . ' | ' . $subject['granombre'] . ' ' . $subject['prlnombre']); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php elseif (empty($subjectGroups)): ?>
                            <tr><td colspan="10">Este perfil no tiene grupos de materias.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($isDraft): ?>
                <div class="actions-row" hidden data-grade-profile-save-actions>
                    <button class="btn-secondary btn-auto" type="button" data-subject-group-add>
                        <i class="fa fa-plus" aria-hidden="true"></i>
                        Agregar grupo
                    </button>
                    <button class="btn-primary btn-inline" type="submit">Guardar grupos</button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</form>

<form method="POST" action="<?= $h(baseUrl('configuracion/academica/calificaciones/perfil/escala')); ?>" id="escala" class="grade-profile-edit-form">
    <input type="hidden" name="pcaid" value="<?= $h($profileId); ?>">
    <section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3>Escala cualitativa</h3>
            <p><?= $isDraft ? 'Pulsa Editar para ajustar equivalencias, rangos y estado de cada escala.' : 'Perfil en solo lectura porque ya no esta en borrador.'; ?></p>
        </div>
        <?php if ($isDraft): ?>
            <button class="btn-secondary btn-auto grade-profile-edit-button" type="button" data-grade-profile-edit>
                <i class="fa fa-pencil" aria-hidden="true"></i>
                Editar
            </button>
        <?php endif; ?>
    </header>

    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Codigo</th><th>Nombre</th><th>Descripcion</th><th>Minimo</th><th>Maximo</th><th>Orden</th><th>Activo</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php foreach ($scales as $scale): ?>
                    <?php $scaleId = (int) $scale['ecaid']; ?>
                    <tr>
                        <td><input type="text" name="scales[<?= $h($scaleId); ?>][ecacodigo]" maxlength="20" value="<?= $h($scale['ecacodigo']); ?>" disabled data-grade-profile-field></td>
                        <td><input type="text" name="scales[<?= $h($scaleId); ?>][ecanombre]" maxlength="80" value="<?= $h($scale['ecanombre']); ?>" disabled data-grade-profile-field></td>
                        <td><input type="text" name="scales[<?= $h($scaleId); ?>][ecadescripcion]" maxlength="250" value="<?= $h($scale['ecadescripcion'] ?? ''); ?>" disabled data-grade-profile-field></td>
                        <td><input type="number" step="0.01" name="scales[<?= $h($scaleId); ?>][ecavalor_minimo]" value="<?= $h($scale['ecavalor_minimo'] ?? ''); ?>" disabled data-grade-profile-field></td>
                        <td><input type="number" step="0.01" name="scales[<?= $h($scaleId); ?>][ecavalor_maximo]" value="<?= $h($scale['ecavalor_maximo'] ?? ''); ?>" disabled data-grade-profile-field></td>
                        <td><input type="number" min="1" name="scales[<?= $h($scaleId); ?>][ecaorden]" value="<?= $h($scale['ecaorden']); ?>" disabled data-grade-profile-field></td>
                        <td><input type="checkbox" name="scales[<?= $h($scaleId); ?>][ecaestado]" value="1" <?= !empty($scale['ecaestado']) ? 'checked' : ''; ?> disabled data-grade-profile-field></td>
                        <td>
                            <?php if ($isDraft): ?>
                                <label class="checkbox-inline" hidden data-grade-profile-edit-control>
                                    <input type="checkbox" name="scales[<?= $h($scaleId); ?>][delete]" value="1" disabled data-grade-profile-field>
                                    Borrar
                                </label>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($isDraft): ?>
                    <tr hidden data-grade-profile-edit-control>
                        <td><input type="text" name="new_scales[0][ecacodigo]" maxlength="20" placeholder="Codigo" disabled data-grade-profile-field></td>
                        <td><input type="text" name="new_scales[0][ecanombre]" maxlength="80" placeholder="Nombre" disabled data-grade-profile-field></td>
                        <td><input type="text" name="new_scales[0][ecadescripcion]" maxlength="250" placeholder="Descripcion" disabled data-grade-profile-field></td>
                        <td><input type="number" step="0.01" name="new_scales[0][ecavalor_minimo]" disabled data-grade-profile-field></td>
                        <td><input type="number" step="0.01" name="new_scales[0][ecavalor_maximo]" disabled data-grade-profile-field></td>
                        <td><input type="number" min="1" name="new_scales[0][ecaorden]" value="<?= $h(count($scales) + 1); ?>" disabled data-grade-profile-field></td>
                        <td><input type="checkbox" name="new_scales[0][ecaestado]" value="1" checked disabled data-grade-profile-field></td>
                        <td>Nuevo</td>
                    </tr>
                <?php elseif (empty($scales)): ?>
                    <tr><td colspan="8">Este perfil no tiene escala cualitativa.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($isDraft): ?>
        <div class="actions-row" hidden data-grade-profile-save-actions>
            <button class="btn-primary btn-inline" type="submit">Guardar escala</button>
        </div>
    <?php endif; ?>
    </section>
</form>

<section class="security-assignment-block" id="promocion">
    <header class="security-assignment-header">
        <div>
            <h3>Promocion</h3>
            <p><?= $isDraft ? 'Edita tramos e instancias extraordinarias por separado.' : 'Perfil en solo lectura porque ya no esta en borrador.'; ?></p>
        </div>
    </header>

    <form method="POST" action="<?= $h(baseUrl('configuracion/academica/calificaciones/perfil/promocion')); ?>" class="grade-profile-edit-form">
        <input type="hidden" name="pcaid" value="<?= $h($profileId); ?>">
        <div class="security-assignment-header">
            <div>
                <h4 class="section-subtitle">Tramos</h4>
            </div>
            <?php if ($isDraft): ?>
                <button class="btn-secondary btn-auto grade-profile-edit-button" type="button" data-grade-profile-edit>
                    <i class="fa fa-pencil" aria-hidden="true"></i>
                    Editar
                </button>
            <?php endif; ?>
        </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Orden</th><th>Minimo</th><th>Maximo</th><th>Resultado</th><th>Extraordinaria</th><th>Activo</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php foreach ($promotionTramos as $tramo): ?>
                    <?php $tramoId = (int) $tramo['rptid']; ?>
                    <tr>
                        <td><input type="number" min="1" name="promotion_tramos[<?= $h($tramoId); ?>][rptorden]" value="<?= $h($tramo['rptorden']); ?>" disabled data-grade-profile-field></td>
                        <td><input type="number" step="0.01" name="promotion_tramos[<?= $h($tramoId); ?>][rptnota_minima]" value="<?= $h($tramo['rptnota_minima']); ?>" disabled data-grade-profile-field></td>
                        <td><input type="number" step="0.01" name="promotion_tramos[<?= $h($tramoId); ?>][rptnota_maxima]" value="<?= $h($tramo['rptnota_maxima']); ?>" disabled data-grade-profile-field></td>
                        <td>
                            <select name="promotion_tramos[<?= $h($tramoId); ?>][rptresultado]" disabled data-grade-profile-field>
                                <?php foreach ($promotionResults as $result): ?>
                                    <option value="<?= $h($result); ?>" <?= (string) $tramo['rptresultado'] === $result ? 'selected' : ''; ?>><?= $h($result); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="checkbox" name="promotion_tramos[<?= $h($tramoId); ?>][rpthabilita_extraordinaria]" value="1" <?= !empty($tramo['rpthabilita_extraordinaria']) ? 'checked' : ''; ?> disabled data-grade-profile-field></td>
                        <td><input type="checkbox" name="promotion_tramos[<?= $h($tramoId); ?>][rptestado]" value="1" <?= !empty($tramo['rptestado']) ? 'checked' : ''; ?> disabled data-grade-profile-field></td>
                        <td>
                            <?php if ($isDraft): ?>
                                <label class="checkbox-inline" hidden data-grade-profile-edit-control>
                                    <input type="checkbox" name="promotion_tramos[<?= $h($tramoId); ?>][delete]" value="1" disabled data-grade-profile-field>
                                    Borrar
                                </label>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($isDraft): ?>
                    <tr hidden data-grade-profile-edit-control>
                        <td><input type="number" min="1" name="new_promotion_tramos[0][rptorden]" value="<?= $h(count($promotionTramos) + 1); ?>" disabled data-grade-profile-field></td>
                        <td><input type="number" step="0.01" name="new_promotion_tramos[0][rptnota_minima]" disabled data-grade-profile-field></td>
                        <td><input type="number" step="0.01" name="new_promotion_tramos[0][rptnota_maxima]" disabled data-grade-profile-field></td>
                        <td>
                            <select name="new_promotion_tramos[0][rptresultado]" disabled data-grade-profile-field>
                                <option value="">Nuevo tramo</option>
                                <?php foreach ($promotionResults as $result): ?>
                                    <option value="<?= $h($result); ?>"><?= $h($result); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="checkbox" name="new_promotion_tramos[0][rpthabilita_extraordinaria]" value="1" disabled data-grade-profile-field></td>
                        <td><input type="checkbox" name="new_promotion_tramos[0][rptestado]" value="1" checked disabled data-grade-profile-field></td>
                        <td>Nuevo</td>
                    </tr>
                <?php elseif (empty($promotionTramos)): ?>
                    <tr><td colspan="7">Este perfil no tiene tramos de promocion.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

        <?php if ($isDraft): ?>
            <div class="actions-row" hidden data-grade-profile-save-actions>
                <button class="btn-primary btn-inline" type="submit">Guardar tramos</button>
            </div>
        <?php endif; ?>
    </form>

    <form method="POST" action="<?= $h(baseUrl('configuracion/academica/calificaciones/perfil/promocion')); ?>" class="grade-profile-edit-form">
        <input type="hidden" name="pcaid" value="<?= $h($profileId); ?>">
        <div class="security-assignment-header">
            <div>
                <h4 class="section-subtitle">Instancias extraordinarias</h4>
            </div>
            <?php if ($isDraft): ?>
                <button class="btn-secondary btn-auto grade-profile-edit-button" type="button" data-grade-profile-edit>
                    <i class="fa fa-pencil" aria-hidden="true"></i>
                    Editar
                </button>
            <?php endif; ?>
        </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Nombre</th><th>Orden</th><th>Estado</th><th>Aplica</th><th>Habilita</th><th>Aprueba</th><th>Nota final</th><th>Siguiente</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php foreach ($extraordinaryInstances as $instance): ?>
                    <?php $instanceId = (int) $instance['iexid']; ?>
                    <tr>
                        <td><input type="text" name="extraordinary_instances[<?= $h($instanceId); ?>][iexnombre]" maxlength="100" value="<?= $h($instance['iexnombre']); ?>" disabled data-grade-profile-field></td>
                        <td><input type="number" min="1" name="extraordinary_instances[<?= $h($instanceId); ?>][iexorden]" value="<?= $h($instance['iexorden']); ?>" disabled data-grade-profile-field></td>
                        <td>
                            <select name="extraordinary_instances[<?= $h($instanceId); ?>][iexestado]" disabled data-grade-profile-field>
                                <?php foreach ($instanceStates as $state): ?>
                                    <option value="<?= $h($state); ?>" <?= (string) $instance['iexestado'] === $state ? 'selected' : ''; ?>><?= $h($state); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select name="extraordinary_instances[<?= $h($instanceId); ?>][iexaplica_sobre]" disabled data-grade-profile-field>
                                <?php foreach ($instanceScopes as $scope): ?>
                                    <option value="<?= $h($scope); ?>" <?= (string) $instance['iexaplica_sobre'] === $scope ? 'selected' : ''; ?>><?= $h($scope); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="number" step="0.01" name="extraordinary_instances[<?= $h($instanceId); ?>][iexnota_habilita_minima]" value="<?= $h($instance['iexnota_habilita_minima']); ?>" disabled data-grade-profile-field>
                            <input type="number" step="0.01" name="extraordinary_instances[<?= $h($instanceId); ?>][iexnota_habilita_maxima]" value="<?= $h($instance['iexnota_habilita_maxima']); ?>" disabled data-grade-profile-field>
                        </td>
                        <td><input type="number" step="0.01" name="extraordinary_instances[<?= $h($instanceId); ?>][iexnota_minima_aprobar]" value="<?= $h($instance['iexnota_minima_aprobar']); ?>" disabled data-grade-profile-field></td>
                        <td><input type="number" step="0.01" name="extraordinary_instances[<?= $h($instanceId); ?>][iexnota_final_aprobado]" value="<?= $h($instance['iexnota_final_aprobado'] ?? ''); ?>" disabled data-grade-profile-field></td>
                        <td><input type="checkbox" name="extraordinary_instances[<?= $h($instanceId); ?>][iexpermite_siguiente]" value="1" <?= !empty($instance['iexpermite_siguiente']) ? 'checked' : ''; ?> disabled data-grade-profile-field></td>
                        <td>
                            <?php if ($isDraft): ?>
                                <label class="checkbox-inline" hidden data-grade-profile-edit-control>
                                    <input type="checkbox" name="extraordinary_instances[<?= $h($instanceId); ?>][delete]" value="1" disabled data-grade-profile-field>
                                    Borrar
                                </label>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($isDraft): ?>
                    <tr hidden data-grade-profile-edit-control>
                        <td><input type="text" name="new_extraordinary_instances[0][iexnombre]" maxlength="100" placeholder="Nueva instancia" disabled data-grade-profile-field></td>
                        <td><input type="number" min="1" name="new_extraordinary_instances[0][iexorden]" value="<?= $h(count($extraordinaryInstances) + 1); ?>" disabled data-grade-profile-field></td>
                        <td>
                            <select name="new_extraordinary_instances[0][iexestado]" disabled data-grade-profile-field>
                                <?php foreach ($instanceStates as $state): ?>
                                    <option value="<?= $h($state); ?>" <?= $state === 'ACTIVA' ? 'selected' : ''; ?>><?= $h($state); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select name="new_extraordinary_instances[0][iexaplica_sobre]" disabled data-grade-profile-field>
                                <?php foreach ($instanceScopes as $scope): ?>
                                    <option value="<?= $h($scope); ?>"><?= $h($scope); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="number" step="0.01" name="new_extraordinary_instances[0][iexnota_habilita_minima]" disabled data-grade-profile-field>
                            <input type="number" step="0.01" name="new_extraordinary_instances[0][iexnota_habilita_maxima]" disabled data-grade-profile-field>
                        </td>
                        <td><input type="number" step="0.01" name="new_extraordinary_instances[0][iexnota_minima_aprobar]" disabled data-grade-profile-field></td>
                        <td><input type="number" step="0.01" name="new_extraordinary_instances[0][iexnota_final_aprobado]" disabled data-grade-profile-field></td>
                        <td><input type="checkbox" name="new_extraordinary_instances[0][iexpermite_siguiente]" value="1" disabled data-grade-profile-field></td>
                        <td>Nuevo</td>
                    </tr>
                <?php elseif (empty($extraordinaryInstances)): ?>
                    <tr><td colspan="9">Este perfil no tiene instancias extraordinarias.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

        <?php if ($isDraft): ?>
            <div class="actions-row" hidden data-grade-profile-save-actions>
                <button class="btn-primary btn-inline" type="submit">Guardar instancias</button>
            </div>
        <?php endif; ?>
    </form>
</section>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
