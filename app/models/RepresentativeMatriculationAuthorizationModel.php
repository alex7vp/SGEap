<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use DateTimeInterface;

class RepresentativeMatriculationAuthorizationModel extends Model
{
    protected string $table = 'representante_habilitacion_matricula';
    protected string $primaryKey = 'rhmid';

    public function allRepresentativesWithAuthorization(?int $periodId): array
    {
        $this->markExpiredAuthorizations();
        $periodFilter = $periodId !== null ? 'AND h.pleid = :period_id' : '';

        $statement = $this->db->prepare(
            "SELECT
                u.usuid,
                u.usunombre,
                u.usuestado,
                p.perid,
                p.percedula,
                p.pernombres,
                p.perapellidos,
                rhm.rhmid,
                rhm.rhmestado,
                rhm.rhmfecha_expiracion,
                rhm.rhmfecha_uso,
                rhm.rhmfecha_anulacion,
                rhm.rhmobservacion
             FROM usuario u
             INNER JOIN persona p ON p.perid = u.perid
             INNER JOIN usuario_rol ur ON ur.usuid = u.usuid
             INNER JOIN rol r ON r.rolid = ur.rolid
             LEFT JOIN LATERAL (
                 SELECT h.*
                 FROM {$this->table} h
                 WHERE h.usuid = u.usuid
                   {$periodFilter}
                 ORDER BY
                    CASE h.rhmestado
                        WHEN 'ACTIVO' THEN 1
                        WHEN 'USADO' THEN 2
                        WHEN 'EXPIRADO' THEN 3
                        WHEN 'ANULADO' THEN 4
                        ELSE 5
                    END,
                    h.rhmfecha_creacion DESC
                 LIMIT 1
             ) rhm ON true
             WHERE r.rolnombre = 'Representante'
               AND ur.usrestado = true
             ORDER BY p.perapellidos ASC, p.pernombres ASC"
        );
        $statement->execute($periodId !== null ? ['period_id' => $periodId] : []);

        return $statement->fetchAll();
    }

    public function activeByUserAndPeriod(int $userId, int $periodId): array|false
    {
        $this->markExpiredAuthorizations();

        $statement = $this->db->prepare(
            "SELECT rhmid, usuid, pleid, rhmestado, rhmfecha_expiracion, rhmobservacion
             FROM {$this->table}
             WHERE usuid = :user_id
               AND pleid = :period_id
               AND rhmestado = 'ACTIVO'
               AND (rhmfecha_expiracion IS NULL OR rhmfecha_expiracion >= CURRENT_TIMESTAMP)
             LIMIT 1"
        );
        $statement->execute([
            'user_id' => $userId,
            'period_id' => $periodId,
        ]);

        return $statement->fetch();
    }

    public function createForUser(int $userId, int $periodId, int $secretaryUserId, ?DateTimeInterface $expiresAt, string $observation = ''): int
    {
        $existing = $this->activeByUserAndPeriod($userId, $periodId);

        if ($existing !== false) {
            return (int) $existing['rhmid'];
        }

        $statement = $this->db->prepare(
            "INSERT INTO {$this->table} (
                usuid, pleid, rhmestado, rhmfecha_expiracion, rhmusuid_secretaria, rhmobservacion
             ) VALUES (
                :user_id, :period_id, 'ACTIVO', :expires_at, :secretary_user_id, NULLIF(:observation, '')
             )
             RETURNING {$this->primaryKey}"
        );
        $statement->execute([
            'user_id' => $userId,
            'period_id' => $periodId,
            'expires_at' => $expiresAt?->format('Y-m-d H:i:s'),
            'secretary_user_id' => $secretaryUserId,
            'observation' => trim($observation),
        ]);

        return (int) $statement->fetchColumn();
    }

    public function useActive(int $userId, int $periodId): void
    {
        $statement = $this->db->prepare(
            "UPDATE {$this->table}
             SET rhmestado = 'USADO',
                 rhmfecha_uso = CURRENT_TIMESTAMP,
                 rhmfecha_modificacion = CURRENT_TIMESTAMP
             WHERE usuid = :user_id
               AND pleid = :period_id
               AND rhmestado = 'ACTIVO'
               AND (rhmfecha_expiracion IS NULL OR rhmfecha_expiracion >= CURRENT_TIMESTAMP)"
        );
        $statement->execute([
            'user_id' => $userId,
            'period_id' => $periodId,
        ]);

        if ($statement->rowCount() === 0) {
            throw new \RuntimeException('La habilitacion para matricular un nuevo estudiante ya no esta activa.');
        }
    }

    public function useById(int $authorizationId): void
    {
        $statement = $this->db->prepare(
            "UPDATE {$this->table}
             SET rhmestado = 'USADO',
                 rhmfecha_uso = CURRENT_TIMESTAMP,
                 rhmfecha_modificacion = CURRENT_TIMESTAMP
             WHERE rhmid = :authorization_id
               AND rhmestado = 'ACTIVO'"
        );
        $statement->execute(['authorization_id' => $authorizationId]);

        if ($statement->rowCount() === 0) {
            throw new \RuntimeException('La habilitacion para matricular un nuevo estudiante ya no esta activa.');
        }
    }

    public function annul(int $authorizationId, int $secretaryUserId, string $reason = ''): void
    {
        $statement = $this->db->prepare(
            "UPDATE {$this->table}
             SET rhmestado = 'ANULADO',
                 rhmfecha_anulacion = CURRENT_TIMESTAMP,
                 rhmmotivo_anulacion = NULLIF(:reason, ''),
                 rhmusuid_secretaria = :secretary_user_id,
                 rhmfecha_modificacion = CURRENT_TIMESTAMP
             WHERE rhmid = :authorization_id
               AND rhmestado = 'ACTIVO'"
        );
        $statement->execute([
            'authorization_id' => $authorizationId,
            'secretary_user_id' => $secretaryUserId,
            'reason' => trim($reason),
        ]);

        if ($statement->rowCount() === 0) {
            throw new \RuntimeException('La habilitacion seleccionada ya no puede anularse.');
        }
    }

    public function markExpiredAuthorizations(): void
    {
        $this->db->exec(
            "UPDATE {$this->table}
             SET rhmestado = 'EXPIRADO',
                 rhmfecha_modificacion = CURRENT_TIMESTAMP
             WHERE rhmestado = 'ACTIVO'
               AND rhmfecha_expiracion IS NOT NULL
               AND rhmfecha_expiracion < CURRENT_TIMESTAMP"
        );
        $this->pruneNewStudentRoleWithoutActiveAuthorization();
    }

    private function pruneNewStudentRoleWithoutActiveAuthorization(): void
    {
        $this->db->exec(
            "DELETE FROM usuario_rol ur
             USING rol r
             WHERE ur.rolid = r.rolid
               AND r.rolnombre = 'Representante matricula nueva'
               AND NOT EXISTS (
                   SELECT 1
                   FROM {$this->table} h
                   WHERE h.usuid = ur.usuid
                     AND h.rhmestado = 'ACTIVO'
                     AND (h.rhmfecha_expiracion IS NULL OR h.rhmfecha_expiracion >= CURRENT_TIMESTAMP)
               )"
        );
    }
}
