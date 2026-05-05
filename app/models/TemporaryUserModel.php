<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use DateTimeInterface;

class TemporaryUserModel extends Model
{
    protected string $table = 'usuario_temporal';
    protected string $primaryKey = 'utid';

    public function allDetailed(): array
    {
        $this->markExpiredAccesses();

        $statement = $this->db->query(
            "SELECT
                ut.utid,
                ut.usuid,
                ut.utestado,
                ut.utfecha_expiracion,
                ut.utfecha_eliminacion,
                ut.utmotivo_eliminacion,
                u.usunombre,
                u.usuestado,
                p.perid,
                p.percedula,
                p.pernombres,
                p.perapellidos
             FROM {$this->table} ut
             INNER JOIN usuario u ON u.usuid = ut.usuid
             INNER JOIN persona p ON p.perid = u.perid
             ORDER BY
                CASE ut.utestado
                    WHEN 'ACTIVO' THEN 1
                    WHEN 'EXPIRADO' THEN 2
                    WHEN 'ELIMINADO' THEN 3
                    ELSE 4
                END,
                ut.utfecha_expiracion ASC,
                p.perapellidos ASC,
                p.pernombres ASC"
        );

        return $statement->fetchAll();
    }

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

    public function existsByUser(int $userId, array $states = []): bool
    {
        $params = ['user_id' => $userId];
        $stateSql = '';

        if ($states !== []) {
            $placeholders = [];

            foreach (array_values($states) as $index => $state) {
                $key = 'state_' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = (string) $state;
            }

            $stateSql = ' AND utestado IN (' . implode(', ', $placeholders) . ')';
        }

        $statement = $this->db->prepare(
            "SELECT 1
             FROM {$this->table}
             WHERE usuid = :user_id
             {$stateSql}
             LIMIT 1"
        );
        $statement->execute($params);

        return $statement->fetchColumn() !== false;
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
                   AND utestado IN ('ACTIVO', 'EXPIRADO')"
            );
            $statement->execute([
                'user_id' => $userId,
                'reason' => trim($reason),
            ]);

            if ($statement->rowCount() === 0) {
                throw new \RuntimeException('El usuario temporal no puede ser eliminado.');
            }

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

    public function updateExpiration(int $userId, DateTimeInterface|string $expiresAt): void
    {
        $this->db->beginTransaction();

        try {
            $statement = $this->db->prepare(
                "UPDATE {$this->table}
                 SET utestado = 'ACTIVO',
                     utfecha_expiracion = :expires_at,
                     utfecha_eliminacion = NULL,
                     utmotivo_eliminacion = NULL,
                     utfecha_modificacion = CURRENT_TIMESTAMP
                 WHERE usuid = :user_id
                   AND utestado IN ('ACTIVO', 'EXPIRADO')"
            );
            $statement->execute([
                'user_id' => $userId,
                'expires_at' => $this->dateTimeValue($expiresAt),
            ]);

            if ($statement->rowCount() === 0) {
                throw new \RuntimeException('El usuario temporal no puede ser actualizado.');
            }

            $userStatement = $this->db->prepare(
                'UPDATE usuario
                 SET usuestado = true,
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

    private function dateTimeValue(DateTimeInterface|string $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return $value;
    }
}
