<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;
use RuntimeException;

class PersonalModel extends Model
{
    protected string $table = 'personal';
    protected string $primaryKey = 'psnid';

    public function allWithPersonAndTypes(?string $typeName = null): array
    {
        $sql = "SELECT
                    ps.psnid,
                    ps.perid,
                    p.percedula,
                    p.pernombres,
                    p.perapellidos,
                    p.pertelefono1,
                    p.percorreo,
                    p.persexo,
                    ps.psnfechacontratacion,
                    ps.psnfechasalida,
                    ps.psnestado,
                    COALESCE(
                        STRING_AGG(DISTINCT tp.tpnombre, ', ' ORDER BY tp.tpnombre),
                        'Sin tipo asignado'
                    ) AS tipos_personal
                FROM {$this->table} ps
                INNER JOIN persona p ON p.perid = ps.perid
                LEFT JOIN asignacion_tipo_personal atp
                    ON atp.psnid = ps.psnid
                    AND atp.atpestado = true
                LEFT JOIN tipo_personal tp ON tp.tpid = atp.tpid";

        $params = [];

        if ($typeName !== null && $typeName !== '') {
            $sql .= " WHERE EXISTS (
                        SELECT 1
                        FROM asignacion_tipo_personal atp_filter
                        INNER JOIN tipo_personal tp_filter ON tp_filter.tpid = atp_filter.tpid
                        WHERE atp_filter.psnid = ps.psnid
                          AND atp_filter.atpestado = true
                          AND tp_filter.tpnombre = :tipo
                    )";
            $params['tipo'] = $typeName;
        }

        $sql .= " GROUP BY
                    ps.psnid,
                    ps.perid,
                    p.percedula,
                    p.pernombres,
                    p.perapellidos,
                    p.pertelefono1,
                    p.percorreo,
                    p.persexo,
                    ps.psnfechacontratacion,
                    ps.psnfechasalida,
                    ps.psnestado
                  ORDER BY p.perapellidos ASC, p.pernombres ASC";

        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function activeTypes(): array
    {
        $statement = $this->db->query(
            "SELECT tpid, tpnombre, tpdescripcion, tpestado
             FROM tipo_personal
             WHERE tpestado = true
             ORDER BY tpnombre ASC"
        );

        return $statement->fetchAll();
    }

    public function allDetailed(string $term = ''): array
    {
        $normalizedTerm = trim($term);

        if ($normalizedTerm === '') {
            $statement = $this->db->query(
                "SELECT
                    ps.psnid,
                    ps.perid,
                    ps.psnestado,
                    p.percedula,
                    p.pernombres,
                    p.perapellidos
                 FROM {$this->table} ps
                 INNER JOIN persona p ON p.perid = ps.perid
                 ORDER BY p.perapellidos ASC, p.pernombres ASC"
            );

            return $statement->fetchAll();
        }

        $statement = $this->db->prepare(
            "SELECT
                ps.psnid,
                ps.perid,
                ps.psnestado,
                p.percedula,
                p.pernombres,
                p.perapellidos
             FROM {$this->table} ps
             INNER JOIN persona p ON p.perid = ps.perid
             WHERE p.percedula ILIKE :term
                OR p.pernombres ILIKE :term
                OR p.perapellidos ILIKE :term
             ORDER BY p.perapellidos ASC, p.pernombres ASC"
        );
        $statement->execute(['term' => '%' . $normalizedTerm . '%']);

        return $statement->fetchAll();
    }

    public function assignedTypeIdsByStaff(): array
    {
        $statement = $this->db->query(
            "SELECT psnid, tpid
             FROM asignacion_tipo_personal
             WHERE atpestado = true"
        );

        $map = [];

        foreach ($statement->fetchAll() as $row) {
            $staffId = (int) $row['psnid'];
            $typeId = (int) $row['tpid'];
            $map[$staffId][] = $typeId;
        }

        return $map;
    }

    public function staffExists(int $staffId): bool
    {
        $statement = $this->db->prepare(
            "SELECT 1
             FROM {$this->table}
             WHERE psnid = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $staffId]);

        return $statement->fetchColumn() !== false;
    }

    public function existsByPersonId(int $personId): bool
    {
        $statement = $this->db->prepare(
            "SELECT 1
             FROM {$this->table}
             WHERE perid = :person_id
             LIMIT 1"
        );
        $statement->execute(['person_id' => $personId]);

        return $statement->fetchColumn() !== false;
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            "INSERT INTO {$this->table} (
                perid,
                psnfechacontratacion,
                psnfechasalida,
                psnestado,
                psnobservacion
            ) VALUES (
                :person_id,
                :hire_date,
                :exit_date,
                :status,
                :note
            )
            RETURNING psnid"
        );

        $statement->bindValue(':person_id', $data['perid'], PDO::PARAM_INT);
        $statement->bindValue(':hire_date', $data['psnfechacontratacion'], PDO::PARAM_STR);
        $statement->bindValue(':exit_date', $data['psnfechasalida'] !== '' ? $data['psnfechasalida'] : null, $data['psnfechasalida'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $statement->bindValue(':status', $data['psnestado'], PDO::PARAM_BOOL);
        $statement->bindValue(':note', $data['psnobservacion'] !== '' ? $data['psnobservacion'] : null, $data['psnobservacion'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    public function findDetailed(int $staffId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT
                ps.psnid,
                ps.perid,
                ps.psnfechacontratacion,
                ps.psnfechasalida,
                ps.psnestado,
                ps.psnobservacion,
                p.percedula,
                p.pernombres,
                p.perapellidos,
                p.pertelefono1,
                p.percorreo
             FROM {$this->table} ps
             INNER JOIN persona p ON p.perid = ps.perid
             WHERE ps.psnid = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $staffId]);

        return $statement->fetch();
    }

    public function validTypeIds(array $typeIds): array
    {
        if ($typeIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($typeIds), '?'));
        $statement = $this->db->prepare(
            "SELECT tpid
             FROM tipo_personal
             WHERE tpestado = true
               AND tpid IN ({$placeholders})"
        );
        $statement->execute($typeIds);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function syncStaffTypes(int $staffId, array $typeIds): void
    {
        if (!$this->staffExists($staffId)) {
            throw new RuntimeException('El personal seleccionado no es valido.');
        }

        $typeIds = array_values(array_unique(array_map('intval', $typeIds)));
        $validTypeIds = $this->validTypeIds($typeIds);

        if (count($validTypeIds) !== count($typeIds)) {
            throw new RuntimeException('Existe al menos un tipo de personal no valido en la asignacion.');
        }

        $manageTransaction = !$this->db->inTransaction();

        try {
            if ($manageTransaction) {
                $this->db->beginTransaction();
            }

            $deleteStatement = $this->db->prepare(
                "DELETE FROM asignacion_tipo_personal
                 WHERE psnid = :staff_id"
            );
            $deleteStatement->execute(['staff_id' => $staffId]);

            if ($validTypeIds !== []) {
                $insertStatement = $this->db->prepare(
                    "INSERT INTO asignacion_tipo_personal (psnid, tpid, atpestado)
                     VALUES (:staff_id, :type_id, true)"
                );

                foreach ($validTypeIds as $typeId) {
                    $insertStatement->execute([
                        'staff_id' => $staffId,
                        'type_id' => $typeId,
                    ]);
                }
            }

            if ($manageTransaction) {
                $this->db->commit();
            }
        } catch (\Throwable $exception) {
            if ($manageTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function update(int $staffId, array $data): void
    {
        $statement = $this->db->prepare(
            "UPDATE {$this->table}
             SET psnfechacontratacion = :fecha_contratacion,
                 psnfechasalida = :fecha_salida,
                 psnestado = :estado,
                 psnobservacion = :observacion,
                 psnfecha_modificacion = CURRENT_TIMESTAMP
             WHERE psnid = :id"
        );

        $statement->bindValue(':id', $staffId, PDO::PARAM_INT);
        $statement->bindValue(':fecha_contratacion', $data['psnfechacontratacion'], PDO::PARAM_STR);
        $statement->bindValue(':fecha_salida', $data['psnfechasalida'] !== '' ? $data['psnfechasalida'] : null, $data['psnfechasalida'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $statement->bindValue(':estado', $data['psnestado'], PDO::PARAM_BOOL);
        $statement->bindValue(':observacion', $data['psnobservacion'] !== '' ? $data['psnobservacion'] : null, $data['psnobservacion'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $statement->execute();
    }

    public function countAll(): int
    {
        $statement = $this->db->query(
            "SELECT COUNT(*) FROM {$this->table}"
        );

        return (int) $statement->fetchColumn();
    }

    public function countActive(): int
    {
        $statement = $this->db->query(
            "SELECT COUNT(*)
             FROM {$this->table}
             WHERE psnestado = true"
        );

        return (int) $statement->fetchColumn();
    }
}
