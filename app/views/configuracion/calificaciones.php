<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$oldScope = (string) ($old['pasalcance'] ?? '');
$oldTarget = (string) ($old['target_id'] ?? '');
?>
<p class="module-note">Crea perfiles de calificacion para un periodo lectivo copiando una plantilla base. Cada perfil conserva su propia version historica.</p>

<?php if (!empty($feedback)): ?>
    <div class="catalog-feedback security-feedback-global">
        <div class="alert <?= ($feedback['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success'; ?> alert-dismissible" data-alert>
            <span><?= $h($feedback['message'] ?? ''); ?></span>
            <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                <i class="fa fa-times" aria-hidden="true"></i>
            </button>
        </div>
    </div>
<?php endif; ?>

<section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3>Crear perfil desde plantilla</h3>
            <p>La copia crea subperiodos, componentes, escalas, ambitos, destrezas y reglas de promocion configuradas en la plantilla.</p>
        </div>
    </header>

    <?php if (empty($templates)): ?>
        <div class="empty-state">No existen plantillas de calificacion activas.</div>
    <?php elseif (empty($periods)): ?>
        <div class="empty-state">No existen periodos lectivos registrados.</div>
    <?php else: ?>
        <form class="data-form" method="POST" action="<?= $h(baseUrl('configuracion/academica/calificaciones/copiar')); ?>">
            <div class="form-grid">
                <div>
                    <div class="input-group">
                        <span class="input-addon">Plantilla</span>
                        <select name="pclid" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($templates as $template): ?>
                                <option value="<?= $h($template['pclid']); ?>" <?= (string) $template['pclid'] === (string) ($old['pclid'] ?? '') ? 'selected' : ''; ?>>
                                    <?= $h($template['pclnombre'] . ' | ' . $template['pcltipo_base']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Periodo</span>
                        <select name="pleid" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($periods as $period): ?>
                                <option value="<?= $h($period['pleid']); ?>" <?= (string) $period['pleid'] === (string) ($old['pleid'] ?? '') ? 'selected' : ''; ?>>
                                    <?= $h($period['pledescripcion']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Nombre</span>
                        <input type="text" name="pcanombre" value="<?= $h($old['pcanombre'] ?? ''); ?>" maxlength="120" required>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Estado</span>
                        <select name="pcaestado">
                            <option value="BORRADOR" <?= (string) ($old['pcaestado'] ?? 'BORRADOR') === 'BORRADOR' ? 'selected' : ''; ?>>Borrador</option>
                            <option value="ACTIVA" <?= (string) ($old['pcaestado'] ?? '') === 'ACTIVA' ? 'selected' : ''; ?>>Activa</option>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Vigencia desde</span>
                        <input type="date" name="pcavigencia_desde" value="<?= $h($old['pcavigencia_desde'] ?? ''); ?>" required>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Vigencia hasta</span>
                        <input type="date" name="pcavigencia_hasta" value="<?= $h($old['pcavigencia_hasta'] ?? ''); ?>">
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Alcance</span>
                        <select name="pasalcance">
                            <option value="" <?= $oldScope === '' ? 'selected' : ''; ?>>Sin asignar</option>
                            <option value="NIVEL" <?= $oldScope === 'NIVEL' ? 'selected' : ''; ?>>Nivel educativo</option>
                            <option value="GRADO" <?= $oldScope === 'GRADO' ? 'selected' : ''; ?>>Grado</option>
                            <option value="CURSO" <?= $oldScope === 'CURSO' ? 'selected' : ''; ?>>Curso</option>
                            <option value="MATERIA" <?= $oldScope === 'MATERIA' ? 'selected' : ''; ?>>Materia del curso</option>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-addon">Destino</span>
                        <select name="target_id">
                            <option value="">Seleccione si asigna alcance</option>
                            <optgroup label="Niveles">
                                <?php foreach ($levels as $level): ?>
                                    <option value="<?= $h($level['nedid']); ?>" <?= $oldScope === 'NIVEL' && $oldTarget === (string) $level['nedid'] ? 'selected' : ''; ?>>
                                        <?= $h($level['nednombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Grados">
                                <?php foreach ($grades as $grade): ?>
                                    <option value="<?= $h($grade['graid']); ?>" <?= $oldScope === 'GRADO' && $oldTarget === (string) $grade['graid'] ? 'selected' : ''; ?>>
                                        <?= $h($grade['nednombre'] . ' | ' . $grade['granombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Cursos">
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= $h($course['curid']); ?>" <?= $oldScope === 'CURSO' && $oldTarget === (string) $course['curid'] ? 'selected' : ''; ?>>
                                        <?= $h($course['pledescripcion'] . ' | ' . $course['granombre'] . ' ' . $course['prlnombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Materias">
                                <?php foreach ($courseSubjects as $subject): ?>
                                    <option value="<?= $h($subject['mtcid']); ?>" <?= $oldScope === 'MATERIA' && $oldTarget === (string) $subject['mtcid'] ? 'selected' : ''; ?>>
                                        <?= $h($subject['pledescripcion'] . ' | ' . $subject['granombre'] . ' ' . $subject['prlnombre'] . ' | ' . $subject['asgnombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                </div>
            </div>
            <div class="actions-row">
                <button class="btn-primary btn-inline" type="submit">Crear perfil</button>
            </div>
        </form>
    <?php endif; ?>
</section>

<section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3>Plantillas disponibles</h3>
            <p>Resumen de modelos base cargados en el sistema.</p>
        </div>
    </header>

    <?php if (empty($templates)): ?>
        <div class="empty-state">No existen plantillas registradas.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Plantilla</th>
                        <th>Tipo</th>
                        <th>Subperiodos</th>
                        <th>Componentes</th>
                        <th>Escalas</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($templates as $template): ?>
                        <tr>
                            <td><span class="cell-title"><?= $h($template['pclnombre']); ?></span></td>
                            <td><?= $h($template['pcltipo_base']); ?></td>
                            <td><?= $h($template['total_subperiodos']); ?></td>
                            <td><?= $h($template['total_componentes']); ?></td>
                            <td><?= $h($template['total_escalas']); ?></td>
                            <td>
                                <a
                                    class="icon-button icon-button-edit"
                                    href="<?= $h(baseUrl('configuracion/academica/calificaciones/plantilla?id=' . (string) $template['pclid'])); ?>"
                                    title="Ver detalle"
                                    aria-label="Ver detalle"
                                >
                                    <i class="fa fa-eye" aria-hidden="true"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="security-assignment-block">
    <header class="security-assignment-header">
        <div>
            <h3>Perfiles creados</h3>
            <p>Configuraciones copiadas para periodos lectivos.</p>
        </div>
    </header>

    <?php if (empty($profiles)): ?>
        <div class="empty-state">Todavia no existen perfiles de calificacion creados.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Periodo</th>
                        <th>Perfil</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Vigencia</th>
                        <th>Subperiodos</th>
                        <th>Asignaciones</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($profiles as $profile): ?>
                        <tr>
                            <td><?= $h($profile['pledescripcion']); ?></td>
                            <td><span class="cell-title"><?= $h($profile['pcanombre']); ?></span></td>
                            <td><?= $h($profile['pcatipo_base']); ?></td>
                            <td><?= $h($profile['pcaestado']); ?></td>
                            <td><?= $h($profile['pcavigencia_desde'] . (($profile['pcavigencia_hasta'] ?? '') !== '' ? ' / ' . $profile['pcavigencia_hasta'] : '')); ?></td>
                            <td><?= $h($profile['total_subperiodos']); ?></td>
                            <td><?= $h($profile['total_asignaciones']); ?></td>
                            <td>
                                <a
                                    class="icon-button icon-button-edit"
                                    href="<?= $h(baseUrl('configuracion/academica/calificaciones/perfil?id=' . (string) $profile['pcaid'])); ?>"
                                    title="Ver perfil"
                                    aria-label="Ver perfil"
                                >
                                    <i class="fa fa-eye" aria-hidden="true"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
