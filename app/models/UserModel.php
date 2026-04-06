<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class UserModel extends Model
{
    protected string $table = 'usuario';
    protected string $primaryKey = 'usuid';

    public function findActiveByUsername(string $username): array|false
    {
        $statement = $this->db->prepare(
            "SELECT usuid, perid, usunombre, usuclave, usuestado
             FROM {$this->table}
             WHERE usunombre = :username
               AND usuestado = true
             LIMIT 1"
        );
        $statement->execute(['username' => $username]);

        return $statement->fetch();
    }

    public function updateLastAccess(int $userId): void
    {
        $statement = $this->db->prepare(
            "UPDATE {$this->table}
             SET usufecha_modificacion = CURRENT_TIMESTAMP
             WHERE {$this->primaryKey} = :id"
        );
        $statement->execute(['id' => $userId]);
    }
}
