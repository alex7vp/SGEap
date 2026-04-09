<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class CourseModel extends Model
{
    protected string $table = 'curso';
    protected string $primaryKey = 'curid';

    public function allByPeriod(int $periodId): array
    {
        $statement = $this->db->prepare(
            "SELECT c.curid, c.pleid, c.graid, c.prlid, c.curestado,
                    g.granombre, n.nednombre, p.prlnombre
             FROM {$this->table} c
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN nivel_educativo n ON n.nedid = g.nedid
             INNER JOIN paralelo p ON p.prlid = c.prlid
             WHERE c.pleid = :period_id
             ORDER BY n.nednombre ASC, g.granombre ASC, p.prlnombre ASC"
        );
        $statement->execute(['period_id' => $periodId]);

        return $statement->fetchAll();
    }

    public function allGrades(): array
    {
        $statement = $this->db->query(
            "SELECT g.graid, g.granombre, n.nednombre
             FROM grado g
             INNER JOIN nivel_educativo n ON n.nedid = g.nedid
             ORDER BY n.nednombre ASC, g.granombre ASC"
        );

        return $statement->fetchAll();
    }

    public function allParallels(): array
    {
        $statement = $this->db->query(
            "SELECT prlid, prlnombre
             FROM paralelo
             ORDER BY prlnombre ASC"
        );

        return $statement->fetchAll();
    }

    public function existsCombination(int $periodId, int $gradeId, int $parallelId, ?int $exceptId = null): bool
    {
        $sql =
            "SELECT 1
             FROM {$this->table}
             WHERE pleid = :period_id
               AND graid = :grade_id
               AND prlid = :parallel_id";

        $params = [
            'period_id' => $periodId,
            'grade_id' => $gradeId,
            'parallel_id' => $parallelId,
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

    public function create(array $data): void
    {
        $statement = $this->db->prepare(
            "INSERT INTO {$this->table} (pleid, graid, prlid, curestado)
             VALUES (:period_id, :grade_id, :parallel_id, :status)"
        );
        $statement->bindValue(':period_id', $data['pleid'], \PDO::PARAM_INT);
        $statement->bindValue(':grade_id', $data['graid'], \PDO::PARAM_INT);
        $statement->bindValue(':parallel_id', $data['prlid'], \PDO::PARAM_INT);
        $statement->bindValue(':status', $data['curestado'], \PDO::PARAM_BOOL);
        $statement->execute();
    }

    public function updateStatus(int $courseId, bool $status): void
    {
        $statement = $this->db->prepare(
            "UPDATE {$this->table}
             SET curestado = :status
             WHERE {$this->primaryKey} = :id"
        );
        $statement->bindValue(':id', $courseId, \PDO::PARAM_INT);
        $statement->bindValue(':status', $status, \PDO::PARAM_BOOL);
        $statement->execute();
    }

    public function findDetailed(int $courseId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT c.curid, c.pleid, c.graid, c.prlid, c.curestado,
                    g.granombre, n.nednombre, p.prlnombre
             FROM {$this->table} c
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN nivel_educativo n ON n.nedid = g.nedid
             INNER JOIN paralelo p ON p.prlid = c.prlid
             WHERE c.curid = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $courseId]);

        return $statement->fetch();
    }
}
