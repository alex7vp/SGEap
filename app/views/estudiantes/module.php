<?php

declare(strict_types=1);

$moduleSuccess = $success ?? null;
$moduleError = $error ?? null;
$success = null;
$error = null;

require BASE_PATH . '/app/views/partials/header.php';

$student = is_array($profile['student'] ?? null) ? $profile['student'] : [];
$matriculation = is_array($profile['matriculation'] ?? null) ? $profile['matriculation'] : null;
$representative = is_array($profile['representative'] ?? null) ? $profile['representative'] : null;
$families = is_array($profile['families'] ?? null) ? $profile['families'] : [];
$healthContext = is_array($profile['health_context'] ?? null) ? $profile['health_context'] : [];
$healthConditions = is_array($profile['health_conditions'] ?? null) ? $profile['health_conditions'] : [];
$healthMeasurement = is_array($profile['health_measurement'] ?? null) ? $profile['health_measurement'] : [];
$healthMeasurements = is_array($profile['health_measurements'] ?? null) ? $profile['health_measurements'] : [];
$healthInsurance = is_array($profile['health_insurance'] ?? null) ? $profile['health_insurance'] : [];
$vitalHistory = is_array($profile['vital_history'] ?? null) ? $profile['vital_history'] : [];
$academicContext = is_array($profile['academic_context'] ?? null) ? $profile['academic_context'] : [];
$resources = is_array($profile['resources'] ?? null) ? $profile['resources'] : [];
$billing = is_array($profile['billing'] ?? null) ? $profile['billing'] : [];
$documents = is_array($profile['documents'] ?? null) ? $profile['documents'] : [];
$isOwnProfile = !empty($isOwnProfile);
$readOnly = !empty($readOnly);

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$whoBmiReference = require BASE_PATH . '/app/support/who_bmi_for_age.php';
$ageMonthsAtDate = static function (mixed $birthDate, mixed $measurementDate): ?int {
    $birth = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $birthDate);
    $date = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $measurementDate);

    if ($birth === false || $date === false) {
        return null;
    }

    $diff = $birth->diff($date);

    return ($diff->y * 12) + $diff->m;
};
$imcDescription = static function (mixed $value, mixed $birthDate = null, mixed $sex = null, mixed $measurementDate = null) use ($ageMonthsAtDate, $whoBmiReference): string {
    $imc = is_numeric($value) ? (float) $value : 0.0;

    if ($imc <= 0) {
        return '';
    }

    $ageMonths = $ageMonthsAtDate($birthDate, $measurementDate ?: date('Y-m-d'));
    $sexKey = mb_strtolower((string) $sex) === 'femenino' ? 'F' : (mb_strtolower((string) $sex) === 'masculino' ? 'M' : '');

    if ($ageMonths !== null && $ageMonths < 61) {
        return 'Requiere curva OMS menor de 5 años';
    }

    if ($ageMonths !== null && $ageMonths <= 228 && $sexKey !== '') {
        $month = max(61, min(228, $ageMonths));
        $reference = $whoBmiReference[$sexKey][$month] ?? null;

        if (is_array($reference)) {
            if ($imc < (float) $reference['sd3neg']) {
                return 'Delgadez severa';
            }

            if ($imc < (float) $reference['sd2neg']) {
                return 'Delgadez';
            }

            if ($imc <= (float) $reference['sd1']) {
                return 'Peso normal';
            }

            if ($imc <= (float) $reference['sd2']) {
                return 'Sobrepeso';
            }

            return 'Obesidad';
        }
    }

    if ($imc < 18.5) {
        return 'Bajo peso';
    }

    if ($imc < 25) {
        return 'Peso normal';
    }

    if ($imc < 30) {
        return 'Sobrepeso';
    }

    return 'Obesidad';
};
$studentId = (int) ($student['estid'] ?? 0);
$studentName = trim((string) (($student['perapellidos'] ?? '') . ' ' . ($student['pernombres'] ?? '')));
$profileUrl = $isOwnProfile ? baseUrl('mi-matricula') : baseUrl('estudiantes/ver?id=' . $studentId);
$moduleBaseUrl = static fn (string $section, string $panel = ''): string => $isOwnProfile
    ? baseUrl('mi-matricula/modulo?seccion=' . $section . ($panel !== '' ? '&panel=' . $panel : ''))
    : baseUrl('estudiantes/modulo?id=' . $studentId . '&seccion=' . $section . ($panel !== '' ? '&panel=' . $panel : ''));
$acceptedDocuments = [];

foreach ($documents as $document) {
    $acceptedDocuments[(int) ($document['domid'] ?? 0)] = !empty($document['madaceptado']);
}

$sexOptions = ['', 'Masculino', 'Femenino'];
$civilStatusOptions = is_array($civilStatuses ?? null) ? $civilStatuses : [];
$instructionLevelOptions = is_array($instructionLevels ?? null) ? $instructionLevels : [];
$healthChartData = array_map(static fn (array $measurement): array => [
    'fecha' => (string) ($measurement['emsfecha_medicion'] ?? ''),
    'peso' => $measurement['emspeso'] !== null ? (float) $measurement['emspeso'] : null,
    'talla' => $measurement['emstalla'] !== null ? (float) $measurement['emstalla'] : null,
    'imc' => $measurement['emsimc'] !== null ? (float) $measurement['emsimc'] : null,
], $healthMeasurements);
$healthChartReference = (static function (array $measurements, array $student, array $reference) use ($ageMonthsAtDate): array {
    $sexKey = mb_strtolower((string) ($student['persexo'] ?? '')) === 'femenino' ? 'F' : (mb_strtolower((string) ($student['persexo'] ?? '')) === 'masculino' ? 'M' : '');

    if ($sexKey === '' || empty($student['perfechanacimiento']) || !isset($reference[$sexKey])) {
        return [];
    }

    $points = [];

    foreach ($measurements as $measurement) {
        $date = (string) ($measurement['emsfecha_medicion'] ?? '');
        $ageMonths = $ageMonthsAtDate($student['perfechanacimiento'], $date);

        if ($date === '' || $ageMonths === null || $ageMonths < 61 || $ageMonths > 228) {
            continue;
        }

        $month = max(61, min(228, $ageMonths));
        $row = $reference[$sexKey][$month] ?? null;

        if (!is_array($row)) {
            continue;
        }

        $points[] = [
            'fecha' => $date,
            'sd2neg' => (float) $row['sd2neg'],
            'sd1' => (float) $row['sd1'],
            'sd2' => (float) $row['sd2'],
        ];
    }

    return $points;
})($healthMeasurements, $student, $whoBmiReference);
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
    <a class="text-link" href="<?= $h($profileUrl); ?>">Volver a la ficha</a>
</div>

<?php if (!($section === 'salud' && $panel === 'mediciones')): ?>
    <?php if ($moduleSuccess): ?><div class="alert alert-success"><?= $h($moduleSuccess); ?></div><?php endif; ?>
    <?php if ($moduleError): ?><div class="alert alert-error"><?= $h($moduleError); ?></div><?php endif; ?>
<?php endif; ?>

<section class="summary-card student-module-card">
    <div class="student-module-header">
        <div>
            <span class="summary-label">Modulo del estudiante</span>
            <h3><?= $h($sectionTitle); ?></h3>
        </div>
    </div>

    <?php if ($readOnly): ?>
        <div class="alert alert-success">Vista de consulta. Los cambios deben solicitarse a secretaria o administracion.</div>
    <?php endif; ?>

    <form class="data-form student-module-form <?= $readOnly ? 'is-readonly' : ''; ?>" method="POST" action="<?= $h(baseUrl('estudiantes/modulo/actualizar')); ?>">
        <input type="hidden" name="estid" value="<?= $h($studentId); ?>">
        <input type="hidden" name="section" value="<?= $h($section); ?>">
        <?php if ($section === 'salud' && $panel !== ''): ?>
            <input type="hidden" name="panel" value="<?= $h($panel); ?>">
        <?php endif; ?>
        <?php if ($section === 'salud' && $panel === 'mediciones'): ?>
            <script type="application/json" data-who-bmi-reference><?= $h(json_encode($whoBmiReference, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></script>
        <?php endif; ?>

        <?php if ($readOnly): ?><fieldset class="student-readonly-fieldset" disabled><?php endif; ?>

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
                <div class="form-group"><div class="input-group"><span class="input-addon">Lugar de nacimiento</span><input name="estlugarnacimiento" value="<?= $h($student['estlugarnacimiento'] ?? ''); ?>"></div></div>
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
                    <div class="form-group"><div class="input-group"><span class="input-addon">Sexo</span><select name="persexo"><?php foreach ($sexOptions as $option): ?><option value="<?= $h($option); ?>" <?= (string) ($representative['persexo'] ?? '') === $option ? 'selected' : ''; ?>><?= $option === '' ? 'Seleccione' : $h($option); ?></option><?php endforeach; ?></select></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Nacimiento</span><input name="perfechanacimiento" type="date" value="<?= $h($representative['perfechanacimiento'] ?? ''); ?>"></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Celular</span><input name="pertelefono1" value="<?= $h($representative['pertelefono1'] ?? ''); ?>"></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Fijo</span><input name="pertelefono2" value="<?= $h($representative['pertelefono2'] ?? ''); ?>"></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Correo</span><input name="percorreo" type="email" value="<?= $h($representative['percorreo'] ?? ''); ?>"></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Estado civil</span><select name="eciid"><option value="">Seleccione</option><?php foreach ($civilStatusOptions as $civilStatus): ?><option value="<?= $h($civilStatus['eciid']); ?>" <?= (int) ($representative['eciid'] ?? 0) === (int) $civilStatus['eciid'] ? 'selected' : ''; ?>><?= $h($civilStatus['ecinombre'] ?? ''); ?></option><?php endforeach; ?></select></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Instruccion</span><select name="istid"><option value="">Seleccione</option><?php foreach ($instructionLevelOptions as $instructionLevel): ?><option value="<?= $h($instructionLevel['istid']); ?>" <?= (int) ($representative['istid'] ?? 0) === (int) $instructionLevel['istid'] ? 'selected' : ''; ?>><?= $h($instructionLevel['istnombre'] ?? ''); ?></option><?php endforeach; ?></select></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Profesion</span><input name="perprofesion" value="<?= $h($representative['perprofesion'] ?? ''); ?>"></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Ocupacion</span><input name="perocupacion" value="<?= $h($representative['perocupacion'] ?? ''); ?>"></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Lugar de trabajo</span><input name="perlugardetrabajo" value="<?= $h($representative['perlugardetrabajo'] ?? ''); ?>"></div></div>
                    <label class="resource-option resource-option-switch family-switch-inline"><span>Habla ingles</span><span class="switch-control"><input type="checkbox" name="perhablaingles" value="1" <?= !empty($representative['perhablaingles']) ? 'checked' : ''; ?>><span class="switch-slider" aria-hidden="true"></span></span></label>
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
                                <div class="form-group"><div class="input-group"><span class="input-addon">Sexo</span><select name="families[<?= $h($index); ?>][persexo]"><?php foreach ($sexOptions as $option): ?><option value="<?= $h($option); ?>" <?= (string) ($family['persexo'] ?? '') === $option ? 'selected' : ''; ?>><?= $option === '' ? 'Seleccione' : $h($option); ?></option><?php endforeach; ?></select></div></div>
                                <div class="form-group"><div class="input-group"><span class="input-addon">Nacimiento</span><input name="families[<?= $h($index); ?>][perfechanacimiento]" type="date" value="<?= $h($family['perfechanacimiento'] ?? ''); ?>"></div></div>
                                <div class="form-group"><div class="input-group"><span class="input-addon">Celular</span><input name="families[<?= $h($index); ?>][pertelefono1]" value="<?= $h($family['pertelefono1'] ?? ''); ?>"></div></div>
                                <div class="form-group"><div class="input-group"><span class="input-addon">Fijo</span><input name="families[<?= $h($index); ?>][pertelefono2]" value="<?= $h($family['pertelefono2'] ?? ''); ?>"></div></div>
                                <div class="form-group"><div class="input-group"><span class="input-addon">Correo</span><input name="families[<?= $h($index); ?>][percorreo]" type="email" value="<?= $h($family['percorreo'] ?? ''); ?>"></div></div>
                                <div class="form-group"><div class="input-group"><span class="input-addon">Estado civil</span><select name="families[<?= $h($index); ?>][eciid]"><option value="">Seleccione</option><?php foreach ($civilStatusOptions as $civilStatus): ?><option value="<?= $h($civilStatus['eciid']); ?>" <?= (int) ($family['eciid'] ?? 0) === (int) $civilStatus['eciid'] ? 'selected' : ''; ?>><?= $h($civilStatus['ecinombre'] ?? ''); ?></option><?php endforeach; ?></select></div></div>
                                <div class="form-group"><div class="input-group"><span class="input-addon">Instruccion</span><select name="families[<?= $h($index); ?>][istid]"><option value="">Seleccione</option><?php foreach ($instructionLevelOptions as $instructionLevel): ?><option value="<?= $h($instructionLevel['istid']); ?>" <?= (int) ($family['istid'] ?? 0) === (int) $instructionLevel['istid'] ? 'selected' : ''; ?>><?= $h($instructionLevel['istnombre'] ?? ''); ?></option><?php endforeach; ?></select></div></div>
                                <div class="form-group"><div class="input-group"><span class="input-addon">Profesion</span><input name="families[<?= $h($index); ?>][perprofesion]" value="<?= $h($family['perprofesion'] ?? ''); ?>"></div></div>
                                <div class="form-group"><div class="input-group"><span class="input-addon">Ocupacion</span><input name="families[<?= $h($index); ?>][perocupacion]" value="<?= $h($family['perocupacion'] ?? ''); ?>"></div></div>
                                <div class="form-group"><div class="input-group"><span class="input-addon">Lugar de trabajo</span><input name="families[<?= $h($index); ?>][perlugardetrabajo]" value="<?= $h($family['perlugardetrabajo'] ?? ''); ?>"></div></div>
                                <label class="resource-option resource-option-switch family-switch-inline"><span>Habla ingles</span><span class="switch-control"><input type="checkbox" name="families[<?= $h($index); ?>][perhablaingles]" value="1" <?= !empty($family['perhablaingles']) ? 'checked' : ''; ?>><span class="switch-slider" aria-hidden="true"></span></span></label>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php elseif ($section === 'salud'): ?>
            <?php if ($panel === ''): ?>
                <?php
                    $healthCards = [
                        ['panel' => 'general', 'label' => 'Datos generales de salud', 'value' => $healthContext['gsnombre'] ?? 'Sin grupo sanguineo'],
                        ['panel' => 'condiciones', 'label' => 'Condiciones de salud', 'value' => count($healthConditions) . ' registrada(s)'],
                        ['panel' => 'historia-vital', 'label' => 'Historia vital', 'value' => !empty($vitalHistory) ? 'Registrada' : 'Pendiente'],
                        ['panel' => 'mediciones', 'label' => 'Mediciones', 'value' => count($healthMeasurements) . ' medicion(es)'],
                    ];
                ?>
                <div class="student-profile-index-grid">
                    <?php foreach ($healthCards as $card): ?>
                        <a class="summary-card student-profile-card student-card-link student-compact-card" href="<?= $h($moduleBaseUrl('salud', (string) $card['panel'])); ?>">
                            <span class="summary-label"><?= $h($card['label']); ?></span>
                            <strong><?= $h($card['value']); ?></strong>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($panel === 'general'): ?>
                <div class="form-grid">
                    <div class="form-group"><div class="input-group"><span class="input-addon">Grupo sanguineo</span><select name="gsid"><option value="">Seleccione</option><?php foreach ($bloodGroups as $bloodGroup): ?><option value="<?= $h($bloodGroup['gsid']); ?>" <?= (int) ($healthContext['gsid'] ?? 0) === (int) $bloodGroup['gsid'] ? 'selected' : ''; ?>><?= $h($bloodGroup['gsnombre'] ?? ''); ?></option><?php endforeach; ?></select></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Atencion medica</span><select name="amid"><option value="">Seleccione</option><?php foreach ($medicalCareTypes as $medicalCareType): ?><option value="<?= $h($medicalCareType['amid']); ?>" <?= (int) ($healthContext['amid'] ?? 0) === (int) $medicalCareType['amid'] ? 'selected' : ''; ?>><?= $h($medicalCareType['amnombre'] ?? ''); ?></option><?php endforeach; ?></select></div></div>
                    <label class="resource-option resource-option-switch family-switch-inline"><span>Tiene discapacidad</span><span class="switch-control"><input type="checkbox" name="ecstienediscapacidad" value="1" <?= !empty($healthContext['ecstienediscapacidad']) ? 'checked' : ''; ?> data-disability-toggle><span class="switch-slider" aria-hidden="true"></span></span></label>
                    <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Detalle discapacidad</span><textarea name="ecsdetallediscapacidad" rows="2" data-disability-detail><?= $h($healthContext['ecsdetallediscapacidad'] ?? ''); ?></textarea></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Seguro medico</span><select name="insurance[smid]"><option value="">Seleccione</option><?php foreach ($insuranceProviders as $insuranceProvider): ?><option value="<?= $h($insuranceProvider['smid']); ?>" <?= (int) ($healthInsurance['smid'] ?? 0) === (int) $insuranceProvider['smid'] ? 'selected' : ''; ?>><?= $h($insuranceProvider['smnombre'] ?? ''); ?></option><?php endforeach; ?></select></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Telefono seguro</span><input name="insurance[msmtelefono]" value="<?= $h($healthInsurance['msmtelefono'] ?? ''); ?>" data-phone-mask></div></div>
                    <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Obs. seguro</span><input name="insurance[msmobservacion]" value="<?= $h($healthInsurance['msmobservacion'] ?? ''); ?>"></div></div>
                </div>
            <?php elseif ($panel === 'condiciones'): ?>
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
            <?php elseif ($panel === 'historia-vital'): ?>
                <div class="form-grid">
                    <div class="form-group"><div class="input-group"><span class="input-addon">Edad madre</span><input name="vital_history[ehvedadmadre]" type="number" min="0" step="1" value="<?= $h($vitalHistory['ehvedadmadre'] ?? ''); ?>"><span class="input-addon input-addon-suffix">años</span></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Tipo embarazo</span><select name="vital_history[teid]"><option value="">Seleccione</option><?php foreach ($pregnancyTypes as $pregnancyType): ?><option value="<?= $h($pregnancyType['teid']); ?>" <?= (int) ($vitalHistory['teid'] ?? 0) === (int) $pregnancyType['teid'] ? 'selected' : ''; ?>><?= $h($pregnancyType['tenombre'] ?? ''); ?></option><?php endforeach; ?></select></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Tipo parto</span><select name="vital_history[tpid]"><option value="">Seleccione</option><?php foreach ($birthTypes as $birthType): ?><option value="<?= $h($birthType['tpid']); ?>" <?= (int) ($vitalHistory['tpid'] ?? 0) === (int) $birthType['tpid'] ? 'selected' : ''; ?>><?= $h($birthType['tpnombre'] ?? ''); ?></option><?php endforeach; ?></select></div></div>
                    <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Complicaciones</span><textarea name="vital_history[ehvcomplicacionesembarazo]" rows="2"><?= $h($vitalHistory['ehvcomplicacionesembarazo'] ?? ''); ?></textarea></div></div>
                    <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Medicacion</span><textarea name="vital_history[ehvmedicacionembarazo]" rows="2"><?= $h($vitalHistory['ehvmedicacionembarazo'] ?? ''); ?></textarea></div></div>
                    <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Detalle embarazo</span><textarea name="vital_history[ehvdetalleembarazo]" rows="3"><?= $h($vitalHistory['ehvdetalleembarazo'] ?? ''); ?></textarea></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Peso al nacer</span><input name="vital_history[ehvpesonacer]" type="number" min="0" step="0.01" value="<?= $h($vitalHistory['ehvpesonacer'] ?? ''); ?>"><span class="input-addon input-addon-suffix">kg</span></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Talla al nacer</span><input name="vital_history[ehvtallanacer]" type="number" min="0" step="0.1" value="<?= $h($vitalHistory['ehvtallanacer'] ?? ''); ?>"><span class="input-addon input-addon-suffix">cm</span></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Edad camino</span><input name="vital_history[ehvedadcaminar]" type="number" min="0" step="1" value="<?= $h($vitalHistory['ehvedadcaminar'] ?? ''); ?>"><span class="input-addon input-addon-suffix">meses</span></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Edad hablo</span><input name="vital_history[ehvedadhablar]" type="number" min="0" step="1" value="<?= $h($vitalHistory['ehvedadhablar'] ?? ''); ?>"><span class="input-addon input-addon-suffix">meses</span></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Lactancia</span><input name="vital_history[ehvperiodolactancia]" type="number" min="0" step="1" value="<?= $h($vitalHistory['ehvperiodolactancia'] ?? ''); ?>"><span class="input-addon input-addon-suffix">meses</span></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Uso biberon</span><input name="vital_history[ehvedadbiberon]" type="number" min="0" step="1" value="<?= $h($vitalHistory['ehvedadbiberon'] ?? ''); ?>"><span class="input-addon input-addon-suffix">meses</span></div></div>
                    <div class="form-group"><div class="input-group"><span class="input-addon">Control esfinteres</span><input name="vital_history[ehvedadcontrolesfinteres]" type="number" min="0" step="1" value="<?= $h($vitalHistory['ehvedadcontrolesfinteres'] ?? ''); ?>"><span class="input-addon input-addon-suffix">meses</span></div></div>
                </div>
            <?php elseif ($panel === 'mediciones'): ?>
                <article id="nueva-medicion" class="family-card health-measurement-card">
                    <div class="family-card-header"><strong>Nueva medicion de peso y talla</strong></div>
                    <div class="form-grid">
                        <div class="form-group"><div class="input-group"><span class="input-addon">Peso (kg)</span><input name="health_measurement[emspeso]" type="number" step="0.01" min="0" value="" data-imc-weight></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Talla (cm)</span><input name="health_measurement[emstalla]" type="number" step="0.1" min="0" value="" data-imc-height></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">IMC</span><input name="health_measurement[emsimc]" readonly value="" data-imc-output></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Descripcion IMC</span><input readonly value="" data-imc-category data-student-birth-date="<?= $h($student['perfechanacimiento'] ?? ''); ?>" data-student-sex="<?= $h($student['persexo'] ?? ''); ?>"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Fecha medicion</span><input name="health_measurement[emsfecha_medicion]" type="date" value="<?= $h(date('Y-m-d')); ?>" data-measurement-date></div></div>
                        <div class="form-group form-group-full"><div class="alert alert-success form-field-alert imc-alert" data-imc-alert hidden></div></div>
                        <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Obs. medicion</span><input name="health_measurement[emsobservacion]" value=""></div></div>
                    </div>
                    <div class="form-actions health-measurement-actions">
                        <a class="btn-secondary btn-auto" href="<?= $h($moduleBaseUrl('salud')); ?>">Volver a salud</a>
                        <?php if (!$readOnly): ?><button type="submit" class="btn-primary btn-auto">Agregar medicion</button><?php endif; ?>
                    </div>
                    <?php if ($moduleSuccess): ?><div class="alert alert-success health-measurement-feedback"><?= $h($moduleSuccess); ?></div><?php endif; ?>
                    <?php if ($moduleError): ?><div class="alert alert-error health-measurement-feedback"><?= $h($moduleError); ?></div><?php endif; ?>
                </article>
                <div class="student-growth-panel">
                    <div class="student-module-header student-module-subheader"><div><span class="summary-label">Evolucion</span><h3>Peso y talla</h3></div></div>
                    <?php if ($healthMeasurements === []): ?>
                        <p class="empty-state">Aun no existen mediciones registradas.</p>
                    <?php else: ?>
                        <div class="student-growth-chart"><canvas data-health-growth-chart data-measurements="<?= $h(json_encode($healthChartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>" data-reference="<?= $h(json_encode($healthChartReference, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>"></canvas></div>
                        <div class="table-wrapper student-growth-table"><table><thead><tr><th>Fecha</th><th>Peso kg</th><th>Talla cm</th><th>IMC</th><th>Descripcion</th><th>Observacion</th></tr></thead><tbody><?php foreach (array_reverse($healthMeasurements) as $measurement): ?><tr><td><?= $h($measurement['emsfecha_medicion'] ?? ''); ?></td><td><?= $h($measurement['emspeso'] ?? ''); ?></td><td><?= $h($measurement['emstalla'] ?? ''); ?></td><td><?= $h($measurement['emsimc'] ?? ''); ?></td><td><?= $h($imcDescription($measurement['emsimc'] ?? null, $student['perfechanacimiento'] ?? null, $student['persexo'] ?? null, $measurement['emsfecha_medicion'] ?? null)); ?></td><td><?= $h($measurement['emsobservacion'] ?? ''); ?></td></tr><?php endforeach; ?></tbody></table></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
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

        <?php if ($readOnly): ?></fieldset><?php endif; ?>

        <div class="form-actions">
            <?php if ($section === 'salud' && $panel === 'mediciones'): ?>
            <?php elseif ($section === 'salud' && $panel !== ''): ?>
                <a class="btn-secondary btn-auto" href="<?= $h($moduleBaseUrl('salud')); ?>">Volver a salud</a>
                <?php if (!$readOnly): ?><button type="submit" class="btn-primary btn-auto">Guardar cambios</button><?php endif; ?>
            <?php elseif (!($section === 'salud' && $panel === '')): ?>
                <a class="btn-secondary btn-auto" href="<?= $h($profileUrl); ?>"><?= $readOnly ? 'Volver a la ficha' : 'Cancelar'; ?></a>
                <?php if (!$readOnly): ?><button type="submit" class="btn-primary btn-auto">Guardar cambios</button><?php endif; ?>
            <?php endif; ?>
        </div>
    </form>
</section>
<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
