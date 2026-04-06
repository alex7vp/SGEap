<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName ?? 'SGEap', ENT_QUOTES, 'UTF-8'); ?> | Dashboard</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset('css/app.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="panel-page">
    <main class="panel-container">
        <section class="panel-card">
            <header class="panel-header">
                <h1><?= htmlspecialchars($appName ?? 'SGEap', ENT_QUOTES, 'UTF-8'); ?></h1>
                <p>Acceso autenticado correctamente.</p>
            </header>

            <div class="panel-body">
                <h2>Dashboard temporal</h2>
                <p>La autenticacion ya esta conectada con la tabla <code>usuario</code>.</p>

                <div class="meta-grid">
                    <div class="meta-item">
                        <strong>ID de usuario</strong>
                        <?= htmlspecialchars((string) ($user['usuid'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                    </div>

                    <div class="meta-item">
                        <strong>ID de persona</strong>
                        <?= htmlspecialchars((string) ($user['perid'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                    </div>

                    <div class="meta-item">
                        <strong>Usuario</strong>
                        <?= htmlspecialchars((string) ($user['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>

                <form method="POST" action="<?= htmlspecialchars(baseUrl('logout'), ENT_QUOTES, 'UTF-8'); ?>">
                    <button class="btn-primary btn-inline" type="submit">Cerrar sesion</button>
                </form>
            </div>
        </section>
    </main>
    <script src="<?= htmlspecialchars(asset('js/app.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
