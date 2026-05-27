<?php

declare(strict_types=1);

$additionalItems = is_array($items ?? null) ? $items : [];
$allConcepts = is_array($allConcepts ?? null) ? $allConcepts : [];
$selectedItemId = (int) ($selectedItemId ?? 0);
$requestedPanel = (string) ($_GET['panel'] ?? '');
$canCreateAdditionalItems = !empty($canCreateAdditionalItems);
$canEditAdditionalItems = !empty($canEditAdditionalItems);
$canRegisterAdditionalItemPayments = !empty($canRegisterAdditionalItemPayments);
$canManageAdditionalItemConcepts = $canCreateAdditionalItems || $canEditAdditionalItems;
$selectedRubrosMode = $selectedItemId > 0
    ? 'detail'
    : ($requestedPanel === 'concepts' && $canManageAdditionalItemConcepts ? 'concepts' : ($additionalItems === [] && $canCreateAdditionalItems ? 'form' : ($requestedPanel === 'form' && $canCreateAdditionalItems ? 'form' : 'list')));
$selectedConceptMode = $allConcepts === [] ? 'form' : 'list';

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static fn (mixed $value): string => '$' . number_format((float) $value, 2, '.', ',');
$concepts = is_array($concepts ?? null) ? $concepts : [];
$methods = is_array($methods ?? null) ? $methods : [];
$levels = is_array($levels ?? null) ? $levels : [];
$courses = is_array($courses ?? null) ? $courses : [];
$students = is_array($students ?? null) ? $students : [];
$assignments = is_array($assignments ?? null) ? $assignments : [];
$assignmentFilters = is_array($assignmentFilters ?? null) ? $assignmentFilters : [];
$assignmentPagination = is_array($assignmentPagination ?? null) ? $assignmentPagination : ['page' => 1, 'pages' => 1, 'total' => count($assignments), 'limit' => 25];
$assignmentStatuses = [
    '' => 'Todos los estados',
    'PENDIENTE' => 'Pendiente',
    'PAGADO' => 'Pagado',
    'VENCIDO' => 'Vencido',
    'EXONERADO' => 'Exonerado',
    'NO_APLICA' => 'No aplica',
    'ANULADO' => 'Anulado',
];
$scopeLabel = static function (array $item): string {
    $scope = (string) ($item['craalcance'] ?? '');

    if ($scope === 'TODOS') {
        return 'Todos';
    }

    if ($scope === 'NIVEL') {
        return 'Nivel: ' . (string) ($item['nednombre'] ?? '');
    }

    if ($scope === 'CURSO') {
        return 'Curso: ' . trim((string) (($item['granombre'] ?? '') . ' ' . ($item['prlnombre'] ?? '')));
    }

    if ($scope === 'ESTUDIANTE') {
        return 'Estudiante: ' . trim((string) (($item['perapellidos'] ?? '') . ' ' . ($item['pernombres'] ?? '')));
    }

    return 'Sin alcance';
};
$statusClass = static function (string $status): string {
    return match ($status) {
        'PAGADO', 'EXONERADO' => 'state-pill-active',
        'ANULADO', 'NO_APLICA' => 'state-pill-inactive',
        default => '',
    };
};
?>

<?php if (empty($currentPeriod)): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para gestionar rubros.</div>
<?php else: ?>
    <p class="module-note">Crea cobros eventuales y asigna rubros adicionales a estudiantes del periodo <?= $h($currentPeriod['pledescripcion'] ?? ''); ?>.</p>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <span><?= $h($success); ?></span>
            <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close><i class="fa fa-times" aria-hidden="true"></i></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <span><?= $h($error); ?></span>
            <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close><i class="fa fa-times" aria-hidden="true"></i></button>
        </div>
    <?php endif; ?>

    <section class="grade-config-view-stack">
    <section class="security-assignment-block grade-profile-creation-block" data-option-view-mode>
        <div class="grade-profile-mode-selector" role="radiogroup" aria-label="Vista de rubros adicionales">
            <?php if ($canCreateAdditionalItems): ?>
                <label class="grade-profile-mode-option">
                    <input type="radio" name="rubro_view_mode" value="form" <?= $selectedRubrosMode === 'form' ? 'checked' : ''; ?> data-option-view-radio>
                    <span>Nuevo rubro</span>
                </label>
            <?php endif; ?>
            <label class="grade-profile-mode-option">
                <input type="radio" name="rubro_view_mode" value="list" <?= $selectedRubrosMode === 'list' ? 'checked' : ''; ?> data-option-view-radio>
                <span>Rubros registrados</span>
            </label>
            <?php if ($canManageAdditionalItemConcepts): ?>
                <label class="grade-profile-mode-option">
                    <input type="radio" name="rubro_view_mode" value="concepts" <?= $selectedRubrosMode === 'concepts' ? 'checked' : ''; ?> data-option-view-radio>
                    <span>Conceptos</span>
                </label>
            <?php endif; ?>
            <?php if ($selectedItemId > 0): ?>
                <label class="grade-profile-mode-option">
                    <input type="radio" name="rubro_view_mode" value="detail" <?= $selectedRubrosMode === 'detail' ? 'checked' : ''; ?> data-option-view-radio>
                    <span>Detalle</span>
                </label>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($canCreateAdditionalItems): ?>
    <section class="security-assignment-block" data-option-view-panel="form" <?= $selectedRubrosMode === 'form' ? '' : 'hidden'; ?>>
        <header class="security-assignment-header">
            <div>
                <h3>Nuevo rubro</h3>
                <p>Define el valor y el grupo de estudiantes a quienes se asignara.</p>
            </div>
        </header>

        <form class="data-form compact-data-form" method="POST" action="<?= $h(baseUrl('contabilidad/rubros')); ?>">
            <?= csrfField(); ?>
            <div class="form-grid">
                <div class="form-group"><div class="input-group"><span class="input-addon">Concepto</span><select name="ccoid" required>
                    <option value="">Seleccione</option>
                    <?php foreach ($concepts as $concept): ?>
                        <option value="<?= $h($concept['ccoid'] ?? ''); ?>"><?= $h($concept['cconombre'] ?? ''); ?></option>
                    <?php endforeach; ?>
                </select></div></div>
                <div class="form-group"><div class="input-group"><span class="input-addon">Nombre</span><input type="text" name="crunombre" maxlength="150" required></div></div>
                <div class="form-group"><div class="input-group"><span class="input-addon">Valor</span><input type="number" name="cruvalor" min="0.01" step="0.01" required></div></div>
                <div class="form-group"><div class="input-group"><span class="input-addon">Fecha limite</span><input type="date" name="crufecha_limite"></div></div>
                <div class="form-group"><div class="input-group"><span class="input-addon">Alcance</span><select name="craalcance" data-rubro-scope required>
                    <option value="TODOS">Todos</option>
                    <option value="NIVEL">Nivel</option>
                    <option value="CURSO">Curso</option>
                    <option value="ESTUDIANTE">Estudiante</option>
                </select></div></div>
                <div class="form-group" data-rubro-target="NIVEL" hidden><div class="input-group"><span class="input-addon">Nivel</span><select name="nedid">
                    <option value="0">Seleccione</option>
                    <?php foreach ($levels as $level): ?>
                        <option value="<?= $h($level['nedid'] ?? ''); ?>"><?= $h($level['nednombre'] ?? ''); ?></option>
                    <?php endforeach; ?>
                </select></div></div>
                <div class="form-group" data-rubro-target="CURSO" hidden><div class="input-group"><span class="input-addon">Curso</span><select name="curid">
                    <option value="0">Seleccione</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= $h($course['curid'] ?? ''); ?>"><?= $h(trim((string) (($course['nednombre'] ?? '') . ' | ' . ($course['granombre'] ?? '') . ' ' . ($course['prlnombre'] ?? '')))); ?></option>
                    <?php endforeach; ?>
                </select></div></div>
                <div class="form-group" data-rubro-target="ESTUDIANTE" hidden><div class="input-group"><span class="input-addon">Estudiante</span><select name="matid">
                    <option value="0">Seleccione</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?= $h($student['matid'] ?? ''); ?>">
                            <?= $h(trim((string) (($student['perapellidos'] ?? '') . ' ' . ($student['pernombres'] ?? '') . ' - ' . ($student['granombre'] ?? '') . ' ' . ($student['prlnombre'] ?? '')))); ?>
                        </option>
                    <?php endforeach; ?>
                </select></div></div>
                <div class="form-group form-grid-wide"><div class="input-group"><span class="input-addon">Descripcion</span><textarea name="crudescripcion" maxlength="250" rows="2"></textarea></div></div>
            </div>
            <div class="actions-row">
                <button class="btn-primary btn-inline" type="submit">
                    <i class="fa fa-plus" aria-hidden="true"></i>
                    Crear rubro
                </button>
            </div>
        </form>
    </section>
    <?php endif; ?>

    <?php if ($canManageAdditionalItemConcepts): ?>
    <section class="security-assignment-block" data-option-view-panel="concepts" <?= $selectedRubrosMode === 'concepts' ? '' : 'hidden'; ?>>
        <header class="security-assignment-header">
            <div>
                <h3>Conceptos de rubros</h3>
                <p>Administra las opciones que aparecen en el campo concepto.</p>
            </div>
        </header>

        <section class="grade-config-view-stack">
        <section class="security-assignment-block grade-profile-creation-block" data-option-view-mode>
            <div class="grade-profile-mode-selector" role="radiogroup" aria-label="Vista de conceptos de rubros">
                <label class="grade-profile-mode-option">
                    <input type="radio" name="concept_view_mode" value="form" <?= $selectedConceptMode === 'form' ? 'checked' : ''; ?> data-option-view-radio>
                    <span>Nuevo concepto</span>
                </label>
                <label class="grade-profile-mode-option">
                    <input type="radio" name="concept_view_mode" value="list" <?= $selectedConceptMode === 'list' ? 'checked' : ''; ?> data-option-view-radio>
                    <span>Conceptos registrados</span>
                </label>
            </div>
        </section>

        <section data-option-view-panel="form" <?= $selectedConceptMode === 'form' ? '' : 'hidden'; ?>>
            <form class="data-form compact-data-form compact-data-form-narrow" method="POST" action="<?= $h(baseUrl('contabilidad/rubros/conceptos')); ?>">
                <?= csrfField(); ?>
                <div class="form-grid">
                    <div class="form-group"><div class="input-group"><span class="input-addon">Nombre</span><input type="text" name="cconombre" maxlength="150" required></div></div>
                    <div class="form-group form-grid-wide"><div class="input-group"><span class="input-addon">Descripcion</span><input type="text" name="ccodescripcion" maxlength="250"></div></div>
                </div>
                <div class="actions-row">
                    <button class="btn-primary btn-inline" type="submit">
                        <i class="fa fa-plus" aria-hidden="true"></i>
                        Agregar concepto
                    </button>
                </div>
            </form>
        </section>

        <section data-option-view-panel="list" <?= $selectedConceptMode === 'list' ? '' : 'hidden'; ?>>
            <?php if ($allConcepts === []): ?>
                <div class="empty-state">No existen conceptos de rubros registrados.</div>
            <?php else: ?>
                <div class="table-wrap">
                <table class="data-table compact-data-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Descripcion</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allConcepts as $concept): ?>
                            <?php $conceptFormId = 'concept-form-' . (int) ($concept['ccoid'] ?? 0); ?>
                            <tr data-concept-row>
                                <td><input class="table-input" form="<?= $h($conceptFormId); ?>" type="text" name="cconombre" value="<?= $h($concept['cconombre'] ?? ''); ?>" readonly required data-concept-input></td>
                                <td><input class="table-input" form="<?= $h($conceptFormId); ?>" type="text" name="ccodescripcion" value="<?= $h($concept['ccodescripcion'] ?? ''); ?>" readonly data-concept-input></td>
                                <td>
                                    <label class="concept-status-switch">
                                        <span data-concept-status-label><?= !empty($concept['ccoestado']) ? 'Activo' : 'Inactivo'; ?></span>
                                        <span class="switch-control switch-control-xsmall">
                                            <input form="<?= $h($conceptFormId); ?>" type="checkbox" name="ccoestado" value="1" <?= !empty($concept['ccoestado']) ? 'checked' : ''; ?> disabled data-concept-status>
                                            <span class="switch-slider switch-slider-xsmall" aria-hidden="true"></span>
                                        </span>
                                    </label>
                                </td>
                                <td>
                                    <form class="accounting-actions-inline" id="<?= $h($conceptFormId); ?>" method="POST" action="<?= $h(baseUrl('contabilidad/rubros/conceptos/actualizar')); ?>">
                                        <?= csrfField(); ?>
                                        <input type="hidden" name="ccoid" value="<?= $h($concept['ccoid'] ?? ''); ?>">
                                        <button class="icon-button icon-button-save" type="submit" title="Guardar" aria-label="Guardar" hidden data-concept-save>
                                            <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                    <button class="icon-button icon-button-edit" type="button" title="Editar" aria-label="Editar" data-concept-edit>
                                        <i class="fa fa-pencil" aria-hidden="true"></i>
                                    </button>
                                    <button class="icon-button icon-button-cancel" type="button" title="Cancelar" aria-label="Cancelar" hidden data-concept-cancel>
                                        <i class="fa fa-undo" aria-hidden="true"></i>
                                    </button>
                                    <form class="accounting-actions-inline" method="POST" action="<?= $h(baseUrl('contabilidad/rubros/conceptos/eliminar')); ?>" onsubmit="return confirm('Confirma que desea eliminar o desactivar este concepto?');">
                                        <?= csrfField(); ?>
                                        <input type="hidden" name="ccoid" value="<?= $h($concept['ccoid'] ?? ''); ?>">
                                        <button class="icon-button icon-button-delete" type="submit" title="Eliminar" aria-label="Eliminar" data-concept-delete>
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
        </section>
        </section>
    </section>
    <?php endif; ?>

    <section class="security-assignment-block" data-option-view-panel="list" <?= $selectedRubrosMode === 'list' ? '' : 'hidden'; ?>>
        <header class="security-assignment-header">
            <div>
                <h3>Rubros creados</h3>
                <p>Consulta el avance de cobro y abre el detalle por estudiante.</p>
            </div>
        </header>

        <?php if ($additionalItems === []): ?>
            <div class="empty-state">Todavia no existen rubros adicionales para este periodo.</div>
        <?php else: ?>
            <div class="table-wrap">
            <table class="data-table compact-data-table">
                <thead>
                    <tr>
                        <th>Rubro</th>
                        <th>Concepto</th>
                        <th>Valor</th>
                        <th>Alcance</th>
                        <th>Asignados</th>
                        <th>Pendientes</th>
                        <th>Saldo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($additionalItems as $item): ?>
                        <tr>
                            <td>
                                <strong><?= $h($item['crunombre'] ?? ''); ?></strong>
                                <span class="cell-subtitle"><?= $h(!empty($item['crufecha_limite']) ? 'Limite ' . $item['crufecha_limite'] : 'Sin fecha limite'); ?></span>
                            </td>
                            <td><?= $h($item['cconombre'] ?? ''); ?></td>
                            <td><?= $h($money($item['cruvalor'] ?? 0)); ?></td>
                            <td><?= $h($scopeLabel($item)); ?></td>
                            <td><?= $h($item['total_asignados'] ?? 0); ?></td>
                            <td><?= $h($item['total_pendientes'] ?? 0); ?></td>
                            <td><?= $h($money($item['valor_pendiente'] ?? 0)); ?></td>
                            <td>
                                <a class="icon-button icon-button-view" href="<?= $h(baseUrl('contabilidad/rubros?rubro=' . (int) ($item['cruid'] ?? 0))); ?>" title="Ver detalle" aria-label="Ver detalle">
                                    <i class="fa fa-search" aria-hidden="true"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($selectedItemId > 0): ?>
        <section class="security-assignment-block" data-option-view-panel="detail" <?= $selectedRubrosMode === 'detail' ? '' : 'hidden'; ?>>
            <header class="security-assignment-header">
                <div>
                    <h3>Detalle de asignacion</h3>
                    <p>Registra pagos internos o cierra rubros que no aplican.</p>
                </div>
            </header>

            <form class="toolbar toolbar-filter accounting-receipts-toolbar" method="GET" action="<?= $h(baseUrl('contabilidad/rubros')); ?>">
                <input type="hidden" name="rubro" value="<?= $h($selectedItemId); ?>">
                <div class="filter-box">
                    <label class="sr-only" for="rubro-assignment-search">Buscar estudiante</label>
                    <input id="rubro-assignment-search" type="search" name="q" value="<?= $h($assignmentFilters['q'] ?? ''); ?>" placeholder="Buscar estudiante">
                </div>
                <div class="filter-box filter-box-compact">
                    <label class="sr-only" for="rubro-assignment-status">Estado</label>
                    <select id="rubro-assignment-status" name="estado">
                        <?php foreach ($assignmentStatuses as $statusValue => $statusLabel): ?>
                            <option value="<?= $h($statusValue); ?>" <?= (string) ($assignmentFilters['estado'] ?? '') === $statusValue ? 'selected' : ''; ?>><?= $h($statusLabel); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-box filter-box-compact">
                    <label class="sr-only" for="rubro-assignment-course">Curso</label>
                    <select id="rubro-assignment-course" name="curso">
                        <option value="0">Todos los cursos</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $h($course['curid'] ?? ''); ?>" <?= (int) ($assignmentFilters['curso'] ?? 0) === (int) ($course['curid'] ?? 0) ? 'selected' : ''; ?>>
                                <?= $h(trim((string) (($course['nednombre'] ?? '') . ' | ' . ($course['granombre'] ?? '') . ' ' . ($course['prlnombre'] ?? '')))); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn-secondary btn-auto" type="submit">
                    <i class="fa fa-filter" aria-hidden="true"></i>
                    Filtrar
                </button>
                <span class="table-status"><?= count($assignments); ?> de <?= $h($assignmentPagination['total'] ?? count($assignments)); ?> registro(s)</span>
            </form>

            <?php if ($assignments === []): ?>
                <div class="empty-state">No hay estudiantes asignados con los filtros seleccionados.</div>
            <?php else: ?>
                <div class="table-wrap">
                <table class="data-table compact-data-table">
                    <thead>
                        <tr>
                            <th>Estudiante</th>
                            <th>Curso</th>
                            <th>Valor</th>
                            <th>Estado</th>
                            <th>Referencia</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $assignment): ?>
                            <?php $status = (string) ($assignment['creestado'] ?? ''); ?>
                            <tr>
                                <td>
                                    <strong><?= $h(trim((string) (($assignment['perapellidos'] ?? '') . ' ' . ($assignment['pernombres'] ?? '')))); ?></strong>
                                    <span class="cell-subtitle"><?= $h($assignment['percedula'] ?? ''); ?></span>
                                </td>
                                <td><?= $h(trim((string) (($assignment['granombre'] ?? '') . ' ' . ($assignment['prlnombre'] ?? '')))); ?></td>
                                <td><?= $h($money($assignment['crevalor'] ?? 0)); ?></td>
                                <td><span class="state-pill <?= $h($statusClass($status)); ?>"><?= $h($status); ?></span></td>
                                <td><?= $h($assignment['cpagreferencia'] ?? ''); ?></td>
                                <td>
                                    <?php if (in_array($status, ['PENDIENTE', 'VENCIDO'], true) && ($canRegisterAdditionalItemPayments || $canEditAdditionalItems)): ?>
                                        <form class="accounting-actions-inline" method="POST" action="<?= $h(baseUrl('contabilidad/rubros/cerrar')); ?>" data-rubro-close-form>
                                            <?= csrfField(); ?>
                                            <input type="hidden" name="cruid" value="<?= $h($selectedItemId); ?>">
                                            <input type="hidden" name="creid" value="<?= $h($assignment['creid'] ?? ''); ?>">
                                            <input type="hidden" name="return_query" value="<?= $h($_SERVER['QUERY_STRING'] ?? ''); ?>">
                                            <select class="table-input" name="estado" data-rubro-close-status>
                                                <?php if ($canRegisterAdditionalItemPayments || $canEditAdditionalItems): ?>
                                                    <option value="PAGADO">Pagado</option>
                                                <?php endif; ?>
                                                <?php if ($canEditAdditionalItems): ?>
                                                    <option value="EXONERADO">Exonerado</option>
                                                    <option value="NO_APLICA">No aplica</option>
                                                    <option value="ANULADO">Anulado</option>
                                                <?php endif; ?>
                                            </select>
                                            <select class="table-input" name="cmpid" data-rubro-payment-field>
                                                <option value="0">Metodo</option>
                                                <?php foreach ($methods as $method): ?>
                                                    <option value="<?= $h($method['cmpid'] ?? ''); ?>"><?= $h($method['cmpnombre'] ?? ''); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input class="table-input" type="text" name="referencia" maxlength="80" placeholder="Referencia" data-rubro-payment-field>
                                            <input class="table-input" type="text" name="observacion" maxlength="250" placeholder="Observacion">
                                            <button class="icon-button icon-button-save" type="submit" title="Guardar" aria-label="Guardar">
                                                <i class="fa fa-check" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="table-status">Cerrado</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php
                    $currentAssignmentPage = (int) ($assignmentPagination['page'] ?? 1);
                    $assignmentPages = (int) ($assignmentPagination['pages'] ?? 1);
                    $assignmentQuery = [
                        'rubro' => $selectedItemId,
                        'q' => (string) ($assignmentFilters['q'] ?? ''),
                        'estado' => (string) ($assignmentFilters['estado'] ?? ''),
                        'curso' => (int) ($assignmentFilters['curso'] ?? 0),
                        'limit' => (int) ($assignmentPagination['limit'] ?? 25),
                    ];
                    $previousAssignmentUrl = baseUrl('contabilidad/rubros?' . http_build_query($assignmentQuery + ['page' => max(1, $currentAssignmentPage - 1)]));
                    $nextAssignmentUrl = baseUrl('contabilidad/rubros?' . http_build_query($assignmentQuery + ['page' => min($assignmentPages, $currentAssignmentPage + 1)]));
                ?>
                <div class="actions-row accounting-pagination">
                    <a class="btn-secondary btn-auto <?= $currentAssignmentPage <= 1 ? 'is-disabled' : ''; ?>" href="<?= $h($previousAssignmentUrl); ?>">
                        <i class="fa fa-chevron-left" aria-hidden="true"></i>
                        Anterior
                    </a>
                    <span class="table-status">Pagina <?= $h($currentAssignmentPage); ?> de <?= $h($assignmentPages); ?></span>
                    <a class="btn-secondary btn-auto <?= $currentAssignmentPage >= $assignmentPages ? 'is-disabled' : ''; ?>" href="<?= $h($nextAssignmentUrl); ?>">
                        Siguiente
                        <i class="fa fa-chevron-right" aria-hidden="true"></i>
                    </a>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    </section>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const scope = document.querySelector('[data-rubro-scope]');
    const targets = Array.from(document.querySelectorAll('[data-rubro-target]'));

    const syncScope = function () {
        const value = scope ? scope.value : 'TODOS';
        targets.forEach(function (target) {
            target.hidden = target.getAttribute('data-rubro-target') !== value;
        });
    };

    if (scope) {
        scope.addEventListener('change', syncScope);
        syncScope();
    }

    document.querySelectorAll('[data-rubro-close-status]').forEach(function (select) {
        const form = select.closest('form');
        const fields = form ? form.querySelectorAll('[data-rubro-payment-field]') : [];
        const syncFields = function () {
            fields.forEach(function (field) {
                field.disabled = select.value !== 'PAGADO';
            });
        };

        select.addEventListener('change', syncFields);
        syncFields();
    });

    document.querySelectorAll('[data-rubro-close-form]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            const status = form.querySelector('[name="estado"]');
            const selectedStatus = status ? status.value : '';
            const messages = {
                PAGADO: 'Confirma que desea registrar este rubro como pagado?',
                EXONERADO: 'Confirma que desea exonerar este rubro?',
                NO_APLICA: 'Confirma que este rubro no aplica para el estudiante?',
                ANULADO: 'Confirma que desea anular este rubro?'
            };

            if (!window.confirm(messages[selectedStatus] || 'Confirma que desea actualizar este rubro?')) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-concept-row]').forEach(function (row) {
        const inputs = Array.from(row.querySelectorAll('[data-concept-input]'));
        const status = row.querySelector('[data-concept-status]');
        const statusLabel = row.querySelector('[data-concept-status-label]');
        const editButton = row.querySelector('[data-concept-edit]');
        const cancelButton = row.querySelector('[data-concept-cancel]');
        const saveButton = row.querySelector('[data-concept-save]');
        const deleteButton = row.querySelector('[data-concept-delete]');
        const originalValues = new Map();
        const originalStatus = status instanceof HTMLInputElement ? status.checked : false;

        inputs.forEach(function (input) {
            originalValues.set(input, input.value);
        });

        const setEditing = function (editing) {
            inputs.forEach(function (input) {
                input.readOnly = !editing;
            });

            if (status instanceof HTMLInputElement) {
                status.disabled = !editing;
            }

            if (editButton) editButton.hidden = editing;
            if (cancelButton) cancelButton.hidden = !editing;
            if (saveButton) saveButton.hidden = !editing;
            if (deleteButton) deleteButton.hidden = editing;
        };

        if (status instanceof HTMLInputElement && statusLabel instanceof HTMLElement) {
            status.addEventListener('change', function () {
                statusLabel.textContent = status.checked ? 'Activo' : 'Inactivo';
            });
        }

        if (editButton) {
            editButton.addEventListener('click', function () {
                setEditing(true);
                if (inputs[0]) {
                    inputs[0].focus();
                    inputs[0].select();
                }
            });
        }

        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                inputs.forEach(function (input) {
                    input.value = originalValues.get(input) || '';
                });

                if (status instanceof HTMLInputElement) {
                    status.checked = originalStatus;
                    status.dispatchEvent(new Event('change'));
                }

                setEditing(false);
            });
        }

        setEditing(false);
    });
});
</script>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
