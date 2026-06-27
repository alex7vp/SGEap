<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$communication = is_array($communication ?? null) ? $communication : null;
$criteria = is_array($criteria ?? null) ? $criteria : [];
$old = is_array($old ?? null) ? $old : [];
$targetType = strtoupper(trim((string) ($criteria['target_type'] ?? '')));

if ($targetType === '' && !empty($criteria['targets'][0])) {
    $targetType = (string) $criteria['targets'][0];
}

if ($targetType === 'MATRICULAS') {
    $targetType = 'ESTUDIANTES';
}

$targetType = in_array($targetType, ['CURSOS', 'ESTUDIANTES', 'REPRESENTANTES', 'PERSONAL', 'TODOS'], true)
    ? $targetType
    : 'CURSOS';
$selectedCourses = array_map('intval', (array) ($criteria['course_ids'] ?? []));
$selectedMatriculations = array_map('intval', (array) ($criteria['matriculation_ids'] ?? []));
$selectedRepresentatives = is_array($selectedRepresentatives ?? null) ? $selectedRepresentatives : [];
$selectedStaff = is_array($selectedStaff ?? null) ? $selectedStaff : [];
$isEdit = $communication !== null;
$formAction = baseUrl('comunicados');

$courseMap = [];
foreach ((array) ($courses ?? []) as $course) {
    $courseMap[(int) $course['curid']] = (string) $course['curso'];
}

$matriculationMap = [];
foreach ((array) ($matriculations ?? []) as $matriculation) {
    $matriculationMap[(int) $matriculation['matid']] = (string) $matriculation['estudiante'];
}

$targetLabels = [
    'CURSOS' => 'Cursos',
    'ESTUDIANTES' => 'Estudiantes',
    'REPRESENTANTES' => 'Representantes',
    'PERSONAL' => 'Personal',
    'TODOS' => 'Todos',
];
?>
<p class="module-note">Use Guardar borrador para preparar el comunicado sin hacerlo visible. Use Enviar para publicarlo y preparar la mensajeria.</p>

<form class="data-form" method="POST" action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8'); ?>" data-communication-form>
    <?= csrfField(); ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="comid" value="<?= htmlspecialchars((string) ($communication['comid'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <input type="hidden" name="action" value="draft" data-communication-action>

    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Contenido</h3>
                <p>Texto que veran los destinatarios dentro del sistema y en correo o WhatsApp cuando se configuren los proveedores.</p>
            </div>
        </header>
        <div class="form-grid">
            <div>
                <label for="communication-title">Titulo</label>
                <input id="communication-title" type="text" name="titulo" maxlength="180" required value="<?= htmlspecialchars((string) ($old['titulo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="form-grid-full">
                <label for="communication-message">Mensaje</label>
                <textarea id="communication-message" name="mensaje" rows="8" required><?= htmlspecialchars((string) ($old['mensaje'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </div>
    </section>

    <section class="security-assignment-block" data-communication-recipient-builder>
        <header class="security-assignment-header">
            <div>
                <h3>Destinatarios</h3>
                <p>Seleccione un solo alcance para el comunicado. Al cambiar el alcance se limpian los destinatarios anteriores.</p>
            </div>
        </header>

        <div class="communication-target-grid" role="radiogroup" aria-label="Tipo de destinatario">
            <?php foreach ($targetLabels as $value => $label): ?>
                <label class="communication-target-option">
                    <input type="radio" name="target_type" value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" <?= $targetType === $value ? 'checked' : ''; ?> data-communication-target-radio>
                    <span>
                        <strong><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <small>
                            <?= match ($value) {
                                'CURSOS' => 'Incluye estudiantes y representantes autorizados.',
                                'ESTUDIANTES' => 'Busca estudiantes puntuales; incluye representante autorizado.',
                                'REPRESENTANTES' => 'Solo representantes seleccionados.',
                                'PERSONAL' => 'Docentes, autoridades y administrativos registrados.',
                                'TODOS' => 'Todos los usuarios activos.',
                            }; ?>
                        </small>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="communication-selected-panel">
            <div>
                <span class="summary-label">Seleccion actual</span>
                <strong data-communication-selected-title><?= htmlspecialchars($targetLabels[$targetType] ?? 'Destinatarios', ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
            <div class="communication-selected-list" data-communication-selected-list>
                <div class="empty-state" data-communication-selected-empty>Sin destinatarios seleccionados.</div>
            </div>
            <div data-communication-hidden-inputs>
                <?php foreach ($selectedCourses as $courseId): ?>
                    <?php if (isset($courseMap[$courseId])): ?>
                        <input type="hidden" name="course_ids[]" value="<?= htmlspecialchars((string) $courseId, ENT_QUOTES, 'UTF-8'); ?>" data-selected-id="<?= htmlspecialchars((string) $courseId, ENT_QUOTES, 'UTF-8'); ?>" data-selected-label="<?= htmlspecialchars($courseMap[$courseId], ENT_QUOTES, 'UTF-8'); ?>" data-selected-type="CURSOS">
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php foreach ($selectedMatriculations as $matriculationId): ?>
                    <?php if (isset($matriculationMap[$matriculationId])): ?>
                        <input type="hidden" name="matriculation_ids[]" value="<?= htmlspecialchars((string) $matriculationId, ENT_QUOTES, 'UTF-8'); ?>" data-selected-id="<?= htmlspecialchars((string) $matriculationId, ENT_QUOTES, 'UTF-8'); ?>" data-selected-label="<?= htmlspecialchars($matriculationMap[$matriculationId], ENT_QUOTES, 'UTF-8'); ?>" data-selected-type="ESTUDIANTES">
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php foreach ($selectedRepresentatives as $representative): ?>
                    <input type="hidden" name="representative_user_ids[]" value="<?= htmlspecialchars((string) ($representative['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" data-selected-id="<?= htmlspecialchars((string) ($representative['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" data-selected-label="<?= htmlspecialchars((string) ($representative['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-selected-detail="<?= htmlspecialchars((string) ($representative['detail'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-selected-type="REPRESENTANTES">
                <?php endforeach; ?>
                <?php foreach ($selectedStaff as $staff): ?>
                    <input type="hidden" name="staff_user_ids[]" value="<?= htmlspecialchars((string) ($staff['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" data-selected-id="<?= htmlspecialchars((string) ($staff['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" data-selected-label="<?= htmlspecialchars((string) ($staff['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-selected-detail="<?= htmlspecialchars((string) ($staff['detail'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-selected-type="PERSONAL">
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <div class="actions-row">
        <button class="btn-secondary btn-inline" type="submit" data-communication-draft>
            <i class="fa fa-save" aria-hidden="true"></i>
            Guardar borrador
        </button>
        <button class="btn-primary btn-inline" type="submit" data-communication-send>
            <i class="fa fa-paper-plane" aria-hidden="true"></i>
            Enviar
        </button>
        <a class="btn-secondary btn-inline" href="<?= htmlspecialchars(baseUrl('comunicados'), ENT_QUOTES, 'UTF-8'); ?>">Cancelar</a>
    </div>
</form>

<dialog class="calendar-dialog communication-recipient-dialog" data-communication-recipient-dialog>
    <header class="security-assignment-header">
        <div>
            <h3 data-communication-recipient-dialog-title>Seleccionar destinatarios</h3>
            <p data-communication-recipient-dialog-copy>Elija los destinatarios para el alcance seleccionado.</p>
        </div>
    </header>

    <div class="communication-selector-panel" data-communication-course-panel>
        <div class="input-group">
            <span class="input-addon">Filtrar</span>
            <input type="search" data-communication-course-filter placeholder="Nivel, grado o paralelo">
        </div>
        <div class="communication-selector-list">
            <?php foreach ((array) ($courses ?? []) as $course): ?>
                <label class="communication-selector-row">
                    <input type="checkbox" value="<?= htmlspecialchars((string) $course['curid'], ENT_QUOTES, 'UTF-8'); ?>" data-course-option data-label="<?= htmlspecialchars((string) $course['curso'], ENT_QUOTES, 'UTF-8'); ?>" <?= in_array((int) $course['curid'], $selectedCourses, true) ? 'checked' : ''; ?>>
                    <span><?= htmlspecialchars((string) $course['curso'], ENT_QUOTES, 'UTF-8'); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="communication-selector-panel" data-communication-search-panel hidden>
        <div class="input-group">
            <span class="input-addon">Buscar</span>
            <input type="search" data-communication-search-input placeholder="Cedula, nombres, apellidos o curso">
        </div>
        <div class="communication-search-results" data-communication-search-results>
            <div class="empty-state">Ingrese un criterio para buscar.</div>
        </div>
    </div>

    <div class="actions-row">
        <button class="btn-primary btn-inline" type="button" data-communication-recipient-close>Aceptar</button>
    </div>
</dialog>

<dialog class="calendar-dialog" data-communication-send-dialog>
    <header class="security-assignment-header">
        <div>
            <h3>Enviar comunicado</h3>
            <p>Al confirmar se publicara el comunicado, se mostrara a los destinatarios y se prepararan las entregas de mensajeria.</p>
        </div>
    </header>
    <div class="actions-row">
        <button class="btn-secondary btn-inline" type="button" data-communication-send-cancel>Cancelar</button>
        <button class="btn-primary btn-inline" type="button" data-communication-send-confirm>Enviar comunicado</button>
    </div>
</dialog>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
