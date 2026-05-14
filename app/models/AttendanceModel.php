<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;
use RuntimeException;

class AttendanceModel extends Model
{
    public function attendanceConfigurationByPeriod(int $periodId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT coaid, pleid, coafecha_inicio_clases, coafecha_fin_clases,
                    coaobservacion, usuid_registro
             FROM configuracion_asistencia
             WHERE pleid = :period_id
             LIMIT 1"
        );
        $statement->execute(['period_id' => $periodId]);

        return $statement->fetch();
    }

    public function saveAttendanceConfiguration(
        int $periodId,
        string $classStartDate,
        string $classEndDate,
        string $note,
        int $userId
    ): void {
        if ($periodId <= 0) {
            throw new RuntimeException('Debe seleccionar un periodo lectivo valido.');
        }

        if (!$this->isIsoDate($classStartDate) || !$this->isIsoDate($classEndDate)) {
            throw new RuntimeException('Las fechas de inicio y fin de clases no son validas.');
        }

        if ($classEndDate < $classStartDate) {
            throw new RuntimeException('La fecha de fin de clases no puede ser anterior al inicio.');
        }

        $periodStatement = $this->db->prepare(
            "SELECT plefechainicio, plefechafin
             FROM periodo_lectivo
             WHERE pleid = :period_id
             LIMIT 1"
        );
        $periodStatement->execute(['period_id' => $periodId]);
        $period = $periodStatement->fetch();

        if ($period === false) {
            throw new RuntimeException('El periodo lectivo seleccionado no existe.');
        }

        if ($classStartDate < (string) $period['plefechainicio'] || $classEndDate > (string) $period['plefechafin']) {
            throw new RuntimeException('El rango de clases debe estar dentro de las fechas del periodo lectivo.');
        }

        $statement = $this->db->prepare(
            "INSERT INTO configuracion_asistencia (
                pleid, coafecha_inicio_clases, coafecha_fin_clases, coaobservacion, usuid_registro
             ) VALUES (
                :period_id, :class_start_date, :class_end_date, :note, :user_id
             )
             ON CONFLICT (pleid) DO UPDATE
             SET coafecha_inicio_clases = EXCLUDED.coafecha_inicio_clases,
                 coafecha_fin_clases = EXCLUDED.coafecha_fin_clases,
                 coaobservacion = EXCLUDED.coaobservacion,
                 usuid_registro = EXCLUDED.usuid_registro,
                 coafecha_modificacion = CURRENT_TIMESTAMP"
        );
        $statement->execute([
            'period_id' => $periodId,
            'class_start_date' => $classStartDate,
            'class_end_date' => $classEndDate,
            'note' => $note !== '' ? $note : null,
            'user_id' => $userId,
        ]);
    }

    public function classDateRangeByPeriod(int $periodId): ?array
    {
        $configuration = $this->attendanceConfigurationByPeriod($periodId);

        if ($configuration !== false) {
            return [
                'start' => (string) $configuration['coafecha_inicio_clases'],
                'end' => (string) $configuration['coafecha_fin_clases'],
                'configured' => true,
            ];
        }

        $statement = $this->db->prepare(
            "SELECT plefechainicio, plefechafin
             FROM periodo_lectivo
             WHERE pleid = :period_id
             LIMIT 1"
        );
        $statement->execute(['period_id' => $periodId]);
        $period = $statement->fetch();

        if ($period === false) {
            return null;
        }

        return [
            'start' => (string) $period['plefechainicio'],
            'end' => (string) $period['plefechafin'],
            'configured' => false,
        ];
    }

    public function dateIsInsideClassRange(int $periodId, string $date): bool
    {
        $range = $this->classDateRangeByPeriod($periodId);

        if ($range === null) {
            return false;
        }

        return $date >= $range['start'] && $date <= $range['end'];
    }

    public function areas(): array
    {
        $statement = $this->db->query(
            "SELECT areaid, areanombre, areaestado
             FROM area_academica
             ORDER BY areanombre ASC"
        );

        return $statement->fetchAll();
    }

    public function activeAreas(): array
    {
        $statement = $this->db->query(
            "SELECT areaid, areanombre
             FROM area_academica
             WHERE areaestado = true
             ORDER BY areanombre ASC"
        );

        return $statement->fetchAll();
    }

    public function subjects(): array
    {
        $statement = $this->db->query(
            "SELECT a.asgid, a.areaid, a.asgnombre, a.asgestado, aa.areanombre
             FROM asignatura a
             INNER JOIN area_academica aa ON aa.areaid = a.areaid
             ORDER BY aa.areanombre ASC, a.asgnombre ASC"
        );

        return $statement->fetchAll();
    }

    public function activeSubjects(): array
    {
        $statement = $this->db->query(
            "SELECT a.asgid, a.areaid, a.asgnombre, aa.areanombre
             FROM asignatura a
             INNER JOIN area_academica aa ON aa.areaid = a.areaid
             WHERE a.asgestado = true
               AND aa.areaestado = true
             ORDER BY aa.areanombre ASC, a.asgnombre ASC"
        );

        return $statement->fetchAll();
    }

    public function createArea(string $name): void
    {
        $statement = $this->db->prepare(
            "INSERT INTO area_academica (areanombre, areaestado)
             VALUES (:name, true)"
        );
        $statement->execute(['name' => $name]);
    }

    public function updateArea(int $areaId, string $name): void
    {
        $statement = $this->db->prepare(
            "UPDATE area_academica
             SET areanombre = :name,
                 areafecha_modificacion = CURRENT_TIMESTAMP
             WHERE areaid = :id"
        );
        $statement->execute([
            'id' => $areaId,
            'name' => $name,
        ]);
    }

    public function updateAreaStatus(int $areaId, bool $status): void
    {
        $statement = $this->db->prepare(
            "UPDATE area_academica
             SET areaestado = :status,
                 areafecha_modificacion = CURRENT_TIMESTAMP
             WHERE areaid = :id"
        );
        $statement->bindValue(':id', $areaId, PDO::PARAM_INT);
        $statement->bindValue(':status', $status, PDO::PARAM_BOOL);
        $statement->execute();
    }

    public function createSubject(int $areaId, string $name): void
    {
        $statement = $this->db->prepare(
            "INSERT INTO asignatura (areaid, asgnombre, asgestado)
             VALUES (:area_id, :name, true)"
        );
        $statement->execute([
            'area_id' => $areaId,
            'name' => $name,
        ]);
    }

    public function updateSubject(int $subjectId, int $areaId, string $name): void
    {
        $statement = $this->db->prepare(
            "UPDATE asignatura
             SET areaid = :area_id,
                 asgnombre = :name,
                 asgfecha_modificacion = CURRENT_TIMESTAMP
             WHERE asgid = :id"
        );
        $statement->execute([
            'id' => $subjectId,
            'area_id' => $areaId,
            'name' => $name,
        ]);
    }

    public function updateSubjectStatus(int $subjectId, bool $status): void
    {
        $statement = $this->db->prepare(
            "UPDATE asignatura
             SET asgestado = :status,
                 asgfecha_modificacion = CURRENT_TIMESTAMP
             WHERE asgid = :id"
        );
        $statement->bindValue(':id', $subjectId, PDO::PARAM_INT);
        $statement->bindValue(':status', $status, PDO::PARAM_BOOL);
        $statement->execute();
    }

    public function courseSubjectsByPeriod(int $periodId): array
    {
        $statement = $this->db->prepare(
            "SELECT mtcid, curid, asgid, pleid, graid, prlid, areaid, areanombre,
                    asgnombre, granombre, prlnombre, mtcnombre_mostrar,
                    mtcfecha_inicio, mtcfecha_fin, mtcorden, mtcestado
             FROM vw_materia_curso
             WHERE pleid = :period_id
             ORDER BY granombre ASC, prlnombre ASC, COALESCE(mtcorden, 999) ASC, asgnombre ASC"
        );
        $statement->execute(['period_id' => $periodId]);

        return $statement->fetchAll();
    }

    public function createCourseSubject(int $courseId, int $subjectId, string $startDate, ?int $order): void
    {
        $statement = $this->db->prepare(
            "INSERT INTO materia_curso (curid, asgid, mtcfecha_inicio, mtcorden, mtcestado)
             VALUES (:course_id, :subject_id, :start_date, :sort_order, true)"
        );
        $statement->bindValue(':course_id', $courseId, PDO::PARAM_INT);
        $statement->bindValue(':subject_id', $subjectId, PDO::PARAM_INT);
        $statement->bindValue(':start_date', $startDate, PDO::PARAM_STR);
        $statement->bindValue(':sort_order', $order, $order === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $statement->execute();
    }

    public function updateCourseSubjectStatus(int $courseSubjectId, bool $status): void
    {
        $endDateSql = $status ? 'NULL' : 'CURRENT_DATE';
        $statement = $this->db->prepare(
            "UPDATE materia_curso
             SET mtcestado = :status,
                 mtcfecha_fin = {$endDateSql},
                 mtcfecha_modificacion = CURRENT_TIMESTAMP
             WHERE mtcid = :id"
        );
        $statement->bindValue(':id', $courseSubjectId, PDO::PARAM_INT);
        $statement->bindValue(':status', $status, PDO::PARAM_BOOL);
        $statement->execute();
    }

    public function courseSubjectExists(int $courseId, int $subjectId): bool
    {
        $statement = $this->db->prepare(
            "SELECT 1
             FROM materia_curso
             WHERE curid = :course_id
               AND asgid = :subject_id
               AND mtcestado = true
             LIMIT 1"
        );
        $statement->execute([
            'course_id' => $courseId,
            'subject_id' => $subjectId,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function findCourseSubject(int $courseSubjectId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT mc.mtcid, mc.curid, mc.asgid, mc.mtcestado, c.pleid
             FROM materia_curso mc
             INNER JOIN curso c ON c.curid = mc.curid
             WHERE mc.mtcid = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $courseSubjectId]);

        return $statement->fetch();
    }

    public function activeTeachers(): array
    {
        $statement = $this->db->query(
            "SELECT DISTINCT ps.perid, p.percedula, p.pernombres, p.perapellidos
             FROM personal ps
             INNER JOIN persona p ON p.perid = ps.perid
             INNER JOIN asignacion_tipo_personal atp ON atp.psnid = ps.psnid
             INNER JOIN tipo_personal tp ON tp.tpid = atp.tpid
             WHERE ps.psnestado = true
               AND atp.atpestado = true
               AND tp.tpnombre = 'Docente'
             ORDER BY p.perapellidos ASC, p.pernombres ASC"
        );

        return $statement->fetchAll();
    }

    public function activeTeacherAssignmentsByCourseSubject(int $periodId): array
    {
        $statement = $this->db->prepare(
            "SELECT mcd.mcdid, mcd.mtcid, mcd.perid, mcd.mcdfecha_inicio,
                    p.percedula, p.pernombres, p.perapellidos
             FROM materia_curso_docente mcd
             INNER JOIN materia_curso mc ON mc.mtcid = mcd.mtcid
             INNER JOIN curso c ON c.curid = mc.curid
             INNER JOIN persona p ON p.perid = mcd.perid
             WHERE c.pleid = :period_id
               AND mcd.mcdestado = true
             ORDER BY p.perapellidos ASC, p.pernombres ASC"
        );
        $statement->execute(['period_id' => $periodId]);

        $map = [];

        foreach ($statement->fetchAll() as $row) {
            $map[(int) $row['mtcid']][] = $row;
        }

        return $map;
    }

    public function assignTeacher(int $courseSubjectId, int $personId, string $startDate): void
    {
        $statement = $this->db->prepare(
            "INSERT INTO materia_curso_docente (mtcid, perid, mcdfecha_inicio, mcdestado)
             VALUES (:course_subject_id, :person_id, :start_date, true)"
        );
        $statement->execute([
            'course_subject_id' => $courseSubjectId,
            'person_id' => $personId,
            'start_date' => $startDate,
        ]);
    }

    public function removeTeacher(int $assignmentId): void
    {
        $statement = $this->db->prepare(
            "UPDATE materia_curso_docente
             SET mcdestado = false,
                 mcdfecha_fin = CURRENT_DATE,
                 mcdfecha_modificacion = CURRENT_TIMESTAMP
             WHERE mcdid = :id"
        );
        $statement->execute(['id' => $assignmentId]);
    }

    public function findTeacherAssignment(int $assignmentId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT mcd.mcdid, mcd.mtcid, mcd.perid, c.pleid
             FROM materia_curso_docente mcd
             INNER JOIN materia_curso mc ON mc.mtcid = mcd.mtcid
             INNER JOIN curso c ON c.curid = mc.curid
             WHERE mcd.mcdid = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $assignmentId]);

        return $statement->fetch();
    }

    public function teacherCourseSubjects(int $personId, int $periodId, string $date): array
    {
        $statement = $this->db->prepare(
            "SELECT
                    mcd.mcdid,
                    mcd.mtcid,
                    v.curid,
                    v.pleid,
                    v.mtcnombre_mostrar,
                    v.areanombre,
                    v.asgnombre,
                    v.granombre,
                    v.prlnombre
             FROM materia_curso_docente mcd
             INNER JOIN vw_materia_curso v ON v.mtcid = mcd.mtcid
             WHERE mcd.perid = :person_id
               AND v.pleid = :period_id
               AND v.mtcestado = true
               AND mcd.mcdestado = true
               AND mcd.mcdfecha_inicio <= :class_date
               AND (mcd.mcdfecha_fin IS NULL OR mcd.mcdfecha_fin >= :class_date_end)
               AND v.mtcfecha_inicio <= :subject_date
               AND (v.mtcfecha_fin IS NULL OR v.mtcfecha_fin >= :subject_date_end)
             ORDER BY v.granombre ASC, v.prlnombre ASC, v.mtcnombre_mostrar ASC"
        );
        $statement->execute([
            'person_id' => $personId,
            'period_id' => $periodId,
            'class_date' => $date,
            'class_date_end' => $date,
            'subject_date' => $date,
            'subject_date_end' => $date,
        ]);

        return $statement->fetchAll();
    }

    public function teacherCalendarAvailabilityByRange(int $personId, int $periodId, string $startDate, string $endDate): array
    {
        $statement = $this->db->prepare(
            "SELECT
                    ca.caid,
                    ca.cafecha,
                    ca.catipo_jornada,
                    ca.cahora_limite,
                    mcd.mcdid,
                    mcd.mtcid,
                    v.curid,
                    v.mtcnombre_mostrar,
                    v.granombre,
                    v.prlnombre,
                    hours.hour AS sclnumero_hora
             FROM calendario_asistencia ca
             INNER JOIN materia_curso_docente mcd ON mcd.perid = :person_id
             INNER JOIN vw_materia_curso v ON v.mtcid = mcd.mtcid
             CROSS JOIN generate_series(1, 7) AS hours(hour)
             WHERE ca.pleid = :period_id
               AND ca.cahabilitado = true
               AND ca.cafecha BETWEEN :start_date AND :end_date
               AND v.pleid = ca.pleid
               AND v.mtcestado = true
               AND mcd.mcdestado = true
               AND mcd.mcdfecha_inicio <= ca.cafecha
               AND (mcd.mcdfecha_fin IS NULL OR mcd.mcdfecha_fin >= ca.cafecha)
               AND v.mtcfecha_inicio <= ca.cafecha
               AND (v.mtcfecha_fin IS NULL OR v.mtcfecha_fin >= ca.cafecha)
               AND hours.hour <= COALESCE(ca.cahora_limite, 7)
               AND (
                   ca.catipo_jornada <> 'ESPECIAL'
                   OR EXISTS (
                       SELECT 1
                       FROM calendario_asistencia_detalle cad
                       WHERE cad.caid = ca.caid
                         AND cad.curid = v.curid
                         AND cad.cadnumero_hora = hours.hour
                         AND cad.cadhabilitado = true
                   )
               )
             ORDER BY ca.cafecha ASC, v.granombre ASC, v.prlnombre ASC, v.mtcnombre_mostrar ASC, hours.hour ASC"
        );
        $statement->execute([
            'person_id' => $personId,
            'period_id' => $periodId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return $statement->fetchAll();
    }

    public function reportCourseSubjects(int $periodId): array
    {
        $statement = $this->db->prepare(
            "SELECT
                    mtcid,
                    curid,
                    mtcnombre_mostrar,
                    granombre,
                    prlnombre,
                    asgnombre
             FROM vw_materia_curso
             WHERE pleid = :period_id
               AND (
                   mtcestado = true
                   OR EXISTS (
                       SELECT 1
                       FROM sesion_clase sc
                       INNER JOIN calendario_asistencia ca ON ca.caid = sc.caid
                       WHERE sc.mtcid = vw_materia_curso.mtcid
                         AND ca.pleid = :period_id_sessions
                   )
               )
             ORDER BY granombre ASC, prlnombre ASC, mtcnombre_mostrar ASC"
        );
        $statement->execute([
            'period_id' => $periodId,
            'period_id_sessions' => $periodId,
        ]);

        return $statement->fetchAll();
    }

    public function reportTeachers(int $periodId): array
    {
        $statement = $this->db->prepare(
            "SELECT DISTINCT
                    mcd.perid,
                    p.perapellidos,
                    p.pernombres
             FROM materia_curso_docente mcd
             INNER JOIN vw_materia_curso v ON v.mtcid = mcd.mtcid
             INNER JOIN persona p ON p.perid = mcd.perid
             WHERE v.pleid = :period_id
               AND (
                   mcd.mcdestado = true
                   OR EXISTS (
                       SELECT 1
                       FROM sesion_clase sc
                       INNER JOIN calendario_asistencia ca ON ca.caid = sc.caid
                       WHERE sc.mcdid = mcd.mcdid
                         AND ca.pleid = :period_id_sessions
                   )
               )
             ORDER BY p.perapellidos ASC, p.pernombres ASC"
        );
        $statement->execute([
            'period_id' => $periodId,
            'period_id_sessions' => $periodId,
        ]);

        return $statement->fetchAll();
    }

    public function findTeacherCourseSubject(int $courseSubjectId, int $assignmentId, int $personId, int $periodId, string $date): array|false
    {
        $statement = $this->db->prepare(
            "SELECT
                    mcd.mcdid,
                    mcd.mtcid,
                    mcd.perid,
                    v.curid,
                    v.pleid,
                    v.mtcnombre_mostrar,
                    v.asgnombre
             FROM materia_curso_docente mcd
             INNER JOIN vw_materia_curso v ON v.mtcid = mcd.mtcid
             WHERE mcd.mcdid = :assignment_id
               AND mcd.mtcid = :course_subject_id
               AND mcd.perid = :person_id
               AND v.pleid = :period_id
               AND v.mtcestado = true
               AND mcd.mcdestado = true
               AND mcd.mcdfecha_inicio <= :class_date
               AND (mcd.mcdfecha_fin IS NULL OR mcd.mcdfecha_fin >= :class_date_end)
               AND v.mtcfecha_inicio <= :subject_date
               AND (v.mtcfecha_fin IS NULL OR v.mtcfecha_fin >= :subject_date_end)
             LIMIT 1"
        );
        $statement->execute([
            'assignment_id' => $assignmentId,
            'course_subject_id' => $courseSubjectId,
            'person_id' => $personId,
            'period_id' => $periodId,
            'class_date' => $date,
            'class_date_end' => $date,
            'subject_date' => $date,
            'subject_date_end' => $date,
        ]);

        return $statement->fetch();
    }

    public function findCalendarDayId(int $periodId, string $date): ?int
    {
        $statement = $this->db->prepare(
            "SELECT caid
             FROM calendario_asistencia
             WHERE pleid = :period_id
               AND cafecha = :class_date
             LIMIT 1"
        );
        $statement->execute([
            'period_id' => $periodId,
            'class_date' => $date,
        ]);

        $calendarId = $statement->fetchColumn();

        if ($calendarId !== false) {
            return (int) $calendarId;
        }

        return null;
    }

    public function calendarAllowsSession(int $calendarId, int $courseId, int $hour): bool
    {
        $statement = $this->db->prepare(
            "SELECT catipo_jornada, cahabilitado, cahora_limite
             FROM calendario_asistencia
             WHERE caid = :calendar_id
             LIMIT 1"
        );
        $statement->execute(['calendar_id' => $calendarId]);
        $calendar = $statement->fetch();

        if ($calendar === false || empty($calendar['cahabilitado'])) {
            return false;
        }

        $limit = $calendar['cahora_limite'] !== null ? (int) $calendar['cahora_limite'] : 7;

        if ($hour > $limit) {
            return false;
        }

        if (($calendar['catipo_jornada'] ?? '') !== 'ESPECIAL') {
            return true;
        }

        $detail = $this->db->prepare(
            "SELECT cadhabilitado
             FROM calendario_asistencia_detalle
             WHERE caid = :calendar_id
               AND curid = :course_id
               AND cadnumero_hora = :hour
             LIMIT 1"
        );
        $detail->execute([
            'calendar_id' => $calendarId,
            'course_id' => $courseId,
            'hour' => $hour,
        ]);
        $enabled = $detail->fetchColumn();

        return $enabled !== false && (bool) $enabled;
    }

    public function createOrFindSession(int $calendarId, int $courseSubjectId, int $assignmentId, int $hour, int $userId): int
    {
        $statement = $this->db->prepare(
            "SELECT sclid
             FROM sesion_clase
             WHERE caid = :calendar_id
               AND mtcid = :course_subject_id
               AND sclnumero_hora = :hour
               AND sclestado <> 'ANULADA'
             LIMIT 1"
        );
        $statement->execute([
            'calendar_id' => $calendarId,
            'course_subject_id' => $courseSubjectId,
            'hour' => $hour,
        ]);

        $sessionId = $statement->fetchColumn();

        if ($sessionId !== false) {
            return (int) $sessionId;
        }

        $insert = $this->db->prepare(
            "INSERT INTO sesion_clase (
                caid, mtcid, mcdid, sclnumero_hora, sclestado, usuid_registro
             ) VALUES (
                :calendar_id, :course_subject_id, :assignment_id, :hour, 'REGISTRADA', :user_id
             )
             RETURNING sclid"
        );
        $insert->execute([
            'calendar_id' => $calendarId,
            'course_subject_id' => $courseSubjectId,
            'assignment_id' => $assignmentId,
            'hour' => $hour,
            'user_id' => $userId,
        ]);

        return (int) $insert->fetchColumn();
    }

    public function sessionForTeacher(int $sessionId, int $personId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT
                    sc.sclid,
                    sc.caid,
                    sc.mtcid,
                    sc.mcdid,
                    sc.sclnumero_hora,
                    sc.sclestado,
                    ca.pleid,
                    ca.cafecha,
                    v.curid,
                    v.mtcnombre_mostrar,
                    v.areanombre,
                    v.asgnombre,
                    v.granombre,
                    v.prlnombre
             FROM sesion_clase sc
             INNER JOIN calendario_asistencia ca ON ca.caid = sc.caid
             INNER JOIN materia_curso_docente mcd ON mcd.mcdid = sc.mcdid
             INNER JOIN vw_materia_curso v ON v.mtcid = sc.mtcid
             WHERE sc.sclid = :session_id
               AND mcd.perid = :person_id
               AND sc.sclestado <> 'ANULADA'
             LIMIT 1"
        );
        $statement->execute([
            'session_id' => $sessionId,
            'person_id' => $personId,
        ]);

        return $statement->fetch();
    }

    public function activeStudentsForSession(int $sessionId): array
    {
        $statement = $this->db->prepare(
            "SELECT
                    m.matid,
                    e.estid,
                    p.percedula,
                    p.perapellidos,
                    p.pernombres
             FROM sesion_clase sc
             INNER JOIN calendario_asistencia ca ON ca.caid = sc.caid
             INNER JOIN materia_curso mc ON mc.mtcid = sc.mtcid
             INNER JOIN matricula m ON m.curid = mc.curid
             INNER JOIN estado_matricula em ON em.emdid = m.emdid
             INNER JOIN estudiante e ON e.estid = m.estid
             INNER JOIN persona p ON p.perid = e.perid
             WHERE sc.sclid = :session_id
               AND e.estestado = true
               AND m.matfecha <= ca.cafecha
               AND (m.matfecha_retiro IS NULL OR m.matfecha_retiro >= ca.cafecha)
               AND LOWER(em.emdnombre) IN ('activo', 'activa')
             ORDER BY p.perapellidos ASC, p.pernombres ASC"
        );
        $statement->execute(['session_id' => $sessionId]);

        return $statement->fetchAll();
    }

    public function attendanceBySession(int $sessionId): array
    {
        $statement = $this->db->prepare(
            "SELECT aesid, sclid, estid, aesestado, aesobservacion
             FROM asistencia_estudiante
             WHERE sclid = :session_id"
        );
        $statement->execute(['session_id' => $sessionId]);

        $map = [];

        foreach ($statement->fetchAll() as $row) {
            $map[(int) $row['estid']] = $row;
        }

        return $map;
    }

    public function teacherSessionSummaryByRange(int $personId, int $periodId, string $startDate, string $endDate): array
    {
        $statement = $this->db->prepare(
            "SELECT
                    ca.cafecha,
                    COUNT(DISTINCT sc.sclid) AS total_sesiones,
                    COUNT(ae.aesid) FILTER (
                        WHERE ae.aesestado IN ('ATRASO', 'FALTA_JUSTIFICADA', 'FALTA_INJUSTIFICADA')
                    ) AS total_alertas
             FROM sesion_clase sc
             INNER JOIN calendario_asistencia ca ON ca.caid = sc.caid
             INNER JOIN materia_curso_docente mcd ON mcd.mcdid = sc.mcdid
             LEFT JOIN asistencia_estudiante ae ON ae.sclid = sc.sclid
             WHERE mcd.perid = :person_id
               AND ca.pleid = :period_id
               AND ca.cafecha BETWEEN :start_date AND :end_date
               AND sc.sclestado <> 'ANULADA'
             GROUP BY ca.cafecha
             ORDER BY ca.cafecha ASC"
        );
        $statement->execute([
            'person_id' => $personId,
            'period_id' => $periodId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $map = [];

        foreach ($statement->fetchAll() as $row) {
            $map[(string) $row['cafecha']] = $row;
        }

        return $map;
    }

    public function teacherSessionsByDate(int $personId, int $periodId, string $date): array
    {
        $statement = $this->db->prepare(
            "SELECT
                    sc.sclid,
                    sc.sclnumero_hora,
                    sc.sclestado,
                    ca.cafecha,
                    v.mtcnombre_mostrar,
                    v.granombre,
                    v.prlnombre,
                    COUNT(ae.aesid) AS total_registros,
                    COUNT(ae.aesid) FILTER (WHERE ae.aesestado = 'ASISTENCIA') AS total_asistencias,
                    COUNT(ae.aesid) FILTER (WHERE ae.aesestado = 'ATRASO') AS total_atrasos,
                    COUNT(ae.aesid) FILTER (WHERE ae.aesestado = 'FALTA_JUSTIFICADA') AS total_faltas_justificadas,
                    COUNT(ae.aesid) FILTER (WHERE ae.aesestado = 'FALTA_INJUSTIFICADA') AS total_faltas_injustificadas
             FROM sesion_clase sc
             INNER JOIN calendario_asistencia ca ON ca.caid = sc.caid
             INNER JOIN vw_materia_curso v ON v.mtcid = sc.mtcid
             INNER JOIN materia_curso_docente mcd ON mcd.mcdid = sc.mcdid
             LEFT JOIN asistencia_estudiante ae ON ae.sclid = sc.sclid
             WHERE mcd.perid = :person_id
               AND ca.pleid = :period_id
               AND ca.cafecha = :class_date
               AND sc.sclestado <> 'ANULADA'
             GROUP BY sc.sclid, ca.cafecha, v.mtcnombre_mostrar, v.granombre, v.prlnombre
             ORDER BY sc.sclnumero_hora ASC, v.mtcnombre_mostrar ASC"
        );
        $statement->execute([
            'person_id' => $personId,
            'period_id' => $periodId,
            'class_date' => $date,
        ]);

        return $statement->fetchAll();
    }

    public function supervisedSessions(int $periodId, string $date, int $courseId = 0): array
    {
        $courseFilter = $courseId > 0 ? 'AND v.curid = :course_id' : '';
        $statement = $this->db->prepare(
            "SELECT
                    sc.sclid,
                    sc.sclnumero_hora,
                    sc.sclestado,
                    sc.sclfecha_registro,
                    sc.sclfecha_anulacion,
                    sc.sclmotivo_anulacion,
                    ca.cafecha,
                    ca.catipo_jornada,
                    v.curid,
                    v.mtcnombre_mostrar,
                    v.granombre,
                    v.prlnombre,
                    docente.perapellidos AS docente_apellidos,
                    docente.pernombres AS docente_nombres,
                    registrador.usunombre AS usuario_registro,
                    anulador.usunombre AS usuario_anulacion,
                    COUNT(ae.aesid) AS total_registros,
                    COUNT(ae.aesid) FILTER (WHERE ae.aesestado = 'ASISTENCIA') AS total_asistencias,
                    COUNT(ae.aesid) FILTER (WHERE ae.aesestado = 'ATRASO') AS total_atrasos,
                    COUNT(ae.aesid) FILTER (WHERE ae.aesestado = 'FALTA_JUSTIFICADA') AS total_faltas_justificadas,
                    COUNT(ae.aesid) FILTER (WHERE ae.aesestado = 'FALTA_INJUSTIFICADA') AS total_faltas_injustificadas
             FROM sesion_clase sc
             INNER JOIN calendario_asistencia ca ON ca.caid = sc.caid
             INNER JOIN vw_materia_curso v ON v.mtcid = sc.mtcid
             INNER JOIN materia_curso_docente mcd ON mcd.mcdid = sc.mcdid
             INNER JOIN persona docente ON docente.perid = mcd.perid
             INNER JOIN usuario registrador ON registrador.usuid = sc.usuid_registro
             LEFT JOIN usuario anulador ON anulador.usuid = sc.usuid_anulacion
             LEFT JOIN asistencia_estudiante ae ON ae.sclid = sc.sclid
             WHERE ca.pleid = :period_id
               AND ca.cafecha = :class_date
               {$courseFilter}
             GROUP BY sc.sclid, ca.cafecha, ca.catipo_jornada, v.curid, v.mtcnombre_mostrar,
                      v.granombre, v.prlnombre, docente.perapellidos, docente.pernombres,
                      registrador.usunombre, anulador.usunombre
             ORDER BY ca.cafecha DESC, v.granombre ASC, v.prlnombre ASC, sc.sclnumero_hora ASC"
        );
        $statement->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $statement->bindValue(':class_date', $date, PDO::PARAM_STR);

        if ($courseId > 0) {
            $statement->bindValue(':course_id', $courseId, PDO::PARAM_INT);
        }

        $statement->execute();

        return $statement->fetchAll();
    }

    public function sessionForSupervision(int $sessionId, int $periodId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT
                    sc.sclid,
                    sc.sclnumero_hora,
                    sc.sclestado,
                    sc.sclfecha_registro,
                    sc.sclfecha_anulacion,
                    sc.sclmotivo_anulacion,
                    ca.pleid,
                    ca.cafecha,
                    ca.catipo_jornada,
                    v.curid,
                    v.mtcnombre_mostrar,
                    v.granombre,
                    v.prlnombre,
                    docente.perapellidos AS docente_apellidos,
                    docente.pernombres AS docente_nombres,
                    registrador.usunombre AS usuario_registro,
                    anulador.usunombre AS usuario_anulacion
             FROM sesion_clase sc
             INNER JOIN calendario_asistencia ca ON ca.caid = sc.caid
             INNER JOIN vw_materia_curso v ON v.mtcid = sc.mtcid
             INNER JOIN materia_curso_docente mcd ON mcd.mcdid = sc.mcdid
             INNER JOIN persona docente ON docente.perid = mcd.perid
             INNER JOIN usuario registrador ON registrador.usuid = sc.usuid_registro
             LEFT JOIN usuario anulador ON anulador.usuid = sc.usuid_anulacion
             WHERE sc.sclid = :session_id
               AND ca.pleid = :period_id
             LIMIT 1"
        );
        $statement->execute([
            'session_id' => $sessionId,
            'period_id' => $periodId,
        ]);

        return $statement->fetch();
    }

    public function attendanceDetailBySession(int $sessionId): array
    {
        $statement = $this->db->prepare(
            "SELECT
                    ae.aesid,
                    ae.estid,
                    ae.aesestado,
                    ae.aesobservacion,
                    ae.aesfecha_registro,
                    ae.aesfecha_modificacion,
                    ae.jaid,
                    p.percedula,
                    p.perapellidos,
                    p.pernombres,
                    registrador.usunombre AS usuario_registro,
                    modificador.usunombre AS usuario_modificacion,
                    ja.jamotivo AS justificacion_motivo
             FROM asistencia_estudiante ae
             INNER JOIN estudiante e ON e.estid = ae.estid
             INNER JOIN persona p ON p.perid = e.perid
             INNER JOIN usuario registrador ON registrador.usuid = ae.usuid_registro
             LEFT JOIN usuario modificador ON modificador.usuid = ae.usuid_modificacion
             LEFT JOIN justificacion_asistencia ja ON ja.jaid = ae.jaid
             WHERE ae.sclid = :session_id
             ORDER BY p.perapellidos ASC, p.pernombres ASC"
        );
        $statement->execute(['session_id' => $sessionId]);

        return $statement->fetchAll();
    }

    public function annulSession(int $sessionId, int $userId, string $reason): void
    {
        if ($reason === '') {
            throw new RuntimeException('Debe ingresar el motivo de anulacion.');
        }

        $statement = $this->db->prepare(
            "UPDATE sesion_clase
             SET sclestado = 'ANULADA',
                 usuid_anulacion = :user_id,
                 sclfecha_anulacion = CURRENT_TIMESTAMP,
                 sclmotivo_anulacion = :reason
             WHERE sclid = :session_id
               AND sclestado <> 'ANULADA'"
        );
        $statement->execute([
            'session_id' => $sessionId,
            'user_id' => $userId,
            'reason' => $reason,
        ]);
    }

    public function studentDailySummary(int $studentId, int $periodId, string $startDate, string $endDate): array
    {
        $statement = $this->db->prepare(
            "SELECT estid, pleid, cafecha, resumen_estado, total_asistencias, total_atrasos,
                    total_faltas_justificadas, total_faltas_injustificadas
             FROM vw_asistencia_resumen_diario
             WHERE estid = :student_id
               AND pleid = :period_id
               AND cafecha BETWEEN :start_date AND :end_date
             ORDER BY cafecha ASC"
        );
        $statement->execute([
            'student_id' => $studentId,
            'period_id' => $periodId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $map = [];

        foreach ($statement->fetchAll() as $row) {
            $map[(string) $row['cafecha']] = $row;
        }

        return $map;
    }

    public function studentAttendanceDetail(int $studentId, int $periodId, string $date): array
    {
        $statement = $this->db->prepare(
            "SELECT
                    ae.aesestado,
                    ae.aesobservacion,
                    ae.aesfecha_registro,
                    ca.cafecha,
                    sc.sclnumero_hora,
                    v.mtcnombre_mostrar,
                    v.asgnombre,
                    docente.perapellidos AS docente_apellidos,
                    docente.pernombres AS docente_nombres,
                    ja.jamotivo AS justificacion_motivo
             FROM asistencia_estudiante ae
             INNER JOIN sesion_clase sc ON sc.sclid = ae.sclid
             INNER JOIN calendario_asistencia ca ON ca.caid = sc.caid
             INNER JOIN vw_materia_curso v ON v.mtcid = sc.mtcid
             INNER JOIN materia_curso_docente mcd ON mcd.mcdid = sc.mcdid
             INNER JOIN persona docente ON docente.perid = mcd.perid
             LEFT JOIN justificacion_asistencia ja ON ja.jaid = ae.jaid
             WHERE ae.estid = :student_id
               AND ca.pleid = :period_id
               AND ca.cafecha = :class_date
               AND sc.sclestado <> 'ANULADA'
             ORDER BY sc.sclnumero_hora ASC, v.mtcnombre_mostrar ASC"
        );
        $statement->execute([
            'student_id' => $studentId,
            'period_id' => $periodId,
            'class_date' => $date,
        ]);

        return $statement->fetchAll();
    }

    public function consolidatedAttendanceReport(
        int $periodId,
        string $startDate,
        string $endDate,
        int $courseId = 0,
        int $studentId = 0,
        int $courseSubjectId = 0,
        int $teacherPersonId = 0
    ): array {
        $courseFilter = $courseId > 0 ? 'AND v.curid = :course_id' : '';
        $studentFilter = $studentId > 0 ? 'AND ae.estid = :student_id' : '';
        $courseSubjectFilter = $courseSubjectId > 0 ? 'AND sc.mtcid = :course_subject_id' : '';
        $teacherFilter = $teacherPersonId > 0 ? 'AND mcd.perid = :teacher_person_id' : '';
        $statement = $this->db->prepare(
            "SELECT
                    ae.estid,
                    v.curid,
                    p.percedula,
                    p.perapellidos,
                    p.pernombres,
                    v.granombre,
                    v.prlnombre,
                    COUNT(*) AS total_registros,
                    COUNT(*) FILTER (WHERE ae.aesestado = 'ASISTENCIA') AS total_asistencias,
                    COUNT(*) FILTER (WHERE ae.aesestado = 'ATRASO') AS total_atrasos,
                    COUNT(*) FILTER (WHERE ae.aesestado = 'FALTA_JUSTIFICADA') AS total_faltas_justificadas,
                    COUNT(*) FILTER (WHERE ae.aesestado = 'FALTA_INJUSTIFICADA') AS total_faltas_injustificadas,
                    MIN(ca.cafecha) AS primera_fecha,
                    MAX(ca.cafecha) AS ultima_fecha
             FROM asistencia_estudiante ae
             INNER JOIN estudiante e ON e.estid = ae.estid
             INNER JOIN persona p ON p.perid = e.perid
             INNER JOIN sesion_clase sc ON sc.sclid = ae.sclid
             INNER JOIN materia_curso_docente mcd ON mcd.mcdid = sc.mcdid
             INNER JOIN calendario_asistencia ca ON ca.caid = sc.caid
             INNER JOIN vw_materia_curso v ON v.mtcid = sc.mtcid
             WHERE ca.pleid = :period_id
               AND ca.cafecha BETWEEN :start_date AND :end_date
                AND sc.sclestado <> 'ANULADA'
                {$courseFilter}
                {$studentFilter}
                {$courseSubjectFilter}
                {$teacherFilter}
              GROUP BY ae.estid, v.curid, p.percedula, p.perapellidos, p.pernombres,
                       v.granombre, v.prlnombre
             ORDER BY v.granombre ASC, v.prlnombre ASC, p.perapellidos ASC, p.pernombres ASC"
        );
        $statement->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $statement->bindValue(':start_date', $startDate, PDO::PARAM_STR);
        $statement->bindValue(':end_date', $endDate, PDO::PARAM_STR);

        if ($courseId > 0) {
            $statement->bindValue(':course_id', $courseId, PDO::PARAM_INT);
        }

        if ($studentId > 0) {
            $statement->bindValue(':student_id', $studentId, PDO::PARAM_INT);
        }

        if ($courseSubjectId > 0) {
            $statement->bindValue(':course_subject_id', $courseSubjectId, PDO::PARAM_INT);
        }

        if ($teacherPersonId > 0) {
            $statement->bindValue(':teacher_person_id', $teacherPersonId, PDO::PARAM_INT);
        }

        $statement->execute();

        return $statement->fetchAll();
    }

    public function attendanceMatrixReport(
        int $periodId,
        string $startDate,
        string $endDate,
        int $courseId = 0,
        int $studentId = 0,
        int $courseSubjectId = 0,
        int $teacherPersonId = 0
    ): array {
        $courseFilter = $courseId > 0 ? 'AND v.curid = :course_id' : '';
        $studentFilter = $studentId > 0 ? 'AND ae.estid = :student_id' : '';
        $courseSubjectFilter = $courseSubjectId > 0 ? 'AND sc.mtcid = :course_subject_id' : '';
        $teacherFilter = $teacherPersonId > 0 ? 'AND mcd.perid = :teacher_person_id' : '';
        $statement = $this->db->prepare(
            "SELECT
                    ae.estid,
                    v.curid,
                    p.percedula,
                    p.perapellidos,
                    p.pernombres,
                    v.granombre,
                    v.prlnombre,
                    ca.cafecha,
                    COUNT(*) FILTER (WHERE ae.aesestado = 'ASISTENCIA') AS total_asistencias,
                    COUNT(*) FILTER (WHERE ae.aesestado = 'ATRASO') AS total_atrasos,
                    COUNT(*) FILTER (WHERE ae.aesestado = 'FALTA_JUSTIFICADA') AS total_faltas_justificadas,
                    COUNT(*) FILTER (WHERE ae.aesestado = 'FALTA_INJUSTIFICADA') AS total_faltas_injustificadas
             FROM asistencia_estudiante ae
             INNER JOIN estudiante e ON e.estid = ae.estid
             INNER JOIN persona p ON p.perid = e.perid
             INNER JOIN sesion_clase sc ON sc.sclid = ae.sclid
             INNER JOIN materia_curso_docente mcd ON mcd.mcdid = sc.mcdid
             INNER JOIN calendario_asistencia ca ON ca.caid = sc.caid
             INNER JOIN vw_materia_curso v ON v.mtcid = sc.mtcid
             WHERE ca.pleid = :period_id
               AND ca.cafecha BETWEEN :start_date AND :end_date
               AND sc.sclestado <> 'ANULADA'
               {$courseFilter}
               {$studentFilter}
               {$courseSubjectFilter}
               {$teacherFilter}
             GROUP BY ae.estid, v.curid, p.percedula, p.perapellidos, p.pernombres,
                      v.granombre, v.prlnombre, ca.cafecha
             ORDER BY v.granombre ASC, v.prlnombre ASC, p.perapellidos ASC, p.pernombres ASC, ca.cafecha ASC"
        );
        $statement->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $statement->bindValue(':start_date', $startDate, PDO::PARAM_STR);
        $statement->bindValue(':end_date', $endDate, PDO::PARAM_STR);

        if ($courseId > 0) {
            $statement->bindValue(':course_id', $courseId, PDO::PARAM_INT);
        }

        if ($studentId > 0) {
            $statement->bindValue(':student_id', $studentId, PDO::PARAM_INT);
        }

        if ($courseSubjectId > 0) {
            $statement->bindValue(':course_subject_id', $courseSubjectId, PDO::PARAM_INT);
        }

        if ($teacherPersonId > 0) {
            $statement->bindValue(':teacher_person_id', $teacherPersonId, PDO::PARAM_INT);
        }

        $statement->execute();
        $dates = [];
        $rows = [];

        foreach ($statement->fetchAll() as $record) {
            $date = (string) $record['cafecha'];
            $studentKey = (string) $record['estid'] . '-' . (string) $record['curid'];
            $dates[$date] = $date;

            if (!isset($rows[$studentKey])) {
                $rows[$studentKey] = [
                    'estid' => (int) $record['estid'],
                    'curid' => (int) $record['curid'],
                    'percedula' => (string) $record['percedula'],
                    'perapellidos' => (string) $record['perapellidos'],
                    'pernombres' => (string) $record['pernombres'],
                    'granombre' => (string) $record['granombre'],
                    'prlnombre' => (string) $record['prlnombre'],
                    'dias' => [],
                    'total_asistencias' => 0,
                    'total_atrasos' => 0,
                    'total_faltas_justificadas' => 0,
                    'total_faltas_injustificadas' => 0,
                ];
            }

            $attendanceCount = (int) $record['total_asistencias'];
            $lateCount = (int) $record['total_atrasos'];
            $justifiedCount = (int) $record['total_faltas_justificadas'];
            $unjustifiedCount = (int) $record['total_faltas_injustificadas'];
            $codes = [];

            if ($attendanceCount > 0 || $lateCount > 0) {
                $codes[] = 'As';
            }

            if ($lateCount > 0) {
                $codes[] = 'A';
            }

            if ($justifiedCount > 0) {
                $codes[] = 'FJ';
            }

            if ($unjustifiedCount > 0) {
                $codes[] = 'FI';
            }

            $rows[$studentKey]['dias'][$date] = implode('/', $codes);
            $rows[$studentKey]['total_asistencias'] += ($attendanceCount > 0 || $lateCount > 0) ? 1 : 0;
            $rows[$studentKey]['total_atrasos'] += $lateCount > 0 ? 1 : 0;
            $rows[$studentKey]['total_faltas_justificadas'] += $justifiedCount > 0 ? 1 : 0;
            $rows[$studentKey]['total_faltas_injustificadas'] += $unjustifiedCount > 0 ? 1 : 0;
        }

        ksort($dates);

        return [
            'dates' => array_values($dates),
            'rows' => array_values($rows),
        ];
    }

    public function studentAttendanceHourlyMatrixReport(
        int $periodId,
        string $startDate,
        string $endDate,
        int $studentId,
        int $courseId = 0,
        int $courseSubjectId = 0,
        int $teacherPersonId = 0
    ): array {
        $courseFilter = $courseId > 0 ? 'AND v.curid = :course_id' : '';
        $courseSubjectFilter = $courseSubjectId > 0 ? 'AND sc.mtcid = :course_subject_id' : '';
        $teacherFilter = $teacherPersonId > 0 ? 'AND mcd.perid = :teacher_person_id' : '';
        $statement = $this->db->prepare(
            "SELECT
                    ca.cafecha,
                    sc.sclnumero_hora,
                    ae.aesestado
             FROM asistencia_estudiante ae
             INNER JOIN sesion_clase sc ON sc.sclid = ae.sclid
             INNER JOIN materia_curso_docente mcd ON mcd.mcdid = sc.mcdid
             INNER JOIN calendario_asistencia ca ON ca.caid = sc.caid
             INNER JOIN vw_materia_curso v ON v.mtcid = sc.mtcid
             WHERE ca.pleid = :period_id
               AND ae.estid = :student_id
               AND ca.cafecha BETWEEN :start_date AND :end_date
               AND sc.sclestado <> 'ANULADA'
               {$courseFilter}
               {$courseSubjectFilter}
               {$teacherFilter}
             ORDER BY ca.cafecha ASC, sc.sclnumero_hora ASC"
        );
        $statement->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $statement->bindValue(':student_id', $studentId, PDO::PARAM_INT);
        $statement->bindValue(':start_date', $startDate, PDO::PARAM_STR);
        $statement->bindValue(':end_date', $endDate, PDO::PARAM_STR);

        if ($courseId > 0) {
            $statement->bindValue(':course_id', $courseId, PDO::PARAM_INT);
        }

        if ($courseSubjectId > 0) {
            $statement->bindValue(':course_subject_id', $courseSubjectId, PDO::PARAM_INT);
        }

        if ($teacherPersonId > 0) {
            $statement->bindValue(':teacher_person_id', $teacherPersonId, PDO::PARAM_INT);
        }

        $statement->execute();
        $months = [];
        $stateCodes = [
            'ASISTENCIA' => 'As',
            'ATRASO' => 'At',
            'FALTA_JUSTIFICADA' => 'FJ',
            'FALTA_INJUSTIFICADA' => 'FI',
        ];
        $start = new \DateTimeImmutable($startDate);
        $end = new \DateTimeImmutable($endDate);

        for ($cursor = $start; $cursor <= $end; $cursor = $cursor->modify('+1 day')) {
            $date = $cursor->format('Y-m-d');
            $month = $cursor->format('Y-m');

            if (!isset($months[$month])) {
                $months[$month] = [
                    'month' => $month,
                    'dates' => [],
                    'summary' => [
                        'asistidos' => 0,
                        'faltas_justificadas' => 0,
                        'faltas_injustificadas' => 0,
                        'atrasos' => 0,
                    ],
                ];
            }

            $months[$month]['dates'][$date] = [
                'date' => $date,
                'hours' => [],
                'flags' => [
                    'asistencia' => false,
                    'atraso' => false,
                    'falta_justificada' => false,
                    'falta_injustificada' => false,
                ],
            ];
        }

        foreach ($statement->fetchAll() as $record) {
            $date = (string) $record['cafecha'];
            $month = substr($date, 0, 7);
            $hour = (int) $record['sclnumero_hora'];
            $status = (string) $record['aesestado'];

            if ($hour < 1 || $hour > 7) {
                continue;
            }

            if (!isset($months[$month])) {
                $months[$month] = [
                    'month' => $month,
                    'dates' => [],
                    'summary' => [
                        'asistidos' => 0,
                        'faltas_justificadas' => 0,
                        'faltas_injustificadas' => 0,
                        'atrasos' => 0,
                    ],
                ];
            }

            if (!isset($months[$month]['dates'][$date])) {
                $months[$month]['dates'][$date] = [
                    'date' => $date,
                    'hours' => [],
                    'flags' => [
                        'asistencia' => false,
                        'atraso' => false,
                        'falta_justificada' => false,
                        'falta_injustificada' => false,
                    ],
                ];
            }

            $months[$month]['dates'][$date]['hours'][$hour] = $stateCodes[$status] ?? '';

            if ($status === 'ASISTENCIA') {
                $months[$month]['dates'][$date]['flags']['asistencia'] = true;
            } elseif ($status === 'ATRASO') {
                $months[$month]['dates'][$date]['flags']['atraso'] = true;
            } elseif ($status === 'FALTA_JUSTIFICADA') {
                $months[$month]['dates'][$date]['flags']['falta_justificada'] = true;
            } elseif ($status === 'FALTA_INJUSTIFICADA') {
                $months[$month]['dates'][$date]['flags']['falta_injustificada'] = true;
            }
        }

        foreach ($months as &$month) {
            $month['summary'] = [
                'asistidos' => 0,
                'faltas_justificadas' => 0,
                'faltas_injustificadas' => 0,
                'atrasos' => 0,
            ];

            foreach ($month['dates'] as &$date) {
                $flags = is_array($date['flags'] ?? null) ? $date['flags'] : [];

                if (!empty($flags['asistencia']) || !empty($flags['atraso'])) {
                    $month['summary']['asistidos']++;
                }

                if (!empty($flags['falta_justificada'])) {
                    $month['summary']['faltas_justificadas']++;
                }

                if (!empty($flags['falta_injustificada'])) {
                    $month['summary']['faltas_injustificadas']++;
                }

                if (!empty($flags['atraso'])) {
                    $month['summary']['atrasos']++;
                }

                unset($date['flags']);
            }
            unset($date);

            $month['dates'] = array_values($month['dates']);
        }
        unset($month);

        return array_values($months);
    }

    public function saveAttendance(int $sessionId, array $records, int $userId): void
    {
        $validStates = ['ASISTENCIA', 'ATRASO', 'FALTA_INJUSTIFICADA'];
        $approvedJustifications = $this->approvedJustificationsForSession($sessionId);

        $this->db->beginTransaction();

        try {
            $statement = $this->db->prepare(
                "INSERT INTO asistencia_estudiante (
                    sclid, estid, aesestado, aesobservacion, jaid, usuid_registro
                 ) VALUES (
                    :session_id, :student_id, :status, :note, :justification_id, :user_id
                 )
                 ON CONFLICT (sclid, estid) DO UPDATE
                 SET aesestado = EXCLUDED.aesestado,
                     aesobservacion = EXCLUDED.aesobservacion,
                     jaid = EXCLUDED.jaid,
                     usuid_modificacion = :mod_user_id,
                     aesfecha_modificacion = CURRENT_TIMESTAMP"
            );

            foreach ($records as $record) {
                $studentId = (int) ($record['estid'] ?? 0);
                $status = (string) ($record['aesestado'] ?? 'ASISTENCIA');

                if ($studentId <= 0 || !in_array($status, $validStates, true)) {
                    throw new RuntimeException('Existe un registro de asistencia no valido.');
                }

                $note = trim((string) ($record['aesobservacion'] ?? ''));
                $justificationId = null;

                if ($status === 'FALTA_INJUSTIFICADA' && isset($approvedJustifications[$studentId])) {
                    $status = 'FALTA_JUSTIFICADA';
                    $justificationId = (int) $approvedJustifications[$studentId]['jaid'];
                }

                $statement->execute([
                    'session_id' => $sessionId,
                    'student_id' => $studentId,
                    'status' => $status,
                    'note' => $note !== '' ? $note : null,
                    'user_id' => $userId,
                    'mod_user_id' => $userId,
                    'justification_id' => $justificationId,
                ]);
            }

            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function closeTeacherSession(int $sessionId, int $personId): void
    {
        $statement = $this->db->prepare(
            "UPDATE sesion_clase sc
             SET sclestado = 'CERRADA'
             FROM materia_curso_docente mcd
             WHERE sc.mcdid = mcd.mcdid
               AND sc.sclid = :session_id
               AND mcd.perid = :person_id
               AND sc.sclestado = 'REGISTRADA'
               AND EXISTS (
                    SELECT 1
                    FROM asistencia_estudiante ae
                    WHERE ae.sclid = sc.sclid
               )"
        );
        $statement->execute([
            'session_id' => $sessionId,
            'person_id' => $personId,
        ]);

        if ($statement->rowCount() === 0) {
            throw new RuntimeException('La sesion no se pudo cerrar. Revise que tenga registros y no este cerrada o anulada.');
        }
    }

    public function studentsForJustification(int $periodId, int $courseId = 0): array
    {
        $courseFilter = $courseId > 0 ? 'AND c.curid = :course_id' : '';
        $statement = $this->db->prepare(
            "SELECT DISTINCT ON (e.estid)
                    e.estid,
                    m.matid,
                    p.percedula,
                    p.perapellidos,
                    p.pernombres,
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
               {$courseFilter}
             ORDER BY e.estid, m.matfecha DESC, m.matid DESC"
        );
        $statement->bindValue(':period_id', $periodId, PDO::PARAM_INT);

        if ($courseId > 0) {
            $statement->bindValue(':course_id', $courseId, PDO::PARAM_INT);
        }

        $statement->execute();

        return $statement->fetchAll();
    }

    public function unjustifiedAbsencesForJustification(int $periodId, int $courseId = 0, int $studentId = 0): array
    {
        $conditions = ['ca.pleid = :period_id', "ae.aesestado = 'FALTA_INJUSTIFICADA'"];
        $params = ['period_id' => $periodId];

        if ($courseId > 0) {
            $conditions[] = 'c.curid = :course_id';
            $params['course_id'] = $courseId;
        }

        if ($studentId > 0) {
            $conditions[] = 'e.estid = :student_id';
            $params['student_id'] = $studentId;
        }

        $statement = $this->db->prepare(
            "SELECT
                    ae.aesid,
                    ae.estid,
                    m.matid,
                    ca.cafecha,
                    sc.sclid,
                    sc.sclnumero_hora,
                    v.mtcnombre_mostrar,
                    CONCAT(g.granombre, ' ', pr.prlnombre) AS curso,
                    p.percedula,
                    p.perapellidos,
                    p.pernombres
             FROM asistencia_estudiante ae
             INNER JOIN sesion_clase sc ON sc.sclid = ae.sclid
             INNER JOIN calendario_asistencia ca ON ca.caid = sc.caid
             INNER JOIN materia_curso mc ON mc.mtcid = sc.mtcid
             INNER JOIN curso c ON c.curid = mc.curid
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN paralelo pr ON pr.prlid = c.prlid
             INNER JOIN vw_materia_curso v ON v.mtcid = sc.mtcid
             INNER JOIN estudiante e ON e.estid = ae.estid
             INNER JOIN persona p ON p.perid = e.perid
             LEFT JOIN matricula m ON m.estid = e.estid
                AND m.curid = c.curid
                AND m.matfecha <= ca.cafecha
                AND (m.matfecha_retiro IS NULL OR m.matfecha_retiro >= ca.cafecha)
             WHERE " . implode(' AND ', $conditions) . "
             ORDER BY ca.cafecha DESC, p.perapellidos ASC, p.pernombres ASC, sc.sclnumero_hora ASC"
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function unjustifiedAbsencesByIds(int $periodId, array $attendanceIds): array
    {
        $attendanceIds = array_values(array_unique(array_filter(array_map('intval', $attendanceIds), static fn (int $id): bool => $id > 0)));

        if ($attendanceIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($attendanceIds), '?'));
        $statement = $this->db->prepare(
            "SELECT
                    ae.aesid,
                    ae.estid,
                    m.matid,
                    ca.cafecha
             FROM asistencia_estudiante ae
             INNER JOIN sesion_clase sc ON sc.sclid = ae.sclid
             INNER JOIN calendario_asistencia ca ON ca.caid = sc.caid
             INNER JOIN materia_curso mc ON mc.mtcid = sc.mtcid
             LEFT JOIN matricula m ON m.estid = ae.estid
                AND m.curid = mc.curid
                AND m.matfecha <= ca.cafecha
                AND (m.matfecha_retiro IS NULL OR m.matfecha_retiro >= ca.cafecha)
             WHERE ca.pleid = ?
               AND ae.aesestado = 'FALTA_INJUSTIFICADA'
               AND ae.aesid IN ({$placeholders})
             ORDER BY ca.cafecha ASC, ae.aesid ASC"
        );
        $statement->execute(array_merge([$periodId], $attendanceIds));

        return $statement->fetchAll();
    }

    public function justificationsByPeriod(int $periodId): array
    {
        $statement = $this->db->prepare(
            "SELECT ja.jaid, ja.estid, ja.matid, ja.jafecha_inicio, ja.jafecha_fin,
                    ja.jatipo, ja.jamotivo, ja.jaobservacion, ja.jaestado,
                    ja.jaarchivo, ja.jaobservacion_revision, ja.jafecha_solicitud, ja.jafecha_revision,
                    p.percedula, p.perapellidos, p.pernombres,
                    reviewer.usunombre AS usuario_revisa
             FROM justificacion_asistencia ja
             INNER JOIN estudiante e ON e.estid = ja.estid
             INNER JOIN persona p ON p.perid = e.perid
             LEFT JOIN usuario reviewer ON reviewer.usuid = ja.jausuid_revisa
             LEFT JOIN matricula m ON m.matid = ja.matid
             LEFT JOIN curso c ON c.curid = m.curid
             WHERE c.pleid = :period_id
                OR (
                    ja.matid IS NULL
                    AND EXISTS (
                        SELECT 1
                        FROM matricula mx
                        INNER JOIN curso cx ON cx.curid = mx.curid
                        WHERE mx.estid = ja.estid
                          AND cx.pleid = :period_id_exists
                    )
                )
             ORDER BY ja.jafecha_solicitud DESC, ja.jaid DESC
             LIMIT 80"
        );
        $statement->execute([
            'period_id' => $periodId,
            'period_id_exists' => $periodId,
        ]);

        return $statement->fetchAll();
    }

    public function createJustification(array $data): int
    {
        $status = in_array((string) ($data['jaestado'] ?? ''), ['PENDIENTE', 'APROBADA', 'RECHAZADA'], true)
            ? (string) $data['jaestado']
            : 'PENDIENTE';

        $statement = $this->db->prepare(
            "INSERT INTO justificacion_asistencia (
                estid, matid, jafecha_inicio, jafecha_fin, jatipo, jamotivo,
                jaobservacion, jaarchivo, jaestado, jausuid_solicita, jausuid_revisa, jafecha_revision
             ) VALUES (
                :student_id, :matriculation_id, :start_date, :end_date, :type, :reason,
                :note, :document_path, :status, :user_id, :review_user_id, :review_date
             )"
        );
        $statement->bindValue(':student_id', $data['estid'], PDO::PARAM_INT);
        $statement->bindValue(':matriculation_id', $data['matid'] > 0 ? $data['matid'] : null, $data['matid'] > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $statement->bindValue(':start_date', $data['jafecha_inicio'], PDO::PARAM_STR);
        $statement->bindValue(':end_date', $data['jafecha_fin'], PDO::PARAM_STR);
        $statement->bindValue(':type', $data['jatipo'], PDO::PARAM_STR);
        $statement->bindValue(':reason', $data['jamotivo'], PDO::PARAM_STR);
        $statement->bindValue(':note', $data['jaobservacion'] !== '' ? $data['jaobservacion'] : null, $data['jaobservacion'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $documentPath = trim((string) ($data['jaarchivo'] ?? ''));
        $statement->bindValue(':document_path', $documentPath !== '' ? $documentPath : null, $documentPath !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $statement->bindValue(':status', $status, PDO::PARAM_STR);
        $statement->bindValue(':user_id', $data['usuid'], PDO::PARAM_INT);
        $statement->bindValue(':review_user_id', $status === 'APROBADA' ? $data['usuid'] : null, $status === 'APROBADA' ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $statement->bindValue(':review_date', $status === 'APROBADA' ? date('Y-m-d H:i:s') : null, $status === 'APROBADA' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $statement->execute();

        $justificationId = (int) $this->db->lastInsertId();

        if ($status === 'APROBADA') {
            $this->applyJustificationToAttendance($justificationId, (int) $data['usuid']);
        }

        return $justificationId;
    }

    public function reviewJustification(int $justificationId, string $status, int $userId, string $note): void
    {
        if (!in_array($status, ['APROBADA', 'RECHAZADA'], true)) {
            throw new RuntimeException('El estado de revision no es valido.');
        }

        $this->db->beginTransaction();

        try {
            $statement = $this->db->prepare(
                "UPDATE justificacion_asistencia
                 SET jaestado = :status,
                     jausuid_revisa = :user_id,
                     jaobservacion_revision = :note,
                     jafecha_revision = CURRENT_TIMESTAMP
                 WHERE jaid = :id
                   AND jaestado <> 'ANULADA'"
            );
            $statement->execute([
                'id' => $justificationId,
                'status' => $status,
                'user_id' => $userId,
                'note' => $note !== '' ? $note : null,
            ]);

            if ($status === 'APROBADA') {
                $this->applyJustificationToAttendance($justificationId, $userId);
            }

            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function confirmJustification(int $justificationId, int $userId): void
    {
        $statement = $this->db->prepare(
            "UPDATE justificacion_asistencia
             SET jaobservacion_revision = 'CONFIRMADA',
                 jausuid_revisa = :user_id,
                 jafecha_revision = CURRENT_TIMESTAMP
             WHERE jaid = :id
               AND jaestado = 'APROBADA'"
        );
        $statement->execute([
            'id' => $justificationId,
            'user_id' => $userId,
        ]);
    }

    public function annulJustification(int $justificationId, int $userId, string $reason): void
    {
        if ($reason === '') {
            throw new RuntimeException('Debe ingresar el motivo de anulacion.');
        }

        $this->db->beginTransaction();

        try {
            $statement = $this->db->prepare(
                "UPDATE justificacion_asistencia
                 SET jaestado = 'ANULADA',
                     jausuid_anulacion = :user_id,
                     jafecha_anulacion = CURRENT_TIMESTAMP,
                     jamotivo_anulacion = :reason
                 WHERE jaid = :id"
            );
            $statement->execute([
                'id' => $justificationId,
                'user_id' => $userId,
                'reason' => $reason,
            ]);

            $attendance = $this->db->prepare(
                "UPDATE asistencia_estudiante
                 SET aesestado = 'FALTA_INJUSTIFICADA',
                     jaid = NULL,
                     usuid_modificacion = :user_id,
                     aesfecha_modificacion = CURRENT_TIMESTAMP
                 WHERE jaid = :id
                   AND aesestado = 'FALTA_JUSTIFICADA'"
            );
            $attendance->execute([
                'id' => $justificationId,
                'user_id' => $userId,
            ]);

            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    private function approvedJustificationsForSession(int $sessionId): array
    {
        $statement = $this->db->prepare(
            "SELECT DISTINCT ON (ja.estid)
                    ja.jaid, ja.estid
             FROM justificacion_asistencia ja
             INNER JOIN sesion_clase sc ON sc.sclid = :session_id
             INNER JOIN calendario_asistencia ca ON ca.caid = sc.caid
             WHERE ja.jaestado = 'APROBADA'
               AND ca.cafecha BETWEEN ja.jafecha_inicio AND ja.jafecha_fin
               AND (
                    ja.sclid IS NULL
                    OR ja.sclid = sc.sclid
               )
             ORDER BY ja.estid, ja.jafecha_revision DESC NULLS LAST, ja.jaid DESC"
        );
        $statement->execute(['session_id' => $sessionId]);

        $map = [];

        foreach ($statement->fetchAll() as $row) {
            $map[(int) $row['estid']] = $row;
        }

        return $map;
    }

    private function applyJustificationToAttendance(int $justificationId, int $userId): void
    {
        $statement = $this->db->prepare(
            "UPDATE asistencia_estudiante ae
             SET aesestado = 'FALTA_JUSTIFICADA',
                 jaid = ja.jaid,
                 usuid_modificacion = :user_id,
                 aesfecha_modificacion = CURRENT_TIMESTAMP
             FROM justificacion_asistencia ja,
                  sesion_clase sc,
                  calendario_asistencia ca
             WHERE ja.jaid = :justification_id
               AND sc.sclid = ae.sclid
               AND ca.caid = sc.caid
               AND (ja.sclid IS NULL OR ja.sclid = sc.sclid)
               AND ae.estid = ja.estid
               AND ae.aesestado = 'FALTA_INJUSTIFICADA'
               AND ca.cafecha BETWEEN ja.jafecha_inicio AND ja.jafecha_fin"
        );
        $statement->execute([
            'justification_id' => $justificationId,
            'user_id' => $userId,
        ]);
    }

    public function calendarDaysByPeriod(int $periodId): array
    {
        $statement = $this->db->prepare(
            "SELECT ca.caid, ca.pleid, ca.cafecha, ca.catipo_jornada, ca.cahabilitado,
                    ca.cahora_limite, ca.caobservacion, ca.cafecha_creacion,
                    u.usunombre AS usuario_registro
             FROM calendario_asistencia ca
             INNER JOIN usuario u ON u.usuid = ca.usuid_registro
             WHERE ca.pleid = :period_id
             ORDER BY ca.cafecha DESC
             LIMIT 60"
        );
        $statement->execute(['period_id' => $periodId]);

        return $statement->fetchAll();
    }

    public function calendarDaysByRange(int $periodId, string $startDate, string $endDate): array
    {
        $statement = $this->db->prepare(
            "SELECT caid, pleid, cafecha, catipo_jornada, cahabilitado, cahora_limite, caobservacion
             FROM calendario_asistencia
             WHERE pleid = :period_id
               AND cafecha BETWEEN :start_date AND :end_date
             ORDER BY cafecha ASC"
        );
        $statement->execute([
            'period_id' => $periodId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $map = [];

        foreach ($statement->fetchAll() as $row) {
            $map[(string) $row['cafecha']] = $row;
        }

        return $map;
    }

    public function calendarDayByDate(int $periodId, string $date): array|false
    {
        $statement = $this->db->prepare(
            "SELECT caid, pleid, cafecha, catipo_jornada, cahabilitado, cahora_limite, caobservacion
             FROM calendario_asistencia
             WHERE pleid = :period_id
               AND cafecha = :class_date
             LIMIT 1"
        );
        $statement->execute([
            'period_id' => $periodId,
            'class_date' => $date,
        ]);

        return $statement->fetch();
    }

    public function calendarDetailsByDay(int $calendarId): array
    {
        $statement = $this->db->prepare(
            "SELECT cadid, caid, curid, cadnumero_hora, cadhabilitado, cadobservacion
             FROM calendario_asistencia_detalle
             WHERE caid = :calendar_id
             ORDER BY curid ASC, cadnumero_hora ASC"
        );
        $statement->execute(['calendar_id' => $calendarId]);

        $map = [];

        foreach ($statement->fetchAll() as $row) {
            $map[(int) $row['curid']][(int) $row['cadnumero_hora']] = $row;
        }

        return $map;
    }

    public function saveCalendarDay(
        int $periodId,
        string $date,
        string $type,
        ?int $hourLimit,
        string $note,
        int $userId,
        array $specialDetails
    ): int {
        $type = in_array($type, ['NORMAL', 'REDUCIDA', 'SUSPENDIDA', 'ESPECIAL'], true) ? $type : 'NORMAL';
        $enabled = $type !== 'SUSPENDIDA';
        $hourLimit = match ($type) {
            'NORMAL' => 7,
            'REDUCIDA' => $hourLimit !== null ? max(1, min(6, (int) $hourLimit)) : 6,
            'SUSPENDIDA' => null,
            default => $hourLimit !== null ? max(1, min(7, (int) $hourLimit)) : null,
        };

        $this->db->beginTransaction();

        try {
            $statement = $this->db->prepare(
                "INSERT INTO calendario_asistencia (
                    pleid, cafecha, catipo_jornada, cahabilitado, cahora_limite, caobservacion, usuid_registro
                 ) VALUES (
                    :period_id, :class_date, :type, :enabled, :hour_limit, :note, :user_id
                 )
                 ON CONFLICT (pleid, cafecha) DO UPDATE
                 SET catipo_jornada = EXCLUDED.catipo_jornada,
                     cahabilitado = EXCLUDED.cahabilitado,
                     cahora_limite = EXCLUDED.cahora_limite,
                     caobservacion = EXCLUDED.caobservacion,
                     cafecha_modificacion = CURRENT_TIMESTAMP
                 RETURNING caid"
            );
            $statement->bindValue(':period_id', $periodId, PDO::PARAM_INT);
            $statement->bindValue(':class_date', $date, PDO::PARAM_STR);
            $statement->bindValue(':type', $type, PDO::PARAM_STR);
            $statement->bindValue(':enabled', $enabled, PDO::PARAM_BOOL);
            $statement->bindValue(':hour_limit', $hourLimit, $hourLimit === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $statement->bindValue(':note', $note !== '' ? $note : null, $note !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $statement->execute();

            $calendarId = (int) $statement->fetchColumn();

            $delete = $this->db->prepare(
                "DELETE FROM calendario_asistencia_detalle
                 WHERE caid = :calendar_id"
            );
            $delete->execute(['calendar_id' => $calendarId]);

            if ($type === 'ESPECIAL' && $specialDetails !== []) {
                $insert = $this->db->prepare(
                    "INSERT INTO calendario_asistencia_detalle (
                        caid, curid, cadnumero_hora, cadhabilitado
                     ) VALUES (
                        :calendar_id, :course_id, :hour, true
                     )"
                );

                foreach ($specialDetails as $courseId => $hours) {
                    $courseId = (int) $courseId;

                    foreach ((array) $hours as $hour) {
                        $hour = (int) $hour;

                        if ($courseId <= 0 || $hour < 1 || $hour > 7) {
                            continue;
                        }

                        $insert->execute([
                            'calendar_id' => $calendarId,
                            'course_id' => $courseId,
                            'hour' => $hour,
                        ]);
                    }
                }
            }

            $this->db->commit();

            return $calendarId;
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    private function isIsoDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $date));

        return checkdate($month, $day, $year);
    }
}
