<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;
use RuntimeException;

class NoveltyModel extends Model
{
    protected string $table = 'novedad_estudiante';
    protected string $primaryKey = 'noeid';

    public function activeTypes(): array
    {
        $statement = $this->db->query(
            "SELECT tnoid, tnonombre, tnodescripcion, tnogravedad
             FROM tipo_novedad
             WHERE tnoestado = true
             ORDER BY
                CASE tnogravedad
                    WHEN 'LEVE' THEN 1
                    WHEN 'MEDIA' THEN 2
                    WHEN 'GRAVE' THEN 3
                    ELSE 4
                END,
                tnonombre ASC"
        );

        return $statement->fetchAll();
    }

    public function activeMatriculationsForPeriod(int $periodId, ?int $teacherPersonId = null): array
    {
        $teacherFilter = $teacherPersonId !== null
            ? "AND EXISTS (
                    SELECT 1
                    FROM materia_curso_docente mcd
                    INNER JOIN materia_curso mc ON mc.mtcid = mcd.mtcid
                    WHERE mc.curid = c.curid
                      AND mcd.perid = :teacher_person_id
                      AND mcd.mcdestado = true
                )"
            : '';

        $statement = $this->db->prepare(
            "SELECT DISTINCT ON (m.matid)
                    m.matid,
                    e.estid,
                    p.percedula,
                    p.perapellidos,
                    p.pernombres,
                    c.curid,
                    CONCAT(g.granombre, ' ', pr.prlnombre) AS curso
             FROM matricula m
             INNER JOIN curso c ON c.curid = m.curid
             INNER JOIN estado_matricula em ON em.emdid = m.emdid
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN paralelo pr ON pr.prlid = c.prlid
             INNER JOIN estudiante e ON e.estid = m.estid
             INNER JOIN persona p ON p.perid = e.perid
             WHERE c.pleid = :period_id
               AND e.estestado = true
               AND LOWER(em.emdnombre) IN ('activo', 'activa')
               AND m.matfecha_retiro IS NULL
               {$teacherFilter}
             ORDER BY m.matid, p.perapellidos ASC, p.pernombres ASC"
        );
        $statement->bindValue(':period_id', $periodId, PDO::PARAM_INT);

        if ($teacherPersonId !== null) {
            $statement->bindValue(':teacher_person_id', $teacherPersonId, PDO::PARAM_INT);
        }

        $statement->execute();

        return $statement->fetchAll();
    }

    public function sessionsForDate(int $periodId, string $date, ?int $teacherPersonId = null): array
    {
        $teacherFilter = $teacherPersonId !== null ? 'AND mcd.perid = :teacher_person_id' : '';
        $statement = $this->db->prepare(
            "SELECT
                    sc.sclid,
                    sc.sclnumero_hora,
                    ca.cafecha,
                    v.curid,
                    v.mtcnombre_mostrar,
                    v.granombre,
                    v.prlnombre,
                    docente.perapellidos AS docente_apellidos,
                    docente.pernombres AS docente_nombres
             FROM sesion_clase sc
             INNER JOIN calendario_asistencia ca ON ca.caid = sc.caid
             INNER JOIN vw_materia_curso v ON v.mtcid = sc.mtcid
             INNER JOIN materia_curso_docente mcd ON mcd.mcdid = sc.mcdid
             INNER JOIN persona docente ON docente.perid = mcd.perid
             WHERE ca.pleid = :period_id
               AND ca.cafecha = :class_date
               AND sc.sclestado <> 'ANULADA'
               {$teacherFilter}
             ORDER BY sc.sclnumero_hora ASC, v.granombre ASC, v.prlnombre ASC, v.mtcnombre_mostrar ASC"
        );
        $statement->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $statement->bindValue(':class_date', $date, PDO::PARAM_STR);

        if ($teacherPersonId !== null) {
            $statement->bindValue(':teacher_person_id', $teacherPersonId, PDO::PARAM_INT);
        }

        $statement->execute();

        return $statement->fetchAll();
    }

    public function create(array $data, ?int $teacherPersonId = null): int
    {
        $context = (string) ($data['noetipo_contexto'] ?? '');
        $sessionId = (int) ($data['sclid'] ?? 0);
        $matriculationId = (int) ($data['matid'] ?? 0);
        $date = (string) ($data['noefecha'] ?? '');
        $description = trim((string) ($data['noedescripcion'] ?? ''));
        $typeId = (int) ($data['tnoid'] ?? 0);
        $hour = trim((string) ($data['noehora'] ?? ''));
        $location = trim((string) ($data['noeubicacion'] ?? ''));
        $userId = (int) ($data['usuid_registro'] ?? 0);

        if ($matriculationId <= 0 || $date === '' || $description === '' || $userId <= 0) {
            throw new RuntimeException('Estudiante, fecha y descripcion son obligatorios.');
        }

        if ($context === 'CLASE') {
            if ($sessionId <= 0) {
                throw new RuntimeException('Debe seleccionar una sesion de clase.');
            }

            if (!$this->matriculationBelongsToSession($matriculationId, $sessionId, $teacherPersonId)) {
                throw new RuntimeException('El estudiante seleccionado no pertenece a la sesion de clase.');
            }

            $sessionDate = $this->sessionDate($sessionId);
            if ($sessionDate !== null) {
                $date = $sessionDate;
            }
        } else {
            $sessionId = 0;

            if ($teacherPersonId !== null && !$this->teacherCanAccessMatriculation($matriculationId, $teacherPersonId)) {
                throw new RuntimeException('El estudiante seleccionado no pertenece a sus cursos asignados.');
            }
        }

        $statement = $this->db->prepare(
            "INSERT INTO novedad_estudiante (
                matid, sclid, tnoid, noetipo_contexto, noefecha, noehora, noeubicacion,
                noedescripcion, usuid_registro
             ) VALUES (
                :matriculation_id, :session_id, :type_id, :context, :novelty_date, :novelty_time,
                :location, :description, :user_id
             )
             RETURNING noeid"
        );
        $statement->bindValue(':matriculation_id', $matriculationId, PDO::PARAM_INT);
        $sessionId > 0
            ? $statement->bindValue(':session_id', $sessionId, PDO::PARAM_INT)
            : $statement->bindValue(':session_id', null, PDO::PARAM_NULL);
        $typeId > 0
            ? $statement->bindValue(':type_id', $typeId, PDO::PARAM_INT)
            : $statement->bindValue(':type_id', null, PDO::PARAM_NULL);
        $statement->bindValue(':context', $context, PDO::PARAM_STR);
        $statement->bindValue(':novelty_date', $date, PDO::PARAM_STR);
        $hour !== ''
            ? $statement->bindValue(':novelty_time', $hour, PDO::PARAM_STR)
            : $statement->bindValue(':novelty_time', null, PDO::PARAM_NULL);
        $location !== ''
            ? $statement->bindValue(':location', $location, PDO::PARAM_STR)
            : $statement->bindValue(':location', null, PDO::PARAM_NULL);
        $statement->bindValue(':description', $description, PDO::PARAM_STR);
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    public function byPeriod(int $periodId, string $date = '', int $matriculationId = 0, ?int $teacherPersonId = null): array
    {
        $conditions = ['c.pleid = :period_id'];
        $params = ['period_id' => $periodId];

        if ($date !== '') {
            $conditions[] = 'ne.noefecha = :novelty_date';
            $params['novelty_date'] = $date;
        }

        if ($matriculationId > 0) {
            $conditions[] = 'ne.matid = :matriculation_id';
            $params['matriculation_id'] = $matriculationId;
        }

        if ($teacherPersonId !== null) {
            $conditions[] = "(
                sc.sclid IS NOT NULL AND mcd.perid = :teacher_person_id
                OR (
                    sc.sclid IS NULL AND EXISTS (
                        SELECT 1
                        FROM materia_curso_docente teacher_mcd
                        INNER JOIN materia_curso teacher_mc ON teacher_mc.mtcid = teacher_mcd.mtcid
                        WHERE teacher_mc.curid = c.curid
                          AND teacher_mcd.perid = :teacher_person_id_exists
                          AND teacher_mcd.mcdestado = true
                    )
                )
            )";
            $params['teacher_person_id'] = $teacherPersonId;
            $params['teacher_person_id_exists'] = $teacherPersonId;
        }

        return $this->noveltyRows('WHERE ' . implode(' AND ', $conditions), $params);
    }

    public function byStudent(int $studentId, int $periodId): array
    {
        return $this->noveltyRows(
            'WHERE m.estid = :student_id AND c.pleid = :period_id',
            ['student_id' => $studentId, 'period_id' => $periodId]
        );
    }

    public function byRepresentative(int $representativePersonId, int $periodId, int $studentId = 0): array
    {
        $studentFilter = $studentId > 0 ? 'AND m.estid = :student_id' : '';
        $params = [
            'representative_person_id' => $representativePersonId,
            'period_id' => $periodId,
        ];

        if ($studentId > 0) {
            $params['student_id'] = $studentId;
        }

        return $this->noveltyRows(
            "WHERE mr.perid = :representative_person_id
               AND c.pleid = :period_id
               {$studentFilter}",
            $params,
            true
        );
    }

    public function annul(int $noveltyId, int $userId, string $reason): void
    {
        if ($noveltyId <= 0 || $userId <= 0 || trim($reason) === '') {
            throw new RuntimeException('Debe seleccionar la novedad e ingresar motivo de anulacion.');
        }

        $statement = $this->db->prepare(
            "UPDATE novedad_estudiante
             SET noeestado = 'ANULADA',
                 usuid_anulacion = :user_id,
                 noefecha_anulacion = CURRENT_TIMESTAMP,
                 noemotivo_anulacion = :reason
             WHERE noeid = :novelty_id
               AND noeestado <> 'ANULADA'"
        );
        $statement->execute([
            'user_id' => $userId,
            'reason' => trim($reason),
            'novelty_id' => $noveltyId,
        ]);

        if ($statement->rowCount() === 0) {
            throw new RuntimeException('La novedad no se pudo anular.');
        }
    }

    private function noveltyRows(string $whereSql, array $params, bool $joinRepresentative = false): array
    {
        $representativeJoin = $joinRepresentative
            ? 'INNER JOIN matricula_representante mr ON mr.matid = m.matid'
            : '';
        $statement = $this->db->prepare(
            "SELECT
                    ne.noeid,
                    ne.matid,
                    ne.sclid,
                    ne.tnoid,
                    ne.noetipo_contexto,
                    ne.noefecha,
                    ne.noehora,
                    ne.noeubicacion,
                    ne.noedescripcion,
                    ne.noeestado,
                    ne.noefecha_registro,
                    ne.noefecha_anulacion,
                    ne.noemotivo_anulacion,
                    tn.tnonombre,
                    tn.tnogravedad,
                    p.percedula,
                    p.perapellidos,
                    p.pernombres,
                    CONCAT(g.granombre, ' ', pr.prlnombre) AS curso,
                    sc.sclnumero_hora,
                    v.mtcnombre_mostrar,
                    docente.perapellidos AS docente_apellidos,
                    docente.pernombres AS docente_nombres,
                    registrador.usunombre AS usuario_registro,
                    anulador.usunombre AS usuario_anulacion
             FROM novedad_estudiante ne
             INNER JOIN matricula m ON m.matid = ne.matid
             INNER JOIN curso c ON c.curid = m.curid
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN paralelo pr ON pr.prlid = c.prlid
             INNER JOIN estudiante e ON e.estid = m.estid
             INNER JOIN persona p ON p.perid = e.perid
             INNER JOIN usuario registrador ON registrador.usuid = ne.usuid_registro
             LEFT JOIN usuario anulador ON anulador.usuid = ne.usuid_anulacion
             LEFT JOIN tipo_novedad tn ON tn.tnoid = ne.tnoid
             LEFT JOIN sesion_clase sc ON sc.sclid = ne.sclid
             LEFT JOIN materia_curso_docente mcd ON mcd.mcdid = sc.mcdid
             LEFT JOIN persona docente ON docente.perid = mcd.perid
             LEFT JOIN vw_materia_curso v ON v.mtcid = sc.mtcid
             {$representativeJoin}
             {$whereSql}
             ORDER BY ne.noefecha DESC, ne.noefecha_registro DESC, p.perapellidos ASC, p.pernombres ASC
             LIMIT 200"
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }

    private function matriculationBelongsToSession(int $matriculationId, int $sessionId, ?int $teacherPersonId = null): bool
    {
        $teacherFilter = $teacherPersonId !== null ? 'AND mcd.perid = :teacher_person_id' : '';
        $statement = $this->db->prepare(
            "SELECT 1
             FROM sesion_clase sc
             INNER JOIN calendario_asistencia ca ON ca.caid = sc.caid
             INNER JOIN materia_curso mc ON mc.mtcid = sc.mtcid
             INNER JOIN materia_curso_docente mcd ON mcd.mcdid = sc.mcdid
             INNER JOIN matricula m ON m.curid = mc.curid
             INNER JOIN estado_matricula em ON em.emdid = m.emdid
             WHERE sc.sclid = :session_id
               AND m.matid = :matriculation_id
               AND sc.sclestado <> 'ANULADA'
               AND m.matfecha <= ca.cafecha
               AND (m.matfecha_retiro IS NULL OR m.matfecha_retiro >= ca.cafecha)
               AND LOWER(em.emdnombre) IN ('activo', 'activa')
               {$teacherFilter}
             LIMIT 1"
        );
        $statement->bindValue(':session_id', $sessionId, PDO::PARAM_INT);
        $statement->bindValue(':matriculation_id', $matriculationId, PDO::PARAM_INT);

        if ($teacherPersonId !== null) {
            $statement->bindValue(':teacher_person_id', $teacherPersonId, PDO::PARAM_INT);
        }

        $statement->execute();

        return $statement->fetchColumn() !== false;
    }

    private function sessionDate(int $sessionId): ?string
    {
        $statement = $this->db->prepare(
            "SELECT ca.cafecha
             FROM sesion_clase sc
             INNER JOIN calendario_asistencia ca ON ca.caid = sc.caid
             WHERE sc.sclid = :session_id
             LIMIT 1"
        );
        $statement->execute(['session_id' => $sessionId]);
        $date = $statement->fetchColumn();

        return $date !== false ? (string) $date : null;
    }

    private function teacherCanAccessMatriculation(int $matriculationId, int $teacherPersonId): bool
    {
        $statement = $this->db->prepare(
            "SELECT 1
             FROM matricula m
             INNER JOIN materia_curso mc ON mc.curid = m.curid
             INNER JOIN materia_curso_docente mcd ON mcd.mtcid = mc.mtcid
             WHERE m.matid = :matriculation_id
               AND mcd.perid = :teacher_person_id
               AND mcd.mcdestado = true
             LIMIT 1"
        );
        $statement->execute([
            'matriculation_id' => $matriculationId,
            'teacher_person_id' => $teacherPersonId,
        ]);

        return $statement->fetchColumn() !== false;
    }
}
