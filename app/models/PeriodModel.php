<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class PeriodModel extends Model
{
    protected string $table = 'periodo_lectivo';
    protected string $primaryKey = 'pleid';

    public function allOrdered(): array
    {
        $statement = $this->db->query(
            "SELECT pleid, pledescripcion, plefechainicio, plefechafin, pleactivo
             FROM {$this->table}
             ORDER BY plefechainicio DESC, pleid DESC"
        );

        return $statement->fetchAll();
    }

    public function active(): array|false
    {
        $statement = $this->db->query(
            "SELECT pleid, pledescripcion, plefechainicio, plefechafin, pleactivo
             FROM {$this->table}
             WHERE pleactivo = true
             ORDER BY plefechainicio DESC, pleid DESC
             LIMIT 1"
        );

        return $statement->fetch();
    }

    public function existsByDescription(string $description, ?int $exceptId = null): bool
    {
        $sql =
            "SELECT 1
             FROM {$this->table}
             WHERE pledescripcion = :description";

        $params = ['description' => $description];

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
            "INSERT INTO {$this->table} (pledescripcion, plefechainicio, plefechafin, pleactivo)
             VALUES (:description, :start_date, :end_date, :active)"
        );
        $statement->bindValue(':description', $data['pledescripcion'], \PDO::PARAM_STR);
        $statement->bindValue(':start_date', $data['plefechainicio'], \PDO::PARAM_STR);
        $statement->bindValue(':end_date', $data['plefechafin'], \PDO::PARAM_STR);
        $statement->bindValue(':active', $data['pleactivo'], \PDO::PARAM_BOOL);
        $statement->execute();

        if ($data['pleactivo']) {
            $periodId = (int) $this->db->lastInsertId();
            $this->activate($periodId);
        }
    }

    public function update(int $periodId, array $data): void
    {
        $statement = $this->db->prepare(
            "UPDATE {$this->table}
             SET pledescripcion = :description,
                 plefechainicio = :start_date,
                 plefechafin = :end_date
             WHERE {$this->primaryKey} = :id"
        );
        $statement->execute([
            'id' => $periodId,
            'description' => $data['pledescripcion'],
            'start_date' => $data['plefechainicio'],
            'end_date' => $data['plefechafin'],
        ]);

        if ($data['pleactivo']) {
            $this->activate($periodId);
        }
    }

    public function activate(int $periodId): void
    {
        $this->db->beginTransaction();

        try {
            $this->db->exec("UPDATE {$this->table} SET pleactivo = false");

            $statement = $this->db->prepare(
                "UPDATE {$this->table}
                 SET pleactivo = true
                 WHERE {$this->primaryKey} = :id"
            );
            $statement->execute(['id' => $periodId]);

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }
}
