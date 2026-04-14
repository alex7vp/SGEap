<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;
use RuntimeException;

class MatriculationModel extends Model
{
    protected string $table = 'matricula';
    protected string $primaryKey = 'matid';

    public function allCoursesByPeriod(int $periodId): array
    {
        $statement = $this->db->prepare(
            "SELECT c.curid, n.nednombre, g.granombre, p.prlnombre
             FROM curso c
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN nivel_educativo n ON n.nedid = g.nedid
             INNER JOIN paralelo p ON p.prlid = c.prlid
             WHERE c.pleid = :period_id
               AND c.curestado = true
             ORDER BY n.nednombre ASC, g.granombre ASC, p.prlnombre ASC"
        );
        $statement->execute(['period_id' => $periodId]);

        return $statement->fetchAll();
    }

    public function allRelationships(): array
    {
        return $this->simpleCatalog('parentesco', 'pteid', 'ptenombre');
    }

    public function allCivilStatuses(): array
    {
        return $this->simpleCatalog('estado_civil', 'eciid', 'ecinombre');
    }

    public function allInstructionLevels(): array
    {
        return $this->simpleCatalog('instruccion', 'istid', 'istnombre');
    }

    public function allEnrollmentStatuses(): array
    {
        return $this->simpleCatalog('estado_matricula', 'emdid', 'emdnombre');
    }

    public function defaultInactiveEnrollmentStatus(): ?array
    {
        $statement = $this->db->query(
            "SELECT emdid, emdnombre
             FROM estado_matricula
             ORDER BY emdid ASC"
        );

        $statuses = $statement->fetchAll();

        if ($statuses === []) {
            return null;
        }

        foreach ($statuses as $status) {
            $name = mb_strtolower(trim((string) ($status['emdnombre'] ?? '')));

            if (in_array($name, ['inactivo', 'inactiva', 'inhabilitado', 'inhabilitada'], true)) {
                return $status;
            }
        }

        return $statuses[0];
    }

    public function allByPeriod(int $periodId): array
    {
        $statement = $this->db->prepare(
            "SELECT m.matid, m.matfecha, m.matfoto,
                    p.percedula, p.perapellidos, p.pernombres,
                    n.nednombre, g.granombre, pr.prlnombre,
                    em.emdnombre,
                    e.estestado,
                    rp.perapellidos AS rep_apellidos,
                    rp.pernombres AS rep_nombres,
                    pt.ptenombre AS rep_parentesco
             FROM {$this->table} m
             INNER JOIN estudiante e ON e.estid = m.estid
             INNER JOIN persona p ON p.perid = e.perid
             INNER JOIN curso c ON c.curid = m.curid
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN nivel_educativo n ON n.nedid = g.nedid
             INNER JOIN paralelo pr ON pr.prlid = c.prlid
             INNER JOIN estado_matricula em ON em.emdid = m.emdid
             LEFT JOIN matricula_representante mr ON mr.matid = m.matid
             LEFT JOIN persona rp ON rp.perid = mr.perid
             LEFT JOIN parentesco pt ON pt.pteid = mr.pteid
             WHERE c.pleid = :period_id
             ORDER BY m.matfecha DESC, p.perapellidos ASC, p.pernombres ASC"
        );
        $statement->execute(['period_id' => $periodId]);

        return $statement->fetchAll();
    }

    public function createEnrollment(array $data): int
    {
        $this->db->beginTransaction();
        $photoPath = null;

        try {
            $studentPersonId = $this->upsertPerson($data['person']);
            $studentId = $this->ensureStudent($studentPersonId, $data['student']);

            if ($this->existsStudentInPeriod($studentId, (int) $data['period']['pleid'])) {
                throw new RuntimeException('El estudiante ya tiene una matricula registrada en el periodo lectivo actual.');
            }

            if (!empty($data['photo']) && is_array($data['photo'])) {
                $photoPath = storeMatriculationPhoto(
                    $data['photo'],
                    (string) $data['person']['percedula'],
                    (string) $data['period']['pledescripcion']
                );
            }

            $matriculaId = $this->insertMatriculation($studentId, $data['matricula'], $photoPath);
            $familyRepresentatives = $this->persistFamilies($studentId, $data['families']);
            $representative = $this->resolveRepresentative($data['representative'] ?? [], $familyRepresentatives);

            $this->insertMatriculationRepresentative($matriculaId, $representative['perid'], $representative['pteid']);

            $this->db->commit();

            return $matriculaId;
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            if ($photoPath !== null) {
                $absolutePhotoPath = BASE_PATH . '/public/assets/' . ltrim($photoPath, '/');

                if (is_file($absolutePhotoPath)) {
                    @unlink($absolutePhotoPath);
                }
            }

            throw $exception;
        }
    }

    private function simpleCatalog(string $table, string $idColumn, string $nameColumn): array
    {
        $statement = $this->db->query(
            "SELECT {$idColumn}, {$nameColumn}
             FROM {$table}
             ORDER BY {$nameColumn} ASC"
        );

        return $statement->fetchAll();
    }

    private function upsertPerson(array $person): int
    {
        $existing = $this->findPersonByCedula((string) $person['percedula']);

        if ($existing !== false) {
            $statement = $this->db->prepare(
                "UPDATE persona
                 SET pernombres = :nombres,
                     perapellidos = :apellidos,
                     pertelefono1 = :telefono1,
                     pertelefono2 = :telefono2,
                     percorreo = :correo,
                     persexo = :sexo
                 WHERE perid = :id"
            );
            $statement->execute([
                'id' => $existing['perid'],
                'nombres' => $person['pernombres'],
                'apellidos' => $person['perapellidos'],
                'telefono1' => $person['pertelefono1'] !== '' ? $person['pertelefono1'] : null,
                'telefono2' => $person['pertelefono2'] !== '' ? $person['pertelefono2'] : null,
                'correo' => $person['percorreo'] !== '' ? $person['percorreo'] : null,
                'sexo' => $person['persexo'] !== '' ? $person['persexo'] : null,
            ]);

            return (int) $existing['perid'];
        }

        $statement = $this->db->prepare(
            "INSERT INTO persona (
                percedula, pernombres, perapellidos, pertelefono1, pertelefono2, percorreo, persexo
             ) VALUES (
                :cedula, :nombres, :apellidos, :telefono1, :telefono2, :correo, :sexo
             ) RETURNING perid"
        );
        $statement->execute([
            'cedula' => $person['percedula'],
            'nombres' => $person['pernombres'],
            'apellidos' => $person['perapellidos'],
            'telefono1' => $person['pertelefono1'] !== '' ? $person['pertelefono1'] : null,
            'telefono2' => $person['pertelefono2'] !== '' ? $person['pertelefono2'] : null,
            'correo' => $person['percorreo'] !== '' ? $person['percorreo'] : null,
            'sexo' => $person['persexo'] !== '' ? $person['persexo'] : null,
        ]);

        return (int) $statement->fetchColumn();
    }

    private function ensureStudent(int $personId, array $student): int
    {
        $existing = $this->findStudentByPersonId($personId);

        if ($existing !== false) {
            $statement = $this->db->prepare(
                "UPDATE estudiante
                 SET estlugarnacimiento = :origen,
                     estdireccion = :direccion,
                     estparroquia = :parroquia
                 WHERE estid = :id"
            );
            $statement->bindValue(':id', $existing['estid'], PDO::PARAM_INT);
            $statement->bindValue(':origen', $student['estlugarnacimiento'] !== '' ? $student['estlugarnacimiento'] : null);
            $statement->bindValue(':direccion', $student['estdireccion'] !== '' ? $student['estdireccion'] : null);
            $statement->bindValue(':parroquia', $student['estparroquia'] !== '' ? $student['estparroquia'] : null);
            $statement->execute();

            return (int) $existing['estid'];
        }

        $statement = $this->db->prepare(
            "INSERT INTO estudiante (
                perid, estlugarnacimiento, estdireccion, estparroquia, estestado
             ) VALUES (
                :perid, :origen, :direccion, :parroquia, :estado
             ) RETURNING estid"
        );
        $statement->bindValue(':perid', $personId, PDO::PARAM_INT);
        $statement->bindValue(':origen', $student['estlugarnacimiento'] !== '' ? $student['estlugarnacimiento'] : null);
        $statement->bindValue(':direccion', $student['estdireccion'] !== '' ? $student['estdireccion'] : null);
        $statement->bindValue(':parroquia', $student['estparroquia'] !== '' ? $student['estparroquia'] : null);
        $statement->bindValue(':estado', false, PDO::PARAM_BOOL);
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    private function existsStudentInPeriod(int $studentId, int $periodId): bool
    {
        $statement = $this->db->prepare(
            "SELECT 1
             FROM matricula m
             INNER JOIN curso c ON c.curid = m.curid
             WHERE m.estid = :student_id
               AND c.pleid = :period_id
             LIMIT 1"
        );
        $statement->execute([
            'student_id' => $studentId,
            'period_id' => $periodId,
        ]);

        return $statement->fetchColumn() !== false;
    }

    private function insertMatriculation(int $studentId, array $matricula, ?string $photoPath): int
    {
        $statement = $this->db->prepare(
            "INSERT INTO matricula (
                estid, curid, matfecha, matfoto, emdid
             ) VALUES (
                :student_id, :course_id, :date, :photo, :status_id
             ) RETURNING matid"
        );
        $statement->execute([
            'student_id' => $studentId,
            'course_id' => $matricula['curid'],
            'date' => $matricula['matfecha'],
            'photo' => $photoPath,
            'status_id' => $matricula['emdid'],
        ]);

        return (int) $statement->fetchColumn();
    }

    public function toggleStudentStatusByMatricula(int $matriculaId): bool
    {
        $statement = $this->db->prepare(
            "SELECT e.estid, e.estestado
             FROM matricula m
             INNER JOIN estudiante e ON e.estid = m.estid
             WHERE m.matid = :matid
             LIMIT 1"
        );
        $statement->execute(['matid' => $matriculaId]);
        $record = $statement->fetch();

        if ($record === false) {
            throw new RuntimeException('La matricula solicitada no existe.');
        }

        $nextStatus = !((bool) ($record['estestado'] ?? false));

        $update = $this->db->prepare(
            "UPDATE estudiante
             SET estestado = :estado
             WHERE estid = :estid"
        );
        $update->bindValue(':estid', (int) $record['estid'], PDO::PARAM_INT);
        $update->bindValue(':estado', $nextStatus, PDO::PARAM_BOOL);
        $update->execute();

        return $nextStatus;
    }

    private function persistFamilies(int $studentId, array $families): array
    {
        $persisted = [];

        foreach ($families as $index => $family) {
            if (
                ($family['percedula'] ?? '') === ''
                && ($family['pernombres'] ?? '') === ''
                && ($family['perapellidos'] ?? '') === ''
            ) {
                continue;
            }

            if (
                ($family['percedula'] ?? '') === ''
                || ($family['pernombres'] ?? '') === ''
                || ($family['perapellidos'] ?? '') === ''
                || (int) ($family['pteid'] ?? 0) <= 0
            ) {
                throw new RuntimeException('Cada familiar debe tener cedula, nombres, apellidos y parentesco.');
            }

            $personId = $this->upsertPerson([
                'percedula' => $family['percedula'],
                'pernombres' => $family['pernombres'],
                'perapellidos' => $family['perapellidos'],
                'pertelefono1' => $family['pertelefono1'] ?? '',
                'pertelefono2' => $family['pertelefono2'] ?? '',
                'percorreo' => $family['percorreo'] ?? '',
                'persexo' => $family['persexo'] ?? '',
            ]);

            $this->upsertFamily($studentId, $personId, $family);

            $persisted[$index] = [
                'perid' => $personId,
                'pteid' => (int) $family['pteid'],
            ];
        }

        return $persisted;
    }

    private function resolveRepresentative(array $representative, array $familyRepresentatives): array
    {
        $source = (string) ($representative['source'] ?? 'family');

        if ($source === 'external') {
            $external = $representative['external'] ?? [];

            if (
                trim((string) ($external['percedula'] ?? '')) === ''
                || trim((string) ($external['pernombres'] ?? '')) === ''
                || trim((string) ($external['perapellidos'] ?? '')) === ''
                || (int) ($external['pteid'] ?? 0) <= 0
            ) {
                throw new RuntimeException('Complete los datos obligatorios del representante externo.');
            }

            $personId = $this->upsertPerson([
                'percedula' => $external['percedula'],
                'pernombres' => $external['pernombres'],
                'perapellidos' => $external['perapellidos'],
                'pertelefono1' => $external['pertelefono1'] ?? '',
                'pertelefono2' => $external['pertelefono2'] ?? '',
                'percorreo' => $external['percorreo'] ?? '',
                'persexo' => $external['persexo'] ?? '',
            ]);

            return [
                'perid' => $personId,
                'pteid' => (int) $external['pteid'],
            ];
        }

        $familyIndex = (int) ($representative['family_index'] ?? -1);

        if (!array_key_exists($familyIndex, $familyRepresentatives)) {
            throw new RuntimeException('Debe seleccionar un representante valido.');
        }

        return $familyRepresentatives[$familyIndex];
    }

    private function upsertFamily(int $studentId, int $personId, array $family): void
    {
        $existing = $this->findFamily($studentId, $personId, (int) $family['pteid']);

        if ($existing !== false) {
            $statement = $this->db->prepare(
                "UPDATE familiar
                 SET eciid = :civil_status,
                     istid = :instruction_level,
                     famprofesion = :profession,
                     famlugardetrabajo = :workplace,
                     famfechanacimiento = :birth_date
                 WHERE famid = :id"
            );
            $statement->execute([
                'id' => $existing['famid'],
                'civil_status' => (int) ($family['eciid'] ?? 0) > 0 ? (int) $family['eciid'] : null,
                'instruction_level' => (int) ($family['istid'] ?? 0) > 0 ? (int) $family['istid'] : null,
                'profession' => ($family['famprofesion'] ?? '') !== '' ? $family['famprofesion'] : null,
                'workplace' => ($family['famlugardetrabajo'] ?? '') !== '' ? $family['famlugardetrabajo'] : null,
                'birth_date' => ($family['famfechanacimiento'] ?? '') !== '' ? $family['famfechanacimiento'] : null,
            ]);

            return;
        }

        $statement = $this->db->prepare(
            "INSERT INTO familiar (
                estid, perid, pteid, eciid, istid, famprofesion, famlugardetrabajo, famfechanacimiento
             ) VALUES (
                :student_id, :person_id, :relationship_id, :civil_status, :instruction_level, :profession, :workplace, :birth_date
             )"
        );
        $statement->execute([
            'student_id' => $studentId,
            'person_id' => $personId,
            'relationship_id' => $family['pteid'],
            'civil_status' => (int) ($family['eciid'] ?? 0) > 0 ? (int) $family['eciid'] : null,
            'instruction_level' => (int) ($family['istid'] ?? 0) > 0 ? (int) $family['istid'] : null,
            'profession' => ($family['famprofesion'] ?? '') !== '' ? $family['famprofesion'] : null,
            'workplace' => ($family['famlugardetrabajo'] ?? '') !== '' ? $family['famlugardetrabajo'] : null,
            'birth_date' => ($family['famfechanacimiento'] ?? '') !== '' ? $family['famfechanacimiento'] : null,
        ]);
    }

    private function insertMatriculationRepresentative(int $matriculaId, int $personId, int $relationshipId): void
    {
        $statement = $this->db->prepare(
            "INSERT INTO matricula_representante (matid, perid, pteid)
             VALUES (:matid, :perid, :pteid)"
        );
        $statement->execute([
            'matid' => $matriculaId,
            'perid' => $personId,
            'pteid' => $relationshipId,
        ]);
    }

    private function findPersonByCedula(string $cedula): array|false
    {
        $statement = $this->db->prepare(
            "SELECT perid, percedula
             FROM persona
             WHERE percedula = :cedula
             LIMIT 1"
        );
        $statement->execute(['cedula' => $cedula]);

        return $statement->fetch();
    }

    private function findStudentByPersonId(int $personId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT estid
             FROM estudiante
             WHERE perid = :perid
             LIMIT 1"
        );
        $statement->execute(['perid' => $personId]);

        return $statement->fetch();
    }

    private function findFamily(int $studentId, int $personId, int $relationshipId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT famid
             FROM familiar
             WHERE estid = :student_id
               AND perid = :person_id
               AND pteid = :relationship_id
             LIMIT 1"
        );
        $statement->execute([
            'student_id' => $studentId,
            'person_id' => $personId,
            'relationship_id' => $relationshipId,
        ]);

        return $statement->fetch();
    }
}
