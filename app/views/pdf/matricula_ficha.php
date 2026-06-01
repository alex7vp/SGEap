<?php

declare(strict_types=1);

$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$text = static function (mixed $value, string $fallback = 'Sin registrar'): string {
    $normalized = trim((string) $value);

    return $normalized !== '' ? $normalized : $fallback;
};
$yesNo = static fn (mixed $value): string => !empty($value) ? 'Si' : 'No';
$date = static function (mixed $value) use ($text): string {
    $raw = trim((string) $value);

    if ($raw === '') {
        return 'Sin registrar';
    }

    $timestamp = strtotime($raw);

    return $timestamp !== false ? date('d/m/Y', $timestamp) : $text($raw);
};
$money = static fn (mixed $value): string => trim((string) $value) !== '' ? (string) $value : '0.00';

$institution = is_array($institution ?? null) ? $institution : [];
$profile = is_array($profile ?? null) ? $profile : [];
$matricula = is_array($matricula ?? null) ? $matricula : [];
$student = is_array($profile['student'] ?? null) ? $profile['student'] : [];
$matriculation = is_array($profile['matriculation'] ?? null) ? $profile['matriculation'] : [];
$representative = is_array($profile['representative'] ?? null) ? $profile['representative'] : [];
$families = is_array($profile['families'] ?? null) ? $profile['families'] : [];
$healthContext = is_array($profile['health_context'] ?? null) ? $profile['health_context'] : [];
$healthInsurance = is_array($profile['health_insurance'] ?? null) ? $profile['health_insurance'] : [];
$healthConditions = is_array($profile['health_conditions'] ?? null) ? $profile['health_conditions'] : [];
$healthMeasurement = is_array($profile['health_measurement'] ?? null) ? $profile['health_measurement'] : [];
$resources = is_array($profile['resources'] ?? null) ? $profile['resources'] : [];
$billing = is_array($profile['billing'] ?? null) ? $profile['billing'] : [];
$documents = is_array($profile['documents'] ?? null) ? $profile['documents'] : [];

$studentName = trim((string) (($student['perapellidos'] ?? '') . ' ' . ($student['pernombres'] ?? '')));
$representativeName = trim((string) (($representative['perapellidos'] ?? '') . ' ' . ($representative['pernombres'] ?? '')));
$courseName = (string) ($matriculation['curso'] ?? (($matricula['granombre'] ?? '') . ' ' . ($matricula['prlnombre'] ?? '')));
$periodName = (string) ($matriculation['pledescripcion'] ?? $matricula['pledescripcion'] ?? '');
$logoPath = BASE_PATH . '/public/assets/images/institucion-logo.png';
$photoPath = trim((string) ($matriculation['matfoto'] ?? ''));
$absolutePhotoPath = $photoPath !== '' ? BASE_PATH . '/public/assets/' . ltrim($photoPath, '/') : '';
$canRenderImages = extension_loaded('gd');

$resourceLabels = [
    'mrtinternet' => 'Internet',
    'mrtcomputador' => 'Computador',
    'mrtlaptop' => 'Laptop',
    'mrttablet' => 'Tablet',
    'mrtcelular' => 'Telefono inteligente',
    'mrtimpresora' => 'Impresora',
];
$mother = null;
$father = null;
$otherFamilies = [];

foreach ($families as $family) {
    $relationship = mb_strtolower(trim((string) ($family['ptenombre'] ?? '')));

    if ($mother === null && $relationship === 'madre') {
        $mother = $family;
        continue;
    }

    if ($father === null && $relationship === 'padre') {
        $father = $family;
        continue;
    }

    $otherFamilies[] = $family;
}

$hasInstitutionMeta = trim((string) ($institution['insruc'] ?? '')) !== ''
    || trim((string) ($institution['inscodigoamie'] ?? '')) !== '';
$hasInstitutionAddress = trim((string) ($institution['insdireccion'] ?? '')) !== '';
$familyPersonName = static fn (array $family): string => trim((string) (($family['perapellidos'] ?? '') . ' ' . ($family['pernombres'] ?? '')));
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 22px 28px; }
        body { color: #1f2933; font-family: "DejaVu Sans", sans-serif; font-size: 10px; line-height: 1.35; }
        h1, h2, h3, p { margin: 0; }
        .header { border-bottom: 2px solid #1f5f8b; display: table; margin-bottom: 12px; padding-bottom: 10px; width: 100%; }
        .header-logo, .header-main, .header-meta { display: table-cell; vertical-align: top; }
        .header-logo { width: 64px; }
        .header-logo img { max-height: 56px; max-width: 56px; }
        .header-main h1 { color: #123f5f; font-size: 16px; text-transform: uppercase; }
        .header-main p { color: #52616b; font-size: 9px; margin-top: 2px; }
        .header-meta { color: #52616b; font-size: 9px; text-align: right; width: 150px; }
        .title-band { background: #123f5f; color: #fff; margin-bottom: 12px; padding: 8px 10px; }
        .title-band h2 { font-size: 15px; text-transform: uppercase; }
        .title-band p { font-size: 10px; margin-top: 2px; }
        .summary { display: table; margin-bottom: 12px; width: 100%; }
        .summary-main, .summary-photo { display: table-cell; vertical-align: top; }
        .summary-main { width: 78%; }
        .summary-photo { text-align: right; width: 22%; }
        .photo-box { border: 1px solid #c9d3dc; display: inline-block; height: 112px; text-align: center; width: 92px; }
        .photo-box img { max-height: 112px; max-width: 92px; }
        .photo-empty { color: #7b8794; font-size: 9px; padding-top: 44px; }
        .grid { display: table; width: 100%; }
        .col { display: table-cell; padding-right: 8px; vertical-align: top; width: 50%; }
        .col:last-child { padding-right: 0; }
        .section { border: 1px solid #d9e2ec; margin-bottom: 10px; page-break-inside: avoid; }
        .section h3 { background: #eef4f8; color: #123f5f; font-size: 11px; padding: 5px 7px; text-transform: uppercase; }
        .content { padding: 6px 7px; }
        .row { border-bottom: 1px solid #edf2f7; display: table; width: 100%; }
        .row:last-child { border-bottom: 0; }
        .label, .value { display: table-cell; padding: 3px 0; vertical-align: top; }
        .label { color: #52616b; font-weight: bold; width: 38%; }
        .value { color: #1f2933; width: 62%; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #eef4f8; color: #123f5f; font-size: 9px; text-align: left; }
        th, td { border: 1px solid #d9e2ec; padding: 4px; vertical-align: top; }
        .muted { color: #7b8794; }
        .signature-grid { display: table; margin-top: 26px; width: 100%; }
        .signature { display: table-cell; padding: 0 18px; text-align: center; width: 50%; }
        .signature-line { border-top: 1px solid #1f2933; padding-top: 4px; }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-logo">
            <?php if ($canRenderImages && is_file($logoPath)): ?>
                <img src="<?= $h($logoPath); ?>" alt="Logo">
            <?php endif; ?>
        </div>
        <div class="header-main">
            <h1><?= $h($text($institution['insnombre'] ?? $appName ?? 'SGEap', 'SGEap')); ?></h1>
            <p><strong>Periodo lectivo:</strong> <?= $h($text($periodName, 'Sin periodo')); ?></p>
            <?php if ($hasInstitutionMeta): ?>
                <p>
                    <?php if (trim((string) ($institution['insruc'] ?? '')) !== ''): ?>
                        RUC: <?= $h($institution['insruc']); ?>
                    <?php endif; ?>
                    <?php if (trim((string) ($institution['insruc'] ?? '')) !== '' && trim((string) ($institution['inscodigoamie'] ?? '')) !== ''): ?>
                        |
                    <?php endif; ?>
                    <?php if (trim((string) ($institution['inscodigoamie'] ?? '')) !== ''): ?>
                        AMIE: <?= $h($institution['inscodigoamie']); ?>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
            <?php if ($hasInstitutionAddress): ?>
                <p><?= $h($institution['insdireccion']); ?></p>
            <?php endif; ?>
        </div>
        <div class="header-meta">
            <p>Generado: <?= $h($generatedAt ?? date('Y-m-d H:i')); ?></p>
            <p>Matricula No. <?= $h($matricula['matid'] ?? $matriculation['matid'] ?? ''); ?></p>
        </div>
    </header>

    <section class="title-band">
        <h2>Ficha de matricula</h2>
        <p><?= $h($text($studentName, 'Estudiante sin nombre')); ?> | <?= $h($text($periodName, 'Periodo no registrado')); ?></p>
    </section>

    <section class="summary">
        <div class="summary-main">
            <div class="grid">
                <div class="col">
                    <div class="section">
                        <h3>Datos del estudiante</h3>
                        <div class="content">
                            <div class="row"><div class="label">Cedula</div><div class="value"><?= $h($text($student['percedula'] ?? null)); ?></div></div>
                            <div class="row"><div class="label">Apellidos</div><div class="value"><?= $h($text($student['perapellidos'] ?? null)); ?></div></div>
                            <div class="row"><div class="label">Nombres</div><div class="value"><?= $h($text($student['pernombres'] ?? null)); ?></div></div>
                            <div class="row"><div class="label">Sexo</div><div class="value"><?= $h($text($student['persexo'] ?? null)); ?></div></div>
                            <div class="row"><div class="label">Nacimiento</div><div class="value"><?= $h($date($student['perfechanacimiento'] ?? null)); ?></div></div>
                            <div class="row"><div class="label">Lugar nacimiento</div><div class="value"><?= $h($text($student['estlugarnacimiento'] ?? null)); ?></div></div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="section">
                        <h3>Matricula</h3>
                        <div class="content">
                            <div class="row"><div class="label">Periodo</div><div class="value"><?= $h($text($periodName)); ?></div></div>
                            <div class="row"><div class="label">Curso</div><div class="value"><?= $h($text($courseName)); ?></div></div>
                            <div class="row"><div class="label">Nivel</div><div class="value"><?= $h($text($matriculation['nednombre'] ?? $matricula['nednombre'] ?? null)); ?></div></div>
                            <div class="row"><div class="label">Fecha</div><div class="value"><?= $h($date($matriculation['matfecha'] ?? $matricula['matfecha'] ?? null)); ?></div></div>
                            <div class="row"><div class="label">Tipo</div><div class="value"><?= $h($text($matriculation['tmanombre'] ?? $matricula['tmanombre'] ?? null)); ?></div></div>
                            <div class="row"><div class="label">Estado</div><div class="value"><?= $h($text($matriculation['emdnombre'] ?? $matricula['emdnombre'] ?? null)); ?></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="summary-photo">
            <div class="photo-box">
                <?php if ($canRenderImages && $absolutePhotoPath !== '' && is_file($absolutePhotoPath)): ?>
                    <img src="<?= $h($absolutePhotoPath); ?>" alt="Foto">
                <?php else: ?>
                    <div class="photo-empty"><?= $canRenderImages ? 'Sin foto' : 'Foto no disponible' ?></div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <div class="grid">
        <div class="col">
            <div class="section">
                <h3>Contacto y domicilio</h3>
                <div class="content">
                    <div class="row"><div class="label">Direccion</div><div class="value"><?= $h($text($student['estdireccion'] ?? null)); ?></div></div>
                    <div class="row"><div class="label">Parroquia</div><div class="value"><?= $h($text($student['estparroquia'] ?? null)); ?></div></div>
                    <div class="row"><div class="label">Telefono 1</div><div class="value"><?= $h($text($student['pertelefono1'] ?? null)); ?></div></div>
                    <div class="row"><div class="label">Telefono 2</div><div class="value"><?= $h($text($student['pertelefono2'] ?? null)); ?></div></div>
                    <div class="row"><div class="label">Correo</div><div class="value"><?= $h($text($student['percorreo'] ?? null)); ?></div></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="section">
                <h3>Representante</h3>
                <div class="content">
                    <div class="row"><div class="label">Nombre</div><div class="value"><?= $h($text($representativeName)); ?></div></div>
                    <div class="row"><div class="label">Cedula</div><div class="value"><?= $h($text($representative['percedula'] ?? null)); ?></div></div>
                    <div class="row"><div class="label">Parentesco</div><div class="value"><?= $h($text($representative['ptenombre'] ?? null)); ?></div></div>
                    <div class="row"><div class="label">Telefono</div><div class="value"><?= $h($text($representative['pertelefono1'] ?? null)); ?></div></div>
                    <div class="row"><div class="label">Correo</div><div class="value"><?= $h($text($representative['percorreo'] ?? null)); ?></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <h3>Datos familiares</h3>
        <div class="content">
            <?php if ($families === []): ?>
                <p class="muted">Sin familiares registrados.</p>
            <?php else: ?>
                <div class="grid">
                    <div class="col">
                        <div class="section">
                            <h3>Padre</h3>
                            <div class="content">
                                <?php if ($father === null): ?>
                                    <p class="muted">Sin registrar.</p>
                                <?php else: ?>
                                    <div class="row"><div class="label">Nombre</div><div class="value"><?= $h($text($familyPersonName($father))); ?></div></div>
                                    <div class="row"><div class="label">Cedula</div><div class="value"><?= $h($text($father['percedula'] ?? null)); ?></div></div>
                                    <div class="row"><div class="label">Telefono</div><div class="value"><?= $h($text($father['pertelefono1'] ?? null)); ?></div></div>
                                    <div class="row"><div class="label">Correo</div><div class="value"><?= $h($text($father['percorreo'] ?? null)); ?></div></div>
                                    <div class="row"><div class="label">Ocupacion</div><div class="value"><?= $h($text($father['perocupacion'] ?? null)); ?></div></div>
                                    <div class="row"><div class="label">Lugar trabajo</div><div class="value"><?= $h($text($father['perlugardetrabajo'] ?? null)); ?></div></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="section">
                            <h3>Madre</h3>
                            <div class="content">
                                <?php if ($mother === null): ?>
                                    <p class="muted">Sin registrar.</p>
                                <?php else: ?>
                                    <div class="row"><div class="label">Nombre</div><div class="value"><?= $h($text($familyPersonName($mother))); ?></div></div>
                                    <div class="row"><div class="label">Cedula</div><div class="value"><?= $h($text($mother['percedula'] ?? null)); ?></div></div>
                                    <div class="row"><div class="label">Telefono</div><div class="value"><?= $h($text($mother['pertelefono1'] ?? null)); ?></div></div>
                                    <div class="row"><div class="label">Correo</div><div class="value"><?= $h($text($mother['percorreo'] ?? null)); ?></div></div>
                                    <div class="row"><div class="label">Ocupacion</div><div class="value"><?= $h($text($mother['perocupacion'] ?? null)); ?></div></div>
                                    <div class="row"><div class="label">Lugar trabajo</div><div class="value"><?= $h($text($mother['perlugardetrabajo'] ?? null)); ?></div></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if ($otherFamilies !== []): ?>
                <table>
                    <thead><tr><th>Parentesco</th><th>Identificacion</th><th>Nombre</th><th>Telefono</th><th>Correo</th><th>Ocupacion</th></tr></thead>
                    <tbody>
                        <?php foreach ($otherFamilies as $family): ?>
                            <tr>
                                <td><?= $h($text($family['ptenombre'] ?? null)); ?></td>
                                <td><?= $h($text($family['percedula'] ?? null)); ?></td>
                                <td><?= $h($text($familyPersonName($family))); ?></td>
                                <td><?= $h($text($family['pertelefono1'] ?? null)); ?></td>
                                <td><?= $h($text($family['percorreo'] ?? null)); ?></td>
                                <td><?= $h($text($family['perocupacion'] ?? null)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="section">
        <h3>Salud</h3>
        <div class="content">
            <div class="grid">
                <div class="col">
                    <div class="row"><div class="label">Grupo sanguineo</div><div class="value"><?= $h($text($healthContext['gsnombre'] ?? null)); ?></div></div>
                    <div class="row"><div class="label">Atencion medica</div><div class="value"><?= $h($text($healthContext['amnombre'] ?? null)); ?></div></div>
                    <div class="row"><div class="label">Discapacidad</div><div class="value"><?= $h($yesNo($healthContext['ecstienediscapacidad'] ?? false)); ?></div></div>
                </div>
                <div class="col">
                    <div class="row"><div class="label">Detalle</div><div class="value"><?= $h($text($healthContext['ecsdetallediscapacidad'] ?? null)); ?></div></div>
                    <div class="row"><div class="label">Seguro</div><div class="value"><?= $h($text($healthInsurance['smnombre'] ?? null)); ?></div></div>
                    <div class="row"><div class="label">Peso / talla / IMC</div><div class="value"><?= $h($text($healthMeasurement['emspeso'] ?? null)); ?> kg / <?= $h($text($healthMeasurement['emstalla'] ?? null)); ?> cm / <?= $h($text($healthMeasurement['emsimc'] ?? null)); ?></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <h3>Condiciones de salud</h3>
        <div class="content">
            <?php if ($healthConditions === []): ?>
                <p class="muted">Sin condiciones de salud registradas.</p>
            <?php else: ?>
                <table>
                    <thead><tr><th>Tipo</th><th>Descripcion</th><th>Medicamentos</th><th>Observacion</th><th>Vigente</th></tr></thead>
                    <tbody>
                        <?php foreach ($healthConditions as $condition): ?>
                            <tr>
                                <td><?= $h($text($condition['tcsnombre'] ?? null)); ?></td>
                                <td><?= $h($text($condition['ecsadescripcion'] ?? null)); ?></td>
                                <td><?= $h($text($condition['ecsamedicamentos'] ?? null)); ?></td>
                                <td><?= $h($text($condition['ecsaobservacion'] ?? null)); ?></td>
                                <td><?= $h($yesNo($condition['ecsavigente'] ?? false)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="section">
        <h3>Recursos y facturacion</h3>
        <div class="content">
            <div class="grid">
                <div class="col">
                    <?php foreach ($resourceLabels as $key => $label): ?>
                        <div class="row"><div class="label"><?= $h($label); ?></div><div class="value"><?= $h($yesNo($resources[$key] ?? false)); ?></div></div>
                    <?php endforeach; ?>
                </div>
                <div class="col">
                    <div class="row"><div class="label">Factura a</div><div class="value"><?= $h($text($billing['mfcnombre'] ?? null)); ?></div></div>
                    <div class="row"><div class="label">Identificacion</div><div class="value"><?= $h($text($billing['mfcidentificacion'] ?? null)); ?></div></div>
                    <div class="row"><div class="label">Direccion</div><div class="value"><?= $h($text($billing['mfcdireccion'] ?? null)); ?></div></div>
                    <div class="row"><div class="label">Correo</div><div class="value"><?= $h($text($billing['mfccorreo'] ?? null)); ?></div></div>
                    <div class="row"><div class="label">Telefono</div><div class="value"><?= $h($text($billing['mfctelefono'] ?? null)); ?></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <h3>Documentos aceptados</h3>
        <div class="content">
            <?php if ($documents === []): ?>
                <p class="muted">Sin documentos registrados.</p>
            <?php else: ?>
                <table>
                    <thead><tr><th>Documento</th><th>Obligatorio</th><th>Aceptado</th><th>Fecha aceptacion</th></tr></thead>
                    <tbody>
                        <?php foreach ($documents as $document): ?>
                            <tr>
                                <td><?= $h($text($document['domnombre'] ?? null)); ?></td>
                                <td><?= $h($yesNo($document['domobligatorio'] ?? false)); ?></td>
                                <td><?= $h($yesNo($document['madaceptado'] ?? false)); ?></td>
                                <td><?= $h($text($document['madfecha_aceptacion'] ?? null)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="signature-grid">
        <div class="signature"><div class="signature-line">Representante</div></div>
        <div class="signature"><div class="signature-line">Secretaria</div></div>
    </div>
</body>
</html>
