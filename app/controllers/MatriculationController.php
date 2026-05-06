<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\MatriculationConfigurationModel;
use App\Models\MatriculationModel;
use App\Models\PersonModel;
use App\Models\RepresentativeMatriculationAuthorizationModel;
use App\Models\StudentModel;
use App\Models\UserModel;

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
        $documents = $matriculationModel->allActiveDocuments();

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
            'housingConditions' => $matriculationModel->allHousingConditions(),
            'bloodGroups' => $matriculationModel->allBloodGroups(),
            'medicalCareTypes' => $matriculationModel->allMedicalCareTypes(),
            'healthConditionTypes' => $matriculationModel->allHealthConditionTypes(),
            'insuranceProviders' => $matriculationModel->allInsuranceProviders(),
            'pregnancyTypes' => $matriculationModel->allPregnancyTypes(),
            'birthTypes' => $matriculationModel->allBirthTypes(),
            'enrollmentStatuses' => $matriculationModel->allEnrollmentStatuses(),
            'documents' => $documents,
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

        $matriculationModel = new MatriculationModel();
        $documents = $matriculationModel->allActiveDocuments();
        $data = $this->formData($period, $documents);

        if (!$this->isValid($data, $documents)) {
            $this->flashOldFormData($data);
            $this->flashMatriculaFormFeedback('error', 'Complete los datos obligatorios de persona, estudiante, familiares, representante, facturacion, documentos y matricula.');
            $this->redirect('/matriculas?panel=nueva#matricula-form');
        }

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

    public function temporary(): void
    {
        $user = $this->requireAuth();
        $matriculationModel = new MatriculationModel();
        $matriculationConfigurationModel = new MatriculationConfigurationModel();
        $period = $matriculationConfigurationModel->findEnabledPeriod();
        $documents = $matriculationModel->allActiveDocuments();
        $usesRepresentativeAuthorization = $this->usesRepresentativeAuthorization($user);

        if ($usesRepresentativeAuthorization && ($period === false || !$this->hasActiveRepresentativeAuthorization($user, (int) $period['pleid']))) {
            $this->temporaryMatriculationForbidden('Secretaria debe habilitar la opcion para matricular un nuevo estudiante.');
        }

        $this->view('matriculas.index', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Matricula de alumno nuevo',
            'currentSection' => 'matricula_temporal',
            'user' => $user,
            'activePanel' => 'nueva',
            'currentPeriod' => $period !== false ? $period : currentAcademicPeriod(),
            'newMatriculaPeriod' => $period !== false ? $period : null,
            'matriculationConfiguration' => $period !== false ? $matriculationConfigurationModel->findByPeriodId((int) $period['pleid']) : false,
            'canCreateMatricula' => $period !== false,
            'newMatriculaLabel' => $period !== false
                ? 'Matricula de alumno nuevo | ' . (string) $period['pledescripcion']
                : 'Matricula de alumno nuevo',
            'courses' => $period !== false ? $matriculationModel->allCoursesByPeriod((int) $period['pleid']) : [],
            'relationships' => $matriculationModel->allRelationships(),
            'civilStatuses' => $matriculationModel->allCivilStatuses(),
            'instructionLevels' => $matriculationModel->allInstructionLevels(),
            'housingConditions' => $matriculationModel->allHousingConditions(),
            'bloodGroups' => $matriculationModel->allBloodGroups(),
            'medicalCareTypes' => $matriculationModel->allMedicalCareTypes(),
            'healthConditionTypes' => $matriculationModel->allHealthConditionTypes(),
            'insuranceProviders' => $matriculationModel->allInsuranceProviders(),
            'pregnancyTypes' => $matriculationModel->allPregnancyTypes(),
            'birthTypes' => $matriculationModel->allBirthTypes(),
            'enrollmentStatuses' => $matriculationModel->allEnrollmentStatuses(),
            'documents' => $documents,
            'matriculas' => [],
            'success' => null,
            'error' => null,
            'matriculaFormFeedback' => $this->matriculaFormFeedback(),
            'matriculaListFeedback' => null,
            'old' => $this->oldFormDataForTemporaryUser($user),
            'isTemporaryMatriculation' => true,
            'matriculationFormAction' => 'matricula-temporal',
        ]);
    }

    public function storeTemporary(): void
    {
        $user = $this->requireAuth();
        $configurationModel = new MatriculationConfigurationModel();
        $period = $configurationModel->findEnabledPeriod();

        if ($period === false) {
            $this->flashMatriculaFormFeedback('error', 'No existe un periodo lectivo con matricula habilitada.');
            $this->redirect('/matricula-temporal#matricula-form');
        }

        $usesRepresentativeAuthorization = $this->usesRepresentativeAuthorization($user);
        $representativeAuthorization = false;

        if ($usesRepresentativeAuthorization) {
            $representativeAuthorization = $this->activeRepresentativeAuthorization($user, (int) $period['pleid']);

            if ($representativeAuthorization === false) {
                $this->flashMatriculaFormFeedback('error', 'Secretaria debe habilitar la opcion para matricular un nuevo estudiante.');
                $this->redirect('/matricula-temporal#matricula-form');
            }
        }

        $matriculationModel = new MatriculationModel();
        $documents = $matriculationModel->allActiveDocuments();
        $data = $this->formData($period, $documents);
        $this->forceTemporaryRepresentative($data, $user);

        if ($this->studentAlreadyExistsByCedula((string) $data['person']['percedula'])) {
            $this->flashOldFormData($data);
            $this->flashMatriculaFormFeedback('error', 'El estudiante ya existe. Acerquese a secretaria para el proceso de matricula de alumno antiguo.');
            $this->redirect('/matricula-temporal#matricula-form');
        }

        if (!$this->isValid($data, $documents)) {
            $this->flashOldFormData($data);
            $this->flashMatriculaFormFeedback('error', 'Complete los datos obligatorios de persona, estudiante, familiares, representante, facturacion, documentos y matricula.');
            $this->redirect('/matricula-temporal#matricula-form');
        }

        try {
            $matriculationModel->createEnrollment($data);

            if ($usesRepresentativeAuthorization) {
                (new RepresentativeMatriculationAuthorizationModel())->useById((int) ($representativeAuthorization['rhmid'] ?? 0));
                (new UserModel())->syncRoleByPerson((int) ($user['perid'] ?? 0), 'Representante matricula nueva', false);
            }
        } catch (\Throwable $exception) {
            $this->flashOldFormData($data);
            $this->flashMatriculaFormFeedback('error', $exception->getMessage());
            $this->redirect('/matricula-temporal#matricula-form');
        }

        $this->flashMatriculaFormFeedback('success', 'Matricula enviada correctamente. Secretaria revisara y activara el registro.');
        $this->redirect('/matricula-temporal#matricula-form');
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
                'perfechanacimiento' => (string) ($person['perfechanacimiento'] ?? ''),
                'eciid' => (int) ($person['eciid'] ?? 0),
                'istid' => (int) ($person['istid'] ?? 0),
                'perprofesion' => (string) ($person['perprofesion'] ?? ''),
                'perocupacion' => (string) ($person['perocupacion'] ?? ''),
                'perlugardetrabajo' => (string) ($person['perlugardetrabajo'] ?? ''),
                'perhablaingles' => !empty($person['perhablaingles']),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function findPersonTemporary(): void
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
                'perfechanacimiento' => (string) ($person['perfechanacimiento'] ?? ''),
                'eciid' => (int) ($person['eciid'] ?? 0),
                'istid' => (int) ($person['istid'] ?? 0),
                'perprofesion' => (string) ($person['perprofesion'] ?? ''),
                'perocupacion' => (string) ($person['perocupacion'] ?? ''),
                'perlugardetrabajo' => (string) ($person['perlugardetrabajo'] ?? ''),
                'perhablaingles' => !empty($person['perhablaingles']),
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

    private function formData(array $period, array $documents = []): array
    {
        $defaultMatricula = $this->defaultMatriculaData();
        $defaultStudentCivilStatusId = $this->defaultStudentCivilStatusId();
        $acceptedDocumentIds = array_map('intval', array_keys((array) ($_POST['documents'] ?? [])));

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
                'perfechanacimiento' => trim($_POST['person']['perfechanacimiento'] ?? ''),
                'eciid' => $defaultStudentCivilStatusId,
                'istid' => (int) ($_POST['person']['istid'] ?? 0),
                'perprofesion' => 'Estudiante',
                'perocupacion' => 'Estudiante',
                'perhablaingles' => false,
            ],
            'student' => [
                'estlugarnacimiento' => trim($_POST['student']['estlugarnacimiento'] ?? ''),
                'estdireccion' => trim($_POST['student']['estdireccion'] ?? ''),
                'estparroquia' => trim($_POST['student']['estparroquia'] ?? ''),
            ],
            'family_context' => [
                'ecfconvivecon_pteids' => $this->cohabitationRelationshipIds((array) ($_POST['family_context']['ecfconvivecon_pteids'] ?? [])),
                'ecfconvivecon' => $this->cohabitationValue((array) ($_POST['family_context']['ecfconvivecon_pteids'] ?? [])),
                'ecfnumerohermanos' => trim((string) ($_POST['family_context']['ecfnumerohermanos'] ?? '')),
                'ecfposicionhermanos' => trim((string) ($_POST['family_context']['ecfposicionhermanos'] ?? '')),
            ],
            'families' => $this->familyRows((array) ($_POST['family'] ?? [])),
            'housing' => [
                'cviid' => (int) ($_POST['housing']['cviid'] ?? 0),
                'estvdescripcion' => trim((string) ($_POST['housing']['estvdescripcion'] ?? '')),
                'estvluzelectrica' => isset($_POST['housing']['estvluzelectrica']),
                'estvaguapotable' => isset($_POST['housing']['estvaguapotable']),
                'estvsshh' => isset($_POST['housing']['estvsshh']),
                'estvtelefono' => isset($_POST['housing']['estvtelefono']),
                'estvcable' => isset($_POST['housing']['estvcable']),
            ],
            'health_context' => [
                'gsid' => (int) ($_POST['health_context']['gsid'] ?? 0),
                'ecstienediscapacidad' => isset($_POST['health_context']['ecstienediscapacidad']),
                'ecsdetallediscapacidad' => trim((string) ($_POST['health_context']['ecsdetallediscapacidad'] ?? '')),
                'amid' => (int) ($_POST['health_context']['amid'] ?? 0),
            ],
            'health_conditions' => $this->healthConditionRows((array) ($_POST['health_conditions'] ?? [])),
            'health_measurement' => [
                'emspeso' => trim((string) ($_POST['health_measurement']['emspeso'] ?? '')),
                'emstalla' => trim((string) ($_POST['health_measurement']['emstalla'] ?? '')),
                'emsimc' => trim((string) ($_POST['health_measurement']['emsimc'] ?? '')),
                'emsfecha_medicion' => trim((string) ($_POST['health_measurement']['emsfecha_medicion'] ?? '')),
                'emsobservacion' => trim((string) ($_POST['health_measurement']['emsobservacion'] ?? '')),
            ],
            'vital_history' => [
                'ehvedadmadre' => trim((string) ($_POST['vital_history']['ehvedadmadre'] ?? '')),
                'ehvcomplicacionesembarazo' => trim((string) ($_POST['vital_history']['ehvcomplicacionesembarazo'] ?? '')),
                'ehvmedicacionembarazo' => trim((string) ($_POST['vital_history']['ehvmedicacionembarazo'] ?? '')),
                'teid' => (int) ($_POST['vital_history']['teid'] ?? 0),
                'tpid' => (int) ($_POST['vital_history']['tpid'] ?? 0),
                'ehvdetalleembarazo' => trim((string) ($_POST['vital_history']['ehvdetalleembarazo'] ?? '')),
                'ehvpesonacer' => trim((string) ($_POST['vital_history']['ehvpesonacer'] ?? '')),
                'ehvtallanacer' => trim((string) ($_POST['vital_history']['ehvtallanacer'] ?? '')),
                'ehvedadcaminar' => trim((string) ($_POST['vital_history']['ehvedadcaminar'] ?? '')),
                'ehvedadhablar' => trim((string) ($_POST['vital_history']['ehvedadhablar'] ?? '')),
                'ehvperiodolactancia' => trim((string) ($_POST['vital_history']['ehvperiodolactancia'] ?? '')),
                'ehvedadbiberon' => trim((string) ($_POST['vital_history']['ehvedadbiberon'] ?? '')),
                'ehvedadcontrolesfinteres' => trim((string) ($_POST['vital_history']['ehvedadcontrolesfinteres'] ?? '')),
            ],
            'academic_context' => [
                'ecafechaingresoinstitucion' => trim((string) ($_POST['academic_context']['ecafechaingresoinstitucion'] ?? '')),
                'ecaharepetidoanios' => isset($_POST['academic_context']['ecaharepetidoanios']),
                'ecadetallerepeticion' => trim((string) ($_POST['academic_context']['ecadetallerepeticion'] ?? '')),
                'ecaasignaturaspreferencia' => trim((string) ($_POST['academic_context']['ecaasignaturaspreferencia'] ?? '')),
                'ecaasignaturasdificultad' => trim((string) ($_POST['academic_context']['ecaasignaturasdificultad'] ?? '')),
                'ecaactividadesextras' => trim((string) ($_POST['academic_context']['ecaactividadesextras'] ?? '')),
            ],
            'representative' => $this->representativeData(),
            'matricula' => [
                'curid' => (int) ($_POST['matricula']['curid'] ?? 0),
                'matfecha' => $defaultMatricula['matfecha'],
                'emdid' => (int) $defaultMatricula['emdid'],
                'tmaid' => (int) $defaultMatricula['tmaid'],
            ],
            'resources' => [
                'mrtinternet' => isset($_POST['resources']['mrtinternet']),
                'mrtcomputador' => isset($_POST['resources']['mrtcomputador']),
                'mrtlaptop' => isset($_POST['resources']['mrtlaptop']),
                'mrttablet' => isset($_POST['resources']['mrttablet']),
                'mrtcelular' => isset($_POST['resources']['mrtcelular']),
                'mrtimpresora' => isset($_POST['resources']['mrtimpresora']),
            ],
            'insurance' => [
                'smid' => (int) ($_POST['insurance']['smid'] ?? 0),
                'msmtelefono' => trim((string) ($_POST['insurance']['msmtelefono'] ?? '')),
                'msmobservacion' => trim((string) ($_POST['insurance']['msmobservacion'] ?? '')),
            ],
            'billing' => [
                'mfcnombre' => trim((string) ($_POST['billing']['mfcnombre'] ?? '')),
                'mfctipoidentificacion' => mb_strtoupper(trim((string) ($_POST['billing']['mfctipoidentificacion'] ?? ''))),
                'mfcidentificacion' => preg_replace('/\D+/', '', (string) ($_POST['billing']['mfcidentificacion'] ?? '')) ?? '',
                'mfcdireccion' => trim((string) ($_POST['billing']['mfcdireccion'] ?? '')),
                'mfccorreo' => trim((string) ($_POST['billing']['mfccorreo'] ?? '')),
                'mfctelefono' => trim((string) ($_POST['billing']['mfctelefono'] ?? '')),
            ],
            'documents_catalog' => $documents,
            'document_acceptances' => $acceptedDocumentIds,
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
                'perfechanacimiento' => trim((string) ($row['perfechanacimiento'] ?? '')),
                'pteid' => (int) ($row['pteid'] ?? 0),
                'eciid' => (int) ($row['eciid'] ?? 0),
                'istid' => (int) ($row['istid'] ?? 0),
                'perprofesion' => trim((string) ($row['perprofesion'] ?? '')),
                'perocupacion' => trim((string) ($row['perocupacion'] ?? '')),
                'perhablaingles' => isset($row['perhablaingles']),
                'perlugardetrabajo' => trim((string) ($row['perlugardetrabajo'] ?? $row['famlugardetrabajo'] ?? '')),
            ];
        }

        return $normalized;
    }

    private function healthConditionRows(array $rows): array
    {
        $normalized = [];

        foreach ($rows as $row) {
            $normalized[] = [
                'tcsid' => (int) ($row['tcsid'] ?? 0),
                'ecsadescripcion' => trim((string) ($row['ecsadescripcion'] ?? '')),
                'ecsamedicamentos' => trim((string) ($row['ecsamedicamentos'] ?? '')),
                'ecsaobservacion' => trim((string) ($row['ecsaobservacion'] ?? '')),
                'ecsavigente' => !isset($row['ecsavigente']) || !empty($row['ecsavigente']),
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
                'perfechanacimiento' => trim((string) ($_POST['representative_external']['perfechanacimiento'] ?? '')),
                'eciid' => (int) ($_POST['representative_external']['eciid'] ?? 0),
                'istid' => (int) ($_POST['representative_external']['istid'] ?? 0),
                'perprofesion' => trim((string) ($_POST['representative_external']['perprofesion'] ?? '')),
                'perocupacion' => trim((string) ($_POST['representative_external']['perocupacion'] ?? '')),
                'perlugardetrabajo' => trim((string) ($_POST['representative_external']['perlugardetrabajo'] ?? '')),
                'perhablaingles' => isset($_POST['representative_external']['perhablaingles']),
                'pteid' => (int) ($_POST['representative_external']['pteid'] ?? 0),
            ],
        ];
    }

    private function isValid(array $data, array $documents = []): bool
    {
        if (
            !$this->isValidCedula($data['person']['percedula'])
            || !$this->isValidEmail($data['person']['percorreo'] ?? '')
            || !$this->areValidFamilyCedulas($data['families'])
            || !$this->areUniqueFamilyCedulas($data['person']['percedula'], $data['families'])
            || !$this->areValidFamilyEmails($data['families'])
            || !$this->isValidRepresentative($data['person']['percedula'], $data['families'], $data['representative'])
            || !$this->isValidBilling($data['billing'])
            || !$this->areRequiredDocumentsAccepted($documents, $data['document_acceptances'] ?? [])
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

    private function areRequiredDocumentsAccepted(array $documents, array $acceptedDocumentIds): bool
    {
        if ($documents === []) {
            return true;
        }

        $acceptedIndex = [];

        foreach ($acceptedDocumentIds as $documentId) {
            $normalizedId = (int) $documentId;

            if ($normalizedId > 0) {
                $acceptedIndex[$normalizedId] = true;
            }
        }

        foreach ($documents as $document) {
            if (empty($document['domobligatorio'])) {
                continue;
            }

            $documentId = (int) ($document['domid'] ?? 0);

            if ($documentId <= 0 || !isset($acceptedIndex[$documentId])) {
                return false;
            }
        }

        return true;
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
            && (int) ($family['pteid'] ?? 0) > 0
            && $this->isAdultBirthDate((string) ($family['perfechanacimiento'] ?? ''));
    }

    private function isAdultBirthDate(string $birthDate): bool
    {
        $birthDate = trim($birthDate);

        if ($birthDate === '') {
            return false;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $birthDate);

        if ($date === false || $date->format('Y-m-d') !== $birthDate) {
            return false;
        }

        return $date->diff(new \DateTimeImmutable('today'))->y >= 18;
    }

    private function flashOldFormData(array $data): void
    {
        sessionFlash('old_matricula_form', json_encode([
            'person' => $data['person'],
            'student' => $data['student'],
            'family_context' => $data['family_context'],
            'families' => $data['families'],
            'housing' => $data['housing'],
            'health_context' => $data['health_context'],
            'health_conditions' => $data['health_conditions'],
            'health_measurement' => $data['health_measurement'],
            'vital_history' => $data['vital_history'],
            'academic_context' => $data['academic_context'],
            'representative' => $data['representative'],
            'matricula' => $data['matricula'],
            'resources' => $data['resources'],
            'insurance' => $data['insurance'],
            'billing' => $data['billing'],
            'documents' => $data['document_acceptances'] ?? [],
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
                'perfechanacimiento' => '',
                'eciid' => $this->defaultStudentCivilStatusId(),
                'istid' => 0,
                'perprofesion' => 'Estudiante',
                'perocupacion' => 'Estudiante',
                'perlugardetrabajo' => '',
                'perhablaingles' => false,
            ],
            'student' => $decoded['student'] ?? [
                'estlugarnacimiento' => '',
                'estdireccion' => '',
                'estparroquia' => '',
            ],
            'family_context' => $decoded['family_context'] ?? [
                'ecfconvivecon_pteids' => [],
                'ecfconvivecon' => '',
                'ecfnumerohermanos' => '',
                'ecfposicionhermanos' => '',
            ],
            'families' => !empty($decoded['families']) && is_array($decoded['families']) ? $decoded['families'] : [],
            'housing' => $decoded['housing'] ?? [
                'cviid' => 0,
                'estvdescripcion' => '',
                'estvluzelectrica' => false,
                'estvaguapotable' => false,
                'estvsshh' => false,
                'estvtelefono' => false,
                'estvcable' => false,
            ],
            'health_context' => $decoded['health_context'] ?? [
                'gsid' => 0,
                'ecstienediscapacidad' => false,
                'ecsdetallediscapacidad' => '',
                'amid' => 0,
            ],
            'health_conditions' => !empty($decoded['health_conditions']) && is_array($decoded['health_conditions']) ? $decoded['health_conditions'] : [],
            'health_measurement' => $decoded['health_measurement'] ?? [
                'emspeso' => '',
                'emstalla' => '',
                'emsimc' => '',
                'emsfecha_medicion' => date('Y-m-d'),
                'emsobservacion' => '',
            ],
            'vital_history' => $decoded['vital_history'] ?? [
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
            ],
            'academic_context' => $decoded['academic_context'] ?? [
                'ecafechaingresoinstitucion' => '',
                'ecaharepetidoanios' => false,
                'ecadetallerepeticion' => '',
                'ecaasignaturaspreferencia' => '',
                'ecaasignaturasdificultad' => '',
                'ecaactividadesextras' => '',
            ],
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
                    'perfechanacimiento' => '',
                    'eciid' => 0,
                    'istid' => 0,
                    'perprofesion' => '',
                    'perocupacion' => '',
                    'perlugardetrabajo' => '',
                    'perhablaingles' => false,
                    'pteid' => 0,
                ],
            ],
            'matricula' => $decoded['matricula'] ?? [
                'curid' => 0,
                'matfecha' => $defaultMatricula['matfecha'],
                'emdid' => $defaultMatricula['emdid'],
                'tmaid' => $defaultMatricula['tmaid'],
            ],
            'resources' => $decoded['resources'] ?? [
                'mrtinternet' => false,
                'mrtcomputador' => false,
                'mrtlaptop' => false,
                'mrttablet' => false,
                'mrtcelular' => false,
                'mrtimpresora' => false,
            ],
            'insurance' => $decoded['insurance'] ?? [
                'smid' => 0,
                'msmtelefono' => '',
                'msmobservacion' => '',
            ],
            'billing' => $decoded['billing'] ?? [
                'mfcnombre' => '',
                'mfctipoidentificacion' => 'CEDULA',
                'mfcidentificacion' => '',
                'mfcdireccion' => '',
                'mfccorreo' => '',
                'mfctelefono' => '',
            ],
            'documents' => !empty($decoded['documents']) && is_array($decoded['documents']) ? array_map('intval', $decoded['documents']) : [],
        ];
    }

    private function oldFormDataForTemporaryUser(array $user): array
    {
        $old = $this->oldFormData();
        $representative = $this->temporaryRepresentativeData($user);
        $existingExternal = is_array($old['representative']['external'] ?? null)
            ? $old['representative']['external']
            : [];

        $old['representative'] = [
            'source' => 'external',
            'family_index' => -1,
            'external' => array_merge($representative, [
                'pteid' => (int) ($existingExternal['pteid'] ?? $representative['pteid']),
            ]),
        ];

        return $old;
    }

    private function usesRepresentativeAuthorization(array $user): bool
    {
        return $this->hasPermission('representante.matricula_nueva', $user)
            && !$this->hasPermission('matricula_temporal.ver', $user);
    }

    private function hasActiveRepresentativeAuthorization(array $user, int $periodId): bool
    {
        return $this->activeRepresentativeAuthorization($user, $periodId) !== false;
    }

    private function activeRepresentativeAuthorization(array $user, int $periodId): array|false
    {
        return (new RepresentativeMatriculationAuthorizationModel())->activeByUserAndPeriod(
            (int) ($user['usuid'] ?? 0),
            $periodId
        );
    }

    private function temporaryMatriculationForbidden(string $message): void
    {
        http_response_code(403);
        $this->view('errors.forbidden', [
            'pageTitle' => 'Acceso restringido',
            'currentSection' => 'dashboard',
            'user' => $_SESSION['auth'] ?? [],
            'requestedPath' => currentPath(),
            'message' => $message,
        ]);
        exit;
    }

    private function forceTemporaryRepresentative(array &$data, array $user): void
    {
        $representative = $this->temporaryRepresentativeData($user);
        $submittedExternal = is_array($data['representative']['external'] ?? null)
            ? $data['representative']['external']
            : [];

        $data['representative'] = [
            'source' => 'external',
            'family_index' => -1,
            'external' => array_merge($submittedExternal, [
                'perid' => $representative['perid'],
                'percedula' => $representative['percedula'],
                'pernombres' => trim((string) ($submittedExternal['pernombres'] ?? '')) !== '' ? $submittedExternal['pernombres'] : $representative['pernombres'],
                'perapellidos' => trim((string) ($submittedExternal['perapellidos'] ?? '')) !== '' ? $submittedExternal['perapellidos'] : $representative['perapellidos'],
                'pertelefono1' => trim((string) ($submittedExternal['pertelefono1'] ?? '')) !== '' ? $submittedExternal['pertelefono1'] : $representative['pertelefono1'],
                'pertelefono2' => trim((string) ($submittedExternal['pertelefono2'] ?? '')) !== '' ? $submittedExternal['pertelefono2'] : $representative['pertelefono2'],
                'percorreo' => trim((string) ($submittedExternal['percorreo'] ?? '')) !== '' ? $submittedExternal['percorreo'] : $representative['percorreo'],
            ]),
        ];
    }

    private function temporaryRepresentativeData(array $user): array
    {
        $userModel = new UserModel();
        $person = $userModel->userWithPerson((int) ($user['usuid'] ?? 0));

        if ($person === false) {
            return [
                'perid' => (int) ($user['perid'] ?? 0),
                'percedula' => '',
                'pernombres' => (string) ($user['first_name'] ?? ''),
                'perapellidos' => (string) ($user['last_name'] ?? ''),
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
        }

        $personModel = new PersonModel();
        $fullPerson = $personModel->findByCedula((string) ($person['percedula'] ?? ''));
        $source = is_array($fullPerson) ? $fullPerson : $person;

        return [
            'perid' => (int) ($source['perid'] ?? $person['perid'] ?? 0),
            'percedula' => (string) ($source['percedula'] ?? ''),
            'pernombres' => (string) ($source['pernombres'] ?? ''),
            'perapellidos' => (string) ($source['perapellidos'] ?? ''),
            'pertelefono1' => (string) ($source['pertelefono1'] ?? ''),
            'pertelefono2' => (string) ($source['pertelefono2'] ?? ''),
            'percorreo' => (string) ($source['percorreo'] ?? ''),
            'persexo' => (string) ($source['persexo'] ?? ''),
            'perfechanacimiento' => (string) ($source['perfechanacimiento'] ?? ''),
            'eciid' => (int) ($source['eciid'] ?? 0),
            'istid' => (int) ($source['istid'] ?? 0),
            'perprofesion' => (string) ($source['perprofesion'] ?? ''),
            'perocupacion' => (string) ($source['perocupacion'] ?? ''),
            'perlugardetrabajo' => (string) ($source['perlugardetrabajo'] ?? ''),
            'perhablaingles' => !empty($source['perhablaingles']),
            'pteid' => 0,
        ];
    }

    private function studentAlreadyExistsByCedula(string $cedula): bool
    {
        if (!$this->isValidCedula($cedula)) {
            return false;
        }

        $person = (new PersonModel())->findByCedula($cedula);

        if ($person === false) {
            return false;
        }

        return (new StudentModel())->existsByPersonId((int) $person['perid']);
    }

    private function defaultMatriculaData(): array
    {
        $matriculationModel = new MatriculationModel();
        $defaultStatus = $matriculationModel->defaultInactiveEnrollmentStatus();

        return [
            'matfecha' => date('Y-m-d'),
            'emdid' => (int) ($defaultStatus['emdid'] ?? 0),
            'emdnombre' => (string) ($defaultStatus['emdnombre'] ?? 'Inactivo'),
            'tmaid' => (int) (($matriculationModel->defaultEnrollmentType()['tmaid'] ?? 0)),
        ];
    }

    private function defaultStudentCivilStatusId(): int
    {
        $matriculationModel = new MatriculationModel();

        foreach ($matriculationModel->allCivilStatuses() as $civilStatus) {
            $name = mb_strtolower(trim((string) ($civilStatus['ecinombre'] ?? '')));

            if ($name === 'soltero' || $name === 'soltera') {
                return (int) ($civilStatus['eciid'] ?? 0);
            }
        }

        return 0;
    }

    private function cohabitationRelationshipIds(array $values): array
    {
        $selected = [];

        foreach ($values as $value) {
            $id = (int) $value;

            if ($id > 0 && !in_array($id, $selected, true)) {
                $selected[] = $id;
            }
        }

        return $selected;
    }

    private function cohabitationValue(array $values): string
    {
        $ids = $this->cohabitationRelationshipIds($values);

        if ($ids === []) {
            return '';
        }

        $matriculationModel = new MatriculationModel();
        $relationships = $matriculationModel->allRelationships();
        $names = [];

        foreach ($relationships as $relationship) {
            $id = (int) ($relationship['pteid'] ?? 0);

            if (in_array($id, $ids, true)) {
                $names[] = (string) ($relationship['ptenombre'] ?? '');
            }
        }

        $selected = array_values(array_filter($names, static fn (string $name): bool => trim($name) !== ''));

        return implode(', ', $selected);
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
