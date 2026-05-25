<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class AccountingModel extends Model
{
    public function dashboardSummary(int $periodId): array
    {
        return [
            'comprobantes_pendientes' => $this->countPendingReceipts($periodId),
            'pagos_aprobados_mes' => $this->approvedPaymentsThisMonth($periodId),
            'obligaciones_vencidas' => $this->countOverdueObligations($periodId),
            'valor_pendiente_pensiones' => $this->pendingPensionAmount($periodId),
            'rubros_pendientes' => $this->countPendingAdditionalItems($periodId),
            'rubros_vencidos' => $this->countOverdueAdditionalItems($periodId),
            'pagos_rechazados_recientes' => $this->countRecentlyRejectedPayments($periodId),
        ];
    }

    public function recentPendingReceipts(int $periodId, int $limit = 8): array
    {
        if ($periodId <= 0) {
            return [];
        }

        $statement = $this->db->prepare(
            "SELECT
                    p.cpagid,
                    p.cpagvalor_reportado,
                    p.cpagfecha_registro,
                    pe.perapellidos,
                    pe.pernombres,
                    o.cobdescripcion,
                    o.cobtipo,
                    g.granombre,
                    pr.prlnombre
             FROM contabilidad_pago p
             INNER JOIN matricula m ON m.matid = p.matid
             INNER JOIN curso c ON c.curid = m.curid
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN paralelo pr ON pr.prlid = c.prlid
             INNER JOIN estudiante e ON e.estid = m.estid
             INNER JOIN persona pe ON pe.perid = e.perid
             LEFT JOIN contabilidad_obligacion o ON o.cobid = p.cobid_sugerido
             WHERE c.pleid = :period_id
               AND p.cpagestado = 'EN_REVISION'
             ORDER BY p.cpagfecha_registro DESC
             LIMIT :limit"
        );
        $statement->bindValue('period_id', $periodId);
        $statement->bindValue('limit', max(1, $limit), \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    private function countPendingReceipts(int $periodId): int
    {
        return $this->countByPeriod(
            "SELECT COUNT(*)
             FROM contabilidad_pago p
             INNER JOIN matricula m ON m.matid = p.matid
             INNER JOIN curso c ON c.curid = m.curid
             WHERE c.pleid = :period_id
               AND p.cpagestado = 'EN_REVISION'",
            $periodId
        );
    }

    private function approvedPaymentsThisMonth(int $periodId): float
    {
        if ($periodId <= 0) {
            return 0.0;
        }

        $statement = $this->db->prepare(
            "SELECT COALESCE(SUM(p.cpagvalor_aprobado), 0)
             FROM contabilidad_pago p
             INNER JOIN matricula m ON m.matid = p.matid
             INNER JOIN curso c ON c.curid = m.curid
             WHERE c.pleid = :period_id
               AND p.cpagestado = 'APROBADO'
               AND p.cpagfecha_revision >= date_trunc('month', CURRENT_DATE)
               AND p.cpagfecha_revision < date_trunc('month', CURRENT_DATE) + interval '1 month'"
        );
        $statement->execute(['period_id' => $periodId]);

        return (float) $statement->fetchColumn();
    }

    private function countOverdueObligations(int $periodId): int
    {
        return $this->countByPeriod(
            "SELECT COUNT(*)
             FROM contabilidad_obligacion o
             INNER JOIN matricula m ON m.matid = o.matid
             INNER JOIN curso c ON c.curid = m.curid
             WHERE c.pleid = :period_id
               AND o.cobestado IN ('PENDIENTE', 'PAGO_PARCIAL', 'VENCIDO')
               AND o.cobfecha_vencimiento IS NOT NULL
               AND o.cobfecha_vencimiento < CURRENT_DATE",
            $periodId
        );
    }

    private function pendingPensionAmount(int $periodId): float
    {
        if ($periodId <= 0) {
            return 0.0;
        }

        $statement = $this->db->prepare(
            "SELECT COALESCE(SUM(o.cobsaldo_pendiente), 0)
             FROM contabilidad_obligacion o
             INNER JOIN matricula m ON m.matid = o.matid
             INNER JOIN curso c ON c.curid = m.curid
             WHERE c.pleid = :period_id
               AND o.cobtipo = 'PENSION'
               AND o.cobestado IN ('PENDIENTE', 'PAGO_PARCIAL', 'VENCIDO')"
        );
        $statement->execute(['period_id' => $periodId]);

        return (float) $statement->fetchColumn();
    }

    private function countPendingAdditionalItems(int $periodId): int
    {
        return $this->countByPeriod(
            "SELECT COUNT(*)
             FROM contabilidad_rubro_estudiante re
             INNER JOIN contabilidad_rubro r ON r.cruid = re.cruid
             WHERE r.pleid = :period_id
               AND re.creestado IN ('PENDIENTE', 'VENCIDO')",
            $periodId
        );
    }

    private function countOverdueAdditionalItems(int $periodId): int
    {
        return $this->countByPeriod(
            "SELECT COUNT(*)
             FROM contabilidad_rubro_estudiante re
             INNER JOIN contabilidad_rubro r ON r.cruid = re.cruid
             WHERE r.pleid = :period_id
               AND re.creestado IN ('PENDIENTE', 'VENCIDO')
               AND re.crefecha_limite IS NOT NULL
               AND re.crefecha_limite < CURRENT_DATE",
            $periodId
        );
    }

    private function countRecentlyRejectedPayments(int $periodId): int
    {
        return $this->countByPeriod(
            "SELECT COUNT(*)
             FROM contabilidad_pago p
             INNER JOIN matricula m ON m.matid = p.matid
             INNER JOIN curso c ON c.curid = m.curid
             WHERE c.pleid = :period_id
               AND p.cpagestado = 'RECHAZADO'
               AND p.cpagfecha_revision >= CURRENT_DATE - interval '30 days'",
            $periodId
        );
    }

    private function countByPeriod(string $sql, int $periodId): int
    {
        if ($periodId <= 0) {
            return 0;
        }

        $statement = $this->db->prepare($sql);
        $statement->execute(['period_id' => $periodId]);

        return (int) $statement->fetchColumn();
    }
}
