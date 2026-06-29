<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CourseModel;
use App\Models\GradebookModel;
use App\Models\MatriculationConfigurationModel;
use App\Models\MatriculationModel;
use App\Models\PersonalModel;
use App\Models\PeriodModel;
use App\Models\PersonModel;
use App\Models\RepresentativeMatriculationAuthorizationModel;
use App\Models\RolePermissionModel;
use App\Models\StudentModel;
use App\Models\UserModel;

class AuthController extends Controller
{
    public function index(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        if (authenticatedSessionExpired()) {
            expireAuthenticatedSession();
            sessionFlash('error', 'La sesion expiro por inactividad. Inicie sesion nuevamente.');
        }

        if (!empty($_SESSION['auth'])) {
            refreshAuthenticatedSessionActivity();
            $this->redirect($this->landingPathForPermissions(
                (array) ($_SESSION['auth']['permissions'] ?? []),
                (int) ($_SESSION['auth']['usuid'] ?? 0)
            ));
        }

        $this->view('auth.login', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'error' => sessionFlash('error'),
            'success' => sessionFlash('success'),
            'oldUsername' => sessionFlash('old_username'),
        ]);
    }

    public function authenticate(): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            sessionFlash('error', 'Usuario y contrasena son obligatorios.');
            sessionFlash('old_username', $username);
            $this->redirect('/login');
        }

        $userModel = new UserModel();
        $user = $userModel->findActiveByUsername($username);

        if ($user === false || !$userModel->verifyPassword($user, $password)) {
            sessionFlash('error', 'Credenciales invalidas.');
            sessionFlash('old_username', $username);
            $this->redirect('/login');
        }

        $rolePermissionModel = new RolePermissionModel();
        $permissionCodes = $rolePermissionModel->permissionCodesByUser((int) $user['usuid']);

        session_regenerate_id(true);

        $_SESSION['auth'] = [
            'usuid' => (int) $user['usuid'],
            'perid' => (int) $user['perid'],
            'username' => (string) $user['usunombre'],
            'first_name' => trim((string) ($user['pernombres'] ?? '')),
            'last_name' => trim((string) ($user['perapellidos'] ?? '')),
            'permissions' => $permissionCodes,
        ];
        $_SESSION['show_login_communications_modal'] = true;
        refreshAuthenticatedSessionActivity();

        $periodModel = new PeriodModel();
        $activePeriod = $periodModel->active();
        setCurrentAcademicPeriod($activePeriod !== false ? $activePeriod : null);

        $userModel->updateLastAccess((int) $user['usuid']);
        $this->redirect($this->landingPathForPermissions($permissionCodes, (int) $user['usuid']));
    }

    public function dashboard(): void
    {
        $user = $this->requireAuth();
        $periodModel = new PeriodModel();
        $personModel = new PersonModel();
        $personalModel = new PersonalModel();
        $studentModel = new StudentModel();
        $courseModel = new CourseModel();
        $matriculationModel = new MatriculationModel();
        $matriculationConfigurationModel = new MatriculationConfigurationModel();
        $currentPeriod = $periodModel->active();
        $enabledMatriculationPeriod = $matriculationConfigurationModel->findEnabledPeriod();
        $canCreateMatricula = $enabledMatriculationPeriod !== false;
        $newMatriculaLabel = 'Nueva matricula';
        $teacherCourses = $currentPeriod !== false
            ? (new GradebookModel())->teacherCourses((int) ($user['perid'] ?? 0), (int) $currentPeriod['pleid'])
            : [];

        if ($this->usesTeacherDashboard($user)) {
            $this->view('auth.dashboard_docente', [
                'appName' => config('app')['name'] ?? 'SGEap',
                'pageTitle' => 'Mis cursos',
                'currentModule' => 'academico',
                'currentSection' => 'docente_cursos',
                'user' => $user,
                'currentPeriod' => $currentPeriod !== false ? $currentPeriod : null,
                'teacherCourses' => $teacherCourses,
                'success' => sessionFlash('success'),
                'error' => sessionFlash('error'),
            ]);
            return;
        }

        if ($canCreateMatricula) {
            $newMatriculaLabel .= ' | ' . (string) $enabledMatriculationPeriod['pledescripcion'];
        }

        $stats = [
            'personas' => $personModel->countAll(),
            'personal' => $personalModel->countAll(),
            'personal_activo' => $personalModel->countActive(),
            'estudiantes' => $studentModel->countAll(),
            'estudiantes_activos' => $studentModel->countActive(),
            'cursos_periodo' => $currentPeriod !== false ? $courseModel->countByPeriod((int) $currentPeriod['pleid']) : 0,
            'cursos_activos' => $currentPeriod !== false ? $courseModel->countActiveByPeriod((int) $currentPeriod['pleid']) : 0,
            'matriculas_periodo' => $currentPeriod !== false ? $matriculationModel->countByPeriod((int) $currentPeriod['pleid']) : 0,
            'periodo_actual' => $currentPeriod !== false ? (string) $currentPeriod['pledescripcion'] : 'Sin periodo activo',
        ];
        $representativeStudents = in_array('representante.estudiantes', (array) ($user['permissions'] ?? []), true)
            ? $studentModel->allByRepresentativePerson(
                (int) ($user['perid'] ?? 0),
                $currentPeriod !== false ? (int) $currentPeriod['pleid'] : null
            )
            : [];
        $canRepresentativeRematriculate = $currentPeriod !== false
            && (new RepresentativeMatriculationAuthorizationModel())->activeByUserAndPeriod(
                (int) ($user['usuid'] ?? 0),
                (int) $currentPeriod['pleid']
            ) !== false;

        $this->view('auth.dashboard', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Dashboard',
            'currentSection' => 'dashboard',
            'user' => $user,
            'stats' => $stats,
            'representativeStudents' => $representativeStudents,
            'canRepresentativeRematriculate' => $canRepresentativeRematriculate,
            'currentPeriod' => $currentPeriod !== false ? $currentPeriod : null,
            'canCreateMatricula' => $canCreateMatricula,
            'newMatriculaLabel' => $newMatriculaLabel,
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function profile(): void
    {
        $user = $this->requireAuth();
        $personModel = new PersonModel();
        $person = $personModel->find((int) ($user['perid'] ?? 0));

        if ($person === false) {
            sessionFlash('error', 'No se encontro la informacion personal del usuario.');
            $this->redirect('/dashboard');
        }

        $this->view('auth.profile', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Mi perfil',
            'currentSection' => 'perfil',
            'user' => $user,
            'instructionLevels' => $personModel->allInstructionLevels(),
            'civilStatuses' => $personModel->allCivilStatuses(),
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
            'old' => $this->profileOldData($person),
        ]);
    }

    public function updateProfile(): void
    {
        $user = $this->requireAuth();
        $personId = (int) ($user['perid'] ?? 0);
        $data = $this->profileFormData();
        $personModel = new PersonModel();

        if ($personId <= 0 || $personModel->find($personId) === false) {
            sessionFlash('error', 'No se encontro la informacion personal del usuario.');
            $this->redirect('/dashboard');
        }

        if ($data['percedula'] === '' || $data['pernombres'] === '' || $data['perapellidos'] === '') {
            $this->flashProfileFormData($data);
            sessionFlash('error', 'Cedula, nombres y apellidos son obligatorios.');
            $this->redirect('/perfil');
        }

        if ($personModel->existsByCedulaExceptId($data['percedula'], $personId)) {
            $this->flashProfileFormData($data);
            sessionFlash('error', 'La cedula ya esta registrada en otra persona.');
            $this->redirect('/perfil');
        }

        $personModel->updateBasic($personId, $data);

        $_SESSION['auth']['first_name'] = $data['pernombres'];
        $_SESSION['auth']['last_name'] = $data['perapellidos'];

        sessionFlash('success', 'Perfil actualizado correctamente.');
        $this->redirect('/perfil');
    }

    public function logout(): void
    {
        expireAuthenticatedSession();
        sessionFlash('success', 'Sesion cerrada correctamente.');
        $this->redirect('/login');
    }

    private function landingPathForPermissions(array $permissions, int $userId): string
    {
        $targets = [
            'dashboard.ver' => '/dashboard',
            'representante.estudiantes' => '/dashboard',
            'matricula_temporal.ver' => '/matricula-temporal',
            'representante.matricula_nueva' => '/matricula-temporal',
            'estudiante.mi_matricula' => '/mi-matricula',
            'estudiantes.gestionar' => '/estudiantes',
            'matriculas.gestionar' => '/matriculas',
            'personas.gestionar' => '/personas',
            'configuracion.gestionar' => '/configuracion',
            'catalogos.gestionar' => '/configuracion/catalogos',
            'cursos.gestionar' => '/cursos',
            'matriculas.documentos' => '/configuracion/matricula/documentos',
            'usuarios_temporales.gestionar' => '/seguridad/usuarios-temporales',
            'seguridad.usuarios' => '/seguridad/usuarios',
            'seguridad.roles_permisos' => '/seguridad/roles-permisos',
        ];

        foreach ($targets as $permission => $path) {
            if ($permission === 'representante.matricula_nueva' && !$this->hasActiveRepresentativeMatriculationAuthorization($userId)) {
                continue;
            }

            if (in_array($permission, $permissions, true)) {
                return $path;
            }
        }

        return '/dashboard';
    }

    private function hasActiveRepresentativeMatriculationAuthorization(int $userId): bool
    {
        $period = (new MatriculationConfigurationModel())->findEnabledPeriod();

        if ($period === false) {
            return false;
        }

        return (new RepresentativeMatriculationAuthorizationModel())->activeNewStudentByUserAndPeriod($userId, (int) $period['pleid']) !== false;
    }

    private function usesTeacherDashboard(array $user): bool
    {
        if (!$this->userHasAnyRoleName($user, ['Docente'])) {
            return false;
        }

        $permissions = (array) ($user['permissions'] ?? []);

        if (
            in_array('representante.estudiantes', $permissions, true)
            || in_array('estudiante.mi_matricula', $permissions, true)
        ) {
            return false;
        }

        return !$this->userHasAnyRoleName($user, [
            'Administrador',
            'Coordinador',
            'Inspector',
            'Rector',
            'Vicerrector',
            'Secretaria',
        ]);
    }

    private function profileOldData(array $person): array
    {
        return [
            'percedula' => sessionFlash('old_percedula') ?? (string) ($person['percedula'] ?? ''),
            'pernombres' => sessionFlash('old_pernombres') ?? (string) ($person['pernombres'] ?? ''),
            'perapellidos' => sessionFlash('old_perapellidos') ?? (string) ($person['perapellidos'] ?? ''),
            'pertelefono1' => sessionFlash('old_pertelefono1') ?? (string) ($person['pertelefono1'] ?? ''),
            'pertelefono2' => sessionFlash('old_pertelefono2') ?? (string) ($person['pertelefono2'] ?? ''),
            'percorreo' => sessionFlash('old_percorreo') ?? (string) ($person['percorreo'] ?? ''),
            'persexo' => sessionFlash('old_persexo') ?? (string) ($person['persexo'] ?? ''),
            'perfechanacimiento' => sessionFlash('old_perfechanacimiento') ?? (string) ($person['perfechanacimiento'] ?? ''),
            'eciid' => sessionFlash('old_eciid') ?? (string) ($person['eciid'] ?? ''),
            'istid' => sessionFlash('old_istid') ?? (string) ($person['istid'] ?? ''),
            'perprofesion' => sessionFlash('old_perprofesion') ?? (string) ($person['perprofesion'] ?? ''),
            'perocupacion' => sessionFlash('old_perocupacion') ?? (string) ($person['perocupacion'] ?? ''),
            'perlugardetrabajo' => sessionFlash('old_perlugardetrabajo') ?? (string) ($person['perlugardetrabajo'] ?? ''),
            'perhablaingles' => sessionFlash('old_perhablaingles') ?? (!empty($person['perhablaingles']) ? '1' : '0'),
        ];
    }

    private function profileFormData(): array
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
            'perlugardetrabajo' => trim((string) ($_POST['perlugardetrabajo'] ?? '')),
            'perhablaingles' => isset($_POST['perhablaingles']),
        ];
    }

    private function flashProfileFormData(array $data): void
    {
        foreach ($data as $key => $value) {
            sessionFlash('old_' . $key, is_bool($value) ? ($value ? '1' : '0') : (string) $value);
        }
    }

}
