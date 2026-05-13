<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$availableStudents = is_array($availableStudents ?? null) ? $availableStudents : [];
$selectedStudentId = (int) ($selectedStudentId ?? 0);
$novelties = is_array($novelties ?? null) ? $novelties : [];
$basePath = (string) (($currentSection ?? '') === 'novedades_representante' ? 'novedades/representante' : 'novedades/mis-novedades');
?>
<p class="module-note">Consulta de novedades registradas en el periodo actual.</p>

<?php if ($currentPeriod === null): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para continuar.</div>
<?php elseif ($selectedStudentId <= 0): ?>
    <div class="empty-state">No existen estudiantes disponibles para consultar novedades.</div>
<?php else: ?>
    <?php if ($availableStudents !== []): ?>
        <section class="security-assignment-block">
            <header class="security-assignment-header">
                <div>
                    <h3>Estudiante</h3>
                    <p>Seleccione el estudiante representado que desea consultar.</p>
                </div>
            </header>
            <form class="data-form" method="GET" action="<?= htmlspecialchars(baseUrl($basePath), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-grid">
                    <div>
                        <div class="input-group">
                            <span class="input-addon">Estudiante</span>
                            <select name="estid" required>
                                <?php foreach ($availableStudents as $student): ?>
                                    <option value="<?= htmlspecialchars((string) $student['estid'], ENT_QUOTES, 'UTF-8'); ?>" <?= $selectedStudentId === (int) $student['estid'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars((string) $student['perapellidos'] . ' ' . $student['pernombres'] . ' | ' . ($student['curso'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="actions-row">
                    <button class="btn-primary btn-inline" type="submit">Consultar</button>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Novedades</h3>
                <p>Listado de novedades registradas en la matricula del periodo actual.</p>
            </div>
        </header>
        <?php
        $showActions = false;
        require BASE_PATH . '/app/views/novedades/_tabla.php';
        ?>
    </section>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
