<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$currentPeriod = is_array($currentPeriod ?? null) ? $currentPeriod : null;
$teacherCourses = is_array($teacherCourses ?? null) ? $teacherCourses : [];
?>
<section class="dashboard-hero teacher-dashboard-hero">
    <div>
        <span class="summary-label">Periodo actual</span>
        <h3><?= $h((string) ($currentPeriod['pledescripcion'] ?? 'Sin periodo activo')); ?></h3>
        <p>
            <?= $currentPeriod !== null
                ? 'Cursos asignados al docente para el periodo lectivo seleccionado.'
                : 'Activa un periodo lectivo para consultar tus cursos asignados.'; ?>
        </p>
    </div>
</section>

<?php if ($teacherCourses === []): ?>
    <section class="security-assignment-block">
        <div class="empty-state">No tienes cursos activos asignados en el periodo actual.</div>
    </section>
<?php else: ?>
    <section class="module-home-grid teacher-course-grid">
        <?php foreach ($teacherCourses as $course): ?>
            <?php
            $courseId = (int) ($course['curid'] ?? 0);
            $courseLabel = trim((string) (($course['granombre'] ?? '') . ' ' . ($course['prlnombre'] ?? '')));
            $subjects = array_values((array) ($course['subjects'] ?? []));
            ?>
            <a class="module-home-card teacher-course-card" href="<?= $h(baseUrl('docente/curso?curid=' . $courseId)); ?>">
                <span class="module-home-card-icon">
                    <i class="fa fa-book" aria-hidden="true"></i>
                </span>
                <strong><?= $h($courseLabel !== '' ? $courseLabel : 'Curso'); ?></strong>
                <p><?= $h((string) ($course['nednombre'] ?? '')); ?></p>
                <span class="cell-subtitle">
                    <?= $h(count($subjects) . ' materia(s) asignada(s)'); ?>
                </span>
            </a>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
