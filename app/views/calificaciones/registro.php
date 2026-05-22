<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$subjects = is_array($subjects ?? null) ? $subjects : [];
$courses = is_array($courses ?? null) ? $courses : [];
$subperiods = is_array($subperiods ?? null) ? $subperiods : [];
$components = is_array($components ?? null) ? $components : [];
$activities = is_array($activities ?? null) ? $activities : [];
$grades = is_array($grades ?? null) ? $grades : [];
$students = is_array($students ?? null) ? $students : [];
$selectedSubject = is_array($selectedSubject ?? null) ? $selectedSubject : false;
$selectedCourse = is_array($selectedCourse ?? null) ? $selectedCourse : false;
$selectedCourseId = (int) ($selectedCourseId ?? 0);
$useAdministrativeSelection = !empty($useAdministrativeSelection);
$canEditSelectedSubject = !empty($canEditSelectedSubject);
$canAuthorizeGradeEntry = !empty($canAuthorizeGradeEntry);
$gradeEntryAuthorization = is_array($gradeEntryAuthorization ?? null) ? $gradeEntryAuthorization : false;
$showFinalAverages = (bool) ($showFinalAverages ?? false);
$today = (string) ($today ?? date('Y-m-d'));
$selectedSubperiod = false;
$selectedSubperiodInRange = false;
$selectedSubperiodAuthorized = false;
$selectedSubperiodCanRegister = false;
$componentPrefixes = [];
$gradeMinimum = is_array($selectedSubject) && is_numeric($selectedSubject['pcaminima'] ?? null) ? (float) $selectedSubject['pcaminima'] : 0.0;
$gradeMaximum = is_array($selectedSubject) && is_numeric($selectedSubject['pcamaxima'] ?? null) ? (float) $selectedSubject['pcamaxima'] : 10.0;

foreach ($subperiods as $subperiod) {
    if ((int) ($subperiod['spcid'] ?? 0) === (int) ($selectedSubperiodId ?? 0)) {
        $selectedSubperiod = $subperiod;
        $selectedSubperiodInRange = $today >= (string) $subperiod['spcfecha_inicio'] && $today <= (string) $subperiod['spcfecha_fin'];
        $selectedSubperiodAuthorized = $gradeEntryAuthorization !== false;
        $selectedSubperiodCanRegister = $selectedSubperiodInRange || $selectedSubperiodAuthorized;
        break;
    }
}

foreach (($components[(int) ($selectedSubperiodId ?? 0)] ?? []) as $component) {
    $componentName = strtoupper((string) ($component['cpcnombre'] ?? ''));
    $componentPrefixes[(int) $component['cpcid']] = str_contains($componentName, 'SUM')
        ? 'S'
        : (str_contains($componentName, 'FORM') ? 'F' : strtoupper(substr((string) $component['cpcnombre'], 0, 1)));
}
?>
<p class="module-note">
    <?= $useAdministrativeSelection
        ? 'Selecciona un curso y luego una materia para consultar las calificaciones por subperiodo y componente.'
        : 'Selecciona una materia asignada para preparar el registro de notas por subperiodo y componente.'; ?>
</p>

<?php if ($currentPeriod === null): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar.</div>
<?php elseif ($selectedSubject === false): ?>
    <section class="security-assignment-block">
        <?php if ($useAdministrativeSelection && $selectedCourse === false): ?>
            <?php if (empty($courses)): ?>
                <div class="empty-state">No existen cursos activos en el periodo actual.</div>
            <?php else: ?>
                <div class="gradebook-subject-grid">
                    <?php foreach ($courses as $course): ?>
                        <a class="gradebook-subject-card" href="<?= $h(baseUrl('calificaciones/registro?curid=' . (string) $course['curid'])); ?>">
                            <span class="gradebook-subject-icon">
                                <i class="fa fa-sitemap" aria-hidden="true"></i>
                            </span>
                            <span class="gradebook-subject-main">
                                <strong><?= $h($course['granombre'] . ' ' . $course['prlnombre']); ?></strong>
                                <span><?= $h($course['nednombre']); ?></span>
                            </span>
                            <span class="gradebook-subject-meta">Ver materias</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php elseif (empty($subjects)): ?>
            <div class="empty-state">No tienes materias activas asignadas en este periodo o todavia no existe un perfil de calificaciones activo para ellas.</div>
        <?php else: ?>
            <?php if ($useAdministrativeSelection && $selectedCourse !== false): ?>
                <header class="security-assignment-header">
                    <div>
                        <h3><?= $h($selectedCourse['granombre'] . ' ' . $selectedCourse['prlnombre']); ?></h3>
                        <p><?= $h($selectedCourse['nednombre']); ?> | Selecciona una materia para consultar calificaciones.</p>
                    </div>
                </header>
            <?php endif; ?>
            <div class="gradebook-subject-grid">
                <?php foreach ($subjects as $subject): ?>
                    <?php $hasProfile = !empty($subject['pcaid']); ?>
                    <?php $subjectUrl = 'calificaciones/registro?mtcid=' . (string) $subject['mtcid'] . ($useAdministrativeSelection ? '&curid=' . (string) $selectedCourseId : ''); ?>
                    <?php if ($hasProfile): ?>
                        <a class="gradebook-subject-card" href="<?= $h(baseUrl($subjectUrl)); ?>">
                    <?php else: ?>
                        <article class="gradebook-subject-card is-disabled">
                    <?php endif; ?>
                            <span class="gradebook-subject-icon">
                                <i class="fa fa-book" aria-hidden="true"></i>
                            </span>
                            <span class="gradebook-subject-main">
                                <strong><?= $h($subject['asgnombre']); ?></strong>
                                <span><?= $h($subject['granombre'] . ' ' . $subject['prlnombre']); ?></span>
                            </span>
                            <span class="gradebook-subject-meta"><?= $h($subject['areanombre']); ?></span>
                            <?php if (!empty($subject['gmcnombre'])): ?>
                                <span class="gradebook-subject-badge">Grupo: <?= $h($subject['gmcnombre']); ?></span>
                            <?php endif; ?>
                            <?php if ($hasProfile): ?>
                                <span class="gradebook-subject-profile"><?= $h($subject['pcanombre']); ?></span>
                            <?php else: ?>
                                <span class="gradebook-subject-badge is-warning">Sin perfil activo</span>
                            <?php endif; ?>
                    <?php if ($hasProfile): ?>
                        </a>
                    <?php else: ?>
                        </article>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php if ($useAdministrativeSelection): ?>
                <div class="actions-row">
                    <a class="btn-secondary btn-auto" href="<?= $h(baseUrl('calificaciones/registro')); ?>">
                        <i class="fa fa-th-large" aria-hidden="true"></i>
                        Cambiar curso
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
<?php endif; ?>

    <?php if ($selectedSubject !== false): ?>
        <section class="security-assignment-block">
            <header class="security-assignment-header">
                <div>
                    <h3><?= $h($selectedSubject['asgnombre']); ?></h3>
                    <p>
                        <?= $h($selectedSubject['granombre'] . ' ' . $selectedSubject['prlnombre']); ?>
                        <?php if (!empty($selectedSubject['gmcnombre'])): ?>
                            | Grupo: <strong><?= $h($selectedSubject['gmcnombre']); ?></strong>
                        <?php endif; ?>
                    </p>
                </div>
            </header>

            <?php if (empty($selectedSubject['pcaid'])): ?>
                <div class="empty-state">Esta materia no tiene perfil de calificaciones activo. Activa un perfil antes de registrar notas.</div>
            <?php elseif ($selectedSubperiodId <= 0 && !$showFinalAverages): ?>
                <?php if (empty($subperiods)): ?>
                    <div class="empty-state">El perfil activo no tiene subperiodos configurados.</div>
                <?php else: ?>
                    <div class="gradebook-subject-grid">
                        <?php foreach ($subperiods as $subperiod): ?>
                            <?php
                            $isInRange = $today >= (string) $subperiod['spcfecha_inicio'] && $today <= (string) $subperiod['spcfecha_fin'];
                            $subperiodUrl = baseUrl(
                                'calificaciones/registro?mtcid='
                                . (string) $selectedSubject['mtcid']
                                . '&spcid='
                                . (string) $subperiod['spcid']
                                . ($useAdministrativeSelection ? '&curid=' . (string) $selectedCourseId : '')
                            );
                            ?>
                            <a class="gradebook-subject-card <?= $isInRange ? '' : 'is-out-of-range'; ?>" href="<?= $h($subperiodUrl); ?>">
                                <span class="gradebook-subject-icon">
                                    <i class="fa fa-calendar-o" aria-hidden="true"></i>
                                </span>
                                <span class="gradebook-subject-main">
                                    <strong><?= $h($subperiod['spcnombre']); ?></strong>
                                    <span>Orden <?= $h($subperiod['spcorden']); ?></span>
                                </span>
                                <span class="gradebook-subject-meta">
                                    <?= $h($subperiod['spcfecha_inicio']); ?> al <?= $h($subperiod['spcfecha_fin']); ?>
                                </span>
                                <?php if (!empty($subperiod['spcpeso_final'])): ?>
                                    <span class="gradebook-subject-badge">Peso final: <?= $h($subperiod['spcpeso_final']); ?></span>
                                <?php endif; ?>
                                <span class="gradebook-subject-badge <?= $isInRange ? '' : 'is-warning'; ?>">
                                    <?= $isInRange ? 'En registro' : 'Fuera de rango'; ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                        <a class="gradebook-subject-card gradebook-final-card" href="<?= $h(baseUrl('calificaciones/registro?mtcid=' . (string) $selectedSubject['mtcid'] . '&final=1' . ($useAdministrativeSelection ? '&curid=' . (string) $selectedCourseId : ''))); ?>">
                            <span class="gradebook-subject-icon">
                                <i class="fa fa-bar-chart" aria-hidden="true"></i>
                            </span>
                            <span class="gradebook-subject-main">
                                <strong>Promedios finales</strong>
                                <span>Resumen de componentes y subperiodos</span>
                            </span>
                            <span class="gradebook-subject-meta">Vista consolidada</span>
                            <span class="gradebook-subject-badge">PROM</span>
                        </a>
                    </div>
                <?php endif; ?>
                <div class="actions-row">
                    <a class="btn-secondary btn-auto" href="<?= $h(baseUrl($useAdministrativeSelection ? 'calificaciones/registro?curid=' . (string) $selectedCourseId : 'calificaciones/registro')); ?>">
                        <i class="fa fa-th-large" aria-hidden="true"></i>
                        Cambiar materia
                    </a>
                </div>
            <?php elseif ($showFinalAverages): ?>
                <div class="gradebook-toolbar">
                    <a class="btn-secondary btn-auto" href="<?= $h(baseUrl('calificaciones/registro?mtcid=' . (string) $selectedSubject['mtcid'] . ($useAdministrativeSelection ? '&curid=' . (string) $selectedCourseId : ''))); ?>">
                        <i class="fa fa-calendar-o" aria-hidden="true"></i>
                        Ver subperiodos
                    </a>
                </div>
                <div class="table-wrap gradebook-table-wrap">
                    <table class="data-table gradebook-register-table gradebook-summary-table">
                        <thead>
                            <tr>
                                <th rowspan="2" class="gradebook-student-col">Nombres</th>
                                <?php foreach ($subperiods as $subperiod): ?>
                                    <?php $subperiodComponents = $components[(int) $subperiod['spcid']] ?? []; ?>
                                    <th colspan="<?= $h(count($subperiodComponents) + 1); ?>" class="gradebook-component-head">
                                        <?= $h($subperiod['spcnombre']); ?>
                                    </th>
                                <?php endforeach; ?>
                                <th rowspan="2" class="gradebook-subperiod-average-head">FINAL</th>
                            </tr>
                            <tr>
                                <?php foreach ($subperiods as $subperiod): ?>
                                    <?php foreach (($components[(int) $subperiod['spcid']] ?? []) as $component): ?>
                                        <th class="gradebook-result-head gradebook-summary-component-head">
                                            <?php foreach (preg_split('/\s+/', trim((string) $component['cpcnombre'])) ?: [] as $componentNamePart): ?>
                                                <span><?= $h($componentNamePart); ?></span>
                                            <?php endforeach; ?>
                                            <?php if (!empty($component['cpcpeso'])): ?>
                                                <small><?= $h(number_format((float) $component['cpcpeso'], 0, ',', '')); ?>%</small>
                                            <?php endif; ?>
                                        </th>
                                    <?php endforeach; ?>
                                    <th class="gradebook-subperiod-average-head">PROM</th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <?php $finalAverageSum = 0.0; ?>
                                <?php $finalAverageCount = 0; ?>
                                <tr>
                                    <td class="gradebook-student-col">
                                        <span class="cell-title"><?= $h($student['perapellidos'] . ' ' . $student['pernombres']); ?></span>
                                    </td>
                                    <?php foreach ($subperiods as $subperiod): ?>
                                        <?php $subperiodAverageParts = []; ?>
                                        <?php $subperiodId = (int) $subperiod['spcid']; ?>
                                        <?php foreach (($components[$subperiodId] ?? []) as $component): ?>
                                            <?php
                                            $componentId = (int) $component['cpcid'];
                                            $componentActivities = $activities[$subperiodId][$componentId] ?? [];
                                            $componentAverage = null;
                                            $componentResult = null;

                                            if ($componentActivities !== []) {
                                                $componentSum = 0.0;

                                                foreach ($componentActivities as $activity) {
                                                    $grade = $grades[(int) $activity['aciid']][(int) $student['matid']] ?? null;
                                                    $componentSum += $grade !== null && $grade['cesnota'] !== null ? (float) $grade['cesnota'] : 0.0;
                                                }

                                                $componentAverage = round($componentSum / count($componentActivities), 2);
                                                $componentResult = !empty($component['cpcpeso'])
                                                    ? round($componentAverage * ((float) $component['cpcpeso'] / 100), 2)
                                                    : $componentAverage;
                                                $subperiodAverageParts[] = $componentResult;
                                            }
                                            ?>
                                            <td class="gradebook-result-cell" title="<?= $componentAverage !== null ? 'Promedio: ' . $h(number_format($componentAverage, 2, ',', '')) : ''; ?>">
                                                <?= $componentResult !== null ? $h(number_format($componentResult, 2, ',', '')) : ''; ?>
                                            </td>
                                        <?php endforeach; ?>
                                        <?php
                                        $subperiodAverage = $subperiodAverageParts !== []
                                            ? round(array_sum($subperiodAverageParts), 2)
                                            : null;

                                        $participatesFinalValue = strtolower((string) ($subperiod['spcparticipa_final'] ?? '1'));
                                        $participatesFinal = !in_array($participatesFinalValue, ['0', 'false', 'f', 'no'], true);

                                        if ($participatesFinal) {
                                            $finalAverageSum += $subperiodAverage ?? 0.0;
                                            $finalAverageCount++;
                                        }
                                        ?>
                                        <td class="gradebook-subperiod-average-cell <?= $subperiodAverage !== null && $subperiodAverage < 7 ? 'is-low-average' : 'is-approved-average'; ?>">
                                            <?= $subperiodAverage !== null ? $h(number_format($subperiodAverage, 2, ',', '')) : ''; ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <?php $finalAverage = $finalAverageCount > 0 ? round($finalAverageSum / $finalAverageCount, 2) : null; ?>
                                    <td class="gradebook-final-average-cell <?= $finalAverage !== null && $finalAverage < 7 ? 'is-low-average' : 'is-approved-average'; ?>">
                                        <?= $finalAverage !== null ? $h(number_format($finalAverage, 2, ',', '')) : ''; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($students)): ?>
                                <tr><td colspan="99">No hay estudiantes activos en el curso.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <?php if (empty($components[$selectedSubperiodId])): ?>
                    <div class="empty-state">Este subperiodo no tiene componentes activos configurados.</div>
                <?php else: ?>
                    <?php if ($canAuthorizeGradeEntry && !$selectedSubperiodInRange && is_array($selectedSubperiod)): ?>
                        <form id="gradebook-reopen-form" method="POST" action="<?= $h(baseUrl('calificaciones/habilitar-subperiodo')); ?>">
                            <input type="hidden" name="mtcid" value="<?= $h($selectedSubject['mtcid']); ?>">
                            <input type="hidden" name="spcid" value="<?= $h($selectedSubperiodId); ?>">
                            <?php if ($useAdministrativeSelection): ?>
                                <input type="hidden" name="curid" value="<?= $h($selectedCourseId); ?>">
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                    <form method="POST" action="<?= $h(baseUrl('calificaciones/notas')); ?>">
                        <input type="hidden" name="mtcid" value="<?= $h($selectedSubject['mtcid']); ?>">
                        <input type="hidden" name="spcid" value="<?= $h($selectedSubperiodId); ?>">
                        <?php if ($useAdministrativeSelection): ?>
                            <input type="hidden" name="curid" value="<?= $h($selectedCourseId); ?>">
                        <?php endif; ?>
                        <div class="gradebook-toolbar">
                            <a class="btn-secondary btn-auto" href="<?= $h(baseUrl('calificaciones/registro?mtcid=' . (string) $selectedSubject['mtcid'] . ($useAdministrativeSelection ? '&curid=' . (string) $selectedCourseId : ''))); ?>">
                                <i class="fa fa-calendar-o" aria-hidden="true"></i>
                                Cambiar subperiodo
                            </a>
                            <?php if ($canEditSelectedSubject): ?>
                                <button type="submit" class="btn-primary btn-auto" <?= $selectedSubperiodCanRegister ? '' : 'disabled'; ?>>
                                    <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                    Guardar notas
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php if (!$canEditSelectedSubject): ?>
                            <div class="empty-state">Vista de consulta. Solo el docente asignado puede agregar actividades o editar notas.</div>
                        <?php elseif ($selectedSubperiodAuthorized): ?>
                            <div class="flash-message success">
                                Registro habilitado temporalmente hasta <?= $h((string) $gradeEntryAuthorization['hrcfecha_fin']); ?>.
                                Motivo: <?= $h((string) $gradeEntryAuthorization['hrcmotivo']); ?>
                            </div>
                        <?php elseif (!$selectedSubperiodInRange): ?>
                            <div class="empty-state">Este subperiodo esta fuera del rango de registro. Las notas quedan solo en lectura.</div>
                        <?php endif; ?>
                        <?php if ($canAuthorizeGradeEntry && !$selectedSubperiodInRange && is_array($selectedSubperiod)): ?>
                            <div class="data-form gradebook-reopen-form">
                                <div class="form-grid">
                                    <div class="input-group">
                                        <span class="input-addon">Habilitar hasta</span>
                                        <input type="datetime-local" name="hrcfecha_fin" required form="gradebook-reopen-form">
                                    </div>
                                    <div class="input-group">
                                        <span class="input-addon">Motivo</span>
                                        <input type="text" name="hrcmotivo" maxlength="250" required placeholder="Motivo de reapertura" form="gradebook-reopen-form">
                                    </div>
                                    <button type="submit" class="btn-secondary btn-auto" form="gradebook-reopen-form">
                                        <i class="fa fa-unlock" aria-hidden="true"></i>
                                        Habilitar registro
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="table-wrap gradebook-table-wrap">
                            <table class="data-table gradebook-register-table" data-gradebook-table>
                                <thead>
                                    <tr>
                                        <th rowspan="2" class="gradebook-student-col">Nombres</th>
                                        <?php foreach (($components[$selectedSubperiodId] ?? []) as $componentIndex => $component): ?>
                                            <?php $componentActivities = $activities[(int) $component['cpcid']] ?? []; ?>
                                            <th
                                                colspan="<?= $h(count($componentActivities) + 2); ?>"
                                                class="gradebook-component-head gradebook-component-<?= $h(($componentIndex % 4) + 1); ?>"
                                            >
                                                <?= $h($component['cpcnombre']); ?>
                                            </th>
                                        <?php endforeach; ?>
                                        <th rowspan="2" class="gradebook-subperiod-average-head">PROM</th>
                                    </tr>
                                    <tr>
                                        <?php foreach (($components[$selectedSubperiodId] ?? []) as $componentIndex => $component): ?>
                                            <?php $componentId = (int) $component['cpcid']; ?>
                                            <?php foreach (($activities[$componentId] ?? []) as $activityIndex => $activity): ?>
                                                <th
                                                    class="gradebook-activity-head gradebook-component-<?= $h(($componentIndex % 4) + 1); ?>"
                                                    title="<?= $h($activity['acinombre']); ?>"
                                                >
                                                    <span><?= $h(($componentPrefixes[$componentId] ?? 'A') . ($activityIndex + 1)); ?></span>
                                                    <button
                                                        type="button"
                                                        class="gradebook-edit-activity-button"
                                                        title="Editar esta columna"
                                                        data-gradebook-edit-column="<?= $h($activity['aciid']); ?>"
                                                        <?= $selectedSubperiodCanRegister && $canEditSelectedSubject ? '' : 'disabled'; ?>
                                                    >
                                                        <i class="fa fa-pencil" aria-hidden="true"></i>
                                                    </button>
                                                </th>
                                            <?php endforeach; ?>
                                            <th class="gradebook-add-head gradebook-component-<?= $h(($componentIndex % 4) + 1); ?>">
                                                <button
                                                    type="button"
                                                    class="gradebook-add-activity-button"
                                                    title="Agregar actividad"
                                                    <?= $selectedSubperiodCanRegister && $canEditSelectedSubject ? '' : 'disabled'; ?>
                                                    onclick="document.getElementById('gradebook-activity-dialog-<?= $h($componentId); ?>').showModal()"
                                                >
                                                    <i class="fa fa-plus" aria-hidden="true"></i>
                                                </button>
                                            </th>
                                            <th
                                                class="gradebook-result-head gradebook-component-<?= $h(($componentIndex % 4) + 1); ?>"
                                                title="Resultado del componente<?= !empty($component['cpcpeso']) ? ' ponderado al ' . $h($component['cpcpeso']) . '%' : ''; ?>"
                                            >
                                                <?= !empty($component['cpcpeso']) ? $h(number_format((float) $component['cpcpeso'], 0, ',', '')) . '%' : 'Prom'; ?>
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <?php $subperiodAverageParts = []; ?>
                                        <tr>
                                            <td class="gradebook-student-col">
                                                <span class="cell-title"><?= $h($student['perapellidos'] . ' ' . $student['pernombres']); ?></span>
                                            </td>
                                            <?php foreach (($components[$selectedSubperiodId] ?? []) as $componentIndex => $component): ?>
                                                <?php $componentId = (int) $component['cpcid']; ?>
                                                <?php foreach (($activities[$componentId] ?? []) as $activity): ?>
                                                    <?php $grade = $grades[(int) $activity['aciid']][(int) $student['matid']] ?? null; ?>
                                                    <?php $gradeValue = $grade !== null && $grade['cesnota'] !== null ? (float) $grade['cesnota'] : null; ?>
                                                    <td class="gradebook-grade-cell gradebook-component-<?= $h(($componentIndex % 4) + 1); ?>">
                                                        <input
                                                            type="number"
                                                            data-gradebook-column="<?= $h($activity['aciid']); ?>"
                                                            name="grades[<?= $h($activity['aciid']); ?>][<?= $h($student['matid']); ?>]"
                                                            min="<?= $h(number_format($gradeMinimum, 2, '.', '')); ?>"
                                                            max="<?= $h(number_format($gradeMaximum, 2, '.', '')); ?>"
                                                            step="0.01"
                                                            value="<?= $gradeValue !== null ? $h(number_format($gradeValue, 2, '.', '')) : ''; ?>"
                                                            class="gradebook-grade-input <?= $gradeValue !== null && $gradeValue < 7 ? 'is-low-grade' : ''; ?>"
                                                            title="Rango permitido: <?= $h(number_format($gradeMinimum, 2, ',', '')); ?> a <?= $h(number_format($gradeMaximum, 2, ',', '')); ?>"
                                                            <?= $selectedSubperiodCanRegister && $canEditSelectedSubject ? 'readonly tabindex="-1"' : 'disabled'; ?>
                                                        >
                                                    </td>
                                                <?php endforeach; ?>
                                                <td class="gradebook-empty-cell gradebook-component-<?= $h(($componentIndex % 4) + 1); ?>"></td>
                                                <?php
                                                $componentActivities = $activities[$componentId] ?? [];
                                                    $componentAverage = null;
                                                    $componentResult = null;

                                                    if ($componentActivities !== []) {
                                                        $componentSum = 0.0;

                                                    foreach ($componentActivities as $activity) {
                                                        $grade = $grades[(int) $activity['aciid']][(int) $student['matid']] ?? null;
                                                        $componentSum += $grade !== null && $grade['cesnota'] !== null ? (float) $grade['cesnota'] : 0.0;
                                                    }

                                                    $componentAverage = round($componentSum / count($componentActivities), 2);
                                                    $componentResult = !empty($component['cpcpeso'])
                                                        ? round($componentAverage * ((float) $component['cpcpeso'] / 100), 2)
                                                        : $componentAverage;
                                                    $subperiodAverageParts[] = $componentResult;
                                                }
                                                ?>
                                                <td
                                                    class="gradebook-result-cell gradebook-component-<?= $h(($componentIndex % 4) + 1); ?>"
                                                    title="<?= $componentAverage !== null ? 'Promedio: ' . $h(number_format($componentAverage, 2, ',', '')) : ''; ?>"
                                                >
                                                    <?= $componentResult !== null ? $h(number_format($componentResult, 2, ',', '')) : ''; ?>
                                                </td>
                                            <?php endforeach; ?>
                                            <?php
                                            $subperiodAverage = $subperiodAverageParts !== []
                                                ? round(array_sum($subperiodAverageParts), 2)
                                                : null;
                                            ?>
                                            <td class="gradebook-subperiod-average-cell <?= $subperiodAverage !== null && $subperiodAverage < 7 ? 'is-low-average' : 'is-approved-average'; ?>">
                                                <?= $subperiodAverage !== null ? $h(number_format($subperiodAverage, 2, ',', '')) : ''; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($students)): ?>
                                        <tr><td colspan="99">No hay estudiantes activos en el curso.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>

                    <?php foreach (($components[$selectedSubperiodId] ?? []) as $component): ?>
                        <?php $componentId = (int) $component['cpcid']; ?>
                        <dialog class="calendar-dialog gradebook-activity-dialog" id="gradebook-activity-dialog-<?= $h($componentId); ?>">
                            <form method="POST" action="<?= $h(baseUrl('calificaciones/actividad')); ?>" class="data-form">
                                <input type="hidden" name="mtcid" value="<?= $h($selectedSubject['mtcid']); ?>">
                                <input type="hidden" name="spcid" value="<?= $h($selectedSubperiodId); ?>">
                                <input type="hidden" name="cpcid" value="<?= $h($componentId); ?>">
                                <?php if ($useAdministrativeSelection): ?>
                                    <input type="hidden" name="curid" value="<?= $h($selectedCourseId); ?>">
                                <?php endif; ?>
                                <h3>Agregar actividad</h3>
                                <p class="module-note"><?= $h($component['cpcnombre']); ?> | <?= $h(is_array($selectedSubperiod) ? $selectedSubperiod['spcnombre'] : ''); ?></p>
                                <div class="input-group">
                                    <span class="input-addon">Nombre</span>
                                    <input type="text" name="acinombre" maxlength="120" required placeholder="Ej. Leccion oral de verbos">
                                </div>
                                <div class="actions-row">
                                    <button type="button" class="btn-secondary" onclick="this.closest('dialog').close()">Cancelar</button>
                                    <button type="submit" class="btn-primary">Guardar</button>
                                </div>
                            </form>
                        </dialog>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    <?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
