<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$requestedPath = (string) ($requestedPath ?? currentPath());
$logoSource = isset($institutionLogo) && $institutionLogo !== null ? asset((string) $institutionLogo) : null;
?>
<section class="forbidden-page" aria-labelledby="forbidden-title">
    <div class="forbidden-identity">
        <?php if ($logoSource !== null): ?>
            <img src="<?= htmlspecialchars($logoSource, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars((string) ($institutionName ?? 'Institucion'), ENT_QUOTES, 'UTF-8'); ?>">
        <?php else: ?>
            <span><?= htmlspecialchars((string) ($institutionInitials ?? 'SG'), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
    </div>

    <div class="forbidden-content">
        <span class="summary-label">Permisos del sistema</span>
        <h3 id="forbidden-title">Acceso restringido</h3>
        <p>No tienes permisos habilitados para acceder a este modulo.</p>
        <div class="forbidden-path">
            <i class="fa fa-lock" aria-hidden="true"></i>
            <span><?= htmlspecialchars($requestedPath, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    </div>

    <div class="forbidden-actions">
        <a class="btn-primary btn-auto" href="<?= htmlspecialchars(baseUrl('dashboard'), ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fa fa-home" aria-hidden="true"></i>
            Ir al inicio
        </a>
        <button class="btn-secondary btn-auto" type="button" onclick="history.back();">
            <i class="fa fa-arrow-left" aria-hidden="true"></i>
            Volver
        </button>
    </div>
</section>
<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
