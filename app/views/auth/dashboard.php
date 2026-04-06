<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';
?>
<section class="dashboard-grid">
    <article class="summary-card">
        <span class="summary-label">Autenticacion</span>
        <strong>Operativa</strong>
        <p>El acceso ya valida contra la tabla <code>usuario</code> y mantiene sesion activa.</p>
    </article>

    <article class="summary-card">
        <span class="summary-label">Personas</span>
        <strong>Listo para registro</strong>
        <p>Se habilito el modulo base para crear y listar personas.</p>
        <a class="text-link" href="<?= htmlspecialchars(baseUrl('personas'), ENT_QUOTES, 'UTF-8'); ?>">Ir a personas</a>
    </article>

    <article class="summary-card">
        <span class="summary-label">Estudiantes</span>
        <strong>Listo para registro</strong>
        <p>Se puede vincular una persona existente como estudiante.</p>
        <a class="text-link" href="<?= htmlspecialchars(baseUrl('estudiantes'), ENT_QUOTES, 'UTF-8'); ?>">Ir a estudiantes</a>
    </article>

    <article class="summary-card">
        <span class="summary-label">Matricula</span>
        <strong>Siguiente paso</strong>
        <p>Ya existe la base relacional para continuar con la matriculacion desde estudiantes.</p>
    </article>
</section>
<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
