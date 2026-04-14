<?php

declare(strict_types=1);

require BASE_PATH . '/app/views/partials/header.php';

$familyRows = $old['families'] ?? [];
$representativeOld = is_array($old['representative'] ?? null) ? $old['representative'] : [];
$oldRepresentativeIndex = (int) ($representativeOld['family_index'] ?? 0);
$representativeSource = (string) ($representativeOld['source'] ?? 'family');
$externalRepresentative = is_array($representativeOld['external'] ?? null) ? $representativeOld['external'] : [];
$motherRelationship = null;
$fatherRelationship = null;

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
        'pteid' => 0,
        'eciid' => 0,
        'istid' => 0,
        'famprofesion' => '',
        'famlugardetrabajo' => '',
        'famfechanacimiento' => '',
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
        <div class="form-group"><div class="input-group"><span class="input-addon">Estado civil</span><select name="family[<?= htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?>][eciid]" data-family-dependent data-submit-enable <?= $familyDisabledAttribute; ?>><option value="">Seleccione</option><?php foreach ($civilStatuses as $civilStatus): ?><option value="<?= htmlspecialchars((string) $civilStatus['eciid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($family['eciid'] ?? 0) === (int) $civilStatus['eciid'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) $civilStatus['ecinombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
        <div class="form-group"><div class="input-group"><span class="input-addon">Instruccion</span><select name="family[<?= htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?>][istid]" data-family-dependent data-submit-enable <?= $familyDisabledAttribute; ?>><option value="">Seleccione</option><?php foreach ($instructionLevels as $instructionLevel): ?><option value="<?= htmlspecialchars((string) $instructionLevel['istid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($family['istid'] ?? 0) === (int) $instructionLevel['istid'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) $instructionLevel['istnombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
        <div class="form-group"><div class="input-group"><span class="input-addon">Celular</span><input name="family[<?= htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?>][pertelefono1]" placeholder="(09) 9894 5698" maxlength="14" inputmode="numeric" value="<?= htmlspecialchars((string) ($family['pertelefono1'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-phone-mask data-family-dependent data-family-person-field data-submit-enable <?= $personDisabledAttribute; ?>></div></div>
        <div class="form-group"><div class="input-group"><span class="input-addon">Correo</span><input name="family[<?= htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?>][percorreo]" type="email" value="<?= htmlspecialchars((string) ($family['percorreo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-family-dependent data-family-person-field data-submit-enable <?= $personDisabledAttribute; ?>></div></div>
        <div class="form-group"><div class="input-group"><span class="input-addon">Profesion</span><input name="family[<?= htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?>][famprofesion]" value="<?= htmlspecialchars((string) ($family['famprofesion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-family-dependent data-submit-enable <?= $familyDisabledAttribute; ?>></div></div>
        <div class="form-group"><div class="input-group"><span class="input-addon">Trabajo</span><input name="family[<?= htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?>][famlugardetrabajo]" value="<?= htmlspecialchars((string) ($family['famlugardetrabajo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-family-dependent data-submit-enable <?= $familyDisabledAttribute; ?>></div></div>
        <div class="form-group"><div class="input-group"><span class="input-addon">Nacimiento</span><input name="family[<?= htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?>][famfechanacimiento]" type="date" value="<?= htmlspecialchars((string) ($family['famfechanacimiento'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-family-dependent data-submit-enable <?= $familyDisabledAttribute; ?>></div></div>
    </div>
    <?php
};

ob_start();
$renderFamilyFields($emptyFamilyRow(), '__INDEX__', $civilStatuses, $instructionLevels, $relationships, null, $personLookupUrl);
$dynamicFamilyTemplate = ob_get_clean();
?>
<p class="module-note">La matriculacion consolida persona, estudiante, familiares, representante y curso en un solo flujo operativo.</p>

<nav class="module-subnav" aria-label="Submodulos de matriculas">
    <a class="<?= $activePanel === 'nueva' ? 'is-active' : ''; ?>" href="<?= htmlspecialchars(baseUrl('matriculas?panel=nueva'), ENT_QUOTES, 'UTF-8'); ?>">Nueva matricula</a>
    <a class="<?= $activePanel === 'gestion' ? 'is-active' : ''; ?>" href="<?= htmlspecialchars(baseUrl('matriculas?panel=gestion'), ENT_QUOTES, 'UTF-8'); ?>">Gestion de matriculas</a>
</nav>

<?php if ($currentPeriod === null): ?>
    <div class="empty-state">No hay un periodo lectivo seleccionado. Elige uno desde el chip de periodo en el navbar para continuar.</div>
<?php else: ?>
    <?php if ($activePanel === ''): ?>
    <section class="security-assignment-block">
        <div class="empty-state">
            Selecciona una opcion para continuar con el modulo de matriculas:
            <a class="text-link" href="<?= htmlspecialchars(baseUrl('matriculas?panel=nueva'), ENT_QUOTES, 'UTF-8'); ?>">Nueva matricula</a>
            o
            <a class="text-link" href="<?= htmlspecialchars(baseUrl('matriculas?panel=gestion'), ENT_QUOTES, 'UTF-8'); ?>">Gestion de matriculas</a>.
        </div>
    </section>
    <?php endif; ?>

    <?php if ($activePanel === 'nueva'): ?>
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
                    <span data-matricula-draft-alert-message>Borrador guardado localmente. Puedes continuar con la matricula y finalizarla despues.</span>
                    <button class="alert-close" type="button" aria-label="Cerrar notificacion" data-alert-close>
                        <i class="fa fa-times" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="wizard-tabs" role="tablist" aria-label="Secciones de matricula">
                    <button type="button" class="wizard-tab is-active" data-wizard-tab="persona">Estudiante</button>
                    <button type="button" class="wizard-tab" data-wizard-tab="estudiante">Datos Personales</button>
                    <button type="button" class="wizard-tab" data-wizard-tab="familiares">Datos Familiares</button>
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
                            <div class="form-group"><div class="input-group"><span class="input-addon">Nombres</span><input name="representative_external[pernombres]" value="<?= htmlspecialchars((string) ($externalRepresentative['pernombres'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-representative-external-person-field></div></div>
                            <div class="form-group"><div class="input-group"><span class="input-addon">Apellidos</span><input name="representative_external[perapellidos]" value="<?= htmlspecialchars((string) ($externalRepresentative['perapellidos'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-representative-external-person-field></div></div>
                            <div class="form-group"><div class="input-group"><span class="input-addon">Parentesco</span><select name="representative_external[pteid]" data-representative-external-detail-field><option value="">Seleccione</option><?php foreach ($relationships as $relationship): ?><option value="<?= htmlspecialchars((string) $relationship['pteid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($externalRepresentative['pteid'] ?? 0) === (int) $relationship['pteid'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) $relationship['ptenombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
                            <div class="form-group"><div class="input-group"><span class="input-addon">Celular</span><input name="representative_external[pertelefono1]" placeholder="(09) 9894 5698" maxlength="14" inputmode="numeric" value="<?= htmlspecialchars((string) ($externalRepresentative['pertelefono1'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-phone-mask data-representative-external-person-field></div></div>
                            <div class="form-group"><div class="input-group"><span class="input-addon">Fijo</span><input name="representative_external[pertelefono2]" value="<?= htmlspecialchars((string) ($externalRepresentative['pertelefono2'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-representative-external-detail-field></div></div>
                            <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Correo</span><input name="representative_external[percorreo]" type="email" value="<?= htmlspecialchars((string) ($externalRepresentative['percorreo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-representative-external-person-field></div></div>
                        </div>
                    </div>
                    <div class="actions-row">
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-save>Guardar</button>
                        <button class="btn-secondary btn-inline" type="button" data-matricula-draft-clear>Borrar temporal</button>
                    </div>
                </section>

                <section class="wizard-panel" data-wizard-panel="matricula" hidden>
                    <div class="form-grid">
                        <div class="form-group"><div class="input-group"><span class="input-addon">Periodo</span><input type="text" value="<?= htmlspecialchars((string) $currentPeriod['pledescripcion'], ENT_QUOTES, 'UTF-8'); ?>" readonly></div></div>
                        <div class="form-group form-group-full"><div class="input-group"><span class="input-addon">Curso</span><select name="matricula[curid]" required><option value="">Seleccione</option><?php foreach ($courses as $course): ?><option value="<?= htmlspecialchars((string) $course['curid'], ENT_QUOTES, 'UTF-8'); ?>" <?= (int) ($old['matricula']['curid'] ?? 0) === (int) $course['curid'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) ($course['nednombre'] . ' | ' . $course['granombre'] . ' | ' . $course['prlnombre']), ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
                        <div class="form-group"><div class="input-group"><span class="input-addon">Foto</span><input type="file" name="matricula_photo" accept=".jpg,.jpeg,.png,.webp"></div></div>
                    </div>
                    <div class="actions-row">
                        <button class="btn-primary btn-inline" type="submit">Guardar matricula</button>
                    </div>
                </section>
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
