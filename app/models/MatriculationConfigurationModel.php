<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use DateTimeImmutable;
use PDO;
use RuntimeException;

class MatriculationConfigurationModel extends Model
{
    protected string $table = 'configuracion_matricula';
    protected string $primaryKey = 'cmid';

    public function allByPeriod(): array
    {
        $statement = $this->db->query(
            "SELECT p.pleid, p.pledescripcion, p.plefechainicio, p.plefechafin,
                    c.cmid, c.cmhabilitada, c.cmfechainicio, c.cmfechafin,
                    c.cmhabilitadaextraordinaria, c.cmfechainicioextraordinaria,
                    c.cmfechafinextraordinaria, c.cmobservacion
             FROM periodo_lectivo p
             LEFT JOIN {$this->table} c ON c.pleid = p.pleid
             ORDER BY p.plefechainicio DESC, p.pleid DESC"
        );

        return $statement->fetchAll();
    }

    public function findByPeriodId(int $periodId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT cmid, pleid, cmhabilitada, cmfechainicio, cmfechafin,
                    cmhabilitadaextraordinaria, cmfechainicioextraordinaria,
                    cmfechafinextraordinaria, cmobservacion
             FROM {$this->table}
             WHERE pleid = :pleid
             LIMIT 1"
        );
        $statement->execute(['pleid' => $periodId]);

        return $statement->fetch();
    }

    public function findById(int $configurationId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT cmid, pleid, cmhabilitada, cmfechainicio, cmfechafin,
                    cmhabilitadaextraordinaria, cmfechainicioextraordinaria,
                    cmfechafinextraordinaria, cmobservacion
             FROM {$this->table}
             WHERE {$this->primaryKey} = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $configurationId]);

        return $statement->fetch();
    }

    public function findEnabledPeriod(): array|false
    {
        $statement = $this->db->query(
            "SELECT p.pleid, p.pledescripcion, p.plefechainicio, p.plefechafin, p.pleactivo,
                    c.cmid, c.cmhabilitada, c.cmfechainicio, c.cmfechafin,
                    c.cmhabilitadaextraordinaria, c.cmfechainicioextraordinaria,
                    c.cmfechafinextraordinaria, c.cmobservacion
             FROM {$this->table} c
             INNER JOIN periodo_lectivo p ON p.pleid = c.pleid
             WHERE c.cmhabilitada = true
                OR c.cmhabilitadaextraordinaria = true
             ORDER BY p.plefechainicio DESC, p.pleid DESC
             LIMIT 1"
        );

        return $statement->fetch();
    }

    public function existsByPeriodId(int $periodId, ?int $exceptId = null): bool
    {
        $sql =
            "SELECT 1
             FROM {$this->table}
             WHERE pleid = :pleid";

        $params = ['pleid' => $periodId];

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
            "INSERT INTO {$this->table} (
                pleid,
                cmhabilitada,
                cmfechainicio,
                cmfechafin,
                cmhabilitadaextraordinaria,
                cmfechainicioextraordinaria,
                cmfechafinextraordinaria,
                cmobservacion
             ) VALUES (
                :pleid,
                :habilitada,
                :fechainicio,
                :fechafin,
                :habilitadaextraordinaria,
                :fechainicioextraordinaria,
                :fechafinextraordinaria,
                :observacion
             )"
        );

        $this->bindConfigurationValues($statement, $data);
        $statement->execute();
    }

    public function update(int $configurationId, array $data): void
    {
        $statement = $this->db->prepare(
            "UPDATE {$this->table}
             SET pleid = :pleid,
                 cmhabilitada = :habilitada,
                 cmfechainicio = :fechainicio,
                 cmfechafin = :fechafin,
                 cmhabilitadaextraordinaria = :habilitadaextraordinaria,
                 cmfechainicioextraordinaria = :fechainicioextraordinaria,
                 cmfechafinextraordinaria = :fechafinextraordinaria,
                 cmobservacion = :observacion,
                 cmfecha_modificacion = CURRENT_TIMESTAMP
             WHERE {$this->primaryKey} = :id"
        );
        $statement->bindValue(':id', $configurationId, PDO::PARAM_INT);
        $this->bindConfigurationValues($statement, $data);
        $statement->execute();
    }

    public function toggleOrdinary(int $configurationId, bool $enabled): void
    {
        $statement = $this->db->prepare(
            "UPDATE {$this->table}
             SET cmhabilitada = :enabled,
                 cmfecha_modificacion = CURRENT_TIMESTAMP
             WHERE {$this->primaryKey} = :id"
        );
        $statement->bindValue(':id', $configurationId, PDO::PARAM_INT);
        $statement->bindValue(':enabled', $enabled, PDO::PARAM_BOOL);
        $statement->execute();
    }

    public function toggleExtraordinary(int $configurationId, bool $enabled): void
    {
        $configuration = $this->findById($configurationId);

        if ($configuration === false) {
            throw new RuntimeException('La configuracion de matricula seleccionada no existe.');
        }

        if ($enabled) {
            $this->assertExtraordinaryCanBeEnabled($configuration);
        }

        $statement = $this->db->prepare(
            "UPDATE {$this->table}
             SET cmhabilitadaextraordinaria = :enabled,
                 cmfecha_modificacion = CURRENT_TIMESTAMP
             WHERE {$this->primaryKey} = :id"
        );
        $statement->bindValue(':id', $configurationId, PDO::PARAM_INT);
        $statement->bindValue(':enabled', $enabled, PDO::PARAM_BOOL);
        $statement->execute();
    }

    public function assertExtraordinaryCanBeEnabled(array $configuration): void
    {
        $ordinaryEnd = trim((string) ($configuration['cmfechafin'] ?? ''));

        if ($ordinaryEnd === '') {
            throw new RuntimeException(
                'No se puede habilitar la matricula extraordinaria sin una fecha de fin para la matricula ordinaria.'
            );
        }

        try {
            $today = new DateTimeImmutable('today');
            $ordinaryEndDate = new DateTimeImmutable($ordinaryEnd);
        } catch (\Exception) {
            throw new RuntimeException('La fecha de fin de la matricula ordinaria no es valida.');
        }

        if ($today <= $ordinaryEndDate) {
            throw new RuntimeException(
                'No se puede habilitar la matricula extraordinaria mientras no haya vencido la fecha de fin de la matricula ordinaria.'
            );
        }
    }

    private function bindConfigurationValues(\PDOStatement $statement, array $data): void
    {
        $statement->bindValue(':pleid', (int) $data['pleid'], PDO::PARAM_INT);
        $statement->bindValue(':habilitada', (bool) $data['cmhabilitada'], PDO::PARAM_BOOL);
        $statement->bindValue(
            ':fechainicio',
            $data['cmfechainicio'] !== '' ? $data['cmfechainicio'] : null,
            $data['cmfechainicio'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $statement->bindValue(
            ':fechafin',
            $data['cmfechafin'] !== '' ? $data['cmfechafin'] : null,
            $data['cmfechafin'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $statement->bindValue(
            ':habilitadaextraordinaria',
            (bool) $data['cmhabilitadaextraordinaria'],
            PDO::PARAM_BOOL
        );
        $statement->bindValue(
            ':fechainicioextraordinaria',
            $data['cmfechainicioextraordinaria'] !== '' ? $data['cmfechainicioextraordinaria'] : null,
            $data['cmfechainicioextraordinaria'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $statement->bindValue(
            ':fechafinextraordinaria',
            $data['cmfechafinextraordinaria'] !== '' ? $data['cmfechafinextraordinaria'] : null,
            $data['cmfechafinextraordinaria'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $statement->bindValue(
            ':observacion',
            $data['cmobservacion'] !== '' ? $data['cmobservacion'] : null,
            $data['cmobservacion'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
    }
}
