<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\RolePermissionModel;
use App\Models\SecurityCatalogModel;
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
            'users' => $userModel->allDetailed(),
            'availablePersons' => $userModel->allWithoutUser(),
            'success' => sessionFlash('success'),
            'error' => sessionFlash('error'),
            'userListFeedback' => $this->userListFeedback(),
            'old' => [
                'perid' => sessionFlash('old_user_perid') ?? '',
                'usunombre' => sessionFlash('old_user_usunombre') ?? '',
                'usuclave' => sessionFlash('old_user_usuclave') ?? '',
                'usuestado' => sessionFlash('old_user_usuestado') ?? '1',
            ],
        ]);
    }

    public function searchUsers(): void
    {
        $this->requireAuth();

        $term = trim($_GET['q'] ?? '');
        $userModel = new UserModel();
        $users = $userModel->allDetailed($term);

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'html' => $this->renderUserRows($users),
            'isEmpty' => empty($users),
            'emptyHtml' => '<div class="empty-state">No se encontraron usuarios con ese filtro.</div>',
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

        $userModel->create($data);
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

        if ($userModel->userWithPerson($userId) === false) {
            $this->flashUserListFeedback('error', 'El usuario solicitado no existe.');
            $this->redirect('/seguridad/usuarios#usuarios-asignados');
        }

        $userModel->updateStatus($userId, $status);
        $this->flashUserListFeedback('success', 'Estado del usuario actualizado correctamente.');
        $this->redirect('/seguridad/usuarios#usuarios-asignados');
    }

    public function rolePermissions(): void
    {
        $user = $this->requireAuth();
        $rolePermissionModel = new RolePermissionModel();
        $roles = $rolePermissionModel->allRoles();
        $users = $rolePermissionModel->allUsers();

        $this->view('seguridad.roles_permisos', [
            'appName' => config('app')['name'] ?? 'SGEap',
            'pageTitle' => 'Designacion de permisos',
            'currentModule' => 'seguridad',
            'currentSection' => 'seguridad_roles_permisos',
            'user' => $user,
            'roles' => $roles,
            'permissions' => $rolePermissionModel->allPermissions(),
            'assignedPermissions' => $rolePermissionModel->assignedPermissionIdsByRole(),
            'users' => $users,
            'assignedRoles' => $rolePermissionModel->assignedRoleIdsByUser(),
            'assignmentFeedback' => $this->assignmentFeedback(),
            'userRoleFeedback' => $this->userRoleFeedback(),
        ]);
    }

    public function searchUserRoles(): void
    {
        $this->requireAuth();

        $term = trim($_GET['q'] ?? '');
        $rolePermissionModel = new RolePermissionModel();
        $roles = $rolePermissionModel->allRoles();
        $users = $rolePermissionModel->allUsers($term);

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'html' => $this->renderUserRoleRows($users, $roles, $rolePermissionModel->assignedRoleIdsByUser()),
            'isEmpty' => empty($users),
            'emptyHtml' => '<div class="empty-state">No se encontraron usuarios con ese filtro.</div>',
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
            $this->redirectToRolePermissions($anchor);
        }

        try {
            $rolePermissionModel->syncUserRoles($userId, $roleIds);
        } catch (\RuntimeException $exception) {
            $this->flashUserRoleFeedback('error', $userId, $exception->getMessage());
            $this->redirectToRolePermissions($anchor);
            return;
        }

        $this->flashUserRoleFeedback('success', $userId, 'Roles actualizados correctamente para el usuario seleccionado.');
        $this->redirectToRolePermissions($anchor);
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

    private function renderUserRoleRows(array $users, array $roles, array $assignedRoles): string
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
        sessionFlash('old_user_usuclave', (string) $data['usuclave']);
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
