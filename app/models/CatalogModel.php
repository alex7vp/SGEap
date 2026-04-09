<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDOException;
use RuntimeException;

class CatalogModel extends Model
{
    private array $catalogMap = [
        'paralelo' => [
            'id' => 'prlid',
            'name' => 'prlnombre',
            'label' => 'Paralelos',
            'description' => 'Catalogo para paralelos academicos.',
        ],
        'nivel_educativo' => [
            'id' => 'nedid',
            'name' => 'nednombre',
            'label' => 'Niveles educativos',
            'description' => 'Base para organizar niveles de estudio.',
        ],
        'parentesco' => [
            'id' => 'pteid',
            'name' => 'ptenombre',
            'label' => 'Parentescos',
            'description' => 'Tipos de relacion familiar o representacion.',
        ],
        'estado_civil' => [
            'id' => 'eciid',
            'name' => 'ecinombre',
            'label' => 'Estados civiles',
            'description' => 'Estados civiles disponibles para personas y familiares.',
        ],
        'instruccion' => [
            'id' => 'istid',
            'name' => 'istnombre',
            'label' => 'Instruccion',
            'description' => 'Nivel de instruccion registrado para familiares o personal.',
        ],
        'estado_matricula' => [
            'id' => 'emdid',
            'name' => 'emdnombre',
            'label' => 'Estados de matricula',
            'description' => 'Estados usados dentro del proceso de matriculacion.',
        ],
        'tipo_personal' => [
            'id' => 'tpid',
            'name' => 'tpnombre',
            'label' => 'Tipos de personal',
            'description' => 'Catalogo para clasificar al personal institucional.',
        ],
    ];

    public function allCatalogs(): array
    {
        $catalogs = [];

        foreach ($this->catalogMap as $table => $config) {
            $statement = $this->db->query(
                "SELECT {$config['id']} AS id, {$config['name']} AS name
                 FROM {$table}
                 ORDER BY {$config['id']} ASC"
            );

            $catalogs[] = [
                'table' => $table,
                'id_column' => $config['id'],
                'name_column' => $config['name'],
                'label' => $config['label'],
                'description' => $config['description'],
                'rows' => $statement->fetchAll(),
            ];
        }

        return $catalogs;
    }

    public function getCatalog(string $table): array
    {
        $config = $this->catalogMap[$table] ?? null;

        if ($config === null) {
            throw new RuntimeException('Catalogo no valido.');
        }

        return $config + ['table' => $table];
    }

    public function createItem(string $table, string $name): void
    {
        $catalog = $this->getCatalog($table);
        $statement = $this->db->prepare(
            "INSERT INTO {$catalog['table']} ({$catalog['name']})
             VALUES (:name)"
        );
        $statement->execute(['name' => $name]);
    }

    public function updateItem(string $table, int $id, string $name): void
    {
        $catalog = $this->getCatalog($table);
        $statement = $this->db->prepare(
            "UPDATE {$catalog['table']}
             SET {$catalog['name']} = :name
             WHERE {$catalog['id']} = :id"
        );
        $statement->execute([
            'id' => $id,
            'name' => $name,
        ]);
    }

    public function deleteItem(string $table, int $id): bool
    {
        $catalog = $this->getCatalog($table);

        try {
            $statement = $this->db->prepare(
                "DELETE FROM {$catalog['table']}
                 WHERE {$catalog['id']} = :id"
            );
            $statement->execute(['id' => $id]);

            return $statement->rowCount() > 0;
        } catch (PDOException) {
            return false;
        }
    }

    public function existsByName(string $table, string $name, ?int $exceptId = null): bool
    {
        $catalog = $this->getCatalog($table);
        $sql =
            "SELECT 1
             FROM {$catalog['table']}
             WHERE {$catalog['name']} = :name";

        $params = ['name' => $name];

        if ($exceptId !== null) {
            $sql .= " AND {$catalog['id']} <> :id";
            $params['id'] = $exceptId;
        }

        $sql .= ' LIMIT 1';

        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchColumn() !== false;
    }
}
