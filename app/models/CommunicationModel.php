<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;
use RuntimeException;

class CommunicationModel extends Model
{
    protected string $table = 'comunicado';
    protected string $primaryKey = 'comid';

    public function page(int $page = 1, int $limit = 25): array
    {
        $page = max(1, $page);
        $limit = max(1, min($limit, 100));
        $offset = ($page - 1) * $limit;

        $statement = $this->db->prepare(
            "SELECT c.comid, c.comtitulo, c.comestado, c.comdestino_resumen,
                    c.comfecha_publicacion, c.comfecha_creacion,
                    u.usunombre AS usuario_creacion,
                    COUNT(DISTINCT cd.cdeid)::int AS total_destinatarios,
                    COUNT(DISTINCT cd.cdeid) FILTER (WHERE cd.cdeestado_lectura = 'LEIDO')::int AS total_leidos,
                    COUNT(ce.cenid) FILTER (WHERE ce.cencanal = 'EMAIL' AND ce.cenestado = 'PENDIENTE')::int AS emails_pendientes,
                    COUNT(ce.cenid) FILTER (WHERE ce.cencanal = 'EMAIL' AND ce.cenestado = 'OMITIDO')::int AS emails_omitidos,
                    COUNT(ce.cenid) FILTER (WHERE ce.cencanal = 'EMAIL' AND ce.cenestado = 'FALLIDO')::int AS emails_fallidos,
                    COUNT(ce.cenid) FILTER (WHERE ce.cencanal = 'WHATSAPP' AND ce.cenestado = 'PENDIENTE')::int AS whatsapp_pendientes,
                    COUNT(ce.cenid) FILTER (WHERE ce.cencanal = 'WHATSAPP' AND ce.cenestado = 'OMITIDO')::int AS whatsapp_omitidos,
                    COUNT(ce.cenid) FILTER (WHERE ce.cencanal = 'WHATSAPP' AND ce.cenestado = 'FALLIDO')::int AS whatsapp_fallidos
             FROM comunicado c
             INNER JOIN usuario u ON u.usuid = c.usuid_creacion
             LEFT JOIN comunicado_destinatario cd ON cd.comid = c.comid
             LEFT JOIN comunicado_entrega ce ON ce.cdeid = cd.cdeid
             GROUP BY c.comid, u.usunombre
             ORDER BY c.comfecha_creacion DESC
             LIMIT :limit OFFSET :offset"
        );
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        $countStatement = $this->db->query('SELECT COUNT(*) FROM comunicado');

        return [
            'rows' => $statement->fetchAll(),
            'total' => (int) $countStatement->fetchColumn(),
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public function receivedByUser(int $userId): array
    {
        $statement = $this->db->prepare(
            "SELECT c.comid, c.comtitulo, c.commensaje, c.comfecha_publicacion,
                    c.comestado, cd.cdeid, cd.cdeestado_lectura, cd.cdefecha_lectura,
                    COALESCE(sender_roles.roles, 'Institucion') AS enviado_por_rol
             FROM comunicado_destinatario cd
             INNER JOIN comunicado c ON c.comid = cd.comid
             LEFT JOIN LATERAL (
                SELECT string_agg(r.rolnombre, ', ' ORDER BY r.rolnombre) AS roles
                FROM usuario_rol ur
                INNER JOIN rol r ON r.rolid = ur.rolid
                WHERE ur.usuid = COALESCE(c.usuid_publicacion, c.usuid_creacion)
                  AND ur.usrestado = true
                  AND r.rolestado = true
             ) sender_roles ON true
             WHERE cd.usuid = :user_id
               AND c.comestado IN ('PUBLICADO', 'ANULADO')
             ORDER BY COALESCE(c.comfecha_publicacion, c.comfecha_creacion) DESC, c.comid DESC"
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    public function unreadByUser(int $userId, int $limit = 5): array
    {
        $limit = max(1, min($limit, 20));
        $statement = $this->db->prepare(
            "SELECT c.comid, c.comtitulo, c.commensaje, c.comfecha_publicacion,
                    cd.cdeid
             FROM comunicado_destinatario cd
             INNER JOIN comunicado c ON c.comid = cd.comid
             WHERE cd.usuid = :user_id
               AND cd.cdeestado_lectura = 'PENDIENTE'
               AND c.comestado = 'PUBLICADO'
             ORDER BY c.comfecha_publicacion DESC, c.comid DESC
             LIMIT {$limit}"
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    public function markReadForUser(int $userId, array $communicationIds): void
    {
        $communicationIds = array_values(array_unique(array_filter(array_map('intval', $communicationIds))));

        if ($userId <= 0 || $communicationIds === []) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($communicationIds), '?'));
        $statement = $this->db->prepare(
            "UPDATE comunicado_destinatario
             SET cdeestado_lectura = 'LEIDO',
                 cdefecha_lectura = COALESCE(cdefecha_lectura, CURRENT_TIMESTAMP),
                 cdefecha_modificacion = CURRENT_TIMESTAMP
             WHERE usuid = ?
               AND comid IN ({$placeholders})
               AND cdeestado_lectura = 'PENDIENTE'"
        );
        $statement->execute([$userId, ...$communicationIds]);
    }

    public function findDetailed(int $communicationId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT c.*, u.usunombre AS usuario_creacion
             FROM comunicado c
             INNER JOIN usuario u ON u.usuid = c.usuid_creacion
             WHERE c.comid = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $communicationId]);

        return $statement->fetch();
    }

    public function recipients(int $communicationId): array
    {
        $statement = $this->db->prepare(
            "SELECT cd.*, p.pernombres, p.perapellidos, p.percorreo, u.usunombre,
                    se.cenestado AS entrega_sistema,
                    ee.cenestado AS entrega_email,
                    ee.cendestino AS email_destino,
                    ee.cenultimo_error AS email_error,
                    we.cenestado AS entrega_whatsapp,
                    we.cendestino AS whatsapp_destino,
                    we.cenultimo_error AS whatsapp_error
             FROM comunicado_destinatario cd
             INNER JOIN usuario u ON u.usuid = cd.usuid
             INNER JOIN persona p ON p.perid = cd.perid
             LEFT JOIN comunicado_entrega se ON se.cdeid = cd.cdeid AND se.cencanal = 'SISTEMA'
             LEFT JOIN comunicado_entrega ee ON ee.cdeid = cd.cdeid AND ee.cencanal = 'EMAIL'
             LEFT JOIN comunicado_entrega we ON we.cdeid = cd.cdeid AND we.cencanal = 'WHATSAPP'
             WHERE cd.comid = :communication_id
             ORDER BY p.perapellidos ASC, p.pernombres ASC"
        );
        $statement->execute(['communication_id' => $communicationId]);

        return $statement->fetchAll();
    }

    public function createDraft(array $data, int $creatorUserId): int
    {
        return $this->saveCommunication(null, $data, $creatorUserId, false);
    }

    public function updateDraft(int $communicationId, array $data): void
    {
        $communication = $this->findDetailed($communicationId);

        if ($communication === false) {
            throw new RuntimeException('El comunicado solicitado no existe.');
        }

        if ((string) $communication['comestado'] !== 'BORRADOR') {
            throw new RuntimeException('Solo se pueden editar comunicados en borrador.');
        }

        $this->saveCommunication($communicationId, $data, (int) $communication['usuid_creacion'], false);
    }

    public function sendNew(array $data, int $creatorUserId): int
    {
        return $this->saveCommunication(null, $data, $creatorUserId, true);
    }

    public function publishDraft(int $communicationId, int $publisherUserId): void
    {
        $communication = $this->findDetailed($communicationId);

        if ($communication === false) {
            throw new RuntimeException('El comunicado solicitado no existe.');
        }

        if ((string) $communication['comestado'] !== 'BORRADOR') {
            throw new RuntimeException('Solo se pueden enviar comunicados en borrador.');
        }

        $criteria = json_decode((string) $communication['comcriterios_json'], true);
        $criteria = is_array($criteria) ? $criteria : [];

        $this->publish($communicationId, $publisherUserId, $criteria);
    }

    public function annul(int $communicationId, int $userId, string $reason): void
    {
        $communication = $this->findDetailed($communicationId);

        if ($communication === false) {
            throw new RuntimeException('El comunicado solicitado no existe.');
        }

        if ((string) $communication['comestado'] !== 'PUBLICADO') {
            throw new RuntimeException('Solo se pueden anular comunicados publicados.');
        }

        $statement = $this->db->prepare(
            "UPDATE comunicado
             SET comestado = 'ANULADO',
                 comfecha_anulacion = CURRENT_TIMESTAMP,
                 commotivo_anulacion = :reason,
                 usuid_anulacion = :user_id,
                 comfecha_modificacion = CURRENT_TIMESTAMP
             WHERE comid = :id"
        );
        $statement->execute([
            'id' => $communicationId,
            'user_id' => $userId,
            'reason' => $reason !== '' ? $reason : null,
        ]);
    }

    public function deleteDraft(int $communicationId): void
    {
        $communication = $this->findDetailed($communicationId);

        if ($communication === false) {
            throw new RuntimeException('El comunicado solicitado no existe.');
        }

        if ((string) $communication['comestado'] !== 'BORRADOR') {
            throw new RuntimeException('Solo se pueden eliminar borradores.');
        }

        $statement = $this->db->prepare('DELETE FROM comunicado WHERE comid = :id');
        $statement->execute(['id' => $communicationId]);
    }

    public function activeCourses(): array
    {
        $statement = $this->db->query(
            "SELECT c.curid,
                    n.nednombre || ' - ' || g.granombre || ' ' || p.prlnombre AS curso
             FROM curso c
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN nivel_educativo n ON n.nedid = g.nedid
             INNER JOIN paralelo p ON p.prlid = c.prlid
             WHERE c.curestado = true
             ORDER BY n.nednombre ASC, g.granombre ASC, p.prlnombre ASC"
        );

        return $statement->fetchAll();
    }

    public function activeMatriculations(): array
    {
        $statement = $this->db->query(
            "SELECT m.matid,
                    p.perapellidos || ' ' || p.pernombres || ' | ' || n.nednombre || ' - ' || g.granombre || ' ' || pr.prlnombre AS estudiante
             FROM matricula m
             INNER JOIN estudiante e ON e.estid = m.estid
             INNER JOIN persona p ON p.perid = e.perid
             INNER JOIN curso c ON c.curid = m.curid
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN nivel_educativo n ON n.nedid = g.nedid
             INNER JOIN paralelo pr ON pr.prlid = c.prlid
             INNER JOIN estado_matricula em ON em.emdid = m.emdid
             WHERE e.estestado = true
               AND LOWER(em.emdnombre) IN ('activo', 'activa', 'habilitado', 'habilitada')
             ORDER BY p.perapellidos ASC, p.pernombres ASC
             LIMIT 500"
        );

        return $statement->fetchAll();
    }

    public function searchStudents(string $term = '', int $limit = 20): array
    {
        $term = trim($term);
        $limit = max(1, min($limit, 50));
        $conditions = [
            'e.estestado = true',
            "LOWER(em.emdnombre) IN ('activo', 'activa', 'habilitado', 'habilitada')",
        ];
        $params = [];

        if ($term !== '') {
            $conditions[] = "(
                p.percedula ILIKE :term
                OR p.pernombres ILIKE :term
                OR p.perapellidos ILIKE :term
                OR n.nednombre ILIKE :term
                OR g.granombre ILIKE :term
                OR pr.prlnombre ILIKE :term
            )";
            $params['term'] = '%' . $term . '%';
        }

        $whereSql = implode(' AND ', $conditions);
        $statement = $this->db->prepare(
            "SELECT m.matid AS id,
                    p.perapellidos || ' ' || p.pernombres AS label,
                    COALESCE(p.percedula, '') || ' | ' || n.nednombre || ' - ' || g.granombre || ' ' || pr.prlnombre AS detail
             FROM matricula m
             INNER JOIN estudiante e ON e.estid = m.estid
             INNER JOIN persona p ON p.perid = e.perid
             INNER JOIN curso c ON c.curid = m.curid
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN nivel_educativo n ON n.nedid = g.nedid
             INNER JOIN paralelo pr ON pr.prlid = c.prlid
             INNER JOIN estado_matricula em ON em.emdid = m.emdid
             WHERE {$whereSql}
             ORDER BY p.perapellidos ASC, p.pernombres ASC
             LIMIT {$limit}"
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function searchRepresentatives(string $term = '', int $limit = 20): array
    {
        $term = trim($term);
        $limit = max(1, min($limit, 50));
        $conditions = ['ru.usuestado = true'];
        $params = [];

        if ($term !== '') {
            $conditions[] = "(
                rp.percedula ILIKE :term
                OR rp.pernombres ILIKE :term
                OR rp.perapellidos ILIKE :term
                OR sp.pernombres ILIKE :term
                OR sp.perapellidos ILIKE :term
            )";
            $params['term'] = '%' . $term . '%';
        }

        $whereSql = implode(' AND ', $conditions);
        $statement = $this->db->prepare(
            "SELECT DISTINCT ru.usuid AS id,
                    rp.perapellidos || ' ' || rp.pernombres AS label,
                    COALESCE(rp.percedula, '') || ' | Estudiante: ' || sp.perapellidos || ' ' || sp.pernombres AS detail
             FROM matricula_representante mr
             INNER JOIN persona rp ON rp.perid = mr.perid
             INNER JOIN usuario ru ON ru.perid = rp.perid
             INNER JOIN matricula m ON m.matid = mr.matid
             INNER JOIN estudiante e ON e.estid = m.estid
             INNER JOIN persona sp ON sp.perid = e.perid
             WHERE {$whereSql}
             ORDER BY label ASC
             LIMIT {$limit}"
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function searchStaff(string $term = '', int $limit = 20): array
    {
        $term = trim($term);
        $limit = max(1, min($limit, 50));
        $conditions = ['ps.psnestado = true', 'u.usuestado = true'];
        $params = [];

        if ($term !== '') {
            $conditions[] = "(
                p.percedula ILIKE :term
                OR p.pernombres ILIKE :term
                OR p.perapellidos ILIKE :term
                OR tp.tpnombre ILIKE :term
            )";
            $params['term'] = '%' . $term . '%';
        }

        $whereSql = implode(' AND ', $conditions);
        $statement = $this->db->prepare(
            "SELECT u.usuid AS id,
                    p.perapellidos || ' ' || p.pernombres AS label,
                    COALESCE(p.percedula, '') || ' | ' || COALESCE(string_agg(DISTINCT tp.tpnombre, ', '), 'Personal') AS detail
             FROM personal ps
             INNER JOIN persona p ON p.perid = ps.perid
             INNER JOIN usuario u ON u.perid = p.perid
             LEFT JOIN asignacion_tipo_personal atp ON atp.psnid = ps.psnid AND atp.atpestado = true
             LEFT JOIN tipo_personal tp ON tp.tpid = atp.tpid
             WHERE {$whereSql}
             GROUP BY u.usuid, p.perid, p.percedula, p.perapellidos, p.pernombres
             ORDER BY p.perapellidos ASC, p.pernombres ASC
             LIMIT {$limit}"
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function selectedUsers(array $userIds): array
    {
        $userIds = $this->positiveIds($userIds);

        if ($userIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($userIds), '?'));
        $statement = $this->db->prepare(
            "SELECT u.usuid AS id,
                    p.perapellidos || ' ' || p.pernombres AS label,
                    COALESCE(p.percedula, '') AS detail
             FROM usuario u
             INNER JOIN persona p ON p.perid = u.perid
             WHERE u.usuid IN ({$placeholders})
             ORDER BY p.perapellidos ASC, p.pernombres ASC"
        );
        $statement->execute($userIds);

        return $statement->fetchAll();
    }

    public function activeRoles(): array
    {
        $statement = $this->db->query(
            "SELECT rolid, rolnombre
             FROM rol
             WHERE rolestado = true
             ORDER BY rolnombre ASC"
        );

        return $statement->fetchAll();
    }

    private function saveCommunication(?int $communicationId, array $data, int $creatorUserId, bool $publish): int
    {
        $title = trim((string) ($data['titulo'] ?? ''));
        $message = trim((string) ($data['mensaje'] ?? ''));
        $criteria = $this->normalizeCriteria($data);
        $summary = $this->criteriaSummary($criteria);

        if ($title === '' || $message === '') {
            throw new RuntimeException('Titulo y mensaje son obligatorios.');
        }

        if ($criteria['targets'] === []) {
            throw new RuntimeException('Seleccione al menos un destinatario.');
        }

        $this->db->beginTransaction();

        try {
            if ($communicationId === null) {
                $statement = $this->db->prepare(
                    "INSERT INTO comunicado (
                        comtitulo, commensaje, comestado, comcriterios_json,
                        comdestino_resumen, usuid_creacion
                     ) VALUES (
                        :title, :message, 'BORRADOR', CAST(:criteria AS jsonb),
                        :summary, :creator_user_id
                     )
                     RETURNING comid"
                );
                $statement->execute([
                    'title' => $title,
                    'message' => $message,
                    'criteria' => json_encode($criteria, JSON_UNESCAPED_UNICODE),
                    'summary' => $summary,
                    'creator_user_id' => $creatorUserId,
                ]);
                $communicationId = (int) $statement->fetchColumn();
            } else {
                $statement = $this->db->prepare(
                    "UPDATE comunicado
                     SET comtitulo = :title,
                         commensaje = :message,
                         comcriterios_json = CAST(:criteria AS jsonb),
                         comdestino_resumen = :summary,
                         comfecha_modificacion = CURRENT_TIMESTAMP
                     WHERE comid = :id"
                );
                $statement->execute([
                    'id' => $communicationId,
                    'title' => $title,
                    'message' => $message,
                    'criteria' => json_encode($criteria, JSON_UNESCAPED_UNICODE),
                    'summary' => $summary,
                ]);
            }

            if ($publish) {
                $this->publish($communicationId, $creatorUserId, $criteria, false);
            }

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }

        return $communicationId;
    }

    private function publish(int $communicationId, int $publisherUserId, array $criteria, bool $transaction = true): void
    {
        if ($transaction) {
            $this->db->beginTransaction();
        }

        try {
            $recipients = $this->resolveRecipients($criteria);

            if ($recipients === []) {
                throw new RuntimeException('No se encontraron destinatarios activos para el comunicado.');
            }

            $update = $this->db->prepare(
                "UPDATE comunicado
                 SET comestado = 'PUBLICADO',
                     comfecha_publicacion = CURRENT_TIMESTAMP,
                     usuid_publicacion = :user_id,
                     comfecha_modificacion = CURRENT_TIMESTAMP
                 WHERE comid = :id"
            );
            $update->execute([
                'id' => $communicationId,
                'user_id' => $publisherUserId,
            ]);

            $insertRecipient = $this->db->prepare(
                "INSERT INTO comunicado_destinatario (
                    comid, usuid, perid, cdetipo, cdeorigen
                 ) VALUES (
                    :communication_id, :user_id, :person_id, :type, :origin
                 )
                 ON CONFLICT (comid, usuid) DO NOTHING
                 RETURNING cdeid"
            );
            $insertSystem = $this->db->prepare(
                "INSERT INTO comunicado_entrega (
                    comid, cdeid, cencanal, cenestado
                 ) VALUES (
                    :communication_id, :recipient_id, 'SISTEMA', 'ENVIADO'
                 )
                 ON CONFLICT (cdeid, cencanal) DO NOTHING"
            );
            $insertEmail = $this->db->prepare(
                "INSERT INTO comunicado_entrega (
                    comid, cdeid, cencanal, cendestino, cenestado
                 ) VALUES (
                    :communication_id, :recipient_id, 'EMAIL', :destination, :status
                 )
                 ON CONFLICT (cdeid, cencanal) DO NOTHING"
            );
            $insertWhatsapp = $this->db->prepare(
                "INSERT INTO comunicado_entrega (
                    comid, cdeid, cencanal, cendestino, cenestado
                 ) VALUES (
                    :communication_id, :recipient_id, 'WHATSAPP', :destination, :status
                 )
                 ON CONFLICT (cdeid, cencanal) DO NOTHING"
            );

            foreach ($recipients as $recipient) {
                $insertRecipient->execute([
                    'communication_id' => $communicationId,
                    'user_id' => $recipient['usuid'],
                    'person_id' => $recipient['perid'],
                    'type' => $recipient['type'],
                    'origin' => $recipient['origin'],
                ]);
                $recipientId = (int) $insertRecipient->fetchColumn();

                if ($recipientId <= 0) {
                    continue;
                }

                $insertSystem->execute([
                    'communication_id' => $communicationId,
                    'recipient_id' => $recipientId,
                ]);

                $email = trim((string) ($recipient['email'] ?? ''));
                $insertEmail->execute([
                    'communication_id' => $communicationId,
                    'recipient_id' => $recipientId,
                    'destination' => $email !== '' ? $email : null,
                    'status' => $email !== '' ? 'PENDIENTE' : 'OMITIDO',
                ]);

                $whatsapp = trim((string) ($recipient['whatsapp'] ?? ''));
                $insertWhatsapp->execute([
                    'communication_id' => $communicationId,
                    'recipient_id' => $recipientId,
                    'destination' => $whatsapp !== '' ? $whatsapp : null,
                    'status' => $whatsapp !== '' ? 'PENDIENTE' : 'OMITIDO',
                ]);
            }

            if ($transaction) {
                $this->db->commit();
            }
        } catch (\Throwable $exception) {
            if ($transaction) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    private function normalizeCriteria(array $data): array
    {
        $targetType = strtoupper(trim((string) ($data['target_type'] ?? '')));
        $legacyTargets = array_values(array_unique(array_map('strval', (array) ($data['targets'] ?? []))));

        if ($targetType === '' && $legacyTargets !== []) {
            $targetType = (string) $legacyTargets[0];
        }

        $targetType = match ($targetType) {
            'ESTUDIANTES' => 'MATRICULAS',
            'REPRESENTANTES' => 'REPRESENTANTES',
            'PERSONAL' => 'PERSONAL',
            'TODOS' => 'TODOS',
            'CURSOS' => 'CURSOS',
            'MATRICULAS' => 'MATRICULAS',
            'ROLES' => 'ROLES',
            'DOCENTES' => 'PERSONAL',
            'ADMINISTRATIVOS' => 'PERSONAL',
            default => '',
        };

        $targets = $targetType !== '' ? [$targetType] : [];
        $courseIds = $targetType === 'CURSOS' ? $this->positiveIds((array) ($data['course_ids'] ?? [])) : [];
        $matriculationIds = $targetType === 'MATRICULAS' ? $this->positiveIds((array) ($data['matriculation_ids'] ?? [])) : [];
        $representativeUserIds = $targetType === 'REPRESENTANTES' ? $this->positiveIds((array) ($data['representative_user_ids'] ?? [])) : [];
        $staffUserIds = $targetType === 'PERSONAL' ? $this->positiveIds((array) ($data['staff_user_ids'] ?? [])) : [];
        $roleIds = $targetType === 'ROLES' ? $this->positiveIds((array) ($data['role_ids'] ?? [])) : [];

        if ($targetType === 'CURSOS' && $courseIds === []) {
            throw new RuntimeException('Seleccione al menos un curso.');
        }

        if ($targetType === 'MATRICULAS' && $matriculationIds === []) {
            throw new RuntimeException('Seleccione al menos un estudiante.');
        }

        if ($targetType === 'REPRESENTANTES' && $representativeUserIds === []) {
            throw new RuntimeException('Seleccione al menos un representante.');
        }

        if ($targetType === 'PERSONAL' && $staffUserIds === []) {
            throw new RuntimeException('Seleccione al menos una persona del personal.');
        }

        return [
            'target_type' => $targetType,
            'targets' => $targets,
            'course_ids' => $courseIds,
            'matriculation_ids' => $matriculationIds,
            'representative_user_ids' => $representativeUserIds,
            'staff_user_ids' => $staffUserIds,
            'role_ids' => $roleIds,
        ];
    }

    private function criteriaSummary(array $criteria): string
    {
        $parts = [];

        foreach ((array) ($criteria['targets'] ?? []) as $target) {
            $parts[] = match ($target) {
                'TODOS' => 'Todos los usuarios activos',
                'CURSOS' => 'Cursos seleccionados',
                'MATRICULAS' => 'Matriculas seleccionadas',
                'REPRESENTANTES' => 'Representantes seleccionados',
                'PERSONAL' => 'Personal seleccionado',
                'ROLES' => 'Roles seleccionados',
                'DOCENTES' => 'Docentes',
                'ADMINISTRATIVOS' => 'Administrativos',
                default => 'Destinatarios',
            };
        }

        return implode(', ', array_unique($parts));
    }

    private function positiveIds(array $values): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $values), static fn (int $id): bool => $id > 0)));
    }

    private function resolveRecipients(array $criteria): array
    {
        $recipients = [];
        $targets = (array) ($criteria['targets'] ?? []);

        if (in_array('TODOS', $targets, true)) {
            $this->mergeRecipients($recipients, $this->usersByAllActive(), 'USUARIO', 'TODOS');
        }

        if (in_array('CURSOS', $targets, true)) {
            $this->mergeRecipients($recipients, $this->usersByCourses((array) ($criteria['course_ids'] ?? [])), 'MATRICULA', 'CURSO');
        }

        if (in_array('MATRICULAS', $targets, true)) {
            $this->mergeRecipients($recipients, $this->usersByMatriculations((array) ($criteria['matriculation_ids'] ?? [])), 'MATRICULA', 'MATRICULA');
        }

        if (in_array('ROLES', $targets, true)) {
            $this->mergeRecipients($recipients, $this->usersByRoles((array) ($criteria['role_ids'] ?? [])), 'USUARIO', 'ROL');
        }

        if (in_array('REPRESENTANTES', $targets, true)) {
            $this->mergeRecipients($recipients, $this->usersByUserIds((array) ($criteria['representative_user_ids'] ?? [])), 'REPRESENTANTE', 'REPRESENTANTE');
        }

        if (in_array('PERSONAL', $targets, true)) {
            $this->mergeRecipients($recipients, $this->usersByUserIds((array) ($criteria['staff_user_ids'] ?? [])), 'PERSONAL', 'PERSONAL');
        }

        if (in_array('DOCENTES', $targets, true)) {
            $this->mergeRecipients($recipients, $this->usersByStaffType(['Docente']), 'DOCENTE', 'DOCENTES');
        }

        if (in_array('ADMINISTRATIVOS', $targets, true)) {
            $this->mergeRecipients($recipients, $this->usersByStaffType(['Secretaria', 'Rector', 'Vicerrector', 'Coordinador', 'Inspector', 'DECE']), 'ADMINISTRATIVO', 'ADMINISTRATIVOS');
        }

        return array_values($recipients);
    }

    private function mergeRecipients(array &$recipients, array $rows, string $type, string $origin): void
    {
        foreach ($rows as $row) {
            $userId = (int) ($row['usuid'] ?? 0);
            $personId = (int) ($row['perid'] ?? 0);

            if ($userId <= 0 || $personId <= 0 || isset($recipients[$userId])) {
                continue;
            }

            $recipients[$userId] = [
                'usuid' => $userId,
                'perid' => $personId,
                'email' => filter_var((string) ($row['percorreo'] ?? ''), FILTER_VALIDATE_EMAIL) !== false
                    ? trim((string) $row['percorreo'])
                    : '',
                'whatsapp' => $this->whatsappDestination(
                    (string) ($row['pertelefono1'] ?? ''),
                    (string) ($row['pertelefono2'] ?? '')
                ),
                'type' => $type,
                'origin' => $origin,
            ];
        }
    }

    private function whatsappDestination(string $phone1, string $phone2): string
    {
        $primary = $this->normalizeEcuadorMobile($phone1);

        if ($primary !== '') {
            return $primary;
        }

        return $this->normalizeEcuadorMobile($phone2);
    }

    private function normalizeEcuadorMobile(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', trim($phone)) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '593')) {
            $national = '0' . substr($digits, 3);
        } else {
            $national = $digits;
        }

        if (strlen($national) === 10 && str_starts_with($national, '09')) {
            return '+593' . substr($national, 1);
        }

        return '';
    }

    private function usersByAllActive(): array
    {
        $statement = $this->db->query(
            "SELECT u.usuid, u.perid, p.percorreo, p.pertelefono1, p.pertelefono2
             FROM usuario u
             INNER JOIN persona p ON p.perid = u.perid
             WHERE u.usuestado = true"
        );

        return $statement->fetchAll();
    }

    private function usersByRoles(array $roleIds): array
    {
        $roleIds = $this->positiveIds($roleIds);

        if ($roleIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($roleIds), '?'));
        $statement = $this->db->prepare(
            "SELECT DISTINCT u.usuid, u.perid, p.percorreo, p.pertelefono1, p.pertelefono2
             FROM usuario u
             INNER JOIN persona p ON p.perid = u.perid
             INNER JOIN usuario_rol ur ON ur.usuid = u.usuid
             INNER JOIN rol r ON r.rolid = ur.rolid
             WHERE u.usuestado = true
               AND ur.usrestado = true
               AND r.rolestado = true
               AND r.rolid IN ({$placeholders})"
        );
        $statement->execute($roleIds);

        return $statement->fetchAll();
    }

    private function usersByUserIds(array $userIds): array
    {
        $userIds = $this->positiveIds($userIds);

        if ($userIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($userIds), '?'));
        $statement = $this->db->prepare(
            "SELECT u.usuid, u.perid, p.percorreo, p.pertelefono1, p.pertelefono2
             FROM usuario u
             INNER JOIN persona p ON p.perid = u.perid
             WHERE u.usuestado = true
               AND u.usuid IN ({$placeholders})"
        );
        $statement->execute($userIds);

        return $statement->fetchAll();
    }

    private function usersByStaffType(array $typeNames): array
    {
        if ($typeNames === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($typeNames), '?'));
        $statement = $this->db->prepare(
            "SELECT DISTINCT u.usuid, u.perid, p.percorreo, p.pertelefono1, p.pertelefono2
             FROM personal ps
             INNER JOIN persona p ON p.perid = ps.perid
             INNER JOIN usuario u ON u.perid = p.perid
             INNER JOIN asignacion_tipo_personal atp ON atp.psnid = ps.psnid
             INNER JOIN tipo_personal tp ON tp.tpid = atp.tpid
             WHERE ps.psnestado = true
               AND atp.atpestado = true
               AND u.usuestado = true
               AND tp.tpnombre IN ({$placeholders})"
        );
        $statement->execute($typeNames);

        return $statement->fetchAll();
    }

    private function usersByCourses(array $courseIds): array
    {
        $courseIds = $this->positiveIds($courseIds);

        if ($courseIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($courseIds), '?'));
        $statement = $this->db->prepare($this->matriculationRecipientSql("m.curid IN ({$placeholders})"));
        $statement->execute(array_merge($courseIds, $courseIds));

        return $statement->fetchAll();
    }

    private function usersByMatriculations(array $matriculationIds): array
    {
        $matriculationIds = $this->positiveIds($matriculationIds);

        if ($matriculationIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($matriculationIds), '?'));
        $statement = $this->db->prepare($this->matriculationRecipientSql("m.matid IN ({$placeholders})"));
        $statement->execute(array_merge($matriculationIds, $matriculationIds));

        return $statement->fetchAll();
    }

    private function matriculationRecipientSql(string $where): string
    {
        return
            "SELECT DISTINCT student_user.usuid, student_user.perid, student_person.percorreo,
                    student_person.pertelefono1, student_person.pertelefono2
             FROM matricula m
             INNER JOIN estudiante e ON e.estid = m.estid
             INNER JOIN persona student_person ON student_person.perid = e.perid
             INNER JOIN usuario student_user ON student_user.perid = student_person.perid
             INNER JOIN estado_matricula em ON em.emdid = m.emdid
             WHERE {$where}
               AND e.estestado = true
               AND student_user.usuestado = true
               AND LOWER(em.emdnombre) IN ('activo', 'activa', 'habilitado', 'habilitada')
             UNION
             SELECT DISTINCT representative_user.usuid, representative_user.perid, representative_person.percorreo,
                    representative_person.pertelefono1, representative_person.pertelefono2
             FROM matricula m
             INNER JOIN estudiante e ON e.estid = m.estid
             INNER JOIN estado_matricula em ON em.emdid = m.emdid
             INNER JOIN matricula_representante mr ON mr.matid = m.matid
             INNER JOIN persona representative_person ON representative_person.perid = mr.perid
             INNER JOIN usuario representative_user ON representative_user.perid = representative_person.perid
             WHERE {$where}
               AND e.estestado = true
               AND representative_user.usuestado = true
               AND LOWER(em.emdnombre) IN ('activo', 'activa', 'habilitado', 'habilitada')";
    }
}
