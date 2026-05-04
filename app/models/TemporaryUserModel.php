<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use DateTimeInterface;

class TemporaryUserModel extends Model
{
    protected string $table = 'usuario_temporal';
    protected string $primaryKey = 'utid';

    public function create(int $userId, DateTimeInterface|string $expiresAt): int
    {
        $statement = $this->db->prepare(
            "INSERT INTO {$this->table} (usuid, utestado, utfecha_expiracion)
             VALUES (:user_id, 'ACTIVO', :expires_at)
             RETURNING {$this->primaryKey}"
        );
        $statement->execute([
            'user_id' => $userId,
            'expires_at' => $this->dateTimeValue($expiresAt),
        ]);

        return (int) $statement->fetchColumn();
    }

    public function activeByUser(int $userId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT utid, usuid, utestado, utfecha_expiracion, utfecha_eliminacion, utmotivo_eliminacion
             FROM {$this->table}
             WHERE usuid = :user_id
               AND utestado = 'ACTIVO'
               AND utfecha_expiracion >= CURRENT_TIMESTAMP
             LIMIT 1"
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetch();
    }

    public function markExpiredAccesses(): void
    {
        $this->db->exec(
            "UPDATE {$this->table}
             SET utestado = 'EXPIRADO',
                 utfecha_modificacion = CURRENT_TIMESTAMP
             WHERE utestado = 'ACTIVO'
               AND utfecha_expiracion < CURRENT_TIMESTAMP"
        );
    }

    public function deleteAccess(int $userId, string $reason = ''): void
    {
        $this->db->beginTransaction();

        try {
            $statement = $this->db->prepare(
                "UPDATE {$this->table}
                 SET utestado = 'ELIMINADO',
                     utfecha_eliminacion = CURRENT_TIMESTAMP,
                     utmotivo_eliminacion = NULLIF(:reason, ''),
                     utfecha_modificacion = CURRENT_TIMESTAMP
                 WHERE usuid = :user_id
                   AND utestado <> 'ELIMINADO'"
            );
            $statement->execute([
                'user_id' => $userId,
                'reason' => trim($reason),
            ]);

            $userStatement = $this->db->prepare(
                'UPDATE usuario
                 SET usuestado = false,
                     usufecha_modificacion = CURRENT_TIMESTAMP
                 WHERE usuid = :user_id'
            );
            $userStatement->execute(['user_id' => $userId]);

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function convertAccess(int $userId): void
    {
        $statement = $this->db->prepare(
            "UPDATE {$this->table}
             SET utestado = 'CONVERTIDO',
                 utfecha_modificacion = CURRENT_TIMESTAMP
             WHERE usuid = :user_id
               AND utestado <> 'CONVERTIDO'"
        );
        $statement->execute(['user_id' => $userId]);
    }

    private function dateTimeValue(DateTimeInterface|string $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return $value;
    }
}
