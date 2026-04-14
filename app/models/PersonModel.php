<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDOException;

class PersonModel extends Model
{
    protected string $table = 'persona';
    protected string $primaryKey = 'perid';

    public function allOrdered(): array
    {
        return $this->search();
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            "INSERT INTO {$this->table} (
                percedula,
                pernombres,
                perapellidos,
                pertelefono1,
                pertelefono2,
                percorreo,
                persexo
            ) VALUES (
                :cedula,
                :nombres,
                :apellidos,
                :telefono1,
                :telefono2,
                :correo,
                :sexo
            )
            RETURNING perid"
        );

        $statement->execute([
            'cedula' => $data['percedula'],
            'nombres' => $data['pernombres'],
            'apellidos' => $data['perapellidos'],
            'telefono1' => $data['pertelefono1'] !== '' ? $data['pertelefono1'] : null,
            'telefono2' => $data['pertelefono2'] !== '' ? $data['pertelefono2'] : null,
            'correo' => $data['percorreo'] !== '' ? $data['percorreo'] : null,
            'sexo' => $data['persexo'] !== '' ? $data['persexo'] : null,
        ]);

        return (int) $statement->fetchColumn();
    }

    public function existsByCedula(string $cedula): bool
    {
        $statement = $this->db->prepare(
            "SELECT 1
             FROM {$this->table}
             WHERE percedula = :cedula
             LIMIT 1"
        );
        $statement->execute(['cedula' => $cedula]);

        return $statement->fetchColumn() !== false;
    }

    public function existsByCedulaExceptId(string $cedula, int $personId): bool
    {
        $statement = $this->db->prepare(
            "SELECT 1
             FROM {$this->table}
             WHERE percedula = :cedula
               AND perid <> :perid
             LIMIT 1"
        );
        $statement->execute([
            'cedula' => $cedula,
            'perid' => $personId,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function findByCedula(string $cedula): array|false
    {
        $statement = $this->db->prepare(
            "SELECT perid, percedula, pernombres, perapellidos, pertelefono1, pertelefono2, percorreo, persexo
             FROM {$this->table}
             WHERE percedula = :cedula
             LIMIT 1"
        );
        $statement->execute(['cedula' => $cedula]);

        return $statement->fetch();
    }

    public function search(string $term = ''): array
    {
        $normalizedTerm = trim($term);

        if ($normalizedTerm === '') {
            $statement = $this->db->query(
                "SELECT perid, percedula, pernombres, perapellidos, pertelefono1, pertelefono2, percorreo, persexo
                 FROM {$this->table}
                 ORDER BY perapellidos ASC, pernombres ASC"
            );

            return $statement->fetchAll();
        }

        $statement = $this->db->prepare(
            "SELECT perid, percedula, pernombres, perapellidos, pertelefono1, pertelefono2, percorreo, persexo
             FROM {$this->table}
             WHERE percedula ILIKE :term
                OR pernombres ILIKE :term
                OR perapellidos ILIKE :term
                OR COALESCE(percorreo, '') ILIKE :term
                OR COALESCE(pertelefono1, '') ILIKE :term
                OR COALESCE(pertelefono2, '') ILIKE :term
             ORDER BY perapellidos ASC, pernombres ASC"
        );
        $statement->execute(['term' => '%' . $normalizedTerm . '%']);

        return $statement->fetchAll();
    }

    public function update(int $personId, array $data): void
    {
        $statement = $this->db->prepare(
            "UPDATE {$this->table}
             SET percedula = :cedula,
                 pernombres = :nombres,
                 perapellidos = :apellidos,
                 pertelefono1 = :telefono1,
                 pertelefono2 = :telefono2,
                 percorreo = :correo,
                 persexo = :sexo
             WHERE perid = :perid"
        );

        $statement->execute([
            'perid' => $personId,
            'cedula' => $data['percedula'],
            'nombres' => $data['pernombres'],
            'apellidos' => $data['perapellidos'],
            'telefono1' => $data['pertelefono1'] !== '' ? $data['pertelefono1'] : null,
            'telefono2' => $data['pertelefono2'] !== '' ? $data['pertelefono2'] : null,
            'correo' => $data['percorreo'] !== '' ? $data['percorreo'] : null,
            'sexo' => $data['persexo'] !== '' ? $data['persexo'] : null,
        ]);
    }

    public function deleteById(int $personId): bool
    {
        try {
            $statement = $this->db->prepare(
                "DELETE FROM {$this->table}
                 WHERE perid = :perid"
            );
            $statement->execute(['perid' => $personId]);

            return $statement->rowCount() > 0;
        } catch (PDOException) {
            return false;
        }
    }

    public function allWithoutStudent(): array
    {
        $statement = $this->db->query(
            "SELECT p.perid, p.percedula, p.pernombres, p.perapellidos
             FROM {$this->table} p
             LEFT JOIN estudiante e ON e.perid = p.perid
             WHERE e.estid IS NULL
             ORDER BY p.perapellidos ASC, p.pernombres ASC"
        );

        return $statement->fetchAll();
    }
}
