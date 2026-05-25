<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$academicViews = is_array($academicViews ?? null) ? $academicViews : [];
$selectedAcademicView = (string) ($selectedAcademicView ?? ($academicViews[0]['key'] ?? ''));
$defaultStartDate = (string) ($currentPeriod['plefechainicio'] ?? date('Y-m-d'));
$assignedSubjectsByCourse = [];
$visibleAssignments = [];
$assignedTeachersBySubject = [];

foreach (($courseSubjects ?? []) as $courseSubject) {
    if (!empty($courseSubject['mtcestado'])) {
        $assignedSubjectsByCourse[(int) $courseSubject['curid']][] = (int) $courseSubject['asgid'];
    }
}

foreach (($activeCourseSubjects ?? []) as $subject) {
    foreach (($teacherAssignments[(int) $subject['mtcid']] ?? []) as $assignment) {
        $assignedTeachersBySubject[(int) $subject['mtcid']][] = (int) $assignment['perid'];
        $visibleAssignments[] = [
            'subject' => $subject,
            'assignment' => $assignment,
        ];
    }
}

$defaultCourseId = 0;
foreach (($activeCourses ?? []) as $course) {
    if (mb_strtoupper((string) ($course['prlnombre'] ?? '')) === 'A') {
        $defaultCourseId = (int) $course['curid'];
        break;
    }
}
if ($defaultCourseId === 0 && !empty($activeCourses)) {
    $defaultCourseId = (int) $activeCourses[0]['curid'];
}

$selectedGradeMode = !empty($gradeFormFeedback) || empty($grades) ? 'form' : 'list';
$selectedCourseMode = !empty($old['graid']) || !empty($old['prlid']) || empty($courses) ? 'form' : 'list';
$selectedAreaMode = empty($areas) ? 'form' : 'list';
$selectedSubjectMode = empty($subjects) ? 'form' : 'list';
$selectedCourseSubjectMode = empty($courseSubjects) ? 'form' : 'list';
$selectedTeacherAssignmentMode = empty($visibleAssignments) ? 'form' : 'list';
?>
<?php if ($academicViews === []): ?>
    <div class="empty-state">No tiene opciones de configuracion academica disponibles.</div>
<?php else: ?>
    <section class="security-assignment-block catalog-selector-block" data-academic-config-view-mode>
        <div class="grade-profile-mode-selector catalog-selector academic-config-selector" role="radiogroup" aria-label="Vista de configuracion academica">
            <?php foreach ($academicViews as $view): ?>
                <?php $viewKey = (string) ($view['key'] ?? ''); ?>
                <label class="grade-profile-mode-option">
                    <input
                        type="radio"
                        name="academic_config_view"
                        value="<?= $h($viewKey); ?>"
                        <?= $selectedAcademicView === $viewKey ? 'checked' : ''; ?>
                        data-academic-config-view-radio
                    >
                    <span><?= $h($view['label'] ?? ''); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </section>

    <section data-academic-config-view-panel="grados" <?= $selectedAcademicView === 'grados' ? '' : 'hidden'; ?>>
        <section class="security-assignment-block grade-profile-creation-block" data-option-view-mode>
            <div class="grade-profile-mode-selector" role="radiogroup" aria-label="Vista de grados">
                <label class="grade-profile-mode-option">
                    <input type="radio" name="grade_view_mode" value="form" <?= $selectedGradeMode === 'form' ? 'checked' : ''; ?> data-option-view-radio>
                    <span>Nuevo grado</span>
                </label>
                <label class="grade-profile-mode-option">
                    <input type="radio" name="grade_view_mode" value="list" <?= $selectedGradeMode === 'list' ? 'checked' : ''; ?> data-option-view-radio>
                    <span>Grados registrados</span>
                </label>
            </div>
        </section>

        <section class="security-assignment-block" id="grado-form" data-option-view-panel="form" <?= $selectedGradeMode === 'form' ? '' : 'hidden'; ?>>
            <?php if (empty($levels)): ?>
                <div class="empty-state">No existen niveles educativos disponibles. Registra primero los niveles en Configuracion &gt; Catalogos.</div>
            <?php else: ?>
                <form class="data-form" method="POST" action="<?= $h(baseUrl('grados')); ?>">
                    <?= csrfField(); ?>
                    <div class="form-grid">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-addon">Nivel</span>
                                <select name="nedid" required>
                                    <option value="">Seleccione una opcion</option>
                                    <?php foreach ($levels as $level): ?>
                                        <?php $levelId = (string) $level['nedid']; ?>
                                        <option value="<?= $h($levelId); ?>" <?= ($gradeOld['nedid'] ?? '') === $levelId ? 'selected' : ''; ?>>
                                            <?= $h($level['nednombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-addon">Grado</span>
                                <input name="granombre" placeholder="Ingrese el nombre del grado" required value="<?= $h($gradeOld['granombre'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="actions-row">
                        <button class="btn-primary btn-inline" type="submit">Guardar grado</button>
                    </div>
                </form>
            <?php endif; ?>
        </section>

        <section data-option-view-panel="list" <?= $selectedGradeMode === 'list' ? '' : 'hidden'; ?>>

        <div class="toolbar toolbar-filter">
            <div class="filter-box">
                <label class="sr-only" for="grade-search">Buscar grados</label>
                <input
                    id="grade-search"
                    type="search"
                    placeholder="Filtrar por nivel educativo o nombre del grado"
                    data-grade-search
                    data-grade-search-url="<?= $h(baseUrl('grados/buscar')); ?>"
                    autocomplete="off"
                >
            </div>
            <span class="filter-status" data-grade-search-status><?= count($grades ?? []); ?> registro(s)</span>
        </div>

        <div data-grade-list-wrapper <?= empty($grades) ? '' : 'hidden'; ?>>
            <div class="empty-state">Todavia no hay grados registrados.</div>
        </div>

        <section id="grados-registrados">
            <div class="table-wrap" data-grade-table-wrapper <?= empty($grades) ? 'hidden' : ''; ?>>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nivel educativo</th>
                            <th>Grado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody data-grade-table-body>
                        <?php require BASE_PATH . '/app/views/grados/_rows.php'; ?>
                    </tbody>
                </table>
            </div>
        </section>
        </section>
    </section>

    <section data-academic-config-view-panel="cursos" <?= $selectedAcademicView === 'cursos' ? '' : 'hidden'; ?>>
        <?php if ($currentPeriod === null): ?>
            <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para continuar.</div>
        <?php else: ?>
            <section class="security-assignment-block grade-profile-creation-block" data-option-view-mode>
                <div class="grade-profile-mode-selector" role="radiogroup" aria-label="Vista de cursos">
                    <label class="grade-profile-mode-option">
                        <input type="radio" name="course_view_mode" value="form" <?= $selectedCourseMode === 'form' ? 'checked' : ''; ?> data-option-view-radio>
                        <span>Nuevo curso</span>
                    </label>
                    <label class="grade-profile-mode-option">
                        <input type="radio" name="course_view_mode" value="list" <?= $selectedCourseMode === 'list' ? 'checked' : ''; ?> data-option-view-radio>
                        <span>Cursos registrados</span>
                    </label>
                </div>
            </section>

            <section class="security-assignment-block" data-option-view-panel="form" <?= $selectedCourseMode === 'form' ? '' : 'hidden'; ?>>
                <?php if (empty($grades) || empty($parallels)): ?>
                    <div class="empty-state">Para registrar cursos necesitas tener grados y paralelos disponibles en la base de datos.</div>
                <?php else: ?>
                    <form class="data-form" method="POST" action="<?= $h(baseUrl('cursos')); ?>">
                        <?= csrfField(); ?>
                        <div class="form-grid">
                            <div>
                                <div class="input-group">
                                    <span class="input-addon">Estado</span>
                                    <select name="curestado">
                                        <option value="1" <?= ($old['curestado'] ?? '1') === '1' ? 'selected' : ''; ?>>Activo</option>
                                        <option value="0" <?= ($old['curestado'] ?? '1') === '0' ? 'selected' : ''; ?>>Inactivo</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <div class="input-group">
                                    <span class="input-addon">Grado</span>
                                    <select name="graid" required>
                                        <option value="">Seleccione</option>
                                        <?php foreach ($grades as $grade): ?>
                                            <?php $gradeId = (string) $grade['graid']; ?>
                                            <option value="<?= $h($gradeId); ?>" <?= ($old['graid'] ?? '') === $gradeId ? 'selected' : ''; ?>>
                                                <?= $h($grade['nednombre'] . ' | ' . $grade['granombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <div class="input-group">
                                    <span class="input-addon">Paralelo</span>
                                    <select name="prlid" required>
                                        <option value="">Seleccione</option>
                                        <?php foreach ($parallels as $parallel): ?>
                                            <?php $parallelId = (string) $parallel['prlid']; ?>
                                            <option value="<?= $h($parallelId); ?>" <?= ($old['prlid'] ?? '') === $parallelId ? 'selected' : ''; ?>>
                                                <?= $h($parallel['prlnombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="actions-row">
                            <button class="btn-primary btn-inline" type="submit">Guardar curso</button>
                        </div>
                    </form>
                <?php endif; ?>
            </section>

            <section class="security-assignment-block" id="cursos-registrados" data-option-view-panel="list" <?= $selectedCourseMode === 'list' ? '' : 'hidden'; ?>>
                <?php if (empty($courses)): ?>
                    <div class="empty-state">Todavia no hay cursos registrados para este periodo.</div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nivel</th>
                                    <th>Grado</th>
                                    <th>Paralelo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td><?= $h($course['nednombre']); ?></td>
                                        <td><?= $h($course['granombre']); ?></td>
                                        <td><?= $h($course['prlnombre']); ?></td>
                                        <td>
                                            <form method="POST" action="<?= $h(baseUrl('cursos/estado')); ?>" class="status-switch-form">
                                                <?= csrfField(); ?>
                                                <input type="hidden" name="curid" value="<?= $h($course['curid']); ?>">
                                                <input type="hidden" name="curestado" value="<?= !empty($course['curestado']) ? '0' : '1'; ?>">
                                                <button class="status-switch <?= !empty($course['curestado']) ? 'is-active' : ''; ?>" type="submit" title="<?= !empty($course['curestado']) ? 'Inactivar curso' : 'Activar curso'; ?>" aria-label="<?= !empty($course['curestado']) ? 'Inactivar curso' : 'Activar curso'; ?>">
                                                    <span class="status-switch-track"><span class="status-switch-thumb"></span></span>
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
        <?php endif; ?>
    </section>

    <section data-academic-config-view-panel="areas" <?= $selectedAcademicView === 'areas' ? '' : 'hidden'; ?>>
        <section class="security-assignment-block grade-profile-creation-block" data-option-view-mode>
            <div class="grade-profile-mode-selector" role="radiogroup" aria-label="Vista de areas academicas">
                <label class="grade-profile-mode-option">
                    <input type="radio" name="area_view_mode" value="form" <?= $selectedAreaMode === 'form' ? 'checked' : ''; ?> data-option-view-radio>
                    <span>Nueva area</span>
                </label>
                <label class="grade-profile-mode-option">
                    <input type="radio" name="area_view_mode" value="list" <?= $selectedAreaMode === 'list' ? 'checked' : ''; ?> data-option-view-radio>
                    <span>Areas registradas</span>
                </label>
            </div>
        </section>

        <section class="security-assignment-block" data-option-view-panel="form" <?= $selectedAreaMode === 'form' ? '' : 'hidden'; ?>>
            <form class="data-form" method="POST" action="<?= $h(baseUrl('configuracion/academica/areas')); ?>">
                <?= csrfField(); ?>
                <div class="form-grid">
                    <div class="form-group-full">
                        <div class="input-group">
                            <span class="input-addon">Nombre</span>
                            <input type="text" name="areanombre" maxlength="100" required>
                        </div>
                    </div>
                </div>
                <div class="actions-row">
                    <button class="btn-primary btn-inline" type="submit">Guardar area</button>
                </div>
            </form>
        </section>
        <section class="security-assignment-block" data-option-view-panel="list" <?= $selectedAreaMode === 'list' ? '' : 'hidden'; ?>>
            <?php if (empty($areas)): ?>
                <div class="empty-state">Todavia no hay areas academicas registradas.</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data-table security-table">
                        <thead>
                            <tr>
                                <th>Area</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($areas as $area): ?>
                                <tr data-security-row>
                                    <td>
                                        <form id="area-form-<?= $h($area['areaid']); ?>" method="POST" action="<?= $h(baseUrl('configuracion/academica/areas/actualizar')); ?>" data-security-edit-form>
                                            <?= csrfField(); ?>
                                            <input type="hidden" name="areaid" value="<?= $h($area['areaid']); ?>">
                                            <input class="security-inline-input" type="text" name="areanombre" maxlength="100" value="<?= $h($area['areanombre']); ?>" readonly required data-security-input>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST" action="<?= $h(baseUrl('configuracion/academica/areas/estado')); ?>" class="status-switch-form">
                                            <?= csrfField(); ?>
                                            <input type="hidden" name="areaid" value="<?= $h($area['areaid']); ?>">
                                            <input type="hidden" name="areaestado" value="<?= !empty($area['areaestado']) ? '0' : '1'; ?>">
                                            <button class="status-switch <?= !empty($area['areaestado']) ? 'is-active' : ''; ?>" type="submit" title="<?= !empty($area['areaestado']) ? 'Inactivar area' : 'Activar area'; ?>" aria-label="<?= !empty($area['areaestado']) ? 'Inactivar area' : 'Activar area'; ?>">
                                                <span class="status-switch-track"><span class="status-switch-thumb"></span></span>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="actions-group" data-security-actions>
                                            <button class="icon-button icon-button-edit" type="button" title="Editar" aria-label="Editar" data-security-edit>
                                                <i class="fa fa-pencil" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                        <div class="actions-group" hidden data-security-edit-actions>
                                            <button class="icon-button icon-button-save" type="submit" form="area-form-<?= $h($area['areaid']); ?>" title="Guardar" aria-label="Guardar">
                                                <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                            </button>
                                            <button class="icon-button icon-button-cancel" type="button" title="Cancelar" aria-label="Cancelar" data-security-cancel>
                                                <i class="fa fa-times" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </section>

    <section data-academic-config-view-panel="asignaturas" <?= $selectedAcademicView === 'asignaturas' ? '' : 'hidden'; ?>>
        <section class="security-assignment-block grade-profile-creation-block" data-option-view-mode>
            <div class="grade-profile-mode-selector" role="radiogroup" aria-label="Vista de asignaturas">
                <label class="grade-profile-mode-option">
                    <input type="radio" name="subject_view_mode" value="form" <?= $selectedSubjectMode === 'form' ? 'checked' : ''; ?> data-option-view-radio>
                    <span>Nueva asignatura</span>
                </label>
                <label class="grade-profile-mode-option">
                    <input type="radio" name="subject_view_mode" value="list" <?= $selectedSubjectMode === 'list' ? 'checked' : ''; ?> data-option-view-radio>
                    <span>Asignaturas registradas</span>
                </label>
            </div>
        </section>

        <section class="security-assignment-block" data-option-view-panel="form" <?= $selectedSubjectMode === 'form' ? '' : 'hidden'; ?>>
            <?php if (empty($activeAreas)): ?>
                <div class="empty-state">No existen areas activas. Registra o activa un area academica primero.</div>
            <?php else: ?>
                <form class="data-form" method="POST" action="<?= $h(baseUrl('configuracion/academica/asignaturas')); ?>">
                    <?= csrfField(); ?>
                    <div class="form-grid">
                        <div>
                            <div class="input-group">
                                <span class="input-addon">Area</span>
                                <select name="areaid" required>
                                    <option value="">Seleccione</option>
                                    <?php foreach ($activeAreas as $area): ?>
                                        <option value="<?= $h($area['areaid']); ?>"><?= $h($area['areanombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div>
                            <div class="input-group">
                                <span class="input-addon">Asignatura</span>
                                <input type="text" name="asgnombre" maxlength="120" required>
                            </div>
                        </div>
                    </div>
                    <div class="actions-row">
                        <button class="btn-primary btn-inline" type="submit">Guardar asignatura</button>
                    </div>
                </form>
            <?php endif; ?>
        </section>
        <section class="security-assignment-block" data-option-view-panel="list" <?= $selectedSubjectMode === 'list' ? '' : 'hidden'; ?>>
            <?php if (empty($subjects)): ?>
                <div class="empty-state">Todavia no hay asignaturas registradas.</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data-table security-table">
                        <thead>
                            <tr>
                                <th>Area</th>
                                <th>Asignatura</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subjects as $subject): ?>
                                <tr data-security-row>
                                    <td>
                                        <form id="subject-form-<?= $h($subject['asgid']); ?>" method="POST" action="<?= $h(baseUrl('configuracion/academica/asignaturas/actualizar')); ?>" data-security-edit-form>
                                            <?= csrfField(); ?>
                                            <input type="hidden" name="asgid" value="<?= $h($subject['asgid']); ?>">
                                            <select class="security-inline-select" name="areaid" required disabled data-security-input>
                                                <?php foreach ($areas as $area): ?>
                                                    <option value="<?= $h($area['areaid']); ?>" <?= (int) $area['areaid'] === (int) $subject['areaid'] ? 'selected' : ''; ?>>
                                                        <?= $h($area['areanombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <input class="security-inline-input" form="subject-form-<?= $h($subject['asgid']); ?>" type="text" name="asgnombre" maxlength="120" value="<?= $h($subject['asgnombre']); ?>" readonly required data-security-input>
                                    </td>
                                    <td>
                                        <form method="POST" action="<?= $h(baseUrl('configuracion/academica/asignaturas/estado')); ?>" class="status-switch-form">
                                            <?= csrfField(); ?>
                                            <input type="hidden" name="asgid" value="<?= $h($subject['asgid']); ?>">
                                            <input type="hidden" name="asgestado" value="<?= !empty($subject['asgestado']) ? '0' : '1'; ?>">
                                            <button class="status-switch <?= !empty($subject['asgestado']) ? 'is-active' : ''; ?>" type="submit" title="<?= !empty($subject['asgestado']) ? 'Inactivar asignatura' : 'Activar asignatura'; ?>" aria-label="<?= !empty($subject['asgestado']) ? 'Inactivar asignatura' : 'Activar asignatura'; ?>">
                                                <span class="status-switch-track"><span class="status-switch-thumb"></span></span>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="actions-group" data-security-actions>
                                            <button class="icon-button icon-button-edit" type="button" title="Editar" aria-label="Editar" data-security-edit>
                                                <i class="fa fa-pencil" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                        <div class="actions-group" hidden data-security-edit-actions>
                                            <button class="icon-button icon-button-save" type="submit" form="subject-form-<?= $h($subject['asgid']); ?>" title="Guardar" aria-label="Guardar">
                                                <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                            </button>
                                            <button class="icon-button icon-button-cancel" type="button" title="Cancelar" aria-label="Cancelar" data-security-cancel>
                                                <i class="fa fa-times" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </section>

    <section data-academic-config-view-panel="materias" <?= $selectedAcademicView === 'materias' ? '' : 'hidden'; ?>>
        <?php if ($currentPeriod === null): ?>
            <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para continuar.</div>
        <?php else: ?>
            <section class="security-assignment-block grade-profile-creation-block" data-option-view-mode>
                <div class="grade-profile-mode-selector" role="radiogroup" aria-label="Vista de materias por curso">
                    <label class="grade-profile-mode-option">
                        <input type="radio" name="course_subject_view_mode" value="form" <?= $selectedCourseSubjectMode === 'form' ? 'checked' : ''; ?> data-option-view-radio>
                        <span>Nueva materia por curso</span>
                    </label>
                    <label class="grade-profile-mode-option">
                        <input type="radio" name="course_subject_view_mode" value="list" <?= $selectedCourseSubjectMode === 'list' ? 'checked' : ''; ?> data-option-view-radio>
                        <span>Materias registradas</span>
                    </label>
                </div>
            </section>

            <section class="security-assignment-block" data-option-view-panel="form" <?= $selectedCourseSubjectMode === 'form' ? '' : 'hidden'; ?>>
                <?php if (empty($activeCourses)): ?>
                    <div class="empty-state">No existen cursos activos para el periodo actual.</div>
                <?php elseif (empty($activeSubjects)): ?>
                    <div class="empty-state">No existen asignaturas activas disponibles.</div>
                <?php else: ?>
                    <form class="data-form" method="POST" action="<?= $h(baseUrl('configuracion/academica/materias-curso')); ?>" data-course-subject-bulk-form>
                        <?= csrfField(); ?>
                        <div class="form-grid">
                            <div>
                                <div class="input-group">
                                    <span class="input-addon">Curso</span>
                                    <select name="curid" required data-course-subject-course>
                                        <option value="">Seleccione</option>
                                        <?php foreach ($activeCourses as $course): ?>
                                            <option value="<?= $h($course['curid']); ?>" <?= $defaultCourseId === (int) $course['curid'] ? 'selected' : ''; ?>>
                                                <?= $h($course['nednombre'] . ' | ' . $course['granombre'] . ' ' . $course['prlnombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <div class="input-group">
                                    <span class="input-addon">Inicio</span>
                                    <input type="date" name="mtcfecha_inicio" value="<?= $h($defaultStartDate); ?>" required>
                                </div>
                            </div>
                            <div>
                                <div class="input-group">
                                    <span class="input-addon">Orden</span>
                                    <input type="number" name="mtcorden" min="1" max="99">
                                </div>
                            </div>
                        </div>
                        <div class="table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Asignar</th>
                                        <th>Area</th>
                                        <th>Asignatura</th>
                                        <th>Estado en curso</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activeSubjects as $subject): ?>
                                        <?php $assignedCourses = []; ?>
                                        <?php foreach ($assignedSubjectsByCourse as $courseId => $assignedSubjectIds): ?>
                                            <?php if (in_array((int) $subject['asgid'], $assignedSubjectIds, true)) {
                                                $assignedCourses[] = $courseId;
                                            } ?>
                                        <?php endforeach; ?>
                                        <tr data-course-subject-row data-assigned-courses="<?= $h(implode(',', $assignedCourses)); ?>">
                                            <td><input type="checkbox" name="asgid[]" value="<?= $h($subject['asgid']); ?>" data-course-subject-checkbox></td>
                                            <td><?= $h($subject['areanombre']); ?></td>
                                            <td><span class="cell-title"><?= $h($subject['asgnombre']); ?></span></td>
                                            <td><span class="cell-subtitle" data-course-subject-status>Disponible</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="actions-row">
                            <button class="btn-primary btn-inline" type="submit">Guardar materias seleccionadas</button>
                        </div>
                    </form>
                <?php endif; ?>
            </section>

            <section class="security-assignment-block" data-option-view-panel="list" <?= $selectedCourseSubjectMode === 'list' ? '' : 'hidden'; ?>>
                <?php if (empty($courseSubjects)): ?>
                    <div class="empty-state">Todavia no existen materias asignadas a cursos en este periodo.</div>
                <?php else: ?>
                    <div class="form-grid" data-course-subject-filter data-course-subject-filter-url="<?= $h(baseUrl('configuracion/academica/materias-curso/buscar')); ?>">
                        <div>
                            <div class="input-group">
                                <span class="input-addon">Curso</span>
                                <select data-course-subject-filter-course>
                                    <option value="">Todos</option>
                                    <?php
                                    $seenCourseFilter = [];
                                    foreach ($courseSubjects as $subject):
                                        $courseId = (int) $subject['curid'];
                                        if (isset($seenCourseFilter[$courseId])) {
                                            continue;
                                        }
                                        $seenCourseFilter[$courseId] = true;
                                    ?>
                                        <option value="<?= $h($courseId); ?>"><?= $h($subject['granombre'] . ' ' . $subject['prlnombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div>
                            <div class="input-group">
                                <span class="input-addon">Area</span>
                                <select data-course-subject-filter-area>
                                    <option value="">Todas</option>
                                    <?php
                                    $seenAreaFilter = [];
                                    foreach ($courseSubjects as $subject):
                                        $areaId = (int) $subject['areaid'];
                                        if (isset($seenAreaFilter[$areaId])) {
                                            continue;
                                        }
                                        $seenAreaFilter[$areaId] = true;
                                    ?>
                                        <option value="<?= $h($areaId); ?>"><?= $h($subject['areanombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div>
                            <div class="input-group">
                                <span class="input-addon">Asignatura</span>
                                <select data-course-subject-filter-subject>
                                    <option value="">Todas</option>
                                    <?php
                                    $seenSubjectFilter = [];
                                    foreach ($courseSubjects as $subject):
                                        $subjectId = (int) $subject['asgid'];
                                        if (isset($seenSubjectFilter[$subjectId])) {
                                            continue;
                                        }
                                        $seenSubjectFilter[$subjectId] = true;
                                    ?>
                                        <option value="<?= $h($subjectId); ?>"><?= $h($subject['asgnombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <p class="cell-subtitle" data-course-subject-filter-status><?= $h(count($courseSubjects)); ?> registro(s)</p>
                    <div class="table-wrap" data-course-subject-filter-table>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Curso</th>
                                    <th>Area</th>
                                    <th>Asignatura</th>
                                    <th>Inicio</th>
                                    <th>Orden</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody data-course-subject-filter-body>
                                <?php require BASE_PATH . '/app/views/configuracion/_materias_curso_rows.php'; ?>
                            </tbody>
                        </table>
                    </div>
                    <div data-course-subject-filter-empty hidden></div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </section>

    <section data-academic-config-view-panel="docentes" <?= $selectedAcademicView === 'docentes' ? '' : 'hidden'; ?>>
        <?php if ($currentPeriod === null): ?>
            <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para continuar.</div>
        <?php else: ?>
            <section class="security-assignment-block grade-profile-creation-block" data-option-view-mode>
                <div class="grade-profile-mode-selector" role="radiogroup" aria-label="Vista de asignacion de docentes">
                    <label class="grade-profile-mode-option">
                        <input type="radio" name="teacher_assignment_view_mode" value="form" <?= $selectedTeacherAssignmentMode === 'form' ? 'checked' : ''; ?> data-option-view-radio>
                        <span>Nueva asignacion</span>
                    </label>
                    <label class="grade-profile-mode-option">
                        <input type="radio" name="teacher_assignment_view_mode" value="list" <?= $selectedTeacherAssignmentMode === 'list' ? 'checked' : ''; ?> data-option-view-radio>
                        <span>Asignaciones registradas</span>
                    </label>
                </div>
            </section>

            <section class="security-assignment-block" data-option-view-panel="form" <?= $selectedTeacherAssignmentMode === 'form' ? '' : 'hidden'; ?>>
                <?php if (empty($activeCourseSubjects)): ?>
                    <div class="empty-state">No existen materias activas por curso. Configuralas primero en Materias por curso.</div>
                <?php elseif (empty($teachers)): ?>
                    <div class="empty-state">No existen docentes activos. Registra personal con tipo Docente antes de continuar.</div>
                <?php else: ?>
                    <form class="data-form" method="POST" action="<?= $h(baseUrl('configuracion/academica/docentes')); ?>" data-teacher-subject-bulk-form>
                        <?= csrfField(); ?>
                        <div class="form-grid">
                            <div>
                                <div class="input-group">
                                    <span class="input-addon">Docente</span>
                                    <select name="perid" required data-teacher-subject-teacher>
                                        <option value="">Seleccione</option>
                                        <?php foreach ($teachers as $teacher): ?>
                                            <option value="<?= $h($teacher['perid']); ?>">
                                                <?= $h($teacher['perapellidos'] . ' ' . $teacher['pernombres'] . ' | ' . $teacher['percedula']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <div class="input-group">
                                    <span class="input-addon">Filtrar curso</span>
                                    <select data-teacher-subject-course-filter>
                                        <option value="">Todos los cursos</option>
                                        <?php
                                        $seenCourses = [];
                                        foreach ($activeCourseSubjects as $subject):
                                            $courseId = (int) $subject['curid'];
                                            if (isset($seenCourses[$courseId])) {
                                                continue;
                                            }
                                            $seenCourses[$courseId] = true;
                                        ?>
                                            <option value="<?= $h($courseId); ?>"><?= $h($subject['granombre'] . ' ' . $subject['prlnombre']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <div class="input-group">
                                    <span class="input-addon">Inicio</span>
                                    <input type="date" name="mcdfecha_inicio" value="<?= $h($defaultStartDate); ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Asignar</th>
                                        <th>Curso</th>
                                        <th>Area</th>
                                        <th>Materia</th>
                                        <th>Estado docente</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activeCourseSubjects as $subject): ?>
                                        <tr data-teacher-subject-row data-course-id="<?= $h($subject['curid']); ?>" data-assigned-teachers="<?= $h(implode(',', $assignedTeachersBySubject[(int) $subject['mtcid']] ?? [])); ?>">
                                            <td><input type="checkbox" name="mtcid[]" value="<?= $h($subject['mtcid']); ?>" data-teacher-subject-checkbox></td>
                                            <td><?= $h($subject['granombre'] . ' ' . $subject['prlnombre']); ?></td>
                                            <td><?= $h($subject['areanombre']); ?></td>
                                            <td><span class="cell-title"><?= $h($subject['asgnombre']); ?></span></td>
                                            <td><span class="cell-subtitle" data-teacher-subject-status>Disponible</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="actions-row">
                            <button class="btn-primary btn-inline" type="submit">Asignar materias seleccionadas</button>
                        </div>
                    </form>
                <?php endif; ?>
            </section>

            <section class="security-assignment-block" data-option-view-panel="list" <?= $selectedTeacherAssignmentMode === 'list' ? '' : 'hidden'; ?>>
                <?php if (empty($visibleAssignments)): ?>
                    <div class="empty-state">Todavia no hay docentes designados en este periodo.</div>
                <?php else: ?>
                    <div class="form-grid" data-teacher-assignment-filter>
                        <div>
                            <div class="input-group">
                                <span class="input-addon">Docente</span>
                                <select data-teacher-assignment-filter-teacher>
                                    <option value="">Todos</option>
                                    <?php
                                    $seenAssignmentTeachers = [];
                                    foreach ($visibleAssignments as $row):
                                        $assignment = $row['assignment'];
                                        $teacherId = (int) $assignment['perid'];
                                        if (isset($seenAssignmentTeachers[$teacherId])) {
                                            continue;
                                        }
                                        $seenAssignmentTeachers[$teacherId] = true;
                                    ?>
                                        <option value="<?= $h($teacherId); ?>"><?= $h($assignment['perapellidos'] . ' ' . $assignment['pernombres']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div>
                            <div class="input-group">
                                <span class="input-addon">Curso</span>
                                <select data-teacher-assignment-filter-course>
                                    <option value="">Todos</option>
                                    <?php
                                    $seenAssignmentCourses = [];
                                    foreach ($visibleAssignments as $row):
                                        $subject = $row['subject'];
                                        $courseId = (int) $subject['curid'];
                                        if (isset($seenAssignmentCourses[$courseId])) {
                                            continue;
                                        }
                                        $seenAssignmentCourses[$courseId] = true;
                                    ?>
                                        <option value="<?= $h($courseId); ?>"><?= $h($subject['granombre'] . ' ' . $subject['prlnombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <p class="cell-subtitle" data-teacher-assignment-filter-status><?= $h(count($visibleAssignments)); ?> registro(s)</p>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Curso</th>
                                    <th>Materia</th>
                                    <th>Docente</th>
                                    <th>Inicio</th>
                                    <th>Accion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($visibleAssignments as $row): ?>
                                    <?php $subject = $row['subject']; $assignment = $row['assignment']; ?>
                                    <tr data-teacher-assignment-row data-teacher-id="<?= $h($assignment['perid']); ?>" data-course-id="<?= $h($subject['curid']); ?>">
                                        <td><?= $h($subject['granombre'] . ' ' . $subject['prlnombre']); ?></td>
                                        <td><?= $h($subject['asgnombre']); ?></td>
                                        <td><?= $h($assignment['perapellidos'] . ' ' . $assignment['pernombres']); ?></td>
                                        <td><?= $h($assignment['mcdfecha_inicio']); ?></td>
                                        <td>
                                            <form method="POST" action="<?= $h(baseUrl('configuracion/academica/docentes/retirar')); ?>">
                                                <?= csrfField(); ?>
                                                <input type="hidden" name="mcdid" value="<?= $h($assignment['mcdid']); ?>">
                                                <button class="btn-secondary btn-inline" type="submit">Retirar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="empty-state" data-teacher-assignment-filter-empty hidden>No se encontraron asignaciones con esos filtros.</div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
