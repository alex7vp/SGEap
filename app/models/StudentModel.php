<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class StudentModel extends Model
{
    protected string $table = 'estudiante';
    protected string $primaryKey = 'estid';

    public function allWithPerson(): array
    {
        $statement = $this->db->query(
            "SELECT
                e.estid,
                e.perid,
                p.percedula,
                p.pernombres,
                p.perapellidos,
                p.persexo,
                e.estlugarnacimiento,
                e.estdireccion,
                e.estparroquia,
                e.estestado
             FROM {$this->table} e
             INNER JOIN persona p ON p.perid = e.perid
             ORDER BY p.perapellidos ASC, p.pernombres ASC"
        );

        return $statement->fetchAll();
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            "INSERT INTO {$this->table} (
                perid,
                estlugarnacimiento,
                estdireccion,
                estparroquia,
                estestado
            ) VALUES (
                :perid,
                :lugarnacimiento,
                :direccion,
                :parroquia,
                :estado
            )
            RETURNING estid"
        );

        $statement->execute([
            'perid' => $data['perid'],
            'lugarnacimiento' => $data['estlugarnacimiento'] !== '' ? $data['estlugarnacimiento'] : null,
            'direccion' => $data['estdireccion'] !== '' ? $data['estdireccion'] : null,
            'parroquia' => $data['estparroquia'] !== '' ? $data['estparroquia'] : null,
            'estado' => $data['estestado'],
        ]);

        return (int) $statement->fetchColumn();
    }

    public function existsByPersonId(int $personId): bool
    {
        $statement = $this->db->prepare(
            "SELECT 1
             FROM {$this->table}
             WHERE perid = :perid
             LIMIT 1"
        );
        $statement->execute(['perid' => $personId]);

        return $statement->fetchColumn() !== false;
    }

    public function countAll(): int
    {
        $statement = $this->db->query(
            "SELECT COUNT(*) FROM {$this->table}"
        );

        return (int) $statement->fetchColumn();
    }

    public function countActive(): int
    {
        $statement = $this->db->query(
            "SELECT COUNT(*) FROM {$this->table}
             WHERE estestado = true"
        );

        return (int) $statement->fetchColumn();
    }
}
