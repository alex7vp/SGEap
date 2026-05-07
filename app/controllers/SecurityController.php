<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\MatriculationConfigurationModel;
use App\Models\PersonalModel;
use App\Models\RepresentativeMatriculationAuthorizationModel;
use App\Models\RolePermissionModel;
use App\Models\SecurityCatalogModel;
use App\Models\StudentModel;
use App\Models\TemporaryUserModel;
use App\Models\UserModel;

class SecurityController extends Controller
{
    public function catalogs(): void
    {
        $user = $this->requireAuth();
        $catalogModel = new SecurityCatalogModel();
        $catalogFeedback = $this->catalogFeedback();

        $this->view('seguridad.catalogos', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Catalogos de seguridad',
            'currentModule' => 'seguridad',
            'currentSection' => 'seguridad_catalogos',
            'user' => $user,
            'catalogs' => $catalogModel->allCatalogs(),
            'catalogFeedback' => $catalogFeedback,
        ]);
    }

    public function users(): void
    {
        $user = $this->requireAuth();
        $userModel = new UserModel();

        $this->view('seguridad.usuarios', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Usuarios',
            'currentModule' => 'seguridad',
            'currentSection' => 'seguridad_usuarios',
            'user' => $user,
            'users' => [],
            'availablePersons' => $userModel->allWithoutUser(),
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
            'userListFeedback' => $this->userListFeedback(),
            'old' => [
                'perid' => sessionFlash('old_user_perid') ?? '',
                'usunombre' => sessionFlash('old_user_usunombre') ?? '',
                'usuclave' => '',
                'usuestado' => sessionFlash('old_user_usuestado') ?? '1',
            ],
        ]);
    }

    public function searchUsers(): void
    {
        $this->requireAuth();

        $term = trim($_GET['q'] ?? '');
        $statusFilter = trim((string) ($_GET['estado'] ?? ''));
        $userModel = new UserModel();
        $status = match ($statusFilter) {
            'activo' => true,
            'inactivo' => false,
            default => null,
        };

        if (mb_strlen($term) < 2 && $status === null) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'html' => '',
                'isEmpty' => true,
                'emptyHtml' => '<div class="empty-state">Escriba al menos 2 caracteres o seleccione un estado para consultar usuarios.</div>',
                'count' => 0,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        $users = $userModel->allDetailed($term, $status, 50);

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'html' => $this->renderUserRows($users),
            'isEmpty' => empty($users),
            'emptyHtml' => '<div class="empty-state">No se encontraron usuarios con ese filtro.</div>',
            'count' => count($users),
            'limited' => count($users) >= 50,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function searchAvailablePersons(): void
    {
        $this->requireAuth();

        $term = trim($_GET['q'] ?? '');
        $userModel = new UserModel();
        $persons = $userModel->allWithoutUser($term);

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'items' => array_map(
                static fn(array $person): array => [
                    'id' => (int) $person['perid'],
                    'label' => (string) $person['percedula'] . ' | ' . $person['perapellidos'] . ' ' . $person['pernombres'],
                    'cedula' => (string) $person['percedula'],
                    'nombres' => (string) $person['pernombres'],
                    'apellidos' => (string) $person['perapellidos'],
                ],
                $persons
            ),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function searchRepresentativeNewStudentAuthorizations(): void
    {
        $this->requireAuth();

        $term = trim((string) ($_GET['q'] ?? ''));
        $enabledPeriod = (new MatriculationConfigurationModel())->findEnabledPeriod();
        $enabledPeriodId = $enabledPeriod !== false ? (int) $enabledPeriod['pleid'] : null;

        if ($enabledPeriodId === null) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'html' => '',
                'isEmpty' => true,
                'emptyHtml' => '<div class="empty-state">No existe un periodo lectivo con matricula habilitada.</div>',
                'count' => 0,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        if (mb_strlen($term) < 2) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'html' => '',
                'isEmpty' => true,
                'emptyHtml' => '<div class="empty-state">Escriba al menos 2 caracteres para buscar representantes activos.</div>',
                'count' => 0,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        $representatives = (new RepresentativeMatriculationAuthorizationModel())
            ->allRepresentativesWithAuthorization($enabledPeriodId, $term, 50);

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'html' => $this->renderRepresentativeAuthorizationRows($representatives),
            'isEmpty' => empty($representatives),
            'emptyHtml' => '<div class="empty-state">No se encontraron representantes activos con ese filtro.</div>',
            'count' => count($representatives),
            'limited' => count($representatives) >= 50,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function temporaryUsers(): void
    {
        $user = $this->requireAuth();
        $userModel = new UserModel();
        $temporaryUserModel = new TemporaryUserModel();
        $authorizationModel = new RepresentativeMatriculationAuthorizationModel();
        $enabledPeriod = (new MatriculationConfigurationModel())->findEnabledPeriod();
        $enabledPeriodId = $enabledPeriod !== false ? (int) $enabledPeriod['pleid'] : null;
        $defaultExpiration = (new \DateTimeImmutable('+30 days'))->format('Y-m-d\TH:i');

        $this->view('seguridad.usuarios_temporales', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Usuarios temporales',
            'currentModule' => 'seguridad',
            'currentSection' => 'seguridad_usuarios_temporales',
            'user' => $user,
            'enabledPeriod' => $enabledPeriod !== false ? $enabledPeriod : null,
            'availablePersons' => $userModel->allWithoutUser(),
            'temporaryUsers' => $temporaryUserModel->allDetailed(),
            'representatives' => [],
            'feedback' => $this->temporaryUserFeedback(),
            'old' => [
                'perid' => sessionFlash('old_temp_user_perid') ?? '',
                'usunombre' => sessionFlash('old_temp_user_usunombre') ?? '',
                'usuclave' => '',
                'utfecha_expiracion' => sessionFlash('old_temp_user_expiration') ?? $defaultExpiration,
            ],
        ]);
    }

    public function storeTemporaryUser(): void
    {
        $this->requireAuth();

        $data = $this->temporaryUserFormData();
        $userModel = new UserModel();

        if ($data['perid'] <= 0 || $data['usunombre'] === '' || $data['usuclave'] === '' || $data['utfecha_expiracion'] === '') {
            $this->flashTemporaryUserFormData($data);
            $this->flashTemporaryUserFeedback('error', 'Persona, usuario, clave y fecha de expiracion son obligatorios.');
            $this->redirect('/seguridad/usuarios-temporales');
        }

        if (mb_strlen($data['usuclave']) < 6) {
            $this->flashTemporaryUserFormData($data);
            $this->flashTemporaryUserFeedback('error', 'La clave temporal debe tener al menos 6 caracteres.');
            $this->redirect('/seguridad/usuarios-temporales');
        }

        $expiration = $this->parseTemporaryExpiration($data['utfecha_expiracion']);

        if ($expiration === null || $expiration <= new \DateTimeImmutable()) {
            $this->flashTemporaryUserFormData($data);
            $this->flashTemporaryUserFeedback('error', 'La fecha de expiracion debe ser posterior a la fecha actual.');
            $this->redirect('/seguridad/usuarios-temporales');
        }

        if ($userModel->existsByPerson($data['perid'])) {
            $this->flashTemporaryUserFormData($data);
            $this->flashTemporaryUserFeedback('error', 'La persona seleccionada ya tiene un usuario asignado.');
            $this->redirect('/seguridad/usuarios-temporales');
        }

        if ($userModel->existsByUsername($data['usunombre'])) {
            $this->flashTemporaryUserFormData($data);
            $this->flashTemporaryUserFeedback('error', 'El nombre de usuario ya esta registrado.');
            $this->redirect('/seguridad/usuarios-temporales');
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $userId = $userModel->createAndReturnId([
                'perid' => $data['perid'],
                'usunombre' => $data['usunombre'],
                'usuclave' => $data['usuclave'],
                'usuestado' => true,
            ]);

            $temporaryUserModel = new TemporaryUserModel();
            $temporaryUserModel->create($userId, $expiration);
            $userModel->assignRoleToUser($userId, 'Representante temporal');

            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $this->flashTemporaryUserFormData($data);
            $this->flashTemporaryUserFeedback('error', 'No se pudo crear el usuario temporal.');
            $this->redirect('/seguridad/usuarios-temporales');
            return;
        }

        $this->flashTemporaryUserFeedback('success', 'Usuario temporal creado correctamente.');
        $this->redirect('/seguridad/usuarios-temporales');
    }

    public function resetTemporaryUserPassword(): void
    {
        $this->requireAuth();

        $userId = (int) ($_POST['usuid'] ?? 0);
        $temporaryPassword = trim((string) ($_POST['usuclave_temporal'] ?? ''));

        if ($userId <= 0 || $temporaryPassword === '') {
            $this->flashTemporaryUserFeedback('error', 'Seleccione un usuario temporal e ingrese una clave.');
            $this->redirect('/seguridad/usuarios-temporales#usuarios-temporales-listado');
        }

        if (mb_strlen($temporaryPassword) < 6) {
            $this->flashTemporaryUserFeedback('error', 'La clave temporal debe tener al menos 6 caracteres.');
            $this->redirect('/seguridad/usuarios-temporales#usuarios-temporales-listado');
        }

        $temporaryUserModel = new TemporaryUserModel();

        if (!$temporaryUserModel->existsByUser($userId, ['ACTIVO', 'EXPIRADO'])) {
            $this->flashTemporaryUserFeedback('error', 'El usuario temporal seleccionado no existe o ya no puede administrarse.');
            $this->redirect('/seguridad/usuarios-temporales#usuarios-temporales-listado');
        }

        $userModel = new UserModel();
        $userModel->resetPassword($userId, $temporaryPassword);
        $this->flashTemporaryUserFeedback('success', 'Clave temporal restablecida correctamente.');
        $this->redirect('/seguridad/usuarios-temporales#usuarios-temporales-listado');
    }

    public function extendTemporaryUser(): void
    {
        $this->requireAuth();

        $userId = (int) ($_POST['usuid'] ?? 0);
        $expiration = $this->parseTemporaryExpiration(trim((string) ($_POST['utfecha_expiracion'] ?? '')));

        if ($userId <= 0 || $expiration === null || $expiration <= new \DateTimeImmutable()) {
            $this->flashTemporaryUserFeedback('error', 'Seleccione un usuario temporal y una fecha de expiracion futura.');
            $this->redirect('/seguridad/usuarios-temporales#usuarios-temporales-listado');
        }

        $temporaryUserModel = new TemporaryUserModel();
        if (!$temporaryUserModel->existsByUser($userId, ['ACTIVO', 'EXPIRADO'])) {
            $this->flashTemporaryUserFeedback('error', 'El usuario temporal seleccionado no existe o ya no puede administrarse.');
            $this->redirect('/seguridad/usuarios-temporales#usuarios-temporales-listado');
        }

        $temporaryUserModel->updateExpiration($userId, $expiration);
        $this->flashTemporaryUserFeedback('success', 'Vigencia del usuario temporal actualizada correctamente.');
        $this->redirect('/seguridad/usuarios-temporales#usuarios-temporales-listado');
    }

    public function deleteTemporaryUser(): void
    {
        $this->requireAuth();

        $userId = (int) ($_POST['usuid'] ?? 0);
        $reason = trim((string) ($_POST['utmotivo_eliminacion'] ?? ''));

        if ($userId <= 0) {
            $this->flashTemporaryUserFeedback('error', 'El usuario temporal seleccionado no es valido.');
            $this->redirect('/seguridad/usuarios-temporales#usuarios-temporales-listado');
        }

        $temporaryUserModel = new TemporaryUserModel();
        if (!$temporaryUserModel->existsByUser($userId, ['ACTIVO', 'EXPIRADO'])) {
            $this->flashTemporaryUserFeedback('error', 'El usuario temporal seleccionado no existe o ya no puede administrarse.');
            $this->redirect('/seguridad/usuarios-temporales#usuarios-temporales-listado');
        }

        $temporaryUserModel->deleteAccess($userId, $reason);
        $this->flashTemporaryUserFeedback('success', 'Acceso temporal anulado correctamente.');
        $this->redirect('/seguridad/usuarios-temporales#usuarios-temporales-listado');
    }

    public function enableRepresentativeNewStudent(): void
    {
        $user = $this->requireAuth();

        $representativeUserId = (int) ($_POST['usuid'] ?? 0);
        $expiration = $this->parseTemporaryExpiration(trim((string) ($_POST['rhmfecha_expiracion'] ?? '')));
        $observation = trim((string) ($_POST['rhmobservacion'] ?? ''));
        $period = (new MatriculationConfigurationModel())->findEnabledPeriod();

        if ($representativeUserId <= 0) {
            $this->flashTemporaryUserFeedback('error', 'Seleccione un representante valido.');
            $this->redirect('/seguridad/usuarios-temporales#representantes-habilitacion');
        }

        if ($period === false) {
            $this->flashTemporaryUserFeedback('error', 'No existe un periodo lectivo con matricula habilitada.');
            $this->redirect('/seguridad/usuarios-temporales#representantes-habilitacion');
        }

        if ($expiration !== null && $expiration <= new \DateTimeImmutable()) {
            $this->flashTemporaryUserFeedback('error', 'La fecha de expiracion debe ser posterior a la fecha actual.');
            $this->redirect('/seguridad/usuarios-temporales#representantes-habilitacion');
        }

        $userModel = new UserModel();

        if (!$userModel->userHasRole($representativeUserId, 'Representante')) {
            $this->flashTemporaryUserFeedback('error', 'El usuario seleccionado no tiene rol de representante formal.');
            $this->redirect('/seguridad/usuarios-temporales#representantes-habilitacion');
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $authorizationModel = new RepresentativeMatriculationAuthorizationModel();
            $authorizationModel->createForUser(
                $representativeUserId,
                (int) $period['pleid'],
                (int) ($user['usuid'] ?? 0),
                $expiration,
                $observation
            );

            $userModel->assignRoleToUser($representativeUserId, 'Representante matricula nueva');

            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $this->flashTemporaryUserFeedback('error', $exception->getMessage());
            $this->redirect('/seguridad/usuarios-temporales#representantes-habilitacion');
        }

        $this->flashTemporaryUserFeedback('success', 'Representante habilitado para matricular un nuevo estudiante.');
        $this->redirect('/seguridad/usuarios-temporales#representantes-habilitacion');
    }

    public function annulRepresentativeNewStudent(): void
    {
        $user = $this->requireAuth();

        $authorizationId = (int) ($_POST['rhmid'] ?? 0);
        $representativePersonId = (int) ($_POST['perid'] ?? 0);
        $reason = trim((string) ($_POST['rhmmotivo_anulacion'] ?? ''));

        if ($authorizationId <= 0 || $representativePersonId <= 0) {
            $this->flashTemporaryUserFeedback('error', 'La habilitacion seleccionada no es valida.');
            $this->redirect('/seguridad/usuarios-temporales#representantes-habilitacion');
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            (new RepresentativeMatriculationAuthorizationModel())->annul(
                $authorizationId,
                (int) ($user['usuid'] ?? 0),
                $reason
            );

            (new UserModel())->syncRoleByPerson($representativePersonId, 'Representante matricula nueva', false);

            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $this->flashTemporaryUserFeedback('error', $exception->getMessage());
            $this->redirect('/seguridad/usuarios-temporales#representantes-habilitacion');
        }

        $this->flashTemporaryUserFeedback('success', 'Habilitacion anulada correctamente.');
        $this->redirect('/seguridad/usuarios-temporales#representantes-habilitacion');
    }

    public function storeUser(): void
    {
        $this->requireAuth();

        $data = $this->userFormData();
        $userModel = new UserModel();

        if ($data['perid'] <= 0 || $data['usunombre'] === '' || $data['usuclave'] === '') {
            $this->flashUserFormData($data);
            sessionFlash('error', 'Persona, usuario y clave son obligatorios.');
            $this->redirect('/seguridad/usuarios');
        }

        if ($userModel->existsByPerson($data['perid'])) {
            $this->flashUserFormData($data);
            sessionFlash('error', 'La persona seleccionada ya tiene un usuario asignado.');
            $this->redirect('/seguridad/usuarios');
        }

        if ($userModel->existsByUsername($data['usunombre'])) {
            $this->flashUserFormData($data);
            sessionFlash('error', 'El nombre de usuario ya esta registrado.');
            $this->redirect('/seguridad/usuarios');
        }

        $userId = $userModel->createAndReturnId($data);

        $personalModel = new PersonalModel();

        foreach ($personalModel->roleNamesForPersonStaffTypes($data['perid']) as $roleName) {
            $userModel->assignRoleToUser($userId, $roleName);
        }

        $studentModel = new StudentModel();

        if ($studentModel->personIsStudent($data['perid'])) {
            $userModel->assignRoleToUser($userId, 'Estudiante');
        }

        sessionFlash('success', 'Usuario asignado correctamente.');
        $this->redirect('/seguridad/usuarios');
    }

    public function toggleUserStatus(): void
    {
        $this->requireAuth();

        $userId = (int) ($_POST['usuid'] ?? 0);
        $status = ($_POST['usuestado'] ?? '0') === '1';
        $userModel = new UserModel();

        if ($userId <= 0) {
            $this->flashUserListFeedback('error', 'El usuario seleccionado no es valido.');
            $this->redirect('/seguridad/usuarios#usuarios-asignados');
        }

        $selectedUser = $userModel->userWithPerson($userId);

        if ($selectedUser === false) {
            $this->flashUserListFeedback('error', 'El usuario solicitado no existe.');
            $this->redirect('/seguridad/usuarios#usuarios-asignados');
        }

        $db = Database::connection();

        try {
            $db->beginTransaction();

            $userModel->updateStatus($userId, $status);

            $studentModel = new StudentModel();

            if ($studentModel->personIsStudent((int) $selectedUser['perid'])) {
                $userModel->syncRepresentativesForStudentPerson((int) $selectedUser['perid'], $status);
            }

            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $this->flashUserListFeedback('error', 'No se pudo actualizar el estado del usuario.');
            $this->redirect('/seguridad/usuarios#usuarios-asignados');
        }

        $this->flashUserListFeedback('success', 'Estado del usuario actualizado correctamente.');
        $this->redirect('/seguridad/usuarios#usuarios-asignados');
    }

    public function resetUserPassword(): void
    {
        $this->requireAuth();

        $userId = (int) ($_POST['usuid'] ?? 0);
        $temporaryPassword = trim((string) ($_POST['usuclave_temporal'] ?? ''));
        $userModel = new UserModel();

        if ($userId <= 0) {
            $this->flashUserListFeedback('error', 'El usuario seleccionado no es valido.');
            $this->redirect('/seguridad/usuarios#usuarios-asignados');
        }

        if ($temporaryPassword === '') {
            $this->flashUserListFeedback('error', 'Ingrese una clave temporal para restablecer el acceso.');
            $this->redirect('/seguridad/usuarios#usuarios-asignados');
        }

        if (mb_strlen($temporaryPassword) < 6) {
            $this->flashUserListFeedback('error', 'La clave temporal debe tener al menos 6 caracteres.');
            $this->redirect('/seguridad/usuarios#usuarios-asignados');
        }

        if ($userModel->userWithPerson($userId) === false) {
            $this->flashUserListFeedback('error', 'El usuario solicitado no existe.');
            $this->redirect('/seguridad/usuarios#usuarios-asignados');
        }

        $userModel->resetPassword($userId, $temporaryPassword);
        $this->flashUserListFeedback('success', 'Clave temporal restablecida. Indiquela al usuario y solicite que la cambie al ingresar.');
        $this->redirect('/seguridad/usuarios#usuarios-asignados');
    }

    public function rolePermissions(): void
    {
        $user = $this->requireAuth();
        $rolePermissionModel = new RolePermissionModel();

        $this->view('seguridad.roles_permisos', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Designacion de permisos',
            'currentModule' => 'seguridad',
            'currentSection' => 'seguridad_roles_permisos',
            'user' => $user,
            'roles' => $rolePermissionModel->allRoles(),
            'permissions' => $rolePermissionModel->allPermissions(),
            'assignedPermissions' => $rolePermissionModel->assignedPermissionIdsByRole(),
            'assignmentFeedback' => $this->assignmentFeedback(),
        ]);
    }

    public function userRoles(): void
    {
        $user = $this->requireAuth();
        $rolePermissionModel = new RolePermissionModel();
        $selectedRole = trim((string) ($_GET['rol'] ?? ''));

        $this->view('seguridad.roles_usuarios', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Roles por usuario',
            'currentModule' => 'seguridad',
            'currentSection' => 'seguridad_usuarios_roles',
            'user' => $user,
            'roles' => $rolePermissionModel->allRoles(),
            'users' => $rolePermissionModel->allUsers('', $selectedRole !== '' ? $selectedRole : null),
            'assignedRoles' => $rolePermissionModel->assignedRoleIdsByUser(),
            'staffManagedRoleNames' => $rolePermissionModel->staffManagedRoleNames(),
            'selectedRole' => $selectedRole,
            'userRoleFeedback' => $this->userRoleFeedback(),
        ]);
    }

    public function searchUserRoles(): void
    {
        $this->requireAuth();

        $term = trim($_GET['q'] ?? '');
        $selectedRole = trim((string) ($_GET['rol'] ?? ''));
        $rolePermissionModel = new RolePermissionModel();
        $roles = $rolePermissionModel->allRoles();
        $users = $rolePermissionModel->allUsers($term, $selectedRole !== '' ? $selectedRole : null);

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'html' => $this->renderUserRoleRows($users, $roles, $rolePermissionModel->assignedRoleIdsByUser(), $rolePermissionModel->staffManagedRoleNames()),
            'isEmpty' => empty($users),
            'emptyHtml' => '<div class="empty-state">No se encontraron usuarios con ese filtro.</div>',
            'count' => count($users),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function updateRolePermissions(): void
    {
        $this->requireAuth();

        $roleId = (int) ($_POST['role_id'] ?? 0);
        $permissionIds = array_map('intval', (array) ($_POST['permission_ids'] ?? []));
        $rolePermissionModel = new RolePermissionModel();
        $anchor = 'role-' . $roleId;

        if ($roleId <= 0) {
            $this->flashAssignmentFeedback('error', $roleId, 'El rol seleccionado no es valido.');
            $this->redirectToRolePermissions($anchor);
        }

        try {
            $rolePermissionModel->syncRolePermissions($roleId, $permissionIds);
        } catch (\RuntimeException $exception) {
            $this->flashAssignmentFeedback('error', $roleId, $exception->getMessage());
            $this->redirectToRolePermissions($anchor);
            return;
        }

        $this->flashAssignmentFeedback('success', $roleId, 'Permisos actualizados correctamente para el rol seleccionado.');
        $this->redirectToRolePermissions($anchor);
    }

    public function updateUserRoles(): void
    {
        $this->requireAuth();

        $userId = (int) ($_POST['user_id'] ?? 0);
        $roleIds = array_map('intval', (array) ($_POST['role_ids'] ?? []));
        $rolePermissionModel = new RolePermissionModel();
        $anchor = 'user-' . $userId;

        if ($userId <= 0) {
            $this->flashUserRoleFeedback('error', $userId, 'El usuario seleccionado no es valido.');
            $this->redirectToUserRoles($anchor);
        }

        try {
            $rolePermissionModel->syncUserRoles($userId, $roleIds);
        } catch (\RuntimeException $exception) {
            $this->flashUserRoleFeedback('error', $userId, $exception->getMessage());
            $this->redirectToUserRoles($anchor);
            return;
        }

        $this->flashUserRoleFeedback('success', $userId, 'Roles actualizados correctamente para el usuario seleccionado.');
        $this->redirectToUserRoles($anchor);
    }

    public function storeCatalogItem(): void
    {
        $this->requireAuth();

        $table = trim($_POST['catalog_table'] ?? '');
        $anchor = trim($_POST['redirect_anchor'] ?? '');
        $catalogModel = new SecurityCatalogModel();

        try {
            $catalog = $catalogModel->getCatalog($table);
            $payload = $catalogModel->sanitizePayload($table, $_POST);
        } catch (\RuntimeException) {
            $this->flashCatalogFeedback('error', $table, 'El catalogo solicitado no es valido.');
            $this->redirectToCatalogs($anchor);
            return;
        }

        $validationMessage = $catalogModel->validatePayload($table, $payload);

        if ($validationMessage !== null) {
            $this->flashCatalogFeedback('error', $table, $validationMessage);
            $this->redirectToCatalogs($anchor);
        }

        $catalogModel->createItem($table, $payload);
        $this->flashCatalogFeedback('success', $table, 'Registro agregado correctamente en ' . strtolower((string) $catalog['label']) . '.');
        $this->redirectToCatalogs($anchor);
    }

    public function updateCatalogItem(): void
    {
        $this->requireAuth();

        $table = trim($_POST['catalog_table'] ?? '');
        $id = (int) ($_POST['catalog_id'] ?? 0);
        $anchor = trim($_POST['redirect_anchor'] ?? '');
        $catalogModel = new SecurityCatalogModel();

        if ($id <= 0) {
            $this->flashCatalogFeedback('error', $table, 'El registro a actualizar no es valido.');
            $this->redirectToCatalogs($anchor);
        }

        try {
            $catalog = $catalogModel->getCatalog($table);
            $payload = $catalogModel->sanitizePayload($table, $_POST);
        } catch (\RuntimeException) {
            $this->flashCatalogFeedback('error', $table, 'El catalogo solicitado no es valido.');
            $this->redirectToCatalogs($anchor);
            return;
        }

        $validationMessage = $catalogModel->validatePayload($table, $payload, $id);

        if ($validationMessage !== null) {
            $this->flashCatalogFeedback('error', $table, $validationMessage);
            $this->redirectToCatalogs($anchor);
        }

        $catalogModel->updateItem($table, $id, $payload);
        $this->flashCatalogFeedback('success', $table, 'Registro actualizado correctamente en ' . strtolower((string) $catalog['label']) . '.');
        $this->redirectToCatalogs($anchor);
    }

    public function deleteCatalogItem(): void
    {
        $this->requireAuth();

        $table = trim($_POST['catalog_table'] ?? '');
        $id = (int) ($_POST['catalog_id'] ?? 0);
        $anchor = trim($_POST['redirect_anchor'] ?? '');
        $catalogModel = new SecurityCatalogModel();

        if ($id <= 0) {
            $this->flashCatalogFeedback('error', $table, 'El registro a eliminar no es valido.');
            $this->redirectToCatalogs($anchor);
        }

        try {
            $catalog = $catalogModel->getCatalog($table);
        } catch (\RuntimeException) {
            $this->flashCatalogFeedback('error', $table, 'El catalogo solicitado no es valido.');
            $this->redirectToCatalogs($anchor);
            return;
        }

        if (!$catalogModel->deleteItem($table, $id)) {
            $this->flashCatalogFeedback('error', $table, 'No se pudo eliminar el registro de ' . strtolower((string) $catalog['label']) . '. Revise si esta siendo usado por otros modulos.');
            $this->redirectToCatalogs($anchor);
        }

        $this->flashCatalogFeedback('success', $table, 'Registro eliminado correctamente de ' . strtolower((string) $catalog['label']) . '.');
        $this->redirectToCatalogs($anchor);
    }

    private function flashCatalogFeedback(string $type, string $table, string $message): void
    {
        sessionFlash('security_catalog_feedback_type', $type);
        sessionFlash('security_catalog_feedback_table', $table);
        sessionFlash('security_catalog_feedback_message', $message);
    }

    private function catalogFeedback(): ?array
    {
        $type = sessionFlash('security_catalog_feedback_type');
        $table = sessionFlash('security_catalog_feedback_table');
        $message = sessionFlash('security_catalog_feedback_message');

        if ($type === null || $table === null || $message === null) {
            return null;
        }

        return [
            'type' => $type,
            'table' => $table,
            'message' => $message,
        ];
    }

    private function flashAssignmentFeedback(string $type, int $roleId, string $message): void
    {
        sessionFlash('security_assignment_feedback_type', $type);
        sessionFlash('security_assignment_feedback_role', (string) $roleId);
        sessionFlash('security_assignment_feedback_message', $message);
    }

    private function assignmentFeedback(): ?array
    {
        $type = sessionFlash('security_assignment_feedback_type');
        $roleId = sessionFlash('security_assignment_feedback_role');
        $message = sessionFlash('security_assignment_feedback_message');

        if ($type === null || $roleId === null || $message === null) {
            return null;
        }

        return [
            'type' => $type,
            'role_id' => (int) $roleId,
            'message' => $message,
        ];
    }

    private function flashUserRoleFeedback(string $type, int $userId, string $message): void
    {
        sessionFlash('security_user_role_feedback_type', $type);
        sessionFlash('security_user_role_feedback_user', (string) $userId);
        sessionFlash('security_user_role_feedback_message', $message);
    }

    private function userRoleFeedback(): ?array
    {
        $type = sessionFlash('security_user_role_feedback_type');
        $userId = sessionFlash('security_user_role_feedback_user');
        $message = sessionFlash('security_user_role_feedback_message');

        if ($type === null || $userId === null || $message === null) {
            return null;
        }

        return [
            'type' => $type,
            'user_id' => (int) $userId,
            'message' => $message,
        ];
    }

    private function redirectToCatalogs(string $anchor = ''): void
    {
        $path = '/seguridad/catalogos';

        if ($anchor !== '') {
            $path .= '#' . ltrim($anchor, '#');
        }

        $this->redirect($path);
    }

    private function redirectToRolePermissions(string $anchor = ''): void
    {
        $path = '/seguridad/roles-permisos';

        if ($anchor !== '') {
            $path .= '#' . ltrim($anchor, '#');
        }

        $this->redirect($path);
    }

    private function redirectToUserRoles(string $anchor = ''): void
    {
        $path = '/seguridad/usuarios-roles';

        if ($anchor !== '') {
            $path .= '#' . ltrim($anchor, '#');
        }

        $this->redirect($path);
    }

    private function renderUserRoleRows(array $users, array $roles, array $assignedRoles, array $staffManagedRoleNames = []): string
    {
        ob_start();
        require BASE_PATH . '/app/views/seguridad/_user_role_rows.php';
        return (string) ob_get_clean();
    }

    private function renderUserRows(array $users): string
    {
        ob_start();
        require BASE_PATH . '/app/views/seguridad/_users_rows.php';
        return (string) ob_get_clean();
    }

    private function renderRepresentativeAuthorizationRows(array $representatives): string
    {
        $formatDateTime = static function (?string $value, string $format): string {
            if ($value === null || $value === '') {
                return '';
            }

            $timestamp = strtotime($value);

            return $timestamp === false ? '' : date($format, $timestamp);
        };

        ob_start();
        require BASE_PATH . '/app/views/seguridad/_representative_authorization_rows.php';
        return (string) ob_get_clean();
    }

    private function temporaryUserFormData(): array
    {
        return [
            'perid' => (int) ($_POST['perid'] ?? 0),
            'usunombre' => trim((string) ($_POST['usunombre'] ?? '')),
            'usuclave' => trim((string) ($_POST['usuclave'] ?? '')),
            'utfecha_expiracion' => trim((string) ($_POST['utfecha_expiracion'] ?? '')),
        ];
    }

    private function flashTemporaryUserFormData(array $data): void
    {
        sessionFlash('old_temp_user_perid', (string) $data['perid']);
        sessionFlash('old_temp_user_usunombre', (string) $data['usunombre']);
        sessionFlash('old_temp_user_expiration', (string) $data['utfecha_expiracion']);
    }

    private function parseTemporaryExpiration(string $value): ?\DateTimeImmutable
    {
        if ($value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable(str_replace('T', ' ', $value));
        } catch (\Exception) {
            return null;
        }
    }

    private function flashTemporaryUserFeedback(string $type, string $message): void
    {
        sessionFlash('temporary_user_feedback_type', $type);
        sessionFlash('temporary_user_feedback_message', $message);
    }

    private function temporaryUserFeedback(): ?array
    {
        $type = sessionFlash('temporary_user_feedback_type');
        $message = sessionFlash('temporary_user_feedback_message');

        if ($type === null || $message === null) {
            return null;
        }

        return [
            'type' => $type,
            'message' => $message,
        ];
    }

    private function userFormData(): array
    {
        return [
            'perid' => (int) ($_POST['perid'] ?? 0),
            'usunombre' => trim($_POST['usunombre'] ?? ''),
            'usuclave' => trim($_POST['usuclave'] ?? ''),
            'usuestado' => ($_POST['usuestado'] ?? '1') === '1',
        ];
    }

    private function flashUserFormData(array $data): void
    {
        sessionFlash('old_user_perid', (string) $data['perid']);
        sessionFlash('old_user_usunombre', (string) $data['usunombre']);
        sessionFlash('old_user_usuestado', $data['usuestado'] ? '1' : '0');
    }

    private function flashUserListFeedback(string $type, string $message): void
    {
        sessionFlash('security_user_list_feedback_type', $type);
        sessionFlash('security_user_list_feedback_message', $message);
    }

    private function userListFeedback(): ?array
    {
        $type = sessionFlash('security_user_list_feedback_type');
        $message = sessionFlash('security_user_list_feedback_message');

        if ($type === null || $message === null) {
            return null;
        }

        return [
            'type' => $type,
            'message' => $message,
        ];
    }
}
