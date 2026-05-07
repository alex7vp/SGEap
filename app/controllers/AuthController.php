<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CourseModel;
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
        if (!empty($_SESSION['auth'])) {
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

        $_SESSION['auth'] = [
            'usuid' => (int) $user['usuid'],
            'perid' => (int) $user['perid'],
            'username' => (string) $user['usunombre'],
            'first_name' => trim((string) ($user['pernombres'] ?? '')),
            'last_name' => trim((string) ($user['perapellidos'] ?? '')),
            'permissions' => $permissionCodes,
        ];

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

        $this->view('auth.dashboard', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Dashboard',
            'currentSection' => 'dashboard',
            'user' => $user,
            'stats' => $stats,
            'representativeStudents' => $representativeStudents,
            'currentPeriod' => $currentPeriod !== false ? $currentPeriod : null,
            'canCreateMatricula' => $canCreateMatricula,
            'newMatriculaLabel' => $newMatriculaLabel,
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
        ]);
    }

    public function logout(): void
    {
        unset($_SESSION['auth']);
        setCurrentAcademicPeriod(null);
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

        return (new RepresentativeMatriculationAuthorizationModel())->activeByUserAndPeriod($userId, (int) $period['pleid']) !== false;
    }
}
