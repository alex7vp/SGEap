<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\PersonalModel;

class PersonalController extends Controller
{
    public function index(): void
    {
        $this->redirect('/personal/asignacion');
    }

    public function assignment(): void
    {
        $user = $this->requireAuth();
        $personalModel = new PersonalModel();

        $this->view('personal.asignacion', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Asignacion de personal',
            'currentSection' => 'personal_assignment',
            'user' => $user,
            'staffAssignments' => $personalModel->allDetailed(),
            'staffTypes' => $personalModel->activeTypes(),
            'assignedTypes' => $personalModel->assignedTypeIdsByStaff(),
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
            'staffTypeFeedback' => $this->staffTypeFeedback(),
        ]);
    }

    public function listing(): void
    {
        $user = $this->requireAuth();
        $personalModel = new PersonalModel();
        $selectedType = trim((string) ($_GET['tipo'] ?? ''));

        $this->view('personal.consulta', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => $selectedType !== '' ? 'Consulta de personal | ' . $selectedType : 'Consulta de personal',
            'currentSection' => 'personal_listing',
            'user' => $user,
            'staffMembers' => $personalModel->allWithPersonAndTypes($selectedType !== '' ? $selectedType : null),
            'staffTypes' => $personalModel->activeTypes(),
            'selectedType' => $selectedType,
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function searchListing(): void
    {
        $this->requireAuth();

        $selectedType = trim((string) ($_GET['tipo'] ?? ''));
        $personalModel = new PersonalModel();
        $staffMembers = $personalModel->allWithPersonAndTypes($selectedType !== '' ? $selectedType : null);

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'html' => $this->renderListingRows($staffMembers),
            'isEmpty' => empty($staffMembers),
            'emptyHtml' => '<div class="empty-state">No existen registros de personal para el filtro seleccionado.</div>',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function searchStaffTypes(): void
    {
        $this->requireAuth();

        $term = trim($_GET['q'] ?? '');
        $personalModel = new PersonalModel();
        $staffAssignments = $personalModel->allDetailed($term);
        $staffTypes = $personalModel->activeTypes();
        $assignedTypes = $personalModel->assignedTypeIdsByStaff();

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'html' => $this->renderStaffTypeRows($staffAssignments, $staffTypes, $assignedTypes),
            'isEmpty' => empty($staffAssignments),
            'emptyHtml' => '<div class="empty-state">No se encontro personal con ese filtro.</div>',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function updateStaffTypes(): void
    {
        $this->requireAuth();

        $staffId = (int) ($_POST['staff_id'] ?? 0);
        $typeIds = array_map('intval', (array) ($_POST['type_ids'] ?? []));
        $personalModel = new PersonalModel();
        $anchor = 'staff-' . $staffId;

        if ($staffId <= 0) {
            $this->flashStaffTypeFeedback('error', $staffId, 'El personal seleccionado no es valido.');
            $this->redirect('/personal/asignacion#' . $anchor);
        }

        try {
            $personalModel->syncStaffTypes($staffId, $typeIds);
        } catch (\RuntimeException $exception) {
            $this->flashStaffTypeFeedback('error', $staffId, $exception->getMessage());
            $this->redirect('/personal/asignacion#' . $anchor);
            return;
        }

        $this->flashStaffTypeFeedback('success', $staffId, 'Tipos de personal actualizados correctamente.');
        $this->redirect('/personal/asignacion#' . $anchor);
    }

    private function renderStaffTypeRows(array $staffAssignments, array $staffTypes, array $assignedTypes): string
    {
        ob_start();
        $staffMembers = $staffAssignments;
        require BASE_PATH . '/app/views/personal/_staff_type_rows.php';
        return (string) ob_get_clean();
    }

    private function renderListingRows(array $staffMembers): string
    {
        ob_start();
        require BASE_PATH . '/app/views/personal/_listing_rows.php';
        return (string) ob_get_clean();
    }

    private function flashStaffTypeFeedback(string $type, int $staffId, string $message): void
    {
        sessionFlash('staff_type_feedback_type', $type);
        sessionFlash('staff_type_feedback_staff', (string) $staffId);
        sessionFlash('staff_type_feedback_message', $message);
    }

    private function staffTypeFeedback(): ?array
    {
        $type = sessionFlash('staff_type_feedback_type');
        $staffId = sessionFlash('staff_type_feedback_staff');
        $message = sessionFlash('staff_type_feedback_message');

        if ($type === null || $staffId === null || $message === null) {
            return null;
        }

        return [
            'type' => $type,
            'staff_id' => (int) $staffId,
            'message' => $message,
        ];
    }
}
