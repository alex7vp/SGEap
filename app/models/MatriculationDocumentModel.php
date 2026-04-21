<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDOException;

class MatriculationDocumentModel extends Model
{
    protected string $table = 'documento_matricula';
    protected string $primaryKey = 'domid';

    public function allOrdered(): array
    {
        $statement = $this->db->query(
            "SELECT domid, domnombre, domdescripcion, domorigen, domurl, domobligatorio, domactivo, domfecha_creacion, domfecha_modificacion
             FROM {$this->table}
             ORDER BY domobligatorio DESC, domactivo DESC, domnombre ASC"
        );

        return $statement->fetchAll();
    }

    public function create(array $data): void
    {
        $statement = $this->db->prepare(
            "INSERT INTO {$this->table} (
                domnombre, domdescripcion, domorigen, domurl, domobligatorio, domactivo
             ) VALUES (
                :nombre, :descripcion, :origen, :url, :obligatorio, :activo
             )"
        );
        $statement->execute([
            'nombre' => $data['domnombre'],
            'descripcion' => $data['domdescripcion'] !== '' ? $data['domdescripcion'] : null,
            'origen' => $data['domorigen'],
            'url' => $data['domurl'],
            'obligatorio' => !empty($data['domobligatorio']),
            'activo' => !empty($data['domactivo']),
        ]);
    }

    public function updateDocument(int $id, array $data): void
    {
        $statement = $this->db->prepare(
            "UPDATE {$this->table}
             SET domnombre = :nombre,
                 domdescripcion = :descripcion,
                 domorigen = :origen,
                 domurl = :url,
                 domobligatorio = :obligatorio,
                 domactivo = :activo,
                 domfecha_modificacion = CURRENT_TIMESTAMP
             WHERE {$this->primaryKey} = :id"
        );
        $statement->execute([
            'id' => $id,
            'nombre' => $data['domnombre'],
            'descripcion' => $data['domdescripcion'] !== '' ? $data['domdescripcion'] : null,
            'origen' => $data['domorigen'],
            'url' => $data['domurl'],
            'obligatorio' => !empty($data['domobligatorio']),
            'activo' => !empty($data['domactivo']),
        ]);
    }

    public function existsByName(string $name, ?int $exceptId = null): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE LOWER(domnombre) = LOWER(:name)";
        $params = ['name' => $name];

        if ($exceptId !== null && $exceptId > 0) {
            $sql .= " AND {$this->primaryKey} <> :id";
            $params['id'] = $exceptId;
        }

        $sql .= ' LIMIT 1';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchColumn() !== false;
    }

    public function deleteDocument(int $id): bool
    {
        try {
            $statement = $this->db->prepare(
                "DELETE FROM {$this->table}
                 WHERE {$this->primaryKey} = :id"
            );
            $statement->execute(['id' => $id]);

            return $statement->rowCount() > 0;
        } catch (PDOException) {
            return false;
        }
    }
}
