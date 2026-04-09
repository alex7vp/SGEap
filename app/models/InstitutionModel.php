<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class InstitutionModel extends Model
{
    protected string $table = 'institucion';
    protected string $primaryKey = 'insid';

    public function current(): array|false
    {
        $statement = $this->db->query(
            "SELECT insid, insnombre, insrazonsocial, insruc, inscodigoamie, insdireccion, instelefono, inscorreoelectronico, insrepresentantelegal
             FROM {$this->table}
             ORDER BY {$this->primaryKey} ASC
             LIMIT 1"
        );

        return $statement->fetch();
    }

    public function existsByRuc(string $ruc, ?int $exceptId = null): bool
    {
        if ($ruc === '') {
            return false;
        }

        return $this->existsByUniqueField('insruc', $ruc, $exceptId);
    }

    public function existsByAmie(string $amie, ?int $exceptId = null): bool
    {
        if ($amie === '') {
            return false;
        }

        return $this->existsByUniqueField('inscodigoamie', $amie, $exceptId);
    }

    public function create(array $data): void
    {
        $statement = $this->db->prepare(
            "INSERT INTO {$this->table} (
                insnombre,
                insrazonsocial,
                insruc,
                inscodigoamie,
                insdireccion,
                instelefono,
                inscorreoelectronico,
                insrepresentantelegal
            ) VALUES (
                :nombre,
                :razon_social,
                :ruc,
                :amie,
                :direccion,
                :telefono,
                :correo,
                :representante
            )"
        );

        $statement->execute([
            'nombre' => $data['insnombre'],
            'razon_social' => $data['insrazonsocial'] !== '' ? $data['insrazonsocial'] : null,
            'ruc' => $data['insruc'] !== '' ? $data['insruc'] : null,
            'amie' => $data['inscodigoamie'] !== '' ? $data['inscodigoamie'] : null,
            'direccion' => $data['insdireccion'] !== '' ? $data['insdireccion'] : null,
            'telefono' => $data['instelefono'] !== '' ? $data['instelefono'] : null,
            'correo' => $data['inscorreoelectronico'] !== '' ? $data['inscorreoelectronico'] : null,
            'representante' => $data['insrepresentantelegal'] !== '' ? $data['insrepresentantelegal'] : null,
        ]);
    }

    public function updateInstitution(int $institutionId, array $data): void
    {
        $statement = $this->db->prepare(
            "UPDATE {$this->table}
             SET insnombre = :nombre,
                 insrazonsocial = :razon_social,
                 insruc = :ruc,
                 inscodigoamie = :amie,
                 insdireccion = :direccion,
                 instelefono = :telefono,
                 inscorreoelectronico = :correo,
                 insrepresentantelegal = :representante
             WHERE {$this->primaryKey} = :id"
        );

        $statement->execute([
            'id' => $institutionId,
            'nombre' => $data['insnombre'],
            'razon_social' => $data['insrazonsocial'] !== '' ? $data['insrazonsocial'] : null,
            'ruc' => $data['insruc'] !== '' ? $data['insruc'] : null,
            'amie' => $data['inscodigoamie'] !== '' ? $data['inscodigoamie'] : null,
            'direccion' => $data['insdireccion'] !== '' ? $data['insdireccion'] : null,
            'telefono' => $data['instelefono'] !== '' ? $data['instelefono'] : null,
            'correo' => $data['inscorreoelectronico'] !== '' ? $data['inscorreoelectronico'] : null,
            'representante' => $data['insrepresentantelegal'] !== '' ? $data['insrepresentantelegal'] : null,
        ]);
    }

    private function existsByUniqueField(string $field, string $value, ?int $exceptId = null): bool
    {
        $sql =
            "SELECT 1
             FROM {$this->table}
             WHERE {$field} = :value";

        $params = ['value' => $value];

        if ($exceptId !== null) {
            $sql .= " AND {$this->primaryKey} <> :id";
            $params['id'] = $exceptId;
        }

        $sql .= ' LIMIT 1';

        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchColumn() !== false;
    }
}
