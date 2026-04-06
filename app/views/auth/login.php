<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName ?? 'SGEap', ENT_QUOTES, 'UTF-8'); ?> | Login</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset('css/app.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="auth-page">
    <section class="auth-card">
        <header class="auth-header">
            <h1><?= htmlspecialchars($appName ?? 'SGEap', ENT_QUOTES, 'UTF-8'); ?></h1>
            <p>Sistema institucional base con PHP nativo y MVC simple.</p>
        </header>

        <div class="auth-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= htmlspecialchars(baseUrl('login'), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input
                        id="username"
                        name="username"
                        type="text"
                        value="<?= htmlspecialchars($oldUsername ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="Ingrese su usuario"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Contrasena</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        placeholder="Ingrese su contrasena"
                        required
                    >
                </div>

                <button class="btn-primary" type="submit">Ingresar</button>
            </form>

            <div class="helper-box">
                Ingrese su nombre de usuario y contrasena registrados en la base de datos.
            </div>
        </div>
    </section>
    <script src="<?= htmlspecialchars(asset('js/app.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
