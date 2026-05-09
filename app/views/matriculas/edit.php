<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$empty = static fn (mixed $value, string $fallback = 'Sin registrar'): string => trim((string) $value) !== '' ? (string) $value : $fallback;
$yesNo = static fn (bool $value): string => $value ? 'Si' : 'No';

$student = is_array($profile['student'] ?? null) ? $profile['student'] : [];
$matriculation = is_array($profile['matriculation'] ?? null) ? $profile['matriculation'] : [];
$representative = is_array($profile['representative'] ?? null) ? $profile['representative'] : [];
$families = is_array($profile['families'] ?? null) ? $profile['families'] : [];
$healthContext = is_array($profile['health_context'] ?? null) ? $profile['health_context'] : [];
$resources = is_array($profile['resources'] ?? null) ? $profile['resources'] : [];
$billing = is_array($profile['billing'] ?? null) ? $profile['billing'] : [];
$documents = is_array($profile['documents'] ?? null) ? $profile['documents'] : [];
$academicContext = is_array($profile['academic_context'] ?? null) ? $profile['academic_context'] : [];
$matriculations = is_array($profile['matriculations'] ?? null) ? $profile['matriculations'] : [];
$healthConditions = is_array($profile['health_conditions'] ?? null) ? $profile['health_conditions'] : [];
$healthMeasurements = is_array($profile['health_measurements'] ?? null) ? $profile['health_measurements'] : [];
$vitalHistory = is_array($profile['vital_history'] ?? null) ? $profile['vital_history'] : [];

$matriculaId = (int) ($matricula['matid'] ?? 0);
$studentName = trim((string) (($student['perapellidos'] ?? '') . ' ' . ($student['pernombres'] ?? '')));
$courseName = (string) ($matriculation['curso'] ?? (($matricula['granombre'] ?? '') . ' ' . ($matricula['prlnombre'] ?? '')));
$representativeName = trim((string) (($representative['perapellidos'] ?? '') . ' ' . ($representative['pernombres'] ?? '')));
$moduleUrl = static fn (string $section, string $panel = ''): string => baseUrl(
    'matriculas/editar?id=' . $matriculaId . '&seccion=' . $section . ($panel !== '' ? '&panel=' . $panel : '')
);

$cards = [
    ['label' => 'Estudiante', 'value' => $empty($student['percedula'] ?? null), 'url' => $moduleUrl('estudiante')],
    ['label' => 'Matricula', 'value' => $empty($courseName), 'url' => $moduleUrl('matricula')],
    ['label' => 'Representante', 'value' => $representativeName !== '' ? $representativeName : 'Pendiente', 'url' => $moduleUrl('representante')],
    ['label' => 'Familiares', 'value' => (string) count($families), 'url' => $moduleUrl('familiares')],
    ['label' => 'Salud general', 'value' => $empty($healthContext['gsnombre'] ?? null), 'url' => $moduleUrl('salud', 'general')],
    ['label' => 'Condiciones de salud', 'value' => (string) count($healthConditions), 'url' => $moduleUrl('salud', 'condiciones')],
    ['label' => 'Historia vital', 'value' => $empty($vitalHistory['ehvpesonacer'] ?? null), 'url' => $moduleUrl('salud', 'historia-vital')],
    ['label' => 'Mediciones', 'value' => (string) count($healthMeasurements), 'url' => $moduleUrl('salud', 'mediciones')],
    ['label' => 'Contexto academico', 'value' => $yesNo(!empty($academicContext['ecaharepetidoanios'])), 'url' => $moduleUrl('academico')],
    ['label' => 'Recursos tecnologicos', 'value' => $yesNo(!empty($resources['mrtinternet'])), 'url' => $moduleUrl('recursos')],
    ['label' => 'Facturacion', 'value' => $empty($billing['mfcnombre'] ?? null), 'url' => $moduleUrl('facturacion')],
    ['label' => 'Documentos', 'value' => (string) count($documents), 'url' => $moduleUrl('documentos')],
    ['label' => 'Historial', 'value' => (string) count($matriculations), 'url' => $moduleUrl('historial')],
];
?>

<div class="toolbar">
    <p>Edicion completa de matricula y ficha asociada.</p>
    <a class="text-link" href="<?= $h(baseUrl('matriculas?panel=gestion#matriculas-registradas')); ?>">Volver a gestion</a>
</div>

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

<section class="student-profile-hero">
    <div>
        <span class="summary-label">Matricula</span>
        <h3><?= $h($studentName !== '' ? $studentName : 'Estudiante sin nombre'); ?></h3>
        <p>
            Cedula: <?= $h($empty($student['percedula'] ?? null)); ?>
            | Curso: <?= $h($empty($courseName)); ?>
            | Periodo: <?= $h($empty($matricula['pledescripcion'] ?? null, 'Sin periodo')); ?>
        </p>
    </div>
    <div class="student-profile-actions">
        <span class="state-pill <?= !empty($student['estestado']) ? 'state-pill-active' : 'state-pill-inactive'; ?>">
            <?= !empty($student['estestado']) ? 'Activo' : 'Inactivo'; ?>
        </span>
    </div>
</section>

<section class="student-profile-index-grid">
    <?php foreach ($cards as $card): ?>
        <a class="summary-card student-profile-card student-card-link student-compact-card" href="<?= $h($card['url']); ?>">
            <span class="summary-label"><?= $h($card['label']); ?></span>
            <strong><?= $h($card['value']); ?></strong>
        </a>
    <?php endforeach; ?>
</section>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
