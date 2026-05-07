<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$student = is_array($profile['student'] ?? null) ? $profile['student'] : [];
$matriculation = is_array($profile['matriculation'] ?? null) ? $profile['matriculation'] : null;
$representative = is_array($profile['representative'] ?? null) ? $profile['representative'] : null;
$families = is_array($profile['families'] ?? null) ? $profile['families'] : [];
$healthContext = is_array($profile['health_context'] ?? null) ? $profile['health_context'] : null;
$academicContext = is_array($profile['academic_context'] ?? null) ? $profile['academic_context'] : null;
$resources = is_array($profile['resources'] ?? null) ? $profile['resources'] : null;
$billing = is_array($profile['billing'] ?? null) ? $profile['billing'] : null;
$documents = is_array($profile['documents'] ?? null) ? $profile['documents'] : [];
$matriculations = is_array($profile['matriculations'] ?? null) ? $profile['matriculations'] : [];

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$empty = static fn (mixed $value, string $fallback = 'Sin registrar'): string => trim((string) $value) !== '' ? (string) $value : $fallback;
$yesNo = static fn (bool $value): string => $value ? 'Si' : 'No';

$studentName = trim((string) (($student['perapellidos'] ?? '') . ' ' . ($student['pernombres'] ?? '')));
$courseName = (string) ($matriculation['curso'] ?? 'Sin matricula en el periodo');
$studentId = (int) ($student['estid'] ?? 0);
$isOwnProfile = !empty($isOwnProfile);
$isRepresentativeProfile = !empty($isRepresentativeProfile);
$canRepresentativeMatriculate = !empty($canRepresentativeMatriculate);
$studentEditUrl = baseUrl('estudiantes/editar?id=' . $studentId);
$moduleUrl = static function (string $section) use ($isOwnProfile, $isRepresentativeProfile, $studentId): string {
    if ($isOwnProfile) {
        return baseUrl('mi-matricula/modulo?seccion=' . $section);
    }

    if ($isRepresentativeProfile) {
        return baseUrl('representante/estudiante/modulo?id=' . $studentId . '&seccion=' . $section);
    }

    return baseUrl('estudiantes/modulo?id=' . $studentId . '&seccion=' . $section);
};

$representativeName = $representative === null
    ? 'Pendiente'
    : trim((string) (($representative['perapellidos'] ?? '') . ' ' . ($representative['pernombres'] ?? '')));

$cards = [
    [
        'label' => 'Estudiante',
        'value' => $empty($student['percedula'] ?? null),
        'url' => $moduleUrl('estudiante'),
    ],
    [
        'label' => 'Matricula',
        'value' => $courseName,
        'url' => $moduleUrl('matricula'),
    ],
    [
        'label' => 'Representante',
        'value' => $representativeName !== '' ? $representativeName : 'Pendiente',
        'url' => $moduleUrl('representante'),
    ],
    [
        'label' => 'Familiares',
        'value' => (string) count($families),
        'url' => $moduleUrl('familiares'),
    ],
    [
        'label' => 'Salud',
        'value' => $empty($healthContext['gsnombre'] ?? null),
        'url' => $moduleUrl('salud'),
    ],
    [
        'label' => 'Contexto academico',
        'value' => $yesNo(!empty($academicContext['ecaharepetidoanios'])),
        'url' => $moduleUrl('academico'),
    ],
    [
        'label' => 'Recursos tecnologicos',
        'value' => $yesNo(!empty($resources['mrtinternet'])),
        'url' => $moduleUrl('recursos'),
    ],
    [
        'label' => 'Facturacion',
        'value' => $empty($billing['mfcnombre'] ?? null),
        'url' => $moduleUrl('facturacion'),
    ],
    [
        'label' => 'Documentos',
        'value' => (string) count($documents),
        'url' => $moduleUrl('documentos'),
    ],
    [
        'label' => 'Historial',
        'value' => (string) count($matriculations),
        'url' => $moduleUrl('historial'),
    ],
];
?>
<div class="toolbar">
    <p><?= $isOwnProfile ? 'Consulta de tu informacion academica y de matricula.' : 'Ficha operativa del estudiante y su matricula asociada.'; ?></p>
    <?php if ($isRepresentativeProfile): ?>
        <a class="text-link" href="<?= $h(baseUrl('dashboard')); ?>">Volver al dashboard</a>
    <?php elseif (!$isOwnProfile): ?>
        <a class="text-link" href="<?= $h(baseUrl('estudiantes')); ?>">Volver al listado</a>
    <?php endif; ?>
</div>

<section class="student-profile-hero">
    <div>
        <span class="summary-label">Ficha del estudiante</span>
        <h3><?= $h($studentName !== '' ? $studentName : 'Estudiante sin nombre'); ?></h3>
        <p>
            Cedula: <?= $h($empty($student['percedula'] ?? null)); ?>
            | Curso: <?= $h($courseName); ?>
            | Periodo: <?= $h($empty($matriculation['pledescripcion'] ?? $currentPeriod['pledescripcion'] ?? null, 'Sin periodo')); ?>
        </p>
    </div>
    <div class="student-profile-actions">
        <span class="state-pill <?= !empty($student['estestado']) ? 'state-pill-active' : 'state-pill-inactive'; ?>">
            <?= !empty($student['estestado']) ? 'Activo' : 'Inactivo'; ?>
        </span>
        <?php if ($canRepresentativeMatriculate): ?>
            <a class="btn-primary btn-auto" href="<?= $h(baseUrl('matricula-temporal?estudiante=' . $studentId)); ?>">Matricular</a>
        <?php endif; ?>
        <?php if (!$isOwnProfile && !$isRepresentativeProfile): ?>
            <a class="btn-secondary btn-auto" href="<?= $h($studentEditUrl); ?>">Editar datos</a>
        <?php endif; ?>
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
