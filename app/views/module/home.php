<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$moduleCards = is_array($moduleCards ?? null) ? $moduleCards : [];
?>
<p class="module-note"><?= htmlspecialchars((string) ($moduleDescription ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>

<?php if (empty($moduleCards)): ?>
    <div class="empty-state">Este modulo todavia no tiene opciones disponibles.</div>
<?php else: ?>
    <section class="module-home-grid">
        <?php foreach ($moduleCards as $card): ?>
            <?php $cardUrl = $card['url'] ?? null; ?>
            <?php if (is_string($cardUrl) && $cardUrl !== ''): ?>
                <a class="module-home-card" href="<?= htmlspecialchars($cardUrl, ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="module-home-card-icon">
                        <i class="fa <?= htmlspecialchars((string) ($card['icon'] ?? 'fa-circle'), ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                    </span>
                    <strong><?= htmlspecialchars((string) ($card['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                    <p><?= htmlspecialchars((string) ($card['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                </a>
            <?php else: ?>
                <article class="module-home-card is-disabled">
                    <span class="module-home-card-icon">
                        <i class="fa <?= htmlspecialchars((string) ($card['icon'] ?? 'fa-circle'), ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                    </span>
                    <strong><?= htmlspecialchars((string) ($card['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                    <p><?= htmlspecialchars((string) ($card['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    <span class="cell-subtitle">Disponible proximamente</span>
                </article>
            <?php endif; ?>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
