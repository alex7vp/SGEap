<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName ?? 'SGEap', ENT_QUOTES, 'UTF-8'); ?> | <?= htmlspecialchars($pageTitle ?? 'Panel', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset('css/app.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="panel-page">
    <main class="shell">
        <aside class="sidebar-card">
            <div class="sidebar-brand">
                <h1><?= htmlspecialchars($appName ?? 'SGEap', ENT_QUOTES, 'UTF-8'); ?></h1>
                <p>Gestion academica</p>
            </div>

            <nav class="sidebar-nav">
                <a class="<?= ($currentSection ?? '') === 'dashboard' ? 'is-active' : ''; ?>" href="<?= htmlspecialchars(baseUrl('dashboard'), ENT_QUOTES, 'UTF-8'); ?>">Dashboard</a>
                <a class="<?= ($currentSection ?? '') === 'personas' ? 'is-active' : ''; ?>" href="<?= htmlspecialchars(baseUrl('personas'), ENT_QUOTES, 'UTF-8'); ?>">Personas</a>
                <a class="<?= ($currentSection ?? '') === 'estudiantes' ? 'is-active' : ''; ?>" href="<?= htmlspecialchars(baseUrl('estudiantes'), ENT_QUOTES, 'UTF-8'); ?>">Estudiantes</a>
            </nav>

            <div class="sidebar-user">
                <strong><?= htmlspecialchars((string) ($user['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                <span>ID persona <?= htmlspecialchars((string) ($user['perid'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>

            <form method="POST" action="<?= htmlspecialchars(baseUrl('logout'), ENT_QUOTES, 'UTF-8'); ?>">
                <button class="btn-secondary" type="submit">Cerrar sesion</button>
            </form>
        </aside>

        <section class="content-card">
            <header class="content-header">
                <div>
                    <p class="eyebrow">Modulo actual</p>
                    <h2><?= htmlspecialchars($pageTitle ?? 'Panel', ENT_QUOTES, 'UTF-8'); ?></h2>
                </div>
            </header>

            <div class="content-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
