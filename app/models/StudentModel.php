<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class StudentModel extends Model
{
    private const STUDENT_ROLE_NAME = 'Estudiante';

    protected string $table = 'estudiante';
    protected string $primaryKey = 'estid';

    public function allWithPerson(?int $periodId = null, array $filters = []): array
    {
        $periodFilter = $periodId !== null ? 'AND c.pleid = :period_id' : '';
        $search = trim((string) ($filters['q'] ?? ''));
        $courseId = (int) ($filters['curid'] ?? 0);
        $sort = (string) ($filters['sort'] ?? 'apellidos');
        $direction = strtolower((string) ($filters['direction'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
        $orderColumns = [
            'cedula' => 'p.percedula',
            'nombres' => 'p.pernombres',
            'apellidos' => 'p.perapellidos',
            'curso' => 'course_data.curso',
        ];
        $orderColumn = $orderColumns[$sort] ?? $orderColumns['apellidos'];
        $where = [];
        $params = [];

        if ($periodId !== null) {
            $params['period_id'] = $periodId;
        }

        if ($search !== '') {
            $where[] = "(p.percedula ILIKE :search
                OR p.pernombres ILIKE :search
                OR p.perapellidos ILIKE :search
                OR COALESCE(course_data.curso, '') ILIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        if ($courseId > 0) {
            $where[] = 'course_data.curid = :course_id';
            $params['course_id'] = $courseId;
        }

        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';
        $statement = $this->db->prepare(
            "SELECT
                e.estid,
                e.perid,
                p.percedula,
                p.pernombres,
                p.perapellidos,
                course_data.curid,
                course_data.curso
             FROM {$this->table} e
             INNER JOIN persona p ON p.perid = e.perid
             LEFT JOIN LATERAL (
                SELECT c.curid,
                       CONCAT(g.granombre, ' ', pr.prlnombre) AS curso
                FROM matricula m
                INNER JOIN curso c ON c.curid = m.curid
                INNER JOIN grado g ON g.graid = c.graid
                INNER JOIN paralelo pr ON pr.prlid = c.prlid
                WHERE m.estid = e.estid
                {$periodFilter}
                ORDER BY m.matfecha DESC, m.matid DESC
                LIMIT 1
             ) course_data ON true
             {$whereSql}
             ORDER BY {$orderColumn} {$direction} NULLS LAST, p.perapellidos ASC, p.pernombres ASC"
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function findDetailed(int $studentId, ?int $periodId = null): array|false
    {
        $periodFilter = $periodId !== null ? 'AND c.pleid = :period_id' : '';
        $statement = $this->db->prepare(
            "SELECT
                e.estid,
                e.perid,
                e.estlugarnacimiento,
                e.estdireccion,
                e.estparroquia,
                e.estestado,
                p.percedula,
                p.pernombres,
                p.perapellidos,
                p.pertelefono1,
                p.pertelefono2,
                p.percorreo,
                p.persexo,
                p.perfechanacimiento,
                p.eciid,
                p.istid,
                p.perprofesion,
                p.perocupacion,
                p.perhablaingles,
                course_data.matid,
                course_data.curid,
                course_data.curso
             FROM {$this->table} e
             INNER JOIN persona p ON p.perid = e.perid
             LEFT JOIN LATERAL (
                SELECT m.matid,
                       c.curid,
                       CONCAT(g.granombre, ' ', pr.prlnombre) AS curso
                FROM matricula m
                INNER JOIN curso c ON c.curid = m.curid
                INNER JOIN grado g ON g.graid = c.graid
                INNER JOIN paralelo pr ON pr.prlid = c.prlid
                WHERE m.estid = e.estid
                {$periodFilter}
                ORDER BY m.matfecha DESC, m.matid DESC
                LIMIT 1
             ) course_data ON true
             WHERE e.estid = :student_id
             LIMIT 1"
        );
        $params = ['student_id' => $studentId];
        if ($periodId !== null) {
            $params['period_id'] = $periodId;
        }
        $statement->execute($params);

        return $statement->fetch();
    }

    public function findByPersonId(int $personId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT estid, perid, estestado
             FROM {$this->table}
             WHERE perid = :person_id
             LIMIT 1"
        );
        $statement->execute(['person_id' => $personId]);

        return $statement->fetch();
    }

    public function profile(int $studentId, ?int $periodId = null): array|false
    {
        $student = $this->findDetailed($studentId, $periodId);

        if ($student === false) {
            return false;
        }

        $matriculation = $this->findProfileMatriculation($studentId, $periodId);
        $matriculationId = is_array($matriculation) ? (int) $matriculation['matid'] : 0;

        return [
            'student' => $student,
            'matriculation' => $matriculation,
            'representative' => $matriculationId > 0 ? $this->findMatriculationRepresentative($matriculationId) : false,
            'families' => $this->familiesByStudent($studentId),
            'health_context' => $this->healthContextByStudent($studentId),
            'health_conditions' => $this->healthConditionsByStudent($studentId),
            'health_measurement' => $this->latestHealthMeasurementByStudent($studentId),
            'health_measurements' => $this->healthMeasurementsByStudent($studentId),
            'health_insurance' => $matriculationId > 0 ? $this->insuranceByMatriculation($matriculationId) : false,
            'vital_history' => $this->vitalHistoryByStudent($studentId),
            'academic_context' => $this->academicContextByStudent($studentId),
            'resources' => $matriculationId > 0 ? $this->resourcesByMatriculation($matriculationId) : false,
            'billing' => $matriculationId > 0 ? $this->billingByMatriculation($matriculationId) : false,
            'documents' => $matriculationId > 0 ? $this->documentsByMatriculation($matriculationId) : [],
            'matriculations' => $this->matriculationsByStudent($studentId),
        ];
    }

    public function allByRepresentativePerson(int $representativePersonId, ?int $periodId = null): array
    {
        $periodFilter = $periodId !== null ? 'AND c.pleid = :period_id' : '';
        $statement = $this->db->prepare(
            "SELECT DISTINCT ON (e.estid)
                e.estid,
                e.perid,
                e.estestado,
                p.percedula,
                p.pernombres,
                p.perapellidos,
                m.matid,
                pl.pledescripcion,
                CONCAT(g.granombre, ' ', pr.prlnombre) AS curso
             FROM matricula_representante mr
             INNER JOIN matricula m ON m.matid = mr.matid
             INNER JOIN estudiante e ON e.estid = m.estid
             INNER JOIN persona p ON p.perid = e.perid
             INNER JOIN curso c ON c.curid = m.curid
             INNER JOIN periodo_lectivo pl ON pl.pleid = c.pleid
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN paralelo pr ON pr.prlid = c.prlid
             WHERE mr.perid = :representative_person_id
             {$periodFilter}
             ORDER BY e.estid, m.matfecha DESC, m.matid DESC"
        );
        $params = ['representative_person_id' => $representativePersonId];

        if ($periodId !== null) {
            $params['period_id'] = $periodId;
        }

        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function representativeCanAccessStudent(int $representativePersonId, int $studentId): bool
    {
        $statement = $this->db->prepare(
            "SELECT 1
             FROM matricula_representante mr
             INNER JOIN matricula m ON m.matid = mr.matid
             WHERE mr.perid = :representative_person_id
               AND m.estid = :student_id
             LIMIT 1"
        );
        $statement->execute([
            'representative_person_id' => $representativePersonId,
            'student_id' => $studentId,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function representativeByStudentAndPerson(int $studentId, int $representativePersonId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT mr.perid, mr.pteid,
                    p.percedula, p.pernombres, p.perapellidos, p.pertelefono1, p.pertelefono2,
                    p.percorreo, p.persexo, p.perfechanacimiento, p.eciid, p.istid,
                    p.perprofesion, p.perocupacion, p.perlugardetrabajo, p.perhablaingles,
                    pt.ptenombre
             FROM matricula_representante mr
             INNER JOIN matricula m ON m.matid = mr.matid
             INNER JOIN persona p ON p.perid = mr.perid
             INNER JOIN parentesco pt ON pt.pteid = mr.pteid
             WHERE m.estid = :student_id
               AND mr.perid = :representative_person_id
             ORDER BY m.matfecha DESC, m.matid DESC
             LIMIT 1"
        );
        $statement->execute([
            'student_id' => $studentId,
            'representative_person_id' => $representativePersonId,
        ]);

        return $statement->fetch();
    }

    private function findProfileMatriculation(int $studentId, ?int $periodId = null): array|false
    {
        $periodFilter = $periodId !== null ? 'AND c.pleid = :period_id' : '';
        $statement = $this->db->prepare(
            "SELECT m.matid, m.matfecha, m.matfoto, m.curid, m.emdid, m.tmaid,
                    em.emdnombre,
                    tm.tmanombre,
                    pl.pledescripcion,
                    n.nednombre,
                    g.granombre,
                    pr.prlnombre,
                    CONCAT(g.granombre, ' ', pr.prlnombre) AS curso
             FROM matricula m
             INNER JOIN curso c ON c.curid = m.curid
             INNER JOIN periodo_lectivo pl ON pl.pleid = c.pleid
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN nivel_educativo n ON n.nedid = g.nedid
             INNER JOIN paralelo pr ON pr.prlid = c.prlid
             INNER JOIN estado_matricula em ON em.emdid = m.emdid
             INNER JOIN tipo_matricula tm ON tm.tmaid = m.tmaid
             WHERE m.estid = :student_id
             {$periodFilter}
             ORDER BY m.matfecha DESC, m.matid DESC
             LIMIT 1"
        );
        $params = ['student_id' => $studentId];
        if ($periodId !== null) {
            $params['period_id'] = $periodId;
        }
        $statement->execute($params);

        return $statement->fetch();
    }

    private function findMatriculationRepresentative(int $matriculationId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT mr.mreid, mr.perid, mr.pteid,
                    p.percedula, p.pernombres, p.perapellidos, p.pertelefono1, p.pertelefono2,
                    p.percorreo, p.persexo, p.perfechanacimiento, p.eciid, p.istid,
                    p.perprofesion, p.perocupacion, p.perlugardetrabajo, p.perhablaingles,
                    pt.ptenombre
             FROM matricula_representante mr
             INNER JOIN persona p ON p.perid = mr.perid
             INNER JOIN parentesco pt ON pt.pteid = mr.pteid
             WHERE mr.matid = :matriculation_id
             LIMIT 1"
        );
        $statement->execute(['matriculation_id' => $matriculationId]);

        return $statement->fetch();
    }

    private function familiesByStudent(int $studentId): array
    {
        $statement = $this->db->prepare(
            "SELECT f.famid, f.perid, f.pteid,
                    p.percedula, p.pernombres, p.perapellidos, p.pertelefono1, p.pertelefono2,
                    p.percorreo, p.persexo, p.perfechanacimiento, p.eciid, p.istid,
                    p.perprofesion, p.perocupacion, p.perlugardetrabajo, p.perhablaingles,
                    pt.ptenombre
             FROM familiar f
             INNER JOIN persona p ON p.perid = f.perid
             INNER JOIN parentesco pt ON pt.pteid = f.pteid
             WHERE f.estid = :student_id
             ORDER BY pt.ptenombre ASC, p.perapellidos ASC, p.pernombres ASC"
        );
        $statement->execute(['student_id' => $studentId]);

        return $statement->fetchAll();
    }

    private function healthContextByStudent(int $studentId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT ecs.*, gs.gsnombre, am.amnombre
             FROM estudiante_contexto_salud ecs
             LEFT JOIN grupo_sanguineo gs ON gs.gsid = ecs.gsid
             LEFT JOIN atencion_medica am ON am.amid = ecs.amid
             WHERE ecs.estid = :student_id
             LIMIT 1"
        );
        $statement->execute(['student_id' => $studentId]);

        return $statement->fetch();
    }

    private function healthConditionsByStudent(int $studentId): array
    {
        $statement = $this->db->prepare(
            "SELECT ecs.ecsaid, ecs.tcsid, ecs.ecsadescripcion, ecs.ecsamedicamentos,
                    ecs.ecsaobservacion, ecs.ecsavigente, tcs.tcsnombre
             FROM estudiante_condicion_salud ecs
             INNER JOIN tipo_condicion_salud tcs ON tcs.tcsid = ecs.tcsid
             WHERE ecs.estid = :student_id
             ORDER BY ecs.ecsafecha_registro DESC"
        );
        $statement->execute(['student_id' => $studentId]);

        return $statement->fetchAll();
    }

    private function latestHealthMeasurementByStudent(int $studentId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT emsid, emspeso, (emstalla * 100) AS emstalla, emsimc, emsfecha_medicion, emsobservacion
             FROM estudiante_medicion_salud
             WHERE estid = :student_id
             ORDER BY emsfecha_medicion DESC, emsid DESC
             LIMIT 1"
        );
        $statement->execute(['student_id' => $studentId]);

        return $statement->fetch();
    }

    private function healthMeasurementsByStudent(int $studentId): array
    {
        $statement = $this->db->prepare(
            "SELECT emsid, emspeso, (emstalla * 100) AS emstalla, emsimc, emsfecha_medicion, emsobservacion
             FROM estudiante_medicion_salud
             WHERE estid = :student_id
             ORDER BY emsfecha_medicion ASC, emsid ASC"
        );
        $statement->execute(['student_id' => $studentId]);

        return $statement->fetchAll();
    }

    private function vitalHistoryByStudent(int $studentId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT *
             FROM estudiante_historia_vital
             WHERE estid = :student_id
             LIMIT 1"
        );
        $statement->execute(['student_id' => $studentId]);

        return $statement->fetch();
    }

    private function insuranceByMatriculation(int $matriculationId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT msm.*, sm.smnombre
             FROM matricula_seguro_medico msm
             INNER JOIN seguro_medico sm ON sm.smid = msm.smid
             WHERE msm.matid = :matriculation_id
             LIMIT 1"
        );
        $statement->execute(['matriculation_id' => $matriculationId]);

        return $statement->fetch();
    }

    private function academicContextByStudent(int $studentId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT *
             FROM estudiante_contexto_academico
             WHERE estid = :student_id
             LIMIT 1"
        );
        $statement->execute(['student_id' => $studentId]);

        return $statement->fetch();
    }

    private function resourcesByMatriculation(int $matriculationId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT *
             FROM matricula_recurso_tecnologico
             WHERE matid = :matriculation_id
             LIMIT 1"
        );
        $statement->execute(['matriculation_id' => $matriculationId]);

        return $statement->fetch();
    }

    private function billingByMatriculation(int $matriculationId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT *
             FROM matricula_facturacion
             WHERE matid = :matriculation_id
             LIMIT 1"
        );
        $statement->execute(['matriculation_id' => $matriculationId]);

        return $statement->fetch();
    }

    private function documentsByMatriculation(int $matriculationId): array
    {
        $statement = $this->db->prepare(
            "SELECT d.domid, d.domnombre, d.domobligatorio, mad.madaceptado, mad.madfecha_aceptacion
             FROM matricula_aceptacion_documentos mad
             INNER JOIN documento_matricula d ON d.domid = mad.domid
             WHERE mad.matid = :matriculation_id
             ORDER BY d.domnombre ASC"
        );
        $statement->execute(['matriculation_id' => $matriculationId]);

        return $statement->fetchAll();
    }

    private function matriculationsByStudent(int $studentId): array
    {
        $statement = $this->db->prepare(
            "SELECT m.matfecha, em.emdnombre, pl.pledescripcion,
                    CONCAT(g.granombre, ' ', pr.prlnombre) AS curso
             FROM matricula m
             INNER JOIN curso c ON c.curid = m.curid
             INNER JOIN periodo_lectivo pl ON pl.pleid = c.pleid
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN paralelo pr ON pr.prlid = c.prlid
             INNER JOIN estado_matricula em ON em.emdid = m.emdid
             WHERE m.estid = :student_id
             ORDER BY pl.plefechainicio DESC, m.matfecha DESC"
        );
        $statement->execute(['student_id' => $studentId]);

        return $statement->fetchAll();
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            "INSERT INTO {$this->table} (
                perid,
                estlugarnacimiento,
                estdireccion,
                estparroquia,
                estestado
            ) VALUES (
                :perid,
                :lugarnacimiento,
                :direccion,
                :parroquia,
                :estado
            )
            RETURNING estid"
        );

        $statement->execute([
            'perid' => $data['perid'],
            'lugarnacimiento' => $data['estlugarnacimiento'] !== '' ? $data['estlugarnacimiento'] : null,
            'direccion' => $data['estdireccion'] !== '' ? $data['estdireccion'] : null,
            'parroquia' => $data['estparroquia'] !== '' ? $data['estparroquia'] : null,
            'estado' => $data['estestado'],
        ]);

        $studentId = (int) $statement->fetchColumn();
        $userModel = new UserModel();
        $userModel->assignRoleByPerson((int) $data['perid'], self::STUDENT_ROLE_NAME);

        return $studentId;
    }

    public function existsByPersonId(int $personId): bool
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

    public function personIsStudent(int $personId): bool
    {
        return $this->existsByPersonId($personId);
    }

    public function updateDetailed(array $data): void
    {
        $this->db->beginTransaction();

        try {
            $personStatement = $this->db->prepare(
                "UPDATE persona
                 SET percedula = :cedula,
                     pernombres = :nombres,
                     perapellidos = :apellidos,
                     pertelefono1 = :telefono1,
                     pertelefono2 = :telefono2,
                     percorreo = :correo,
                     persexo = :sexo,
                     perfechanacimiento = :nacimiento,
                     perprofesion = :profesion,
                     perocupacion = :ocupacion
                 WHERE perid = :person_id"
            );
            $personStatement->execute([
                'person_id' => $data['perid'],
                'cedula' => $data['percedula'],
                'nombres' => $data['pernombres'],
                'apellidos' => $data['perapellidos'],
                'telefono1' => $data['pertelefono1'] !== '' ? $data['pertelefono1'] : null,
                'telefono2' => $data['pertelefono2'] !== '' ? $data['pertelefono2'] : null,
                'correo' => $data['percorreo'] !== '' ? $data['percorreo'] : null,
                'sexo' => $data['persexo'] !== '' ? $data['persexo'] : null,
                'nacimiento' => $data['perfechanacimiento'] !== '' ? $data['perfechanacimiento'] : null,
                'profesion' => $data['perprofesion'] !== '' ? $data['perprofesion'] : null,
                'ocupacion' => $data['perocupacion'] !== '' ? $data['perocupacion'] : null,
            ]);

            $studentStatement = $this->db->prepare(
                "UPDATE {$this->table}
                 SET estlugarnacimiento = :lugar_nacimiento,
                     estdireccion = :direccion,
                     estparroquia = :parroquia,
                     estestado = :estado
                 WHERE estid = :student_id"
            );
            $studentStatement->execute([
                'student_id' => $data['estid'],
                'lugar_nacimiento' => $data['estlugarnacimiento'] !== '' ? $data['estlugarnacimiento'] : null,
                'direccion' => $data['estdireccion'] !== '' ? $data['estdireccion'] : null,
                'parroquia' => $data['estparroquia'] !== '' ? $data['estparroquia'] : null,
                'estado' => $data['estestado'],
            ]);

            if ((int) ($data['matid'] ?? 0) > 0 && (int) ($data['curid'] ?? 0) > 0) {
                $matriculationStatement = $this->db->prepare(
                    "UPDATE matricula
                     SET curid = :course_id
                     WHERE matid = :matriculation_id"
                );
                $matriculationStatement->execute([
                    'matriculation_id' => $data['matid'],
                    'course_id' => $data['curid'],
                ]);
            }

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function updateModule(int $studentId, string $section, array $data, ?int $periodId = null): void
    {
        $profile = $this->profile($studentId, $periodId);

        if ($profile === false) {
            throw new \RuntimeException('El estudiante solicitado no existe.');
        }

        $student = $profile['student'];
        $matriculation = is_array($profile['matriculation'] ?? null) ? $profile['matriculation'] : null;
        $matriculationId = is_array($matriculation) ? (int) ($matriculation['matid'] ?? 0) : 0;

        if ($section === 'estudiante') {
            $data['estid'] = $studentId;
            $data['perid'] = (int) $student['perid'];
            $data['matid'] = $matriculationId;
            $this->updateDetailed($data);
            return;
        }

        $this->db->beginTransaction();

        try {
            if ($section === 'matricula') {
                $this->requireMatriculation($matriculationId);
                $this->updateMatriculationModule($matriculationId, $data);
            } elseif ($section === 'representante') {
                $this->requireMatriculation($matriculationId);
                $this->updateRepresentativeModule($matriculationId, $data);
            } elseif ($section === 'familiares') {
                $this->updateFamiliesModule($studentId, (int) $student['perid'], $data['families'] ?? []);
            } elseif ($section === 'salud') {
                $this->updateHealthPanelModule($studentId, $matriculationId, $data);
            } elseif ($section === 'academico') {
                $this->upsertAcademicModule($studentId, $data);
            } elseif ($section === 'recursos') {
                $this->requireMatriculation($matriculationId);
                $this->upsertResourcesModule($matriculationId, $data);
            } elseif ($section === 'facturacion') {
                $this->requireMatriculation($matriculationId);
                $this->upsertBillingModule($matriculationId, $data);
            } elseif ($section === 'documentos') {
                $this->requireMatriculation($matriculationId);
                $this->replaceDocumentsModule($matriculationId, $data['documents_catalog'] ?? [], $data['documents'] ?? []);
            }

            if ($this->db->inTransaction()) {
                $this->db->commit();
            }
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    private function requireMatriculation(int $matriculationId): void
    {
        if ($matriculationId <= 0) {
            throw new \RuntimeException('El estudiante no tiene matricula asociada en el periodo actual.');
        }
    }

    private function updateMatriculationModule(int $matriculationId, array $data): void
    {
        $statement = $this->db->prepare(
            "UPDATE matricula
             SET curid = :curid,
                 matfecha = :matfecha,
                 emdid = :emdid,
                 tmaid = :tmaid
             WHERE matid = :matid"
        );
        $statement->execute([
            'matid' => $matriculationId,
            'curid' => (int) ($data['curid'] ?? 0),
            'matfecha' => trim((string) ($data['matfecha'] ?? '')),
            'emdid' => (int) ($data['emdid'] ?? 0),
            'tmaid' => (int) ($data['tmaid'] ?? 0),
        ]);
    }

    private function updateRepresentativeModule(int $matriculationId, array $data): void
    {
        $representativeId = (int) ($data['perid'] ?? 0);
        $relationshipId = (int) ($data['pteid'] ?? 0);

        if ($representativeId <= 0 || $relationshipId <= 0) {
            throw new \RuntimeException('El representante debe tener persona y parentesco registrados.');
        }

        $this->updatePerson($representativeId, $data);

        $statement = $this->db->prepare(
            "INSERT INTO matricula_representante (matid, perid, pteid)
             VALUES (:matid, :perid, :pteid)
             ON CONFLICT (matid) DO UPDATE
             SET perid = EXCLUDED.perid,
                 pteid = EXCLUDED.pteid"
        );
        $statement->execute([
            'matid' => $matriculationId,
            'perid' => $representativeId,
            'pteid' => $relationshipId,
        ]);
    }

    private function updateFamiliesModule(int $studentId, int $studentPersonId, array $families): void
    {
        foreach ($families as $family) {
            $familyId = (int) ($family['famid'] ?? 0);
            $personId = (int) ($family['perid'] ?? 0);
            $relationshipId = (int) ($family['pteid'] ?? 0);

            if ($familyId <= 0 || $personId <= 0 || $relationshipId <= 0) {
                continue;
            }

            if ($personId === $studentPersonId) {
                throw new \RuntimeException('El estudiante no puede registrarse como su propio familiar.');
            }

            $family['perhablaingles'] = !empty($family['perhablaingles']);
            $this->updatePerson($personId, $family);

            $statement = $this->db->prepare(
                "UPDATE familiar
                 SET pteid = :pteid
                 WHERE famid = :famid
                   AND estid = :student_id"
            );
            $statement->execute([
                'famid' => $familyId,
                'student_id' => $studentId,
                'pteid' => $relationshipId,
            ]);
        }
    }

    private function updatePerson(int $personId, array $data): void
    {
        $existingStatement = $this->db->prepare(
            "SELECT *
             FROM persona
             WHERE perid = :person_id
             LIMIT 1"
        );
        $existingStatement->execute(['person_id' => $personId]);
        $existing = $existingStatement->fetch();

        if ($existing === false) {
            throw new \RuntimeException('La persona solicitada no existe.');
        }

        $statement = $this->db->prepare(
            "UPDATE persona
             SET percedula = :cedula,
                 pernombres = :nombres,
                 perapellidos = :apellidos,
                 pertelefono1 = :telefono1,
                 pertelefono2 = :telefono2,
                 percorreo = :correo,
                 persexo = :sexo,
                 perfechanacimiento = :birth_date,
                 eciid = :civil_status,
                 istid = :instruction_level,
                 perprofesion = :profession,
                 perocupacion = :occupation,
                 perlugardetrabajo = :workplace,
                 perhablaingles = :speaks_english
             WHERE perid = :person_id"
        );
        $statement->execute([
            'person_id' => $personId,
            'cedula' => trim((string) ($data['percedula'] ?? $existing['percedula'] ?? '')),
            'nombres' => trim((string) ($data['pernombres'] ?? $existing['pernombres'] ?? '')),
            'apellidos' => trim((string) ($data['perapellidos'] ?? $existing['perapellidos'] ?? '')),
            'telefono1' => trim((string) ($data['pertelefono1'] ?? $existing['pertelefono1'] ?? '')) !== '' ? trim((string) ($data['pertelefono1'] ?? $existing['pertelefono1'])) : null,
            'telefono2' => trim((string) ($data['pertelefono2'] ?? $existing['pertelefono2'] ?? '')) !== '' ? trim((string) ($data['pertelefono2'] ?? $existing['pertelefono2'])) : null,
            'correo' => trim((string) ($data['percorreo'] ?? $existing['percorreo'] ?? '')) !== '' ? trim((string) ($data['percorreo'] ?? $existing['percorreo'])) : null,
            'sexo' => trim((string) ($data['persexo'] ?? $existing['persexo'] ?? '')) !== '' ? trim((string) ($data['persexo'] ?? $existing['persexo'])) : null,
            'birth_date' => trim((string) ($data['perfechanacimiento'] ?? $existing['perfechanacimiento'] ?? '')) !== '' ? trim((string) ($data['perfechanacimiento'] ?? $existing['perfechanacimiento'])) : null,
            'civil_status' => (int) ($data['eciid'] ?? $existing['eciid'] ?? 0) > 0 ? (int) ($data['eciid'] ?? $existing['eciid']) : null,
            'instruction_level' => (int) ($data['istid'] ?? $existing['istid'] ?? 0) > 0 ? (int) ($data['istid'] ?? $existing['istid']) : null,
            'profession' => trim((string) ($data['perprofesion'] ?? $existing['perprofesion'] ?? '')) !== '' ? trim((string) ($data['perprofesion'] ?? $existing['perprofesion'])) : null,
            'occupation' => trim((string) ($data['perocupacion'] ?? $existing['perocupacion'] ?? '')) !== '' ? trim((string) ($data['perocupacion'] ?? $existing['perocupacion'])) : null,
            'workplace' => trim((string) ($data['perlugardetrabajo'] ?? $existing['perlugardetrabajo'] ?? '')) !== '' ? trim((string) ($data['perlugardetrabajo'] ?? $existing['perlugardetrabajo'])) : null,
            'speaks_english' => $this->dbBool(array_key_exists('perhablaingles', $data) ? !empty($data['perhablaingles']) : !empty($existing['perhablaingles'])),
        ]);
    }

    private function upsertHealthModule(int $studentId, array $data): void
    {
        $statement = $this->db->prepare(
            "INSERT INTO estudiante_contexto_salud (estid, gsid, ecstienediscapacidad, ecsdetallediscapacidad, amid)
             VALUES (:student_id, :blood_group, :has_disability, :disability_detail, :medical_care)
             ON CONFLICT (estid) DO UPDATE
             SET gsid = EXCLUDED.gsid,
                 ecstienediscapacidad = EXCLUDED.ecstienediscapacidad,
                 ecsdetallediscapacidad = EXCLUDED.ecsdetallediscapacidad,
                 amid = EXCLUDED.amid,
                 ecsfecha_modificacion = CURRENT_TIMESTAMP"
        );
        $statement->execute([
            'student_id' => $studentId,
            'blood_group' => (int) ($data['gsid'] ?? 0) > 0 ? (int) $data['gsid'] : null,
            'has_disability' => $this->dbBool(!empty($data['ecstienediscapacidad'])),
            'disability_detail' => trim((string) ($data['ecsdetallediscapacidad'] ?? '')) !== '' ? trim((string) $data['ecsdetallediscapacidad']) : null,
            'medical_care' => (int) ($data['amid'] ?? 0) > 0 ? (int) $data['amid'] : null,
        ]);

        $this->replaceHealthConditionsModule($studentId, $data['health_conditions'] ?? []);
        $this->insertHealthMeasurementModule($studentId, $data['health_measurement'] ?? []);
    }

    private function updateHealthPanelModule(int $studentId, int $matriculationId, array $data): void
    {
        $panel = (string) ($data['health_panel'] ?? '');

        if ($panel === 'general') {
            $this->upsertHealthGeneralModule($studentId, $data);
            $this->upsertInsuranceModule($matriculationId, $data['insurance'] ?? []);
            return;
        }

        if ($panel === 'condiciones') {
            $this->replaceHealthConditionsModule($studentId, $data['health_conditions'] ?? []);
            return;
        }

        if ($panel === 'historia-vital') {
            $this->upsertVitalHistoryModule($studentId, $data['vital_history'] ?? []);
            return;
        }

        if ($panel === 'mediciones') {
            $this->insertHealthMeasurementModule($studentId, $data['health_measurement'] ?? []);
            return;
        }

        $this->upsertHealthModule($studentId, $data);
    }

    private function upsertHealthGeneralModule(int $studentId, array $data): void
    {
        $statement = $this->db->prepare(
            "INSERT INTO estudiante_contexto_salud (estid, gsid, ecstienediscapacidad, ecsdetallediscapacidad, amid)
             VALUES (:student_id, :blood_group, :has_disability, :disability_detail, :medical_care)
             ON CONFLICT (estid) DO UPDATE
             SET gsid = EXCLUDED.gsid,
                 ecstienediscapacidad = EXCLUDED.ecstienediscapacidad,
                 ecsdetallediscapacidad = EXCLUDED.ecsdetallediscapacidad,
                 amid = EXCLUDED.amid,
                 ecsfecha_modificacion = CURRENT_TIMESTAMP"
        );
        $statement->execute([
            'student_id' => $studentId,
            'blood_group' => (int) ($data['gsid'] ?? 0) > 0 ? (int) $data['gsid'] : null,
            'has_disability' => $this->dbBool(!empty($data['ecstienediscapacidad'])),
            'disability_detail' => trim((string) ($data['ecsdetallediscapacidad'] ?? '')) !== '' ? trim((string) $data['ecsdetallediscapacidad']) : null,
            'medical_care' => (int) ($data['amid'] ?? 0) > 0 ? (int) $data['amid'] : null,
        ]);
    }

    private function upsertInsuranceModule(int $matriculationId, array $insurance): void
    {
        if ($matriculationId <= 0) {
            return;
        }

        $insuranceId = (int) ($insurance['smid'] ?? 0);

        if ($insuranceId <= 0) {
            $deleteStatement = $this->db->prepare(
                "DELETE FROM matricula_seguro_medico
                 WHERE matid = :matriculation_id"
            );
            $deleteStatement->execute(['matriculation_id' => $matriculationId]);
            return;
        }

        $statement = $this->db->prepare(
            "INSERT INTO matricula_seguro_medico (matid, smid, msmtelefono, msmobservacion)
             VALUES (:matid, :insurance_id, :phone, :observation)
             ON CONFLICT (matid) DO UPDATE
             SET smid = EXCLUDED.smid,
                 msmtelefono = EXCLUDED.msmtelefono,
                 msmobservacion = EXCLUDED.msmobservacion,
                 msmfecha_modificacion = CURRENT_TIMESTAMP"
        );
        $statement->execute([
            'matid' => $matriculationId,
            'insurance_id' => $insuranceId,
            'phone' => trim((string) ($insurance['msmtelefono'] ?? '')) !== '' ? trim((string) $insurance['msmtelefono']) : null,
            'observation' => trim((string) ($insurance['msmobservacion'] ?? '')) !== '' ? trim((string) $insurance['msmobservacion']) : null,
        ]);
    }

    private function replaceHealthConditionsModule(int $studentId, array $conditions): void
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
                throw new \RuntimeException('Cada condicion de salud debe tener tipo y descripcion.');
            }

            $insertStatement->execute([
                'student_id' => $studentId,
                'type_id' => $typeId,
                'description' => $description,
                'medications' => trim((string) ($condition['ecsamedicamentos'] ?? '')) !== '' ? trim((string) $condition['ecsamedicamentos']) : null,
                'observation' => trim((string) ($condition['ecsaobservacion'] ?? '')) !== '' ? trim((string) $condition['ecsaobservacion']) : null,
                'is_active' => $this->dbBool(!array_key_exists('ecsavigente', $condition) || !empty($condition['ecsavigente'])),
            ]);
        }
    }

    private function insertHealthMeasurementModule(int $studentId, array $measurement): void
    {
        $weight = trim((string) ($measurement['emspeso'] ?? ''));
        $height = trim((string) ($measurement['emstalla'] ?? ''));
        $date = trim((string) ($measurement['emsfecha_medicion'] ?? ''));

        if ($weight === '' || $height === '' || $date === '') {
            return;
        }

        $weightValue = (float) $weight;
        $heightValue = (float) $height;
        $heightMeters = $heightValue / 100;
        $imc = trim((string) ($measurement['emsimc'] ?? ''));

        if ($imc === '' && $heightValue > 0) {
            $imc = number_format($weightValue / ($heightMeters * $heightMeters), 2, '.', '');
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
            'weight' => $weightValue,
            'height' => $heightMeters,
            'imc' => (float) $imc,
            'measurement_date' => $date,
            'observation' => trim((string) ($measurement['emsobservacion'] ?? '')) !== '' ? trim((string) $measurement['emsobservacion']) : null,
        ]);
    }

    private function upsertVitalHistoryModule(int $studentId, array $history): void
    {
        $statement = $this->db->prepare(
            "INSERT INTO estudiante_historia_vital (
                estid, ehvedadmadre, ehvcomplicacionesembarazo, ehvmedicacionembarazo, teid, tpid,
                ehvdetalleembarazo, ehvpesonacer, ehvtallanacer, ehvedadcaminar, ehvedadhablar,
                ehvperiodolactancia, ehvedadbiberon, ehvedadcontrolesfinteres
             ) VALUES (
                :student_id, :mother_age, :pregnancy_complications, :pregnancy_medication, :pregnancy_type, :birth_type,
                :pregnancy_detail, :birth_weight, :birth_height, :walk_age, :speak_age,
                :lactation_period, :bottle_age, :sphincter_age
             )
             ON CONFLICT (estid) DO UPDATE
             SET ehvedadmadre = EXCLUDED.ehvedadmadre,
                 ehvcomplicacionesembarazo = EXCLUDED.ehvcomplicacionesembarazo,
                 ehvmedicacionembarazo = EXCLUDED.ehvmedicacionembarazo,
                 teid = EXCLUDED.teid,
                 tpid = EXCLUDED.tpid,
                 ehvdetalleembarazo = EXCLUDED.ehvdetalleembarazo,
                 ehvpesonacer = EXCLUDED.ehvpesonacer,
                 ehvtallanacer = EXCLUDED.ehvtallanacer,
                 ehvedadcaminar = EXCLUDED.ehvedadcaminar,
                 ehvedadhablar = EXCLUDED.ehvedadhablar,
                 ehvperiodolactancia = EXCLUDED.ehvperiodolactancia,
                 ehvedadbiberon = EXCLUDED.ehvedadbiberon,
                 ehvedadcontrolesfinteres = EXCLUDED.ehvedadcontrolesfinteres,
                 ehvfecha_modificacion = CURRENT_TIMESTAMP"
        );
        $statement->execute([
            'student_id' => $studentId,
            'mother_age' => trim((string) ($history['ehvedadmadre'] ?? '')) !== '' ? (int) $history['ehvedadmadre'] : null,
            'pregnancy_complications' => trim((string) ($history['ehvcomplicacionesembarazo'] ?? '')) !== '' ? trim((string) $history['ehvcomplicacionesembarazo']) : null,
            'pregnancy_medication' => trim((string) ($history['ehvmedicacionembarazo'] ?? '')) !== '' ? trim((string) $history['ehvmedicacionembarazo']) : null,
            'pregnancy_type' => (int) ($history['teid'] ?? 0) > 0 ? (int) $history['teid'] : null,
            'birth_type' => (int) ($history['tpid'] ?? 0) > 0 ? (int) $history['tpid'] : null,
            'pregnancy_detail' => trim((string) ($history['ehvdetalleembarazo'] ?? '')) !== '' ? trim((string) $history['ehvdetalleembarazo']) : null,
            'birth_weight' => trim((string) ($history['ehvpesonacer'] ?? '')) !== '' ? trim((string) $history['ehvpesonacer']) : null,
            'birth_height' => trim((string) ($history['ehvtallanacer'] ?? '')) !== '' ? trim((string) $history['ehvtallanacer']) : null,
            'walk_age' => trim((string) ($history['ehvedadcaminar'] ?? '')) !== '' ? trim((string) $history['ehvedadcaminar']) : null,
            'speak_age' => trim((string) ($history['ehvedadhablar'] ?? '')) !== '' ? trim((string) $history['ehvedadhablar']) : null,
            'lactation_period' => trim((string) ($history['ehvperiodolactancia'] ?? '')) !== '' ? trim((string) $history['ehvperiodolactancia']) : null,
            'bottle_age' => trim((string) ($history['ehvedadbiberon'] ?? '')) !== '' ? trim((string) $history['ehvedadbiberon']) : null,
            'sphincter_age' => trim((string) ($history['ehvedadcontrolesfinteres'] ?? '')) !== '' ? trim((string) $history['ehvedadcontrolesfinteres']) : null,
        ]);
    }

    private function upsertAcademicModule(int $studentId, array $data): void
    {
        $repeatedYears = !empty($data['ecaharepetidoanios']);
        $statement = $this->db->prepare(
            "INSERT INTO estudiante_contexto_academico (
                estid, ecafechaingresoinstitucion, ecaharepetidoanios, ecadetallerepeticion,
                ecaasignaturaspreferencia, ecaasignaturasdificultad, ecaactividadesextras
             ) VALUES (
                :student_id, :entry_date, :repeated_years, :repetition_detail,
                :preferred_subjects, :difficult_subjects, :extra_activities
             )
             ON CONFLICT (estid) DO UPDATE
             SET ecafechaingresoinstitucion = EXCLUDED.ecafechaingresoinstitucion,
                 ecaharepetidoanios = EXCLUDED.ecaharepetidoanios,
                 ecadetallerepeticion = EXCLUDED.ecadetallerepeticion,
                 ecaasignaturaspreferencia = EXCLUDED.ecaasignaturaspreferencia,
                 ecaasignaturasdificultad = EXCLUDED.ecaasignaturasdificultad,
                 ecaactividadesextras = EXCLUDED.ecaactividadesextras,
                 ecafecha_modificacion = CURRENT_TIMESTAMP"
        );
        $statement->execute([
            'student_id' => $studentId,
            'entry_date' => trim((string) ($data['ecafechaingresoinstitucion'] ?? '')) !== '' ? trim((string) $data['ecafechaingresoinstitucion']) : null,
            'repeated_years' => $this->dbBool($repeatedYears),
            'repetition_detail' => $repeatedYears && trim((string) ($data['ecadetallerepeticion'] ?? '')) !== '' ? trim((string) $data['ecadetallerepeticion']) : null,
            'preferred_subjects' => trim((string) ($data['ecaasignaturaspreferencia'] ?? '')) !== '' ? trim((string) $data['ecaasignaturaspreferencia']) : null,
            'difficult_subjects' => trim((string) ($data['ecaasignaturasdificultad'] ?? '')) !== '' ? trim((string) $data['ecaasignaturasdificultad']) : null,
            'extra_activities' => trim((string) ($data['ecaactividadesextras'] ?? '')) !== '' ? trim((string) $data['ecaactividadesextras']) : null,
        ]);
    }

    private function upsertResourcesModule(int $matriculationId, array $data): void
    {
        $statement = $this->db->prepare(
            "INSERT INTO matricula_recurso_tecnologico (
                matid, mrtinternet, mrtcomputador, mrtlaptop, mrttablet, mrtcelular, mrtimpresora
             ) VALUES (
                :matid, :internet, :computer, :laptop, :tablet, :phone, :printer
             )
             ON CONFLICT (matid) DO UPDATE
             SET mrtinternet = EXCLUDED.mrtinternet,
                 mrtcomputador = EXCLUDED.mrtcomputador,
                 mrtlaptop = EXCLUDED.mrtlaptop,
                 mrttablet = EXCLUDED.mrttablet,
                 mrtcelular = EXCLUDED.mrtcelular,
                 mrtimpresora = EXCLUDED.mrtimpresora,
                 mrtfecha_modificacion = CURRENT_TIMESTAMP"
        );
        $statement->execute([
            'matid' => $matriculationId,
            'internet' => $this->dbBool(!empty($data['mrtinternet'])),
            'computer' => $this->dbBool(!empty($data['mrtcomputador'])),
            'laptop' => $this->dbBool(!empty($data['mrtlaptop'])),
            'tablet' => $this->dbBool(!empty($data['mrttablet'])),
            'phone' => $this->dbBool(!empty($data['mrtcelular'])),
            'printer' => $this->dbBool(!empty($data['mrtimpresora'])),
        ]);
    }

    private function upsertBillingModule(int $matriculationId, array $data): void
    {
        $statement = $this->db->prepare(
            "INSERT INTO matricula_facturacion (
                matid, mfcnombre, mfctipoidentificacion, mfcidentificacion, mfcdireccion, mfccorreo, mfctelefono
             ) VALUES (
                :matid, :name, :id_type, :id_number, :address, :email, :phone
             )
             ON CONFLICT (matid) DO UPDATE
             SET mfcnombre = EXCLUDED.mfcnombre,
                 mfctipoidentificacion = EXCLUDED.mfctipoidentificacion,
                 mfcidentificacion = EXCLUDED.mfcidentificacion,
                 mfcdireccion = EXCLUDED.mfcdireccion,
                 mfccorreo = EXCLUDED.mfccorreo,
                 mfctelefono = EXCLUDED.mfctelefono,
                 mfcfecha_modificacion = CURRENT_TIMESTAMP"
        );
        $statement->execute([
            'matid' => $matriculationId,
            'name' => trim((string) ($data['mfcnombre'] ?? '')),
            'id_type' => mb_strtoupper(trim((string) ($data['mfctipoidentificacion'] ?? 'CEDULA'))),
            'id_number' => preg_replace('/\D+/', '', (string) ($data['mfcidentificacion'] ?? '')) ?? '',
            'address' => trim((string) ($data['mfcdireccion'] ?? '')) !== '' ? trim((string) $data['mfcdireccion']) : null,
            'email' => trim((string) ($data['mfccorreo'] ?? '')) !== '' ? trim((string) $data['mfccorreo']) : null,
            'phone' => trim((string) ($data['mfctelefono'] ?? '')) !== '' ? trim((string) $data['mfctelefono']) : null,
        ]);
    }

    private function replaceDocumentsModule(int $matriculationId, array $documents, array $acceptedDocumentIds): void
    {
        $accepted = array_flip(array_map('intval', $acceptedDocumentIds));
        $statement = $this->db->prepare(
            "INSERT INTO matricula_aceptacion_documentos (matid, domid, madaceptado, madfecha_aceptacion)
             VALUES (:matid, :domid, :accepted, :accepted_at)
             ON CONFLICT (matid, domid) DO UPDATE
             SET madaceptado = EXCLUDED.madaceptado,
                 madfecha_aceptacion = EXCLUDED.madfecha_aceptacion"
        );

        foreach ($documents as $document) {
            $documentId = (int) ($document['domid'] ?? 0);

            if ($documentId <= 0) {
                continue;
            }

            $isAccepted = isset($accepted[$documentId]);
            $statement->execute([
                'matid' => $matriculationId,
                'domid' => $documentId,
                'accepted' => $this->dbBool($isAccepted),
                'accepted_at' => $isAccepted ? date('Y-m-d H:i:s') : null,
            ]);
        }
    }

    private function dbBool(bool $value): string
    {
        return $value ? 'true' : 'false';
    }

    public function cedulaExistsForOtherPerson(string $cedula, int $personId): bool
    {
        $statement = $this->db->prepare(
            "SELECT 1
             FROM persona
             WHERE percedula = :cedula
               AND perid <> :person_id
             LIMIT 1"
        );
        $statement->execute([
            'cedula' => $cedula,
            'person_id' => $personId,
        ]);

        return $statement->fetchColumn() !== false;
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
            "SELECT COUNT(*) FROM {$this->table}
             WHERE estestado = true"
        );

        return (int) $statement->fetchColumn();
    }
}
