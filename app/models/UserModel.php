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
             WHERE usunombre = :username
               AND usuestado = true
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

    public function allDetailed(string $term = ''): array
    {
        $normalizedTerm = trim($term);

        if ($normalizedTerm === '') {
            $statement = $this->db->query(
                "SELECT u.usuid, u.perid, u.usunombre, u.usuestado, p.percedula, p.pernombres, p.perapellidos
                 FROM {$this->table} u
                 INNER JOIN persona p ON p.perid = u.perid
                 ORDER BY p.perapellidos ASC, p.pernombres ASC"
            );

            return $statement->fetchAll();
        }

        $statement = $this->db->prepare(
            "SELECT u.usuid, u.perid, u.usunombre, u.usuestado, p.percedula, p.pernombres, p.perapellidos
             FROM {$this->table} u
             INNER JOIN persona p ON p.perid = u.perid
             WHERE u.usunombre ILIKE :term
                OR p.percedula ILIKE :term
                OR p.pernombres ILIKE :term
                OR p.perapellidos ILIKE :term
             ORDER BY p.perapellidos ASC, p.pernombres ASC"
        );
        $statement->execute(['term' => '%' . $normalizedTerm . '%']);

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
        $statement = $this->db->prepare(
            "INSERT INTO {$this->table} (perid, usunombre, usuclave, usuestado)
             VALUES (:perid, :username, :password, :status)"
        );
        $statement->bindValue(':perid', $data['perid'], \PDO::PARAM_INT);
        $statement->bindValue(':username', $data['usunombre'], \PDO::PARAM_STR);
        $statement->bindValue(':password', $data['usuclave'], \PDO::PARAM_STR);
        $statement->bindValue(':status', $data['usuestado'], \PDO::PARAM_BOOL);
        $statement->execute();
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
