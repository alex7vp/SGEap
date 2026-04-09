<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDOException;
use RuntimeException;

class SecurityCatalogModel extends Model
{
    private array $catalogMap = [
        'rol' => [
            'id' => 'rolid',
            'label' => 'Roles',
            'description' => 'Catalogo para perfiles y agrupacion de permisos del sistema.',
            'modified_column' => 'rolfecha_modificacion',
            'fields' => [
                [
                    'name' => 'rolnombre',
                    'label' => 'Nombre',
                    'type' => 'text',
                    'required' => true,
                    'unique' => true,
                ],
                [
                    'name' => 'roldescripcion',
                    'label' => 'Descripcion',
                    'type' => 'text',
                    'required' => false,
                    'unique' => false,
                ],
                [
                    'name' => 'rolestado',
                    'label' => 'Estado',
                    'type' => 'bool',
                    'required' => true,
                    'unique' => false,
                ],
            ],
        ],
        'permiso' => [
            'id' => 'prmid',
            'label' => 'Permisos',
            'description' => 'Catalogo de permisos funcionales y codigos de control de acceso.',
            'modified_column' => 'prmfecha_modificacion',
            'fields' => [
                [
                    'name' => 'prmnombre',
                    'label' => 'Nombre',
                    'type' => 'text',
                    'required' => true,
                    'unique' => true,
                ],
                [
                    'name' => 'prmcodigo',
                    'label' => 'Codigo',
                    'type' => 'text',
                    'required' => true,
                    'unique' => true,
                ],
                [
                    'name' => 'prmdescripcion',
                    'label' => 'Descripcion',
                    'type' => 'text',
                    'required' => false,
                    'unique' => false,
                ],
                [
                    'name' => 'prmestado',
                    'label' => 'Estado',
                    'type' => 'bool',
                    'required' => true,
                    'unique' => false,
                ],
            ],
        ],
    ];

    public function allCatalogs(): array
    {
        $catalogs = [];

        foreach ($this->catalogMap as $table => $config) {
            $columns = array_map(
                static fn(array $field): string => $field['name'],
                $config['fields']
            );

            $statement = $this->db->query(
                "SELECT {$config['id']} AS id, " . implode(', ', $columns) . "
                 FROM {$table}
                 ORDER BY {$config['id']} ASC"
            );

            $catalogs[] = [
                'table' => $table,
                'id_column' => $config['id'],
                'label' => $config['label'],
                'description' => $config['description'],
                'fields' => $config['fields'],
                'rows' => $statement->fetchAll(),
            ];
        }

        return $catalogs;
    }

    public function getCatalog(string $table): array
    {
        $config = $this->catalogMap[$table] ?? null;

        if ($config === null) {
            throw new RuntimeException('Catalogo de seguridad no valido.');
        }

        return $config + ['table' => $table];
    }

    public function sanitizePayload(string $table, array $source): array
    {
        $catalog = $this->getCatalog($table);
        $payload = [];

        foreach ($catalog['fields'] as $field) {
            $name = $field['name'];

            if ($field['type'] === 'bool') {
                $payload[$name] = ($source[$name] ?? '1') === '1';
                continue;
            }

            $payload[$name] = trim((string) ($source[$name] ?? ''));
        }

        return $payload;
    }

    public function validatePayload(string $table, array $payload, ?int $exceptId = null): ?string
    {
        $catalog = $this->getCatalog($table);

        foreach ($catalog['fields'] as $field) {
            $name = $field['name'];

            if (($field['required'] ?? false) && $field['type'] !== 'bool' && $payload[$name] === '') {
                return 'El campo ' . strtolower((string) $field['label']) . ' es obligatorio.';
            }

            if (($field['unique'] ?? false) && $this->existsByFieldValue($table, $name, (string) $payload[$name], $exceptId)) {
                return 'Ya existe un registro con el mismo ' . strtolower((string) $field['label']) . '.';
            }
        }

        return null;
    }

    public function createItem(string $table, array $payload): void
    {
        $catalog = $this->getCatalog($table);
        $columns = [];
        $values = [];
        $params = [];

        foreach ($catalog['fields'] as $field) {
            $columns[] = $field['name'];
            $values[] = ':' . $field['name'];
            $params[$field['name']] = $payload[$field['name']];
        }

        $statement = $this->db->prepare(
            "INSERT INTO {$catalog['table']} (" . implode(', ', $columns) . ")
             VALUES (" . implode(', ', $values) . ")"
        );
        $statement->execute($params);
    }

    public function updateItem(string $table, int $id, array $payload): void
    {
        $catalog = $this->getCatalog($table);
        $sets = [];
        $params = ['id' => $id];

        foreach ($catalog['fields'] as $field) {
            $sets[] = $field['name'] . ' = :' . $field['name'];
            $params[$field['name']] = $payload[$field['name']];
        }

        if (!empty($catalog['modified_column'])) {
            $sets[] = $catalog['modified_column'] . ' = CURRENT_TIMESTAMP';
        }

        $statement = $this->db->prepare(
            "UPDATE {$catalog['table']}
             SET " . implode(', ', $sets) . "
             WHERE {$catalog['id']} = :id"
        );
        $statement->execute($params);
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

    private function existsByFieldValue(string $table, string $fieldName, string $value, ?int $exceptId = null): bool
    {
        $catalog = $this->getCatalog($table);
        $sql =
            "SELECT 1
             FROM {$catalog['table']}
             WHERE {$fieldName} = :value";

        $params = ['value' => $value];

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
