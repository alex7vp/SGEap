<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDOException;

class UserModel extends Model
{
    protected string $table = 'usuario';
    protected string $primaryKey = 'usuid';

    public function findActiveByUsername(string $username): array|false
    {
        $statement = $this->db->prepare(
            "SELECT u.usuid, u.perid, u.usunombre, u.usuclave, u.usuestado, p.pernombres, p.perapellidos
             FROM {$this->table} u
             INNER JOIN persona p ON p.perid = u.perid
             LEFT JOIN usuario_temporal ut ON ut.usuid = u.usuid
             WHERE usunombre = :username
               AND usuestado = true
               AND (
                    ut.utid IS NULL
                    OR (
                        ut.utestado = 'ACTIVO'
                        AND ut.utfecha_expiracion >= CURRENT_TIMESTAMP
                    )
               )
             LIMIT 1"
        );
        $statement->execute(['username' => $username]);

        return $statement->fetch();
    }

    public function updateLastAccess(int $userId): void
    {
        $statement = $this->db->prepare(
            "UPDATE {$this->table}
             SET usufecha_modificacion = CURRENT_TIMESTAMP
             WHERE {$this->primaryKey} = :id"
        );
        $statement->execute(['id' => $userId]);
    }

    public function allDetailed(string $term = '', ?bool $status = null, int $limit = 50): array
    {
        $normalizedTerm = trim($term);
        $conditions = [];
        $params = [];

        if ($normalizedTerm === '' && $status === null) {
            return [];
        }

        if ($normalizedTerm !== '') {
            $conditions[] = "(u.usunombre ILIKE :term
                OR p.percedula ILIKE :term
                OR p.pernombres ILIKE :term
                OR p.perapellidos ILIKE :term)";
            $params['term'] = '%' . $normalizedTerm . '%';
        }

        if ($status !== null) {
            $conditions[] = 'u.usuestado = :status';
            $params['status'] = $status;
        }

        $whereSql = $conditions !== [] ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $limit = max(1, min($limit, 100));
        $statement = $this->db->prepare(
            "SELECT u.usuid, u.perid, u.usunombre, u.usuestado, p.percedula, p.pernombres, p.perapellidos
             FROM {$this->table} u
             INNER JOIN persona p ON p.perid = u.perid
             {$whereSql}
             ORDER BY p.perapellidos ASC, p.pernombres ASC
             LIMIT {$limit}"
        );
        foreach ($params as $key => $value) {
            if ($key === 'status') {
                $statement->bindValue(':' . $key, (bool) $value, \PDO::PARAM_BOOL);
                continue;
            }

            $statement->bindValue(':' . $key, $value, \PDO::PARAM_STR);
        }
        $statement->execute();

        return $statement->fetchAll();
    }

    public function allWithoutUser(string $term = ''): array
    {
        $normalizedTerm = trim($term);

        if ($normalizedTerm === '') {
            $statement = $this->db->query(
                "SELECT p.perid, p.percedula, p.pernombres, p.perapellidos
                 FROM persona p
                 LEFT JOIN {$this->table} u ON u.perid = p.perid
                 WHERE u.usuid IS NULL
                 ORDER BY p.perapellidos ASC, p.pernombres ASC
                 LIMIT 20"
            );

            return $statement->fetchAll();
        }

        $statement = $this->db->prepare(
            "SELECT p.perid, p.percedula, p.pernombres, p.perapellidos
             FROM persona p
             LEFT JOIN {$this->table} u ON u.perid = p.perid
             WHERE u.usuid IS NULL
               AND (
                    p.percedula ILIKE :term
                    OR p.pernombres ILIKE :term
                    OR p.perapellidos ILIKE :term
               )
             ORDER BY p.perapellidos ASC, p.pernombres ASC
             LIMIT 20"
        );
        $statement->execute(['term' => '%' . $normalizedTerm . '%']);

        return $statement->fetchAll();
    }

    public function existsByUsername(string $username, ?int $exceptId = null): bool
    {
        $sql =
            "SELECT 1
             FROM {$this->table}
             WHERE usunombre = :username";

        $params = ['username' => $username];

        if ($exceptId !== null) {
            $sql .= " AND {$this->primaryKey} <> :id";
            $params['id'] = $exceptId;
        }

        $sql .= ' LIMIT 1';

        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchColumn() !== false;
    }

    public function existsByPerson(int $personId): bool
    {
        $statement = $this->db->prepare(
            "SELECT 1
             FROM {$this->table}
             WHERE perid = :perid
             LIMIT 1"
        );
        $statement->execute(['perid' => $personId]);

        return $statement->fetchColumn() !== false;
    }

    public function create(array $data): void
    {
        $passwordHash = password_hash((string) $data['usuclave'], PASSWORD_DEFAULT);

        if ($passwordHash === false) {
            throw new \RuntimeException('No se pudo proteger la clave del usuario.');
        }

        $statement = $this->db->prepare(
            "INSERT INTO {$this->table} (perid, usunombre, usuclave, usuestado)
             VALUES (:perid, :username, :password, :status)"
        );
        $statement->bindValue(':perid', $data['perid'], \PDO::PARAM_INT);
        $statement->bindValue(':username', $data['usunombre'], \PDO::PARAM_STR);
        $statement->bindValue(':password', $passwordHash, \PDO::PARAM_STR);
        $statement->bindValue(':status', $data['usuestado'], \PDO::PARAM_BOOL);
        $statement->execute();
    }

    public function createAndReturnId(array $data): int
    {
        $passwordHash = password_hash((string) $data['usuclave'], PASSWORD_DEFAULT);

        if ($passwordHash === false) {
            throw new \RuntimeException('No se pudo proteger la clave del usuario.');
        }

        $statement = $this->db->prepare(
            "INSERT INTO {$this->table} (perid, usunombre, usuclave, usuestado)
             VALUES (:perid, :username, :password, :status)
             RETURNING {$this->primaryKey}"
        );
        $statement->bindValue(':perid', $data['perid'], \PDO::PARAM_INT);
        $statement->bindValue(':username', $data['usunombre'], \PDO::PARAM_STR);
        $statement->bindValue(':password', $passwordHash, \PDO::PARAM_STR);
        $statement->bindValue(':status', $data['usuestado'], \PDO::PARAM_BOOL);
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    public function assignRoleToUser(int $userId, string $roleName): void
    {
        $statement = $this->db->prepare(
            "INSERT INTO usuario_rol (usuid, rolid, usrestado)
             SELECT u.usuid, r.rolid, true
             FROM {$this->table} u
             INNER JOIN rol r ON r.rolnombre = :role_name
             WHERE u.usuid = :user_id
               AND NOT EXISTS (
                   SELECT 1
                   FROM usuario_rol ur
                   WHERE ur.usuid = u.usuid
                     AND ur.rolid = r.rolid
               )"
        );
        $statement->execute([
            'user_id' => $userId,
            'role_name' => $roleName,
        ]);
    }

    public function syncRoleByPerson(int $personId, string $roleName, bool $assign): void
    {
        if ($assign) {
            $statement = $this->db->prepare(
                "INSERT INTO usuario_rol (usuid, rolid, usrestado)
                 SELECT u.usuid, r.rolid, true
                 FROM {$this->table} u
                 INNER JOIN rol r ON r.rolnombre = :role_name
                 WHERE u.perid = :person_id
                   AND NOT EXISTS (
                       SELECT 1
                       FROM usuario_rol ur
                       WHERE ur.usuid = u.usuid
                         AND ur.rolid = r.rolid
                   )"
            );
            $statement->execute([
                'person_id' => $personId,
                'role_name' => $roleName,
            ]);

            return;
        }

        $statement = $this->db->prepare(
            "DELETE FROM usuario_rol ur
             USING {$this->table} u, rol r
             WHERE ur.usuid = u.usuid
               AND ur.rolid = r.rolid
               AND u.perid = :person_id
               AND r.rolnombre = :role_name"
        );
        $statement->execute([
            'person_id' => $personId,
            'role_name' => $roleName,
        ]);
    }

    public function assignRoleByPerson(int $personId, string $roleName): void
    {
        $this->syncRoleByPerson($personId, $roleName, true);
    }

    public function verifyPassword(array $user, string $plainPassword): bool
    {
        $storedPassword = (string) ($user['usuclave'] ?? '');

        if ($storedPassword === '') {
            return false;
        }

        return password_verify($plainPassword, $storedPassword);
    }

    public function updateStatus(int $userId, bool $status): void
    {
        $statement = $this->db->prepare(
            "UPDATE {$this->table}
             SET usuestado = :status,
                 usufecha_modificacion = CURRENT_TIMESTAMP
             WHERE {$this->primaryKey} = :id"
        );
        $statement->bindValue(':id', $userId, \PDO::PARAM_INT);
        $statement->bindValue(':status', $status, \PDO::PARAM_BOOL);
        $statement->execute();
    }

    public function resetPassword(int $userId, string $temporaryPassword): void
    {
        $passwordHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);

        if ($passwordHash === false) {
            throw new \RuntimeException('No se pudo proteger la nueva clave del usuario.');
        }

        $statement = $this->db->prepare(
            "UPDATE {$this->table}
             SET usuclave = :password,
                 usufecha_modificacion = CURRENT_TIMESTAMP
             WHERE {$this->primaryKey} = :id"
        );
        $statement->bindValue(':id', $userId, \PDO::PARAM_INT);
        $statement->bindValue(':password', $passwordHash, \PDO::PARAM_STR);
        $statement->execute();
    }

    public function userWithPerson(int $userId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT u.usuid, u.perid, u.usunombre, u.usuestado, p.percedula, p.pernombres, p.perapellidos
             FROM {$this->table} u
             INNER JOIN persona p ON p.perid = u.perid
             WHERE u.usuid = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $userId]);

        return $statement->fetch();
    }
}
