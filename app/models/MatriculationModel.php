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

    public function allHousingConditions(): array
    {
        return $this->simpleCatalog('condicion_vivienda', 'cviid', 'cvinombre');
    }

    public function allBloodGroups(): array
    {
        return $this->simpleCatalog('grupo_sanguineo', 'gsid', 'gsnombre');
    }

    public function allMedicalCareTypes(): array
    {
        return $this->simpleCatalog('atencion_medica', 'amid', 'amnombre');
    }

    public function allHealthConditionTypes(): array
    {
        return $this->simpleCatalog('tipo_condicion_salud', 'tcsid', 'tcsnombre');
    }

    public function allInsuranceProviders(): array
    {
        return $this->simpleCatalog('seguro_medico', 'smid', 'smnombre');
    }

    public function allPregnancyTypes(): array
    {
        return $this->simpleCatalog('tipo_embarazo', 'teid', 'tenombre');
    }

    public function allBirthTypes(): array
    {
        return $this->simpleCatalog('tipo_parto', 'tpid', 'tpnombre');
    }

    public function allEnrollmentStatuses(): array
    {
        return $this->simpleCatalog('estado_matricula', 'emdid', 'emdnombre');
    }

    public function allActiveDocuments(): array
    {
        $statement = $this->db->query(
            "SELECT domid, domnombre, domdescripcion, domorigen, domurl, domobligatorio, domactivo
             FROM documento_matricula
             WHERE domactivo = true
             ORDER BY domobligatorio DESC, domnombre ASC"
        );

        return $statement->fetchAll();
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

    public function defaultEnrollmentType(): ?array
    {
        $statement = $this->db->query(
            "SELECT tmaid, tmanombre
             FROM tipo_matricula
             WHERE tmaestado = true
             ORDER BY tmaid ASC"
        );

        $types = $statement->fetchAll();

        if ($types === []) {
            return null;
        }

        foreach ($types as $type) {
            $name = mb_strtolower(trim((string) ($type['tmanombre'] ?? '')));

            if (in_array($name, ['ordinaria', 'regular'], true)) {
                return $type;
            }
        }

        return $types[0];
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

    public function countByPeriod(int $periodId): int
    {
        $statement = $this->db->prepare(
            "SELECT COUNT(*)
             FROM {$this->table} m
             INNER JOIN curso c ON c.curid = m.curid
             WHERE c.pleid = :period_id"
        );
        $statement->execute(['period_id' => $periodId]);

        return (int) $statement->fetchColumn();
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
            $familyRepresentatives = $this->persistFamilies($studentId, $studentPersonId, $data['families']);
            $representative = $this->resolveRepresentative($studentPersonId, $data['representative'] ?? [], $familyRepresentatives);

            $this->upsertStudentFamilyContext($studentId, $data['family_context'] ?? []);
            $this->upsertStudentHousing($studentId, $data['housing'] ?? []);
            $this->upsertStudentHealthContext($studentId, $data['health_context'] ?? []);
            $this->replaceStudentHealthConditions($studentId, $data['health_conditions'] ?? []);
            $this->insertStudentHealthMeasurement($studentId, $data['health_measurement'] ?? []);
            $this->upsertStudentVitalHistory($studentId, $data['vital_history'] ?? []);
            $this->upsertStudentAcademicContext($studentId, $data['academic_context'] ?? []);
            $this->insertMatriculationRepresentative($matriculaId, $representative['perid'], $representative['pteid']);
            $this->insertMatriculationResources($matriculaId, $data['resources'] ?? []);
            $this->insertMatriculationInsurance($matriculaId, $data['insurance'] ?? []);
            $this->insertMatriculationBilling($matriculaId, $data['billing'] ?? []);
            $this->insertDocumentAcceptances(
                $matriculaId,
                $data['documents_catalog'] ?? [],
                $data['document_acceptances'] ?? []
            );

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
                     persexo = :sexo,
                     perfechanacimiento = :birth_date,
                     istid = :instruction_level,
                     perprofesion = :profession,
                     perocupacion = :occupation,
                     perhablaingles = :speaks_english
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
                'birth_date' => ($person['perfechanacimiento'] ?? '') !== '' ? $person['perfechanacimiento'] : null,
                'instruction_level' => (int) ($person['istid'] ?? 0) > 0 ? (int) $person['istid'] : null,
                'profession' => ($person['perprofesion'] ?? '') !== '' ? $person['perprofesion'] : null,
                'occupation' => ($person['perocupacion'] ?? '') !== '' ? $person['perocupacion'] : null,
                'speaks_english' => !empty($person['perhablaingles']),
            ]);

            return (int) $existing['perid'];
        }

        $statement = $this->db->prepare(
            "INSERT INTO persona (
                percedula, pernombres, perapellidos, pertelefono1, pertelefono2, percorreo, persexo,
                perfechanacimiento, istid, perprofesion, perocupacion, perhablaingles
             ) VALUES (
                :cedula, :nombres, :apellidos, :telefono1, :telefono2, :correo, :sexo,
                :birth_date, :instruction_level, :profession, :occupation, :speaks_english
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
            'birth_date' => ($person['perfechanacimiento'] ?? '') !== '' ? $person['perfechanacimiento'] : null,
            'instruction_level' => (int) ($person['istid'] ?? 0) > 0 ? (int) $person['istid'] : null,
            'profession' => ($person['perprofesion'] ?? '') !== '' ? $person['perprofesion'] : null,
            'occupation' => ($person['perocupacion'] ?? '') !== '' ? $person['perocupacion'] : null,
            'speaks_english' => !empty($person['perhablaingles']),
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
                estid, curid, matfecha, matfoto, emdid, tmaid
             ) VALUES (
                :student_id, :course_id, :date, :photo, :status_id, :type_id
             ) RETURNING matid"
        );
        $statement->execute([
            'student_id' => $studentId,
            'course_id' => $matricula['curid'],
            'date' => $matricula['matfecha'],
            'photo' => $photoPath,
            'status_id' => $matricula['emdid'],
            'type_id' => $matricula['tmaid'],
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

    private function persistFamilies(int $studentId, int $studentPersonId, array $families): array
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
                'perfechanacimiento' => $family['perfechanacimiento'] ?? '',
                'istid' => $family['istid'] ?? 0,
                'perprofesion' => $family['perprofesion'] ?? '',
                'perocupacion' => $family['perocupacion'] ?? '',
                'perhablaingles' => !empty($family['perhablaingles']),
            ]);

            if ($personId === $studentPersonId) {
                throw new RuntimeException('El estudiante no puede registrarse como su propio familiar.');
            }

            $this->upsertFamily($studentId, $personId, $family);

            $persisted[$index] = [
                'perid' => $personId,
                'pteid' => (int) $family['pteid'],
            ];
        }

        return $persisted;
    }

    private function resolveRepresentative(int $studentPersonId, array $representative, array $familyRepresentatives): array
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
                'perfechanacimiento' => $external['perfechanacimiento'] ?? '',
                'istid' => $external['istid'] ?? 0,
                'perprofesion' => $external['perprofesion'] ?? '',
                'perocupacion' => $external['perocupacion'] ?? '',
                'perhablaingles' => !empty($external['perhablaingles']),
            ]);

            if ($personId === $studentPersonId) {
                throw new RuntimeException('El estudiante no puede registrarse como su propio representante.');
            }

            return [
                'perid' => $personId,
                'pteid' => (int) $external['pteid'],
            ];
        }

        $familyIndex = (int) ($representative['family_index'] ?? -1);

        if (!array_key_exists($familyIndex, $familyRepresentatives)) {
            throw new RuntimeException('Debe seleccionar un representante valido.');
        }

        if ((int) ($familyRepresentatives[$familyIndex]['perid'] ?? 0) === $studentPersonId) {
            throw new RuntimeException('El estudiante no puede registrarse como su propio representante.');
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
                     famlugardetrabajo = :workplace
                 WHERE famid = :id"
            );
            $statement->execute([
                'id' => $existing['famid'],
                'civil_status' => (int) ($family['eciid'] ?? 0) > 0 ? (int) $family['eciid'] : null,
                'workplace' => ($family['famlugardetrabajo'] ?? '') !== '' ? $family['famlugardetrabajo'] : null,
            ]);

            return;
        }

        $statement = $this->db->prepare(
            "INSERT INTO familiar (
                estid, perid, pteid, eciid, famlugardetrabajo
             ) VALUES (
                :student_id, :person_id, :relationship_id, :civil_status, :workplace
             )"
        );
        $statement->execute([
            'student_id' => $studentId,
            'person_id' => $personId,
            'relationship_id' => $family['pteid'],
            'civil_status' => (int) ($family['eciid'] ?? 0) > 0 ? (int) $family['eciid'] : null,
            'workplace' => ($family['famlugardetrabajo'] ?? '') !== '' ? $family['famlugardetrabajo'] : null,
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

    private function insertMatriculationResources(int $matriculaId, array $resources): void
    {
        $statement = $this->db->prepare(
            "INSERT INTO matricula_recurso_tecnologico (
                matid, mrtinternet, mrtcomputador, mrtlaptop, mrttablet, mrtcelular, mrtimpresora
             ) VALUES (
                :matid, :internet, :computador, :laptop, :tablet, :celular, :impresora
             )"
        );
        $statement->bindValue(':matid', $matriculaId, PDO::PARAM_INT);
        $statement->bindValue(':internet', (bool) ($resources['mrtinternet'] ?? false), PDO::PARAM_BOOL);
        $statement->bindValue(':computador', (bool) ($resources['mrtcomputador'] ?? false), PDO::PARAM_BOOL);
        $statement->bindValue(':laptop', (bool) ($resources['mrtlaptop'] ?? false), PDO::PARAM_BOOL);
        $statement->bindValue(':tablet', (bool) ($resources['mrttablet'] ?? false), PDO::PARAM_BOOL);
        $statement->bindValue(':celular', (bool) ($resources['mrtcelular'] ?? false), PDO::PARAM_BOOL);
        $statement->bindValue(':impresora', (bool) ($resources['mrtimpresora'] ?? false), PDO::PARAM_BOOL);
        $statement->execute();
    }

    private function insertMatriculationInsurance(int $matriculaId, array $insurance): void
    {
        $insuranceId = (int) ($insurance['smid'] ?? 0);

        if ($insuranceId <= 0) {
            return;
        }

        $statement = $this->db->prepare(
            "INSERT INTO matricula_seguro_medico (
                matid, smid, msmtelefono, msmobservacion
             ) VALUES (
                :matid, :smid, :telefono, :observacion
             )"
        );
        $statement->execute([
            'matid' => $matriculaId,
            'smid' => $insuranceId,
            'telefono' => ($insurance['msmtelefono'] ?? '') !== '' ? $insurance['msmtelefono'] : null,
            'observacion' => ($insurance['msmobservacion'] ?? '') !== '' ? $insurance['msmobservacion'] : null,
        ]);
    }

    private function upsertStudentFamilyContext(int $studentId, array $context): void
    {
        $existing = $this->findSingleByStudent('estudiante_contexto_familiar', 'ecfid', $studentId);

        $payload = [
            'student_id' => $studentId,
            'convive_con' => ($context['ecfconvivecon'] ?? '') !== '' ? $context['ecfconvivecon'] : null,
            'numero_hermanos' => ($context['ecfnumerohermanos'] ?? '') !== '' ? (int) $context['ecfnumerohermanos'] : null,
            'posicion' => ($context['ecfposicionhermanos'] ?? '') !== '' ? $context['ecfposicionhermanos'] : null,
        ];

        if ($existing !== false) {
            $statement = $this->db->prepare(
                "UPDATE estudiante_contexto_familiar
                 SET ecfconvivecon = :convive_con,
                     ecfnumerohermanos = :numero_hermanos,
                     ecfposicionhermanos = :posicion,
                     ecffecha_modificacion = CURRENT_TIMESTAMP
                 WHERE ecfid = :id"
            );
            $payload['id'] = (int) $existing['id'];
            $statement->execute($payload);
            return;
        }

        $statement = $this->db->prepare(
            "INSERT INTO estudiante_contexto_familiar (
                estid, ecfconvivecon, ecfnumerohermanos, ecfposicionhermanos
             ) VALUES (
                :student_id, :convive_con, :numero_hermanos, :posicion
             )"
        );
        $statement->execute($payload);
    }

    private function upsertStudentHousing(int $studentId, array $housing): void
    {
        $existing = $this->findSingleByStudent('estudiante_vivienda', 'estvid', $studentId);

        $payload = [
            'student_id' => $studentId,
            'housing_condition' => (int) ($housing['cviid'] ?? 0) > 0 ? (int) $housing['cviid'] : null,
            'description' => ($housing['estvdescripcion'] ?? '') !== '' ? $housing['estvdescripcion'] : null,
            'electricity' => !empty($housing['estvluzelectrica']),
            'water' => !empty($housing['estvaguapotable']),
            'sshh' => !empty($housing['estvsshh']),
            'telephone' => !empty($housing['estvtelefono']),
            'cable' => !empty($housing['estvcable']),
        ];

        if ($payload['housing_condition'] === null && $payload['description'] === null
            && !$payload['electricity'] && !$payload['water'] && !$payload['sshh']
            && !$payload['telephone'] && !$payload['cable']) {
            return;
        }

        if ($existing !== false) {
            $statement = $this->db->prepare(
                "UPDATE estudiante_vivienda
                 SET cviid = :housing_condition,
                     estvdescripcion = :description,
                     estvluzelectrica = :electricity,
                     estvaguapotable = :water,
                     estvsshh = :sshh,
                     estvtelefono = :telephone,
                     estvcable = :cable,
                     estvfecha_modificacion = CURRENT_TIMESTAMP
                 WHERE estvid = :id"
            );
            $payload['id'] = (int) $existing['id'];
            $statement->execute($payload);
            return;
        }

        $statement = $this->db->prepare(
            "INSERT INTO estudiante_vivienda (
                estid, cviid, estvdescripcion, estvluzelectrica, estvaguapotable, estvsshh, estvtelefono, estvcable
             ) VALUES (
                :student_id, :housing_condition, :description, :electricity, :water, :sshh, :telephone, :cable
             )"
        );
        $statement->execute($payload);
    }

    private function upsertStudentHealthContext(int $studentId, array $context): void
    {
        $existing = $this->findSingleByStudent('estudiante_contexto_salud', 'ecsid', $studentId);

        $payload = [
            'student_id' => $studentId,
            'blood_group' => (int) ($context['gsid'] ?? 0) > 0 ? (int) $context['gsid'] : null,
            'has_disability' => !empty($context['ecstienediscapacidad']),
            'disability_detail' => ($context['ecsdetallediscapacidad'] ?? '') !== '' ? $context['ecsdetallediscapacidad'] : null,
            'medical_care' => (int) ($context['amid'] ?? 0) > 0 ? (int) $context['amid'] : null,
        ];

        if ($existing !== false) {
            $statement = $this->db->prepare(
                "UPDATE estudiante_contexto_salud
                 SET gsid = :blood_group,
                     ecstienediscapacidad = :has_disability,
                     ecsdetallediscapacidad = :disability_detail,
                     amid = :medical_care,
                     ecsfecha_modificacion = CURRENT_TIMESTAMP
                 WHERE ecsid = :id"
            );
            $payload['id'] = (int) $existing['id'];
            $statement->execute($payload);
            return;
        }

        $statement = $this->db->prepare(
            "INSERT INTO estudiante_contexto_salud (
                estid, gsid, ecstienediscapacidad, ecsdetallediscapacidad, amid
             ) VALUES (
                :student_id, :blood_group, :has_disability, :disability_detail, :medical_care
             )"
        );
        $statement->execute($payload);
    }

    private function replaceStudentHealthConditions(int $studentId, array $conditions): void
    {
        $deleteStatement = $this->db->prepare(
            "DELETE FROM estudiante_condicion_salud
             WHERE estid = :student_id"
        );
        $deleteStatement->execute(['student_id' => $studentId]);

        $insertStatement = $this->db->prepare(
            "INSERT INTO estudiante_condicion_salud (
                estid, tcsid, ecsadescripcion, ecsamedicamentos, ecsaobservacion, ecsavigente
             ) VALUES (
                :student_id, :type_id, :description, :medications, :observation, :is_active
             )"
        );

        foreach ($conditions as $condition) {
            $typeId = (int) ($condition['tcsid'] ?? 0);
            $description = trim((string) ($condition['ecsadescripcion'] ?? ''));

            if ($typeId <= 0 && $description === '') {
                continue;
            }

            if ($typeId <= 0 || $description === '') {
                continue;
            }

            $insertStatement->execute([
                'student_id' => $studentId,
                'type_id' => $typeId,
                'description' => $description,
                'medications' => ($condition['ecsamedicamentos'] ?? '') !== '' ? $condition['ecsamedicamentos'] : null,
                'observation' => ($condition['ecsaobservacion'] ?? '') !== '' ? $condition['ecsaobservacion'] : null,
                'is_active' => !array_key_exists('ecsavigente', $condition) || !empty($condition['ecsavigente']),
            ]);
        }
    }

    private function insertStudentHealthMeasurement(int $studentId, array $measurement): void
    {
        $weight = trim((string) ($measurement['emspeso'] ?? ''));
        $height = trim((string) ($measurement['emstalla'] ?? ''));
        $date = trim((string) ($measurement['emsfecha_medicion'] ?? ''));

        if ($weight === '' || $height === '' || $date === '') {
            return;
        }

        $statement = $this->db->prepare(
            "INSERT INTO estudiante_medicion_salud (
                estid, emspeso, emstalla, emsimc, emsfecha_medicion, emsobservacion
             ) VALUES (
                :student_id, :weight, :height, :imc, :measurement_date, :observation
             )"
        );
        $statement->execute([
            'student_id' => $studentId,
            'weight' => (float) $weight,
            'height' => (float) $height,
            'imc' => (float) ($measurement['emsimc'] ?? 0),
            'measurement_date' => $date,
            'observation' => ($measurement['emsobservacion'] ?? '') !== '' ? $measurement['emsobservacion'] : null,
        ]);
    }

    private function upsertStudentVitalHistory(int $studentId, array $history): void
    {
        $existing = $this->findSingleByStudent('estudiante_historia_vital', 'ehvid', $studentId);
        $payload = [
            'student_id' => $studentId,
            'mother_age' => ($history['ehvedadmadre'] ?? '') !== '' ? (int) $history['ehvedadmadre'] : null,
            'pregnancy_complications' => ($history['ehvcomplicacionesembarazo'] ?? '') !== '' ? $history['ehvcomplicacionesembarazo'] : null,
            'pregnancy_medication' => ($history['ehvmedicacionembarazo'] ?? '') !== '' ? $history['ehvmedicacionembarazo'] : null,
            'pregnancy_type' => (int) ($history['teid'] ?? 0) > 0 ? (int) $history['teid'] : null,
            'birth_type' => (int) ($history['tpid'] ?? 0) > 0 ? (int) $history['tpid'] : null,
            'pregnancy_detail' => ($history['ehvdetalleembarazo'] ?? '') !== '' ? $history['ehvdetalleembarazo'] : null,
            'birth_weight' => ($history['ehvpesonacer'] ?? '') !== '' ? $history['ehvpesonacer'] : null,
            'birth_height' => ($history['ehvtallanacer'] ?? '') !== '' ? $history['ehvtallanacer'] : null,
            'walk_age' => ($history['ehvedadcaminar'] ?? '') !== '' ? $history['ehvedadcaminar'] : null,
            'speak_age' => ($history['ehvedadhablar'] ?? '') !== '' ? $history['ehvedadhablar'] : null,
            'lactation_period' => ($history['ehvperiodolactancia'] ?? '') !== '' ? $history['ehvperiodolactancia'] : null,
            'bottle_age' => ($history['ehvedadbiberon'] ?? '') !== '' ? $history['ehvedadbiberon'] : null,
            'sphincter_age' => ($history['ehvedadcontrolesfinteres'] ?? '') !== '' ? $history['ehvedadcontrolesfinteres'] : null,
        ];

        if ($existing !== false) {
            $statement = $this->db->prepare(
                "UPDATE estudiante_historia_vital
                 SET ehvedadmadre = :mother_age,
                     ehvcomplicacionesembarazo = :pregnancy_complications,
                     ehvmedicacionembarazo = :pregnancy_medication,
                     teid = :pregnancy_type,
                     tpid = :birth_type,
                     ehvdetalleembarazo = :pregnancy_detail,
                     ehvpesonacer = :birth_weight,
                     ehvtallanacer = :birth_height,
                     ehvedadcaminar = :walk_age,
                     ehvedadhablar = :speak_age,
                     ehvperiodolactancia = :lactation_period,
                     ehvedadbiberon = :bottle_age,
                     ehvedadcontrolesfinteres = :sphincter_age,
                     ehvfecha_modificacion = CURRENT_TIMESTAMP
                 WHERE ehvid = :id"
            );
            $payload['id'] = (int) $existing['id'];
            $statement->execute($payload);
            return;
        }

        $statement = $this->db->prepare(
            "INSERT INTO estudiante_historia_vital (
                estid, ehvedadmadre, ehvcomplicacionesembarazo, ehvmedicacionembarazo, teid, tpid,
                ehvdetalleembarazo, ehvpesonacer, ehvtallanacer, ehvedadcaminar, ehvedadhablar,
                ehvperiodolactancia, ehvedadbiberon, ehvedadcontrolesfinteres
             ) VALUES (
                :student_id, :mother_age, :pregnancy_complications, :pregnancy_medication, :pregnancy_type, :birth_type,
                :pregnancy_detail, :birth_weight, :birth_height, :walk_age, :speak_age,
                :lactation_period, :bottle_age, :sphincter_age
             )"
        );
        $statement->execute($payload);
    }

    private function upsertStudentAcademicContext(int $studentId, array $context): void
    {
        $existing = $this->findSingleByStudent('estudiante_contexto_academico', 'ecaid', $studentId);
        $payload = [
            'student_id' => $studentId,
            'entry_date' => ($context['ecafechaingresoinstitucion'] ?? '') !== '' ? $context['ecafechaingresoinstitucion'] : null,
            'repeated_years' => !empty($context['ecaharepetidoanios']),
            'repetition_detail' => ($context['ecadetallerepeticion'] ?? '') !== '' ? $context['ecadetallerepeticion'] : null,
            'preferred_subjects' => ($context['ecaasignaturaspreferencia'] ?? '') !== '' ? $context['ecaasignaturaspreferencia'] : null,
            'difficult_subjects' => ($context['ecaasignaturasdificultad'] ?? '') !== '' ? $context['ecaasignaturasdificultad'] : null,
            'extra_activities' => ($context['ecaactividadesextras'] ?? '') !== '' ? $context['ecaactividadesextras'] : null,
        ];

        if ($existing !== false) {
            $statement = $this->db->prepare(
                "UPDATE estudiante_contexto_academico
                 SET ecafechaingresoinstitucion = :entry_date,
                     ecaharepetidoanios = :repeated_years,
                     ecadetallerepeticion = :repetition_detail,
                     ecaasignaturaspreferencia = :preferred_subjects,
                     ecaasignaturasdificultad = :difficult_subjects,
                     ecaactividadesextras = :extra_activities,
                     ecafecha_modificacion = CURRENT_TIMESTAMP
                 WHERE ecaid = :id"
            );
            $payload['id'] = (int) $existing['id'];
            $statement->execute($payload);
            return;
        }

        $statement = $this->db->prepare(
            "INSERT INTO estudiante_contexto_academico (
                estid, ecafechaingresoinstitucion, ecaharepetidoanios, ecadetallerepeticion,
                ecaasignaturaspreferencia, ecaasignaturasdificultad, ecaactividadesextras
             ) VALUES (
                :student_id, :entry_date, :repeated_years, :repetition_detail,
                :preferred_subjects, :difficult_subjects, :extra_activities
             )"
        );
        $statement->execute($payload);
    }

    private function insertMatriculationBilling(int $matriculaId, array $billing): void
    {
        $statement = $this->db->prepare(
            "INSERT INTO matricula_facturacion (
                matid, mfcnombre, mfctipoidentificacion, mfcidentificacion, mfcdireccion, mfccorreo, mfctelefono
             ) VALUES (
                :matid, :nombre, :tipo_identificacion, :identificacion, :direccion, :correo, :telefono
             )"
        );
        $statement->execute([
            'matid' => $matriculaId,
            'nombre' => $billing['mfcnombre'],
            'tipo_identificacion' => $billing['mfctipoidentificacion'],
            'identificacion' => $billing['mfcidentificacion'],
            'direccion' => ($billing['mfcdireccion'] ?? '') !== '' ? $billing['mfcdireccion'] : null,
            'correo' => ($billing['mfccorreo'] ?? '') !== '' ? $billing['mfccorreo'] : null,
            'telefono' => ($billing['mfctelefono'] ?? '') !== '' ? $billing['mfctelefono'] : null,
        ]);
    }

    private function insertDocumentAcceptances(int $matriculaId, array $documents, array $acceptedDocumentIds): void
    {
        if ($documents === []) {
            return;
        }

        $acceptedIndex = [];

        foreach ($acceptedDocumentIds as $documentId) {
            $normalizedId = (int) $documentId;

            if ($normalizedId > 0) {
                $acceptedIndex[$normalizedId] = true;
            }
        }

        $statement = $this->db->prepare(
            "INSERT INTO matricula_aceptacion_documentos (
                matid, domid, madaceptado, madfecha_aceptacion
             ) VALUES (
                :matid, :domid, :aceptado, :fecha_aceptacion
             )"
        );

        foreach ($documents as $document) {
            $documentId = (int) ($document['domid'] ?? 0);

            if ($documentId <= 0) {
                continue;
            }

            $isAccepted = isset($acceptedIndex[$documentId]);

            $statement->bindValue(':matid', $matriculaId, PDO::PARAM_INT);
            $statement->bindValue(':domid', $documentId, PDO::PARAM_INT);
            $statement->bindValue(':aceptado', $isAccepted, PDO::PARAM_BOOL);
            $statement->bindValue(':fecha_aceptacion', $isAccepted ? date('Y-m-d H:i:s') : null);
            $statement->execute();
        }
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

    private function findSingleByStudent(string $table, string $primaryKey, int $studentId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT {$primaryKey} AS id
             FROM {$table}
             WHERE estid = :student_id
             LIMIT 1"
        );
        $statement->execute(['student_id' => $studentId]);

        return $statement->fetch();
    }
}
