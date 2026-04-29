<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$student = is_array($profile['student'] ?? null) ? $profile['student'] : [];
$matriculation = is_array($profile['matriculation'] ?? null) ? $profile['matriculation'] : null;
$representative = is_array($profile['representative'] ?? null) ? $profile['representative'] : null;
$families = is_array($profile['families'] ?? null) ? $profile['families'] : [];
$healthContext = is_array($profile['health_context'] ?? null) ? $profile['health_context'] : [];
$healthConditions = is_array($profile['health_conditions'] ?? null) ? $profile['health_conditions'] : [];
$healthMeasurement = is_array($profile['health_measurement'] ?? null) ? $profile['health_measurement'] : [];
$academicContext = is_array($profile['academic_context'] ?? null) ? $profile['academic_context'] : [];
$resources = is_array($profile['resources'] ?? null) ? $profile['resources'] : [];
$billing = is_array($profile['billing'] ?? null) ? $profile['billing'] : [];
$documents = is_array($profile['documents'] ?? null) ? $profile['documents'] : [];

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$studentId = (int) ($student['estid'] ?? 0);
$studentName = trim((string) (($student['perapellidos'] ?? '') . ' ' . ($student['pernombres'] ?? '')));
$acceptedDocuments = [];

foreach ($documents as $document) {
    $acceptedDocuments[(int) ($document['domid'] ?? 0)] = !empty($document['madaceptado']);
}

$sexOptions = ['', 'Masculino', 'Femenino'];
$healthConditionTemplate = '<article class="family-card health-condition-card" data-health-condition-row data-health-condition-index="__INDEX__">'
    . '<div class="family-card-header"><strong>Condicion de salud</strong><button class="btn-secondary btn-auto" type="button" data-health-condition-remove>Quitar</button></div>'
    . '<div class="form-grid">'
    . '<div class="form-group"><div class="input-group"><span class="input-addon">Tipo</span><select name="health_conditions[__INDEX__][tcsid]"><option value="">Seleccione</option>';
foreach ($healthConditionTypes as $healthConditionType) {
    $healthConditionTemplate .= '<option value="' . $h($healthConditionType['tcsid'] ?? '') . '">' . $h($healthConditionType['tcsnombre'] ?? '') . '</option>';
}
$healthConditionTemplate .= '</select></div></div>'
    . '<div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Descripcion</span><input name="health_conditions[__INDEX__][ecsadescripcion]"></div></div>'
    . '<div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Medicamentos</span><input name="health_conditions[__INDEX__][ecsamedicamentos]"></div></div>'
    . '<div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Observacion</span><textarea name="health_conditions[__INDEX__][ecsaobservacion]" rows="2"></textarea></div></div>'
    . '<label class="resource-option resource-option-switch family-switch-inline"><span>Vigente</span><span class="switch-control"><input type="checkbox" name="health_conditions[__INDEX__][ecsavigente]" value="1" checked><span class="switch-slider" aria-hidden="true"></span></span></label>'
    . '</div></article>';
?>
<div class="toolbar">
    <p><?= $h($studentName !== '' ? $studentName : 'Estudiante sin nombre'); ?></p>
    <a class="text-link" href="<?= $h(baseUrl('estudiantes/ver?id=' . $studentId)); ?>">Volver a la ficha</a>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= $h($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= $h($error); ?></div><?php endif; ?>

<section class="summary-card student-module-card">
    <div class="student-module-header">
        <div>
            <span class="summary-label">Modulo del estudiante</span>
            <h3><?= $h($sectionTitle); ?></h3>
        </div>
    </div>

    <form class="data-form student-module-form" method="POST" action="<?= $h(baseUrl('estudiantes/modulo/actualizar')); ?>">
        <input type="hidden" name="estid" value="<?= $h($studentId); ?>">
        <input type="hidden" name="section" value="<?= $h($section); ?>">

        <?php if ($section === 'estudiante'): ?>
            <div class="form-grid">
                <div class="form-group"><div class="input-group"><span class="input-addon">Cedula</span><input name="percedula" maxlength="10" inputmode="numeric" required value="<?= $h($student['percedula'] ?? ''); ?>"></div></div>
                <div class="form-group"><div class="input-group"><span class="input-addon">Sexo</span><select name="persexo"><?php foreach ($sexOptions as $option): ?><option value="<?= $h($option); ?>" <?= (string) ($student['persexo'] ?? '') === $option ? 'selected' : ''; ?>><?= $option === '' ? 'Seleccione' : $h($option); ?></option><?php endforeach; ?></select></div></div>
                <div class="form-group"><div class="input-group"><span class="input-addon">Nombres</span><input name="pernombres" required value="<?= $h($student['pernombres'] ?? ''); ?>"></div></div>
                <div class="form-group"><div class="input-group"><span class="input-addon">Apellidos</span><input name="perapellidos" required value="<?= $h($student['perapellidos'] ?? ''); ?>"></div></div>
                <div class="form-group"><div class="input-group"><span class="input-addon">Nacimiento</span><input name="perfechanacimiento" type="date" value="<?= $h($student['perfechanacimiento'] ?? ''); ?>"></div></div>
                <div class="form-group"><div class="input-group"><span class="input-addon">Celular</span><input name="pertelefono1" value="<?= $h($student['pertelefono1'] ?? ''); ?>"></div></div>
                <div class="form-group"><div class="input-group"><span class="input-addon">Fijo</span><input name="pertelefono2" value="<?= $h($student['pertelefono2'] ?? ''); ?>"></div></div>
                <div class="form-group"><div class="input-group"><span class="input-addon">Correo</span><input name="percorreo" type="email" value="<?= $h($student['percorreo'] ?? ''); ?>"></div></div>
                <div class="form-group"><div class="input-group"><span class="input-addon">Profesion</span><input name="perprofesion" value="<?= $h($student['perprofesion'] ?? ''); ?>"></div></div>
                <div class="form-group"><div class="input-group"><span class="input-addon">Ocupacion</span><input name="perocupacion" value="<?= $h($student['perocupacion'] ?? ''); ?>"></div></div>
                <div class="form-group"><div class="input-group"><span class="input-addon">Origen</span><input name="estlugarnacimiento" value="<?= $h($student['estlugarnacimiento'] ?? ''); ?>"></div></div>
                <div class="form-group"><div class="input-group"><span class="input-addon">Parroquia</span><input name="estparroquia" value="<?= $h($student['estparroquia'] ?? ''); ?>"></div></div>
                <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Direccion</span><input name="estdireccion" value="<?= $h($student['estdireccion'] ?? ''); ?>"></div></div>
                <div class="form-group"><div class="input-group"><span class="input-addon">Estado</span><select name="estestado"><option value="1" <?= !empty($student['estestado']) ? 'selected' : ''; ?>>Activo</option><option value="0" <?= empty($student['estestado']) ? 'selected' : ''; ?>>Inactivo</option></select></div></div>
                <input type="hidden" name="curid" value="<?= $h($student['curid'] ?? 0); ?>">
            </div>
        <?php elseif ($section === 'matricula'): ?>
            <?php if ($matriculation === null): ?>
                <p class="empty-state">El estudiante no tiene matricula asociada en el periodo actual.</p>
            <?php else: ?>
                <div class="form-grid">
                    <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Curso</span><select name="curid" required><?php foreach ($courses as $course): ?><option value="<?= $h($course['curid']); ?>" <?= (int) ($matriculation['curid'] ?? 0) === (int) $course['curid'] ? 'selected' : ''; ?>><?= $h(($course['granombre'] ?? '') . ' ' . ($course['prlnombre'] ?? '')); ?></option><?php endforeach; ?></select></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Fecha</span><input name="matfecha" type="date" required value="<?= $h($matriculation['matfecha'] ?? ''); ?>"></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Estado</span><select name="emdid" required><?php foreach ($enrollmentStatuses as $status): ?><option value="<?= $h($status['emdid']); ?>" <?= (int) ($matriculation['emdid'] ?? 0) === (int) $status['emdid'] ? 'selected' : ''; ?>><?= $h($status['emdnombre'] ?? ''); ?></option><?php endforeach; ?></select></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Tipo</span><select name="tmaid" required><?php foreach ($enrollmentTypes as $type): ?><option value="<?= $h($type['tmaid']); ?>" <?= (int) ($matriculation['tmaid'] ?? 0) === (int) $type['tmaid'] ? 'selected' : ''; ?>><?= $h($type['tmanombre'] ?? ''); ?></option><?php endforeach; ?></select></div></div>
                </div>
            <?php endif; ?>
        <?php elseif ($section === 'representante'): ?>
            <?php if ($representative === null): ?>
                <p class="empty-state">No existe representante vinculado a esta matricula.</p>
            <?php else: ?>
                <input type="hidden" name="perid" value="<?= $h($representative['perid'] ?? 0); ?>">
                <div class="form-grid">
                    <div class="form-group"><div class="input-group"><span class="input-addon">Cedula</span><input name="percedula" maxlength="10" inputmode="numeric" required value="<?= $h($representative['percedula'] ?? ''); ?>"></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Parentesco</span><select name="pteid" required><?php foreach ($relationships as $relationship): ?><option value="<?= $h($relationship['pteid']); ?>" <?= (int) ($representative['pteid'] ?? 0) === (int) $relationship['pteid'] ? 'selected' : ''; ?>><?= $h($relationship['ptenombre'] ?? ''); ?></option><?php endforeach; ?></select></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Nombres</span><input name="pernombres" required value="<?= $h($representative['pernombres'] ?? ''); ?>"></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Apellidos</span><input name="perapellidos" required value="<?= $h($representative['perapellidos'] ?? ''); ?>"></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Nacimiento</span><input name="perfechanacimiento" type="date" value="<?= $h($representative['perfechanacimiento'] ?? ''); ?>"></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Telefono</span><input name="pertelefono1" value="<?= $h($representative['pertelefono1'] ?? ''); ?>"></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Correo</span><input name="percorreo" type="email" value="<?= $h($representative['percorreo'] ?? ''); ?>"></div></div>
                </div>
            <?php endif; ?>
        <?php elseif ($section === 'familiares'): ?>
            <?php if ($families === []): ?>
                <p class="empty-state">No existen familiares registrados.</p>
            <?php else: ?>
                <div class="student-module-list">
                    <?php foreach ($families as $index => $family): ?>
                        <article class="student-module-edit-item">
                            <input type="hidden" name="families[<?= $h($index); ?>][famid]" value="<?= $h($family['famid'] ?? 0); ?>">
                            <input type="hidden" name="families[<?= $h($index); ?>][perid]" value="<?= $h($family['perid'] ?? 0); ?>">
                            <div class="form-grid">
                                <div class="form-group"><div class="input-group"><span class="input-addon">Cedula</span><input name="families[<?= $h($index); ?>][percedula]" maxlength="10" inputmode="numeric" required value="<?= $h($family['percedula'] ?? ''); ?>"></div></div>
                                <div class="form-group"><div class="input-group"><span class="input-addon">Parentesco</span><select name="families[<?= $h($index); ?>][pteid]" required><?php foreach ($relationships as $relationship): ?><option value="<?= $h($relationship['pteid']); ?>" <?= (int) ($family['pteid'] ?? 0) === (int) $relationship['pteid'] ? 'selected' : ''; ?>><?= $h($relationship['ptenombre'] ?? ''); ?></option><?php endforeach; ?></select></div></div>
                                <div class="form-group"><div class="input-group"><span class="input-addon">Nombres</span><input name="families[<?= $h($index); ?>][pernombres]" required value="<?= $h($family['pernombres'] ?? ''); ?>"></div></div>
                                <div class="form-group"><div class="input-group"><span class="input-addon">Apellidos</span><input name="families[<?= $h($index); ?>][perapellidos]" required value="<?= $h($family['perapellidos'] ?? ''); ?>"></div></div>
                                <div class="form-group"><div class="input-group"><span class="input-addon">Nacimiento</span><input name="families[<?= $h($index); ?>][perfechanacimiento]" type="date" value="<?= $h($family['perfechanacimiento'] ?? ''); ?>"></div></div>
                                <div class="form-group"><div class="input-group"><span class="input-addon">Telefono</span><input name="families[<?= $h($index); ?>][pertelefono1]" value="<?= $h($family['pertelefono1'] ?? ''); ?>"></div></div>
                                <div class="form-group"><div class="input-group"><span class="input-addon">Correo</span><input name="families[<?= $h($index); ?>][percorreo]" type="email" value="<?= $h($family['percorreo'] ?? ''); ?>"></div></div>
                                <div class="form-group"><div class="input-group"><span class="input-addon">Trabajo</span><input name="families[<?= $h($index); ?>][famlugardetrabajo]" value="<?= $h($family['famlugardetrabajo'] ?? ''); ?>"></div></div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php elseif ($section === 'salud'): ?>
            <div class="form-grid">
                <div class="form-group"><div class="input-group"><span class="input-addon">Grupo sanguineo</span><select name="gsid"><option value="">Seleccione</option><?php foreach ($bloodGroups as $bloodGroup): ?><option value="<?= $h($bloodGroup['gsid']); ?>" <?= (int) ($healthContext['gsid'] ?? 0) === (int) $bloodGroup['gsid'] ? 'selected' : ''; ?>><?= $h($bloodGroup['gsnombre'] ?? ''); ?></option><?php endforeach; ?></select></div></div>
                <div class="form-group"><div class="input-group"><span class="input-addon">Atencion medica</span><select name="amid"><option value="">Seleccione</option><?php foreach ($medicalCareTypes as $medicalCareType): ?><option value="<?= $h($medicalCareType['amid']); ?>" <?= (int) ($healthContext['amid'] ?? 0) === (int) $medicalCareType['amid'] ? 'selected' : ''; ?>><?= $h($medicalCareType['amnombre'] ?? ''); ?></option><?php endforeach; ?></select></div></div>
                <label class="resource-option resource-option-switch family-switch-inline"><span>Tiene discapacidad</span><span class="switch-control"><input type="checkbox" name="ecstienediscapacidad" value="1" <?= !empty($healthContext['ecstienediscapacidad']) ? 'checked' : ''; ?> data-disability-toggle><span class="switch-slider" aria-hidden="true"></span></span></label>
                <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Detalle discapacidad</span><textarea name="ecsdetallediscapacidad" rows="2" data-disability-detail><?= $h($healthContext['ecsdetallediscapacidad'] ?? ''); ?></textarea></div></div>
            </div>
            <div class="family-actions">
                <button class="btn-secondary btn-inline" type="button" data-health-condition-add>Agregar condicion de salud</button>
            </div>
            <div class="family-stack" data-health-condition-rows>
                <?php foreach ($healthConditions as $healthIndex => $healthCondition): ?>
                    <article class="family-card health-condition-card" data-health-condition-row data-health-condition-index="<?= $h($healthIndex); ?>">
                        <div class="family-card-header">
                            <strong>Condicion de salud <?= $h($healthIndex + 1); ?></strong>
                            <button class="btn-secondary btn-auto" type="button" data-health-condition-remove>Quitar</button>
                        </div>
                        <div class="form-grid">
                            <div class="form-group"><div class="input-group"><span class="input-addon">Tipo</span><select name="health_conditions[<?= $h($healthIndex); ?>][tcsid]"><option value="">Seleccione</option><?php foreach ($healthConditionTypes as $healthConditionType): ?><option value="<?= $h($healthConditionType['tcsid']); ?>" <?= (int) ($healthCondition['tcsid'] ?? 0) === (int) $healthConditionType['tcsid'] ? 'selected' : ''; ?>><?= $h($healthConditionType['tcsnombre'] ?? ''); ?></option><?php endforeach; ?></select></div></div>
                            <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Descripcion</span><input name="health_conditions[<?= $h($healthIndex); ?>][ecsadescripcion]" value="<?= $h($healthCondition['ecsadescripcion'] ?? ''); ?>"></div></div>
                            <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Medicamentos</span><input name="health_conditions[<?= $h($healthIndex); ?>][ecsamedicamentos]" value="<?= $h($healthCondition['ecsamedicamentos'] ?? ''); ?>"></div></div>
                            <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Observacion</span><textarea name="health_conditions[<?= $h($healthIndex); ?>][ecsaobservacion]" rows="2"><?= $h($healthCondition['ecsaobservacion'] ?? ''); ?></textarea></div></div>
                            <label class="resource-option resource-option-switch family-switch-inline"><span>Vigente</span><span class="switch-control"><input type="checkbox" name="health_conditions[<?= $h($healthIndex); ?>][ecsavigente]" value="1" <?= !array_key_exists('ecsavigente', $healthCondition) || !empty($healthCondition['ecsavigente']) ? 'checked' : ''; ?>><span class="switch-slider" aria-hidden="true"></span></span></label>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <template data-health-condition-template><?= $healthConditionTemplate; ?></template>
            <div class="form-grid">
                <div class="form-group"><div class="input-group"><span class="input-addon">Peso (kg)</span><input name="health_measurement[emspeso]" type="number" step="0.01" min="0" value="<?= $h($healthMeasurement['emspeso'] ?? ''); ?>" data-imc-weight></div></div>
                <div class="form-group"><div class="input-group"><span class="input-addon">Talla (cm)</span><input name="health_measurement[emstalla]" type="number" step="0.1" min="0" value="<?= $h($healthMeasurement['emstalla'] ?? ''); ?>" data-imc-height></div></div>
                <div class="form-group"><div class="input-group"><span class="input-addon">IMC</span><input name="health_measurement[emsimc]" readonly value="<?= $h($healthMeasurement['emsimc'] ?? ''); ?>" data-imc-output></div></div>
                <div class="form-group"><div class="input-group"><span class="input-addon">Fecha medicion</span><input name="health_measurement[emsfecha_medicion]" type="date" value="<?= $h($healthMeasurement['emsfecha_medicion'] ?? date('Y-m-d')); ?>"></div></div>
                <div class="form-group form-group-full"><div class="alert alert-success form-field-alert imc-alert" data-imc-alert hidden></div></div>
                <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Obs. medicion</span><input name="health_measurement[emsobservacion]" value="<?= $h($healthMeasurement['emsobservacion'] ?? ''); ?>"></div></div>
            </div>
        <?php elseif ($section === 'academico'): ?>
            <div class="form-grid">
                <div class="form-group"><div class="input-group"><span class="input-addon">Ingreso institucion</span><input name="ecafechaingresoinstitucion" type="date" value="<?= $h($academicContext['ecafechaingresoinstitucion'] ?? ''); ?>"></div></div>
                <label class="resource-option resource-option-switch family-switch-inline"><span>Ha repetido años</span><span class="switch-control"><input type="checkbox" name="ecaharepetidoanios" value="1" <?= !empty($academicContext['ecaharepetidoanios']) ? 'checked' : ''; ?> data-repeated-years-toggle><span class="switch-slider" aria-hidden="true"></span></span></label>
                <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Detalle repeticion</span><textarea name="ecadetallerepeticion" rows="2" data-repeated-years-detail <?= empty($academicContext['ecaharepetidoanios']) ? 'disabled' : ''; ?>><?= $h($academicContext['ecadetallerepeticion'] ?? ''); ?></textarea></div></div>
                <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Asignaturas preferidas</span><textarea name="ecaasignaturaspreferencia" rows="2"><?= $h($academicContext['ecaasignaturaspreferencia'] ?? ''); ?></textarea></div></div>
                <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Asignaturas dificultad</span><textarea name="ecaasignaturasdificultad" rows="2"><?= $h($academicContext['ecaasignaturasdificultad'] ?? ''); ?></textarea></div></div>
                <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Actividades extras</span><textarea name="ecaactividadesextras" rows="2"><?= $h($academicContext['ecaactividadesextras'] ?? ''); ?></textarea></div></div>
            </div>
        <?php elseif ($section === 'recursos'): ?>
            <div class="resource-grid">
                <?php foreach (['mrtinternet' => 'Internet', 'mrtcomputador' => 'Computador', 'mrtlaptop' => 'Laptop', 'mrttablet' => 'Tablet', 'mrtcelular' => 'Telefono inteligente', 'mrtimpresora' => 'Impresora'] as $field => $label): ?>
                    <label class="resource-option"><span><?= $h($label); ?></span><input type="checkbox" name="<?= $h($field); ?>" value="1" <?= !empty($resources[$field]) ? 'checked' : ''; ?>></label>
                <?php endforeach; ?>
            </div>
        <?php elseif ($section === 'facturacion'): ?>
            <div class="form-grid">
                <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Nombre / Razon social</span><input name="mfcnombre" required value="<?= $h($billing['mfcnombre'] ?? ''); ?>"></div></div>
                <div class="form-group"><div class="input-group"><span class="input-addon">Tipo ID</span><select name="mfctipoidentificacion" required data-billing-id-type><option value="CEDULA" <?= ($billing['mfctipoidentificacion'] ?? 'CEDULA') === 'CEDULA' ? 'selected' : ''; ?>>Cedula</option><option value="RUC" <?= ($billing['mfctipoidentificacion'] ?? '') === 'RUC' ? 'selected' : ''; ?>>RUC</option></select></div></div>
                <div class="form-group"><div class="input-group"><span class="input-addon">Identificacion</span><input name="mfcidentificacion" required inputmode="numeric" maxlength="<?= ($billing['mfctipoidentificacion'] ?? 'CEDULA') === 'RUC' ? '13' : '10'; ?>" value="<?= $h($billing['mfcidentificacion'] ?? ''); ?>" data-billing-id-number></div></div>
                <div class="form-group"><div class="input-group"><span class="input-addon">Celular</span><input name="mfctelefono" value="<?= $h($billing['mfctelefono'] ?? ''); ?>" data-phone-mask></div></div>
                <div class="form-group"><div class="input-group"><span class="input-addon">Correo</span><input name="mfccorreo" type="email" value="<?= $h($billing['mfccorreo'] ?? ''); ?>"></div></div>
                <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Direccion</span><input name="mfcdireccion" value="<?= $h($billing['mfcdireccion'] ?? ''); ?>"></div></div>
            </div>
        <?php elseif ($section === 'documentos'): ?>
            <div class="student-module-list">
                <?php foreach ($documentsCatalog as $document): ?>
                    <?php $documentId = (int) ($document['domid'] ?? 0); ?>
                    <label class="resource-option">
                        <span><?= $h($document['domnombre'] ?? 'Documento'); ?><?= !empty($document['domobligatorio']) ? ' | Obligatorio' : ''; ?></span>
                        <input type="checkbox" name="documents[<?= $h($documentId); ?>]" value="1" <?= !empty($acceptedDocuments[$documentId]) ? 'checked' : ''; ?>>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <a class="btn-secondary btn-auto" href="<?= $h(baseUrl('estudiantes/ver?id=' . $studentId)); ?>">Cancelar</a>
            <button type="submit" class="btn-primary btn-auto">Guardar cambios</button>
        </div>
    </form>
</section>
<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
