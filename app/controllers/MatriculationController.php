<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\MatriculationConfigurationModel;
use App\Models\MatriculationModel;
use App\Models\PersonModel;

class MatriculationController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth();
        $matriculationModel = new MatriculationModel();
        $matriculationConfigurationModel = new MatriculationConfigurationModel();
        $activePanel = $this->activePanel();
        $viewedPeriod = currentAcademicPeriod();
        $enabledMatriculationPeriod = $matriculationConfigurationModel->findEnabledPeriod();
        $newMatriculaPeriod = $enabledMatriculationPeriod !== false ? $enabledMatriculationPeriod : $viewedPeriod;
        $matriculationConfiguration = is_array($newMatriculaPeriod)
            ? $matriculationConfigurationModel->findByPeriodId((int) $newMatriculaPeriod['pleid'])
            : false;
        $canCreateMatricula = $enabledMatriculationPeriod !== false;
        $newMatriculaLabel = 'Nueva matricula';

        if ($canCreateMatricula && is_array($newMatriculaPeriod)) {
            $newMatriculaLabel .= ' | ' . (string) $newMatriculaPeriod['pledescripcion'];
        }

        if (!$canCreateMatricula && $activePanel === 'nueva') {
            $activePanel = '';
        }

        $this->view('matriculas.index', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Matriculas',
            'currentSection' => 'matriculas',
            'user' => $user,
            'activePanel' => $activePanel,
            'currentPeriod' => $viewedPeriod,
            'newMatriculaPeriod' => $newMatriculaPeriod,
            'matriculationConfiguration' => $matriculationConfiguration,
            'canCreateMatricula' => $canCreateMatricula,
            'newMatriculaLabel' => $newMatriculaLabel,
            'courses' => is_array($newMatriculaPeriod) ? $matriculationModel->allCoursesByPeriod((int) $newMatriculaPeriod['pleid']) : [],
            'relationships' => $matriculationModel->allRelationships(),
            'civilStatuses' => $matriculationModel->allCivilStatuses(),
            'instructionLevels' => $matriculationModel->allInstructionLevels(),
            'enrollmentStatuses' => $matriculationModel->allEnrollmentStatuses(),
            'matriculas' => $viewedPeriod !== null ? $matriculationModel->allByPeriod((int) $viewedPeriod['pleid']) : [],
            'success' => null,
            'error' => null,
            'matriculaFormFeedback' => $this->matriculaFormFeedback(),
            'matriculaListFeedback' => $this->matriculaListFeedback(),
            'old' => $this->oldFormData(),
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        $configurationModel = new MatriculationConfigurationModel();
        $period = $configurationModel->findEnabledPeriod();

        if ($period === false) {
            $this->flashMatriculaListFeedback('error', 'No existe un periodo lectivo con matricula habilitada.');
            $this->redirect('/matriculas?panel=gestion#matriculas-registradas');
        }

        $data = $this->formData($period);

        if (!$this->isValid($data)) {
            $this->flashOldFormData($data);
            $this->flashMatriculaFormFeedback('error', 'Complete los datos obligatorios de persona, estudiante, familiares, representante y matricula.');
            $this->redirect('/matriculas?panel=nueva#matricula-form');
        }

        $matriculationModel = new MatriculationModel();

        try {
            $matriculationModel->createEnrollment($data);
        } catch (\Throwable $exception) {
            $this->flashOldFormData($data);
            $this->flashMatriculaFormFeedback('error', $exception->getMessage());
            $this->redirect('/matriculas?panel=nueva#matricula-form');
        }

        $this->flashMatriculaListFeedback('success', 'Matricula registrada correctamente para el periodo actual.');
        $this->redirect('/matriculas?panel=gestion#matriculas-registradas');
    }

    public function findPerson(): void
    {
        $this->requireAuth();

        header('Content-Type: application/json; charset=UTF-8');

        $cedula = trim((string) ($_GET['cedula'] ?? ''));

        if (!$this->isValidCedula($cedula)) {
            http_response_code(422);
            echo json_encode([
                'found' => false,
                'message' => 'La cedula debe tener 10 digitos.',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        $personModel = new PersonModel();
        $person = $personModel->findByCedula($cedula);

        if ($person === false) {
            echo json_encode([
                'found' => false,
                'message' => 'Persona no registrada, favor completar los datos.',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        echo json_encode([
            'found' => true,
            'person' => [
                'perid' => (int) ($person['perid'] ?? 0),
                'percedula' => (string) ($person['percedula'] ?? ''),
                'pernombres' => (string) ($person['pernombres'] ?? ''),
                'perapellidos' => (string) ($person['perapellidos'] ?? ''),
                'pertelefono1' => (string) ($person['pertelefono1'] ?? ''),
                'pertelefono2' => (string) ($person['pertelefono2'] ?? ''),
                'percorreo' => (string) ($person['percorreo'] ?? ''),
                'persexo' => (string) ($person['persexo'] ?? ''),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function toggleStatus(): void
    {
        $this->requireAuth();

        $matriculaId = (int) ($_POST['matid'] ?? 0);
        $redirectTo = trim($_POST['redirect_to'] ?? '/matriculas?panel=gestion#matriculas-registradas');

        if ($matriculaId <= 0) {
            $this->flashMatriculaListFeedback('error', 'La matricula seleccionada no es valida.');
            $this->redirect($redirectTo);
        }

        $matriculationModel = new MatriculationModel();

        try {
            $isActive = $matriculationModel->toggleStudentStatusByMatricula($matriculaId);
        } catch (\Throwable $exception) {
            $this->flashMatriculaListFeedback('error', $exception->getMessage());
            $this->redirect($redirectTo);
        }

        $this->flashMatriculaListFeedback(
            'success',
            $isActive
                ? 'Matricula habilitada correctamente. El estudiante queda activo.'
                : 'Matricula inhabilitada correctamente. El estudiante queda inactivo.'
        );
        $this->redirect($redirectTo);
    }

    private function formData(array $period): array
    {
        $defaultMatricula = $this->defaultMatriculaData();

        return [
            'period' => $period,
            'person' => [
                'percedula' => trim($_POST['person']['percedula'] ?? ''),
                'pernombres' => trim($_POST['person']['pernombres'] ?? ''),
                'perapellidos' => trim($_POST['person']['perapellidos'] ?? ''),
                'pertelefono1' => trim($_POST['person']['pertelefono1'] ?? ''),
                'pertelefono2' => trim($_POST['person']['pertelefono2'] ?? ''),
                'percorreo' => trim($_POST['person']['percorreo'] ?? ''),
                'persexo' => trim($_POST['person']['persexo'] ?? ''),
            ],
            'student' => [
                'estlugarnacimiento' => trim($_POST['student']['estlugarnacimiento'] ?? ''),
                'estdireccion' => trim($_POST['student']['estdireccion'] ?? ''),
                'estparroquia' => trim($_POST['student']['estparroquia'] ?? ''),
            ],
            'families' => $this->familyRows((array) ($_POST['family'] ?? [])),
            'representative' => $this->representativeData(),
            'matricula' => [
                'curid' => (int) ($_POST['matricula']['curid'] ?? 0),
                'matfecha' => $defaultMatricula['matfecha'],
                'emdid' => (int) $defaultMatricula['emdid'],
            ],
            'resources' => [
                'mrtinternet' => isset($_POST['resources']['mrtinternet']),
                'mrtcomputador' => isset($_POST['resources']['mrtcomputador']),
                'mrtlaptop' => isset($_POST['resources']['mrtlaptop']),
                'mrttablet' => isset($_POST['resources']['mrttablet']),
                'mrtcelular' => isset($_POST['resources']['mrtcelular']),
            ],
            'billing' => [
                'mfcnombre' => trim((string) ($_POST['billing']['mfcnombre'] ?? '')),
                'mfctipoidentificacion' => mb_strtoupper(trim((string) ($_POST['billing']['mfctipoidentificacion'] ?? ''))),
                'mfcidentificacion' => preg_replace('/\D+/', '', (string) ($_POST['billing']['mfcidentificacion'] ?? '')) ?? '',
                'mfcdireccion' => trim((string) ($_POST['billing']['mfcdireccion'] ?? '')),
                'mfccorreo' => trim((string) ($_POST['billing']['mfccorreo'] ?? '')),
                'mfctelefono' => trim((string) ($_POST['billing']['mfctelefono'] ?? '')),
            ],
            'photo' => $_FILES['matricula_photo'] ?? null,
        ];
    }

    private function familyRows(array $rows): array
    {
        $normalized = [];

        foreach ($rows as $row) {
            $normalized[] = [
                'percedula' => trim((string) ($row['percedula'] ?? '')),
                'perid' => (int) ($row['perid'] ?? 0),
                'pernombres' => trim((string) ($row['pernombres'] ?? '')),
                'perapellidos' => trim((string) ($row['perapellidos'] ?? '')),
                'pertelefono1' => trim((string) ($row['pertelefono1'] ?? '')),
                'pertelefono2' => trim((string) ($row['pertelefono2'] ?? '')),
                'percorreo' => trim((string) ($row['percorreo'] ?? '')),
                'persexo' => trim((string) ($row['persexo'] ?? '')),
                'pteid' => (int) ($row['pteid'] ?? 0),
                'eciid' => (int) ($row['eciid'] ?? 0),
                'istid' => (int) ($row['istid'] ?? 0),
                'famprofesion' => trim((string) ($row['famprofesion'] ?? '')),
                'famlugardetrabajo' => trim((string) ($row['famlugardetrabajo'] ?? '')),
                'famfechanacimiento' => trim((string) ($row['famfechanacimiento'] ?? '')),
            ];
        }

        return $normalized;
    }

    private function representativeData(): array
    {
        return [
            'source' => trim((string) ($_POST['representative_source'] ?? 'family')),
            'family_index' => (int) ($_POST['representative_index'] ?? -1),
            'external' => [
                'perid' => (int) ($_POST['representative_external']['perid'] ?? 0),
                'percedula' => trim((string) ($_POST['representative_external']['percedula'] ?? '')),
                'pernombres' => trim((string) ($_POST['representative_external']['pernombres'] ?? '')),
                'perapellidos' => trim((string) ($_POST['representative_external']['perapellidos'] ?? '')),
                'pertelefono1' => trim((string) ($_POST['representative_external']['pertelefono1'] ?? '')),
                'pertelefono2' => trim((string) ($_POST['representative_external']['pertelefono2'] ?? '')),
                'percorreo' => trim((string) ($_POST['representative_external']['percorreo'] ?? '')),
                'persexo' => trim((string) ($_POST['representative_external']['persexo'] ?? '')),
                'pteid' => (int) ($_POST['representative_external']['pteid'] ?? 0),
            ],
        ];
    }

    private function isValid(array $data): bool
    {
        if (
            !$this->isValidCedula($data['person']['percedula'])
            || !$this->isValidEmail($data['person']['percorreo'] ?? '')
            || !$this->areValidFamilyCedulas($data['families'])
            || !$this->areUniqueFamilyCedulas($data['person']['percedula'], $data['families'])
            || !$this->areValidFamilyEmails($data['families'])
            || !$this->isValidRepresentative($data['person']['percedula'], $data['families'], $data['representative'])
            || !$this->isValidBilling($data['billing'])
            || $data['person']['pernombres'] === ''
            || $data['person']['perapellidos'] === ''
            || $data['matricula']['curid'] <= 0
            || $data['matricula']['emdid'] <= 0
            || $data['matricula']['matfecha'] === ''
        ) {
            return false;
        }

        $validFamilies = 0;

        foreach ($data['families'] as $family) {
            if ($family['percedula'] === '' && $family['pernombres'] === '' && $family['perapellidos'] === '') {
                continue;
            }

            if ($family['pteid'] <= 0) {
                return false;
            }

            $validFamilies++;
        }

        return $data['representative']['source'] === 'external' || $validFamilies > 0;
    }

    private function isValidCedula(string $cedula): bool
    {
        return preg_match('/^\d{10}$/', $cedula) === 1;
    }

    private function isValidEmail(string $email): bool
    {
        $normalized = trim($email);

        if ($normalized === '') {
            return true;
        }

        return filter_var($normalized, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function isValidPhone(string $phone): bool
    {
        $normalized = preg_replace('/\D+/', '', trim($phone)) ?? '';

        if ($normalized === '') {
            return true;
        }

        return preg_match('/^\d{10}$/', $normalized) === 1;
    }

    private function isValidBilling(array $billing): bool
    {
        $name = trim((string) ($billing['mfcnombre'] ?? ''));
        $type = mb_strtoupper(trim((string) ($billing['mfctipoidentificacion'] ?? '')));
        $identification = preg_replace('/\D+/', '', (string) ($billing['mfcidentificacion'] ?? '')) ?? '';
        $email = trim((string) ($billing['mfccorreo'] ?? ''));
        $phone = trim((string) ($billing['mfctelefono'] ?? ''));

        if ($name === '' || !in_array($type, ['CEDULA', 'RUC'], true)) {
            return false;
        }

        if ($type === 'CEDULA' && preg_match('/^\d{10}$/', $identification) !== 1) {
            return false;
        }

        if ($type === 'RUC' && preg_match('/^\d{13}$/', $identification) !== 1) {
            return false;
        }

        return $this->isValidEmail($email) && $this->isValidPhone($phone);
    }

    private function areValidFamilyCedulas(array $families): bool
    {
        foreach ($families as $family) {
            $cedula = (string) ($family['percedula'] ?? '');
            $nombres = (string) ($family['pernombres'] ?? '');
            $apellidos = (string) ($family['perapellidos'] ?? '');

            if ($cedula === '' && $nombres === '' && $apellidos === '') {
                continue;
            }

            if (!$this->isValidCedula($cedula)) {
                return false;
            }
        }

        return true;
    }

    private function areValidFamilyEmails(array $families): bool
    {
        foreach ($families as $family) {
            if (!$this->isValidEmail((string) ($family['percorreo'] ?? ''))) {
                return false;
            }
        }

        return true;
    }

    private function areUniqueFamilyCedulas(string $studentCedula, array $families): bool
    {
        $usedCedulas = [];
        $normalizedStudentCedula = trim($studentCedula);

        foreach ($families as $family) {
            $cedula = trim((string) ($family['percedula'] ?? ''));
            $nombres = trim((string) ($family['pernombres'] ?? ''));
            $apellidos = trim((string) ($family['perapellidos'] ?? ''));

            if ($cedula === '' && $nombres === '' && $apellidos === '') {
                continue;
            }

            if ($cedula === $normalizedStudentCedula) {
                return false;
            }

            if (in_array($cedula, $usedCedulas, true)) {
                return false;
            }

            $usedCedulas[] = $cedula;
        }

        return true;
    }

    private function representativeHasData(array $representative): bool
    {
        $external = $representative['external'] ?? [];

        return trim((string) ($external['percedula'] ?? '')) !== ''
            || trim((string) ($external['pernombres'] ?? '')) !== ''
            || trim((string) ($external['perapellidos'] ?? '')) !== '';
    }

    private function isValidRepresentative(string $studentCedula, array $families, array $representative): bool
    {
        $source = $representative['source'] ?? 'family';

        if ($source === 'external') {
            $external = $representative['external'] ?? [];

            if (
                !$this->representativeHasData($representative)
                || !$this->isValidCedula((string) ($external['percedula'] ?? ''))
                || !$this->isValidEmail((string) ($external['percorreo'] ?? ''))
                || trim((string) ($external['pernombres'] ?? '')) === ''
                || trim((string) ($external['perapellidos'] ?? '')) === ''
                || (int) ($external['pteid'] ?? 0) <= 0
            ) {
                return false;
            }

            $externalCedula = trim((string) ($external['percedula'] ?? ''));

            if ($externalCedula === trim($studentCedula)) {
                return false;
            }

            foreach ($families as $family) {
                $familyCedula = trim((string) ($family['percedula'] ?? ''));
                $familyNames = trim((string) ($family['pernombres'] ?? ''));
                $familyLastnames = trim((string) ($family['perapellidos'] ?? ''));

                if ($familyCedula === '' && $familyNames === '' && $familyLastnames === '') {
                    continue;
                }

                if ($familyCedula === $externalCedula) {
                    return false;
                }
            }

            return true;
        }

        $familyIndex = (int) ($representative['family_index'] ?? -1);

        if ($familyIndex < 0 || !array_key_exists($familyIndex, $families)) {
            return false;
        }

        $family = $families[$familyIndex];

        return trim((string) ($family['percedula'] ?? '')) !== ''
            && trim((string) ($family['pernombres'] ?? '')) !== ''
            && trim((string) ($family['perapellidos'] ?? '')) !== ''
            && (int) ($family['pteid'] ?? 0) > 0;
    }

    private function flashOldFormData(array $data): void
    {
        sessionFlash('old_matricula_form', json_encode([
            'person' => $data['person'],
            'student' => $data['student'],
            'families' => $data['families'],
            'representative' => $data['representative'],
            'matricula' => $data['matricula'],
            'resources' => $data['resources'],
            'billing' => $data['billing'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function oldFormData(): array
    {
        $defaultMatricula = $this->defaultMatriculaData();
        $raw = sessionFlash('old_matricula_form');
        $decoded = is_string($raw) ? json_decode($raw, true) : null;

        if (!is_array($decoded)) {
            $decoded = [];
        }

        return [
            'person' => $decoded['person'] ?? [
                'percedula' => '',
                'pernombres' => '',
                'perapellidos' => '',
                'pertelefono1' => '',
                'pertelefono2' => '',
                'percorreo' => '',
                'persexo' => '',
            ],
            'student' => $decoded['student'] ?? [
                'estlugarnacimiento' => '',
                'estdireccion' => '',
                'estparroquia' => '',
            ],
            'families' => !empty($decoded['families']) && is_array($decoded['families']) ? $decoded['families'] : [],
            'representative' => $decoded['representative'] ?? [
                'source' => 'family',
                'family_index' => 0,
                'external' => [
                    'perid' => 0,
                    'percedula' => '',
                    'pernombres' => '',
                    'perapellidos' => '',
                    'pertelefono1' => '',
                    'pertelefono2' => '',
                    'percorreo' => '',
                    'persexo' => '',
                    'pteid' => 0,
                ],
            ],
            'matricula' => $decoded['matricula'] ?? [
                'curid' => 0,
                'matfecha' => $defaultMatricula['matfecha'],
                'emdid' => $defaultMatricula['emdid'],
            ],
            'resources' => $decoded['resources'] ?? [
                'mrtinternet' => false,
                'mrtcomputador' => false,
                'mrtlaptop' => false,
                'mrttablet' => false,
                'mrtcelular' => false,
            ],
            'billing' => $decoded['billing'] ?? [
                'mfcnombre' => '',
                'mfctipoidentificacion' => 'CEDULA',
                'mfcidentificacion' => '',
                'mfcdireccion' => '',
                'mfccorreo' => '',
                'mfctelefono' => '',
            ],
        ];
    }

    private function defaultMatriculaData(): array
    {
        $matriculationModel = new MatriculationModel();
        $defaultStatus = $matriculationModel->defaultInactiveEnrollmentStatus();

        return [
            'matfecha' => date('Y-m-d'),
            'emdid' => (int) ($defaultStatus['emdid'] ?? 0),
            'emdnombre' => (string) ($defaultStatus['emdnombre'] ?? 'Inactivo'),
        ];
    }

    private function flashMatriculaFormFeedback(string $type, string $message): void
    {
        sessionFlash('matricula_form_feedback_type', $type);
        sessionFlash('matricula_form_feedback_message', $message);
    }

    private function matriculaFormFeedback(): ?array
    {
        $type = sessionFlash('matricula_form_feedback_type');
        $message = sessionFlash('matricula_form_feedback_message');

        if ($type === null || $message === null) {
            return null;
        }

        return ['type' => $type, 'message' => $message];
    }

    private function flashMatriculaListFeedback(string $type, string $message): void
    {
        sessionFlash('matricula_list_feedback_type', $type);
        sessionFlash('matricula_list_feedback_message', $message);
    }

    private function matriculaListFeedback(): ?array
    {
        $type = sessionFlash('matricula_list_feedback_type');
        $message = sessionFlash('matricula_list_feedback_message');

        if ($type === null || $message === null) {
            return null;
        }

        return ['type' => $type, 'message' => $message];
    }

    private function activePanel(): string
    {
        $panel = trim((string) ($_GET['panel'] ?? ''));

        return in_array($panel, ['nueva', 'gestion'], true) ? $panel : '';
    }
}
