<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$course = is_array($course ?? null) ? $course : [];
$students = is_array($students ?? null) ? $students : [];
$courseId = (int) ($course['curid'] ?? 0);
$courseLabel = trim((string) (($course['granombre'] ?? '') . ' ' . ($course['prlnombre'] ?? '')));
?>
<section class="security-assignment-block">
    <div class="module-header-row">
        <div>
            <h3>Lista del curso</h3>
            <p><?= $h(trim((string) (($course['nednombre'] ?? '') . ' | ' . ($courseLabel !== '' ? $courseLabel : 'Curso')))); ?></p>
        </div>
        <div class="actions-row">
            <a class="btn-secondary btn-auto" href="<?= $h(baseUrl('docente/curso/lista?curid=' . $courseId . '&pdf=1')); ?>" target="_blank" rel="noopener noreferrer">
                <i class="fa fa-file-pdf-o" aria-hidden="true"></i>
                <span>Generar PDF</span>
            </a>
            <a class="btn-secondary btn-auto" href="<?= $h(baseUrl('docente/curso?curid=' . $courseId)); ?>">Volver al curso</a>
        </div>
    </div>

    <?php if ($students === []): ?>
        <div class="empty-state">No existen estudiantes activos para este curso.</div>
    <?php else: ?>
        <table class="data-table compact-data-table">
            <thead>
                <tr>
                    <th style="width: 64px;">#</th>
                    <th>Apellidos y nombres</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $index => $student): ?>
                    <?php $studentName = trim((string) (($student['perapellidos'] ?? '') . ' ' . ($student['pernombres'] ?? ''))); ?>
                    <tr>
                        <td><?= $index + 1; ?></td>
                        <td><?= $h($studentName !== '' ? $studentName : 'Estudiante sin nombre'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
