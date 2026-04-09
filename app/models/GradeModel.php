<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDOException;

class GradeModel extends Model
{
    protected string $table = 'grado';
    protected string $primaryKey = 'graid';

    public function allOrdered(): array
    {
        return $this->search();
    }

    public function search(string $term = ''): array
    {
        $normalizedTerm = trim($term);

        if ($normalizedTerm === '') {
            $statement = $this->db->query(
                "SELECT g.graid, g.nedid, g.granombre, n.nednombre
                 FROM {$this->table} g
                 INNER JOIN nivel_educativo n ON n.nedid = g.nedid
                 ORDER BY n.nednombre ASC, g.granombre ASC"
            );

            return $statement->fetchAll();
        }

        $statement = $this->db->prepare(
            "SELECT g.graid, g.nedid, g.granombre, n.nednombre
             FROM {$this->table} g
             INNER JOIN nivel_educativo n ON n.nedid = g.nedid
             WHERE g.granombre ILIKE :term
                OR n.nednombre ILIKE :term
             ORDER BY n.nednombre ASC, g.granombre ASC"
        );
        $statement->execute(['term' => '%' . $normalizedTerm . '%']);

        return $statement->fetchAll();
    }

    public function allLevels(): array
    {
        $statement = $this->db->query(
            "SELECT nedid, nednombre
             FROM nivel_educativo
             ORDER BY nednombre ASC"
        );

        return $statement->fetchAll();
    }

    public function findDetailed(int $gradeId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT g.graid, g.nedid, g.granombre, n.nednombre
             FROM {$this->table} g
             INNER JOIN nivel_educativo n ON n.nedid = g.nedid
             WHERE g.graid = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $gradeId]);

        return $statement->fetch();
    }

    public function existsCombination(int $levelId, string $name, ?int $exceptId = null): bool
    {
        $sql =
            "SELECT 1
             FROM {$this->table}
             WHERE nedid = :level_id
               AND granombre = :name";

        $params = [
            'level_id' => $levelId,
            'name' => $name,
        ];

        if ($exceptId !== null) {
            $sql .= " AND {$this->primaryKey} <> :id";
            $params['id'] = $exceptId;
        }

        $sql .= ' LIMIT 1';

        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchColumn() !== false;
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            "INSERT INTO {$this->table} (nedid, granombre)
             VALUES (:level_id, :name)
             RETURNING {$this->primaryKey}"
        );
        $statement->execute([
            'level_id' => $data['nedid'],
            'name' => $data['granombre'],
        ]);

        return (int) $statement->fetchColumn();
    }

    public function update(int $gradeId, array $data): void
    {
        $statement = $this->db->prepare(
            "UPDATE {$this->table}
             SET nedid = :level_id,
                 granombre = :name
             WHERE {$this->primaryKey} = :id"
        );
        $statement->execute([
            'id' => $gradeId,
            'level_id' => $data['nedid'],
            'name' => $data['granombre'],
        ]);
    }

    public function deleteById(int $gradeId): bool
    {
        try {
            $statement = $this->db->prepare(
                "DELETE FROM {$this->table}
                 WHERE {$this->primaryKey} = :id"
            );
            $statement->execute(['id' => $gradeId]);

            return $statement->rowCount() > 0;
        } catch (PDOException) {
            return false;
        }
    }
}
