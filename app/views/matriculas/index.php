<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$familyRows = $old['families'] ?? [];
?>
<p class="module-note">La matriculacion consolida persona, estudiante, familiares, representante y curso en un solo flujo operativo.</p>

<?php if ($currentPeriod === null): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para continuar.</div>
<?php else: ?>
    <section class="security-assignment-block" id="matricula-form">
        <header class="security-assignment-header">
            <div>
                <h3>Nueva matricula</h3>
                <p>El registro se guardara en el periodo actual: <strong><?= htmlspecialchars((string) $currentPeriod['pledescripcion'], ENT_QUOTES, 'UTF-8'); ?></strong>.</p>
            </div>
        </header>

        <?php if (!empty($matriculaFormFeedback)): ?>
            <div class="catalog-feedback security-feedback-global">
                <div class="alert <?= ($matriculaFormFeedback['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success'; ?> alert-dismissible" data-alert>
                    <span><?= htmlspecialchars((string) ($matriculaFormFeedback['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                    <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                        <i class="fa fa-times" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($courses) || empty($enrollmentStatuses) || empty($relationships)): ?>
            <div class="empty-state">Para matricular necesitas cursos activos del periodo, estados de matricula y parentescos registrados.</div>
        <?php else: ?>
            <form class="data-form matricula-form" method="POST" action="<?= htmlspecialchars(baseUrl('matriculas'), ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data" data-matricula-form>
                <div class="alert alert-success alert-dismissible matricula-draft-alert" data-matricula-draft-alert hidden>
                    <span>Borrador guardado localmente. Puedes continuar con la matricula y finalizarla despues.</span>
                    <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                        <i class="fa fa-times" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="wizard-tabs" role="tablist" aria-label="Secciones de matricula">
                    <button type="button" class="wizard-tab is-active" data-wizard-tab="persona">Persona</button>
                    <button type="button" class="wizard-tab" data-wizard-tab="estudiante">Estudiante</button>
                    <button type="button" class="wizard-tab" data-wizard-tab="familiares">Familiares</button>
                    <button type="button" class="wizard-tab" data-wizard-tab="representante">Representante</button>
                    <button type="button" class="wizard-tab" data-wizard-tab="matricula">Matricula</button>
                </div>

                <section class="wizard-panel is-active" data-wizard-panel="persona">
                    <div class="form-grid">
                        <div class="form-group"><div class="input-group"><span class="input-addon">Cedula</span><input name="person[percedula]" maxlength="10" minlength="10" pattern="\d{10}" inputmode="numeric" required value="<?= htmlspecialchars((string) ($old['person']['percedula'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Sexo</span><select name="person[persexo]"><option value="">Seleccione</option><option value="Masculino" <?= ($old['person']['persexo'] ?? '') === 'Masculino' ? 'selected' : ''; ?>>Masculino</option><option value="Femenino" <?= ($old['person']['persexo'] ?? '') === 'Femenino' ? 'selected' : ''; ?>>Femenino</option></select></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Nombres</span><input name="person[pernombres]" required value="<?= htmlspecialchars((string) ($old['person']['pernombres'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Apellidos</span><input name="person[perapellidos]" required value="<?= htmlspecialchars((string) ($old['person']['perapellidos'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Celular</span><input name="person[pertelefono1]" placeholder="(09) 9894 5698" maxlength="14" inputmode="numeric" value="<?= htmlspecialchars((string) ($old['person']['pertelefono1'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-phone-mask></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Fijo</span><input name="person[pertelefono2]" value="<?= htmlspecialchars((string) ($old['person']['pertelefono2'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">E-mail</span><input name="person[percorreo]" type="email" value="<?= htmlspecialchars((string) ($old['person']['percorreo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                    </div>
                    <div class="actions-row">
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-save>Guardar</button>
                    </div>
                </section>

                <section class="wizard-panel" data-wizard-panel="estudiante" hidden>
                    <div class="form-grid">
                        <div class="form-group"><div class="input-group"><span class="input-addon">Origen</span><input name="student[estlugarnacimiento]" value="<?= htmlspecialchars((string) ($old['student']['estlugarnacimiento'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Parroquia</span><input name="student[estparroquia]" value="<?= htmlspecialchars((string) ($old['student']['estparroquia'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Direccion</span><input name="student[estdireccion]" value="<?= htmlspecialchars((string) ($old['student']['estdireccion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Estado</span><select name="student[estestado]"><option value="1" <?= !empty($old['student']['estestado']) ? 'selected' : ''; ?>>Activo</option><option value="0" <?= empty($old['student']['estestado']) ? 'selected' : ''; ?>>Inactivo</option></select></div></div>
                    </div>
                    <div class="actions-row">
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-save>Guardar</button>
                    </div>
                </section>

                <section class="wizard-panel" data-wizard-panel="familiares" hidden>
                    <div class="toolbar toolbar-filter">
                        <p>Agrega uno o varios familiares vinculados al estudiante. Desde aqui se construye luego el representante.</p>
                        <button class="btn-primary btn-auto" type="button" data-family-add>Agregar familiar</button>
                    </div>
                    <div class="family-stack" data-family-rows>
                        <?php foreach ($familyRows as $index => $family): ?>
                            <article class="family-card" data-family-row data-family-index="<?= htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="family-card-header">
                                    <strong>Familiar <?= $index + 1; ?></strong>
                                    <button type="button" class="icon-button icon-button-delete" data-family-remove title="Quitar familiar" aria-label="Quitar familiar">&#128465;</button>
                                </div>
                                <div class="form-grid">
                                    <div class="form-group"><div class="input-group"><span class="input-addon">Cedula</span><input name="family[<?= $index; ?>][percedula]" maxlength="10" minlength="10" pattern="\d{10}" inputmode="numeric" value="<?= htmlspecialchars((string) ($family['percedula'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-family-field="cedula"></div></div>
                                    <div class="form-group"><div class="input-group"><span class="input-addon">Sexo</span><select name="family[<?= $index; ?>][persexo]"><option value="">Seleccione</option><option value="Masculino" <?= ($family['persexo'] ?? '') === 'Masculino' ? 'selected' : ''; ?>>Masculino</option><option value="Femenino" <?= ($family['persexo'] ?? '') === 'Femenino' ? 'selected' : ''; ?>>Femenino</option></select></div></div>
                                    <div class="form-group"><div class="input-group"><span class="input-addon">Nombres</span><input name="family[<?= $index; ?>][pernombres]" value="<?= htmlspecialchars((string) ($family['pernombres'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-family-field="nombres"></div></div>
                                    <div class="form-group"><div class="input-group"><span class="input-addon">Apellidos</span><input name="family[<?= $index; ?>][perapellidos]" value="<?= htmlspecialchars((string) ($family['perapellidos'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-family-field="apellidos"></div></div>
                                    <div class="form-group"><div class="input-group"><span class="input-addon">Parentesco</span><select name="family[<?= $index; ?>][pteid]" data-family-field="parentesco"><option value="">Seleccione</option><?php foreach ($relationships as $relationship): ?><option value="<?= htmlspecialchars((string) $relationship['pteid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($family['pteid'] ?? 0) === (int) $relationship['pteid'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) $relationship['ptenombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
                                    <div class="form-group"><div class="input-group"><span class="input-addon">Estado civil</span><select name="family[<?= $index; ?>][eciid]"><option value="">Seleccione</option><?php foreach ($civilStatuses as $civilStatus): ?><option value="<?= htmlspecialchars((string) $civilStatus['eciid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($family['eciid'] ?? 0) === (int) $civilStatus['eciid'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) $civilStatus['ecinombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
                                    <div class="form-group"><div class="input-group"><span class="input-addon">Instruccion</span><select name="family[<?= $index; ?>][istid]"><option value="">Seleccione</option><?php foreach ($instructionLevels as $instructionLevel): ?><option value="<?= htmlspecialchars((string) $instructionLevel['istid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($family['istid'] ?? 0) === (int) $instructionLevel['istid'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) $instructionLevel['istnombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
                                    <div class="form-group"><div class="input-group"><span class="input-addon">Celular</span><input name="family[<?= $index; ?>][pertelefono1]" placeholder="(09) 9894 5698" maxlength="14" inputmode="numeric" value="<?= htmlspecialchars((string) ($family['pertelefono1'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-phone-mask></div></div>
                                    <div class="form-group"><div class="input-group"><span class="input-addon">Correo</span><input name="family[<?= $index; ?>][percorreo]" type="email" value="<?= htmlspecialchars((string) ($family['percorreo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                                    <div class="form-group"><div class="input-group"><span class="input-addon">Profesion</span><input name="family[<?= $index; ?>][famprofesion]" value="<?= htmlspecialchars((string) ($family['famprofesion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                                    <div class="form-group"><div class="input-group"><span class="input-addon">Trabajo</span><input name="family[<?= $index; ?>][famlugardetrabajo]" value="<?= htmlspecialchars((string) ($family['famlugardetrabajo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                                    <div class="form-group"><div class="input-group"><span class="input-addon">Nacimiento</span><input name="family[<?= $index; ?>][famfechanacimiento]" type="date" value="<?= htmlspecialchars((string) ($family['famfechanacimiento'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <div class="actions-row">
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-save>Guardar</button>
                    </div>
                </section>

                <section class="wizard-panel" data-wizard-panel="representante" hidden>
                    <p class="module-note">Selecciona uno de los familiares cargados para que quede como representante de la matricula.</p>
                    <input type="hidden" name="representative_index" value="<?= htmlspecialchars((string) ($old['representative_index'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" data-representative-index-input>
                    <div class="representative-options" data-representative-options></div>
                    <div class="actions-row">
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-save>Guardar</button>
                    </div>
                </section>

                <section class="wizard-panel" data-wizard-panel="matricula" hidden>
                    <div class="form-grid">
                        <div class="form-group"><div class="input-group"><span class="input-addon">Periodo</span><input type="text" value="<?= htmlspecialchars((string) $currentPeriod['pledescripcion'], ENT_QUOTES, 'UTF-8'); ?>" readonly></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Fecha</span><input type="date" name="matricula[matfecha]" required value="<?= htmlspecialchars((string) ($old['matricula']['matfecha'] ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Curso</span><select name="matricula[curid]" required><option value="">Seleccione</option><?php foreach ($courses as $course): ?><option value="<?= htmlspecialchars((string) $course['curid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($old['matricula']['curid'] ?? 0) === (int) $course['curid'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) ($course['nednombre'] . ' | ' . $course['granombre'] . ' | ' . $course['prlnombre']), ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Estado</span><select name="matricula[emdid]" required><option value="">Seleccione</option><?php foreach ($enrollmentStatuses as $status): ?><option value="<?= htmlspecialchars((string) $status['emdid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($old['matricula']['emdid'] ?? 0) === (int) $status['emdid'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) $status['emdnombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Foto</span><input type="file" name="matricula_photo" accept=".jpg,.jpeg,.png,.webp"></div></div>
                    </div>
                    <div class="actions-row">
                        <button class="btn-primary btn-inline" type="submit">Guardar matricula</button>
                    </div>
                </section>
            </form>

            <template data-family-template>
                <article class="family-card" data-family-row data-family-index="__INDEX__">
                    <div class="family-card-header"><strong>Familiar __NUMBER__</strong><button type="button" class="icon-button icon-button-delete" data-family-remove title="Quitar familiar" aria-label="Quitar familiar">&#128465;</button></div>
                    <div class="form-grid">
                        <div class="form-group"><div class="input-group"><span class="input-addon">Cedula</span><input name="family[__INDEX__][percedula]" maxlength="10" minlength="10" pattern="\d{10}" inputmode="numeric" data-family-field="cedula"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Sexo</span><select name="family[__INDEX__][persexo]"><option value="">Seleccione</option><option value="Masculino">Masculino</option><option value="Femenino">Femenino</option></select></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Nombres</span><input name="family[__INDEX__][pernombres]" data-family-field="nombres"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Apellidos</span><input name="family[__INDEX__][perapellidos]" data-family-field="apellidos"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Parentesco</span><select name="family[__INDEX__][pteid]" data-family-field="parentesco"><option value="">Seleccione</option><?php foreach ($relationships as $relationship): ?><option value="<?= htmlspecialchars((string) $relationship['pteid'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars((string) $relationship['ptenombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Estado civil</span><select name="family[__INDEX__][eciid]"><option value="">Seleccione</option><?php foreach ($civilStatuses as $civilStatus): ?><option value="<?= htmlspecialchars((string) $civilStatus['eciid'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars((string) $civilStatus['ecinombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Instruccion</span><select name="family[__INDEX__][istid]"><option value="">Seleccione</option><?php foreach ($instructionLevels as $instructionLevel): ?><option value="<?= htmlspecialchars((string) $instructionLevel['istid'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars((string) $instructionLevel['istnombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Celular</span><input name="family[__INDEX__][pertelefono1]" placeholder="(09) 9894 5698" maxlength="14" inputmode="numeric" data-phone-mask></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Correo</span><input name="family[__INDEX__][percorreo]" type="email"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Profesion</span><input name="family[__INDEX__][famprofesion]"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Trabajo</span><input name="family[__INDEX__][famlugardetrabajo]"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Nacimiento</span><input name="family[__INDEX__][famfechanacimiento]" type="date"></div></div>
                    </div>
                </article>
            </template>
        <?php endif; ?>
    </section>

    <section class="security-assignment-block" id="matriculas-registradas">
        <header class="security-assignment-header">
            <div><h3>Matriculas registradas</h3><p>Listado de matriculas correspondientes al periodo actual en la sesion.</p></div>
        </header>
        <?php if (!empty($matriculaListFeedback)): ?>
            <div class="catalog-feedback security-feedback-global">
                <div class="alert <?= ($matriculaListFeedback['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success'; ?> alert-dismissible" data-alert>
                    <span><?= htmlspecialchars((string) ($matriculaListFeedback['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                    <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close><i class="fa fa-times" aria-hidden="true"></i></button>
                </div>
            </div>
        <?php endif; ?>
        <?php if (empty($matriculas)): ?>
            <div class="empty-state">Todavia no hay matriculas registradas para este periodo.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Estudiante</th><th>Curso</th><th>Representante</th><th>Estado</th><th>Fecha</th><th>Foto</th></tr></thead>
                    <tbody>
                        <?php foreach ($matriculas as $matricula): ?>
                            <tr>
                                <td><span class="cell-title"><?= htmlspecialchars((string) $matricula['percedula'], ENT_QUOTES, 'UTF-8'); ?></span><span class="cell-subtitle"><strong><?= htmlspecialchars((string) $matricula['perapellidos'], ENT_QUOTES, 'UTF-8'); ?></strong> <?= htmlspecialchars((string) $matricula['pernombres'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><?= htmlspecialchars((string) ($matricula['nednombre'] . ' | ' . $matricula['granombre'] . ' | ' . $matricula['prlnombre']), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars(trim((string) (($matricula['rep_apellidos'] ?? '') . ' ' . ($matricula['rep_nombres'] ?? '')) . (($matricula['rep_parentesco'] ?? '') !== '' ? ' (' . $matricula['rep_parentesco'] . ')' : '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) $matricula['emdnombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) $matricula['matfecha'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php if (!empty($matricula['matfoto'])): ?><a class="text-link" href="<?= htmlspecialchars(asset((string) $matricula['matfoto']), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Ver foto</a><?php else: ?>Sin foto<?php endif; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
