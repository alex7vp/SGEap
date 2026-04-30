<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CourseModel;
use App\Models\MatriculationModel;
use App\Models\PersonModel;
use App\Models\StudentModel;

class StudentController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth();
        $studentModel = new StudentModel();
        $courseModel = new CourseModel();
        $currentPeriod = currentAcademicPeriod();
        $periodId = is_array($currentPeriod) ? (int) $currentPeriod['pleid'] : null;

        $this->view('estudiantes.index', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Estudiantes',
            'currentSection' => 'estudiantes',
            'user' => $user,
            'students' => $studentModel->allWithPerson($periodId),
            'courses' => $periodId !== null ? $courseModel->allByPeriod($periodId) : [],
            'currentPeriod' => $currentPeriod,
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function search(): void
    {
        $this->requireAuth();

        header('Content-Type: application/json; charset=UTF-8');

        $currentPeriod = currentAcademicPeriod();
        $periodId = is_array($currentPeriod) ? (int) $currentPeriod['pleid'] : null;
        $studentModel = new StudentModel();
        $students = $studentModel->allWithPerson($periodId, [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'curid' => (int) ($_GET['curid'] ?? 0),
            'sort' => trim((string) ($_GET['sort'] ?? 'apellidos')),
            'direction' => trim((string) ($_GET['direction'] ?? 'asc')),
        ]);

        echo json_encode([
            'html' => $this->studentRowsHtml($students),
            'count' => count($students),
            'isEmpty' => $students === [],
            'emptyHtml' => '<div class="empty-state">No se encontraron estudiantes.</div>',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function create(): void
    {
        $user = $this->requireAuth();
        $personModel = new PersonModel();

        $this->view('estudiantes.create', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Nuevo estudiante',
            'currentSection' => 'estudiantes',
            'user' => $user,
            'persons' => $personModel->allWithoutStudent(),
            'error' => sessionFlash('error'),
            'old' => [
                'perid' => sessionFlash('old_perid') ?? '',
                'estlugarnacimiento' => sessionFlash('old_estlugarnacimiento') ?? '',
                'estdireccion' => sessionFlash('old_estdireccion') ?? '',
                'estparroquia' => sessionFlash('old_estparroquia') ?? '',
                'estestado' => sessionFlash('old_estestado') ?? '1',
            ],
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();

        $data = $this->studentFormData();

        if ($data['perid'] <= 0) {
            $this->flashStudentFormData($data);
            sessionFlash('error', 'Debe seleccionar una persona.');
            $this->redirect('/estudiantes/crear');
        }

        $personModel = new PersonModel();
        $studentModel = new StudentModel();
        $person = $personModel->find($data['perid']);

        if ($person === false) {
            $this->flashStudentFormData($data);
            sessionFlash('error', 'La persona seleccionada no existe.');
            $this->redirect('/estudiantes/crear');
        }

        if ($studentModel->existsByPersonId($data['perid'])) {
            $this->flashStudentFormData($data);
            sessionFlash('error', 'La persona seleccionada ya tiene un registro como estudiante.');
            $this->redirect('/estudiantes/crear');
        }

        $studentModel->create($data);
        sessionFlash('success', 'Estudiante registrado correctamente.');
        $this->redirect('/estudiantes');
    }

    public function edit(): void
    {
        $user = $this->requireAuth();
        $studentId = (int) ($_GET['id'] ?? 0);

        if ($studentId <= 0) {
            sessionFlash('error', 'El estudiante solicitado no es valido.');
            $this->redirect('/estudiantes');
        }

        $studentModel = new StudentModel();
        $courseModel = new CourseModel();
        $currentPeriod = currentAcademicPeriod();
        $student = $studentModel->findDetailed($studentId, is_array($currentPeriod) ? (int) $currentPeriod['pleid'] : null);

        if ($student === false) {
            sessionFlash('error', 'El estudiante solicitado no existe.');
            $this->redirect('/estudiantes');
        }

        $this->view('estudiantes.edit', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Editar estudiante',
            'currentSection' => 'estudiantes',
            'user' => $user,
            'student' => $student,
            'currentPeriod' => $currentPeriod,
            'courses' => is_array($currentPeriod) ? $courseModel->allByPeriod((int) $currentPeriod['pleid']) : [],
            'error' => sessionFlash('error'),
            'success' => sessionFlash('success'),
        ]);
    }

    public function show(): void
    {
        $user = $this->requireAuth();
        $studentId = (int) ($_GET['id'] ?? 0);

        if ($studentId <= 0) {
            sessionFlash('error', 'El estudiante solicitado no es valido.');
            $this->redirect('/estudiantes');
        }

        $studentModel = new StudentModel();
        $matriculationModel = new MatriculationModel();
        $currentPeriod = currentAcademicPeriod();
        $profile = $studentModel->profile($studentId, is_array($currentPeriod) ? (int) $currentPeriod['pleid'] : null);

        if ($profile === false) {
            sessionFlash('error', 'El estudiante solicitado no existe.');
            $this->redirect('/estudiantes');
        }

        $this->view('estudiantes.show', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Ficha del estudiante',
            'currentSection' => 'estudiantes',
            'user' => $user,
            'currentPeriod' => $currentPeriod,
            'profile' => $profile,
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function module(): void
    {
        $user = $this->requireAuth();
        $studentId = (int) ($_GET['id'] ?? 0);
        $section = trim((string) ($_GET['seccion'] ?? ''));
        $panel = trim((string) ($_GET['panel'] ?? ''));
        $sections = $this->studentProfileSections();

        if ($studentId <= 0 || !isset($sections[$section])) {
            sessionFlash('error', 'El modulo solicitado no es valido.');
            $this->redirect('/estudiantes');
        }

        $studentModel = new StudentModel();
        $matriculationModel = new MatriculationModel();
        $currentPeriod = currentAcademicPeriod();
        $profile = $studentModel->profile($studentId, is_array($currentPeriod) ? (int) $currentPeriod['pleid'] : null);

        if ($profile === false) {
            sessionFlash('error', 'El estudiante solicitado no existe.');
            $this->redirect('/estudiantes');
        }

        $this->view('estudiantes.module', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => $sections[$section],
            'currentSection' => 'estudiantes',
            'user' => $user,
            'currentPeriod' => $currentPeriod,
            'profile' => $profile,
            'section' => $section,
            'panel' => $section === 'salud' ? $this->healthPanel($panel) : '',
            'sectionTitle' => $sections[$section],
            'courses' => is_array($currentPeriod) ? $matriculationModel->allCoursesByPeriod((int) $currentPeriod['pleid']) : [],
            'relationships' => $matriculationModel->allRelationships(),
            'civilStatuses' => $matriculationModel->allCivilStatuses(),
            'instructionLevels' => $matriculationModel->allInstructionLevels(),
            'bloodGroups' => $matriculationModel->allBloodGroups(),
            'medicalCareTypes' => $matriculationModel->allMedicalCareTypes(),
            'healthConditionTypes' => $matriculationModel->allHealthConditionTypes(),
            'insuranceProviders' => $matriculationModel->allInsuranceProviders(),
            'pregnancyTypes' => $matriculationModel->allPregnancyTypes(),
            'birthTypes' => $matriculationModel->allBirthTypes(),
            'enrollmentStatuses' => $matriculationModel->allEnrollmentStatuses(),
            'enrollmentTypes' => $matriculationModel->allEnrollmentTypes(),
            'documentsCatalog' => $matriculationModel->allActiveDocuments(),
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function updateModule(): void
    {
        $this->requireAuth();
        $studentId = (int) ($_POST['estid'] ?? 0);
        $section = trim((string) ($_POST['section'] ?? ''));
        $panel = trim((string) ($_POST['panel'] ?? ''));
        $sections = $this->studentProfileSections();

        if ($studentId <= 0 || !isset($sections[$section])) {
            sessionFlash('error', 'El modulo solicitado no es valido.');
            $this->redirect('/estudiantes');
        }

        $studentModel = new StudentModel();
        $currentPeriod = currentAcademicPeriod();

        try {
            $studentModel->updateModule(
                $studentId,
                $section,
                $this->moduleFormData($section, $panel),
                is_array($currentPeriod) ? (int) $currentPeriod['pleid'] : null
            );
        } catch (\Throwable $exception) {
            sessionFlash('error', 'No se pudo actualizar el modulo: ' . $exception->getMessage());
            $this->redirect($this->moduleRedirectPath($studentId, $section, $panel));
        }

        sessionFlash('success', 'Modulo actualizado correctamente.');
        $this->redirect($this->moduleRedirectPath($studentId, $section, $panel));
    }

    public function update(): void
    {
        $this->requireAuth();
        $studentModel = new StudentModel();
        $data = $this->studentUpdateData();
        $currentPeriod = currentAcademicPeriod();
        $student = $studentModel->findDetailed($data['estid'], is_array($currentPeriod) ? (int) $currentPeriod['pleid'] : null);

        if ($student === false) {
            sessionFlash('error', 'El estudiante solicitado no existe.');
            $this->redirect('/estudiantes');
        }

        $data['perid'] = (int) $student['perid'];
        $data['matid'] = (int) ($student['matid'] ?? 0);

        if (!$this->isValidCedula($data['percedula']) || $data['pernombres'] === '' || $data['perapellidos'] === '') {
            sessionFlash('error', 'Cedula, nombres y apellidos son obligatorios.');
            $this->redirect('/estudiantes/editar?id=' . $data['estid']);
        }

        if ($studentModel->cedulaExistsForOtherPerson($data['percedula'], $data['perid'])) {
            sessionFlash('error', 'Ya existe otra persona registrada con esa cedula.');
            $this->redirect('/estudiantes/editar?id=' . $data['estid']);
        }

        try {
            $studentModel->updateDetailed($data);
        } catch (\Throwable $exception) {
            sessionFlash('error', 'No se pudo actualizar el estudiante: ' . $exception->getMessage());
            $this->redirect('/estudiantes/editar?id=' . $data['estid']);
        }

        sessionFlash('success', 'Estudiante actualizado correctamente.');
        $this->redirect('/estudiantes');
    }

    private function studentFormData(): array
    {
        return [
            'perid' => (int) ($_POST['perid'] ?? 0),
            'estlugarnacimiento' => trim($_POST['estlugarnacimiento'] ?? ''),
            'estdireccion' => trim($_POST['estdireccion'] ?? ''),
            'estparroquia' => trim($_POST['estparroquia'] ?? ''),
            'estestado' => ($_POST['estestado'] ?? '1') === '1',
        ];
    }

    private function studentUpdateData(): array
    {
        return [
            'estid' => (int) ($_POST['estid'] ?? 0),
            'perid' => 0,
            'matid' => 0,
            'curid' => (int) ($_POST['curid'] ?? 0),
            'percedula' => trim((string) ($_POST['percedula'] ?? '')),
            'pernombres' => trim((string) ($_POST['pernombres'] ?? '')),
            'perapellidos' => trim((string) ($_POST['perapellidos'] ?? '')),
            'persexo' => trim((string) ($_POST['persexo'] ?? '')),
            'perfechanacimiento' => trim((string) ($_POST['perfechanacimiento'] ?? '')),
            'pertelefono1' => trim((string) ($_POST['pertelefono1'] ?? '')),
            'pertelefono2' => trim((string) ($_POST['pertelefono2'] ?? '')),
            'percorreo' => trim((string) ($_POST['percorreo'] ?? '')),
            'perprofesion' => trim((string) ($_POST['perprofesion'] ?? '')),
            'perocupacion' => trim((string) ($_POST['perocupacion'] ?? '')),
            'estlugarnacimiento' => trim((string) ($_POST['estlugarnacimiento'] ?? '')),
            'estdireccion' => trim((string) ($_POST['estdireccion'] ?? '')),
            'estparroquia' => trim((string) ($_POST['estparroquia'] ?? '')),
            'estestado' => ($_POST['estestado'] ?? '1') === '1',
        ];
    }

    private function moduleFormData(string $section, string $panel = ''): array
    {
        if ($section === 'estudiante') {
            return $this->studentUpdateData();
        }

        if ($section === 'matricula') {
            return [
                'curid' => (int) ($_POST['curid'] ?? 0),
                'matfecha' => trim((string) ($_POST['matfecha'] ?? '')),
                'emdid' => (int) ($_POST['emdid'] ?? 0),
                'tmaid' => (int) ($_POST['tmaid'] ?? 0),
            ];
        }

        if ($section === 'representante') {
            return [
                'perid' => (int) ($_POST['perid'] ?? 0),
                'pteid' => (int) ($_POST['pteid'] ?? 0),
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
                'perlugardetrabajo' => trim((string) ($_POST['perlugardetrabajo'] ?? '')),
                'perhablaingles' => isset($_POST['perhablaingles']),
            ];
        }

        if ($section === 'familiares') {
            return ['families' => (array) ($_POST['families'] ?? [])];
        }

        if ($section === 'salud') {
            $healthPanel = $this->healthPanel($panel);
            $data = ['health_panel' => $healthPanel];

            if ($healthPanel === 'general') {
                $data += [
                    'gsid' => (int) ($_POST['gsid'] ?? 0),
                    'amid' => (int) ($_POST['amid'] ?? 0),
                    'ecstienediscapacidad' => isset($_POST['ecstienediscapacidad']),
                    'ecsdetallediscapacidad' => trim((string) ($_POST['ecsdetallediscapacidad'] ?? '')),
                    'insurance' => [
                        'smid' => (int) ($_POST['insurance']['smid'] ?? 0),
                        'msmtelefono' => trim((string) ($_POST['insurance']['msmtelefono'] ?? '')),
                        'msmobservacion' => trim((string) ($_POST['insurance']['msmobservacion'] ?? '')),
                    ],
                ];
            } elseif ($healthPanel === 'condiciones') {
                $data['health_conditions'] = (array) ($_POST['health_conditions'] ?? []);
            } elseif ($healthPanel === 'historia-vital') {
                $data['vital_history'] = [
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
                ];
            } elseif ($healthPanel === 'mediciones') {
                $data['health_measurement'] = [
                    'emspeso' => trim((string) ($_POST['health_measurement']['emspeso'] ?? '')),
                    'emstalla' => trim((string) ($_POST['health_measurement']['emstalla'] ?? '')),
                    'emsimc' => trim((string) ($_POST['health_measurement']['emsimc'] ?? '')),
                    'emsfecha_medicion' => trim((string) ($_POST['health_measurement']['emsfecha_medicion'] ?? '')),
                    'emsobservacion' => trim((string) ($_POST['health_measurement']['emsobservacion'] ?? '')),
                ];
            }

            return $data;
        }

        if ($section === 'academico') {
            return [
                'ecafechaingresoinstitucion' => trim((string) ($_POST['ecafechaingresoinstitucion'] ?? '')),
                'ecaharepetidoanios' => isset($_POST['ecaharepetidoanios']),
                'ecadetallerepeticion' => trim((string) ($_POST['ecadetallerepeticion'] ?? '')),
                'ecaasignaturaspreferencia' => trim((string) ($_POST['ecaasignaturaspreferencia'] ?? '')),
                'ecaasignaturasdificultad' => trim((string) ($_POST['ecaasignaturasdificultad'] ?? '')),
                'ecaactividadesextras' => trim((string) ($_POST['ecaactividadesextras'] ?? '')),
            ];
        }

        if ($section === 'recursos') {
            return [
                'mrtinternet' => isset($_POST['mrtinternet']),
                'mrtcomputador' => isset($_POST['mrtcomputador']),
                'mrtlaptop' => isset($_POST['mrtlaptop']),
                'mrttablet' => isset($_POST['mrttablet']),
                'mrtcelular' => isset($_POST['mrtcelular']),
                'mrtimpresora' => isset($_POST['mrtimpresora']),
            ];
        }

        if ($section === 'facturacion') {
            return [
                'mfcnombre' => trim((string) ($_POST['mfcnombre'] ?? '')),
                'mfctipoidentificacion' => mb_strtoupper(trim((string) ($_POST['mfctipoidentificacion'] ?? 'CEDULA'))),
                'mfcidentificacion' => preg_replace('/\D+/', '', (string) ($_POST['mfcidentificacion'] ?? '')) ?? '',
                'mfcdireccion' => trim((string) ($_POST['mfcdireccion'] ?? '')),
                'mfccorreo' => trim((string) ($_POST['mfccorreo'] ?? '')),
                'mfctelefono' => trim((string) ($_POST['mfctelefono'] ?? '')),
            ];
        }

        if ($section === 'documentos') {
            $matriculationModel = new MatriculationModel();

            return [
                'documents_catalog' => $matriculationModel->allActiveDocuments(),
                'documents' => array_map('intval', array_keys((array) ($_POST['documents'] ?? []))),
            ];
        }

        return [];
    }

    private function isValidCedula(string $cedula): bool
    {
        return preg_match('/^\d{10}$/', $cedula) === 1;
    }

    private function studentRowsHtml(array $students): string
    {
        if ($students === []) {
            return '';
        }

        $html = '';

        foreach ($students as $student) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars((string) ($student['percedula'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td>' . htmlspecialchars((string) ($student['pernombres'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td>' . htmlspecialchars((string) ($student['perapellidos'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td>' . htmlspecialchars((string) ($student['curso'] ?? 'Sin matricula'), ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td><a class="icon-button icon-button-view" href="'
                . htmlspecialchars(baseUrl('estudiantes/ver?id=' . (int) ($student['estid'] ?? 0)), ENT_QUOTES, 'UTF-8')
                . '" title="Ver ficha" aria-label="Ver ficha"><i class="fa fa-search" aria-hidden="true"></i></a></td>';
            $html .= '</tr>';
        }

        return $html;
    }

    private function studentProfileSections(): array
    {
        return [
            'estudiante' => 'Datos del estudiante',
            'matricula' => 'Matricula',
            'representante' => 'Representante',
            'familiares' => 'Familiares',
            'salud' => 'Salud',
            'academico' => 'Contexto academico',
            'recursos' => 'Recursos tecnologicos',
            'facturacion' => 'Facturacion',
            'documentos' => 'Documentos',
            'historial' => 'Historial de matriculas',
        ];
    }

    private function healthPanel(string $panel): string
    {
        return in_array($panel, ['general', 'condiciones', 'historia-vital', 'mediciones'], true) ? $panel : '';
    }

    private function moduleRedirectPath(int $studentId, string $section, string $panel): string
    {
        $healthPanel = $section === 'salud' ? $this->healthPanel($panel) : '';
        $path = '/estudiantes/modulo?id=' . $studentId . '&seccion=' . $section;

        if ($healthPanel !== '') {
            $path .= '&panel=' . $healthPanel;
        }

        if ($healthPanel === 'mediciones') {
            $path .= '#nueva-medicion';
        }

        return $path;
    }

    private function flashStudentFormData(array $data): void
    {
        sessionFlash('old_perid', (string) $data['perid']);
        sessionFlash('old_estlugarnacimiento', (string) $data['estlugarnacimiento']);
        sessionFlash('old_estdireccion', (string) $data['estdireccion']);
        sessionFlash('old_estparroquia', (string) $data['estparroquia']);
        sessionFlash('old_estestado', $data['estestado'] ? '1' : '0');
    }
}
