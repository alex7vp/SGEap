<?php

declare(strict_types=1);

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$roleId = (int) ($role['rolid'] ?? 0);
$roleAnchor = 'role-' . $roleId;
$assignedIds = $assignedPermissions[$roleId] ?? [];
$categoryLabels = [
    'asistencia' => 'Asistencia',
    'calificaciones' => 'Calificaciones',
    'contabilidad' => 'Gestion Contable',
    'configuracion' => 'Configuracion',
    'catalogos' => 'Catalogos',
    'cursos' => 'Cursos',
    'dashboard' => 'Dashboard',
    'estudiante' => 'Estudiante',
    'estudiantes' => 'Estudiantes',
    'justificaciones' => 'Justificaciones',
    'matricula_temporal' => 'Matricula temporal',
    'matriculas' => 'Matriculas',
    'novedades' => 'Novedades',
    'personas' => 'Personas',
    'representante' => 'Representantes',
    'seguridad' => 'Seguridad',
    'usuarios_temporales' => 'Usuarios temporales',
];
$permissionCategories = [];

foreach ($permissions as $permission) {
    $code = (string) ($permission['prmcodigo'] ?? '');
    $categoryKey = str_contains($code, '.') ? strstr($code, '.', true) : $code;
    $categoryKey = $categoryKey !== '' ? $categoryKey : 'otros';

    if (!isset($permissionCategories[$categoryKey])) {
        $permissionCategories[$categoryKey] = [
            'label' => $categoryLabels[$categoryKey] ?? ucfirst(str_replace('_', ' ', $categoryKey)),
            'items' => [],
            'assigned' => 0,
        ];
    }

    if (in_array((int) ($permission['prmid'] ?? 0), $assignedIds, true)) {
        $permissionCategories[$categoryKey]['assigned']++;
    }

    $permissionCategories[$categoryKey]['items'][] = $permission;
}

uasort($permissionCategories, static fn (array $a, array $b): int => strcmp((string) $a['label'], (string) $b['label']));
?>
<article class="permission-card" id="<?= $h($roleAnchor); ?>">
    <header class="permission-card-header">
        <div>
            <h3><?= $h($role['rolnombre'] ?? 'Rol'); ?></h3>
            <p><?= $h(($role['roldescripcion'] ?? '') ?: 'Sin descripcion registrada.'); ?></p>
        </div>
        <span class="state-pill <?= !empty($role['rolestado']) ? 'state-pill-active' : 'state-pill-inactive'; ?>">
            <?= !empty($role['rolestado']) ? 'Activo' : 'Inactivo'; ?>
        </span>
    </header>

    <?php if (!empty($assignmentFeedback) && (int) ($assignmentFeedback['role_id'] ?? 0) === $roleId): ?>
        <div class="catalog-feedback">
            <div class="alert <?= ($assignmentFeedback['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success'; ?> alert-dismissible" data-alert>
                <span><?= $h($assignmentFeedback['message'] ?? ''); ?></span>
                <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                    <i class="fa fa-times" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= $h(baseUrl('seguridad/roles-permisos')); ?>" class="permission-form">
        <?= csrfField(); ?>
        <input type="hidden" name="role_id" value="<?= $h($roleId); ?>">

        <div class="permission-category-list">
            <?php foreach ($permissionCategories as $category): ?>
                <?php
                $categoryItems = $category['items'];
                $categoryTotal = count($categoryItems);
                $categoryAssigned = (int) $category['assigned'];
                ?>
                <details class="permission-category">
                    <summary class="permission-category-summary">
                        <span>
                            <strong><?= $h($category['label']); ?></strong>
                            <small><?= $h($categoryAssigned); ?> de <?= $h($categoryTotal); ?> asignados</small>
                        </span>
                        <i class="fa fa-chevron-down" aria-hidden="true"></i>
                    </summary>

                    <div class="permission-list permission-list-compact">
                        <?php foreach ($categoryItems as $permission): ?>
                            <?php
                            $permissionId = (int) $permission['prmid'];
                            $isAssigned = in_array($permissionId, $assignedIds, true);
                            ?>
                            <label class="permission-option">
                                <input
                                    type="checkbox"
                                    name="permission_ids[]"
                                    value="<?= $h($permissionId); ?>"
                                    <?= $isAssigned ? 'checked' : ''; ?>
                                >
                                <span class="permission-option-body">
                                    <span class="permission-option-title">
                                        <strong><?= $h($permission['prmnombre']); ?></strong>
                                        <code><?= $h($permission['prmcodigo']); ?></code>
                                    </span>
                                    <span class="permission-option-description">
                                        <?= $h(($permission['prmdescripcion'] ?? '') ?: 'Sin descripcion registrada.'); ?>
                                    </span>
                                </span>
                                <span class="permission-option-state <?= !empty($permission['prmestado']) ? 'is-active' : 'is-inactive'; ?>">
                                    <?= !empty($permission['prmestado']) ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>

        <div class="actions-row">
            <button class="btn-primary btn-inline" type="submit">Guardar permisos</button>
        </div>
    </form>
</article>
