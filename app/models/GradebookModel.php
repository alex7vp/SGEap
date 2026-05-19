<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class GradebookModel extends Model
{
    public function coursesByPeriod(int $periodId): array
    {
        $statement = $this->db->prepare(
            "SELECT
                c.curid,
                c.pleid,
                c.graid,
                c.prlid,
                g.granombre,
                n.nednombre,
                p.prlnombre
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

    public function courseByPeriod(int $courseId, int $periodId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT
                c.curid,
                c.pleid,
                c.graid,
                c.prlid,
                g.granombre,
                n.nednombre,
                p.prlnombre
             FROM curso c
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN nivel_educativo n ON n.nedid = g.nedid
             INNER JOIN paralelo p ON p.prlid = c.prlid
             WHERE c.curid = :course_id
               AND c.pleid = :period_id
             LIMIT 1"
        );
        $statement->execute([
            'course_id' => $courseId,
            'period_id' => $periodId,
        ]);

        return $statement->fetch();
    }

    public function teacherSubjects(int $personId, int $periodId): array
    {
        $statement = $this->db->prepare(
            "SELECT
                mcd.mcdid,
                mcd.mtcid,
                v.curid,
                v.graid,
                cfg.nedid,
                v.granombre,
                v.prlnombre,
                v.areanombre,
                v.asgnombre,
                v.mtcnombre_mostrar,
                cfg.pcaid,
                cfg.pcanombre,
                cfg.pcatipo_base,
                p.pcaminima,
                p.pcamaxima,
                cfg.tipo_registro,
                cfg.tipo_visualizacion,
                cfg.promediable,
                cfg.visible_libreta,
                cfg.gmcid,
                cfg.gmcnombre,
                cfg.promedia_como_materia_individual,
                cfg.visible_como_materia_individual
             FROM materia_curso_docente mcd
             INNER JOIN vw_materia_curso v ON v.mtcid = mcd.mtcid
             LEFT JOIN vw_calificacion_materia_config_agrupada cfg
                ON cfg.mtcid = mcd.mtcid
                AND cfg.pleid = v.pleid
             LEFT JOIN perfil_calificacion p ON p.pcaid = cfg.pcaid
             WHERE mcd.perid = :person_id
               AND mcd.mcdestado = true
               AND v.pleid = :period_id
               AND v.mtcestado = true
             ORDER BY v.granombre ASC, v.prlnombre ASC, v.areanombre ASC, v.asgnombre ASC"
        );
        $statement->execute([
            'person_id' => $personId,
            'period_id' => $periodId,
        ]);

        return $statement->fetchAll();
    }

    public function selectedTeacherSubject(int $personId, int $periodId, int $courseSubjectId): array|false
    {
        foreach ($this->teacherSubjects($personId, $periodId) as $subject) {
            if ((int) $subject['mtcid'] === $courseSubjectId) {
                return $subject;
            }
        }

        return false;
    }

    public function subperiods(int $profileId): array
    {
        $statement = $this->db->prepare(
            "SELECT *
             FROM subperiodo_calificacion
             WHERE pcaid = :profile_id
             ORDER BY spcorden ASC"
        );
        $statement->execute(['profile_id' => $profileId]);

        return $statement->fetchAll();
    }

    public function components(int $profileId): array
    {
        $statement = $this->db->prepare(
            "SELECT c.*
             FROM componente_calificacion c
             INNER JOIN subperiodo_calificacion s ON s.spcid = c.spcid
             WHERE s.pcaid = :profile_id
               AND c.cpcestado = true
             ORDER BY s.spcorden ASC, c.cpcorden ASC"
        );
        $statement->execute(['profile_id' => $profileId]);
        $components = [];

        foreach ($statement->fetchAll() as $component) {
            $components[(int) $component['spcid']][] = $component;
        }

        return $components;
    }

    public function componentInSubperiod(int $profileId, int $subperiodId, int $componentId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT c.*
             FROM componente_calificacion c
             INNER JOIN subperiodo_calificacion s ON s.spcid = c.spcid
             WHERE c.cpcid = :component_id
               AND c.spcid = :subperiod_id
               AND s.pcaid = :profile_id
               AND c.cpcestado = true"
        );
        $statement->execute([
            'component_id' => $componentId,
            'subperiod_id' => $subperiodId,
            'profile_id' => $profileId,
        ]);

        return $statement->fetch();
    }

    public function subperiodByProfile(int $profileId, int $subperiodId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT *
             FROM subperiodo_calificacion
             WHERE pcaid = :profile_id
               AND spcid = :subperiod_id"
        );
        $statement->execute([
            'profile_id' => $profileId,
            'subperiod_id' => $subperiodId,
        ]);

        return $statement->fetch();
    }

    public function activitiesBySubperiod(int $courseSubjectId, int $subperiodId): array
    {
        $statement = $this->db->prepare(
            "SELECT
                a.aciid,
                a.cpcid,
                a.acinombre,
                a.acidescripcion,
                a.acifecha,
                a.acipeso,
                a.aciestado,
                c.cpcnombre,
                c.cpcorden
             FROM actividad_calificacion a
             INNER JOIN componente_calificacion c ON c.cpcid = a.cpcid
             WHERE a.mtcid = :course_subject_id
               AND c.spcid = :subperiod_id
               AND a.aciestado <> 'ANULADA'
             ORDER BY c.cpcorden ASC, a.aciid ASC"
        );
        $statement->execute([
            'course_subject_id' => $courseSubjectId,
            'subperiod_id' => $subperiodId,
        ]);
        $activities = [];

        foreach ($statement->fetchAll() as $activity) {
            $activities[(int) $activity['cpcid']][] = $activity;
        }

        return $activities;
    }

    public function activitiesByProfile(int $courseSubjectId, int $profileId): array
    {
        $statement = $this->db->prepare(
            "SELECT
                a.aciid,
                a.cpcid,
                a.acinombre,
                a.acidescripcion,
                a.acifecha,
                a.acipeso,
                a.aciestado,
                c.cpcnombre,
                c.cpcorden,
                s.spcid,
                s.spcnombre,
                s.spcorden
             FROM actividad_calificacion a
             INNER JOIN componente_calificacion c ON c.cpcid = a.cpcid
             INNER JOIN subperiodo_calificacion s ON s.spcid = c.spcid
             WHERE a.mtcid = :course_subject_id
               AND s.pcaid = :profile_id
               AND a.aciestado <> 'ANULADA'
             ORDER BY s.spcorden ASC, c.cpcorden ASC, a.aciid ASC"
        );
        $statement->execute([
            'course_subject_id' => $courseSubjectId,
            'profile_id' => $profileId,
        ]);
        $activities = [];

        foreach ($statement->fetchAll() as $activity) {
            $activities[(int) $activity['spcid']][(int) $activity['cpcid']][] = $activity;
        }

        return $activities;
    }

    public function gradesByActivities(array $activityIds): array
    {
        $activityIds = array_values(array_unique(array_filter(array_map('intval', $activityIds))));

        if ($activityIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($activityIds), '?'));
        $statement = $this->db->prepare(
            "SELECT
                aciid,
                matid,
                cesnota,
                ecaid,
                cesestado,
                cesobservacion
             FROM calificacion_estudiante
             WHERE aciid IN ($placeholders)"
        );
        $statement->execute($activityIds);
        $grades = [];

        foreach ($statement->fetchAll() as $grade) {
            $grades[(int) $grade['aciid']][(int) $grade['matid']] = $grade;
        }

        return $grades;
    }

    public function createActivity(int $courseSubjectId, int $componentId, string $name, string $date, int $userId): int
    {
        $statement = $this->db->prepare(
            "INSERT INTO actividad_calificacion (
                cpcid,
                mtcid,
                acinombre,
                acifecha,
                usuid_creacion
            ) VALUES (
                :component_id,
                :course_subject_id,
                :name,
                :activity_date,
                :user_id
            )
            RETURNING aciid"
        );
        $statement->execute([
            'component_id' => $componentId,
            'course_subject_id' => $courseSubjectId,
            'name' => $name,
            'activity_date' => $date,
            'user_id' => $userId,
        ]);

        return (int) $statement->fetchColumn();
    }

    public function saveGrades(
        array $submittedGrades,
        array $validActivityIds,
        array $validMatriculationIds,
        int $userId,
        float $minimum,
        float $maximum
    ): int
    {
        $validActivityIds = array_flip(array_map('intval', $validActivityIds));
        $validMatriculationIds = array_flip(array_map('intval', $validMatriculationIds));
        $saved = 0;

        $statement = $this->db->prepare(
            "INSERT INTO calificacion_estudiante (
                aciid,
                matid,
                cesnota,
                cesestado,
                usuid_registro
            ) VALUES (
                :activity_id,
                :matriculation_id,
                :grade,
                'REGISTRADA',
                :user_id
            )
            ON CONFLICT (aciid, matid) DO UPDATE
            SET cesnota = EXCLUDED.cesnota,
                cesestado = 'CORREGIDA',
                usuid_modificacion = EXCLUDED.usuid_registro,
                cesfecha_modificacion = CURRENT_TIMESTAMP"
        );

        $this->db->beginTransaction();

        try {
            foreach ($submittedGrades as $activityId => $studentGrades) {
                $activityId = (int) $activityId;

                if (!isset($validActivityIds[$activityId]) || !is_array($studentGrades)) {
                    continue;
                }

                foreach ($studentGrades as $matriculationId => $grade) {
                    $matriculationId = (int) $matriculationId;
                    $grade = str_replace(',', '.', trim((string) $grade));

                    if (!isset($validMatriculationIds[$matriculationId]) || $grade === '') {
                        continue;
                    }

                    if (!is_numeric($grade)) {
                        continue;
                    }

                    $gradeValue = round((float) $grade, 2);

                    if ($gradeValue < $minimum || $gradeValue > $maximum) {
                        throw new \InvalidArgumentException(
                            'Las notas deben estar entre ' . number_format($minimum, 2, '.', '') . ' y ' . number_format($maximum, 2, '.', '') . '.'
                        );
                    }

                    $statement->execute([
                        'activity_id' => $activityId,
                        'matriculation_id' => $matriculationId,
                        'grade' => number_format($gradeValue, 2, '.', ''),
                        'user_id' => $userId,
                    ]);
                    $saved++;
                }
            }

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }

        return $saved;
    }

    public function studentsByCourse(int $courseId): array
    {
        $statement = $this->db->prepare(
            "SELECT
                m.matid,
                e.estid,
                p.percedula,
                p.perapellidos,
                p.pernombres
             FROM matricula m
             INNER JOIN estudiante e ON e.estid = m.estid
             INNER JOIN persona p ON p.perid = e.perid
             INNER JOIN estado_matricula em ON em.emdid = m.emdid
             WHERE m.curid = :course_id
               AND em.emdnombre = 'Activo'
             ORDER BY p.perapellidos ASC, p.pernombres ASC"
        );
        $statement->execute(['course_id' => $courseId]);

        return $statement->fetchAll();
    }

    public function finalReportSubjectDefinitions(int $courseId, int $periodId): array
    {
        $statement = $this->db->prepare(
            "SELECT
                v.mtcid,
                v.mtcorden,
                v.areaid,
                v.areanombre,
                v.asgnombre,
                cfg.pcaid,
                p.pcaaprobacion,
                cfg.promediable,
                cfg.visible_libreta,
                cfg.gmcid,
                cfg.gmcnombre,
                cfg.gmcmodo_calculo,
                cfg.grupo_promediable,
                cfg.grupo_visible_libreta,
                cfg.grupo_orden,
                cfg.grupo_materia_peso,
                cfg.grupo_materia_orden,
                cfg.gmcdincluye_calculo,
                cfg.visible_como_materia_individual,
                cfg.promedia_como_materia_individual
             FROM vw_materia_curso v
             LEFT JOIN vw_calificacion_materia_config_agrupada cfg
                ON cfg.mtcid = v.mtcid
                AND cfg.pleid = v.pleid
             LEFT JOIN perfil_calificacion p ON p.pcaid = cfg.pcaid
             WHERE v.curid = :course_id
               AND v.pleid = :period_id
               AND v.mtcestado = true
             ORDER BY COALESCE(cfg.grupo_orden, v.mtcorden, 9999) ASC,
                      COALESCE(cfg.grupo_materia_orden, v.mtcorden, 9999) ASC,
                      v.areanombre ASC,
                      v.asgnombre ASC"
        );
        $statement->execute([
            'course_id' => $courseId,
            'period_id' => $periodId,
        ]);

        $definitions = [];
        $groups = [];

        foreach ($statement->fetchAll() as $row) {
            if (!empty($row['gmcid']) && !empty($row['grupo_visible_libreta'])) {
                $groupId = (int) $row['gmcid'];

                if (!isset($groups[$groupId])) {
                    $groups[$groupId] = [
                        'key' => 'group-' . $groupId,
                        'name' => (string) $row['gmcnombre'],
                        'promediable' => $this->truthy($row['grupo_promediable'] ?? true),
                        'approval' => is_numeric($row['pcaaprobacion'] ?? null) ? (float) $row['pcaaprobacion'] : 7.0,
                        'items' => [],
                        'order' => (int) ($row['grupo_orden'] ?? $row['mtcorden'] ?? 9999),
                    ];
                }

                if ($this->truthy($row['gmcdincluye_calculo'] ?? true)) {
                    $groups[$groupId]['items'][] = [
                        'mtcid' => (int) $row['mtcid'],
                        'pcaid' => (int) $row['pcaid'],
                    ];
                }

                if (!$this->truthy($row['visible_como_materia_individual'] ?? false)) {
                    continue;
                }
            }

            if (!$this->truthy($row['visible_libreta'] ?? true) || empty($row['pcaid'])) {
                continue;
            }

            $definitions[] = [
                'key' => 'subject-' . (int) $row['mtcid'],
                'name' => (string) $row['asgnombre'],
                'promediable' => $this->truthy($row['promedia_como_materia_individual'] ?? $row['promediable'] ?? true),
                'approval' => is_numeric($row['pcaaprobacion'] ?? null) ? (float) $row['pcaaprobacion'] : 7.0,
                'items' => [[
                    'mtcid' => (int) $row['mtcid'],
                    'pcaid' => (int) $row['pcaid'],
                ]],
                'order' => (int) ($row['mtcorden'] ?? 9999),
            ];
        }

        foreach ($groups as $group) {
            if ($group['items'] !== []) {
                $definitions[] = $group;
            }
        }

        usort($definitions, static fn (array $a, array $b): int => ($a['order'] <=> $b['order']) ?: strcmp($a['name'], $b['name']));

        return $definitions;
    }

    private function truthy(mixed $value): bool
    {
        return !in_array(strtolower((string) $value), ['0', 'false', 'f', 'no', ''], true);
    }
}
