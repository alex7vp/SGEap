<?php

declare(strict_types=1);

use App\Core\Database;

define('BASE_PATH', dirname(__DIR__, 2));

require BASE_PATH . '/app/core/helpers.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $parts = explode('\\', $relativeClass);
    $className = array_pop($parts);
    $directories = array_map('strtolower', $parts);
    $file = BASE_PATH . '/app/' . implode('/', $directories);

    if ($file !== BASE_PATH . '/app/') {
        $file .= '/';
    }

    $file .= $className . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

loadEnvironment(BASE_PATH . '/.env');

$appConfig = require BASE_PATH . '/config/app.php';
date_default_timezone_set((string) ($appConfig['timezone'] ?? 'America/Guayaquil'));

$options = parseCliOptions($argv);
$excelPath = (string) ($options['file'] ?? 'C:\\Users\\Alex\\Downloads\\Matrículas (1).xlsx');
$commit = isset($options['commit']);
$periodDescription = (string) ($options['period'] ?? '2025 2026');
$parallelName = (string) ($options['parallel'] ?? 'A');
$matriculationDate = (string) ($options['date'] ?? '2025-09-01');
$reportPath = (string) ($options['report'] ?? BASE_PATH . '/storage/temp/import_matriculas_2025_2026_report.csv');
$repairRepresentatives = isset($options['repair-representatives']);

if (isset($options['help'])) {
    echo usage();
    exit(0);
}

if (!is_file($excelPath)) {
    fwrite(STDERR, "No existe el archivo Excel: {$excelPath}\n");
    exit(1);
}

try {
    $rows = readXlsxFirstSheet($excelPath);
    $plannedRows = planImport($rows);
    $summary = summarizePlan($plannedRows);

    if ($commit) {
        $summary = executeImport($plannedRows, $periodDescription, $parallelName, $matriculationDate, $summary, $repairRepresentatives);
    }

    writeReport($plannedRows, $reportPath);
    printSummary($summary, $commit, $excelPath, $reportPath);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}

function usage(): string
{
    return <<<TXT
Uso:
  php database/imports/import_matriculas_2025_2026.php [--file=RUTA] [--commit]

Opciones:
  --file=RUTA        Excel de origen. Por defecto usa C:\\Users\\Alex\\Downloads\\Matrículas (1).xlsx
  --period=PERIODO   Periodo lectivo destino. Por defecto: 2025 2026
  --parallel=NOMBRE  Paralelo destino. Por defecto: A
  --date=YYYY-MM-DD  Fecha de matricula. Por defecto: 2025-09-01
  --report=RUTA      Ruta del CSV de reporte.
  --commit           Escribe en base de datos. Sin esta opcion solo simula.
  --repair-representatives
                    Con --commit, intenta agregar representantes faltantes a matriculas ya importadas.

TXT;
}

function parseCliOptions(array $argv): array
{
    $options = [];

    foreach (array_slice($argv, 1) as $argument) {
        if (!str_starts_with($argument, '--')) {
            continue;
        }

        $argument = substr($argument, 2);

        if (str_contains($argument, '=')) {
            [$key, $value] = explode('=', $argument, 2);
            $options[$key] = $value;
            continue;
        }

        $options[$argument] = true;
    }

    return $options;
}

function readXlsxFirstSheet(string $path): array
{
    if (!class_exists(ZipArchive::class)) {
        throw new RuntimeException('La extension PHP zip es requerida para leer archivos .xlsx.');
    }

    $zip = new ZipArchive();

    if ($zip->open($path) !== true) {
        throw new RuntimeException('No se pudo abrir el archivo Excel.');
    }

    $sharedStrings = readSharedStrings($zip);
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    if ($sheetXml === false) {
        throw new RuntimeException('No se encontro xl/worksheets/sheet1.xml en el Excel.');
    }

    $sheet = simplexml_load_string($sheetXml);

    if ($sheet === false) {
        throw new RuntimeException('No se pudo leer la hoja principal del Excel.');
    }

    $rows = [];

    foreach ($sheet->sheetData->row as $rowNode) {
        $row = [
            '_row' => (int) $rowNode['r'],
            '_cells' => [],
        ];

        foreach ($rowNode->c as $cell) {
            $ref = (string) $cell['r'];
            $column = columnNumber($ref);
            $row['_cells'][$column] = cellValue($cell, $sharedStrings);
        }

        $rows[] = $row;
    }

    return $rows;
}

function readSharedStrings(ZipArchive $zip): array
{
    $xml = $zip->getFromName('xl/sharedStrings.xml');

    if ($xml === false) {
        return [];
    }

    $shared = simplexml_load_string($xml);

    if ($shared === false) {
        return [];
    }

    $strings = [];

    foreach ($shared->si as $item) {
        $strings[] = trim((string) domText($item));
    }

    return $strings;
}

function domText(SimpleXMLElement $node): string
{
    $dom = dom_import_simplexml($node);

    return $dom instanceof DOMNode ? (string) $dom->textContent : '';
}

function columnNumber(string $cellReference): int
{
    $letters = preg_replace('/\d+/', '', strtoupper($cellReference)) ?? '';
    $number = 0;

    foreach (str_split($letters) as $letter) {
        $number = ($number * 26) + (ord($letter) - ord('A') + 1);
    }

    return $number;
}

function cellValue(SimpleXMLElement $cell, array $sharedStrings): string
{
    $type = (string) $cell['t'];

    if ($type === 's') {
        $index = (int) $cell->v;
        return trim((string) ($sharedStrings[$index] ?? ''));
    }

    if ($type === 'inlineStr') {
        return trim((string) domText($cell->is));
    }

    return trim((string) $cell->v);
}

function planImport(array $rows): array
{
    $planned = [];
    $seenStudentCedulas = [];
    $artificialCounter = 1;

    foreach (array_slice($rows, 1) as $row) {
        $cells = $row['_cells'];

        if (!isRealStudentRow($cells)) {
            continue;
        }

        $originalCedula = onlyDigits(value($cells, 2));
        $studentCedula = $originalCedula;
        $warnings = [];
        $status = 'importable';
        $reason = '';

        if (!isValidTenDigitId($studentCedula)) {
            $studentCedula = artificialCedula($artificialCounter++);
            $warnings[] = 'cedula_estudiante_artificial';
        }

        if ($originalCedula !== '' && isValidTenDigitId($originalCedula)) {
            if (isset($seenStudentCedulas[$originalCedula])) {
                $status = 'omitted';
                $reason = 'duplicado_cedula_estudiante';
            } else {
                $seenStudentCedulas[$originalCedula] = true;
            }
        }

        $level = value($cells, 3);
        $gradeName = mapLevelToGrade($level);

        if ($status === 'importable' && $gradeName === null) {
            $status = 'omitted';
            $reason = 'nivel_no_mapeado';
        }

        $bloodGroup = normalizeBloodGroup(value($cells, 6));

        if (value($cells, 6) !== '' && $bloodGroup === null) {
            $warnings[] = 'grupo_sanguineo_no_importado';
        }

        $father = familyPayload($cells, 'Padre', 13, 14, 15, 16, 17, 18, 19, 20, 21);
        $mother = familyPayload($cells, 'Madre', 22, 23, 24, 25, 26, 27, 28, 29, 30);

        if ($father !== null && !isValidTenDigitId($father['percedula'])) {
            $warnings[] = 'padre_omitido_cedula_invalida';
            $father = null;
        }

        if ($mother !== null && !isValidTenDigitId($mother['percedula'])) {
            $warnings[] = 'madre_omitida_cedula_invalida';
            $mother = null;
        }

        $representative = resolveRepresentativeFromFamilies(value($cells, 33), value($cells, 34), $father, $mother);

        if (value($cells, 33) !== '' && $representative === null) {
            $warnings[] = 'representante_sin_cedula_no_insertado';
        } elseif ($representative !== null) {
            $warnings[] = 'representante_inferido_desde_' . strtolower($representative['relationship']);
        }

        $planned[] = [
            'excel_row' => (int) $row['_row'],
            'status' => $status,
            'reason' => $reason,
            'warnings' => $warnings,
        'student' => [
            'original_cedula' => $originalCedula,
            'percedula' => $studentCedula,
            'pernombres' => strLimit(normalizeName(value($cells, 4)), 100),
            'perapellidos' => strLimit(normalizeName(value($cells, 5)), 100),
            'pertelefono1' => normalizePhone(value($cells, 11)),
            'percorreo' => normalizeEmail(value($cells, 12)),
            'perfechanacimiento' => normalizeDate(value($cells, 7)),
            'estlugarnacimiento' => strLimit(normalizeName(value($cells, 8)), 150),
            'estparroquia' => strLimit(normalizeName(value($cells, 9)), 100),
            'estdireccion' => strLimit(normalizeText(value($cells, 10)), 250),
            'blood_group' => $bloodGroup,
        ],
            'course' => [
                'excel_level' => $level,
                'grade_name' => $gradeName,
            ],
            'family_context' => [
                'convive_con' => strLimit(normalizeText(value($cells, 31)), 250),
                'numero_hermanos' => normalizeSiblingCount(value($cells, 32)),
            ],
            'families' => array_values(array_filter([$father, $mother])),
            'representative' => $representative,
            'health' => [
                'has_condition' => parseBool(value($cells, 38)),
                'condition_detail' => strLimit(normalizeText(value($cells, 39)), 500),
            ],
            'resources' => normalizeResources(value($cells, 40), value($cells, 41)),
            'billing' => [
                'name' => strLimit(trim(normalizeName(value($cells, 42)) . ' ' . normalizeName(value($cells, 43))), 150),
                'identification' => onlyDigits(value($cells, 44)) !== '' ? onlyDigits(value($cells, 44)) : $studentCedula,
                'phone' => normalizePhone(value($cells, 45)),
                'address' => strLimit(normalizeText(value($cells, 46)), 250),
                'email' => normalizeEmail(value($cells, 47)),
            ],
        ];
    }

    return $planned;
}

function isRealStudentRow(array $cells): bool
{
    return value($cells, 2) !== '' || value($cells, 4) !== '' || value($cells, 5) !== '';
}

function value(array $cells, int $column): string
{
    return trim((string) ($cells[$column] ?? ''));
}

function onlyDigits(string $value): string
{
    return preg_replace('/\D+/', '', $value) ?? '';
}

function isValidTenDigitId(string $value): bool
{
    return preg_match('/^\d{10}$/', $value) === 1;
}

function artificialCedula(int $counter): string
{
    return str_pad((string) $counter, 10, '0', STR_PAD_LEFT);
}

function mapLevelToGrade(string $level): ?string
{
    $normalized = normalizeKey($level);
    $map = [
        'inicial 2 subnivel 1' => 'Inicial 2',
        'inicial 2 subnivel 2' => 'Inicial 2',
        '1ro de basica' => 'Preparatoria',
        '2do de basica' => '2do Año',
        '3ro de basica' => '3er Año',
        '4to de basica' => '4to Año',
        '5to de basica' => '5to Año',
        '6to de basica' => '6to Año',
        '7mo de basica' => '7mo Año',
        '8vo de basica' => '8vo Año',
        '9no de basica' => '9no Año',
        '10mo de basica' => '10mo Año',
        '1ro de bgu' => '1ro BGU',
        '2do de bgu' => '2do BGU',
        '3ro de bgu' => '3ro BGU',
    ];

    return $map[$normalized] ?? null;
}

function normalizeBloodGroup(string $value): ?string
{
    $normalized = strtoupper(str_replace(' ', '', trim($value)));
    $normalized = str_replace('0', 'O', $normalized);
    $allowed = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    return in_array($normalized, $allowed, true) ? $normalized : null;
}

function familyPayload(
    array $cells,
    string $relationship,
    int $cedulaCol,
    int $namesCol,
    int $lastnamesCol,
    int $instructionCol,
    int $professionCol,
    int $occupationCol,
    int $phone1Col,
    int $phone2Col,
    int $emailCol
): ?array {
    $cedula = onlyDigits(value($cells, $cedulaCol));
    $names = normalizeName(value($cells, $namesCol));
    $lastnames = normalizeName(value($cells, $lastnamesCol));

    if ($cedula === '' && $names === '' && $lastnames === '') {
        return null;
    }

    return [
        'relationship' => $relationship,
        'percedula' => $cedula,
        'pernombres' => strLimit($names, 100),
        'perapellidos' => strLimit($lastnames, 100),
        'istnombre' => normalizeInstruction(value($cells, $instructionCol)),
        'perprofesion' => strLimit(normalizeText(value($cells, $professionCol)), 150),
        'perocupacion' => strLimit(normalizeText(value($cells, $occupationCol)), 150),
        'pertelefono1' => normalizePhone(value($cells, $phone1Col)),
        'pertelefono2' => normalizePhone(value($cells, $phone2Col)),
        'percorreo' => normalizeEmail(value($cells, $emailCol)),
    ];
}

function resolveRepresentativeFromFamilies(string $representativeName, string $relationship, ?array $father, ?array $mother): ?array
{
    $relationshipKey = normalizeKey($relationship);

    if (str_contains($relationshipKey, 'padre') && $father !== null) {
        return [
            'relationship' => 'Padre',
            'percedula' => $father['percedula'],
        ];
    }

    if (str_contains($relationshipKey, 'madre') && $mother !== null) {
        return [
            'relationship' => 'Madre',
            'percedula' => $mother['percedula'],
        ];
    }

    $nameKey = normalizeKey($representativeName);

    if ($nameKey === '') {
        return null;
    }

    if ($father !== null) {
        $fatherName = normalizeKey(trim((string) $father['pernombres'] . ' ' . (string) $father['perapellidos']));
        $fatherNameReverse = normalizeKey(trim((string) $father['perapellidos'] . ' ' . (string) $father['pernombres']));

        if ($nameKey === $fatherName || $nameKey === $fatherNameReverse || str_contains($fatherName, $nameKey) || str_contains($fatherNameReverse, $nameKey)) {
            return [
                'relationship' => 'Padre',
                'percedula' => $father['percedula'],
            ];
        }
    }

    if ($mother !== null) {
        $motherName = normalizeKey(trim((string) $mother['pernombres'] . ' ' . (string) $mother['perapellidos']));
        $motherNameReverse = normalizeKey(trim((string) $mother['perapellidos'] . ' ' . (string) $mother['pernombres']));

        if ($nameKey === $motherName || $nameKey === $motherNameReverse || str_contains($motherName, $nameKey) || str_contains($motherNameReverse, $nameKey)) {
            return [
                'relationship' => 'Madre',
                'percedula' => $mother['percedula'],
            ];
        }
    }

    return null;
}

function normalizeInstruction(string $value): ?string
{
    $normalized = normalizeKey($value);

    return match ($normalized) {
        'sin instruccion' => 'Sin instruccion',
        'primaria' => 'Primaria',
        'secundaria' => 'Secundaria',
        'bachiller', 'bachillerato' => 'Bachiller',
        'tercer nivel', 'superior' => 'Tercer Nivel',
        'cuarto nivel' => 'Cuarto Nivel',
        'doctorado' => 'Doctorado',
        default => null,
    };
}

function normalizeResources(string $internet, string $devices): array
{
    $deviceKey = normalizeKey($devices);

    return [
        'internet' => parseBool($internet),
        'computador' => str_contains($deviceKey, 'computador'),
        'laptop' => str_contains($deviceKey, 'laptop'),
        'tablet' => str_contains($deviceKey, 'tablet'),
        'celular' => str_contains($deviceKey, 'celular') || str_contains($deviceKey, 'telefono'),
        'impresora' => str_contains($deviceKey, 'impresora'),
    ];
}

function parseBool(string $value): bool
{
    return in_array(normalizeKey($value), ['si', 'sí', 's', 'yes', '1', 'true'], true);
}

function normalizeSiblingCount(string $value): ?int
{
    $normalized = normalizeKey($value);
    $words = [
        'cero' => 0,
        'uno' => 1,
        'una' => 1,
        'dos' => 2,
        'tres' => 3,
        'cuatro' => 4,
        'cinco' => 5,
    ];

    if (isset($words[$normalized])) {
        return $words[$normalized];
    }

    $digits = onlyDigits($value);

    return $digits !== '' ? (int) $digits : null;
}

function normalizeDate(string $value): ?string
{
    $value = trim($value);

    if ($value === '') {
        return null;
    }

    if (preg_match('/^\d+(\.\d+)?$/', $value) === 1) {
        $days = (int) floor((float) $value);
        $date = DateTimeImmutable::createFromFormat('Y-m-d', '1899-12-30');
        return $date instanceof DateTimeImmutable ? $date->modify("+{$days} days")->format('Y-m-d') : null;
    }

    foreach (['Y/m/d', 'Y-n-j', 'Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
        $date = DateTimeImmutable::createFromFormat('!' . $format, $value);

        if ($date instanceof DateTimeImmutable) {
            return $date->format('Y-m-d');
        }
    }

    return null;
}

function normalizePhone(string $value): ?string
{
    $clean = onlyDigits($value);

    if ($clean === '' || normalizeKey($value) === 'no' || normalizeKey($value) === 'nd') {
        return null;
    }

    if (str_starts_with($clean, '593')) {
        $clean = '0' . substr($clean, 3);
    }

    return substr($clean, 0, 20);
}

function normalizeEmail(string $value): ?string
{
    $email = strtolower(trim($value));

    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $email : null;
}

function normalizeText(string $value): string
{
    $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

    return $value === 'System.Xml.XmlElement' ? '' : $value;
}

function normalizeName(string $value): string
{
    return normalizeText($value);
}

function strLimit(?string $value, int $maxLength): ?string
{
    if ($value === null) {
        return null;
    }

    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $maxLength, 'UTF-8');
    }

    return substr($value, 0, $maxLength);
}

function normalizeKey(string $value): string
{
    $value = mb_strtolower(normalizeText($value), 'UTF-8');
    $from = ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'];
    $to = ['a', 'e', 'i', 'o', 'u', 'u', 'n'];

    return str_replace($from, $to, $value);
}

function summarizePlan(array $plannedRows): array
{
    $summary = [
        'real_rows' => count($plannedRows),
        'importable' => 0,
        'omitted' => 0,
        'inserted' => 0,
        'skipped_existing' => 0,
        'errors' => 0,
        'artificial_student_ids' => 0,
        'duplicate_rows' => 0,
        'unmapped_levels' => 0,
        'blood_group_not_imported' => 0,
        'representative_without_id' => 0,
    ];

    foreach ($plannedRows as $row) {
        if ($row['status'] === 'importable') {
            $summary['importable']++;
        } else {
            $summary['omitted']++;
        }

        if ($row['reason'] === 'duplicado_cedula_estudiante') {
            $summary['duplicate_rows']++;
        }

        if ($row['reason'] === 'nivel_no_mapeado') {
            $summary['unmapped_levels']++;
        }

        if (in_array('cedula_estudiante_artificial', $row['warnings'], true)) {
            $summary['artificial_student_ids']++;
        }

        if (in_array('grupo_sanguineo_no_importado', $row['warnings'], true)) {
            $summary['blood_group_not_imported']++;
        }

        if (in_array('representante_sin_cedula_no_insertado', $row['warnings'], true)) {
            $summary['representative_without_id']++;
        }
    }

    return $summary;
}

function executeImport(array &$plannedRows, string $periodDescription, string $parallelName, string $matriculationDate, array $summary, bool $repairRepresentatives = false): array
{
    $db = Database::connection();
    $catalogs = loadCatalogs($db);
    $periodId = findRequiredId($db, 'periodo_lectivo', 'pleid', 'pledescripcion', $periodDescription);
    $statusId = findRequiredId($db, 'estado_matricula', 'emdid', 'emdnombre', 'Activo');
    $typeId = findRequiredId($db, 'tipo_matricula', 'tmaid', 'tmanombre', 'ORDINARIA');
    $conditionTypeId = $catalogs['health_condition_types']['CONDICION_MEDICA'] ?? null;

    $db->beginTransaction();

    try {
        foreach ($plannedRows as &$row) {
            if ($row['status'] !== 'importable') {
                continue;
            }

            $courseId = findCourseId($db, $periodId, (string) $row['course']['grade_name'], $parallelName);

            if ($courseId === null) {
                $row['status'] = 'omitted';
                $row['reason'] = 'curso_no_encontrado';
                $summary['omitted']++;
                $summary['importable']--;
                continue;
            }

            $studentPersonId = upsertPerson($db, [
                'percedula' => $row['student']['percedula'],
                'pernombres' => $row['student']['pernombres'],
                'perapellidos' => $row['student']['perapellidos'],
                'pertelefono1' => $row['student']['pertelefono1'],
                'pertelefono2' => null,
                'percorreo' => $row['student']['percorreo'],
                'persexo' => null,
                'perfechanacimiento' => $row['student']['perfechanacimiento'],
                'eciid' => null,
                'istid' => null,
                'perprofesion' => null,
                'perocupacion' => null,
                'perhablaingles' => false,
            ]);

            $studentId = ensureStudent($db, $studentPersonId, $row['student']);

            if (studentHasMatriculationInPeriod($db, $studentId, $periodId)) {
                if ($repairRepresentatives && repairRepresentative($db, $studentId, $periodId, $row, $catalogs)) {
                    $row['warnings'][] = 'representante_reparado';
                }

                $row['status'] = 'omitted';
                $row['reason'] = 'matricula_existente_periodo';
                $summary['skipped_existing']++;
                continue;
            }

            upsertFamilyContext($db, $studentId, $row['family_context']);
            upsertHealthContext($db, $studentId, $catalogs['blood_groups'][$row['student']['blood_group']] ?? null);

            if ($row['health']['has_condition'] && $row['health']['condition_detail'] !== '' && $conditionTypeId !== null) {
                insertHealthCondition($db, $studentId, $conditionTypeId, $row['health']['condition_detail']);
            }

            $familyPersonIds = [];

            foreach ($row['families'] as $family) {
                $personId = upsertPerson($db, [
                    'percedula' => $family['percedula'],
                    'pernombres' => $family['pernombres'],
                    'perapellidos' => $family['perapellidos'],
                    'pertelefono1' => $family['pertelefono1'],
                    'pertelefono2' => $family['pertelefono2'],
                    'percorreo' => $family['percorreo'],
                    'persexo' => null,
                    'perfechanacimiento' => null,
                    'eciid' => null,
                    'istid' => $family['istnombre'] !== null ? ($catalogs['instruction_levels'][$family['istnombre']] ?? null) : null,
                    'perprofesion' => $family['perprofesion'] !== '' ? $family['perprofesion'] : null,
                    'perocupacion' => $family['perocupacion'] !== '' ? $family['perocupacion'] : null,
                    'perhablaingles' => false,
                ]);

                $relationshipId = (int) $catalogs['relationships'][$family['relationship']];
                upsertFamily($db, $studentId, $personId, $relationshipId, null);
                $familyPersonIds[$family['relationship']] = [
                    'person_id' => $personId,
                    'relationship_id' => $relationshipId,
                ];
            }

            $matriculaId = insertMatriculation($db, $studentId, $courseId, $matriculationDate, $statusId, $typeId);
            insertRepresentativeFromPlan($db, $matriculaId, $row, $familyPersonIds);
            insertResources($db, $matriculaId, $row['resources']);
            insertBilling($db, $matriculaId, $row['billing'], $row['student']);

            $row['status'] = 'inserted';
            $row['reason'] = '';
            $summary['inserted']++;
        }

        $db->commit();
    } catch (Throwable $exception) {
        $db->rollBack();
        throw $exception;
    }

    return $summary;
}

function loadCatalogs(PDO $db): array
{
    return [
        'relationships' => keyValue($db, 'parentesco', 'ptenombre', 'pteid'),
        'instruction_levels' => keyValue($db, 'instruccion', 'istnombre', 'istid'),
        'blood_groups' => keyValue($db, 'grupo_sanguineo', 'gsnombre', 'gsid'),
        'health_condition_types' => keyValue($db, 'tipo_condicion_salud', 'tcsnombre', 'tcsid'),
    ];
}

function keyValue(PDO $db, string $table, string $keyColumn, string $valueColumn): array
{
    $rows = $db->query("SELECT {$keyColumn}, {$valueColumn} FROM {$table}")->fetchAll(PDO::FETCH_ASSOC);
    $values = [];

    foreach ($rows as $row) {
        $values[(string) $row[$keyColumn]] = (int) $row[$valueColumn];
    }

    return $values;
}

function findRequiredId(PDO $db, string $table, string $idColumn, string $nameColumn, string $name): int
{
    $statement = $db->prepare("SELECT {$idColumn} FROM {$table} WHERE {$nameColumn} = :name LIMIT 1");
    $statement->execute(['name' => $name]);
    $id = $statement->fetchColumn();

    if ($id === false) {
        throw new RuntimeException("No se encontro {$table}.{$nameColumn} = {$name}");
    }

    return (int) $id;
}

function findCourseId(PDO $db, int $periodId, string $gradeName, string $parallelName): ?int
{
    $statement = $db->prepare(
        "SELECT c.curid
         FROM curso c
         INNER JOIN grado g ON g.graid = c.graid
         INNER JOIN paralelo p ON p.prlid = c.prlid
         WHERE c.pleid = :period_id
           AND g.granombre = :grade_name
           AND p.prlnombre = :parallel_name
         LIMIT 1"
    );
    $statement->execute([
        'period_id' => $periodId,
        'grade_name' => $gradeName,
        'parallel_name' => $parallelName,
    ]);
    $id = $statement->fetchColumn();

    return $id === false ? null : (int) $id;
}

function upsertPerson(PDO $db, array $person): int
{
    $existing = findPersonByCedula($db, (string) $person['percedula']);

    if ($existing !== null) {
        $statement = $db->prepare(
            "UPDATE persona
             SET pernombres = :names,
                 perapellidos = :lastnames,
                 pertelefono1 = :phone1,
                 pertelefono2 = :phone2,
                 percorreo = :email,
                 persexo = :sex,
                 perfechanacimiento = :birth_date,
                 eciid = :civil_status,
                 istid = :instruction_level,
                 perprofesion = :profession,
                 perocupacion = :occupation,
                 perhablaingles = :speaks_english
             WHERE perid = :id"
        );
        $statement->execute(bindPerson($person, false) + ['id' => $existing]);

        return $existing;
    }

    $statement = $db->prepare(
        "INSERT INTO persona (
            percedula, pernombres, perapellidos, pertelefono1, pertelefono2, percorreo, persexo,
            perfechanacimiento, eciid, istid, perprofesion, perocupacion, perhablaingles
         ) VALUES (
            :cedula, :names, :lastnames, :phone1, :phone2, :email, :sex,
            :birth_date, :civil_status, :instruction_level, :profession, :occupation, :speaks_english
         ) RETURNING perid"
    );
    $statement->execute(bindPerson($person));

    return (int) $statement->fetchColumn();
}

function bindPerson(array $person, bool $includeCedula = true): array
{
    $payload = [
        'names' => strLimit((string) $person['pernombres'], 100),
        'lastnames' => strLimit((string) $person['perapellidos'], 100),
        'phone1' => $person['pertelefono1'],
        'phone2' => $person['pertelefono2'],
        'email' => strLimit($person['percorreo'], 150),
        'sex' => strLimit($person['persexo'], 20),
        'birth_date' => $person['perfechanacimiento'],
        'civil_status' => $person['eciid'],
        'instruction_level' => $person['istid'],
        'profession' => strLimit($person['perprofesion'], 150),
        'occupation' => strLimit($person['perocupacion'], 150),
        'speaks_english' => !empty($person['perhablaingles']) ? 'true' : 'false',
    ];

    if ($includeCedula) {
        $payload = ['cedula' => $person['percedula']] + $payload;
    }

    return $payload;
}

function findPersonByCedula(PDO $db, string $cedula): ?int
{
    $statement = $db->prepare('SELECT perid FROM persona WHERE percedula = :cedula LIMIT 1');
    $statement->execute(['cedula' => $cedula]);
    $id = $statement->fetchColumn();

    return $id === false ? null : (int) $id;
}

function ensureStudent(PDO $db, int $personId, array $student): int
{
    $statement = $db->prepare('SELECT estid FROM estudiante WHERE perid = :person_id LIMIT 1');
    $statement->execute(['person_id' => $personId]);
    $existing = $statement->fetchColumn();

    if ($existing !== false) {
        $update = $db->prepare(
            "UPDATE estudiante
             SET estlugarnacimiento = :birth_place,
                 estdireccion = :address,
                 estparroquia = :parish,
                 estestado = true
             WHERE estid = :id"
        );
        $update->execute([
            'id' => (int) $existing,
            'birth_place' => $student['estlugarnacimiento'] !== '' ? strLimit($student['estlugarnacimiento'], 150) : null,
            'address' => $student['estdireccion'] !== '' ? strLimit($student['estdireccion'], 250) : null,
            'parish' => $student['estparroquia'] !== '' ? strLimit($student['estparroquia'], 100) : null,
        ]);

        return (int) $existing;
    }

    $insert = $db->prepare(
        "INSERT INTO estudiante (perid, estlugarnacimiento, estdireccion, estparroquia, estestado)
         VALUES (:person_id, :birth_place, :address, :parish, true)
         RETURNING estid"
    );
    $insert->execute([
        'person_id' => $personId,
        'birth_place' => $student['estlugarnacimiento'] !== '' ? strLimit($student['estlugarnacimiento'], 150) : null,
        'address' => $student['estdireccion'] !== '' ? strLimit($student['estdireccion'], 250) : null,
        'parish' => $student['estparroquia'] !== '' ? strLimit($student['estparroquia'], 100) : null,
    ]);

    return (int) $insert->fetchColumn();
}

function studentHasMatriculationInPeriod(PDO $db, int $studentId, int $periodId): bool
{
    $statement = $db->prepare(
        "SELECT 1
         FROM matricula m
         INNER JOIN curso c ON c.curid = m.curid
         WHERE m.estid = :student_id
           AND c.pleid = :period_id
         LIMIT 1"
    );
    $statement->execute([
        'student_id' => $studentId,
        'period_id' => $periodId,
    ]);

    return $statement->fetchColumn() !== false;
}

function findMatriculationIdInPeriod(PDO $db, int $studentId, int $periodId): ?int
{
    $statement = $db->prepare(
        "SELECT m.matid
         FROM matricula m
         INNER JOIN curso c ON c.curid = m.curid
         WHERE m.estid = :student_id
           AND c.pleid = :period_id
         ORDER BY m.matfecha DESC, m.matid DESC
         LIMIT 1"
    );
    $statement->execute([
        'student_id' => $studentId,
        'period_id' => $periodId,
    ]);
    $id = $statement->fetchColumn();

    return $id === false ? null : (int) $id;
}

function repairRepresentative(PDO $db, int $studentId, int $periodId, array $row, array $catalogs): bool
{
    if (!is_array($row['representative'] ?? null)) {
        return false;
    }

    $matriculaId = findMatriculationIdInPeriod($db, $studentId, $periodId);

    if ($matriculaId === null || matriculationHasRepresentative($db, $matriculaId)) {
        return false;
    }

    $family = null;

    foreach ($row['families'] as $candidate) {
        if (($candidate['relationship'] ?? '') === $row['representative']['relationship']) {
            $family = $candidate;
            break;
        }
    }

    if ($family === null) {
        return false;
    }

    $relationshipId = (int) ($catalogs['relationships'][$family['relationship']] ?? 0);

    if ($relationshipId <= 0) {
        return false;
    }

    $personId = upsertPerson($db, [
        'percedula' => $family['percedula'],
        'pernombres' => $family['pernombres'],
        'perapellidos' => $family['perapellidos'],
        'pertelefono1' => $family['pertelefono1'],
        'pertelefono2' => $family['pertelefono2'],
        'percorreo' => $family['percorreo'],
        'persexo' => null,
        'perfechanacimiento' => null,
        'eciid' => null,
        'istid' => $family['istnombre'] !== null ? ($catalogs['instruction_levels'][$family['istnombre']] ?? null) : null,
        'perprofesion' => $family['perprofesion'] !== '' ? $family['perprofesion'] : null,
        'perocupacion' => $family['perocupacion'] !== '' ? $family['perocupacion'] : null,
        'perhablaingles' => false,
    ]);

    upsertFamily($db, $studentId, $personId, $relationshipId, null);
    insertMatriculationRepresentativeIfMissing($db, $matriculaId, $personId, $relationshipId);

    return true;
}

function upsertFamilyContext(PDO $db, int $studentId, array $context): void
{
    $statement = $db->prepare(
        "INSERT INTO estudiante_contexto_familiar (estid, ecfconvivecon, ecfnumerohermanos)
         VALUES (:student_id, :cohabits, :siblings)
         ON CONFLICT (estid) DO UPDATE
         SET ecfconvivecon = EXCLUDED.ecfconvivecon,
             ecfnumerohermanos = EXCLUDED.ecfnumerohermanos,
             ecffecha_modificacion = CURRENT_TIMESTAMP"
    );
    $statement->execute([
        'student_id' => $studentId,
        'cohabits' => $context['convive_con'] !== '' ? strLimit($context['convive_con'], 250) : null,
        'siblings' => $context['numero_hermanos'],
    ]);
}

function upsertHealthContext(PDO $db, int $studentId, ?int $bloodGroupId): void
{
    $statement = $db->prepare(
        "INSERT INTO estudiante_contexto_salud (estid, gsid)
         VALUES (:student_id, :blood_group_id)
         ON CONFLICT (estid) DO UPDATE
         SET gsid = EXCLUDED.gsid,
             ecsfecha_modificacion = CURRENT_TIMESTAMP"
    );
    $statement->execute([
        'student_id' => $studentId,
        'blood_group_id' => $bloodGroupId,
    ]);
}

function insertHealthCondition(PDO $db, int $studentId, int $conditionTypeId, string $detail): void
{
    $statement = $db->prepare(
        "INSERT INTO estudiante_condicion_salud (estid, tcsid, ecsadescripcion, ecsavigente)
         VALUES (:student_id, :type_id, :description, true)"
    );
    $statement->execute([
        'student_id' => $studentId,
        'type_id' => $conditionTypeId,
        'description' => strLimit($detail, 500),
    ]);
}

function upsertFamily(PDO $db, int $studentId, int $personId, int $relationshipId, ?string $workplace): void
{
    $statement = $db->prepare(
        "INSERT INTO familiar (estid, perid, pteid, famlugardetrabajo)
         VALUES (:student_id, :person_id, :relationship_id, :workplace)
         ON CONFLICT (estid, perid, pteid) DO UPDATE
         SET famlugardetrabajo = EXCLUDED.famlugardetrabajo"
    );
    $statement->execute([
        'student_id' => $studentId,
        'person_id' => $personId,
        'relationship_id' => $relationshipId,
        'workplace' => strLimit($workplace, 150),
    ]);
}

function insertRepresentativeFromPlan(PDO $db, int $matriculaId, array $row, array $familyPersonIds): void
{
    if (!is_array($row['representative'] ?? null)) {
        return;
    }

    $relationship = (string) ($row['representative']['relationship'] ?? '');
    $family = $familyPersonIds[$relationship] ?? null;

    if (!is_array($family)) {
        return;
    }

    insertMatriculationRepresentativeIfMissing(
        $db,
        $matriculaId,
        (int) $family['person_id'],
        (int) $family['relationship_id']
    );
}

function matriculationHasRepresentative(PDO $db, int $matriculaId): bool
{
    $statement = $db->prepare(
        "SELECT 1
         FROM matricula_representante
         WHERE matid = :matricula_id
         LIMIT 1"
    );
    $statement->execute(['matricula_id' => $matriculaId]);

    return $statement->fetchColumn() !== false;
}

function insertMatriculationRepresentativeIfMissing(PDO $db, int $matriculaId, int $personId, int $relationshipId): void
{
    $statement = $db->prepare(
        "INSERT INTO matricula_representante (matid, perid, pteid)
         VALUES (:matricula_id, :person_id, :relationship_id)
         ON CONFLICT (matid) DO NOTHING"
    );
    $statement->execute([
        'matricula_id' => $matriculaId,
        'person_id' => $personId,
        'relationship_id' => $relationshipId,
    ]);
}

function insertMatriculation(PDO $db, int $studentId, int $courseId, string $date, int $statusId, int $typeId): int
{
    $statement = $db->prepare(
        "INSERT INTO matricula (estid, curid, matfecha, matfoto, emdid, tmaid)
         VALUES (:student_id, :course_id, :date, null, :status_id, :type_id)
         RETURNING matid"
    );
    $statement->execute([
        'student_id' => $studentId,
        'course_id' => $courseId,
        'date' => $date,
        'status_id' => $statusId,
        'type_id' => $typeId,
    ]);

    return (int) $statement->fetchColumn();
}

function insertResources(PDO $db, int $matriculaId, array $resources): void
{
    $statement = $db->prepare(
        "INSERT INTO matricula_recurso_tecnologico (
            matid, mrtinternet, mrtcomputador, mrtlaptop, mrttablet, mrtcelular, mrtimpresora
         ) VALUES (
            :matricula_id, :internet, :computer, :laptop, :tablet, :phone, :printer
         )"
    );
    $statement->execute([
        'matricula_id' => $matriculaId,
        'internet' => !empty($resources['internet']) ? 'true' : 'false',
        'computer' => !empty($resources['computador']) ? 'true' : 'false',
        'laptop' => !empty($resources['laptop']) ? 'true' : 'false',
        'tablet' => !empty($resources['tablet']) ? 'true' : 'false',
        'phone' => !empty($resources['celular']) ? 'true' : 'false',
        'printer' => !empty($resources['impresora']) ? 'true' : 'false',
    ]);
}

function insertBilling(PDO $db, int $matriculaId, array $billing, array $student): void
{
    $name = trim((string) $billing['name']);

    if ($name === '') {
        $name = trim((string) $student['pernombres'] . ' ' . (string) $student['perapellidos']);
    }

    $name = strLimit($name, 150);
    $identification = strLimit((string) $billing['identification'], 13);
    $type = strlen($identification) === 13 ? 'RUC' : 'CEDULA';

    $statement = $db->prepare(
        "INSERT INTO matricula_facturacion (
            matid, mfcnombre, mfctipoidentificacion, mfcidentificacion, mfcdireccion, mfccorreo, mfctelefono
         ) VALUES (
            :matricula_id, :name, :type, :identification, :address, :email, :phone
         )"
    );
    $statement->execute([
        'matricula_id' => $matriculaId,
        'name' => $name,
        'type' => $type,
        'identification' => $identification,
        'address' => $billing['address'] !== '' ? strLimit($billing['address'], 250) : null,
        'email' => strLimit($billing['email'], 150),
        'phone' => $billing['phone'],
    ]);
}

function writeReport(array $plannedRows, string $path): void
{
    $directory = dirname($path);

    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException("No se pudo crear el directorio del reporte: {$directory}");
    }

    $handle = fopen($path, 'wb');

    if ($handle === false) {
        throw new RuntimeException("No se pudo escribir el reporte: {$path}");
    }

    fputcsv($handle, [
        'fila_excel',
        'estado',
        'motivo',
        'advertencias',
        'cedula_original_estudiante',
        'cedula_final_estudiante',
        'nombres_estudiante',
        'apellidos_estudiante',
        'nivel_excel',
        'grado_mapeado',
        'grupo_sanguineo',
    ]);

    foreach ($plannedRows as $row) {
        fputcsv($handle, [
            $row['excel_row'],
            $row['status'],
            $row['reason'],
            implode('|', $row['warnings']),
            $row['student']['original_cedula'],
            $row['student']['percedula'],
            $row['student']['pernombres'],
            $row['student']['perapellidos'],
            $row['course']['excel_level'],
            $row['course']['grade_name'],
            $row['student']['blood_group'],
        ]);
    }

    fclose($handle);
}

function printSummary(array $summary, bool $commit, string $excelPath, string $reportPath): void
{
    echo ($commit ? "IMPORTACION REAL\n" : "SIMULACION\n");
    echo "Archivo: {$excelPath}\n";
    echo "Filas reales: {$summary['real_rows']}\n";
    echo "Importables: {$summary['importable']}\n";
    echo "Omitidas: {$summary['omitted']}\n";
    echo "Insertadas: {$summary['inserted']}\n";
    echo "Ya matriculadas/omitidas: {$summary['skipped_existing']}\n";
    echo "Cedulas artificiales estudiante: {$summary['artificial_student_ids']}\n";
    echo "Duplicados omitidos: {$summary['duplicate_rows']}\n";
    echo "Niveles no mapeados: {$summary['unmapped_levels']}\n";
    echo "Grupos sanguineos no importados: {$summary['blood_group_not_imported']}\n";
    echo "Representantes sin cedula no insertados: {$summary['representative_without_id']}\n";
    echo "Reporte: {$reportPath}\n";
}
