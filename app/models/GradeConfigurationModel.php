<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use PDO;
use RuntimeException;

class GradeConfigurationModel extends Model
{
    public function templates(): array
    {
        $statement = $this->db->query(
            "SELECT
                p.pclid,
                p.pclnombre,
                p.pcldescripcion,
                p.pcltipo_base,
                p.pclminima,
                p.pclmaxima,
                p.pclaprobacion,
                p.pclpromedia_final,
                p.pclaplica_promocion,
                COUNT(DISTINCT s.psuid) AS total_subperiodos,
                COUNT(DISTINCT c.pcoid) AS total_componentes,
                COUNT(DISTINCT e.pecid) AS total_escalas
             FROM plantilla_calificacion p
             LEFT JOIN plantilla_subperiodo s ON s.pclid = p.pclid
             LEFT JOIN plantilla_componente c ON c.psuid = s.psuid
             LEFT JOIN plantilla_escala_cualitativa e ON e.pclid = p.pclid
             WHERE p.pclestado = true
             GROUP BY p.pclid
             ORDER BY p.pclnombre ASC"
        );

        return $statement->fetchAll();
    }

    public function templateDetail(int $templateId): array
    {
        $template = $this->findTemplate($templateId);

        $subperiods = $this->templateRows(
            "SELECT *
             FROM plantilla_subperiodo
             WHERE pclid = :template_id
             ORDER BY psuorden ASC",
            $templateId
        );
        $components = [];

        if ($subperiods !== []) {
            $statement = $this->db->prepare(
                "SELECT c.*
                 FROM plantilla_componente c
                 INNER JOIN plantilla_subperiodo s ON s.psuid = c.psuid
                 WHERE s.pclid = :template_id
                 ORDER BY s.psuorden ASC, c.pcoorden ASC"
            );
            $statement->execute(['template_id' => $templateId]);

            foreach ($statement->fetchAll() as $component) {
                $components[(int) $component['psuid']][] = $component;
            }
        }

        $scales = $this->templateRows(
            "SELECT *
             FROM plantilla_escala_cualitativa
             WHERE pclid = :template_id
             ORDER BY pecorden ASC",
            $templateId
        );
        $ambits = $this->templateRows(
            "SELECT *
             FROM plantilla_ambito
             WHERE pclid = :template_id
             ORDER BY pamborden ASC",
            $templateId
        );
        $skills = [];

        if ($ambits !== []) {
            $statement = $this->db->prepare(
                "SELECT d.*
                 FROM plantilla_destreza d
                 INNER JOIN plantilla_ambito a ON a.pambid = d.pambid
                 WHERE a.pclid = :template_id
                 ORDER BY a.pamborden ASC, d.pdesorden ASC"
            );
            $statement->execute(['template_id' => $templateId]);

            foreach ($statement->fetchAll() as $skill) {
                $skills[(int) $skill['pambid']][] = $skill;
            }
        }

        return [
            'template' => $template,
            'subperiods' => $subperiods,
            'components' => $components,
            'scales' => $scales,
            'ambits' => $ambits,
            'skills' => $skills,
            'promotionTramos' => $this->templateRows(
                "SELECT *
                 FROM plantilla_promocion_tramo
                 WHERE pclid = :template_id
                 ORDER BY pptorden ASC",
                $templateId
            ),
            'extraordinaryInstances' => $this->templateRows(
                "SELECT *
                 FROM plantilla_instancia_extraordinaria
                 WHERE pclid = :template_id
                 ORDER BY pieorden ASC",
                $templateId
            ),
        ];
    }

    public function profiles(): array
    {
        $statement = $this->db->query(
            "SELECT
                p.pcaid,
                p.pleid,
                pl.pledescripcion,
                p.pcanombre,
                p.pcaversion,
                p.pcaestado,
                p.pcatipo_base,
                p.pcavigencia_desde,
                p.pcavigencia_hasta,
                COUNT(DISTINCT s.spcid) AS total_subperiodos,
                COUNT(DISTINCT a.pasid) AS total_asignaciones
             FROM perfil_calificacion p
             INNER JOIN periodo_lectivo pl ON pl.pleid = p.pleid
             LEFT JOIN subperiodo_calificacion s ON s.pcaid = p.pcaid
             LEFT JOIN perfil_calificacion_asignacion a ON a.pcaid = p.pcaid AND a.pasestado = true
             GROUP BY p.pcaid, pl.pledescripcion, pl.plefechainicio
             ORDER BY pl.plefechainicio DESC, p.pcanombre ASC, p.pcaversion DESC"
        );

        return $statement->fetchAll();
    }

    public function profileDetail(int $profileId): array
    {
        $profile = $this->findProfile($profileId);
        $subperiods = $this->profileRows(
            "SELECT *
             FROM subperiodo_calificacion
             WHERE pcaid = :profile_id
             ORDER BY spcorden ASC",
            $profileId
        );
        $components = [];

        if ($subperiods !== []) {
            $statement = $this->db->prepare(
                "SELECT c.*
                 FROM componente_calificacion c
                 INNER JOIN subperiodo_calificacion s ON s.spcid = c.spcid
                 WHERE s.pcaid = :profile_id
                 ORDER BY s.spcorden ASC, c.cpcorden ASC"
            );
            $statement->execute(['profile_id' => $profileId]);

            foreach ($statement->fetchAll() as $component) {
                $components[(int) $component['spcid']][] = $component;
            }
        }

        return [
            'profile' => $profile,
            'subperiods' => $subperiods,
            'components' => $components,
            'scales' => $this->profileRows(
                "SELECT *
                 FROM escala_cualitativa
                 WHERE pcaid = :profile_id
                 ORDER BY ecaorden ASC",
                $profileId
            ),
            'ambits' => $this->profileRows(
                "SELECT *
                 FROM ambito_calificacion
                 WHERE pcaid = :profile_id
                 ORDER BY amborden ASC",
                $profileId
            ),
            'assignments' => $this->profileAssignments($profileId),
            'subjectConfigurations' => $this->profileSubjectConfigurations($profileId),
            'subjectGroups' => $this->profileSubjectGroups($profileId),
            'promotionTramos' => $this->profilePromotionTramos($profileId),
            'extraordinaryInstances' => $this->profileRows(
                "SELECT *
                 FROM instancia_extraordinaria
                 WHERE pcaid = :profile_id
                 ORDER BY iexorden ASC",
                $profileId
            ),
        ];
    }

    public function levels(): array
    {
        $statement = $this->db->query(
            "SELECT nedid, nednombre
             FROM nivel_educativo
             ORDER BY nednombre ASC"
        );

        return $statement->fetchAll();
    }

    public function grades(): array
    {
        $statement = $this->db->query(
            "SELECT g.graid, g.granombre, n.nednombre
             FROM grado g
             INNER JOIN nivel_educativo n ON n.nedid = g.nedid
             ORDER BY n.nednombre ASC, g.granombre ASC"
        );

        return $statement->fetchAll();
    }

    public function courses(): array
    {
        $statement = $this->db->query(
            "SELECT c.curid, c.pleid, pl.pledescripcion, g.granombre, n.nednombre, pr.prlnombre
             FROM curso c
             INNER JOIN periodo_lectivo pl ON pl.pleid = c.pleid
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN nivel_educativo n ON n.nedid = g.nedid
             INNER JOIN paralelo pr ON pr.prlid = c.prlid
             WHERE c.curestado = true
             ORDER BY pl.plefechainicio DESC, n.nednombre ASC, g.granombre ASC, pr.prlnombre ASC"
        );

        return $statement->fetchAll();
    }

    public function courseSubjects(): array
    {
        $statement = $this->db->query(
            "SELECT
                mc.mtcid,
                c.pleid,
                pl.pledescripcion,
                g.granombre,
                pr.prlnombre,
                aa.areanombre,
                a.asgnombre
             FROM materia_curso mc
             INNER JOIN curso c ON c.curid = mc.curid
             INNER JOIN periodo_lectivo pl ON pl.pleid = c.pleid
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN paralelo pr ON pr.prlid = c.prlid
             INNER JOIN asignatura a ON a.asgid = mc.asgid
             INNER JOIN area_academica aa ON aa.areaid = a.areaid
             WHERE mc.mtcestado = true
             ORDER BY pl.plefechainicio DESC, g.granombre ASC, pr.prlnombre ASC, aa.areanombre ASC, a.asgnombre ASC"
        );

        return $statement->fetchAll();
    }

    public function createProfileFromTemplate(array $data, int $userId): int
    {
        $templateId = (int) ($data['pclid'] ?? 0);
        $periodId = (int) ($data['pleid'] ?? 0);
        $profileName = trim((string) ($data['pcanombre'] ?? ''));
        $state = trim((string) ($data['pcaestado'] ?? 'BORRADOR'));
        $validFrom = trim((string) ($data['pcavigencia_desde'] ?? ''));
        $validTo = trim((string) ($data['pcavigencia_hasta'] ?? ''));
        $scope = trim((string) ($data['pasalcance'] ?? ''));
        $targetId = (int) ($data['target_id'] ?? 0);

        if ($templateId <= 0 || $periodId <= 0 || $profileName === '' || $validFrom === '') {
            throw new InvalidArgumentException('Plantilla, periodo, nombre y vigencia desde son obligatorios.');
        }

        if (!in_array($state, ['BORRADOR', 'ACTIVA'], true)) {
            throw new InvalidArgumentException('El estado inicial del perfil no es valido.');
        }

        if ($validTo !== '' && $validTo < $validFrom) {
            throw new InvalidArgumentException('La vigencia hasta no puede ser menor que la vigencia desde.');
        }

        if ($scope !== '' && !in_array($scope, ['NIVEL', 'GRADO', 'CURSO', 'MATERIA'], true)) {
            throw new InvalidArgumentException('El alcance seleccionado no es valido.');
        }

        if ($scope !== '' && $targetId <= 0) {
            throw new InvalidArgumentException('Seleccione el destino de la asignacion del perfil.');
        }

        $template = $this->findTemplate($templateId);
        $period = $this->findPeriod($periodId);

        if ($this->profileNameExists($periodId, $profileName)) {
            throw new RuntimeException('Ya existe un perfil con ese nombre para el periodo seleccionado.');
        }

        $this->db->beginTransaction();

        try {
            $profileId = $this->insertProfile($template, $periodId, $profileName, $state, $validFrom, $validTo, $userId);
            $subperiodMap = $this->copySubperiods($templateId, $profileId, (string) $period['plefechainicio'], (string) $period['plefechafin']);
            $this->copyComponents($subperiodMap);
            $this->copyQualitativeScale($templateId, $profileId);
            $ambitMap = $this->copyAmbits($templateId, $profileId);
            $this->copySkills($ambitMap);
            $this->copyPromotion($templateId, $profileId);

            if ($scope !== '') {
                $this->assertScopeTargetBelongsToPeriod($scope, $targetId, $periodId);
                $this->insertAssignment($profileId, $periodId, $scope, $targetId, $userId);
            }

            $this->audit($userId, 'CONFIGURACION_COPIADA', 'perfil_calificacion', $profileId, null, json_encode([
                'plantilla' => $template['pclnombre'] ?? '',
                'periodo' => $period['pledescripcion'] ?? '',
                'alcance' => $scope,
                'target_id' => $targetId,
            ], JSON_UNESCAPED_UNICODE));

            $this->db->commit();

            return $profileId;
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function updateDraftProfileSchedule(int $profileId, array $subperiods, array $components, array $newSubperiods, array $newComponents, int $userId): void
    {
        $profile = $this->findProfile($profileId);

        if (($profile['pcaestado'] ?? '') !== 'BORRADOR') {
            throw new RuntimeException('Solo se puede editar un perfil en estado BORRADOR.');
        }

        $this->db->beginTransaction();

        try {
            $deleteSubperiodStatement = $this->db->prepare(
                "DELETE FROM componente_calificacion c
                 USING subperiodo_calificacion s
                 WHERE s.spcid = c.spcid
                   AND s.pcaid = :profile_id
                   AND s.spcid = :subperiod_id"
            );
            $deleteSubperiodHeaderStatement = $this->db->prepare(
                "DELETE FROM subperiodo_calificacion
                 WHERE pcaid = :profile_id
                   AND spcid = :subperiod_id"
            );
            $subperiodStatement = $this->db->prepare(
                "UPDATE subperiodo_calificacion
                 SET spcnombre = :name,
                     spcfecha_inicio = :start_date,
                     spcfecha_fin = :end_date,
                     spcparticipa_final = :participates,
                     spcpeso_final = :weight,
                     spcfecha_modificacion = CURRENT_TIMESTAMP
                 WHERE spcid = :subperiod_id
                   AND pcaid = :profile_id"
            );

            foreach ($subperiods as $subperiodId => $data) {
                if (!empty($data['delete'])) {
                    $deleteSubperiodStatement->execute([
                        'profile_id' => $profileId,
                        'subperiod_id' => (int) $subperiodId,
                    ]);
                    $deleteSubperiodHeaderStatement->execute([
                        'profile_id' => $profileId,
                        'subperiod_id' => (int) $subperiodId,
                    ]);
                    continue;
                }

                $name = trim((string) ($data['spcnombre'] ?? ''));
                $start = trim((string) ($data['spcfecha_inicio'] ?? ''));
                $end = trim((string) ($data['spcfecha_fin'] ?? ''));

                if ($name === '') {
                    throw new InvalidArgumentException('El nombre del subperiodo es obligatorio.');
                }

                if ($start === '' || $end === '') {
                    throw new InvalidArgumentException('Las fechas de subperiodo son obligatorias.');
                }

                if ($end < $start) {
                    throw new InvalidArgumentException('La fecha fin de un subperiodo no puede ser menor que la fecha inicio.');
                }

                $weight = trim((string) ($data['spcpeso_final'] ?? ''));
                $subperiodStatement->execute([
                    'subperiod_id' => (int) $subperiodId,
                    'profile_id' => $profileId,
                    'name' => $name,
                    'start_date' => $start,
                    'end_date' => $end,
                    'participates' => !empty($data['spcparticipa_final']) ? 'true' : 'false',
                    'weight' => $weight !== '' ? $weight : null,
                ]);
            }

            $insertSubperiodStatement = $this->db->prepare(
                "INSERT INTO subperiodo_calificacion (
                    pcaid,
                    spcnombre,
                    spcorden,
                    spcfecha_inicio,
                    spcfecha_fin,
                    spcestado,
                    spcparticipa_final,
                    spcpeso_final
                 )
                 SELECT
                    :profile_id,
                    :name,
                    COALESCE(MAX(spcorden), 0) + 1,
                    :start_date,
                    :end_date,
                    'EN_REGISTRO',
                    :participates,
                    :weight
                 FROM subperiodo_calificacion
                 WHERE pcaid = :profile_id_filter"
            );

            foreach ($newSubperiods as $data) {
                if (!is_array($data)) {
                    continue;
                }

                $name = trim((string) ($data['spcnombre'] ?? ''));
                $start = trim((string) ($data['spcfecha_inicio'] ?? ''));
                $end = trim((string) ($data['spcfecha_fin'] ?? ''));

                if ($name === '' && $start === '' && $end === '') {
                    continue;
                }

                if ($name === '') {
                    throw new InvalidArgumentException('El nombre del subperiodo es obligatorio.');
                }

                if ($start === '' || $end === '') {
                    throw new InvalidArgumentException('Las fechas de subperiodo son obligatorias.');
                }

                if ($end < $start) {
                    throw new InvalidArgumentException('La fecha fin de un subperiodo no puede ser menor que la fecha inicio.');
                }

                $weight = trim((string) ($data['spcpeso_final'] ?? ''));
                $insertSubperiodStatement->execute([
                    'profile_id' => $profileId,
                    'profile_id_filter' => $profileId,
                    'name' => $name,
                    'start_date' => $start,
                    'end_date' => $end,
                    'participates' => !empty($data['spcparticipa_final']) ? 'true' : 'false',
                    'weight' => $weight !== '' ? $weight : null,
                ]);
            }

            $componentStatement = $this->db->prepare(
                "UPDATE componente_calificacion c
                 SET cpcnombre = :name,
                     cpcpeso = :weight,
                     cpctipo_calculo = :calculation_type,
                     cpcestado = :status,
                     cpcfecha_modificacion = CURRENT_TIMESTAMP
                 FROM subperiodo_calificacion s
                 WHERE s.spcid = c.spcid
                   AND s.pcaid = :profile_id
                   AND c.cpcid = :component_id"
            );
            $deleteComponentStatement = $this->db->prepare(
                "DELETE FROM componente_calificacion c
                 USING subperiodo_calificacion s
                 WHERE s.spcid = c.spcid
                   AND s.pcaid = :profile_id
                   AND c.cpcid = :component_id"
            );

            foreach ($components as $componentId => $data) {
                if (!empty($data['delete'])) {
                    $deleteComponentStatement->execute([
                        'component_id' => (int) $componentId,
                        'profile_id' => $profileId,
                    ]);
                    continue;
                }

                $name = trim((string) ($data['cpcnombre'] ?? ''));
                $weight = trim((string) ($data['cpcpeso'] ?? ''));
                $type = trim((string) ($data['cpctipo_calculo'] ?? 'PROMEDIO_SIMPLE'));

                if ($name === '') {
                    throw new InvalidArgumentException('El nombre del componente es obligatorio.');
                }

                if (!in_array($type, ['PROMEDIO_SIMPLE', 'PROMEDIO_PONDERADO', 'SUMA'], true)) {
                    throw new InvalidArgumentException('El tipo de calculo de componente no es valido.');
                }

                $componentStatement->execute([
                    'component_id' => (int) $componentId,
                    'profile_id' => $profileId,
                    'name' => $name,
                    'weight' => $weight !== '' ? $weight : null,
                    'calculation_type' => $type,
                    'status' => !empty($data['cpcestado']) ? 'true' : 'false',
                ]);
            }

            $insertComponentStatement = $this->db->prepare(
                "INSERT INTO componente_calificacion (
                    spcid,
                    cpcnombre,
                    cpcorden,
                    cpcpeso,
                    cpctipo_calculo,
                    cpcestado
                 )
                 SELECT
                    s.spcid,
                    :name,
                    COALESCE(MAX(c.cpcorden), 0) + 1,
                    :weight,
                    :calculation_type,
                    true
                 FROM subperiodo_calificacion s
                 LEFT JOIN componente_calificacion c ON c.spcid = s.spcid
                 WHERE s.spcid = :subperiod_id
                   AND s.pcaid = :profile_id
                 GROUP BY s.spcid"
            );

            foreach ($newComponents as $subperiodId => $rows) {
                if (!is_array($rows)) {
                    continue;
                }

                foreach ($rows as $data) {
                    $name = trim((string) ($data['cpcnombre'] ?? ''));

                    if ($name === '') {
                        continue;
                    }

                    $weight = trim((string) ($data['cpcpeso'] ?? ''));
                    $type = trim((string) ($data['cpctipo_calculo'] ?? 'PROMEDIO_SIMPLE'));

                    if (!in_array($type, ['PROMEDIO_SIMPLE', 'PROMEDIO_PONDERADO', 'SUMA'], true)) {
                        throw new InvalidArgumentException('El tipo de calculo de componente no es valido.');
                    }

                    $insertComponentStatement->execute([
                        'subperiod_id' => (int) $subperiodId,
                        'profile_id' => $profileId,
                        'name' => $name,
                        'weight' => $weight !== '' ? $weight : null,
                        'calculation_type' => $type,
                    ]);

                    if ($insertComponentStatement->rowCount() === 0) {
                        throw new InvalidArgumentException('El subperiodo seleccionado para agregar componente no es valido.');
                    }
                }
            }

            $this->audit($userId, 'CONFIGURACION_EDITADA', 'perfil_calificacion', $profileId, null, 'Subperiodos y componentes actualizados');
            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function activateProfile(int $profileId, int $userId): void
    {
        $profile = $this->findProfile($profileId);

        if (!in_array((string) ($profile['pcaestado'] ?? ''), ['BORRADOR', 'EN_REVISION'], true)) {
            throw new RuntimeException('Solo se puede activar un perfil en BORRADOR o EN_REVISION.');
        }

        $statement = $this->db->prepare(
            "UPDATE perfil_calificacion
             SET pcaestado = 'ACTIVA',
                 usuid_autorizacion = :user_id,
                 pcafecha_modificacion = CURRENT_TIMESTAMP
             WHERE pcaid = :profile_id"
        );
        $statement->execute([
            'profile_id' => $profileId,
            'user_id' => $userId,
        ]);

        $this->audit($userId, 'CONFIGURACION_ACTIVADA', 'perfil_calificacion', $profileId, (string) $profile['pcaestado'], 'ACTIVA');
    }

    public function updateDraftProfileAssignments(int $profileId, array $assignments, array $newAssignments, int $userId): void
    {
        $profile = $this->editableProfile($profileId);
        $this->db->beginTransaction();

        try {
            $update = $this->db->prepare(
                "UPDATE perfil_calificacion_asignacion
                 SET pasprioridad = :priority,
                     pasestado = :status,
                     pasfecha_modificacion = CURRENT_TIMESTAMP
                 WHERE pasid = :assignment_id
                   AND pcaid = :profile_id"
            );

            foreach ($assignments as $assignmentId => $data) {
                $priority = max(1, (int) ($data['pasprioridad'] ?? 1));
                $update->execute([
                    'assignment_id' => (int) $assignmentId,
                    'profile_id' => $profileId,
                    'priority' => $priority,
                    'status' => empty($data['delete']) ? 'true' : 'false',
                ]);
            }

            foreach ($newAssignments as $data) {
                if (!is_array($data)) {
                    continue;
                }

                $scope = trim((string) ($data['pasalcance'] ?? ''));
                $targetId = (int) ($data['target_id'] ?? 0);

                if ($scope === '' && $targetId <= 0) {
                    continue;
                }

                if (!in_array($scope, ['NIVEL', 'GRADO', 'CURSO', 'MATERIA'], true)) {
                    throw new InvalidArgumentException('El alcance de asignacion no es valido.');
                }

                if ($targetId <= 0) {
                    throw new InvalidArgumentException('Seleccione el destino de la asignacion.');
                }

                $this->assertScopeTargetBelongsToPeriod($scope, $targetId, (int) $profile['pleid']);
                $this->insertAssignment(
                    $profileId,
                    (int) $profile['pleid'],
                    $scope,
                    $targetId,
                    $userId,
                    max(1, (int) ($data['pasprioridad'] ?? 1))
                );
            }

            $this->audit($userId, 'ASIGNACIONES_EDITADAS', 'perfil_calificacion', $profileId, null, 'Asignaciones actualizadas');
            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function updateDraftProfileScale(int $profileId, array $scales, array $newScales, int $userId): void
    {
        $this->editableProfile($profileId);
        $this->db->beginTransaction();

        try {
            $delete = $this->db->prepare(
                "DELETE FROM escala_cualitativa
                 WHERE ecaid = :scale_id
                   AND pcaid = :profile_id"
            );
            $update = $this->db->prepare(
                "UPDATE escala_cualitativa
                 SET ecacodigo = :code,
                     ecanombre = :name,
                     ecadescripcion = :description,
                     ecavalor_minimo = :minimum,
                     ecavalor_maximo = :maximum,
                     ecaorden = :order_number,
                     ecaestado = :status
                 WHERE ecaid = :scale_id
                   AND pcaid = :profile_id"
            );
            $temporaryOrder = $this->db->prepare(
                "UPDATE escala_cualitativa
                 SET ecaorden = -ecaid
                 WHERE ecaid = :scale_id
                   AND pcaid = :profile_id"
            );

            foreach ($scales as $scaleId => $data) {
                if (!empty($data['delete'])) {
                    $delete->execute([
                        'scale_id' => (int) $scaleId,
                        'profile_id' => $profileId,
                    ]);
                    continue;
                }

                $temporaryOrder->execute([
                    'scale_id' => (int) $scaleId,
                    'profile_id' => $profileId,
                ]);
            }

            foreach ($scales as $scaleId => $data) {
                if (!empty($data['delete'])) {
                    continue;
                }

                $scale = $this->scalePayload($data);
                $update->execute($scale + [
                    'scale_id' => (int) $scaleId,
                    'profile_id' => $profileId,
                ]);
            }

            $insert = $this->db->prepare(
                "INSERT INTO escala_cualitativa (
                    pcaid,
                    ecacodigo,
                    ecanombre,
                    ecadescripcion,
                    ecavalor_minimo,
                    ecavalor_maximo,
                    ecaorden,
                    ecaestado
                 ) VALUES (
                    :profile_id,
                    :code,
                    :name,
                    :description,
                    :minimum,
                    :maximum,
                    :order_number,
                    :status
                 )"
            );

            foreach ($newScales as $data) {
                if (!is_array($data) || trim((string) ($data['ecacodigo'] ?? '')) === '') {
                    continue;
                }

                $insert->execute($this->scalePayload($data) + ['profile_id' => $profileId]);
            }

            $this->audit($userId, 'ESCALA_EDITADA', 'perfil_calificacion', $profileId, null, 'Escala cualitativa actualizada');
            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function updateDraftProfilePromotion(int $profileId, array $tramos, array $newTramos, array $instances, array $newInstances, int $userId): void
    {
        $this->editableProfile($profileId);
        $this->db->beginTransaction();

        try {
            $ruleId = $this->promotionRuleId($profileId);
            $deleteTramo = $this->db->prepare(
                "DELETE FROM regla_promocion_tramo
                 WHERE rptid = :tramo_id
                   AND rprid = :rule_id"
            );
            $updateTramo = $this->db->prepare(
                "UPDATE regla_promocion_tramo
                 SET rptorden = :order_number,
                     rptnota_minima = :minimum,
                     rptnota_maxima = :maximum,
                     rptresultado = :result,
                     rpthabilita_extraordinaria = :extraordinary,
                     rptestado = :status
                 WHERE rptid = :tramo_id
                   AND rprid = :rule_id"
            );
            $temporaryTramoOrder = $this->db->prepare(
                "UPDATE regla_promocion_tramo
                 SET rptorden = -rptid
                 WHERE rptid = :tramo_id
                   AND rprid = :rule_id"
            );

            foreach ($tramos as $tramoId => $data) {
                if (!empty($data['delete'])) {
                    $deleteTramo->execute([
                        'tramo_id' => (int) $tramoId,
                        'rule_id' => $ruleId,
                    ]);
                    continue;
                }

                $temporaryTramoOrder->execute([
                    'tramo_id' => (int) $tramoId,
                    'rule_id' => $ruleId,
                ]);
            }

            foreach ($tramos as $tramoId => $data) {
                if (!empty($data['delete'])) {
                    continue;
                }

                $updateTramo->execute($this->promotionTramoPayload($data) + [
                    'tramo_id' => (int) $tramoId,
                    'rule_id' => $ruleId,
                ]);
            }

            $insertTramo = $this->db->prepare(
                "INSERT INTO regla_promocion_tramo (
                    rprid,
                    rptorden,
                    rptnota_minima,
                    rptnota_maxima,
                    rptresultado,
                    rpthabilita_extraordinaria,
                    rptestado
                 ) VALUES (
                    :rule_id,
                    :order_number,
                    :minimum,
                    :maximum,
                    :result,
                    :extraordinary,
                    :status
                 )"
            );

            foreach ($newTramos as $data) {
                if (!is_array($data) || trim((string) ($data['rptresultado'] ?? '')) === '') {
                    continue;
                }

                $insertTramo->execute($this->promotionTramoPayload($data) + ['rule_id' => $ruleId]);
            }

            $this->updateDraftExtraordinaryInstances($profileId, $instances, $newInstances);

            $this->audit($userId, 'PROMOCION_EDITADA', 'perfil_calificacion', $profileId, null, 'Promocion actualizada');
            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function updateDraftProfileSubjectConfigurations(int $profileId, array $subjects, int $userId): void
    {
        $this->editableProfile($profileId);
        $allowedSubjects = array_fill_keys(array_map(
            static fn (array $row): int => (int) $row['mtcid'],
            $this->profileSubjectConfigurations($profileId)
        ), true);
        $allowedAreas = array_fill_keys(array_map(
            static fn (array $row): int => (int) $row['areaid'],
            $this->profileSubjectConfigurations($profileId)
        ), true);

        if ($allowedSubjects === []) {
            throw new RuntimeException('El perfil no tiene materias aplicables. Configure primero las asignaciones.');
        }

        $this->db->beginTransaction();

        try {
            $statement = $this->db->prepare(
                "INSERT INTO materia_calificacion_config (
                    mtcid,
                    pcaid,
                    mcctipo_registro,
                    mcctipo_visualizacion,
                    mccpromediable,
                    mccvisible_libreta,
                    mccusa_equivalencia,
                    mccestado,
                    mccobservacion
                 ) VALUES (
                    :course_subject_id,
                    :profile_id,
                    :record_type,
                    :display_type,
                    :averages,
                    :report_visible,
                    :uses_equivalence,
                    :status,
                    :observation
                 )
                 ON CONFLICT (mtcid, pcaid) DO UPDATE
                 SET mcctipo_registro = EXCLUDED.mcctipo_registro,
                     mcctipo_visualizacion = EXCLUDED.mcctipo_visualizacion,
                     mccpromediable = EXCLUDED.mccpromediable,
                     mccvisible_libreta = EXCLUDED.mccvisible_libreta,
                     mccusa_equivalencia = EXCLUDED.mccusa_equivalencia,
                     mccestado = EXCLUDED.mccestado,
                     mccobservacion = EXCLUDED.mccobservacion,
                     mccfecha_modificacion = CURRENT_TIMESTAMP"
            );

            foreach ($subjects as $courseSubjectId => $data) {
                $courseSubjectId = (int) $courseSubjectId;

                if (!isset($allowedSubjects[$courseSubjectId]) || !is_array($data)) {
                    continue;
                }

                $payload = $this->subjectConfigurationPayload($data);
                $statement->execute($payload + [
                    'course_subject_id' => $courseSubjectId,
                    'profile_id' => $profileId,
                ]);
            }

            $this->audit($userId, 'MATERIAS_EDITADAS', 'perfil_calificacion', $profileId, null, 'Configuracion de materias actualizada');
            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function updateDraftProfileSubjectGroups(int $profileId, array $groups, array $newGroups, int $userId): void
    {
        $this->editableProfile($profileId);
        $allowedSubjects = array_fill_keys(array_map(
            static fn (array $row): int => (int) $row['mtcid'],
            $this->profileSubjectConfigurations($profileId)
        ), true);

        if ($allowedSubjects === []) {
            throw new RuntimeException('El perfil no tiene materias aplicables para agrupar.');
        }

        $this->assertUniqueSubjectsAcrossGroups($groups, $newGroups, $allowedSubjects);

        $this->db->beginTransaction();

        try {
            $temporaryOrder = $this->db->prepare(
                "UPDATE grupo_materia_calificacion
                 SET gmcorden = -gmcid
                 WHERE gmcid = :group_id
                   AND pcaid = :profile_id"
            );
            $updateGroup = $this->db->prepare(
                "UPDATE grupo_materia_calificacion
                 SET gmcnombre = :name,
                     areaid = :area_id,
                     gmcdescripcion = :description,
                     gmcmodo_calculo = :calculation_mode,
                     gmcmtcid_representante = :representative_subject_id,
                     gmcvisualizacion = :display_mode,
                     gmcpromediable = :averages,
                     gmcvisible_libreta = :report_visible,
                     gmcestado = :status,
                     gmcorden = :order_number,
                     gmcfecha_modificacion = CURRENT_TIMESTAMP
                 WHERE gmcid = :group_id
                   AND pcaid = :profile_id"
            );
            $deleteDetails = $this->db->prepare(
                "DELETE FROM grupo_materia_calificacion_detalle
                 WHERE gmcid = :group_id
                   AND pcaid = :profile_id"
            );
            $deleteGroup = $this->db->prepare(
                "DELETE FROM grupo_materia_calificacion
                 WHERE gmcid = :group_id
                   AND pcaid = :profile_id"
            );
            $insertDetail = $this->subjectGroupDetailInsertStatement();

            foreach ($groups as $groupId => $data) {
                if (!is_array($data)) {
                    continue;
                }

                if (!empty($data['delete'])) {
                    continue;
                }

                $temporaryOrder->execute([
                    'group_id' => (int) $groupId,
                    'profile_id' => $profileId,
                ]);
            }

            foreach ($groups as $groupId => $data) {
                if (!is_array($data)) {
                    continue;
                }

                if (!empty($data['delete'])) {
                    $deleteDetails->execute([
                        'group_id' => (int) $groupId,
                        'profile_id' => $profileId,
                    ]);
                    $deleteGroup->execute([
                        'group_id' => (int) $groupId,
                        'profile_id' => $profileId,
                    ]);
                    continue;
                }

                $payload = $this->subjectGroupPayload($data, $allowedSubjects, $allowedAreas);
                $updateGroup->execute($payload + [
                    'group_id' => (int) $groupId,
                    'profile_id' => $profileId,
                ]);

                $deleteDetails->execute([
                    'group_id' => (int) $groupId,
                    'profile_id' => $profileId,
                ]);

                if (!empty($data['gmcestado'])) {
                    foreach ($this->subjectGroupSelectedSubjects($data, $allowedSubjects, false) as $index => $subjectId) {
                        $insertDetail->execute([
                            'group_id' => (int) $groupId,
                            'profile_id' => $profileId,
                            'course_subject_id' => $subjectId,
                            'order_number' => $index + 1,
                        ]);
                    }
                }
            }

            foreach ($newGroups as $data) {
                if (!is_array($data) || trim((string) ($data['gmcnombre'] ?? '')) === '') {
                    continue;
                }

                $this->insertSubjectGroup($profileId, $data, $allowedSubjects, $allowedAreas);
            }

            $this->audit($userId, 'GRUPOS_MATERIAS_EDITADOS', 'perfil_calificacion', $profileId, null, 'Grupos de materias actualizados');
            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    private function findTemplate(int $templateId): array
    {
        $statement = $this->db->prepare(
            "SELECT *
             FROM plantilla_calificacion
             WHERE pclid = :id
               AND pclestado = true
             LIMIT 1"
        );
        $statement->execute(['id' => $templateId]);
        $template = $statement->fetch();

        if ($template === false) {
            throw new InvalidArgumentException('La plantilla seleccionada no existe o no esta activa.');
        }

        return $template;
    }

    private function findProfile(int $profileId): array
    {
        $statement = $this->db->prepare(
            "SELECT p.*, pl.pledescripcion, pl.plefechainicio, pl.plefechafin
             FROM perfil_calificacion p
             INNER JOIN periodo_lectivo pl ON pl.pleid = p.pleid
             WHERE p.pcaid = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $profileId]);
        $profile = $statement->fetch();

        if ($profile === false) {
            throw new InvalidArgumentException('El perfil de calificaciones no existe.');
        }

        return $profile;
    }

    private function editableProfile(int $profileId): array
    {
        $profile = $this->findProfile($profileId);

        if (($profile['pcaestado'] ?? '') !== 'BORRADOR') {
            throw new RuntimeException('Solo se puede editar un perfil en estado BORRADOR.');
        }

        return $profile;
    }

    private function scalePayload(array $data): array
    {
        $code = trim((string) ($data['ecacodigo'] ?? ''));
        $name = trim((string) ($data['ecanombre'] ?? ''));
        $minimum = trim((string) ($data['ecavalor_minimo'] ?? ''));
        $maximum = trim((string) ($data['ecavalor_maximo'] ?? ''));

        if ($code === '' || $name === '') {
            throw new InvalidArgumentException('Codigo y nombre de escala son obligatorios.');
        }

        if (($minimum === '') !== ($maximum === '')) {
            throw new InvalidArgumentException('Complete minimo y maximo de escala, o deje ambos vacios.');
        }

        if ($minimum !== '' && (float) $maximum < (float) $minimum) {
            throw new InvalidArgumentException('El maximo de escala no puede ser menor que el minimo.');
        }

        return [
            'code' => $code,
            'name' => $name,
            'description' => trim((string) ($data['ecadescripcion'] ?? '')),
            'minimum' => $minimum !== '' ? $minimum : null,
            'maximum' => $maximum !== '' ? $maximum : null,
            'order_number' => max(1, (int) ($data['ecaorden'] ?? 1)),
            'status' => empty($data['ecaestado']) ? 'false' : 'true',
        ];
    }

    private function promotionRuleId(int $profileId): int
    {
        $statement = $this->db->prepare(
            "SELECT rprid
             FROM regla_promocion
             WHERE pcaid = :profile_id
             ORDER BY rprid ASC
             LIMIT 1"
        );
        $statement->execute(['profile_id' => $profileId]);
        $ruleId = $statement->fetchColumn();

        if ($ruleId !== false) {
            return (int) $ruleId;
        }

        $insert = $this->db->prepare(
            "INSERT INTO regla_promocion (
                pcaid,
                rprnombre,
                rprdescripcion,
                rpraplica_sobre,
                rprrequiere_todas_materias,
                rprestado
             ) VALUES (
                :profile_id,
                'Promocion general',
                'Regla creada desde editor de perfil.',
                'PROMEDIO_GENERAL',
                true,
                true
             )
             RETURNING rprid"
        );
        $insert->execute(['profile_id' => $profileId]);

        return (int) $insert->fetchColumn();
    }

    private function promotionTramoPayload(array $data): array
    {
        $result = trim((string) ($data['rptresultado'] ?? ''));
        $minimum = trim((string) ($data['rptnota_minima'] ?? ''));
        $maximum = trim((string) ($data['rptnota_maxima'] ?? ''));

        if (!in_array($result, ['PROMOVIDO', 'SUPLETORIO', 'RECUPERACION', 'EXAMEN_GRACIA', 'NO_PROMOVIDO'], true)) {
            throw new InvalidArgumentException('El resultado de promocion no es valido.');
        }

        if ($minimum === '' || $maximum === '') {
            throw new InvalidArgumentException('Los rangos de promocion son obligatorios.');
        }

        if ((float) $maximum < (float) $minimum) {
            throw new InvalidArgumentException('El maximo de promocion no puede ser menor que el minimo.');
        }

        return [
            'order_number' => max(1, (int) ($data['rptorden'] ?? 1)),
            'minimum' => $minimum,
            'maximum' => $maximum,
            'result' => $result,
            'extraordinary' => empty($data['rpthabilita_extraordinaria']) ? 'false' : 'true',
            'status' => empty($data['rptestado']) ? 'false' : 'true',
        ];
    }

    private function updateDraftExtraordinaryInstances(int $profileId, array $instances, array $newInstances): void
    {
        $delete = $this->db->prepare(
            "DELETE FROM instancia_extraordinaria
             WHERE iexid = :instance_id
               AND pcaid = :profile_id"
        );
        $update = $this->db->prepare(
            "UPDATE instancia_extraordinaria
             SET iexnombre = :name,
                 iexorden = :order_number,
                 iexestado = :status,
                 iexaplica_sobre = :applies_to,
                 iexnota_habilita_minima = :enabled_minimum,
                 iexnota_habilita_maxima = :enabled_maximum,
                 iexnota_minima_aprobar = :approval_minimum,
                 iexnota_final_aprobado = :approved_final,
                 iexpermite_siguiente = :allows_next,
                 iexfecha_modificacion = CURRENT_TIMESTAMP
             WHERE iexid = :instance_id
               AND pcaid = :profile_id"
        );
        $temporaryOrder = $this->db->prepare(
            "UPDATE instancia_extraordinaria
             SET iexorden = -iexid
             WHERE iexid = :instance_id
               AND pcaid = :profile_id"
        );

        foreach ($instances as $instanceId => $data) {
            if (!empty($data['delete'])) {
                $delete->execute([
                    'instance_id' => (int) $instanceId,
                    'profile_id' => $profileId,
                ]);
                continue;
            }

            $temporaryOrder->execute([
                'instance_id' => (int) $instanceId,
                'profile_id' => $profileId,
            ]);
        }

        foreach ($instances as $instanceId => $data) {
            if (!empty($data['delete'])) {
                continue;
            }

            $update->execute($this->extraordinaryPayload($data) + [
                'instance_id' => (int) $instanceId,
                'profile_id' => $profileId,
            ]);
        }

        $insert = $this->db->prepare(
            "INSERT INTO instancia_extraordinaria (
                pcaid,
                iexnombre,
                iexorden,
                iexestado,
                iexaplica_sobre,
                iexnota_habilita_minima,
                iexnota_habilita_maxima,
                iexnota_minima_aprobar,
                iexnota_final_aprobado,
                iexpermite_siguiente
             ) VALUES (
                :profile_id,
                :name,
                :order_number,
                :status,
                :applies_to,
                :enabled_minimum,
                :enabled_maximum,
                :approval_minimum,
                :approved_final,
                :allows_next
             )"
        );

        foreach ($newInstances as $data) {
            if (!is_array($data) || trim((string) ($data['iexnombre'] ?? '')) === '') {
                continue;
            }

            $insert->execute($this->extraordinaryPayload($data) + ['profile_id' => $profileId]);
        }
    }

    private function extraordinaryPayload(array $data): array
    {
        $name = trim((string) ($data['iexnombre'] ?? ''));
        $appliesTo = trim((string) ($data['iexaplica_sobre'] ?? 'MATERIA'));
        $enabledMinimum = trim((string) ($data['iexnota_habilita_minima'] ?? ''));
        $enabledMaximum = trim((string) ($data['iexnota_habilita_maxima'] ?? ''));
        $approvalMinimum = trim((string) ($data['iexnota_minima_aprobar'] ?? ''));
        $approvedFinal = trim((string) ($data['iexnota_final_aprobado'] ?? ''));

        if ($name === '') {
            throw new InvalidArgumentException('El nombre de la instancia extraordinaria es obligatorio.');
        }

        if (!in_array($appliesTo, ['MATERIA', 'PROMEDIO_GENERAL'], true)) {
            throw new InvalidArgumentException('El alcance de la instancia extraordinaria no es valido.');
        }

        if ($enabledMinimum === '' || $enabledMaximum === '' || $approvalMinimum === '') {
            throw new InvalidArgumentException('Los rangos de instancia extraordinaria son obligatorios.');
        }

        if ((float) $enabledMaximum < (float) $enabledMinimum) {
            throw new InvalidArgumentException('El maximo habilitante no puede ser menor que el minimo.');
        }

        return [
            'name' => $name,
            'order_number' => max(1, (int) ($data['iexorden'] ?? 1)),
            'status' => in_array((string) ($data['iexestado'] ?? 'ACTIVA'), ['BORRADOR', 'ACTIVA', 'CERRADA', 'ANULADA'], true)
                ? (string) ($data['iexestado'] ?? 'ACTIVA')
                : 'ACTIVA',
            'applies_to' => $appliesTo,
            'enabled_minimum' => $enabledMinimum,
            'enabled_maximum' => $enabledMaximum,
            'approval_minimum' => $approvalMinimum,
            'approved_final' => $approvedFinal !== '' ? $approvedFinal : null,
            'allows_next' => empty($data['iexpermite_siguiente']) ? 'false' : 'true',
        ];
    }

    private function subjectConfigurationPayload(array $data): array
    {
        $recordType = trim((string) ($data['mcctipo_registro'] ?? 'CUANTITATIVO'));
        $displayType = trim((string) ($data['mcctipo_visualizacion'] ?? 'MIXTA'));

        if (!in_array($recordType, ['CUANTITATIVO', 'CUALITATIVO', 'AMBITOS_DESTREZAS'], true)) {
            throw new InvalidArgumentException('El tipo de registro de materia no es valido.');
        }

        if (!in_array($displayType, ['CUANTITATIVA', 'CUALITATIVA', 'MIXTA'], true)) {
            throw new InvalidArgumentException('El tipo de visualizacion de materia no es valido.');
        }

        return [
            'record_type' => $recordType,
            'display_type' => $displayType,
            'averages' => empty($data['mccpromediable']) ? 'false' : 'true',
            'report_visible' => empty($data['mccvisible_libreta']) ? 'false' : 'true',
            'uses_equivalence' => empty($data['mccusa_equivalencia']) ? 'false' : 'true',
            'status' => empty($data['mccestado']) ? 'false' : 'true',
            'observation' => trim((string) ($data['mccobservacion'] ?? '')),
        ];
    }

    private function subjectGroupPayload(array $data, array $allowedSubjects, array $allowedAreas): array
    {
        $areaId = (int) ($data['areaid'] ?? 0);
        $name = trim((string) ($data['gmcnombre'] ?? ''));
        $calculationMode = trim((string) ($data['gmcmodo_calculo'] ?? 'PROMEDIO_SIMPLE'));
        $displayMode = trim((string) ($data['gmcvisualizacion'] ?? 'GRUPO'));
        $representativeSubjectId = (int) ($data['gmcmtcid_representante'] ?? 0);

        if ($name === '') {
            throw new InvalidArgumentException('El nombre del grupo de materias es obligatorio.');
        }

        if ($areaId <= 0 || !isset($allowedAreas[$areaId])) {
            throw new InvalidArgumentException('Seleccione un area valida para el grupo de materias.');
        }

        if (!in_array($calculationMode, ['PROMEDIO_SIMPLE', 'PROMEDIO_PONDERADO', 'SUMA'], true)) {
            throw new InvalidArgumentException('El modo de calculo del grupo no es valido.');
        }

        if (!in_array($displayMode, ['GRUPO', 'REPRESENTANTE'], true)) {
            throw new InvalidArgumentException('La visualizacion del grupo no es valida.');
        }

        if ($representativeSubjectId > 0 && !isset($allowedSubjects[$representativeSubjectId])) {
            throw new InvalidArgumentException('La materia representante no pertenece al alcance del perfil.');
        }

        return [
            'name' => $name,
            'area_id' => $areaId,
            'description' => trim((string) ($data['gmcdescripcion'] ?? '')),
            'calculation_mode' => $calculationMode,
            'representative_subject_id' => $representativeSubjectId > 0 ? $representativeSubjectId : null,
            'display_mode' => $displayMode,
            'averages' => empty($data['gmcpromediable']) ? 'false' : 'true',
            'report_visible' => empty($data['gmcvisible_libreta']) ? 'false' : 'true',
            'status' => empty($data['gmcestado']) ? 'false' : 'true',
            'order_number' => max(1, (int) ($data['gmcorden'] ?? 1)),
        ];
    }

    private function subjectGroupSelectedSubjects(array $data, array $allowedSubjects, bool $requireMinimum): array
    {
        $selectedSubjects = array_values(array_unique(array_filter(
            array_map(static fn (mixed $subjectId): int => (int) $subjectId, is_array($data['mtcid'] ?? null) ? $data['mtcid'] : []),
            static fn (int $subjectId): bool => $subjectId > 0
        )));

        if ($requireMinimum && count($selectedSubjects) < 2) {
            throw new InvalidArgumentException('Seleccione al menos dos materias para crear un grupo.');
        }

        foreach ($selectedSubjects as $subjectId) {
            if (!isset($allowedSubjects[$subjectId])) {
                throw new InvalidArgumentException('Una materia seleccionada no pertenece al alcance del perfil.');
            }
        }

        return $selectedSubjects;
    }

    private function assertUniqueSubjectsAcrossGroups(array $groups, array $newGroups, array $allowedSubjects): void
    {
        $usedSubjects = [];

        foreach ($groups as $data) {
            if (!is_array($data) || !empty($data['delete']) || empty($data['gmcestado'])) {
                continue;
            }

            foreach ($this->subjectGroupSelectedSubjects($data, $allowedSubjects, true) as $subjectId) {
                if (isset($usedSubjects[$subjectId])) {
                    throw new InvalidArgumentException('Una materia no puede estar en dos grupos activos del mismo perfil.');
                }

                $usedSubjects[$subjectId] = true;
            }
        }

        foreach ($newGroups as $data) {
            if (!is_array($data) || trim((string) ($data['gmcnombre'] ?? '')) === '' || empty($data['gmcestado'])) {
                continue;
            }

            foreach ($this->subjectGroupSelectedSubjects($data, $allowedSubjects, true) as $subjectId) {
                if (isset($usedSubjects[$subjectId])) {
                    throw new InvalidArgumentException('Una materia no puede estar en dos grupos activos del mismo perfil.');
                }

                $usedSubjects[$subjectId] = true;
            }
        }
    }

    private function insertSubjectGroup(int $profileId, array $data, array $allowedSubjects, array $allowedAreas): void
    {
        $payload = $this->subjectGroupPayload($data, $allowedSubjects, $allowedAreas);
        $selectedSubjects = $this->subjectGroupSelectedSubjects($data, $allowedSubjects, true);

        $insertGroup = $this->db->prepare(
            "INSERT INTO grupo_materia_calificacion (
                pcaid,
                areaid,
                gmcnombre,
                gmcdescripcion,
                gmcmodo_calculo,
                gmcmtcid_representante,
                gmcvisualizacion,
                gmcpromediable,
                gmcvisible_libreta,
                gmcestado,
                gmcorden
             ) VALUES (
                :profile_id,
                :area_id,
                :name,
                :description,
                :calculation_mode,
                :representative_subject_id,
                :display_mode,
                :averages,
                :report_visible,
                :status,
                :order_number
             )
             RETURNING gmcid"
        );
        $insertGroup->execute($payload + ['profile_id' => $profileId]);
        $groupId = (int) $insertGroup->fetchColumn();

        $insertDetail = $this->subjectGroupDetailInsertStatement();

        foreach ($selectedSubjects as $index => $subjectId) {
            $insertDetail->execute([
                'group_id' => $groupId,
                'profile_id' => $profileId,
                'course_subject_id' => $subjectId,
                'order_number' => $index + 1,
            ]);
        }
    }

    private function subjectGroupDetailInsertStatement(): \PDOStatement
    {
        return $this->db->prepare(
            "INSERT INTO grupo_materia_calificacion_detalle (
                gmcid,
                pcaid,
                mtcid,
                gmcdpeso,
                gmcdorden,
                gmcdincluye_calculo,
                gmcdvisible_detalle,
                gmcdestado
             ) VALUES (
                :group_id,
                :profile_id,
                :course_subject_id,
                NULL,
                :order_number,
                true,
                false,
                true
             )"
        );
    }

    private function profileRows(string $sql, int $profileId): array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute(['profile_id' => $profileId]);

        return $statement->fetchAll();
    }

    private function profileAssignments(int $profileId): array
    {
        $statement = $this->db->prepare(
            "SELECT
                a.pasid,
                a.pasalcance,
                a.pasprioridad,
                a.pasestado,
                COALESCE(
                    ne.nednombre,
                    g.granombre,
                    gc.granombre || ' ' || pr.prlnombre,
                    gm.granombre || ' ' || prm.prlnombre || ' | ' || asg.asgnombre
                ) AS destino
             FROM perfil_calificacion_asignacion a
             LEFT JOIN nivel_educativo ne ON ne.nedid = a.nedid
             LEFT JOIN grado g ON g.graid = a.graid
             LEFT JOIN curso c ON c.curid = a.curid
             LEFT JOIN grado gc ON gc.graid = c.graid
             LEFT JOIN paralelo pr ON pr.prlid = c.prlid
             LEFT JOIN materia_curso mc ON mc.mtcid = a.mtcid
             LEFT JOIN curso cm ON cm.curid = mc.curid
             LEFT JOIN grado gm ON gm.graid = cm.graid
             LEFT JOIN paralelo prm ON prm.prlid = cm.prlid
             LEFT JOIN asignatura asg ON asg.asgid = mc.asgid
             WHERE a.pcaid = :profile_id
             ORDER BY a.pasprioridad DESC, a.pasid DESC"
        );
        $statement->execute(['profile_id' => $profileId]);

        return $statement->fetchAll();
    }

    private function profileSubjectConfigurations(int $profileId): array
    {
        $profile = $this->findProfile($profileId);
        $statement = $this->db->prepare(
            "SELECT *
             FROM (
                SELECT
                    mc.mtcid,
                    c.curid,
                    c.pleid,
                    g.graid,
                    n.nedid,
                    pl.pledescripcion,
                    n.nednombre,
                    g.granombre,
                    pr.prlnombre,
                    aa.areaid,
                    aa.areanombre,
                    asg.asgnombre,
                    a.pasalcance,
                    a.pasprioridad,
                    mcc.mccid,
                    COALESCE(mcc.mcctipo_registro, p.pcatipo_base) AS mcctipo_registro,
                    COALESCE(mcc.mcctipo_visualizacion, 'MIXTA') AS mcctipo_visualizacion,
                    COALESCE(mcc.mccpromediable, true) AS mccpromediable,
                    COALESCE(mcc.mccvisible_libreta, true) AS mccvisible_libreta,
                    COALESCE(mcc.mccusa_equivalencia, true) AS mccusa_equivalencia,
                    COALESCE(mcc.mccestado, true) AS mccestado,
                    COALESCE(mcc.mccobservacion, '') AS mccobservacion,
                    ROW_NUMBER() OVER (
                        PARTITION BY mc.mtcid
                        ORDER BY
                            CASE a.pasalcance
                                WHEN 'MATERIA' THEN 4
                                WHEN 'CURSO' THEN 3
                                WHEN 'GRADO' THEN 2
                                WHEN 'NIVEL' THEN 1
                                ELSE 0
                            END DESC,
                            a.pasprioridad DESC,
                            a.pasid DESC
                    ) AS prioridad_resuelta
                 FROM materia_curso mc
                 INNER JOIN curso c ON c.curid = mc.curid
                 INNER JOIN periodo_lectivo pl ON pl.pleid = c.pleid
                 INNER JOIN grado g ON g.graid = c.graid
                 INNER JOIN nivel_educativo n ON n.nedid = g.nedid
                 INNER JOIN paralelo pr ON pr.prlid = c.prlid
                 INNER JOIN asignatura asg ON asg.asgid = mc.asgid
                 INNER JOIN area_academica aa ON aa.areaid = asg.areaid
                 INNER JOIN perfil_calificacion p ON p.pcaid = :profile_id
                 INNER JOIN perfil_calificacion_asignacion a
                    ON a.pcaid = p.pcaid
                    AND a.pleid = c.pleid
                    AND a.pasestado = true
                    AND (
                        (a.pasalcance = 'MATERIA' AND a.mtcid = mc.mtcid)
                        OR
                        (a.pasalcance = 'CURSO' AND a.curid = c.curid)
                        OR
                        (a.pasalcance = 'GRADO' AND a.graid = c.graid)
                        OR
                        (a.pasalcance = 'NIVEL' AND a.nedid = g.nedid)
                    )
                 LEFT JOIN materia_calificacion_config mcc
                    ON mcc.mtcid = mc.mtcid
                    AND mcc.pcaid = p.pcaid
                 WHERE c.pleid = :period_id
                   AND mc.mtcestado = true
             ) materias
             WHERE prioridad_resuelta = 1
             ORDER BY nednombre ASC, granombre ASC, prlnombre ASC, areanombre ASC, asgnombre ASC"
        );
        $statement->execute([
            'profile_id' => $profileId,
            'period_id' => (int) $profile['pleid'],
        ]);

        return $statement->fetchAll();
    }

    private function profileSubjectGroups(int $profileId): array
    {
        $statement = $this->db->prepare(
            "SELECT
                g.*,
                ga.areanombre,
                representante.asgnombre AS representante_nombre
             FROM grupo_materia_calificacion g
             INNER JOIN area_academica ga ON ga.areaid = g.areaid
             LEFT JOIN materia_curso mcr ON mcr.mtcid = g.gmcmtcid_representante
             LEFT JOIN asignatura representante ON representante.asgid = mcr.asgid
             WHERE g.pcaid = :profile_id
             ORDER BY g.gmcorden ASC, g.gmcnombre ASC"
        );
        $statement->execute(['profile_id' => $profileId]);
        $groups = [];

        foreach ($statement->fetchAll() as $group) {
            $group['details'] = [];
            $groups[(int) $group['gmcid']] = $group;
        }

        if ($groups === []) {
            return [];
        }

        $details = $this->db->prepare(
            "SELECT
                d.*,
                v.granombre,
                v.prlnombre,
                v.areanombre,
                v.asgnombre,
                v.mtcnombre_mostrar
             FROM grupo_materia_calificacion_detalle d
             INNER JOIN vw_materia_curso v ON v.mtcid = d.mtcid
             WHERE d.gmcid IN (" . implode(',', array_fill(0, count($groups), '?')) . ")
             ORDER BY d.gmcdorden ASC, v.asgnombre ASC"
        );
        $details->execute(array_keys($groups));

        foreach ($details->fetchAll() as $detail) {
            $groupId = (int) $detail['gmcid'];
            if (isset($groups[$groupId])) {
                $groups[$groupId]['details'][] = $detail;
            }
        }

        return array_values($groups);
    }

    private function profilePromotionTramos(int $profileId): array
    {
        $statement = $this->db->prepare(
            "SELECT t.*, r.rprnombre
             FROM regla_promocion_tramo t
             INNER JOIN regla_promocion r ON r.rprid = t.rprid
             WHERE r.pcaid = :profile_id
             ORDER BY r.rprid ASC, t.rptorden ASC"
        );
        $statement->execute(['profile_id' => $profileId]);

        return $statement->fetchAll();
    }

    private function findPeriod(int $periodId): array
    {
        $statement = $this->db->prepare(
            "SELECT pleid, pledescripcion, plefechainicio, plefechafin
             FROM periodo_lectivo
             WHERE pleid = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $periodId]);
        $period = $statement->fetch();

        if ($period === false) {
            throw new InvalidArgumentException('El periodo seleccionado no existe.');
        }

        return $period;
    }

    private function profileNameExists(int $periodId, string $profileName): bool
    {
        $statement = $this->db->prepare(
            "SELECT 1
             FROM perfil_calificacion
             WHERE pleid = :period_id
               AND pcanombre = :name
             LIMIT 1"
        );
        $statement->execute([
            'period_id' => $periodId,
            'name' => $profileName,
        ]);

        return $statement->fetchColumn() !== false;
    }

    private function insertProfile(array $template, int $periodId, string $profileName, string $state, string $validFrom, string $validTo, int $userId): int
    {
        $statement = $this->db->prepare(
            "INSERT INTO perfil_calificacion (
                pleid,
                pcanombre,
                pcadescripcion,
                pcaversion,
                pcaestado,
                pcatipo_base,
                pcavigencia_desde,
                pcavigencia_hasta,
                pcaminima,
                pcamaxima,
                pcaaprobacion,
                pcadecimales,
                pcametodo_decimal,
                pcapromedia_final,
                pcaaplica_promocion,
                usuid_creacion
             ) VALUES (
                :period_id,
                :name,
                :description,
                1,
                :state,
                :type,
                :valid_from,
                :valid_to,
                :minimum,
                :maximum,
                :approval,
                :decimals,
                :decimal_method,
                :average_final,
                :promotion,
                :user_id
             )
             RETURNING pcaid"
        );
        $statement->execute([
            'period_id' => $periodId,
            'name' => $profileName,
            'description' => $template['pcldescripcion'] ?? null,
            'state' => $state,
            'type' => $template['pcltipo_base'],
            'valid_from' => $validFrom,
            'valid_to' => $validTo !== '' ? $validTo : null,
            'minimum' => $template['pclminima'],
            'maximum' => $template['pclmaxima'],
            'approval' => $template['pclaprobacion'],
            'decimals' => (int) $template['pcldecimales'],
            'decimal_method' => $template['pclmetodo_decimal'],
            'average_final' => $this->boolSql($template['pclpromedia_final']),
            'promotion' => $this->boolSql($template['pclaplica_promocion']),
            'user_id' => $userId,
        ]);

        return (int) $statement->fetchColumn();
    }

    private function copySubperiods(int $templateId, int $profileId, string $periodStart, string $periodEnd): array
    {
        $subperiods = $this->templateRows(
            "SELECT *
             FROM plantilla_subperiodo
             WHERE pclid = :template_id
             ORDER BY psuorden ASC",
            $templateId
        );
        $dateRanges = $this->splitDateRange($periodStart, $periodEnd, count($subperiods));
        $map = [];
        $statement = $this->db->prepare(
            "INSERT INTO subperiodo_calificacion (
                pcaid,
                spcnombre,
                spcorden,
                spcfecha_inicio,
                spcfecha_fin,
                spcestado,
                spcparticipa_final,
                spcpeso_final
             ) VALUES (
                :profile_id,
                :name,
                :order_number,
                :start_date,
                :end_date,
                'EN_REGISTRO',
                :final_participation,
                :final_weight
             )
             RETURNING spcid"
        );

        foreach ($subperiods as $index => $subperiod) {
            $range = $dateRanges[$index] ?? ['start' => $periodStart, 'end' => $periodEnd];
            $statement->execute([
                'profile_id' => $profileId,
                'name' => $subperiod['psunombre'],
                'order_number' => (int) $subperiod['psuorden'],
                'start_date' => $range['start'],
                'end_date' => $range['end'],
                'final_participation' => $this->boolSql($subperiod['psuparticipa_final']),
                'final_weight' => $subperiod['psupeso_final'],
            ]);
            $map[(int) $subperiod['psuid']] = (int) $statement->fetchColumn();
        }

        return $map;
    }

    private function copyComponents(array $subperiodMap): void
    {
        if ($subperiodMap === []) {
            return;
        }

        $statement = $this->db->prepare(
            "SELECT *
             FROM plantilla_componente
             WHERE psuid = :template_subperiod_id
             ORDER BY pcoorden ASC"
        );
        $insert = $this->db->prepare(
            "INSERT INTO componente_calificacion (
                spcid,
                cpcnombre,
                cpcorden,
                cpcpeso,
                cpctipo_calculo,
                cpcestado
             ) VALUES (
                :subperiod_id,
                :name,
                :order_number,
                :weight,
                :calculation_type,
                :status
             )"
        );

        foreach ($subperiodMap as $templateSubperiodId => $subperiodId) {
            $statement->execute(['template_subperiod_id' => $templateSubperiodId]);

            foreach ($statement->fetchAll() as $component) {
                $insert->execute([
                    'subperiod_id' => $subperiodId,
                    'name' => $component['pconombre'],
                    'order_number' => (int) $component['pcoorden'],
                    'weight' => $component['pcopeso'],
                    'calculation_type' => $component['pcotipo_calculo'],
                    'status' => $this->boolSql($component['pcoestado']),
                ]);
            }
        }
    }

    private function copyQualitativeScale(int $templateId, int $profileId): void
    {
        $rows = $this->templateRows(
            "SELECT *
             FROM plantilla_escala_cualitativa
             WHERE pclid = :template_id
             ORDER BY pecorden ASC",
            $templateId
        );
        $insert = $this->db->prepare(
            "INSERT INTO escala_cualitativa (
                pcaid,
                ecacodigo,
                ecanombre,
                ecadescripcion,
                ecavalor_minimo,
                ecavalor_maximo,
                ecaorden,
                ecaestado
             ) VALUES (
                :profile_id,
                :code,
                :name,
                :description,
                :minimum,
                :maximum,
                :order_number,
                :status
             )"
        );

        foreach ($rows as $row) {
            $insert->execute([
                'profile_id' => $profileId,
                'code' => $row['peccodigo'],
                'name' => $row['pecnombre'],
                'description' => $row['pecdescripcion'],
                'minimum' => $row['pecvalor_minimo'],
                'maximum' => $row['pecvalor_maximo'],
                'order_number' => (int) $row['pecorden'],
                'status' => $this->boolSql($row['pecestado']),
            ]);
        }
    }

    private function copyAmbits(int $templateId, int $profileId): array
    {
        $rows = $this->templateRows(
            "SELECT *
             FROM plantilla_ambito
             WHERE pclid = :template_id
             ORDER BY pamborden ASC",
            $templateId
        );
        $insert = $this->db->prepare(
            "INSERT INTO ambito_calificacion (
                pcaid,
                ambnombre,
                ambdescripcion,
                amborden,
                ambestado
             ) VALUES (
                :profile_id,
                :name,
                :description,
                :order_number,
                :status
             )
             RETURNING ambid"
        );
        $map = [];

        foreach ($rows as $row) {
            $insert->execute([
                'profile_id' => $profileId,
                'name' => $row['pambnombre'],
                'description' => $row['pambdescripcion'],
                'order_number' => (int) $row['pamborden'],
                'status' => $this->boolSql($row['pambestado']),
            ]);
            $map[(int) $row['pambid']] = (int) $insert->fetchColumn();
        }

        return $map;
    }

    private function copySkills(array $ambitMap): void
    {
        if ($ambitMap === []) {
            return;
        }

        $statement = $this->db->prepare(
            "SELECT *
             FROM plantilla_destreza
             WHERE pambid = :template_ambit_id
             ORDER BY pdesorden ASC"
        );
        $insert = $this->db->prepare(
            "INSERT INTO destreza_calificacion (
                ambid,
                descodigo,
                desnombre,
                desdescripcion,
                desorden,
                desestado
             ) VALUES (
                :ambit_id,
                :code,
                :name,
                :description,
                :order_number,
                :status
             )"
        );

        foreach ($ambitMap as $templateAmbitId => $ambitId) {
            $statement->execute(['template_ambit_id' => $templateAmbitId]);

            foreach ($statement->fetchAll() as $skill) {
                $insert->execute([
                    'ambit_id' => $ambitId,
                    'code' => $skill['pdescodigo'],
                    'name' => $skill['pdesnombre'],
                    'description' => $skill['pdesdescripcion'],
                    'order_number' => (int) $skill['pdesorden'],
                    'status' => $this->boolSql($skill['pdesestado']),
                ]);
            }
        }
    }

    private function copyPromotion(int $templateId, int $profileId): void
    {
        $tramos = $this->templateRows(
            "SELECT *
             FROM plantilla_promocion_tramo
             WHERE pclid = :template_id
               AND pptestado = true
             ORDER BY pptorden ASC",
            $templateId
        );

        if ($tramos === []) {
            return;
        }

        $ruleStatement = $this->db->prepare(
            "INSERT INTO regla_promocion (
                pcaid,
                rprnombre,
                rprdescripcion,
                rpraplica_sobre,
                rprrequiere_todas_materias,
                rprestado
             ) VALUES (
                :profile_id,
                'Promocion general',
                'Regla copiada desde plantilla.',
                'PROMEDIO_GENERAL',
                true,
                true
             )
             RETURNING rprid"
        );
        $ruleStatement->execute(['profile_id' => $profileId]);
        $ruleId = (int) $ruleStatement->fetchColumn();

        $tramoStatement = $this->db->prepare(
            "INSERT INTO regla_promocion_tramo (
                rprid,
                rptorden,
                rptnota_minima,
                rptnota_maxima,
                rptresultado,
                rpthabilita_extraordinaria,
                rptestado
             ) VALUES (
                :rule_id,
                :order_number,
                :minimum,
                :maximum,
                :result,
                :extraordinary,
                :status
             )"
        );

        foreach ($tramos as $tramo) {
            $tramoStatement->execute([
                'rule_id' => $ruleId,
                'order_number' => (int) $tramo['pptorden'],
                'minimum' => $tramo['pptnota_minima'],
                'maximum' => $tramo['pptnota_maxima'],
                'result' => $tramo['pptresultado'],
                'extraordinary' => $this->boolSql($tramo['ppthabilita_extraordinaria']),
                'status' => $this->boolSql($tramo['pptestado']),
            ]);
        }

        $this->copyExtraordinaryInstances($templateId, $profileId);
    }

    private function copyExtraordinaryInstances(int $templateId, int $profileId): void
    {
        $rows = $this->templateRows(
            "SELECT *
             FROM plantilla_instancia_extraordinaria
             WHERE pclid = :template_id
               AND pieestado = true
             ORDER BY pieorden ASC",
            $templateId
        );
        $insert = $this->db->prepare(
            "INSERT INTO instancia_extraordinaria (
                pcaid,
                iexnombre,
                iexorden,
                iexestado,
                iexaplica_sobre,
                iexnota_habilita_minima,
                iexnota_habilita_maxima,
                iexnota_minima_aprobar,
                iexnota_final_aprobado,
                iexpermite_siguiente
             ) VALUES (
                :profile_id,
                :name,
                :order_number,
                'ACTIVA',
                :applies_to,
                :enabled_minimum,
                :enabled_maximum,
                :approval_minimum,
                :approved_final,
                :allows_next
             )"
        );

        foreach ($rows as $row) {
            $insert->execute([
                'profile_id' => $profileId,
                'name' => $row['pienombre'],
                'order_number' => (int) $row['pieorden'],
                'applies_to' => $row['pieaplica_sobre'],
                'enabled_minimum' => $row['pienota_habilita_minima'],
                'enabled_maximum' => $row['pienota_habilita_maxima'],
                'approval_minimum' => $row['pienota_minima_aprobar'],
                'approved_final' => $row['pienota_final_aprobado'],
                'allows_next' => $this->boolSql($row['piepermite_siguiente']),
            ]);
        }
    }

    private function templateRows(string $sql, int $templateId): array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute(['template_id' => $templateId]);

        return $statement->fetchAll();
    }

    private function splitDateRange(string $startDate, string $endDate, int $parts): array
    {
        if ($parts <= 0) {
            return [];
        }

        $start = new DateTimeImmutable($startDate);
        $end = new DateTimeImmutable($endDate);
        $totalDays = ((int) $start->diff($end)->format('%a')) + 1;
        $baseDays = intdiv($totalDays, $parts);
        $remainingDays = $totalDays % $parts;
        $ranges = [];
        $current = $start;

        for ($i = 0; $i < $parts; $i++) {
            $days = $baseDays + ($i < $remainingDays ? 1 : 0);
            $rangeEnd = $current->add(new DateInterval('P' . max(0, $days - 1) . 'D'));
            $ranges[] = [
                'start' => $current->format('Y-m-d'),
                'end' => $rangeEnd->format('Y-m-d'),
            ];
            $current = $rangeEnd->add(new DateInterval('P1D'));
        }

        return $ranges;
    }

    private function assertScopeTargetBelongsToPeriod(string $scope, int $targetId, int $periodId): void
    {
        if ($scope === 'NIVEL') {
            $statement = $this->db->prepare(
                "SELECT 1
                 FROM nivel_educativo
                 WHERE nedid = :target_id
                 LIMIT 1"
            );
            $statement->execute(['target_id' => $targetId]);

            if ($statement->fetchColumn() === false) {
                throw new InvalidArgumentException('El nivel seleccionado no existe.');
            }

            return;
        }

        if ($scope === 'GRADO') {
            $statement = $this->db->prepare(
                "SELECT 1
                 FROM grado
                 WHERE graid = :target_id
                 LIMIT 1"
            );
            $statement->execute(['target_id' => $targetId]);

            if ($statement->fetchColumn() === false) {
                throw new InvalidArgumentException('El grado seleccionado no existe.');
            }

            return;
        }

        if ($scope === 'CURSO') {
            $statement = $this->db->prepare(
                "SELECT 1
                 FROM curso
                 WHERE curid = :target_id
                   AND pleid = :period_id
                 LIMIT 1"
            );
            $statement->execute([
                'target_id' => $targetId,
                'period_id' => $periodId,
            ]);

            if ($statement->fetchColumn() === false) {
                throw new InvalidArgumentException('El curso seleccionado no pertenece al periodo del perfil.');
            }

            return;
        }

        $statement = $this->db->prepare(
            "SELECT 1
             FROM materia_curso mc
             INNER JOIN curso c ON c.curid = mc.curid
             WHERE mc.mtcid = :target_id
               AND c.pleid = :period_id
             LIMIT 1"
        );
        $statement->execute([
            'target_id' => $targetId,
            'period_id' => $periodId,
        ]);

        if ($statement->fetchColumn() === false) {
            throw new InvalidArgumentException('La materia seleccionada no pertenece al periodo del perfil.');
        }
    }

    private function insertAssignment(int $profileId, int $periodId, string $scope, int $targetId, int $userId, ?int $priorityOverride = null): void
    {
        $priority = $priorityOverride ?? [
            'NIVEL' => 1,
            'GRADO' => 2,
            'CURSO' => 3,
            'MATERIA' => 4,
        ][$scope] ?? 1;
        $priority = max(1, $priority);

        $data = [
            'profile_id' => $profileId,
            'period_id' => $periodId,
            'scope' => $scope,
            'level_id' => null,
            'grade_id' => null,
            'course_id' => null,
            'course_subject_id' => null,
            'priority' => $priority,
            'user_id' => $userId,
        ];

        if ($scope === 'NIVEL') {
            $data['level_id'] = $targetId;
        } elseif ($scope === 'GRADO') {
            $data['grade_id'] = $targetId;
        } elseif ($scope === 'CURSO') {
            $data['course_id'] = $targetId;
        } else {
            $data['course_subject_id'] = $targetId;
        }

        $statement = $this->db->prepare(
            "INSERT INTO perfil_calificacion_asignacion (
                pcaid,
                pleid,
                pasalcance,
                nedid,
                graid,
                curid,
                mtcid,
                pasprioridad,
                pasestado,
                usuid_registro
             ) VALUES (
                :profile_id,
                :period_id,
                :scope,
                :level_id,
                :grade_id,
                :course_id,
                :course_subject_id,
                :priority,
                true,
                :user_id
             )"
        );
        $statement->execute($data);
    }

    private function audit(int $userId, string $action, string $entity, int $entityId, ?string $previous, ?string $new): void
    {
        $statement = $this->db->prepare(
            "INSERT INTO auditoria_calificacion (
                usuid,
                auctipo_accion,
                aucentidad,
                aucentidad_id,
                aucvalor_anterior,
                aucvalor_nuevo
             ) VALUES (
                :user_id,
                :action,
                :entity,
                :entity_id,
                :previous_value,
                :new_value
             )"
        );
        $statement->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'previous_value' => $previous,
            'new_value' => $new,
        ]);
    }

    private function boolSql(mixed $value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    }
}
