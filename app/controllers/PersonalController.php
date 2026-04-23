<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\PersonalModel;
use App\Models\PersonModel;

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

    public function edit(): void
    {
        $user = $this->requireAuth();
        $staffId = (int) ($_GET['id'] ?? 0);
        $personalModel = new PersonalModel();
        $staff = $personalModel->findDetailed($staffId);

        if ($staff === false) {
            sessionFlash('error', 'El personal solicitado no existe.');
            $this->redirect('/personal/consulta');
        }

        $this->view('personal.editar', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Editar personal',
            'currentSection' => 'personal_listing',
            'user' => $user,
            'staff' => $staff,
            'error' => sessionFlash('error'),
            'old' => [
                'psnid' => (string) $staff['psnid'],
                'perid' => (string) $staff['perid'],
                'percedula' => sessionFlash('old_staff_person_cedula') ?? (string) ($staff['percedula'] ?? ''),
                'pernombres' => sessionFlash('old_staff_person_names') ?? (string) ($staff['pernombres'] ?? ''),
                'perapellidos' => sessionFlash('old_staff_person_lastnames') ?? (string) ($staff['perapellidos'] ?? ''),
                'pertelefono1' => sessionFlash('old_staff_person_phone1') ?? (string) ($staff['pertelefono1'] ?? ''),
                'pertelefono2' => sessionFlash('old_staff_person_phone2') ?? (string) ($staff['pertelefono2'] ?? ''),
                'percorreo' => sessionFlash('old_staff_person_email') ?? (string) ($staff['percorreo'] ?? ''),
                'persexo' => sessionFlash('old_staff_person_gender') ?? (string) ($staff['persexo'] ?? ''),
                'psnfechacontratacion' => sessionFlash('old_staff_hire_date') ?? (string) ($staff['psnfechacontratacion'] ?? ''),
                'psnfechasalida' => sessionFlash('old_staff_exit_date') ?? (string) ($staff['psnfechasalida'] ?? ''),
                'psnestado' => sessionFlash('old_staff_status') ?? (!empty($staff['psnestado']) ? '1' : '0'),
                'psnobservacion' => sessionFlash('old_staff_note') ?? (string) ($staff['psnobservacion'] ?? ''),
            ],
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

    public function update(): void
    {
        $this->requireAuth();

        $staffId = (int) ($_POST['psnid'] ?? 0);
        $personId = (int) ($_POST['perid'] ?? 0);
        $personData = $this->personFormData();
        $staffData = $this->staffFormData();
        $personalModel = new PersonalModel();
        $personModel = new PersonModel();

        if ($staffId <= 0 || $personId <= 0) {
            sessionFlash('error', 'El personal a actualizar no es valido.');
            $this->redirect('/personal/consulta');
        }

        if (
            $personData['percedula'] === ''
            || $personData['pernombres'] === ''
            || $personData['perapellidos'] === ''
        ) {
            $this->flashPersonAndStaffFormData($personData, $staffData);
            sessionFlash('error', 'Cedula, nombres y apellidos son obligatorios.');
            $this->redirect('/personal/editar?id=' . $staffId);
        }

        if ($staffData['psnfechacontratacion'] === '') {
            $this->flashPersonAndStaffFormData($personData, $staffData);
            sessionFlash('error', 'La fecha de contratacion es obligatoria.');
            $this->redirect('/personal/editar?id=' . $staffId);
        }

        if (
            $staffData['psnfechasalida'] !== ''
            && $staffData['psnfechasalida'] < $staffData['psnfechacontratacion']
        ) {
            $this->flashPersonAndStaffFormData($personData, $staffData);
            sessionFlash('error', 'La fecha de salida no puede ser menor a la fecha de contratacion.');
            $this->redirect('/personal/editar?id=' . $staffId);
        }

        $staff = $personalModel->findDetailed($staffId);

        if ($staff === false || (int) ($staff['perid'] ?? 0) !== $personId) {
            sessionFlash('error', 'El personal solicitado no existe.');
            $this->redirect('/personal/consulta');
        }

        if ($personModel->existsByCedulaExceptId($personData['percedula'], $personId)) {
            $this->flashPersonAndStaffFormData($personData, $staffData);
            sessionFlash('error', 'La cedula ya esta registrada en otra persona.');
            $this->redirect('/personal/editar?id=' . $staffId);
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $personModel->update($personId, $personData);
            $personalModel->update($staffId, $staffData);
            $db->commit();
        } catch (\Throwable $exception) {
            $db->rollBack();
            $this->flashPersonAndStaffFormData($personData, $staffData);
            sessionFlash('error', 'No se pudo actualizar la informacion del personal.');
            $this->redirect('/personal/editar?id=' . $staffId);
            return;
        }

        sessionFlash('success', 'Informacion de personal actualizada correctamente.');
        $this->redirect('/personal/consulta');
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

    private function staffFormData(): array
    {
        return [
            'psnfechacontratacion' => trim((string) ($_POST['psnfechacontratacion'] ?? '')),
            'psnfechasalida' => trim((string) ($_POST['psnfechasalida'] ?? '')),
            'psnestado' => ($_POST['psnestado'] ?? '1') === '1',
            'psnobservacion' => trim((string) ($_POST['psnobservacion'] ?? '')),
        ];
    }

    private function personFormData(): array
    {
        return [
            'percedula' => trim((string) ($_POST['percedula'] ?? '')),
            'pernombres' => trim((string) ($_POST['pernombres'] ?? '')),
            'perapellidos' => trim((string) ($_POST['perapellidos'] ?? '')),
            'pertelefono1' => trim((string) ($_POST['pertelefono1'] ?? '')),
            'pertelefono2' => trim((string) ($_POST['pertelefono2'] ?? '')),
            'percorreo' => trim((string) ($_POST['percorreo'] ?? '')),
            'persexo' => trim((string) ($_POST['persexo'] ?? '')),
        ];
    }

    private function flashPersonAndStaffFormData(array $personData, array $staffData): void
    {
        sessionFlash('old_staff_person_cedula', (string) ($personData['percedula'] ?? ''));
        sessionFlash('old_staff_person_names', (string) ($personData['pernombres'] ?? ''));
        sessionFlash('old_staff_person_lastnames', (string) ($personData['perapellidos'] ?? ''));
        sessionFlash('old_staff_person_phone1', (string) ($personData['pertelefono1'] ?? ''));
        sessionFlash('old_staff_person_phone2', (string) ($personData['pertelefono2'] ?? ''));
        sessionFlash('old_staff_person_email', (string) ($personData['percorreo'] ?? ''));
        sessionFlash('old_staff_person_gender', (string) ($personData['persexo'] ?? ''));
        sessionFlash('old_staff_hire_date', (string) ($staffData['psnfechacontratacion'] ?? ''));
        sessionFlash('old_staff_exit_date', (string) ($staffData['psnfechasalida'] ?? ''));
        sessionFlash('old_staff_status', !empty($staffData['psnestado']) ? '1' : '0');
        sessionFlash('old_staff_note', (string) ($staffData['psnobservacion'] ?? ''));
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
