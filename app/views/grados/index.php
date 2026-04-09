<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';
?>
<div class="toolbar">
    <p>Estructura base de grados por nivel educativo para la gestion academica.</p>
    <a class="btn-primary btn-auto btn-icon-only btn-icon-small" href="<?= htmlspecialchars(baseUrl('grados/crear'), ENT_QUOTES, 'UTF-8'); ?>" title="Nuevo grado" aria-label="Nuevo grado">
        <i class="fa fa-plus" aria-hidden="true"></i>
    </a>
</div>

<div class="toolbar toolbar-filter">
    <div class="filter-box">
        <label class="sr-only" for="grade-search">Buscar grados</label>
        <input
            id="grade-search"
            type="search"
            placeholder="Filtrar por nivel educativo o nombre del grado"
            data-grade-search
            data-grade-search-url="<?= htmlspecialchars(baseUrl('grados/buscar'), ENT_QUOTES, 'UTF-8'); ?>"
            autocomplete="off"
        >
    </div>
    <span class="filter-status" data-grade-search-status><?= count($grades); ?> registro(s)</span>
</div>

<div data-grade-list-wrapper <?= empty($grades) ? '' : 'hidden'; ?>>
    <div class="empty-state">Todavia no hay grados registrados.</div>
</div>

<section id="grados-registrados">
    <?php if (!empty($gradeListFeedback)): ?>
        <div class="catalog-feedback security-feedback-global">
            <div class="alert <?= ($gradeListFeedback['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success'; ?> alert-dismissible" data-alert>
                <span><?= htmlspecialchars((string) ($gradeListFeedback['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                    <i class="fa fa-times" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>

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

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
