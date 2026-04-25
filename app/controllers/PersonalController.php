<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\PersonalModel;
use App\Models\PersonModel;
use PDOException;

class PersonalController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth();

        $this->view('module.home', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Personal',
            'currentSection' => 'personal',
            'user' => $user,
            'moduleDescription' => 'Administra el registro, la asignacion de tipos y la consulta general del personal institucional.',
            'moduleCards' => [
                [
                    'label' => 'Registro de personal',
                    'description' => 'Registra datos de persona, datos laborales y tipos para incorporarla al personal institucional.',
                    'url' => baseUrl('personal/registro'),
                    'icon' => 'fa-user-plus',
                ],
                [
                    'label' => 'Asignacion del personal',
                    'description' => 'Asigna uno o varios tipos institucionales al personal registrado.',
                    'url' => baseUrl('personal/asignacion'),
                    'icon' => 'fa-check-square-o',
                ],
                [
                    'label' => 'Consulta de personal',
                    'description' => 'Consulta, filtra y edita la informacion del personal institucional.',
                    'url' => baseUrl('personal/consulta'),
                    'icon' => 'fa-list',
                ],
            ],
        ]);
    }

    public function create(): void
    {
        $user = $this->requireAuth();
        $personalModel = new PersonalModel();
        $personModel = new PersonModel();

        $this->view('personal.registro', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Registro de personal',
            'currentSection' => 'personal_register',
            'user' => $user,
            'staffTypes' => $personalModel->activeTypes(),
            'civilStatuses' => $personModel->allCivilStatuses(),
            'instructionLevels' => $personModel->allInstructionLevels(),
            'error' => sessionFlash('error'),
            'old' => $this->registrationFormOldData(),
        ]);
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
        $personModel = new PersonModel();

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
            'civilStatuses' => $personModel->allCivilStatuses(),
            'instructionLevels' => $personModel->allInstructionLevels(),
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
                'perfechanacimiento' => sessionFlash('old_staff_person_birth_date') ?? (string) ($staff['perfechanacimiento'] ?? ''),
                'eciid' => sessionFlash('old_staff_person_civil_status') ?? (string) ($staff['eciid'] ?? ''),
                'istid' => sessionFlash('old_staff_person_instruction') ?? (string) ($staff['istid'] ?? ''),
                'perprofesion' => sessionFlash('old_staff_person_profession') ?? (string) ($staff['perprofesion'] ?? ''),
                'perocupacion' => sessionFlash('old_staff_person_occupation') ?? (string) ($staff['perocupacion'] ?? ''),
                'perhablaingles' => sessionFlash('old_staff_person_speaks_english') ?? (!empty($staff['perhablaingles']) ? '1' : '0'),
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

    public function store(): void
    {
        $this->requireAuth();

        $personData = $this->personFormData();
        $staffData = $this->staffFormData();
        $typeIds = array_values(array_unique(array_map('intval', (array) ($_POST['type_ids'] ?? []))));
        $personalModel = new PersonalModel();
        $personModel = new PersonModel();

        if (
            $personData['percedula'] === ''
            || $personData['pernombres'] === ''
            || $personData['perapellidos'] === ''
        ) {
            $this->flashRegistrationFormData($personData, $staffData, $typeIds);
            sessionFlash('error', 'Cedula, nombres y apellidos son obligatorios.');
            $this->redirect('/personal/registro');
        }

        if ($staffData['psnfechacontratacion'] === '') {
            $this->flashRegistrationFormData($personData, $staffData, $typeIds);
            sessionFlash('error', 'La fecha de contratacion es obligatoria.');
            $this->redirect('/personal/registro');
        }

        if (
            $staffData['psnfechasalida'] !== ''
            && $staffData['psnfechasalida'] < $staffData['psnfechacontratacion']
        ) {
            $this->flashRegistrationFormData($personData, $staffData, $typeIds);
            sessionFlash('error', 'La fecha de salida no puede ser menor a la fecha de contratacion.');
            $this->redirect('/personal/registro');
        }

        $validTypeIds = $personalModel->validTypeIds($typeIds);

        if ($typeIds === []) {
            $this->flashRegistrationFormData($personData, $staffData, $typeIds);
            sessionFlash('error', 'Debe seleccionar al menos un tipo de personal.');
            $this->redirect('/personal/registro');
        }

        if (count($validTypeIds) !== count($typeIds)) {
            $this->flashRegistrationFormData($personData, $staffData, $typeIds);
            sessionFlash('error', 'Existe al menos un tipo de personal no valido en el registro.');
            $this->redirect('/personal/registro');
        }

        $existingPerson = $personModel->findByCedula($personData['percedula']);

        if ($existingPerson !== false && $personalModel->existsByPersonId((int) $existingPerson['perid'])) {
            $this->flashRegistrationFormData($personData, $staffData, $typeIds);
            sessionFlash('error', 'La persona ingresada ya se encuentra registrada como personal.');
            $this->redirect('/personal/registro');
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            if ($existingPerson === false) {
                $personId = $personModel->create($personData);
            } else {
                $personId = (int) $existingPerson['perid'];
                $personModel->updateBasic($personId, $personData);
            }

            $staffId = $personalModel->create(array_merge(
                ['perid' => $personId],
                $staffData
            ));

            $personalModel->syncStaffTypes($staffId, $typeIds);
            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $this->flashRegistrationFormData($personData, $staffData, $typeIds);
            sessionFlash('error', $this->humanizeRegistrationException($exception));
            $this->redirect('/personal/registro');
            return;
        }

        sessionFlash('success', 'Personal registrado correctamente.');
        $this->redirect('/personal/consulta');
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
            $personModel->updateBasic($personId, $personData);
            $personalModel->update($staffId, $staffData);
            $db->commit();
        } catch (\Throwable $exception) {
            $db->rollBack();
            $this->flashPersonAndStaffFormData($personData, $staffData);
            sessionFlash('error', $this->humanizeUpdateException($exception));
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
            'perfechanacimiento' => trim((string) ($_POST['perfechanacimiento'] ?? '')),
            'eciid' => (int) ($_POST['eciid'] ?? 0),
            'istid' => (int) ($_POST['istid'] ?? 0),
            'perprofesion' => trim((string) ($_POST['perprofesion'] ?? '')),
            'perocupacion' => trim((string) ($_POST['perocupacion'] ?? '')),
            'perhablaingles' => isset($_POST['perhablaingles']),
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
        sessionFlash('old_staff_person_birth_date', (string) ($personData['perfechanacimiento'] ?? ''));
        sessionFlash('old_staff_person_civil_status', (string) ($personData['eciid'] ?? ''));
        sessionFlash('old_staff_person_instruction', (string) ($personData['istid'] ?? ''));
        sessionFlash('old_staff_person_profession', (string) ($personData['perprofesion'] ?? ''));
        sessionFlash('old_staff_person_occupation', (string) ($personData['perocupacion'] ?? ''));
        sessionFlash('old_staff_person_speaks_english', !empty($personData['perhablaingles']) ? '1' : '0');
        sessionFlash('old_staff_hire_date', (string) ($staffData['psnfechacontratacion'] ?? ''));
        sessionFlash('old_staff_exit_date', (string) ($staffData['psnfechasalida'] ?? ''));
        sessionFlash('old_staff_status', !empty($staffData['psnestado']) ? '1' : '0');
        sessionFlash('old_staff_note', (string) ($staffData['psnobservacion'] ?? ''));
    }

    private function flashRegistrationFormData(array $personData, array $staffData, array $typeIds): void
    {
        $this->flashPersonAndStaffFormData($personData, $staffData);
        sessionFlash('old_staff_type_ids', implode(',', array_map('strval', $typeIds)));
    }

    private function registrationFormOldData(): array
    {
        $selectedTypes = trim((string) (sessionFlash('old_staff_type_ids') ?? ''));

        return [
            'percedula' => sessionFlash('old_staff_person_cedula') ?? '',
            'pernombres' => sessionFlash('old_staff_person_names') ?? '',
            'perapellidos' => sessionFlash('old_staff_person_lastnames') ?? '',
            'pertelefono1' => sessionFlash('old_staff_person_phone1') ?? '',
            'pertelefono2' => sessionFlash('old_staff_person_phone2') ?? '',
            'percorreo' => sessionFlash('old_staff_person_email') ?? '',
            'persexo' => sessionFlash('old_staff_person_gender') ?? '',
            'perfechanacimiento' => sessionFlash('old_staff_person_birth_date') ?? '',
            'eciid' => sessionFlash('old_staff_person_civil_status') ?? '',
            'istid' => sessionFlash('old_staff_person_instruction') ?? '',
            'perprofesion' => sessionFlash('old_staff_person_profession') ?? '',
            'perocupacion' => sessionFlash('old_staff_person_occupation') ?? '',
            'perhablaingles' => sessionFlash('old_staff_person_speaks_english') ?? '0',
            'psnfechacontratacion' => sessionFlash('old_staff_hire_date') ?? '',
            'psnfechasalida' => sessionFlash('old_staff_exit_date') ?? '',
            'psnestado' => sessionFlash('old_staff_status') ?? '1',
            'psnobservacion' => sessionFlash('old_staff_note') ?? '',
            'type_ids' => $selectedTypes !== ''
                ? array_map('intval', array_filter(explode(',', $selectedTypes), static fn (string $value): bool => $value !== ''))
                : [],
        ];
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

    private function humanizeRegistrationException(\Throwable $exception): string
    {
        if ($exception instanceof PDOException) {
            $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());

            if ($sqlState === '23505') {
                return 'No se pudo registrar el personal porque la persona o su asignacion ya existen en el sistema.';
            }

            if ($sqlState === '23503') {
                return 'No se pudo registrar el personal porque uno de los datos relacionados no existe o ya no esta disponible.';
            }

            if ($sqlState === '23514') {
                return 'No se pudo registrar el personal porque una validacion de fechas o estado fue rechazada por la base de datos.';
            }
        }

        if ($exception instanceof \RuntimeException && trim($exception->getMessage()) !== '') {
            return $exception->getMessage();
        }

        $message = 'No se pudo registrar el personal institucional.';

        if ((string) env('APP_DEBUG', 'false') === 'true' && trim($exception->getMessage()) !== '') {
            $message .= ' Detalle: ' . $exception->getMessage();
        }

        return $message;
    }

    private function humanizeUpdateException(\Throwable $exception): string
    {
        if ($exception instanceof PDOException) {
            $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());

            if ($sqlState === '23505') {
                return 'No se pudo actualizar el personal porque la cedula ingresada ya pertenece a otro registro.';
            }

            if ($sqlState === '23514') {
                return 'No se pudo actualizar el personal porque una validacion de fechas o estado fue rechazada por la base de datos.';
            }

            if ($sqlState === '23503') {
                return 'No se pudo actualizar el personal porque uno de los datos relacionados no existe o ya no esta disponible.';
            }
        }

        $message = 'No se pudo actualizar la informacion del personal.';

        if ((string) env('APP_DEBUG', 'false') === 'true' && trim($exception->getMessage()) !== '') {
            $message .= ' Detalle: ' . $exception->getMessage();
        }

        return $message;
    }
}
