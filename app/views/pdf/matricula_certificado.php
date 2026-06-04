<?php

declare(strict_types=1);

$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$institution = is_array($institution ?? null) ? $institution : [];
$matricula = is_array($matricula ?? null) ? $matricula : [];
$secretary = is_array($secretary ?? null) ? $secretary : [];

$studentName = trim((string) (($matricula['perapellidos'] ?? '') . ' ' . ($matricula['pernombres'] ?? '')));
$matriculationNumber = max(1, (int) ($matricula['numero_matricula'] ?? 1));
$folioNumber = max(1, (int) ($matricula['folio'] ?? (int) ceil($matriculationNumber / 3)));
$matriculationDate = trim((string) ($matricula['matfecha'] ?? ''));
$dateDisplay = $matriculationDate !== '' ? date('Y/m/d', strtotime($matriculationDate)) : date('Y/m/d');
$level = trim((string) ($matricula['nednombre'] ?? ''));
$grade = trim((string) ($matricula['granombre'] ?? ''));
$parallel = trim((string) ($matricula['prlnombre'] ?? ''));
$institutionName = trim((string) (($institutionName ?? '') !== '' ? $institutionName : (($institution['insrazonsocial'] ?? '') !== '' ? $institution['insrazonsocial'] : ($institution['insnombre'] ?? $appName ?? 'SGEap'))));
$secretaryName = trim((string) (($secretary['pernombres'] ?? '') . ' ' . ($secretary['perapellidos'] ?? '')));
$mineducLogo = BASE_PATH . '/public/assets/img/reportes/certificado-mineduc.jpg';
$ecuadorShield = BASE_PATH . '/public/assets/img/reportes/certificado-escudo-ecuador.jpg';
$hasMineducLogo = is_file($mineducLogo);
$hasEcuadorShield = is_file($ecuadorShield);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 54px 62px; }
        body {
            color: #111827;
            font-family: DejaVu Sans, sans-serif;
            font-size: 13px;
            line-height: 1.45;
        }
        .certificate {
            min-height: 620px;
            padding-top: 10px;
        }
        .certificate-header {
            width: 100%;
            border-collapse: collapse;
            margin: -12px 0 28px;
        }
        .certificate-header td {
            border: 0;
            padding: 0;
            vertical-align: middle;
        }
        .mineduc-logo {
            width: 210px;
            height: auto;
        }
        .ecuador-shield {
            width: 100%;
            max-width: 74px;
            height: auto;
        }
        .title {
            font-size: 19px;
            font-weight: 700;
            margin: 0 0 12px;
            text-align: center;
            text-transform: uppercase;
        }
        .institution {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 2px;
            text-align: center;
            text-transform: uppercase;
        }
        .location {
            font-size: 12px;
            font-weight: 700;
            margin: 0 0 28px;
            text-align: center;
            text-transform: uppercase;
        }
        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
            table-layout: fixed;
        }
        .meta td {
            padding: 3px 0;
            vertical-align: top;
        }
        .meta .label {
            font-weight: 700;
            white-space: nowrap;
        }
        .meta .value {
            border-bottom: 1px solid transparent;
            padding-left: 14px;
        }
        .meta .gutter {
            width: 28px;
        }
        .body-text {
            margin-top: 12px;
        }
        .student-name {
            font-size: 15px;
            font-weight: 700;
            margin: 12px 0;
            text-align: center;
            text-transform: uppercase;
        }
        .signature {
            margin-top: 92px;
            text-align: center;
        }
        .signature-line {
            display: inline-block;
            width: 180px;
            border-top: 1px solid #111827;
            padding-top: 6px;
        }
        .secretary {
            font-weight: 700;
            margin-top: 2px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <section class="certificate">
        <?php if ($hasMineducLogo || $hasEcuadorShield): ?>
            <table class="certificate-header">
                <tr>
                    <td style="width: 70%;">
                        <?php if ($hasMineducLogo): ?>
                            <img class="mineduc-logo" src="<?= $h($mineducLogo); ?>" alt="">
                        <?php endif; ?>
                    </td>
                    <td style="width: 30%; text-align: right;">
                        <?php if ($hasEcuadorShield): ?>
                            <img class="ecuador-shield" src="<?= $h($ecuadorShield); ?>" alt="">
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        <?php endif; ?>
        <h1 class="title">Certificado de Matrícula</h1>
        <p class="institution">Institución: <?= $h($institutionName); ?></p>
        <p class="location">Quito - Ecuador</p>

        <table class="meta">
            <colgroup>
                <col style="width: 30%;">
                <col style="width: 41%;">
                <col style="width: 1%;">
                <col style="width: 15%;">
                <col style="width: 13%;">
            </colgroup>
            <tr>
                <td class="label">Año Lectivo:</td>
                <td class="value"><?= $h($matricula['pledescripcion'] ?? ''); ?></td>
                <td class="gutter"></td>
                <td class="label">No. Matrícula:</td>
                <td class="value"><?= $h(str_pad((string) $matriculationNumber, 3, '0', STR_PAD_LEFT)); ?></td>
            </tr>
            <tr>
                <td class="label">Nivel de Educación:</td>
                <td class="value" colspan="2"><?= $h(trim($level . ' - Grado ' . $grade)); ?></td>
                <td class="label">Paralelo:</td>
                <td class="value"><?= $h($parallel !== '' ? 'PARALELO ' . $parallel : ''); ?></td>
            </tr>
            <tr>
                <td class="label">Fecha:</td>
                <td class="value"><?= $h($dateDisplay); ?></td>
                <td class="gutter"></td>
                <td class="label">Folio:</td>
                <td class="value"><?= $h(str_pad((string) $folioNumber, 3, '0', STR_PAD_LEFT)); ?></td>
            </tr>
        </table>

        <p class="body-text">Quien suscribe secretaria certifica que el/la estudiante:</p>
        <p class="student-name"><?= $h($studentName); ?></p>
        <p>
            Previo el cumplimiento de los requisitos legales, se matriculó en el grado indicado
            según consta en los registros de matrículas que reposan en esta Institución.
        </p>

        <div class="signature">
            <div class="signature-line"><?= $h($secretaryName !== '' ? $secretaryName : ''); ?></div>
            <p class="secretary">Secretaria</p>
        </div>
    </section>
</body>
</html>
