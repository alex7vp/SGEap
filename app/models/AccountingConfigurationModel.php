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
                    c.ccoid,
                    co.cconombre,
                    c.cfotipo,
                    c.cfovalor_oficial,
                    c.cfocantidad_pensiones,
                    c.cfomes_inicio,
                    c.cfomes_fin,
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
             ORDER BY p.plefechainicio DESC,
                      CASE c.cfoalcance
                          WHEN 'INSTITUCION' THEN 1
                          WHEN 'NIVEL' THEN 2
                          WHEN 'GRADO' THEN 3
                          ELSE 5
                      END,
                      COALESCE(n.nednombre, gn.nednombre, ''),
                      COALESCE(g.granombre, ''),
                      c.cfotipo"
        );

        return $statement->fetchAll();
    }

    public function moduleSettingsByPeriod(): array
    {
        $statement = $this->db->query(
            "SELECT
                    p.pleid,
                    p.pledescripcion,
                    COALESCE(m.ccmrepresentante_rubros_visible, false) AS representante_rubros_visible
             FROM periodo_lectivo p
             LEFT JOIN contabilidad_configuracion_modulo m ON m.pleid = p.pleid
             ORDER BY p.plefechainicio DESC, p.pleid DESC"
        );

        return $statement->fetchAll();
    }

    public function moduleSettingsForPeriod(int $periodId): array
    {
        if ($periodId <= 0) {
            return [
                'representante_rubros_visible' => false,
            ];
        }

        $statement = $this->db->prepare(
            "SELECT ccmrepresentante_rubros_visible
             FROM contabilidad_configuracion_modulo
             WHERE pleid = :period_id
             LIMIT 1"
        );
        $statement->execute(['period_id' => $periodId]);
        $row = $statement->fetch();

        return [
            'representante_rubros_visible' => $row !== false && !empty($row['ccmrepresentante_rubros_visible']),
        ];
    }

    public function saveModuleSettings(int $periodId, bool $representativeAdditionalItemsVisible, int $userId): void
    {
        if ($periodId <= 0) {
            throw new RuntimeException('Seleccione un periodo lectivo valido.');
        }

        $previous = $this->moduleSettingsForPeriod($periodId);

        $statement = $this->db->prepare(
            "INSERT INTO contabilidad_configuracion_modulo (
                pleid,
                ccmrepresentante_rubros_visible,
                usuid_registro
             ) VALUES (
                :period_id,
                :visible,
                :user_id
             )
             ON CONFLICT (pleid) DO UPDATE
             SET ccmrepresentante_rubros_visible = EXCLUDED.ccmrepresentante_rubros_visible,
                 usuid_registro = EXCLUDED.usuid_registro,
                 ccmfecha_modificacion = CURRENT_TIMESTAMP
             RETURNING ccmid"
        );
        $statement->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $statement->bindValue(':visible', $representativeAdditionalItemsVisible, PDO::PARAM_BOOL);
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->execute();
        $settingId = (int) $statement->fetchColumn();

        $this->audit(
            'contabilidad_configuracion_modulo',
            $settingId,
            'EDITAR',
            $previous,
            ['representante_rubros_visible' => $representativeAdditionalItemsVisible],
            $userId,
            'Actualizacion de servicios contables'
        );
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

    public function createConfiguration(array $data): int
    {
        $statement = $this->db->prepare(
            "INSERT INTO {$this->table} (
                pleid,
                cfoalcance,
                nedid,
                graid,
                ccoid,
                cfotipo,
                cfovalor_oficial,
                cfocantidad_pensiones,
                cfomes_inicio,
                cfomes_fin,
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
                :ccoid,
                :tipo,
                :valor_oficial,
                :cantidad_pensiones,
                :mes_inicio,
                :mes_fin,
                :anio_inicio,
                :dia_vencimiento,
                :genera_mora,
                :mora_tipo,
                :mora_valor,
                :estado,
                :observacion,
                :usuario
             )
             RETURNING {$this->primaryKey}"
        );

        $this->bindConfiguration($statement, $data, true);
        $statement->execute();
        $configurationId = (int) $statement->fetchColumn();

        $this->audit(
            $this->table,
            $configurationId,
            'CREAR',
            null,
            $data,
            (int) ($data['usuid_registro'] ?? 0),
            'Creacion de configuracion contable'
        );

        return $configurationId;
    }

    public function updateConfiguration(int $configurationId, array $data): void
    {
        $previous = $this->findConfiguration($configurationId);
        $statement = $this->db->prepare(
            "UPDATE {$this->table}
             SET pleid = :pleid,
                 cfoalcance = :alcance,
                 nedid = :nedid,
                 graid = :graid,
                 ccoid = :ccoid,
                 cfotipo = :tipo,
                 cfovalor_oficial = :valor_oficial,
                 cfocantidad_pensiones = :cantidad_pensiones,
                 cfomes_inicio = :mes_inicio,
                 cfomes_fin = :mes_fin,
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

        $this->audit(
            $this->table,
            $configurationId,
            'EDITAR',
            $previous !== false ? $previous : null,
            $data,
            (int) ($data['usuid_registro'] ?? 0),
            'Actualizacion de configuracion contable'
        );
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

    public function findCombination(array $data, string $type, ?int $exceptId = null): array|false
    {
        $conditions = [
            'pleid = :pleid',
            'cfoalcance = :alcance',
            'cfotipo = :tipo',
        ];
        $params = [
            'pleid' => (int) $data['pleid'],
            'alcance' => (string) $data['cfoalcance'],
            'tipo' => mb_strtoupper(trim($type)),
        ];

        if ($data['cfoalcance'] === 'NIVEL') {
            $conditions[] = 'nedid = :target_id';
            $params['target_id'] = (int) $data['nedid'];
        } elseif ($data['cfoalcance'] === 'GRADO') {
            $conditions[] = 'graid = :target_id';
            $params['target_id'] = (int) $data['graid'];
        }

        if ($exceptId !== null) {
            $conditions[] = "{$this->primaryKey} <> :id";
            $params['id'] = $exceptId;
        }

        $sql = 'SELECT * FROM ' . $this->table . ' WHERE ' . implode(' AND ', $conditions) . " ORDER BY cfoestado DESC, {$this->primaryKey} ASC LIMIT 1";
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement->fetch();
    }

    public function saveConfigurationPair(array $pensionData, array $matriculationData, ?int $pensionId = null, ?int $matriculationId = null): void
    {
        $this->db->beginTransaction();

        try {
            if ($pensionId !== null) {
                $this->updateConfiguration($pensionId, $pensionData);
            } else {
                $this->createConfiguration($pensionData);
            }

            if ($matriculationId !== null) {
                $this->updateConfiguration($matriculationId, $matriculationData);
            } else {
                $this->createConfiguration($matriculationData);
            }

            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    private function bindConfiguration(\PDOStatement $statement, array $data, bool $bindUser): void
    {
        $statement->bindValue(':pleid', (int) $data['pleid'], PDO::PARAM_INT);
        $statement->bindValue(':alcance', (string) $data['cfoalcance']);
        $this->bindNullableInt($statement, ':nedid', $data['nedid'] ?? null);
        $this->bindNullableInt($statement, ':graid', $data['graid'] ?? null);
        $statement->bindValue(':ccoid', (int) $data['ccoid'], PDO::PARAM_INT);
        $statement->bindValue(':tipo', (string) $data['cfotipo']);
        $statement->bindValue(':valor_oficial', (string) $data['cfovalor_oficial']);
        $this->bindNullableInt($statement, ':cantidad_pensiones', $data['cfocantidad_pensiones'] ?? null);
        $this->bindNullableInt($statement, ':mes_inicio', $data['cfomes_inicio'] ?? null);
        $this->bindNullableInt($statement, ':mes_fin', $data['cfomes_fin'] ?? null);
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

    private function audit(string $table, int $recordId, string $action, mixed $before, mixed $after, int $userId, string $observation = ''): void
    {
        if ($recordId <= 0 || $userId <= 0) {
            return;
        }

        $statement = $this->db->prepare(
            "INSERT INTO contabilidad_auditoria (
                cautabla,
                cauregistro_id,
                cauaccion,
                cauvalor_anterior,
                cauvalor_nuevo,
                cauobservacion,
                usuid
             ) VALUES (
                :table_name,
                :record_id,
                :action,
                :before_value,
                :after_value,
                :observation,
                :user_id
             )"
        );
        $statement->execute([
            'table_name' => $table,
            'record_id' => $recordId,
            'action' => $action,
            'before_value' => $before === null ? null : json_encode($before, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'after_value' => $after === null ? null : json_encode($after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'observation' => $observation !== '' ? mb_substr($observation, 0, 250) : null,
            'user_id' => $userId,
        ]);
    }
}
