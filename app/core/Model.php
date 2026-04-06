<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

abstract class Model
{
    protected PDO $db;
    protected string $table = '';
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(): array
    {
        $statement = $this->db->query("SELECT * FROM {$this->table}");
        return $statement->fetchAll();
    }

    public function find(int|string $id): array|false
    {
        $statement = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1"
        );
        $statement->execute(['id' => $id]);

        return $statement->fetch();
    }
}
