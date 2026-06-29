<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$course = is_array($course ?? null) ? $course : [];
$courseId = (int) ($course['curid'] ?? 0);
$courseLabel = trim((string) (($course['granombre'] ?? '') . ' ' . ($course['prlnombre'] ?? '')));
$subjects = array_values((array) ($course['subjects'] ?? []));
$actions = [
    [
        'label' => 'Asistencia',
        'description' => 'Registrar asistencia y novedades del curso segun el calendario disponible.',
        'url' => baseUrl('asistencia/registro?curid=' . $courseId),
        'icon' => 'fa-calendar-check-o',
    ],
    [
        'label' => 'Calificaciones',
        'description' => 'Seleccionar materias asignadas y registrar notas.',
        'url' => baseUrl('calificaciones/registro?curid=' . $courseId),
        'icon' => 'fa-check-square',
    ],
    [
        'label' => 'Lista del curso',
        'description' => 'Consultar la lista simple de estudiantes activos.',
        'url' => baseUrl('docente/curso/lista?curid=' . $courseId),
        'icon' => 'fa-list-ol',
    ],
    [
        'label' => 'Reporte mensual',
        'description' => 'Generar la matriz mensual de asistencia del curso.',
        'url' => baseUrl('reportes/asistencia?curid=' . $courseId),
        'icon' => 'fa-bar-chart',
    ],
];
?>
<section class="security-assignment-block teacher-course-detail">
    <div class="module-header-row">
        <div>
            <h3><?= $h($courseLabel !== '' ? $courseLabel : 'Curso'); ?></h3>
            <p><?= $h((string) ($course['nednombre'] ?? '')); ?></p>
        </div>
        <a class="btn-secondary btn-auto" href="<?= $h(baseUrl('docente/cursos')); ?>">Volver a mis cursos</a>
    </div>

    <?php if ($subjects !== []): ?>
        <div class="dashboard-status-list teacher-subject-list">
            <?php foreach ($subjects as $subject): ?>
                <span class="permission-option-state is-active"><?= $h($subject); ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="module-home-grid teacher-course-actions">
    <?php foreach ($actions as $action): ?>
        <a class="module-home-card" href="<?= $h($action['url']); ?>">
            <span class="module-home-card-icon">
                <i class="fa <?= $h($action['icon']); ?>" aria-hidden="true"></i>
            </span>
            <strong><?= $h($action['label']); ?></strong>
            <p><?= $h($action['description']); ?></p>
        </a>
    <?php endforeach; ?>
</section>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
