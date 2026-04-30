<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$familyRows = $old['families'] ?? [];
$representativeOld = is_array($old['representative'] ?? null) ? $old['representative'] : [];
$oldRepresentativeIndex = (int) ($representativeOld['family_index'] ?? 0);
$representativeSource = (string) ($representativeOld['source'] ?? 'family');
$externalRepresentative = is_array($representativeOld['external'] ?? null) ? $representativeOld['external'] : [];
$resourcesOld = array_merge([
    'mrtinternet' => false,
    'mrtcomputador' => false,
    'mrtlaptop' => false,
    'mrttablet' => false,
    'mrtcelular' => false,
    'mrtimpresora' => false,
], is_array($old['resources'] ?? null) ? $old['resources'] : []);
$insuranceOld = array_merge([
    'smid' => 0,
    'msmtelefono' => '',
    'msmobservacion' => '',
], is_array($old['insurance'] ?? null) ? $old['insurance'] : []);
$billingOld = array_merge([
    'mfcnombre' => '',
    'mfctipoidentificacion' => 'CEDULA',
    'mfcidentificacion' => '',
    'mfcdireccion' => '',
    'mfccorreo' => '',
    'mfctelefono' => '',
], is_array($old['billing'] ?? null) ? $old['billing'] : []);
$familyContextOld = array_merge([
    'ecfconvivecon_pteids' => [],
    'ecfconvivecon' => '',
    'ecfnumerohermanos' => '',
    'ecfposicionhermanos' => '',
], is_array($old['family_context'] ?? null) ? $old['family_context'] : []);
$cohabitationSelectedIds = array_filter(array_map(
    'intval',
    (array) ($familyContextOld['ecfconvivecon_pteids'] ?? [])
));
$cohabitationSelectedNames = array_filter(array_map(
    static fn (string $value): string => strtolower(trim($value)),
    explode(',', (string) ($familyContextOld['ecfconvivecon'] ?? ''))
));
$housingOld = array_merge([
    'cviid' => 0,
    'estvdescripcion' => '',
    'estvluzelectrica' => false,
    'estvaguapotable' => false,
    'estvsshh' => false,
    'estvtelefono' => false,
    'estvcable' => false,
], is_array($old['housing'] ?? null) ? $old['housing'] : []);
$healthContextOld = array_merge([
    'gsid' => 0,
    'ecstienediscapacidad' => false,
    'ecsdetallediscapacidad' => '',
    'amid' => 0,
], is_array($old['health_context'] ?? null) ? $old['health_context'] : []);
$healthConditionsOld = is_array($old['health_conditions'] ?? null) ? $old['health_conditions'] : [];
$healthConditionsOld = array_values(array_filter($healthConditionsOld, static function ($row): bool {
    if (!is_array($row)) {
        return false;
    }

    return (int) ($row['tcsid'] ?? 0) > 0
        || trim((string) ($row['ecsadescripcion'] ?? '')) !== ''
        || trim((string) ($row['ecsamedicamentos'] ?? '')) !== ''
        || trim((string) ($row['ecsaobservacion'] ?? '')) !== '';
}));
$healthMeasurementOld = array_merge([
    'emspeso' => '',
    'emstalla' => '',
    'emsimc' => '',
    'emsfecha_medicion' => date('Y-m-d'),
    'emsobservacion' => '',
], is_array($old['health_measurement'] ?? null) ? $old['health_measurement'] : []);
$vitalHistoryOld = array_merge([
    'ehvedadmadre' => '',
    'ehvcomplicacionesembarazo' => '',
    'ehvmedicacionembarazo' => '',
    'teid' => 0,
    'tpid' => 0,
    'ehvdetalleembarazo' => '',
    'ehvpesonacer' => '',
    'ehvtallanacer' => '',
    'ehvedadcaminar' => '',
    'ehvedadhablar' => '',
    'ehvperiodolactancia' => '',
    'ehvedadbiberon' => '',
    'ehvedadcontrolesfinteres' => '',
], is_array($old['vital_history'] ?? null) ? $old['vital_history'] : []);
$academicContextOld = array_merge([
    'ecafechaingresoinstitucion' => '',
    'ecaharepetidoanios' => false,
    'ecadetallerepeticion' => '',
    'ecaasignaturaspreferencia' => '',
    'ecaasignaturasdificultad' => '',
    'ecaactividadesextras' => '',
], is_array($old['academic_context'] ?? null) ? $old['academic_context'] : []);
$documentAcceptancesOld = array_map('intval', is_array($old['documents'] ?? null) ? $old['documents'] : []);
$motherRelationship = null;
$fatherRelationship = null;
$resolveDocumentUrl = static function (string $origin, string $url): string {
    $normalizedOrigin = mb_strtoupper(trim($origin));
    $normalizedUrl = trim($url);

    if ($normalizedUrl === '') {
        return '#';
    }

    if ($normalizedOrigin === 'ARCHIVO') {
        return asset(ltrim($normalizedUrl, '/'));
    }

    if (
        str_starts_with($normalizedUrl, 'http://')
        || str_starts_with($normalizedUrl, 'https://')
        || str_starts_with($normalizedUrl, '/')
    ) {
        return $normalizedUrl;
    }

    if (str_starts_with($normalizedUrl, 'assets/')) {
        return asset(substr($normalizedUrl, 7));
    }

    return baseUrl($normalizedUrl);
};

foreach ($relationships as $relationship) {
    $relationshipName = mb_strtolower(trim((string) ($relationship['ptenombre'] ?? '')));

    if ($motherRelationship === null && $relationshipName === 'madre') {
        $motherRelationship = $relationship;
    }

    if ($fatherRelationship === null && $relationshipName === 'padre') {
        $fatherRelationship = $relationship;
    }
}

$emptyFamilyRow = static function (): array {
    return [
        'percedula' => '',
        'pernombres' => '',
        'perapellidos' => '',
        'pertelefono1' => '',
        'pertelefono2' => '',
        'percorreo' => '',
        'persexo' => '',
        'perfechanacimiento' => '',
        'pteid' => 0,
        'eciid' => 0,
        'istid' => 0,
        'perprofesion' => '',
        'perocupacion' => '',
        'perlugardetrabajo' => '',
        'perhablaingles' => false,
    ];
};

$emptyRepresentativeRow = static function (): array {
    return [
        'perid' => 0,
        'percedula' => '',
        'pernombres' => '',
        'perapellidos' => '',
        'pertelefono1' => '',
        'pertelefono2' => '',
        'percorreo' => '',
        'persexo' => '',
        'perfechanacimiento' => '',
        'eciid' => 0,
        'istid' => 0,
        'perprofesion' => '',
        'perocupacion' => '',
        'perlugardetrabajo' => '',
        'perhablaingles' => false,
        'pteid' => 0,
    ];
};

$hasFamilyRowData = static function (array $row): bool {
    return trim((string) ($row['percedula'] ?? '')) !== ''
        || trim((string) ($row['pernombres'] ?? '')) !== ''
        || trim((string) ($row['perapellidos'] ?? '')) !== '';
};

$motherRow = $emptyFamilyRow();
$fatherRow = $emptyFamilyRow();
$additionalFamilyRows = [];
$representativeIndex = 0;
$activePanel = $activePanel ?? '';
$personLookupUrl = baseUrl('matriculas/persona');
$externalRepresentative = array_merge($emptyRepresentativeRow(), $externalRepresentative);

foreach ($familyRows as $index => $family) {
    $relationshipId = (int) ($family['pteid'] ?? 0);

    if ($motherRelationship !== null && $relationshipId === (int) $motherRelationship['pteid']) {
        $motherRow = $family;

        if ($oldRepresentativeIndex === (int) $index) {
            $representativeIndex = 0;
        }

        continue;
    }

    if ($fatherRelationship !== null && $relationshipId === (int) $fatherRelationship['pteid']) {
        $fatherRow = $family;

        if ($oldRepresentativeIndex === (int) $index) {
            $representativeIndex = 1;
        }

        continue;
    }

    $additionalFamilyRows[] = $family;

    if ($oldRepresentativeIndex === (int) $index) {
        $representativeIndex = (int) $index;
    }
}

if ($motherRelationship !== null) {
    $motherRow['pteid'] = (int) $motherRelationship['pteid'];
}

if ($fatherRelationship !== null) {
    $fatherRow['pteid'] = (int) $fatherRelationship['pteid'];
}

$motherVisible = $hasFamilyRowData($motherRow);
$fatherVisible = $hasFamilyRowData($fatherRow);
$renderFamilyFields = static function (
    array $family,
    int|string $index,
    array $civilStatuses,
    array $instructionLevels,
    array $relationships,
    ?array $fixedRelationship = null,
    string $lookupUrl = ''
): void {
    $relationshipLabel = $fixedRelationship !== null ? (string) ($fixedRelationship['ptenombre'] ?? '') : '';
    $personId = (int) ($family['perid'] ?? 0);
    $hasManualData = trim((string) ($family['pernombres'] ?? '')) !== '' || trim((string) ($family['perapellidos'] ?? '')) !== '';
    $isEditable = $personId <= 0 && $hasManualData;
    $isLocked = $personId > 0;
    $personDisabledAttribute = !$isEditable && !$isLocked ? 'disabled' : ($isLocked ? 'disabled' : '');
    $familyDisabledAttribute = !$isEditable && !$isLocked ? 'disabled' : '';
    ?>
    <div class="family-lookup-row">
        <div class="input-group">
            <span class="input-addon">Cedula</span>
            <input
                name="family[<?= htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?>][percedula]"
                maxlength="10"
                minlength="10"
                pattern="\d{10}"
                inputmode="numeric"
                value="<?= htmlspecialchars((string) ($family['percedula'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                data-family-cedula
            >
        </div>
        <button
            class="btn-primary btn-auto"
            type="button"
            data-family-search
            data-family-search-url="<?= htmlspecialchars($lookupUrl, ENT_QUOTES, 'UTF-8'); ?>"
        >
            Buscar
        </button>
    </div>
    <div class="family-lookup-alert" data-family-lookup-alert <?= (!$isEditable && !$isLocked) ? 'hidden' : ''; ?>>
        <?php if ($isEditable): ?>
            <div class="alert alert-error form-field-alert">
                <span>Persona no registrada, favor completar los datos.</span>
            </div>
        <?php endif; ?>
    </div>
    <input type="hidden" name="family[<?= htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?>][perid]" value="<?= htmlspecialchars((string) $personId, ENT_QUOTES, 'UTF-8'); ?>" data-family-person-id>
    <div class="form-grid">
        <div class="form-group"><div class="input-group"><span class="input-addon">Sexo</span><select name="family[<?= htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?>][persexo]" data-family-dependent data-family-person-field data-submit-enable <?= $personDisabledAttribute; ?>><option value="">Seleccione</option><option value="Masculino" <?= ($family['persexo'] ?? '') === 'Masculino' ? 'selected' : ''; ?>>Masculino</option><option value="Femenino" <?= ($family['persexo'] ?? '') === 'Femenino' ? 'selected' : ''; ?>>Femenino</option></select></div></div>
        <div class="form-group"><div class="input-group"><span class="input-addon">Nombres</span><input name="family[<?= htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?>][pernombres]" value="<?= htmlspecialchars((string) ($family['pernombres'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-family-field="nombres" data-family-dependent data-family-person-field data-submit-enable <?= $personDisabledAttribute; ?>></div></div>
        <div class="form-group"><div class="input-group"><span class="input-addon">Apellidos</span><input name="family[<?= htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?>][perapellidos]" value="<?= htmlspecialchars((string) ($family['perapellidos'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-family-field="apellidos" data-family-dependent data-family-person-field data-submit-enable <?= $personDisabledAttribute; ?>></div></div>
        <?php if ($fixedRelationship !== null): ?>
            <div class="form-group">
                <div class="input-group">
                    <span class="input-addon">Parentesco</span>
                    <input type="text" value="<?= htmlspecialchars($relationshipLabel, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                    <input type="hidden" name="family[<?= htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?>][pteid]" value="<?= htmlspecialchars((string) ($fixedRelationship['pteid'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
        <?php else: ?>
            <div class="form-group"><div class="input-group"><span class="input-addon">Parentesco</span><select name="family[<?= htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?>][pteid]" data-family-field="parentesco" data-family-dependent data-submit-enable <?= $familyDisabledAttribute; ?>><option value="">Seleccione</option><?php foreach ($relationships as $relationship): ?><option value="<?= htmlspecialchars((string) $relationship['pteid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($family['pteid'] ?? 0) === (int) $relationship['pteid'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) $relationship['ptenombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
        <?php endif; ?>
        <div class="form-group"><div class="input-group"><span class="input-addon">Estado civil</span><select name="family[<?= htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?>][eciid]" data-family-dependent data-family-person-field data-submit-enable <?= $personDisabledAttribute; ?>><option value="">Seleccione</option><?php foreach ($civilStatuses as $civilStatus): ?><option value="<?= htmlspecialchars((string) $civilStatus['eciid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($family['eciid'] ?? 0) === (int) $civilStatus['eciid'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) $civilStatus['ecinombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
        <div class="form-group"><div class="input-group"><span class="input-addon">Instruccion</span><select name="family[<?= htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?>][istid]" data-family-dependent data-submit-enable <?= $familyDisabledAttribute; ?>><option value="">Seleccione</option><?php foreach ($instructionLevels as $instructionLevel): ?><option value="<?= htmlspecialchars((string) $instructionLevel['istid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($family['istid'] ?? 0) === (int) $instructionLevel['istid'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) $instructionLevel['istnombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
        <div class="form-group"><div class="input-group"><span class="input-addon">Nacimiento</span><input name="family[<?= htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?>][perfechanacimiento]" type="date" value="<?= htmlspecialchars((string) ($family['perfechanacimiento'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-family-dependent data-family-person-field data-submit-enable <?= $personDisabledAttribute; ?>></div></div>
        <div class="form-group"><div class="input-group"><span class="input-addon">Celular</span><input name="family[<?= htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?>][pertelefono1]" placeholder="(09) 9894 5698" maxlength="14" inputmode="numeric" value="<?= htmlspecialchars((string) ($family['pertelefono1'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-phone-mask data-family-dependent data-family-person-field data-submit-enable <?= $personDisabledAttribute; ?>></div></div>
        <div class="form-group"><div class="input-group"><span class="input-addon">Correo</span><input name="family[<?= htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?>][percorreo]" type="email" value="<?= htmlspecialchars((string) ($family['percorreo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-family-dependent data-family-person-field data-submit-enable <?= $personDisabledAttribute; ?>></div></div>
        <div class="form-group"><div class="input-group"><span class="input-addon">Profesion</span><input name="family[<?= htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?>][perprofesion]" value="<?= htmlspecialchars((string) ($family['perprofesion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-family-dependent data-family-person-field data-submit-enable <?= $personDisabledAttribute; ?>></div></div>
        <div class="form-group"><div class="input-group"><span class="input-addon">Ocupacion</span><input name="family[<?= htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?>][perocupacion]" value="<?= htmlspecialchars((string) ($family['perocupacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-family-dependent data-family-person-field data-submit-enable <?= $personDisabledAttribute; ?>></div></div>
        <div class="form-group"><div class="input-group"><span class="input-addon">Lugar de trabajo</span><input name="family[<?= htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?>][perlugardetrabajo]" value="<?= htmlspecialchars((string) ($family['perlugardetrabajo'] ?? $family['famlugardetrabajo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-family-dependent data-family-person-field data-submit-enable <?= $personDisabledAttribute; ?>></div></div>
        <label class="resource-option resource-option-switch family-switch-inline">
            <span>Habla ingles</span>
            <span class="switch-control">
                <input type="checkbox" name="family[<?= htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?>][perhablaingles]" value="1" <?= !empty($family['perhablaingles']) ? 'checked' : ''; ?> data-family-dependent data-family-person-field data-submit-enable <?= $personDisabledAttribute; ?>>
                <span class="switch-slider" aria-hidden="true"></span>
            </span>
        </label>
    </div>
    <?php
};

ob_start();
$renderFamilyFields($emptyFamilyRow(), '__INDEX__', $civilStatuses, $instructionLevels, $relationships, null, $personLookupUrl);
$dynamicFamilyTemplate = ob_get_clean();

ob_start();
?>
<article class="family-card health-condition-card" data-health-condition-row data-health-condition-index="__INDEX__">
    <div class="family-card-header">
        <strong>Condicion de salud</strong>
        <button class="btn-secondary btn-auto" type="button" data-health-condition-remove>Quitar</button>
    </div>
    <div class="form-grid">
        <div class="form-group"><div class="input-group"><span class="input-addon">Tipo</span><select name="health_conditions[__INDEX__][tcsid]"><option value="">Seleccione</option><?php foreach (($healthConditionTypes ?? []) as $healthConditionType): ?><option value="<?= htmlspecialchars((string) $healthConditionType['tcsid'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars((string) $healthConditionType['tcsnombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
        <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Descripcion</span><input name="health_conditions[__INDEX__][ecsadescripcion]"></div></div>
        <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Medicamentos</span><input name="health_conditions[__INDEX__][ecsamedicamentos]"></div></div>
        <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Observacion</span><textarea name="health_conditions[__INDEX__][ecsaobservacion]" rows="2"></textarea></div></div>
        <label class="resource-option resource-option-switch family-switch-inline">
            <span>Vigente</span>
            <span class="switch-control">
                <input type="checkbox" name="health_conditions[__INDEX__][ecsavigente]" value="1" checked>
                <span class="switch-slider" aria-hidden="true"></span>
            </span>
        </label>
    </div>
</article>
<?php
$healthConditionTemplate = ob_get_clean();
?>
<p class="module-note">La matriculacion consolida persona, estudiante, familiares, representante y curso en un solo flujo operativo.</p>

<nav class="module-subnav" aria-label="Submodulos de matriculas">
    <?php if (!empty($canCreateMatricula)): ?>
        <a class="<?= $activePanel === 'nueva' ? 'is-active' : ''; ?>" href="<?= htmlspecialchars(baseUrl('matriculas?panel=nueva'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars((string) ($newMatriculaLabel ?? 'Nueva matricula'), ENT_QUOTES, 'UTF-8'); ?></a>
    <?php endif; ?>
    <a class="<?= $activePanel === 'gestion' ? 'is-active' : ''; ?>" href="<?= htmlspecialchars(baseUrl('matriculas?panel=gestion'), ENT_QUOTES, 'UTF-8'); ?>">Gestion de matriculas</a>
</nav>

<?php if ($currentPeriod === null && empty($newMatriculaPeriod)): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para continuar.</div>
<?php else: ?>
    <?php if ($activePanel === ''): ?>
    <section class="security-assignment-block">
        <div class="empty-state">
            Selecciona una opcion para continuar con el modulo de matriculas:
            <?php if (!empty($canCreateMatricula)): ?>
                <a class="text-link" href="<?= htmlspecialchars(baseUrl('matriculas?panel=nueva'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars((string) ($newMatriculaLabel ?? 'Nueva matricula'), ENT_QUOTES, 'UTF-8'); ?></a>
                o
            <?php endif; ?>
            <a class="text-link" href="<?= htmlspecialchars(baseUrl('matriculas?panel=gestion'), ENT_QUOTES, 'UTF-8'); ?>">Gestion de matriculas</a>.
        </div>
        <?php if (empty($canCreateMatricula)): ?>
            <div class="empty-state">No existe un periodo lectivo con matricula habilitada. Configuralo desde <a class="text-link" href="<?= htmlspecialchars(baseUrl('configuracion/matricula'), ENT_QUOTES, 'UTF-8'); ?>">Configuracion de matricula</a>.</div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($activePanel === 'nueva' && !empty($canCreateMatricula)): ?>
    <section class="security-assignment-block" id="matricula-form">
        <header class="security-assignment-header">
            <div>
                <h3><?= htmlspecialchars((string) ($newMatriculaLabel ?? 'Nueva matricula'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p>El registro se guardara en el periodo habilitado: <strong><?= htmlspecialchars((string) (($newMatriculaPeriod['pledescripcion'] ?? $currentPeriod['pledescripcion'] ?? 'Sin periodo')), ENT_QUOTES, 'UTF-8'); ?></strong>.</p>
            </div>
        </header>

        <?php if (empty($courses) || empty($enrollmentStatuses) || empty($relationships)): ?>
            <div class="empty-state">Para matricular necesitas cursos activos del periodo, estados de matricula y parentescos registrados.</div>
        <?php else: ?>
            <form class="data-form matricula-form" method="POST" action="<?= htmlspecialchars(baseUrl('matriculas'), ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data" data-matricula-form>
                <div class="wizard-tabs" role="tablist" aria-label="Secciones de matricula">
                    <button type="button" class="wizard-tab is-active" data-wizard-tab="persona">Estudiante</button>
                    <button type="button" class="wizard-tab" data-wizard-tab="estudiante">Datos Personales</button>
                    <button type="button" class="wizard-tab" data-wizard-tab="contexto-familiar">Contexto familiar</button>
                    <button type="button" class="wizard-tab" data-wizard-tab="familiares">Datos Familiares</button>
                    <button type="button" class="wizard-tab" data-wizard-tab="vivienda">Vivienda</button>
                    <button type="button" class="wizard-tab" data-wizard-tab="salud">Salud</button>
                    <button type="button" class="wizard-tab" data-wizard-tab="historia-vital">Historia vital</button>
                    <button type="button" class="wizard-tab" data-wizard-tab="academico">Contexto academico</button>
                    <button type="button" class="wizard-tab" data-wizard-tab="representante">Representante</button>
                    <button type="button" class="wizard-tab" data-wizard-tab="recursos">Recursos tecnologicos</button>
                    <button type="button" class="wizard-tab" data-wizard-tab="facturacion">Facturacion</button>
                    <button type="button" class="wizard-tab" data-wizard-tab="documentos">Documentos</button>
                    <button type="button" class="wizard-tab" data-wizard-tab="matricula">Matricula</button>
                </div>

                <section class="wizard-panel is-active" data-wizard-panel="persona">
                    <div class="form-grid">
                        <div class="form-group"><div class="input-group"><span class="input-addon">Cedula</span><input name="person[percedula]" maxlength="10" minlength="10" pattern="\d{10}" inputmode="numeric" required value="<?= htmlspecialchars((string) ($old['person']['percedula'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Sexo</span><select name="person[persexo]"><option value="">Seleccione</option><option value="Masculino" <?= ($old['person']['persexo'] ?? '') === 'Masculino' ? 'selected' : ''; ?>>Masculino</option><option value="Femenino" <?= ($old['person']['persexo'] ?? '') === 'Femenino' ? 'selected' : ''; ?>>Femenino</option></select></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Nacimiento</span><input name="person[perfechanacimiento]" type="date" value="<?= htmlspecialchars((string) ($old['person']['perfechanacimiento'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Instruccion</span><select name="person[istid]"><option value="">Seleccione</option><?php foreach ($instructionLevels as $instructionLevel): ?><option value="<?= htmlspecialchars((string) $instructionLevel['istid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($old['person']['istid'] ?? 0) === (int) $instructionLevel['istid'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) $instructionLevel['istnombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Nombres</span><input name="person[pernombres]" required value="<?= htmlspecialchars((string) ($old['person']['pernombres'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Apellidos</span><input name="person[perapellidos]" required value="<?= htmlspecialchars((string) ($old['person']['perapellidos'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Celular</span><input name="person[pertelefono1]" placeholder="(09) 9894 5698" maxlength="14" inputmode="numeric" value="<?= htmlspecialchars((string) ($old['person']['pertelefono1'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-phone-mask></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Fijo</span><input name="person[pertelefono2]" value="<?= htmlspecialchars((string) ($old['person']['pertelefono2'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">E-mail</span><input name="person[percorreo]" type="email" value="<?= htmlspecialchars((string) ($old['person']['percorreo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                    </div>
                    <div class="actions-row">
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-save>Guardar</button>
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-clear>Borrar temporal</button>
                    </div>
                </section>

                <section class="wizard-panel" data-wizard-panel="estudiante" hidden>
                    <div class="form-grid">
                        <div class="form-group"><div class="input-group"><span class="input-addon">Ciudad</span><input name="student[estlugarnacimiento]" value="<?= htmlspecialchars((string) ($old['student']['estlugarnacimiento'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Parroquia</span><input name="student[estparroquia]" value="<?= htmlspecialchars((string) ($old['student']['estparroquia'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Direccion</span><input name="student[estdireccion]" value="<?= htmlspecialchars((string) ($old['student']['estdireccion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                    </div>
                    <div class="actions-row">
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-save>Guardar</button>
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-clear>Borrar temporal</button>
                    </div>
                </section>

                <section class="wizard-panel" data-wizard-panel="familiares" hidden>
                    <p class="module-note">Registra la informacion de madre, padre y otros familiares vinculados al estudiante.</p>
                    <div class="family-actions">
                        <button class="btn-secondary btn-inline" type="button" data-family-toggle="father" <?= $fatherVisible ? 'hidden' : ''; ?>>Agregar datos del padre</button>
                        <button class="btn-secondary btn-inline" type="button" data-family-toggle="mother" <?= $motherVisible ? 'hidden' : ''; ?>>Agregar datos de la madre</button>
                        <button class="btn-secondary btn-inline" type="button" data-family-add>Agregar familiar</button>
                    </div>
                    <div class="family-stack" data-family-rows>
                        <article class="family-card" data-family-row data-family-slot="father" data-family-index="1" data-family-relationship-label="Padre" <?= $fatherVisible ? '' : 'hidden'; ?>>
                            <div class="family-card-header">
                                <strong>Padre</strong>
                                <button class="btn-secondary btn-auto" type="button" data-family-hide="father">Quitar</button>
                            </div>
                            <?php $renderFamilyFields($fatherRow, 1, $civilStatuses, $instructionLevels, $relationships, $fatherRelationship, $personLookupUrl); ?>
                        </article>

                        <article class="family-card" data-family-row data-family-slot="mother" data-family-index="0" data-family-relationship-label="Madre" <?= $motherVisible ? '' : 'hidden'; ?>>
                            <div class="family-card-header">
                                <strong>Madre</strong>
                                <button class="btn-secondary btn-auto" type="button" data-family-hide="mother">Quitar</button>
                            </div>
                            <?php $renderFamilyFields($motherRow, 0, $civilStatuses, $instructionLevels, $relationships, $motherRelationship, $personLookupUrl); ?>
                        </article>

                        <?php foreach ($additionalFamilyRows as $additionalIndex => $family): ?>
                            <?php $rowIndex = $additionalIndex + 2; ?>
                            <article class="family-card" data-family-row data-family-index="<?= htmlspecialchars((string) $rowIndex, ENT_QUOTES, 'UTF-8'); ?>" data-family-relationship-label="Familiar adicional" data-family-removable>
                                <div class="family-card-header">
                                    <strong data-family-card-title>Familiar adicional <?= $additionalIndex + 1; ?></strong>
                                    <button class="btn-secondary btn-auto" type="button" data-family-remove>Quitar</button>
                                </div>
                                <?php $renderFamilyFields($family, $rowIndex, $civilStatuses, $instructionLevels, $relationships, null, $personLookupUrl); ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <template data-family-template>
                        <article class="family-card" data-family-row data-family-index="__INDEX__" data-family-relationship-label="Familiar adicional" data-family-removable>
                            <div class="family-card-header">
                                <strong data-family-card-title>Familiar adicional</strong>
                                <button class="btn-secondary btn-auto" type="button" data-family-remove>Quitar</button>
                            </div>
                            <?= $dynamicFamilyTemplate; ?>
                        </article>
                    </template>
                    <div class="actions-row">
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-save>Guardar</button>
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-clear>Borrar temporal</button>
                    </div>
                </section>

                <section class="wizard-panel" data-wizard-panel="vivienda" hidden>
                    <div class="form-grid">
                        <div class="form-group"><div class="input-group"><span class="input-addon">Condicion</span><select name="housing[cviid]"><option value="">Seleccione</option><?php foreach ($housingConditions as $housingCondition): ?><option value="<?= htmlspecialchars((string) $housingCondition['cviid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($housingOld['cviid'] ?? 0) === (int) $housingCondition['cviid'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) $housingCondition['cvinombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
                        <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Descripcion</span><input name="housing[estvdescripcion]" value="<?= htmlspecialchars((string) ($housingOld['estvdescripcion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                    </div>
                    <div class="resource-grid">
                        <label class="resource-option"><span>Luz electrica</span><input type="checkbox" name="housing[estvluzelectrica]" value="1" <?= !empty($housingOld['estvluzelectrica']) ? 'checked' : ''; ?>></label>
                        <label class="resource-option"><span>Agua potable</span><input type="checkbox" name="housing[estvaguapotable]" value="1" <?= !empty($housingOld['estvaguapotable']) ? 'checked' : ''; ?>></label>
                        <label class="resource-option"><span>SSHH</span><input type="checkbox" name="housing[estvsshh]" value="1" <?= !empty($housingOld['estvsshh']) ? 'checked' : ''; ?>></label>
                        <label class="resource-option"><span>Telefono</span><input type="checkbox" name="housing[estvtelefono]" value="1" <?= !empty($housingOld['estvtelefono']) ? 'checked' : ''; ?>></label>
                        <label class="resource-option"><span>Cable</span><input type="checkbox" name="housing[estvcable]" value="1" <?= !empty($housingOld['estvcable']) ? 'checked' : ''; ?>></label>
                    </div>
                    <div class="actions-row">
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-save>Guardar</button>
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-clear>Borrar temporal</button>
                    </div>
                </section>

                <section class="wizard-panel" data-wizard-panel="salud" hidden>
                    <div class="form-grid">
                        <div class="form-group"><div class="input-group"><span class="input-addon">Grupo sanguineo</span><select name="health_context[gsid]"><option value="">Seleccione</option><?php foreach ($bloodGroups as $bloodGroup): ?><option value="<?= htmlspecialchars((string) $bloodGroup['gsid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($healthContextOld['gsid'] ?? 0) === (int) $bloodGroup['gsid'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) $bloodGroup['gsnombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Atencion medica</span><select name="health_context[amid]"><option value="">Seleccione</option><?php foreach ($medicalCareTypes as $medicalCareType): ?><option value="<?= htmlspecialchars((string) $medicalCareType['amid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($healthContextOld['amid'] ?? 0) === (int) $medicalCareType['amid'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) $medicalCareType['amnombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
                        <label class="resource-option resource-option-switch family-switch-inline">
                            <span>Tiene discapacidad</span>
                            <span class="switch-control">
                                <input type="checkbox" name="health_context[ecstienediscapacidad]" value="1" <?= !empty($healthContextOld['ecstienediscapacidad']) ? 'checked' : ''; ?> data-disability-toggle>
                                <span class="switch-slider" aria-hidden="true"></span>
                            </span>
                        </label>
                        <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Detalle discapacidad</span><textarea name="health_context[ecsdetallediscapacidad]" rows="2" data-disability-detail><?= htmlspecialchars((string) ($healthContextOld['ecsdetallediscapacidad'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div></div>
                    </div>
                    <div class="family-actions">
                        <button class="btn-secondary btn-inline" type="button" data-health-condition-add>Agregar condicion de salud</button>
                        <span class="action-helper-text">Usa este boton si el estudiante tiene alergias, enfermedades, tratamientos u otra condicion medica.</span>
                    </div>
                    <div class="family-stack" data-health-condition-rows>
                        <?php foreach ($healthConditionsOld as $healthIndex => $healthCondition): ?>
                            <article class="family-card health-condition-card" data-health-condition-row data-health-condition-index="<?= htmlspecialchars((string) $healthIndex, ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="family-card-header">
                                    <strong>Condicion de salud <?= $healthIndex + 1; ?></strong>
                                    <button class="btn-secondary btn-auto" type="button" data-health-condition-remove>Quitar</button>
                                </div>
                                <div class="form-grid">
                                    <div class="form-group"><div class="input-group"><span class="input-addon">Tipo</span><select name="health_conditions[<?= htmlspecialchars((string) $healthIndex, ENT_QUOTES, 'UTF-8'); ?>][tcsid]"><option value="">Seleccione</option><?php foreach ($healthConditionTypes as $healthConditionType): ?><option value="<?= htmlspecialchars((string) $healthConditionType['tcsid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($healthCondition['tcsid'] ?? 0) === (int) $healthConditionType['tcsid'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) $healthConditionType['tcsnombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
                                    <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Descripcion</span><input name="health_conditions[<?= htmlspecialchars((string) $healthIndex, ENT_QUOTES, 'UTF-8'); ?>][ecsadescripcion]" value="<?= htmlspecialchars((string) ($healthCondition['ecsadescripcion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                                    <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Medicamentos</span><input name="health_conditions[<?= htmlspecialchars((string) $healthIndex, ENT_QUOTES, 'UTF-8'); ?>][ecsamedicamentos]" value="<?= htmlspecialchars((string) ($healthCondition['ecsamedicamentos'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                                    <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Observacion</span><textarea name="health_conditions[<?= htmlspecialchars((string) $healthIndex, ENT_QUOTES, 'UTF-8'); ?>][ecsaobservacion]" rows="2"><?= htmlspecialchars((string) ($healthCondition['ecsaobservacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div></div>
                                    <label class="resource-option resource-option-switch family-switch-inline">
                                        <span>Vigente</span>
                                        <span class="switch-control">
                                            <input type="checkbox" name="health_conditions[<?= htmlspecialchars((string) $healthIndex, ENT_QUOTES, 'UTF-8'); ?>][ecsavigente]" value="1" <?= !array_key_exists('ecsavigente', $healthCondition) || !empty($healthCondition['ecsavigente']) ? 'checked' : ''; ?>>
                                            <span class="switch-slider" aria-hidden="true"></span>
                                        </span>
                                    </label>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <template data-health-condition-template><?= $healthConditionTemplate; ?></template>
                    <div class="form-grid">
                        <div class="form-group"><div class="input-group"><span class="input-addon">Peso (kg)</span><input name="health_measurement[emspeso]" type="number" step="0.01" min="0" value="<?= htmlspecialchars((string) ($healthMeasurementOld['emspeso'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-imc-weight></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Talla (cm)</span><input name="health_measurement[emstalla]" type="number" step="0.1" min="0" value="<?= htmlspecialchars((string) ($healthMeasurementOld['emstalla'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-imc-height></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">IMC</span><input name="health_measurement[emsimc]" readonly value="<?= htmlspecialchars((string) ($healthMeasurementOld['emsimc'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-imc-output></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Fecha medicion</span><input name="health_measurement[emsfecha_medicion]" type="date" value="<?= htmlspecialchars((string) ($healthMeasurementOld['emsfecha_medicion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group form-group-full"><div class="alert alert-success form-field-alert imc-alert" data-imc-alert hidden></div></div>
                        <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Obs. medicion</span><input name="health_measurement[emsobservacion]" value="<?= htmlspecialchars((string) ($healthMeasurementOld['emsobservacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group"><div class="input-group"><span class="input-addon">Seguro medico</span><select name="insurance[smid]"><option value="">Seleccione</option><?php foreach ($insuranceProviders as $insuranceProvider): ?><option value="<?= htmlspecialchars((string) $insuranceProvider['smid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($insuranceOld['smid'] ?? 0) === (int) $insuranceProvider['smid'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) $insuranceProvider['smnombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Telefono seguro</span><input name="insurance[msmtelefono]" value="<?= htmlspecialchars((string) ($insuranceOld['msmtelefono'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-phone-mask></div></div>
                        <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Obs. seguro</span><input name="insurance[msmobservacion]" value="<?= htmlspecialchars((string) ($insuranceOld['msmobservacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                    </div>
                    <div class="actions-row">
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-save>Guardar</button>
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-clear>Borrar temporal</button>
                    </div>
                </section>

                <section class="wizard-panel" data-wizard-panel="historia-vital" hidden>
                    <div class="form-grid">
                        <div class="form-group"><div class="input-group"><span class="input-addon">Edad madre</span><input name="vital_history[ehvedadmadre]" type="number" min="0" value="<?= htmlspecialchars((string) ($vitalHistoryOld['ehvedadmadre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Tipo embarazo</span><select name="vital_history[teid]"><option value="">Seleccione</option><?php foreach ($pregnancyTypes as $pregnancyType): ?><option value="<?= htmlspecialchars((string) $pregnancyType['teid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($vitalHistoryOld['teid'] ?? 0) === (int) $pregnancyType['teid'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) $pregnancyType['tenombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Tipo parto</span><select name="vital_history[tpid]"><option value="">Seleccione</option><?php foreach ($birthTypes as $birthType): ?><option value="<?= htmlspecialchars((string) $birthType['tpid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($vitalHistoryOld['tpid'] ?? 0) === (int) $birthType['tpid'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) $birthType['tpnombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
                        <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Complicaciones</span><textarea name="vital_history[ehvcomplicacionesembarazo]" rows="2"><?= htmlspecialchars((string) ($vitalHistoryOld['ehvcomplicacionesembarazo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div></div>
                        <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Medicacion</span><textarea name="vital_history[ehvmedicacionembarazo]" rows="2"><?= htmlspecialchars((string) ($vitalHistoryOld['ehvmedicacionembarazo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div></div>
                        <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Detalle embarazo</span><textarea name="vital_history[ehvdetalleembarazo]" rows="3"><?= htmlspecialchars((string) ($vitalHistoryOld['ehvdetalleembarazo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Peso al nacer</span><input name="vital_history[ehvpesonacer]" value="<?= htmlspecialchars((string) ($vitalHistoryOld['ehvpesonacer'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Talla al nacer</span><input name="vital_history[ehvtallanacer]" value="<?= htmlspecialchars((string) ($vitalHistoryOld['ehvtallanacer'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Edad camino</span><input name="vital_history[ehvedadcaminar]" value="<?= htmlspecialchars((string) ($vitalHistoryOld['ehvedadcaminar'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Edad hablo</span><input name="vital_history[ehvedadhablar]" value="<?= htmlspecialchars((string) ($vitalHistoryOld['ehvedadhablar'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Lactancia</span><input name="vital_history[ehvperiodolactancia]" value="<?= htmlspecialchars((string) ($vitalHistoryOld['ehvperiodolactancia'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Uso biberon</span><input name="vital_history[ehvedadbiberon]" value="<?= htmlspecialchars((string) ($vitalHistoryOld['ehvedadbiberon'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Control esfinteres</span><input name="vital_history[ehvedadcontrolesfinteres]" value="<?= htmlspecialchars((string) ($vitalHistoryOld['ehvedadcontrolesfinteres'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                    </div>
                    <div class="actions-row">
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-save>Guardar</button>
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-clear>Borrar temporal</button>
                    </div>
                </section>

                <section class="wizard-panel" data-wizard-panel="academico" hidden>
                    <div class="form-grid">
                        <div class="form-group"><div class="input-group"><span class="input-addon">Ingreso institucion</span><input name="academic_context[ecafechaingresoinstitucion]" type="date" value="<?= htmlspecialchars((string) ($academicContextOld['ecafechaingresoinstitucion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <label class="resource-option resource-option-switch family-switch-inline">
                            <span>Ha repetido años</span>
                            <span class="switch-control">
                                <input type="checkbox" name="academic_context[ecaharepetidoanios]" value="1" <?= !empty($academicContextOld['ecaharepetidoanios']) ? 'checked' : ''; ?> data-repeated-years-toggle>
                                <span class="switch-slider" aria-hidden="true"></span>
                            </span>
                        </label>
                        <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Detalle repeticion</span><textarea name="academic_context[ecadetallerepeticion]" rows="2" data-repeated-years-detail <?= empty($academicContextOld['ecaharepetidoanios']) ? 'disabled' : ''; ?>><?= htmlspecialchars((string) ($academicContextOld['ecadetallerepeticion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div></div>
                        <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Asignaturas preferidas</span><textarea name="academic_context[ecaasignaturaspreferencia]" rows="2"><?= htmlspecialchars((string) ($academicContextOld['ecaasignaturaspreferencia'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div></div>
                        <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Asignaturas dificultad</span><textarea name="academic_context[ecaasignaturasdificultad]" rows="2"><?= htmlspecialchars((string) ($academicContextOld['ecaasignaturasdificultad'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div></div>
                        <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Actividades extras</span><textarea name="academic_context[ecaactividadesextras]" rows="2"><?= htmlspecialchars((string) ($academicContextOld['ecaactividadesextras'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div></div>
                    </div>
                    <div class="actions-row">
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-save>Guardar</button>
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-clear>Borrar temporal</button>
                    </div>
                </section>

                <section class="wizard-panel" data-wizard-panel="representante" hidden>
                    <p class="module-note">Selecciona un familiar cargado o elige Otro para registrar un tutor, apoderado o responsable externo.</p>
                    <input type="hidden" name="representative_source" value="<?= htmlspecialchars($representativeSource, ENT_QUOTES, 'UTF-8'); ?>" data-representative-source-input>
                    <input type="hidden" name="representative_index" value="<?= htmlspecialchars((string) $representativeIndex, ENT_QUOTES, 'UTF-8'); ?>" data-representative-index-input>
                    <div class="representative-options" data-representative-options></div>
                    <div class="family-card" data-representative-external-form <?= $representativeSource === 'external' ? '' : 'hidden'; ?>>
                        <div class="family-card-header">
                            <strong>Representante externo</strong>
                        </div>
                        <div class="family-lookup-row">
                            <div class="input-group">
                                <span class="input-addon">Cedula</span>
                                <input
                                    name="representative_external[percedula]"
                                    maxlength="10"
                                    minlength="10"
                                    pattern="\d{10}"
                                    inputmode="numeric"
                                    value="<?= htmlspecialchars((string) ($externalRepresentative['percedula'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-representative-external-cedula
                                >
                            </div>
                            <button
                                class="btn-primary btn-auto"
                                type="button"
                                data-representative-external-search
                                data-representative-search-url="<?= htmlspecialchars($personLookupUrl, ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                Buscar
                            </button>
                        </div>
                        <div class="family-lookup-alert" data-representative-external-alert hidden></div>
                        <input type="hidden" name="representative_external[perid]" value="<?= htmlspecialchars((string) ($externalRepresentative['perid'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" data-representative-external-person-id>
                        <div class="form-grid">
                            <div class="form-group"><div class="input-group"><span class="input-addon">Sexo</span><select name="representative_external[persexo]" data-representative-external-person-field><option value="">Seleccione</option><option value="Masculino" <?= ($externalRepresentative['persexo'] ?? '') === 'Masculino' ? 'selected' : ''; ?>>Masculino</option><option value="Femenino" <?= ($externalRepresentative['persexo'] ?? '') === 'Femenino' ? 'selected' : ''; ?>>Femenino</option></select></div></div>
                            <div class="form-group"><div class="input-group"><span class="input-addon">Nacimiento</span><input name="representative_external[perfechanacimiento]" type="date" value="<?= htmlspecialchars((string) ($externalRepresentative['perfechanacimiento'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-representative-external-person-field></div></div>
                            <div class="form-group"><div class="input-group"><span class="input-addon">Estado civil</span><select name="representative_external[eciid]" data-representative-external-person-field><option value="">Seleccione</option><?php foreach ($civilStatuses as $civilStatus): ?><option value="<?= htmlspecialchars((string) $civilStatus['eciid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($externalRepresentative['eciid'] ?? 0) === (int) $civilStatus['eciid'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) $civilStatus['ecinombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
                            <div class="form-group"><div class="input-group"><span class="input-addon">Nombres</span><input name="representative_external[pernombres]" value="<?= htmlspecialchars((string) ($externalRepresentative['pernombres'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-representative-external-person-field></div></div>
                            <div class="form-group"><div class="input-group"><span class="input-addon">Apellidos</span><input name="representative_external[perapellidos]" value="<?= htmlspecialchars((string) ($externalRepresentative['perapellidos'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-representative-external-person-field></div></div>
                            <div class="form-group"><div class="input-group"><span class="input-addon">Parentesco</span><select name="representative_external[pteid]" data-representative-external-detail-field><option value="">Seleccione</option><?php foreach ($relationships as $relationship): ?><option value="<?= htmlspecialchars((string) $relationship['pteid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($externalRepresentative['pteid'] ?? 0) === (int) $relationship['pteid'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) $relationship['ptenombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
                            <div class="form-group"><div class="input-group"><span class="input-addon">Instruccion</span><select name="representative_external[istid]" data-representative-external-person-field><option value="">Seleccione</option><?php foreach ($instructionLevels as $instructionLevel): ?><option value="<?= htmlspecialchars((string) $instructionLevel['istid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($externalRepresentative['istid'] ?? 0) === (int) $instructionLevel['istid'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) $instructionLevel['istnombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
                            <div class="form-group"><div class="input-group"><span class="input-addon">Celular</span><input name="representative_external[pertelefono1]" placeholder="(09) 9894 5698" maxlength="14" inputmode="numeric" value="<?= htmlspecialchars((string) ($externalRepresentative['pertelefono1'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-phone-mask data-representative-external-person-field></div></div>
                            <div class="form-group"><div class="input-group"><span class="input-addon">Fijo</span><input name="representative_external[pertelefono2]" value="<?= htmlspecialchars((string) ($externalRepresentative['pertelefono2'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-representative-external-detail-field></div></div>
                            <div class="form-group"><div class="input-group"><span class="input-addon">Profesion</span><input name="representative_external[perprofesion]" value="<?= htmlspecialchars((string) ($externalRepresentative['perprofesion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-representative-external-person-field></div></div>
                            <div class="form-group"><div class="input-group"><span class="input-addon">Ocupacion</span><input name="representative_external[perocupacion]" value="<?= htmlspecialchars((string) ($externalRepresentative['perocupacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-representative-external-person-field></div></div>
                            <div class="form-group"><div class="input-group"><span class="input-addon">Lugar de trabajo</span><input name="representative_external[perlugardetrabajo]" value="<?= htmlspecialchars((string) ($externalRepresentative['perlugardetrabajo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-representative-external-person-field></div></div>
                            <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Correo</span><input name="representative_external[percorreo]" type="email" value="<?= htmlspecialchars((string) ($externalRepresentative['percorreo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-representative-external-person-field></div></div>
                            <label class="resource-option resource-option-switch family-switch-inline">
                                <span>Habla ingles</span>
                                <span class="switch-control">
                                    <input type="checkbox" name="representative_external[perhablaingles]" value="1" <?= !empty($externalRepresentative['perhablaingles']) ? 'checked' : ''; ?> data-representative-external-person-field>
                                    <span class="switch-slider" aria-hidden="true"></span>
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="actions-row">
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-save>Guardar</button>
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-clear>Borrar temporal</button>
                    </div>
                </section>

                <section class="wizard-panel" data-wizard-panel="recursos" hidden>
                    <p class="module-note">Marca los recursos tecnologicos disponibles para el estudiante al momento de la matricula.</p>
                    <div class="resource-grid">
                        <label class="resource-option">
                            <span>Internet</span>
                            <input type="checkbox" name="resources[mrtinternet]" value="1" <?= !empty($resourcesOld['mrtinternet']) ? 'checked' : ''; ?>>
                        </label>
                        <label class="resource-option">
                            <span>Computador</span>
                            <input type="checkbox" name="resources[mrtcomputador]" value="1" <?= !empty($resourcesOld['mrtcomputador']) ? 'checked' : ''; ?>>
                        </label>
                        <label class="resource-option">
                            <span>Laptop</span>
                            <input type="checkbox" name="resources[mrtlaptop]" value="1" <?= !empty($resourcesOld['mrtlaptop']) ? 'checked' : ''; ?>>
                        </label>
                        <label class="resource-option">
                            <span>Tablet</span>
                            <input type="checkbox" name="resources[mrttablet]" value="1" <?= !empty($resourcesOld['mrttablet']) ? 'checked' : ''; ?>>
                        </label>
                        <label class="resource-option">
                            <span>Telefono inteligente</span>
                            <input type="checkbox" name="resources[mrtcelular]" value="1" <?= !empty($resourcesOld['mrtcelular']) ? 'checked' : ''; ?>>
                        </label>
                        <label class="resource-option">
                            <span>Impresora</span>
                            <input type="checkbox" name="resources[mrtimpresora]" value="1" <?= !empty($resourcesOld['mrtimpresora']) ? 'checked' : ''; ?>>
                        </label>
                    </div>
                    <div class="actions-row">
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-save>Guardar</button>
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-clear>Borrar temporal</button>
                    </div>
                </section>

                <section class="wizard-panel" data-wizard-panel="facturacion" hidden>
                    <p class="module-note">Registra los datos que se utilizaran para la facturacion de la matricula.</p>
                    <div class="form-grid">
                        <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Nombre / Razon social</span><input name="billing[mfcnombre]" required value="<?= htmlspecialchars((string) ($billingOld['mfcnombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Tipo ID</span><select name="billing[mfctipoidentificacion]" required data-billing-id-type><option value="CEDULA" <?= ($billingOld['mfctipoidentificacion'] ?? 'CEDULA') === 'CEDULA' ? 'selected' : ''; ?>>Cedula</option><option value="RUC" <?= ($billingOld['mfctipoidentificacion'] ?? '') === 'RUC' ? 'selected' : ''; ?>>RUC</option></select></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Identificacion</span><input name="billing[mfcidentificacion]" required inputmode="numeric" maxlength="<?= ($billingOld['mfctipoidentificacion'] ?? 'CEDULA') === 'RUC' ? '13' : '10'; ?>" placeholder="<?= ($billingOld['mfctipoidentificacion'] ?? 'CEDULA') === 'RUC' ? 'Ej: 1790012345001' : 'Ej: 1711894939'; ?>" value="<?= htmlspecialchars((string) ($billingOld['mfcidentificacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-billing-id-number></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Celular</span><input name="billing[mfctelefono]" placeholder="(09) 9894 5698" maxlength="14" inputmode="numeric" value="<?= htmlspecialchars((string) ($billingOld['mfctelefono'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-phone-mask></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Correo</span><input name="billing[mfccorreo]" type="email" placeholder="correo@dominio.com" value="<?= htmlspecialchars((string) ($billingOld['mfccorreo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Direccion</span><input name="billing[mfcdireccion]" value="<?= htmlspecialchars((string) ($billingOld['mfcdireccion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                    </div>
                    <div class="actions-row">
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-save>Guardar</button>
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-clear>Borrar temporal</button>
                    </div>
                </section>

                <section class="wizard-panel" data-wizard-panel="documentos" hidden>
                    <p class="module-note">Revise cada documento y marque su aceptacion. Los documentos obligatorios habilitan el boton de matricula.</p>
                    <?php if (empty($documents)): ?>
                        <div class="empty-state">No existen documentos activos configurados para la matricula.</div>
                    <?php else: ?>
                        <div class="documents-stack" data-document-acceptance-list>
                            <?php foreach ($documents as $document): ?>
                                <?php
                                $documentId = (int) ($document['domid'] ?? 0);
                                $isRequired = !empty($document['domobligatorio']);
                                $documentUrl = $resolveDocumentUrl((string) ($document['domorigen'] ?? 'URL'), (string) ($document['domurl'] ?? ''));
                                ?>
                                <label class="document-card">
                                    <div class="document-card-main">
                                        <div class="document-card-title-row">
                                            <strong><?= htmlspecialchars((string) ($document['domnombre'] ?? 'Documento'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <span class="document-card-badge <?= $isRequired ? 'is-required' : 'is-optional'; ?>">
                                                <?= $isRequired ? 'Obligatorio' : 'Opcional'; ?>
                                            </span>
                                        </div>
                                        <?php if (trim((string) ($document['domdescripcion'] ?? '')) !== ''): ?>
                                            <p class="document-card-description"><?= htmlspecialchars((string) $document['domdescripcion'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <?php endif; ?>
                                        <a class="text-link" href="<?= htmlspecialchars($documentUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Abrir documento</a>
                                    </div>
                                    <span class="document-card-check">
                                        <input
                                            type="checkbox"
                                            name="documents[<?= htmlspecialchars((string) $documentId, ENT_QUOTES, 'UTF-8'); ?>]"
                                            value="1"
                                            data-document-checkbox
                                            <?= $isRequired ? 'data-document-required' : ''; ?>
                                            <?= in_array($documentId, $documentAcceptancesOld, true) ? 'checked' : ''; ?>
                                        >
                                        <span>Acepto</span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="actions-row">
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-save>Guardar</button>
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-clear>Borrar temporal</button>
                    </div>
                </section>

                <section class="wizard-panel" data-wizard-panel="contexto-familiar" hidden>
                    <div class="form-grid">
                        <div class="form-group form-group-full">
                            <div class="input-group">
                                <span class="input-addon">Convive con</span>
                                <div class="resource-grid cohabitation-grid">
                                    <?php foreach ($relationships as $relationship): ?>
                                        <?php
                                            $relationshipId = (int) ($relationship['pteid'] ?? 0);
                                            $relationshipName = (string) ($relationship['ptenombre'] ?? '');
                                            $relationshipKey = strtolower(trim($relationshipName));
                                            $isSelected = in_array($relationshipId, $cohabitationSelectedIds, true)
                                                || ($cohabitationSelectedIds === [] && in_array($relationshipKey, $cohabitationSelectedNames, true));
                                        ?>
                                        <label class="resource-option">
                                            <span><?= htmlspecialchars($relationshipName, ENT_QUOTES, 'UTF-8'); ?></span>
                                            <input
                                                type="checkbox"
                                                name="family_context[ecfconvivecon_pteids][<?= htmlspecialchars((string) $relationshipId, ENT_QUOTES, 'UTF-8'); ?>]"
                                                value="<?= htmlspecialchars((string) $relationshipId, ENT_QUOTES, 'UTF-8'); ?>"
                                                <?= $isSelected ? 'checked' : ''; ?>
                                            >
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">No. hermanos</span><input name="family_context[ecfnumerohermanos]" type="number" min="0" value="<?= htmlspecialchars((string) ($familyContextOld['ecfnumerohermanos'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Posicion</span><input name="family_context[ecfposicionhermanos]" placeholder="Ej: 1ro de 3" value="<?= htmlspecialchars((string) ($familyContextOld['ecfposicionhermanos'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                    </div>
                    <div class="actions-row">
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-save>Guardar</button>
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-clear>Borrar temporal</button>
                    </div>
                </section>

                <section class="wizard-panel" data-wizard-panel="matricula" hidden>
                    <div class="form-grid">
                        <div class="form-group"><div class="input-group"><span class="input-addon">Periodo</span><input type="text" value="<?= htmlspecialchars((string) (($newMatriculaPeriod['pledescripcion'] ?? $currentPeriod['pledescripcion'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" readonly></div></div>
                        <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Curso</span><select name="matricula[curid]" required><option value="">Seleccione</option><?php foreach ($courses as $course): ?><option value="<?= htmlspecialchars((string) $course['curid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($old['matricula']['curid'] ?? 0) === (int) $course['curid'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) ($course['nednombre'] . ' | ' . $course['granombre'] . ' | ' . $course['prlnombre']), ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Foto</span><input type="file" name="matricula_photo" accept=".jpg,.jpeg,.png,.webp"></div></div>
                    </div>
                    <div class="actions-row">
                        <button class="btn-primary btn-inline" type="submit" data-matricula-submit disabled>Guardar matricula</button>
                    </div>
                </section>

                <div class="alert alert-success alert-dismissible matricula-draft-alert wizard-feedback-bottom" data-matricula-draft-alert hidden>
                    <span data-matricula-draft-alert-message>Borrador guardado localmente. Puedes continuar con la matricula y finalizarla despues.</span>
                    <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                        <i class="fa fa-times" aria-hidden="true"></i>
                    </button>
                </div>

                <?php if (!empty($matriculaFormFeedback)): ?>
                    <div class="catalog-feedback security-feedback-global wizard-feedback-bottom">
                        <div class="alert <?= ($matriculaFormFeedback['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success'; ?> alert-dismissible" data-alert>
                            <span><?= htmlspecialchars((string) ($matriculaFormFeedback['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                            <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                                <i class="fa fa-times" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($activePanel === 'gestion'): ?>
    <section class="security-assignment-block" id="matriculas-registradas">
        <header class="security-assignment-header">
            <div><h3>Gestion de matriculas</h3><p>Listado de matriculas correspondientes al periodo actual. Desde aqui puedes habilitar o inhabilitar estudiantes matriculados.</p></div>
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
                    <thead><tr><th>Estudiante</th><th>Curso</th><th>Representante</th><th>Matricula</th><th>Fecha</th><th>Foto</th><th>Habilitacion</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($matriculas as $matricula): ?>
                            <tr>
                                <td><span class="cell-title"><?= htmlspecialchars((string) $matricula['percedula'], ENT_QUOTES, 'UTF-8'); ?></span><span class="cell-subtitle"><strong><?= htmlspecialchars((string) $matricula['perapellidos'], ENT_QUOTES, 'UTF-8'); ?></strong> <?= htmlspecialchars((string) $matricula['pernombres'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><?= htmlspecialchars((string) ($matricula['nednombre'] . ' | ' . $matricula['granombre'] . ' | ' . $matricula['prlnombre']), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars(trim((string) (($matricula['rep_apellidos'] ?? '') . ' ' . ($matricula['rep_nombres'] ?? '')) . (($matricula['rep_parentesco'] ?? '') !== '' ? ' (' . $matricula['rep_parentesco'] . ')' : '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) $matricula['emdnombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) $matricula['matfecha'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php if (!empty($matricula['matfoto'])): ?><a class="text-link" href="<?= htmlspecialchars(asset((string) $matricula['matfoto']), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Ver foto</a><?php else: ?>Sin foto<?php endif; ?></td>
                                <td>
                                    <span class="state-pill <?= !empty($matricula['estestado']) ? 'state-pill-active' : 'state-pill-inactive'; ?>">
                                        <?= !empty($matricula['estestado']) ? 'Habilitada' : 'Inhabilitada'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions-group">
                                        <form method="POST" action="<?= htmlspecialchars(baseUrl('matriculas/estado'), ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="matid" value="<?= htmlspecialchars((string) $matricula['matid'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="redirect_to" value="/matriculas?panel=gestion#matriculas-registradas">
                                            <button class="btn-primary btn-auto" type="submit">
                                                <?= !empty($matricula['estestado']) ? 'Inhabilitar' : 'Habilitar'; ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
