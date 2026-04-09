<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;
use RuntimeException;

class RolePermissionModel extends Model
{
    public function allRoles(): array
    {
        $statement = $this->db->query(
            'SELECT rolid, rolnombre, roldescripcion, rolestado
             FROM rol
             ORDER BY rolnombre ASC'
        );

        return $statement->fetchAll();
    }

    public function allPermissions(): array
    {
        $statement = $this->db->query(
            'SELECT prmid, prmnombre, prmcodigo, prmdescripcion, prmestado
             FROM permiso
             ORDER BY prmnombre ASC'
        );

        return $statement->fetchAll();
    }

    public function assignedPermissionIdsByRole(): array
    {
        $statement = $this->db->query(
            'SELECT rolid, prmid
             FROM rol_permiso
             WHERE rpeestado = true'
        );

        $map = [];

        foreach ($statement->fetchAll() as $row) {
            $roleId = (int) $row['rolid'];
            $permissionId = (int) $row['prmid'];
            $map[$roleId][] = $permissionId;
        }

        return $map;
    }

    public function allUsers(string $term = ''): array
    {
        $normalizedTerm = trim($term);

        if ($normalizedTerm === '') {
            $statement = $this->db->query(
                'SELECT u.usuid, u.usunombre, u.usuestado, u.perid, p.pernombres, p.perapellidos, p.percedula
                 FROM usuario u
                 INNER JOIN persona p ON p.perid = u.perid
                 WHERE u.usuestado = true
                 ORDER BY p.perapellidos ASC, p.pernombres ASC'
            );

            return $statement->fetchAll();
        }

        $statement = $this->db->prepare(
            'SELECT u.usuid, u.usunombre, u.usuestado, u.perid, p.pernombres, p.perapellidos, p.percedula
             FROM usuario u
             INNER JOIN persona p ON p.perid = u.perid
             WHERE u.usuestado = true
               AND (
                    u.usunombre ILIKE :term
                    OR p.percedula ILIKE :term
                    OR p.pernombres ILIKE :term
                    OR p.perapellidos ILIKE :term
               )
             ORDER BY p.perapellidos ASC, p.pernombres ASC'
        );
        $statement->execute(['term' => '%' . $normalizedTerm . '%']);

        return $statement->fetchAll();
    }

    public function assignedRoleIdsByUser(): array
    {
        $statement = $this->db->query(
            'SELECT usuid, rolid
             FROM usuario_rol
             WHERE usrestado = true'
        );

        $map = [];

        foreach ($statement->fetchAll() as $row) {
            $userId = (int) $row['usuid'];
            $roleId = (int) $row['rolid'];
            $map[$userId][] = $roleId;
        }

        return $map;
    }

    public function roleExists(int $roleId): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1
             FROM rol
             WHERE rolid = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $roleId]);

        return $statement->fetchColumn() !== false;
    }

    public function userExists(int $userId): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1
             FROM usuario
             WHERE usuid = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $userId]);

        return $statement->fetchColumn() !== false;
    }

    public function validPermissionIds(array $permissionIds): array
    {
        if ($permissionIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($permissionIds), '?'));
        $statement = $this->db->prepare(
            "SELECT prmid
             FROM permiso
             WHERE prmid IN ({$placeholders})"
        );
        $statement->execute($permissionIds);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function syncRolePermissions(int $roleId, array $permissionIds): void
    {
        if (!$this->roleExists($roleId)) {
            throw new RuntimeException('El rol seleccionado no es valido.');
        }

        $permissionIds = array_values(array_unique(array_map('intval', $permissionIds)));
        $validPermissionIds = $this->validPermissionIds($permissionIds);

        if (count($validPermissionIds) !== count($permissionIds)) {
            throw new RuntimeException('Existe al menos un permiso no valido en la asignacion.');
        }

        $this->db->beginTransaction();

        try {
            $deleteStatement = $this->db->prepare(
                'DELETE FROM rol_permiso
                 WHERE rolid = :role_id'
            );
            $deleteStatement->execute(['role_id' => $roleId]);

            if ($validPermissionIds !== []) {
                $insertStatement = $this->db->prepare(
                    'INSERT INTO rol_permiso (rolid, prmid, rpeestado)
                     VALUES (:role_id, :permission_id, true)'
                );

                foreach ($validPermissionIds as $permissionId) {
                    $insertStatement->execute([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function validRoleIds(array $roleIds): array
    {
        if ($roleIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($roleIds), '?'));
        $statement = $this->db->prepare(
            "SELECT rolid
             FROM rol
             WHERE rolid IN ({$placeholders})"
        );
        $statement->execute($roleIds);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function syncUserRoles(int $userId, array $roleIds): void
    {
        if (!$this->userExists($userId)) {
            throw new RuntimeException('El usuario seleccionado no es valido.');
        }

        $roleIds = array_values(array_unique(array_map('intval', $roleIds)));
        $validRoleIds = $this->validRoleIds($roleIds);

        if (count($validRoleIds) !== count($roleIds)) {
            throw new RuntimeException('Existe al menos un rol no valido en la asignacion.');
        }

        $this->db->beginTransaction();

        try {
            $deleteStatement = $this->db->prepare(
                'DELETE FROM usuario_rol
                 WHERE usuid = :user_id'
            );
            $deleteStatement->execute(['user_id' => $userId]);

            if ($validRoleIds !== []) {
                $insertStatement = $this->db->prepare(
                    'INSERT INTO usuario_rol (usuid, rolid, usrestado)
                     VALUES (:user_id, :role_id, true)'
                );

                foreach ($validRoleIds as $roleId) {
                    $insertStatement->execute([
                        'user_id' => $userId,
                        'role_id' => $roleId,
                    ]);
                }
            }

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }
}
