<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$students = is_array($students ?? null) ? $students : [];
$novelties = is_array($novelties ?? null) ? $novelties : [];
$selectedDate = (string) ($selectedDate ?? '');
$selectedMatriculationId = (int) ($selectedMatriculationId ?? 0);
?>
<p class="module-note">Consulta novedades registradas en el periodo actual y anula registros cuando corresponda.</p>

<?php if ($currentPeriod === null): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para continuar.</div>
<?php else: ?>
    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Filtros</h3>
                <p>Puede filtrar por fecha o estudiante.</p>
            </div>
        </header>
        <form class="data-form" method="GET" action="<?= htmlspecialchars(baseUrl('novedades/supervision'), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-grid">
                <div>
                    <div class="input-group">
                        <span class="input-addon">Fecha</span>
                        <input type="date" name="fecha" value="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Estudiante</span>
                        <select name="matid">
                            <option value="">Todos</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= htmlspecialchars((string) $student['matid'], ENT_QUOTES, 'UTF-8'); ?>" <?= $selectedMatriculationId === (int) $student['matid'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars((string) $student['perapellidos'] . ' ' . $student['pernombres'] . ' | ' . $student['curso'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="actions-row">
                <button class="btn-primary btn-inline" type="submit">Consultar</button>
                <a class="btn-secondary btn-inline" href="<?= htmlspecialchars(baseUrl('novedades/supervision'), ENT_QUOTES, 'UTF-8'); ?>">Limpiar</a>
            </div>
        </form>
    </section>

    <section class="security-assignment-block">
        <header class="security-assignment-header">
            <div>
                <h3>Novedades registradas</h3>
                <p>Se muestran hasta 200 novedades recientes.</p>
            </div>
        </header>
        <?php
        $showActions = true;
        require BASE_PATH . '/app/views/novedades/_tabla.php';
        ?>
    </section>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
