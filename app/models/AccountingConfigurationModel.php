<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;
use RuntimeException;

class AccountingConfigurationModel extends Model
{
    protected string $table = 'contabilidad_configuracion_obligacion';
    protected string $primaryKey = 'cfoid';

    public function allConfigurations(): array
    {
        $statement = $this->db->query(
            "SELECT
                    c.cfoid,
                    c.pleid,
                    p.pledescripcion,
                    c.cfoalcance,
                    c.nedid,
                    n.nednombre,
                    c.graid,
                    g.granombre,
                    gn.nednombre AS grado_nednombre,
                    c.curid,
                    cg.granombre AS curso_granombre,
                    cn.nednombre AS curso_nednombre,
                    pr.prlnombre,
                    c.ccoid,
                    co.cconombre,
                    c.cfotipo,
                    c.cfovalor_oficial,
                    c.cfocantidad_pensiones,
                    c.cfomes_inicio,
                    c.cfoanio_inicio,
                    c.cfodia_vencimiento,
                    c.cfogenera_mora,
                    c.cfomora_tipo,
                    c.cfomora_valor,
                    c.cfoestado,
                    c.cfoobservacion,
                    c.cfofecha_creacion,
                    c.cfofecha_modificacion
             FROM {$this->table} c
             INNER JOIN periodo_lectivo p ON p.pleid = c.pleid
             INNER JOIN contabilidad_concepto co ON co.ccoid = c.ccoid
             LEFT JOIN nivel_educativo n ON n.nedid = c.nedid
             LEFT JOIN grado g ON g.graid = c.graid
             LEFT JOIN nivel_educativo gn ON gn.nedid = g.nedid
             LEFT JOIN curso cu ON cu.curid = c.curid
             LEFT JOIN grado cg ON cg.graid = cu.graid
             LEFT JOIN nivel_educativo cn ON cn.nedid = cg.nedid
             LEFT JOIN paralelo pr ON pr.prlid = cu.prlid
             ORDER BY p.plefechainicio DESC,
                      CASE c.cfoalcance
                          WHEN 'INSTITUCION' THEN 1
                          WHEN 'NIVEL' THEN 2
                          WHEN 'GRADO' THEN 3
                          WHEN 'CURSO' THEN 4
                          ELSE 5
                      END,
                      COALESCE(n.nednombre, gn.nednombre, cn.nednombre, ''),
                      COALESCE(g.granombre, cg.granombre, ''),
                      COALESCE(pr.prlnombre, ''),
                      c.cfotipo"
        );

        return $statement->fetchAll();
    }

    public function findConfiguration(int $configurationId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT *
             FROM {$this->table}
             WHERE {$this->primaryKey} = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $configurationId]);

        return $statement->fetch();
    }

    public function obligationConceptId(string $type): int
    {
        $code = mb_strtoupper(trim($type));
        $statement = $this->db->prepare(
            "SELECT ccoid
             FROM contabilidad_concepto
             WHERE ccocodigo = :code
               AND ccocategoria = 'OBLIGACION'
               AND ccoestado = true
             LIMIT 1"
        );
        $statement->execute(['code' => $code]);
        $conceptId = $statement->fetchColumn();

        if ($conceptId === false) {
            throw new RuntimeException('No existe el concepto contable requerido para ' . $code . '.');
        }

        return (int) $conceptId;
    }

    public function createConfiguration(array $data): void
    {
        $statement = $this->db->prepare(
            "INSERT INTO {$this->table} (
                pleid,
                cfoalcance,
                nedid,
                graid,
                curid,
                ccoid,
                cfotipo,
                cfovalor_oficial,
                cfocantidad_pensiones,
                cfomes_inicio,
                cfoanio_inicio,
                cfodia_vencimiento,
                cfogenera_mora,
                cfomora_tipo,
                cfomora_valor,
                cfoestado,
                cfoobservacion,
                usuid_registro
             ) VALUES (
                :pleid,
                :alcance,
                :nedid,
                :graid,
                :curid,
                :ccoid,
                :tipo,
                :valor_oficial,
                :cantidad_pensiones,
                :mes_inicio,
                :anio_inicio,
                :dia_vencimiento,
                :genera_mora,
                :mora_tipo,
                :mora_valor,
                :estado,
                :observacion,
                :usuario
             )"
        );

        $this->bindConfiguration($statement, $data, true);
        $statement->execute();
    }

    public function updateConfiguration(int $configurationId, array $data): void
    {
        $statement = $this->db->prepare(
            "UPDATE {$this->table}
             SET pleid = :pleid,
                 cfoalcance = :alcance,
                 nedid = :nedid,
                 graid = :graid,
                 curid = :curid,
                 ccoid = :ccoid,
                 cfotipo = :tipo,
                 cfovalor_oficial = :valor_oficial,
                 cfocantidad_pensiones = :cantidad_pensiones,
                 cfomes_inicio = :mes_inicio,
                 cfoanio_inicio = :anio_inicio,
                 cfodia_vencimiento = :dia_vencimiento,
                 cfogenera_mora = :genera_mora,
                 cfomora_tipo = :mora_tipo,
                 cfomora_valor = :mora_valor,
                 cfoestado = :estado,
                 cfoobservacion = :observacion,
                 cfofecha_modificacion = CURRENT_TIMESTAMP
             WHERE {$this->primaryKey} = :id"
        );
        $statement->bindValue(':id', $configurationId, PDO::PARAM_INT);
        $this->bindConfiguration($statement, $data, false);
        $statement->execute();
    }

    public function existsActiveCombination(array $data, ?int $exceptId = null): bool
    {
        if (empty($data['cfoestado'])) {
            return false;
        }

        $conditions = [
            'pleid = :pleid',
            'cfoalcance = :alcance',
            'cfotipo = :tipo',
            'cfoestado = true',
        ];
        $params = [
            'pleid' => (int) $data['pleid'],
            'alcance' => (string) $data['cfoalcance'],
            'tipo' => (string) $data['cfotipo'],
        ];

        if ($data['cfoalcance'] === 'NIVEL') {
            $conditions[] = 'nedid = :target_id';
            $params['target_id'] = (int) $data['nedid'];
        } elseif ($data['cfoalcance'] === 'GRADO') {
            $conditions[] = 'graid = :target_id';
            $params['target_id'] = (int) $data['graid'];
        } elseif ($data['cfoalcance'] === 'CURSO') {
            $conditions[] = 'curid = :target_id';
            $params['target_id'] = (int) $data['curid'];
        }

        $sql = 'SELECT 1 FROM ' . $this->table . ' WHERE ' . implode(' AND ', $conditions);

        if ($exceptId !== null) {
            $sql .= " AND {$this->primaryKey} <> :id";
            $params['id'] = $exceptId;
        }

        $sql .= ' LIMIT 1';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchColumn() !== false;
    }

    private function bindConfiguration(\PDOStatement $statement, array $data, bool $bindUser): void
    {
        $statement->bindValue(':pleid', (int) $data['pleid'], PDO::PARAM_INT);
        $statement->bindValue(':alcance', (string) $data['cfoalcance']);
        $this->bindNullableInt($statement, ':nedid', $data['nedid'] ?? null);
        $this->bindNullableInt($statement, ':graid', $data['graid'] ?? null);
        $this->bindNullableInt($statement, ':curid', $data['curid'] ?? null);
        $statement->bindValue(':ccoid', (int) $data['ccoid'], PDO::PARAM_INT);
        $statement->bindValue(':tipo', (string) $data['cfotipo']);
        $statement->bindValue(':valor_oficial', (string) $data['cfovalor_oficial']);
        $this->bindNullableInt($statement, ':cantidad_pensiones', $data['cfocantidad_pensiones'] ?? null);
        $this->bindNullableInt($statement, ':mes_inicio', $data['cfomes_inicio'] ?? null);
        $this->bindNullableInt($statement, ':anio_inicio', $data['cfoanio_inicio'] ?? null);
        $statement->bindValue(':dia_vencimiento', (int) $data['cfodia_vencimiento'], PDO::PARAM_INT);
        $statement->bindValue(':genera_mora', (bool) $data['cfogenera_mora'], PDO::PARAM_BOOL);
        $this->bindNullableString($statement, ':mora_tipo', $data['cfomora_tipo'] ?? null);
        $this->bindNullableString($statement, ':mora_valor', $data['cfomora_valor'] ?? null);
        $statement->bindValue(':estado', (bool) $data['cfoestado'], PDO::PARAM_BOOL);
        $this->bindNullableString($statement, ':observacion', $data['cfoobservacion'] ?? null);
        if ($bindUser) {
            $statement->bindValue(':usuario', (int) $data['usuid_registro'], PDO::PARAM_INT);
        }
    }

    private function bindNullableInt(\PDOStatement $statement, string $parameter, mixed $value): void
    {
        if ($value === null || $value === '') {
            $statement->bindValue($parameter, null, PDO::PARAM_NULL);
            return;
        }

        $statement->bindValue($parameter, (int) $value, PDO::PARAM_INT);
    }

    private function bindNullableString(\PDOStatement $statement, string $parameter, mixed $value): void
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            $statement->bindValue($parameter, null, PDO::PARAM_NULL);
            return;
        }

        $statement->bindValue($parameter, $value);
    }
}
