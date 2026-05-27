<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AccountingConfigurationModel;
use App\Models\AccountingModel;
use App\Models\CourseModel;
use App\Models\GradeModel;

class AccountingController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) ($period['pleid'] ?? 0) : 0;
        $accountingModel = new AccountingModel();

        if ((string) ($_GET['chart'] ?? '') === 'month-payment-status') {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'rows' => $accountingModel->monthPaymentStatusChart($periodId, (string) ($_GET['month'] ?? '')),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        $this->view('contabilidad.index', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Gestion Contable',
            'currentModule' => 'contabilidad',
            'currentSection' => 'contabilidad_dashboard',
            'user' => $user,
            'currentPeriod' => $period,
            'summary' => $accountingModel->dashboardSummary($periodId),
            'charts' => $accountingModel->dashboardCharts($periodId),
            'pendingReceipts' => $accountingModel->recentPendingReceipts($periodId),
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function exportCsv(): void
    {
        $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) ($period['pleid'] ?? 0) : 0;
        $type = (string) ($_GET['tipo'] ?? '');
        $model = new AccountingModel();

        $exports = [
            'obligaciones-pendientes' => [
                'filename' => 'obligaciones_pendientes',
                'headers' => ['Cedula', 'Apellidos', 'Nombres', 'Nivel', 'Grado', 'Paralelo', 'Tipo', 'Obligacion', 'Vencimiento', 'Valor', 'Pagado', 'Saldo', 'Estado'],
                'rows' => $model->exportPendingObligations($periodId),
                'mapper' => static fn (array $row): array => [
                    $row['percedula'] ?? '',
                    $row['perapellidos'] ?? '',
                    $row['pernombres'] ?? '',
                    $row['nednombre'] ?? '',
                    $row['granombre'] ?? '',
                    $row['prlnombre'] ?? '',
                    $row['cobtipo'] ?? '',
                    $row['cobdescripcion'] ?? '',
                    $row['cobfecha_vencimiento'] ?? '',
                    $row['cobvalor_final'] ?? '0.00',
                    $row['cobvalor_pagado'] ?? '0.00',
                    $row['cobsaldo_pendiente'] ?? '0.00',
                    $row['cobestado'] ?? '',
                ],
            ],
            'pagos' => [
                'filename' => 'pagos_contables',
                'headers' => ['ID', 'Estado', 'Origen', 'Cedula', 'Apellidos', 'Nombres', 'Curso', 'Tipo concepto', 'Concepto', 'Reportado', 'Aprobado', 'Metodo', 'Referencia', 'Factura', 'Registro', 'Revision', 'Reverso', 'Motivo rechazo', 'Motivo reverso'],
                'rows' => $model->exportReviewedPayments($periodId),
                'mapper' => static fn (array $row): array => [
                    $row['cpagid'] ?? '',
                    $row['cpagestado'] ?? '',
                    $row['cpagorigen'] ?? '',
                    $row['percedula'] ?? '',
                    $row['perapellidos'] ?? '',
                    $row['pernombres'] ?? '',
                    trim((string) (($row['granombre'] ?? '') . ' ' . ($row['prlnombre'] ?? ''))),
                    $row['tipo_concepto'] ?? '',
                    $row['concepto'] ?? '',
                    $row['cpagvalor_reportado'] ?? '',
                    $row['cpagvalor_aprobado'] ?? '',
                    $row['cmpnombre'] ?? '',
                    $row['cpagreferencia'] ?? '',
                    $row['cpagdocumento_externo_numero'] ?? '',
                    $row['cpagfecha_registro'] ?? '',
                    $row['cpagfecha_revision'] ?? '',
                    $row['cpagfecha_reverso'] ?? '',
                    $row['cpagmotivo_rechazo'] ?? '',
                    $row['cpagmotivo_reverso'] ?? '',
                ],
            ],
            'rubros' => [
                'filename' => 'rubros_adicionales',
                'headers' => ['Rubro', 'Concepto', 'Valor base', 'Fecha limite base', 'Cedula', 'Apellidos', 'Nombres', 'Nivel', 'Grado', 'Paralelo', 'Valor asignado', 'Fecha limite', 'Estado', 'Referencia pago', 'Fecha pago', 'Observacion'],
                'rows' => $model->exportAdditionalItems($periodId),
                'mapper' => static fn (array $row): array => [
                    $row['crunombre'] ?? '',
                    $row['cconombre'] ?? '',
                    $row['cruvalor'] ?? '0.00',
                    $row['crufecha_limite'] ?? '',
                    $row['percedula'] ?? '',
                    $row['perapellidos'] ?? '',
                    $row['pernombres'] ?? '',
                    $row['nednombre'] ?? '',
                    $row['granombre'] ?? '',
                    $row['prlnombre'] ?? '',
                    $row['crevalor'] ?? '0.00',
                    $row['crefecha_limite'] ?? '',
                    $row['creestado'] ?? '',
                    $row['cpagreferencia'] ?? '',
                    $row['cpagfecha_revision'] ?? '',
                    $row['creobservacion_interna'] ?? '',
                ],
            ],
        ];

        if (!isset($exports[$type])) {
            sessionFlash('error', 'Seleccione un reporte valido para exportar.');
            $this->redirect('/contabilidad');
        }

        $periodLabel = $this->csvSafeFilename((string) ($period['pledescripcion'] ?? 'periodo'));
        $config = $exports[$type];
        $filename = $config['filename'] . '_' . $periodLabel . '_' . date('Ymd_His') . '.csv';

        $this->streamCsv($filename, $config['headers'], $config['rows'], $config['mapper']);
    }

    public function obligations(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) ($period['pleid'] ?? 0) : 0;
        $accountingModel = new AccountingModel();
        $filters = $this->obligationFilters();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = max(10, min(100, (int) ($_GET['limit'] ?? 25)));
        $pagination = $accountingModel->obligationStudentsPage($periodId, $filters, $limit, $page);
        $students = $pagination['rows'];
        $reference = $accountingModel->obligationReference($periodId, $filters);

        if ((string) ($_GET['ajax'] ?? '') === '1') {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'rows' => $students,
                'reference' => $reference,
                'count' => count($students),
                'total' => $pagination['total'],
                'page' => $pagination['page'],
                'limit' => $pagination['limit'],
                'pages' => $pagination['pages'],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        $gradeModel = new GradeModel();
        $courseModel = new CourseModel();

        $this->view('contabilidad.obligaciones', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Obligaciones',
            'currentModule' => 'contabilidad',
            'currentSection' => 'contabilidad_obligaciones',
            'user' => $user,
            'currentPeriod' => $period,
            'levels' => $gradeModel->allLevels(),
            'courses' => $periodId > 0 ? $courseModel->allByPeriod($periodId) : [],
            'filters' => $filters,
            'students' => $students,
            'pagination' => $pagination,
            'reference' => $reference,
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function generateObligations(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) ($period['pleid'] ?? 0) : 0;
        $matriculationId = (int) ($_POST['matid'] ?? 0);
        $scholarshipPercent = (float) ($_POST['beca_porcentaje'] ?? 0);
        $scholarshipAmount = (float) ($_POST['beca_valor'] ?? 0);

        if ($periodId <= 0 || $matriculationId <= 0) {
            sessionFlash('error', 'Seleccione un estudiante valido para generar obligaciones.');
            $this->redirect('/contabilidad/obligaciones');
        }

        try {
            $created = (new AccountingModel())->generateStudentObligations($matriculationId, $periodId, (int) ($user['usuid'] ?? 0), $scholarshipPercent, $scholarshipAmount);
            $total = (int) $created['matricula'] + (int) $created['pensiones'];
            sessionFlash('success', $total > 0
                ? 'Obligaciones asignadas correctamente.'
                : 'El estudiante ya tenia obligaciones asignadas; se actualizaron los valores de pension.');
        } catch (\Throwable $exception) {
            sessionFlash('error', $exception->getMessage());
        }

        $this->redirect('/contabilidad/obligaciones');
    }

    public function obligationDetail(): void
    {
        $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) ($period['pleid'] ?? 0) : 0;
        $matriculationId = (int) ($_GET['matid'] ?? 0);

        header('Content-Type: application/json; charset=UTF-8');

        if ($periodId <= 0 || $matriculationId <= 0) {
            echo json_encode(['rows' => []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        echo json_encode([
            'rows' => (new AccountingModel())->studentObligations($matriculationId, $periodId),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function representativePayments(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) ($period['pleid'] ?? 0) : 0;
        $accountingModel = new AccountingModel();
        $moduleSettings = (new AccountingConfigurationModel())->moduleSettingsForPeriod($periodId);
        $canViewAdditionalItems = $this->hasPermission('contabilidad.representante.rubros.ver', $user)
            && !empty($moduleSettings['representante_rubros_visible']);

        $this->view('contabilidad.representante', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Mis pagos',
            'currentModule' => 'inicio',
            'currentSection' => 'representante_pagos',
            'user' => $user,
            'currentPeriod' => $period,
            'obligations' => $accountingModel->representativeObligations((int) ($user['perid'] ?? 0), $periodId),
            'additionalItems' => $canViewAdditionalItems
                ? $accountingModel->representativeAdditionalItems((int) ($user['perid'] ?? 0), $periodId)
                : [],
            'additionalItemsVisible' => $canViewAdditionalItems,
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function storeRepresentativeReceipt(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) ($period['pleid'] ?? 0) : 0;
        $obligationId = (int) ($_POST['cobid'] ?? 0);

        try {
            $studentName = trim((string) ($_POST['estudiante'] ?? ''));
            $fileData = storeAccountingReceiptFile($_FILES['comprobante'] ?? [], $studentName);
            (new AccountingModel())->registerRepresentativeReceipt(
                (int) ($user['perid'] ?? 0),
                (int) ($user['usuid'] ?? 0),
                $periodId,
                $obligationId,
                $fileData
            );
            sessionFlash('success', 'Comprobante registrado correctamente. Queda pendiente de revision por secretaria.');
        } catch (\Throwable $exception) {
            sessionFlash('error', $exception->getMessage());
        }

        $this->redirect('/representante/contabilidad');
    }

    public function receipts(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) ($period['pleid'] ?? 0) : 0;
        $status = (string) ($_GET['estado'] ?? 'EN_REVISION');
        $filters = $this->receiptFilters();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = max(10, min(100, (int) ($_GET['limit'] ?? 25)));
        $accountingModel = new AccountingModel();
        $pagination = $accountingModel->receiptsForReviewPage($periodId, $status, $filters, $limit, $page);

        if ((string) ($_GET['ajax'] ?? '') === '1') {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'rows' => $pagination['rows'],
                'total' => $pagination['total'],
                'page' => $pagination['page'],
                'limit' => $pagination['limit'],
                'pages' => $pagination['pages'],
                'status' => $status,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        $this->view('contabilidad.comprobantes', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Comprobantes',
            'currentModule' => 'contabilidad',
            'currentSection' => 'contabilidad_comprobantes',
            'user' => $user,
            'currentPeriod' => $period,
            'status' => $status,
            'filters' => $filters,
            'courses' => $periodId > 0 ? (new CourseModel())->allByPeriod($periodId) : [],
            'receipts' => $pagination['rows'],
            'pagination' => $pagination,
            'canReversePayments' => $this->hasPermission('contabilidad.pagos.reversar', $user),
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function approveReceipt(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) ($period['pleid'] ?? 0) : 0;

        try {
            (new AccountingModel())->approveReceipt(
                (int) ($_POST['cpagid'] ?? 0),
                $periodId,
                (int) ($user['usuid'] ?? 0),
                (float) ($_POST['valor_aprobado'] ?? 0),
                (string) ($_POST['observacion'] ?? ''),
                (string) ($_POST['factura'] ?? ''),
                !empty($_POST['confirmar_duplicado'])
            );
            sessionFlash('success', 'Comprobante aprobado y aplicado correctamente.');
        } catch (\Throwable $exception) {
            sessionFlash('error', $exception->getMessage());
        }

        $this->redirect('/contabilidad/comprobantes');
    }

    public function rejectReceipt(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) ($period['pleid'] ?? 0) : 0;

        try {
            (new AccountingModel())->rejectReceipt(
                (int) ($_POST['cpagid'] ?? 0),
                $periodId,
                (int) ($user['usuid'] ?? 0),
                (string) ($_POST['motivo'] ?? '')
            );
            sessionFlash('success', 'Comprobante rechazado. El representante podra registrar nuevamente el pago.');
        } catch (\Throwable $exception) {
            sessionFlash('error', $exception->getMessage());
        }

        $this->redirect('/contabilidad/comprobantes');
    }

    public function reversePayment(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) ($period['pleid'] ?? 0) : 0;

        try {
            (new AccountingModel())->reversePayment(
                (int) ($_POST['cpagid'] ?? 0),
                $periodId,
                (int) ($user['usuid'] ?? 0),
                (string) ($_POST['motivo_reverso'] ?? '')
            );
            sessionFlash('success', 'Pago reversado correctamente.');
        } catch (\Throwable $exception) {
            sessionFlash('error', $exception->getMessage());
        }

        $this->redirect('/contabilidad/comprobantes?estado=APROBADO');
    }

    public function updateObligation(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) ($period['pleid'] ?? 0) : 0;
        $obligationId = (int) ($_POST['cobid'] ?? 0);
        $finalValue = (float) ($_POST['cobvalor_final'] ?? 0);

        try {
            (new AccountingModel())->updateObligationFinalValue($obligationId, $periodId, $finalValue, (int) ($user['usuid'] ?? 0));
            sessionFlash('success', 'Obligacion actualizada correctamente.');
        } catch (\Throwable $exception) {
            sessionFlash('error', $exception->getMessage());
        }

        $this->redirect('/contabilidad/obligaciones');
    }

    public function annulObligation(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) ($period['pleid'] ?? 0) : 0;
        $obligationId = (int) ($_POST['cobid'] ?? 0);
        $reason = trim((string) ($_POST['motivo'] ?? ''));

        try {
            (new AccountingModel())->annulObligation($obligationId, $periodId, (int) ($user['usuid'] ?? 0), $reason);
            sessionFlash('success', 'Obligacion anulada correctamente.');
        } catch (\Throwable $exception) {
            sessionFlash('error', $exception->getMessage());
        }

        $this->redirect('/contabilidad/obligaciones');
    }

    public function additionalItems(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) ($period['pleid'] ?? 0) : 0;
        $accountingModel = new AccountingModel();
        $selectedItemId = (int) ($_GET['rubro'] ?? 0);
        $canCreateAdditionalItems = $this->hasPermission('contabilidad.rubros.crear', $user);
        $canEditAdditionalItems = $this->hasPermission('contabilidad.rubros.editar', $user);
        $canRegisterAdditionalItemPayments = $this->hasPermission('contabilidad.pagos.registrar', $user);
        $assignmentFilters = $this->additionalItemAssignmentFilters();
        $assignmentPage = max(1, (int) ($_GET['page'] ?? 1));
        $assignmentLimit = max(10, min(100, (int) ($_GET['limit'] ?? 25)));
        $assignmentPagination = $selectedItemId > 0
            ? $accountingModel->additionalItemAssignmentsPage($selectedItemId, $periodId, $assignmentFilters, $assignmentLimit, $assignmentPage)
            : ['rows' => [], 'total' => 0, 'page' => 1, 'limit' => $assignmentLimit, 'pages' => 1];

        $this->view('contabilidad.rubros', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Rubros adicionales',
            'currentModule' => 'contabilidad',
            'currentSection' => 'contabilidad_rubros',
            'user' => $user,
            'currentPeriod' => $period,
            'concepts' => $accountingModel->activeAdditionalItemConcepts(),
            'allConcepts' => $accountingModel->additionalItemConcepts(),
            'methods' => $accountingModel->paymentMethods(),
            'levels' => (new GradeModel())->allLevels(),
            'courses' => $periodId > 0 ? (new CourseModel())->allByPeriod($periodId) : [],
            'students' => $accountingModel->additionalItemStudents($periodId),
            'items' => $accountingModel->additionalItems($periodId),
            'selectedItemId' => $selectedItemId,
            'assignments' => $assignmentPagination['rows'],
            'assignmentFilters' => $assignmentFilters,
            'assignmentPagination' => $assignmentPagination,
            'canCreateAdditionalItems' => $canCreateAdditionalItems,
            'canEditAdditionalItems' => $canEditAdditionalItems,
            'canRegisterAdditionalItemPayments' => $canRegisterAdditionalItemPayments,
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function storeAdditionalItem(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) ($period['pleid'] ?? 0) : 0;

        try {
            $itemId = (new AccountingModel())->createAdditionalItem($periodId, (int) ($user['usuid'] ?? 0), $_POST);
            sessionFlash('success', 'Rubro adicional creado y asignado correctamente.');
            $this->redirect('/contabilidad/rubros?rubro=' . $itemId);
        } catch (\Throwable $exception) {
            sessionFlash('error', $exception->getMessage());
            $this->redirect('/contabilidad/rubros');
        }
    }

    public function closeAdditionalItem(): void
    {
        $user = $this->requireAuth();
        $period = currentAcademicPeriod();
        $periodId = $period !== null ? (int) ($period['pleid'] ?? 0) : 0;
        $itemId = (int) ($_POST['cruid'] ?? 0);
        $returnQuery = trim((string) ($_POST['return_query'] ?? ''));
        $status = strtoupper(trim((string) ($_POST['estado'] ?? '')));
        $canRegisterPayment = $this->hasPermission('contabilidad.pagos.registrar', $user);
        $canEditRubros = $this->hasPermission('contabilidad.rubros.editar', $user);

        if (($status === 'PAGADO' && !$canRegisterPayment && !$canEditRubros) || ($status !== 'PAGADO' && !$canEditRubros)) {
            $this->denyAccess();
        }

        try {
            (new AccountingModel())->closeAdditionalItemAssignment(
                (int) ($_POST['creid'] ?? 0),
                $periodId,
                (int) ($user['usuid'] ?? 0),
                $status,
                (int) ($_POST['cmpid'] ?? 0),
                (string) ($_POST['referencia'] ?? ''),
                (string) ($_POST['observacion'] ?? '')
            );
            sessionFlash('success', 'Rubro actualizado correctamente.');
        } catch (\Throwable $exception) {
            sessionFlash('error', $exception->getMessage());
        }

        $redirectQuery = $returnQuery !== '' ? $returnQuery : ($itemId > 0 ? 'rubro=' . $itemId : '');
        $this->redirect('/contabilidad/rubros' . ($redirectQuery !== '' ? '?' . $redirectQuery : ''));
    }

    public function storeAdditionalItemConcept(): void
    {
        $this->requireAuth();

        try {
            (new AccountingModel())->createAdditionalItemConcept(
                (string) ($_POST['cconombre'] ?? ''),
                (string) ($_POST['ccodescripcion'] ?? '')
            );
            sessionFlash('success', 'Concepto agregado correctamente.');
        } catch (\Throwable $exception) {
            sessionFlash('error', $exception->getMessage());
        }

        $this->redirect('/contabilidad/rubros?panel=concepts');
    }

    public function updateAdditionalItemConcept(): void
    {
        $this->requireAuth();

        try {
            (new AccountingModel())->updateAdditionalItemConcept(
                (int) ($_POST['ccoid'] ?? 0),
                (string) ($_POST['cconombre'] ?? ''),
                (string) ($_POST['ccodescripcion'] ?? ''),
                !empty($_POST['ccoestado'])
            );
            sessionFlash('success', 'Concepto actualizado correctamente.');
        } catch (\Throwable $exception) {
            sessionFlash('error', $exception->getMessage());
        }

        $this->redirect('/contabilidad/rubros?panel=concepts');
    }

    public function deleteAdditionalItemConcept(): void
    {
        $this->requireAuth();

        try {
            (new AccountingModel())->deleteAdditionalItemConcept((int) ($_POST['ccoid'] ?? 0));
            sessionFlash('success', 'Concepto eliminado o desactivado correctamente.');
        } catch (\Throwable $exception) {
            sessionFlash('error', $exception->getMessage());
        }

        $this->redirect('/contabilidad/rubros?panel=concepts');
    }

    private function obligationFilters(): array
    {
        return [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'nivel' => (int) ($_GET['nivel'] ?? 0),
            'curso' => (int) ($_GET['curso'] ?? 0),
        ];
    }

    private function receiptFilters(): array
    {
        return [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'curso' => (int) ($_GET['curso'] ?? 0),
        ];
    }

    private function additionalItemAssignmentFilters(): array
    {
        return [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'estado' => strtoupper(trim((string) ($_GET['estado'] ?? ''))),
            'curso' => (int) ($_GET['curso'] ?? 0),
        ];
    }

    private function streamCsv(string $filename, array $headers, array $rows, callable $mapper): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        if ($output === false) {
            throw new \RuntimeException('No se pudo generar el archivo CSV.');
        }

        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, $headers, ';');

        foreach ($rows as $row) {
            fputcsv($output, array_map(static fn (mixed $value): string => (string) $value, $mapper($row)), ';');
        }

        fclose($output);
        exit;
    }

    private function csvSafeFilename(string $value): string
    {
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $normalized = strtolower((string) $normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? '';
        $normalized = trim($normalized, '_');

        return $normalized !== '' ? $normalized : 'periodo';
    }
}
