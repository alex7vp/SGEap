<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;
use RuntimeException;

class AccountingModel extends Model
{
    private const MONTH_NAMES = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

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

    public function dashboardCharts(int $periodId): array
    {
        if ($periodId <= 0) {
            return [
                'payment_months' => [],
                'month_payment_status' => [],
                'receipts_status' => [],
                'obligations_status' => [],
                'payments_monthly' => [],
                'pending_by_course' => [],
            ];
        }

        return [
            'payment_months' => $this->paymentMonthOptions($periodId),
            'month_payment_status' => $this->monthPaymentStatusChart($periodId, null),
            'receipts_status' => $this->receiptStatusChart($periodId),
            'obligations_status' => $this->obligationStatusChart($periodId),
            'payments_monthly' => $this->approvedPaymentsMonthlyChart($periodId),
            'pending_by_course' => $this->pendingByCourseChart($periodId),
        ];
    }

    public function monthPaymentStatusChart(int $periodId, ?string $month): array
    {
        if ($periodId <= 0) {
            return [
                ['label' => 'Pagado', 'value' => 0],
                ['label' => 'Pendiente', 'value' => 0],
            ];
        }

        $month = $this->normalizeChartMonth($month);
        $whereMonth = '';
        $params = ['period_id' => $periodId];

        if ($month !== null) {
            $whereMonth = " AND make_date(o.cobanio, o.cobmes, 1) = to_date(:month, 'YYYY-MM')";
            $params['month'] = $month;
        }

        $statement = $this->db->prepare(
            "SELECT
                    COALESCE(SUM(o.cobvalor_pagado), 0) AS paid,
                    COALESCE(SUM(o.cobsaldo_pendiente), 0) AS pending
             FROM contabilidad_obligacion o
             INNER JOIN matricula m ON m.matid = o.matid
             INNER JOIN curso c ON c.curid = m.curid
             WHERE c.pleid = :period_id
               AND o.cobestado <> 'ANULADO'
               AND o.cobtipo = 'PENSION'
               AND o.cobanio IS NOT NULL
               AND o.cobmes IS NOT NULL
               {$whereMonth}"
        );
        $statement->execute($params);
        $row = $statement->fetch() ?: ['paid' => 0, 'pending' => 0];

        return [
            ['label' => 'Pagado', 'value' => (float) ($row['paid'] ?? 0)],
            ['label' => 'Pendiente', 'value' => (float) ($row['pending'] ?? 0)],
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
             INNER JOIN contabilidad_obligacion o ON o.cobid = p.cobid_sugerido
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

    public function obligationStudents(int $periodId, array $filters = [], int $limit = 25, int $page = 1): array
    {
        return $this->obligationStudentsPage($periodId, $filters, $limit, $page)['rows'];
    }

    public function obligationStudentsPage(int $periodId, array $filters = [], int $limit = 25, int $page = 1): array
    {
        if ($periodId <= 0) {
            return [
                'rows' => [],
                'total' => 0,
                'page' => 1,
                'limit' => max(1, $limit),
                'pages' => 1,
            ];
        }

        $conditions = ['c.pleid = :period_id'];
        $params = ['period_id' => $periodId];
        $search = trim((string) ($filters['q'] ?? ''));
        $levelId = (int) ($filters['nivel'] ?? 0);
        $courseId = (int) ($filters['curso'] ?? 0);
        $limit = min(100, max(10, $limit));
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        if ($search !== '') {
            $conditions[] = "(lower(pe.pernombres || ' ' || pe.perapellidos) LIKE lower(:search)
                OR lower(pe.perapellidos || ' ' || pe.pernombres) LIKE lower(:search)
                OR lower(COALESCE(pe.percedula, '')) LIKE lower(:search))";
            $params['search'] = '%' . $search . '%';
        }

        if ($levelId > 0) {
            $conditions[] = 'n.nedid = :level_id';
            $params['level_id'] = $levelId;
        }

        if ($courseId > 0) {
            $conditions[] = 'c.curid = :course_id';
            $params['course_id'] = $courseId;
        }

        $countSql = "SELECT COUNT(*)
                FROM matricula m
                INNER JOIN estudiante e ON e.estid = m.estid
                INNER JOIN persona pe ON pe.perid = e.perid
                INNER JOIN curso c ON c.curid = m.curid
                INNER JOIN grado g ON g.graid = c.graid
                INNER JOIN nivel_educativo n ON n.nedid = g.nedid
                WHERE " . implode(' AND ', $conditions);
        $countStatement = $this->db->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStatement->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $countStatement->execute();
        $total = (int) $countStatement->fetchColumn();
        $pages = max(1, (int) ceil($total / $limit));
        $page = min($page, $pages);
        $offset = ($page - 1) * $limit;

        $sql = "SELECT
                    m.matid,
                    e.estid,
                    pe.percedula,
                    pe.pernombres,
                    pe.perapellidos,
                    n.nedid,
                    n.nednombre,
                    g.graid,
                    g.granombre,
                    c.curid,
                    pr.prlnombre,
                    pension.cfoid AS pension_cfoid,
                    pension.cfovalor_oficial AS pension_valor,
                    pension.cfocantidad_pensiones,
                    pension.cfomes_inicio,
                    pension.cfomes_fin,
                    pension.cfoanio_inicio,
                    matricula.cfoid AS matricula_cfoid,
                    matricula.cfovalor_oficial AS matricula_valor,
                    COALESCE(ob.total_obligaciones, 0) AS total_obligaciones,
                    COALESCE(ob.matricula_generada, 0) AS matricula_generada,
                    COALESCE(ob.pensiones_generadas, 0) AS pensiones_generadas,
                    COALESCE(ob.saldo_pendiente, 0) AS saldo_pendiente,
                    pension_actual.cobdescuento_tipo AS pension_descuento_tipo,
                    pension_actual.cobdescuento_valor AS pension_descuento_valor,
                    pension_actual.cobvalor_descuento AS pension_valor_descuento,
                    pension_actual.cobvalor_final AS pension_valor_final
                FROM matricula m
                INNER JOIN estudiante e ON e.estid = m.estid
                INNER JOIN persona pe ON pe.perid = e.perid
                INNER JOIN curso c ON c.curid = m.curid
                INNER JOIN grado g ON g.graid = c.graid
                INNER JOIN nivel_educativo n ON n.nedid = g.nedid
                INNER JOIN paralelo pr ON pr.prlid = c.prlid
                LEFT JOIN LATERAL (
                    SELECT cfo.*
                    FROM contabilidad_configuracion_obligacion cfo
                    WHERE cfo.pleid = c.pleid
                      AND cfo.cfotipo = 'PENSION'
                      AND cfo.cfoestado = true
                      AND (
                          cfo.cfoalcance = 'INSTITUCION'
                          OR (cfo.cfoalcance = 'NIVEL' AND cfo.nedid = n.nedid)
                          OR (cfo.cfoalcance = 'GRADO' AND cfo.graid = g.graid)
                      )
                    ORDER BY CASE cfo.cfoalcance WHEN 'GRADO' THEN 1 WHEN 'NIVEL' THEN 2 ELSE 3 END
                    LIMIT 1
                ) pension ON true
                LEFT JOIN LATERAL (
                    SELECT cfo.*
                    FROM contabilidad_configuracion_obligacion cfo
                    WHERE cfo.pleid = c.pleid
                      AND cfo.cfotipo = 'MATRICULA'
                      AND cfo.cfoestado = true
                      AND (
                          cfo.cfoalcance = 'INSTITUCION'
                          OR (cfo.cfoalcance = 'NIVEL' AND cfo.nedid = n.nedid)
                          OR (cfo.cfoalcance = 'GRADO' AND cfo.graid = g.graid)
                      )
                    ORDER BY CASE cfo.cfoalcance WHEN 'GRADO' THEN 1 WHEN 'NIVEL' THEN 2 ELSE 3 END
                    LIMIT 1
                ) matricula ON true
                LEFT JOIN LATERAL (
                    SELECT
                        COUNT(*) AS total_obligaciones,
                        COUNT(*) FILTER (WHERE cobtipo = 'MATRICULA') AS matricula_generada,
                        COUNT(*) FILTER (WHERE cobtipo = 'PENSION') AS pensiones_generadas,
                        COALESCE(SUM(cobsaldo_pendiente) FILTER (WHERE cobestado <> 'ANULADO'), 0) AS saldo_pendiente
                    FROM contabilidad_obligacion o
                    WHERE o.matid = m.matid
                ) ob ON true
                LEFT JOIN LATERAL (
                    SELECT
                        o.cobdescuento_tipo,
                        o.cobdescuento_valor,
                        o.cobvalor_descuento,
                        o.cobvalor_final
                    FROM contabilidad_obligacion o
                    WHERE o.matid = m.matid
                      AND o.cobtipo = 'PENSION'
                      AND o.cobestado <> 'ANULADO'
                    ORDER BY o.cobanio ASC, o.cobmes ASC, o.cobid ASC
                    LIMIT 1
                ) pension_actual ON true
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY pe.perapellidos, pe.pernombres
                LIMIT :limit OFFSET :offset";

        $statement = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $statement->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $statement->execute();

        return [
            'rows' => $statement->fetchAll(),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => $pages,
        ];
    }

    public function obligationReference(int $periodId, array $filters = []): array
    {
        if ($periodId <= 0) {
            return $this->emptyObligationReference();
        }

        [$whereSql, $params] = $this->obligationFilterWhere($periodId, $filters);
        $sql = "WITH referencias AS (
                    SELECT
                        pension.cfovalor_oficial AS pension_valor,
                        pension.cfocantidad_pensiones,
                        matricula.cfovalor_oficial AS matricula_valor
                    FROM matricula m
                    INNER JOIN estudiante e ON e.estid = m.estid
                    INNER JOIN persona pe ON pe.perid = e.perid
                    INNER JOIN curso c ON c.curid = m.curid
                    INNER JOIN grado g ON g.graid = c.graid
                    INNER JOIN nivel_educativo n ON n.nedid = g.nedid
                    LEFT JOIN LATERAL (
                        SELECT cfo.*
                        FROM contabilidad_configuracion_obligacion cfo
                        WHERE cfo.pleid = c.pleid
                          AND cfo.cfotipo = 'PENSION'
                          AND cfo.cfoestado = true
                          AND (
                              cfo.cfoalcance = 'INSTITUCION'
                              OR (cfo.cfoalcance = 'NIVEL' AND cfo.nedid = n.nedid)
                              OR (cfo.cfoalcance = 'GRADO' AND cfo.graid = g.graid)
                          )
                        ORDER BY CASE cfo.cfoalcance WHEN 'GRADO' THEN 1 WHEN 'NIVEL' THEN 2 ELSE 3 END
                        LIMIT 1
                    ) pension ON true
                    LEFT JOIN LATERAL (
                        SELECT cfo.*
                        FROM contabilidad_configuracion_obligacion cfo
                        WHERE cfo.pleid = c.pleid
                          AND cfo.cfotipo = 'MATRICULA'
                          AND cfo.cfoestado = true
                          AND (
                              cfo.cfoalcance = 'INSTITUCION'
                              OR (cfo.cfoalcance = 'NIVEL' AND cfo.nedid = n.nedid)
                              OR (cfo.cfoalcance = 'GRADO' AND cfo.graid = g.graid)
                          )
                        ORDER BY CASE cfo.cfoalcance WHEN 'GRADO' THEN 1 WHEN 'NIVEL' THEN 2 ELSE 3 END
                        LIMIT 1
                    ) matricula ON true
                    WHERE {$whereSql}
                )
                SELECT
                    COUNT(*) AS total_estudiantes,
                    COUNT(DISTINCT pension_valor) FILTER (WHERE pension_valor IS NOT NULL) AS pension_distintas,
                    COUNT(DISTINCT matricula_valor) FILTER (WHERE matricula_valor IS NOT NULL) AS matricula_distintas,
                    COUNT(DISTINCT cfocantidad_pensiones) FILTER (WHERE cfocantidad_pensiones IS NOT NULL) AS meses_distintos,
                    MIN(pension_valor) AS pension_valor,
                    MIN(matricula_valor) AS matricula_valor,
                    MIN(cfocantidad_pensiones) AS cantidad_pensiones
                FROM referencias";

        $statement = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $statement->execute();
        $row = $statement->fetch();

        if ($row === false || (int) ($row['total_estudiantes'] ?? 0) === 0) {
            return $this->emptyObligationReference();
        }

        $matriculationCount = (int) ($row['matricula_distintas'] ?? 0);
        $pensionCount = (int) ($row['pension_distintas'] ?? 0);
        $monthsCount = (int) ($row['meses_distintos'] ?? 0);

        return [
            'matricula' => $matriculationCount === 1 ? (float) $row['matricula_valor'] : null,
            'matricula_label' => $matriculationCount === 1 ? null : ($matriculationCount === 0 ? 'Sin configurar' : 'Varios'),
            'pension' => $pensionCount === 1 ? (float) $row['pension_valor'] : null,
            'pension_label' => $pensionCount === 1 ? null : ($pensionCount === 0 ? 'Sin configurar' : 'Varios'),
            'meses' => $monthsCount === 1 ? (int) $row['cantidad_pensiones'] : null,
            'meses_label' => $monthsCount === 1 ? null : ($monthsCount === 0 ? 'Sin configurar' : 'Varios'),
        ];
    }

    public function generateStudentObligations(int $matriculationId, int $periodId, int $userId, float $scholarshipPercent = 0.0, float $scholarshipAmount = 0.0): array
    {
        $context = $this->matriculationAccountingContext($matriculationId, $periodId);

        if ($context === false) {
            throw new RuntimeException('La matricula seleccionada no pertenece al periodo actual.');
        }

        $pensionConfig = $this->applicableObligationConfiguration($periodId, (int) $context['nedid'], (int) $context['graid'], 'PENSION');
        $matriculationConfig = $this->applicableObligationConfiguration($periodId, (int) $context['nedid'], (int) $context['graid'], 'MATRICULA');

        if ($pensionConfig === false || $matriculationConfig === false) {
            throw new RuntimeException('Falta configurar pension o matricula para el nivel/grado del estudiante.');
        }

        $discount = $this->normalizePensionDiscount((float) $pensionConfig['cfovalor_oficial'], $scholarshipPercent, $scholarshipAmount);
        $created = ['matricula' => 0, 'pensiones' => 0];
        $this->db->beginTransaction();

        try {
            $created['matricula'] = $this->insertMatriculationObligationIfMissing($matriculationId, $matriculationConfig, $userId);
            $created['pensiones'] = $this->insertPensionObligationsIfMissing($matriculationId, $pensionConfig, $userId, $discount);
            $this->updateExistingPensionValues($matriculationId, $pensionConfig, $discount);
            $this->audit(
                'matricula',
                $matriculationId,
                ((int) $created['matricula'] + (int) $created['pensiones']) > 0 ? 'CREAR' : 'EDITAR',
                null,
                [
                    'matricula_creada' => $created['matricula'],
                    'pensiones_creadas' => $created['pensiones'],
                    'beca_porcentaje' => $scholarshipPercent,
                    'beca_valor' => $scholarshipAmount,
                ],
                $userId,
                'Generacion o actualizacion de obligaciones'
            );
            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }

        return $created;
    }

    public function studentObligations(int $matriculationId, int $periodId): array
    {
        if ($matriculationId <= 0 || $periodId <= 0) {
            return [];
        }

        $statement = $this->db->prepare(
            "SELECT
                    o.cobid,
                    o.cobtipo,
                    o.cobdescripcion,
                    o.cobanio,
                    o.cobmes,
                    o.cobfecha_vencimiento,
                    o.cobvalor_base,
                    o.cobvalor_descuento,
                    o.cobvalor_final,
                    o.cobvalor_pagado,
                    o.cobsaldo_pendiente,
                    o.cobestado
             FROM contabilidad_obligacion o
             INNER JOIN matricula m ON m.matid = o.matid
             INNER JOIN curso c ON c.curid = m.curid
             WHERE o.matid = :matid
               AND c.pleid = :period_id
             ORDER BY o.coborden ASC, o.cobanio NULLS FIRST, o.cobmes NULLS FIRST, o.cobid ASC"
        );
        $statement->execute([
            'matid' => $matriculationId,
            'period_id' => $periodId,
        ]);

        return $statement->fetchAll();
    }

    public function updateObligationFinalValue(int $obligationId, int $periodId, float $finalValue, int $userId = 0): void
    {
        $obligation = $this->findPeriodObligation($obligationId, $periodId);

        if ($obligation === false) {
            throw new RuntimeException('La obligacion seleccionada no existe.');
        }

        if ((string) $obligation['cobestado'] === 'ANULADO') {
            throw new RuntimeException('No se puede editar una obligacion anulada.');
        }

        $baseValue = (float) $obligation['cobvalor_base'];
        $paidValue = (float) $obligation['cobvalor_pagado'];
        $finalValue = round(max(0.0, min($baseValue, $finalValue)), 2);

        if ($finalValue < $paidValue) {
            throw new RuntimeException('El valor final no puede ser menor al valor ya pagado.');
        }

        $discount = round($baseValue - $finalValue, 2);
        $statement = $this->db->prepare(
            "UPDATE contabilidad_obligacion
             SET cobdescuento_tipo = :discount_type,
                 cobdescuento_valor = :discount_value,
                 cobvalor_descuento = :discount_amount,
                 cobvalor_final = :final_value,
                 cobsaldo_pendiente = :final_value - cobvalor_pagado,
                 cobfecha_modificacion = CURRENT_TIMESTAMP
             WHERE cobid = :id"
        );
        $this->bindNullableString($statement, ':discount_type', $discount > 0 ? 'VALOR_FIJO' : null);
        $this->bindNullableString($statement, ':discount_value', $discount > 0 ? number_format($discount, 2, '.', '') : null);
        $statement->bindValue(':discount_amount', number_format($discount, 2, '.', ''));
        $statement->bindValue(':final_value', number_format($finalValue, 2, '.', ''));
        $statement->bindValue(':id', $obligationId, PDO::PARAM_INT);
        $statement->execute();

        if ($userId > 0) {
            $this->audit(
                'contabilidad_obligacion',
                $obligationId,
                'EDITAR',
                $obligation,
                ['cobvalor_final' => number_format($finalValue, 2, '.', ''), 'cobvalor_descuento' => number_format($discount, 2, '.', '')],
                $userId,
                'Actualizacion de valor final de obligacion'
            );
        }
    }

    public function annulObligation(int $obligationId, int $periodId, int $userId, string $reason): void
    {
        $reason = trim($reason);
        $obligation = $this->findPeriodObligation($obligationId, $periodId);

        if ($obligation === false) {
            throw new RuntimeException('La obligacion seleccionada no existe.');
        }

        if ((string) $obligation['cobestado'] === 'ANULADO') {
            throw new RuntimeException('La obligacion ya esta anulada.');
        }

        if ((float) $obligation['cobvalor_pagado'] > 0) {
            throw new RuntimeException('No se puede anular una obligacion con pagos registrados.');
        }

        if ($reason === '') {
            throw new RuntimeException('Debe ingresar un motivo para anular la obligacion.');
        }

        $statement = $this->db->prepare(
            "UPDATE contabilidad_obligacion
             SET cobestado = 'ANULADO',
                 usuid_anulacion = :user_id,
                 cobfecha_anulacion = CURRENT_TIMESTAMP,
                 cobmotivo_anulacion = :reason,
                 cobfecha_modificacion = CURRENT_TIMESTAMP
             WHERE cobid = :id"
        );
        $statement->execute([
            'user_id' => $userId,
            'reason' => $reason,
            'id' => $obligationId,
        ]);

        $this->audit(
            'contabilidad_obligacion',
            $obligationId,
            'ANULAR',
            $obligation,
            ['cobestado' => 'ANULADO', 'motivo' => $reason],
            $userId,
            $reason
        );
    }

    public function additionalItemConcepts(): array
    {
        $statement = $this->db->query(
            "SELECT ccoid, ccocodigo, cconombre, ccodescripcion, ccoestado
             FROM contabilidad_concepto
             WHERE ccocategoria = 'RUBRO'
             ORDER BY cconombre ASC"
        );

        return $statement->fetchAll();
    }

    public function activeAdditionalItemConcepts(): array
    {
        return array_values(array_filter($this->additionalItemConcepts(), static fn (array $concept): bool => !empty($concept['ccoestado'])));
    }

    public function createAdditionalItemConcept(string $name, string $description = ''): void
    {
        $name = trim($name);
        $description = trim($description);

        if ($name === '') {
            throw new RuntimeException('Ingrese el nombre del concepto.');
        }

        $code = $this->conceptCodeFromName($name);
        $statement = $this->db->prepare(
            "INSERT INTO contabilidad_concepto (ccocodigo, cconombre, ccocategoria, ccodescripcion, ccoestado)
             VALUES (:code, :name, 'RUBRO', :description, true)
             ON CONFLICT (ccocategoria, ccocodigo) DO UPDATE
             SET cconombre = EXCLUDED.cconombre,
                 ccodescripcion = EXCLUDED.ccodescripcion,
                 ccoestado = true,
                 ccofecha_modificacion = CURRENT_TIMESTAMP"
        );
        $statement->execute([
            'code' => $code,
            'name' => $name,
            'description' => $description !== '' ? $description : null,
        ]);
    }

    public function updateAdditionalItemConcept(int $conceptId, string $name, string $description = '', bool $active = true): void
    {
        $name = trim($name);
        $description = trim($description);

        if ($conceptId <= 0 || $name === '') {
            throw new RuntimeException('Complete los datos del concepto.');
        }

        $statement = $this->db->prepare(
            "UPDATE contabilidad_concepto
             SET ccocodigo = :code,
                 cconombre = :name,
                 ccodescripcion = :description,
                 ccoestado = :active,
                 ccofecha_modificacion = CURRENT_TIMESTAMP
             WHERE ccoid = :id
               AND ccocategoria = 'RUBRO'"
        );
        $statement->bindValue(':code', $this->conceptCodeFromName($name));
        $statement->bindValue(':name', $name);
        $this->bindNullableString($statement, ':description', $description);
        $statement->bindValue(':active', $active, PDO::PARAM_BOOL);
        $statement->bindValue(':id', $conceptId, PDO::PARAM_INT);
        $statement->execute();
    }

    public function deleteAdditionalItemConcept(int $conceptId): void
    {
        if ($conceptId <= 0) {
            throw new RuntimeException('Seleccione un concepto valido.');
        }

        $used = $this->db->prepare(
            "SELECT 1
             FROM contabilidad_rubro
             WHERE ccoid = :id
             LIMIT 1"
        );
        $used->execute(['id' => $conceptId]);

        if ($used->fetchColumn() !== false) {
            $this->updateAdditionalItemConceptStatus($conceptId, false);
            return;
        }

        $statement = $this->db->prepare(
            "DELETE FROM contabilidad_concepto
             WHERE ccoid = :id
               AND ccocategoria = 'RUBRO'"
        );
        $statement->execute(['id' => $conceptId]);
    }

    public function paymentMethods(): array
    {
        $statement = $this->db->query(
            "SELECT cmpid, cmpcodigo, cmpnombre
             FROM contabilidad_metodo_pago
             WHERE cmpestado = true
             ORDER BY cmpnombre ASC"
        );

        return $statement->fetchAll();
    }

    public function additionalItemStudents(int $periodId): array
    {
        if ($periodId <= 0) {
            return [];
        }

        $statement = $this->db->prepare(
            "SELECT
                    m.matid,
                    pe.percedula,
                    pe.pernombres,
                    pe.perapellidos,
                    g.granombre,
                    pr.prlnombre
             FROM matricula m
             INNER JOIN curso c ON c.curid = m.curid
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN paralelo pr ON pr.prlid = c.prlid
             INNER JOIN estudiante e ON e.estid = m.estid
             INNER JOIN persona pe ON pe.perid = e.perid
             WHERE c.pleid = :period_id
             ORDER BY pe.perapellidos, pe.pernombres"
        );
        $statement->execute(['period_id' => $periodId]);

        return $statement->fetchAll();
    }

    public function additionalItems(int $periodId): array
    {
        if ($periodId <= 0) {
            return [];
        }

        $statement = $this->db->prepare(
            "SELECT
                    r.cruid,
                    r.crunombre,
                    r.crudescripcion,
                    r.cruvalor,
                    r.crufecha_limite,
                    r.cruestado,
                    co.cconombre,
                    a.craalcance,
                    n.nednombre,
                    g.granombre,
                    pr.prlnombre,
                    pe.pernombres,
                    pe.perapellidos,
                    COUNT(re.creid) AS total_asignados,
                    COUNT(re.creid) FILTER (WHERE re.creestado IN ('PENDIENTE', 'VENCIDO')) AS total_pendientes,
                    COUNT(re.creid) FILTER (WHERE re.creestado = 'PAGADO') AS total_pagados,
                    COALESCE(SUM(re.crevalor) FILTER (WHERE re.creestado IN ('PENDIENTE', 'VENCIDO')), 0) AS valor_pendiente
             FROM contabilidad_rubro r
             INNER JOIN contabilidad_concepto co ON co.ccoid = r.ccoid
             LEFT JOIN contabilidad_rubro_asignacion a ON a.cruid = r.cruid
             LEFT JOIN nivel_educativo n ON n.nedid = a.nedid
             LEFT JOIN curso c ON c.curid = a.curid
             LEFT JOIN grado g ON g.graid = c.graid
             LEFT JOIN paralelo pr ON pr.prlid = c.prlid
             LEFT JOIN matricula m ON m.matid = a.matid
             LEFT JOIN estudiante e ON e.estid = m.estid
             LEFT JOIN persona pe ON pe.perid = e.perid
             LEFT JOIN contabilidad_rubro_estudiante re ON re.cruid = r.cruid
             WHERE r.pleid = :period_id
             GROUP BY r.cruid, co.cconombre, a.craalcance, n.nednombre, g.granombre, pr.prlnombre, pe.pernombres, pe.perapellidos
             ORDER BY r.crufecha_creacion DESC, r.cruid DESC"
        );
        $statement->execute(['period_id' => $periodId]);

        return $statement->fetchAll();
    }

    public function additionalItemAssignments(int $additionalItemId, int $periodId): array
    {
        return $this->additionalItemAssignmentsPage($additionalItemId, $periodId)['rows'];
    }

    public function additionalItemAssignmentsPage(int $additionalItemId, int $periodId, array $filters = [], int $limit = 25, int $page = 1): array
    {
        if ($additionalItemId <= 0 || $periodId <= 0) {
            return [
                'rows' => [],
                'total' => 0,
                'page' => 1,
                'limit' => max(1, $limit),
                'pages' => 1,
            ];
        }

        $conditions = ['r.cruid = :item_id', 'r.pleid = :period_id'];
        $params = [
            'item_id' => $additionalItemId,
            'period_id' => $periodId,
        ];
        $search = trim((string) ($filters['q'] ?? ''));
        $status = strtoupper(trim((string) ($filters['estado'] ?? '')));
        $courseId = (int) ($filters['curso'] ?? 0);
        $validStatuses = ['PENDIENTE', 'PAGADO', 'VENCIDO', 'EXONERADO', 'NO_APLICA', 'ANULADO'];

        if ($search !== '') {
            $conditions[] = "(lower(pe.pernombres || ' ' || pe.perapellidos) LIKE lower(:search)
                OR lower(pe.perapellidos || ' ' || pe.pernombres) LIKE lower(:search)
                OR lower(COALESCE(pe.percedula, '')) LIKE lower(:search))";
            $params['search'] = '%' . $search . '%';
        }

        if (in_array($status, $validStatuses, true)) {
            $conditions[] = 're.creestado = :status';
            $params['status'] = $status;
        }

        if ($courseId > 0) {
            $conditions[] = 'c.curid = :course_id';
            $params['course_id'] = $courseId;
        }

        $limit = min(100, max(10, $limit));
        $page = max(1, $page);
        $whereSql = implode(' AND ', $conditions);

        $countStatement = $this->db->prepare(
            "SELECT COUNT(*)
             FROM contabilidad_rubro_estudiante re
             INNER JOIN contabilidad_rubro r ON r.cruid = re.cruid
             INNER JOIN matricula m ON m.matid = re.matid
             INNER JOIN curso c ON c.curid = m.curid
             INNER JOIN estudiante e ON e.estid = m.estid
             INNER JOIN persona pe ON pe.perid = e.perid
             WHERE {$whereSql}"
        );
        foreach ($params as $key => $value) {
            $countStatement->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $countStatement->execute();
        $total = (int) $countStatement->fetchColumn();
        $pages = max(1, (int) ceil($total / $limit));
        $page = min($page, $pages);
        $offset = ($page - 1) * $limit;

        $statement = $this->db->prepare(
            "SELECT
                    re.creid,
                    re.crevalor,
                    re.crefecha_limite,
                    re.creestado,
                    re.creobservacion_interna,
                    pe.percedula,
                    pe.pernombres,
                    pe.perapellidos,
                    c.curid,
                    g.granombre,
                    pr.prlnombre,
                    pay.cpagid,
                    pay.cpagreferencia
             FROM contabilidad_rubro_estudiante re
             INNER JOIN contabilidad_rubro r ON r.cruid = re.cruid
             INNER JOIN matricula m ON m.matid = re.matid
             INNER JOIN curso c ON c.curid = m.curid
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN paralelo pr ON pr.prlid = c.prlid
             INNER JOIN estudiante e ON e.estid = m.estid
             INNER JOIN persona pe ON pe.perid = e.perid
             LEFT JOIN LATERAL (
                 SELECT p.cpagid, p.cpagreferencia
                 FROM contabilidad_pago_rubro prb
                 INNER JOIN contabilidad_pago p ON p.cpagid = prb.cpagid
                 WHERE prb.creid = re.creid
                   AND prb.cprestado = 'ACTIVO'
                   AND p.cpagestado = 'APROBADO'
                 ORDER BY p.cpagfecha_registro DESC, p.cpagid DESC
                 LIMIT 1
             ) pay ON true
             WHERE {$whereSql}
             ORDER BY pe.perapellidos, pe.pernombres"
            . " LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $statement->execute();

        return [
            'rows' => $statement->fetchAll(),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => $pages,
        ];
    }

    public function createAdditionalItem(int $periodId, int $userId, array $data): int
    {
        $conceptId = (int) ($data['ccoid'] ?? 0);
        $name = trim((string) ($data['crunombre'] ?? ''));
        $description = trim((string) ($data['crudescripcion'] ?? ''));
        $value = round((float) ($data['cruvalor'] ?? 0), 2);
        $deadline = trim((string) ($data['crufecha_limite'] ?? ''));
        $scope = strtoupper(trim((string) ($data['craalcance'] ?? '')));
        $levelId = (int) ($data['nedid'] ?? 0);
        $courseId = (int) ($data['curid'] ?? 0);
        $matriculationId = (int) ($data['matid'] ?? 0);

        if ($periodId <= 0) {
            throw new RuntimeException('Seleccione un periodo lectivo para crear rubros.');
        }

        $this->assertAdditionalItemConcept($conceptId);

        if ($conceptId <= 0 || $name === '' || $value <= 0) {
            throw new RuntimeException('Complete concepto, nombre y valor del rubro.');
        }

        if (!in_array($scope, ['TODOS', 'NIVEL', 'CURSO', 'ESTUDIANTE'], true)) {
            throw new RuntimeException('Seleccione un alcance valido para el rubro.');
        }

        if (($scope === 'NIVEL' && $levelId <= 0) || ($scope === 'CURSO' && $courseId <= 0) || ($scope === 'ESTUDIANTE' && $matriculationId <= 0)) {
            throw new RuntimeException('Seleccione el destino del rubro.');
        }

        $this->db->beginTransaction();

        try {
            $statement = $this->db->prepare(
                "INSERT INTO contabilidad_rubro (
                    pleid, ccoid, crunombre, crudescripcion, cruvalor, crufecha_limite, usuid_registro
                 ) VALUES (
                    :period_id, :concept_id, :name, :description, :value, :deadline, :user_id
                 )
                 RETURNING cruid"
            );
            $statement->bindValue(':period_id', $periodId, PDO::PARAM_INT);
            $statement->bindValue(':concept_id', $conceptId, PDO::PARAM_INT);
            $statement->bindValue(':name', $name);
            $this->bindNullableString($statement, ':description', $description);
            $statement->bindValue(':value', number_format($value, 2, '.', ''));
            $this->bindNullableString($statement, ':deadline', $deadline);
            $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $statement->execute();
            $itemId = (int) $statement->fetchColumn();

            $assignment = $this->db->prepare(
                "INSERT INTO contabilidad_rubro_asignacion (cruid, craalcance, pleid, nedid, curid, matid)
                 VALUES (:item_id, :scope, :period_id, :level_id, :course_id, :matriculation_id)"
            );
            $assignment->bindValue(':item_id', $itemId, PDO::PARAM_INT);
            $assignment->bindValue(':scope', $scope);
            $assignment->bindValue(':period_id', $periodId, PDO::PARAM_INT);
            $this->bindNullableInt($assignment, ':level_id', $scope === 'NIVEL' ? $levelId : null);
            $this->bindNullableInt($assignment, ':course_id', $scope === 'CURSO' ? $courseId : null);
            $this->bindNullableInt($assignment, ':matriculation_id', $scope === 'ESTUDIANTE' ? $matriculationId : null);
            $assignment->execute();

            $this->materializeAdditionalItemAssignments($itemId, $periodId, $scope, $levelId, $courseId, $matriculationId, $value, $deadline, $userId);
            $this->audit(
                'contabilidad_rubro',
                $itemId,
                'CREAR',
                null,
                [
                    'ccoid' => $conceptId,
                    'crunombre' => $name,
                    'cruvalor' => number_format($value, 2, '.', ''),
                    'crufecha_limite' => $deadline,
                    'craalcance' => $scope,
                    'nedid' => $scope === 'NIVEL' ? $levelId : null,
                    'curid' => $scope === 'CURSO' ? $courseId : null,
                    'matid' => $scope === 'ESTUDIANTE' ? $matriculationId : null,
                ],
                $userId,
                'Creacion y asignacion de rubro adicional'
            );
            $this->db->commit();

            return $itemId;
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function closeAdditionalItemAssignment(int $assignmentId, int $periodId, int $userId, string $status, int $paymentMethodId = 0, string $reference = '', string $observation = ''): void
    {
        $status = strtoupper(trim($status));
        $reference = trim($reference);
        $observation = trim($observation);

        if (!in_array($status, ['PAGADO', 'EXONERADO', 'NO_APLICA', 'ANULADO'], true)) {
            throw new RuntimeException('Seleccione una accion valida para el rubro.');
        }

        $this->db->beginTransaction();

        try {
            $assignment = $this->findAdditionalItemAssignmentForUpdate($assignmentId, $periodId);

            if ($assignment === false) {
                throw new RuntimeException('La asignacion del rubro no existe.');
            }

            if (!in_array((string) $assignment['creestado'], ['PENDIENTE', 'VENCIDO'], true)) {
                throw new RuntimeException('El rubro seleccionado ya fue cerrado.');
            }

            if ($status === 'PAGADO') {
                $this->registerAdditionalItemPayment($assignment, $userId, $paymentMethodId, $reference, $observation);
            }

            $statement = $this->db->prepare(
                "UPDATE contabilidad_rubro_estudiante
                 SET creestado = :status,
                     creobservacion_interna = :observation,
                     usuid_cierre = :user_id,
                     crefecha_cierre = CURRENT_TIMESTAMP,
                     cremotivo_cierre = :reason,
                     crefecha_modificacion = CURRENT_TIMESTAMP
                 WHERE creid = :assignment_id"
            );
            $statement->execute([
                'status' => $status,
                'observation' => $observation !== '' ? $observation : null,
                'user_id' => $userId,
                'reason' => $observation !== '' ? $observation : $status,
                'assignment_id' => $assignmentId,
            ]);

            $this->audit(
                'contabilidad_rubro_estudiante',
                $assignmentId,
                $status === 'ANULADO' ? 'ANULAR' : 'EDITAR',
                $assignment,
                [
                    'creestado' => $status,
                    'cmpid' => $paymentMethodId > 0 ? $paymentMethodId : null,
                    'referencia' => $reference,
                    'observacion' => $observation,
                ],
                $userId,
                $observation !== '' ? $observation : $status
            );

            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function representativeObligations(int $representativePersonId, int $periodId): array
    {
        if ($representativePersonId <= 0 || $periodId <= 0) {
            return [];
        }

        $statement = $this->db->prepare(
            "SELECT
                    o.cobid,
                    o.matid,
                    o.cobtipo,
                    o.cobdescripcion,
                    o.cobfecha_vencimiento,
                    o.cobvalor_final,
                    o.cobvalor_pagado,
                    o.cobsaldo_pendiente,
                    o.cobestado,
                    pe.pernombres,
                    pe.perapellidos,
                    pe.percedula,
                    g.granombre,
                    pr.prlnombre,
                    lp.cpagid,
                    lp.cpagestado,
                    lp.cpagfecha_registro,
                    lp.cpagmotivo_rechazo,
                    COALESCE(ph.comprobantes, '[]'::jsonb) AS comprobantes
             FROM matricula_representante mr
             INNER JOIN matricula m ON m.matid = mr.matid
             INNER JOIN curso c ON c.curid = m.curid
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN paralelo pr ON pr.prlid = c.prlid
             INNER JOIN estudiante e ON e.estid = m.estid
             INNER JOIN persona pe ON pe.perid = e.perid
             INNER JOIN contabilidad_obligacion o ON o.matid = m.matid
             LEFT JOIN LATERAL (
                 SELECT p.cpagid, p.cpagestado, p.cpagfecha_registro, p.cpagmotivo_rechazo
                 FROM contabilidad_pago p
                 WHERE p.cobid_sugerido = o.cobid
                   AND p.cpagorigen = 'REPRESENTANTE'
                 ORDER BY p.cpagfecha_registro DESC, p.cpagid DESC
                 LIMIT 1
             ) lp ON true
             LEFT JOIN LATERAL (
                 SELECT jsonb_agg(
                     jsonb_build_object(
                         'cpagid', p.cpagid,
                         'estado', p.cpagestado,
                         'valor_reportado', p.cpagvalor_reportado,
                         'valor_aprobado', p.cpagvalor_aprobado,
                         'fecha_registro', p.cpagfecha_registro,
                         'fecha_revision', p.cpagfecha_revision,
                         'archivo_ruta', p.cpagarchivo_ruta,
                         'archivo_nombre', p.cpagarchivo_nombre,
                         'motivo_rechazo', p.cpagmotivo_rechazo
                     )
                     ORDER BY p.cpagfecha_registro ASC, p.cpagid ASC
                 ) AS comprobantes
                 FROM contabilidad_pago p
                 WHERE p.cobid_sugerido = o.cobid
                   AND p.cpagorigen = 'REPRESENTANTE'
             ) ph ON true
             WHERE mr.perid = :representative_person_id
               AND c.pleid = :period_id
               AND o.cobestado <> 'ANULADO'
             ORDER BY pe.perapellidos, pe.pernombres, o.coborden, o.cobid"
        );
        $statement->execute([
            'representative_person_id' => $representativePersonId,
            'period_id' => $periodId,
        ]);

        return $statement->fetchAll();
    }

    public function exportPendingObligations(int $periodId): array
    {
        if ($periodId <= 0) {
            return [];
        }

        $statement = $this->db->prepare(
            "SELECT
                    pe.percedula,
                    pe.perapellidos,
                    pe.pernombres,
                    n.nednombre,
                    g.granombre,
                    pr.prlnombre,
                    o.cobtipo,
                    o.cobdescripcion,
                    o.cobfecha_vencimiento,
                    o.cobvalor_final,
                    o.cobvalor_pagado,
                    o.cobsaldo_pendiente,
                    o.cobestado
             FROM contabilidad_obligacion o
             INNER JOIN matricula m ON m.matid = o.matid
             INNER JOIN curso c ON c.curid = m.curid
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN nivel_educativo n ON n.nedid = g.nedid
             INNER JOIN paralelo pr ON pr.prlid = c.prlid
             INNER JOIN estudiante e ON e.estid = m.estid
             INNER JOIN persona pe ON pe.perid = e.perid
             WHERE c.pleid = :period_id
               AND o.cobestado <> 'ANULADO'
               AND o.cobsaldo_pendiente > 0
             ORDER BY pe.perapellidos, pe.pernombres, o.coborden, o.cobid"
        );
        $statement->execute(['period_id' => $periodId]);

        return $statement->fetchAll();
    }

    public function exportReviewedPayments(int $periodId): array
    {
        if ($periodId <= 0) {
            return [];
        }

        $statement = $this->db->prepare(
            "SELECT
                    p.cpagid,
                    p.cpagestado,
                    p.cpagorigen,
                    p.cpagvalor_reportado,
                    p.cpagvalor_aprobado,
                    p.cpagfecha_registro,
                    p.cpagfecha_revision,
                    p.cpagfecha_reverso,
                    p.cpagreferencia,
                    p.cpagdocumento_externo_numero,
                    p.cpagmotivo_rechazo,
                    p.cpagmotivo_reverso,
                    mp.cmpnombre,
                    pe.percedula,
                    pe.perapellidos,
                    pe.pernombres,
                    g.granombre,
                    pr.prlnombre,
                    COALESCE(o.cobdescripcion, r.crunombre) AS concepto,
                    CASE WHEN o.cobid IS NOT NULL THEN 'OBLIGACION' ELSE 'RUBRO' END AS tipo_concepto
             FROM contabilidad_pago p
             INNER JOIN matricula m ON m.matid = p.matid
             INNER JOIN curso c ON c.curid = m.curid
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN paralelo pr ON pr.prlid = c.prlid
             INNER JOIN estudiante e ON e.estid = m.estid
             INNER JOIN persona pe ON pe.perid = e.perid
             LEFT JOIN contabilidad_metodo_pago mp ON mp.cmpid = p.cmpid
             LEFT JOIN contabilidad_obligacion o ON o.cobid = p.cobid_sugerido
             LEFT JOIN contabilidad_rubro_estudiante re ON re.creid = p.creid_sugerido
             LEFT JOIN contabilidad_rubro r ON r.cruid = re.cruid
             WHERE c.pleid = :period_id
               AND p.cpagestado IN ('APROBADO', 'RECHAZADO', 'REVERSADO')
             ORDER BY p.cpagfecha_registro DESC, p.cpagid DESC"
        );
        $statement->execute(['period_id' => $periodId]);

        return $statement->fetchAll();
    }

    public function exportAdditionalItems(int $periodId): array
    {
        if ($periodId <= 0) {
            return [];
        }

        $statement = $this->db->prepare(
            "SELECT
                    r.crunombre,
                    co.cconombre,
                    r.cruvalor,
                    r.crufecha_limite,
                    pe.percedula,
                    pe.perapellidos,
                    pe.pernombres,
                    n.nednombre,
                    g.granombre,
                    pr.prlnombre,
                    re.crevalor,
                    re.crefecha_limite,
                    re.creestado,
                    re.creobservacion_interna,
                    pay.cpagreferencia,
                    pay.cpagfecha_revision
             FROM contabilidad_rubro_estudiante re
             INNER JOIN contabilidad_rubro r ON r.cruid = re.cruid
             INNER JOIN contabilidad_concepto co ON co.ccoid = r.ccoid
             INNER JOIN matricula m ON m.matid = re.matid
             INNER JOIN curso c ON c.curid = m.curid
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN nivel_educativo n ON n.nedid = g.nedid
             INNER JOIN paralelo pr ON pr.prlid = c.prlid
             INNER JOIN estudiante e ON e.estid = m.estid
             INNER JOIN persona pe ON pe.perid = e.perid
             LEFT JOIN LATERAL (
                 SELECT p.cpagreferencia, p.cpagfecha_revision
                 FROM contabilidad_pago_rubro prb
                 INNER JOIN contabilidad_pago p ON p.cpagid = prb.cpagid
                 WHERE prb.creid = re.creid
                   AND prb.cprestado = 'ACTIVO'
                   AND p.cpagestado = 'APROBADO'
                 ORDER BY p.cpagfecha_revision DESC NULLS LAST, p.cpagid DESC
                 LIMIT 1
             ) pay ON true
             WHERE r.pleid = :period_id
               AND c.pleid = :period_id
             ORDER BY r.crunombre, pe.perapellidos, pe.pernombres"
        );
        $statement->execute(['period_id' => $periodId]);

        return $statement->fetchAll();
    }

    public function representativeAdditionalItems(int $representativePersonId, int $periodId): array
    {
        if ($representativePersonId <= 0 || $periodId <= 0) {
            return [];
        }

        $statement = $this->db->prepare(
            "SELECT
                    re.creid,
                    re.matid,
                    re.crevalor,
                    re.crefecha_limite,
                    re.creestado,
                    r.crunombre,
                    r.crudescripcion,
                    co.cconombre,
                    pe.pernombres,
                    pe.perapellidos,
                    pe.percedula,
                    g.granombre,
                    pr.prlnombre,
                    pay.cpagreferencia,
                    pay.cpagfecha_revision
             FROM matricula_representante mr
             INNER JOIN matricula m ON m.matid = mr.matid
             INNER JOIN curso c ON c.curid = m.curid
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN paralelo pr ON pr.prlid = c.prlid
             INNER JOIN estudiante e ON e.estid = m.estid
             INNER JOIN persona pe ON pe.perid = e.perid
             INNER JOIN contabilidad_rubro_estudiante re ON re.matid = m.matid
             INNER JOIN contabilidad_rubro r ON r.cruid = re.cruid
             INNER JOIN contabilidad_concepto co ON co.ccoid = r.ccoid
             LEFT JOIN LATERAL (
                 SELECT p.cpagreferencia, p.cpagfecha_revision
                 FROM contabilidad_pago_rubro prb
                 INNER JOIN contabilidad_pago p ON p.cpagid = prb.cpagid
                 WHERE prb.creid = re.creid
                   AND prb.cprestado = 'ACTIVO'
                   AND p.cpagestado = 'APROBADO'
                 ORDER BY p.cpagfecha_revision DESC NULLS LAST, p.cpagid DESC
                 LIMIT 1
             ) pay ON true
             WHERE mr.perid = :representative_person_id
               AND c.pleid = :period_id
               AND r.pleid = :period_id
               AND re.creestado <> 'ANULADO'
             ORDER BY pe.perapellidos, pe.pernombres, re.crefecha_limite NULLS LAST, r.crunombre"
        );
        $statement->execute([
            'representative_person_id' => $representativePersonId,
            'period_id' => $periodId,
        ]);

        return $statement->fetchAll();
    }

    public function registerRepresentativeReceipt(int $representativePersonId, int $userId, int $periodId, int $obligationId, array $fileData): void
    {
        $obligation = $this->findRepresentativeObligation($representativePersonId, $periodId, $obligationId);

        if ($obligation === false) {
            throw new RuntimeException('La obligacion seleccionada no esta disponible para este representante.');
        }

        if (in_array((string) $obligation['cobestado'], ['PAGADO', 'ANULADO'], true)) {
            throw new RuntimeException('La obligacion seleccionada no requiere comprobante.');
        }

        if ($this->hasPendingReceiptForObligation($obligationId)) {
            throw new RuntimeException('Ya existe un comprobante pendiente de revision para esta obligacion.');
        }

        $reportedValue = max(0.01, (float) ($obligation['cobsaldo_pendiente'] ?? 0));
        $this->db->beginTransaction();

        try {
            $statement = $this->db->prepare(
                "INSERT INTO contabilidad_pago (
                    matid,
                    cobid_sugerido,
                    cpagorigen,
                    cpagestado,
                    cpagvalor_reportado,
                    cpagarchivo_ruta,
                    cpagarchivo_nombre,
                    cpagarchivo_extension,
                    cpagarchivo_mime,
                    cpagarchivo_tamano,
                    cpagarchivo_hash,
                    usuid_registro
                 ) VALUES (
                    :matid,
                    :cobid,
                    'REPRESENTANTE',
                    'EN_REVISION',
                    :valor_reportado,
                    :archivo_ruta,
                    :archivo_nombre,
                    :archivo_extension,
                    :archivo_mime,
                    :archivo_tamano,
                    :archivo_hash,
                    :usuario
                 )"
            );
            $statement->execute([
                'matid' => (int) $obligation['matid'],
                'cobid' => $obligationId,
                'valor_reportado' => number_format($reportedValue, 2, '.', ''),
                'archivo_ruta' => (string) $fileData['path'],
                'archivo_nombre' => (string) $fileData['original_name'],
                'archivo_extension' => (string) $fileData['extension'],
                'archivo_mime' => (string) $fileData['mime'],
                'archivo_tamano' => (int) $fileData['size'],
                'archivo_hash' => (string) $fileData['hash'],
                'usuario' => $userId,
            ]);

            $this->db->prepare(
                "UPDATE contabilidad_obligacion
                 SET cobestado = 'EN_REVISION',
                     cobfecha_modificacion = CURRENT_TIMESTAMP
                 WHERE cobid = :id"
            )->execute(['id' => $obligationId]);

            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function receiptsForReview(int $periodId, string $status = 'EN_REVISION', array $filters = []): array
    {
        return $this->receiptsForReviewPage($periodId, $status, $filters)['rows'];
    }

    public function receiptsForReviewPage(int $periodId, string $status = 'EN_REVISION', array $filters = [], int $limit = 25, int $page = 1): array
    {
        if ($periodId <= 0) {
            return [
                'rows' => [],
                'total' => 0,
                'page' => 1,
                'limit' => max(1, $limit),
                'pages' => 1,
            ];
        }

        $validStatuses = ['EN_REVISION', 'APROBADO', 'RECHAZADO', 'REVERSADO'];
        $status = in_array($status, $validStatuses, true) ? $status : 'EN_REVISION';
        $conditions = ['c.pleid = :period_id', 'p.cpagestado = :status'];
        $params = [
            'period_id' => $periodId,
            'status' => $status,
        ];
        $search = trim((string) ($filters['q'] ?? ''));
        $courseId = (int) ($filters['curso'] ?? 0);
        $limit = min(100, max(10, $limit));
        $page = max(1, $page);

        if ($search !== '') {
            $conditions[] = "(lower(pe.pernombres || ' ' || pe.perapellidos) LIKE lower(:search)
                OR lower(pe.perapellidos || ' ' || pe.pernombres) LIKE lower(:search)
                OR lower(COALESCE(pe.percedula, '')) LIKE lower(:search)
                OR lower(COALESCE(o.cobdescripcion, '')) LIKE lower(:search))";
            $params['search'] = '%' . $search . '%';
        }

        if ($courseId > 0) {
            $conditions[] = 'c.curid = :course_id';
            $params['course_id'] = $courseId;
        }

        $whereSql = implode(' AND ', $conditions);
        $countStatement = $this->db->prepare(
            "SELECT COUNT(*)
             FROM contabilidad_pago p
             INNER JOIN matricula m ON m.matid = p.matid
             INNER JOIN curso c ON c.curid = m.curid
             INNER JOIN estudiante e ON e.estid = m.estid
             INNER JOIN persona pe ON pe.perid = e.perid
             INNER JOIN contabilidad_obligacion o ON o.cobid = p.cobid_sugerido
             WHERE {$whereSql}"
        );
        foreach ($params as $key => $value) {
            $countStatement->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $countStatement->execute();
        $total = (int) $countStatement->fetchColumn();
        $pages = max(1, (int) ceil($total / $limit));
        $page = min($page, $pages);
        $offset = ($page - 1) * $limit;

        $statement = $this->db->prepare(
            "SELECT
                    p.cpagid,
                    p.cpagestado,
                    p.cpagvalor_reportado,
                    p.cpagvalor_aprobado,
                    p.cpagfecha_registro,
                    p.cpagfecha_revision,
                    p.cpagarchivo_ruta,
                    p.cpagarchivo_nombre,
                    p.cpagarchivo_extension,
                    p.cpagarchivo_hash,
                    p.cpagobservacion_interna,
                    p.cpagmotivo_rechazo,
                    p.cpagmotivo_reverso,
                    p.cpagfecha_reverso,
                    p.cpagdocumento_externo_numero,
                    o.cobid,
                    o.cobdescripcion,
                    o.cobvalor_final,
                    o.cobvalor_pagado,
                    o.cobsaldo_pendiente,
                    o.cobestado,
                    pe.pernombres,
                    pe.perapellidos,
                    pe.percedula,
                    c.curid,
                    g.granombre,
                    pr.prlnombre,
                    dup.cpagid AS duplicado_cpagid
             FROM contabilidad_pago p
             INNER JOIN matricula m ON m.matid = p.matid
             INNER JOIN curso c ON c.curid = m.curid
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN paralelo pr ON pr.prlid = c.prlid
             INNER JOIN estudiante e ON e.estid = m.estid
             INNER JOIN persona pe ON pe.perid = e.perid
             INNER JOIN contabilidad_obligacion o ON o.cobid = p.cobid_sugerido
             LEFT JOIN LATERAL (
                 SELECT pd.cpagid
                 FROM contabilidad_pago pd
                 WHERE pd.cpagid <> p.cpagid
                   AND pd.cpagarchivo_hash = p.cpagarchivo_hash
                   AND pd.cpagarchivo_hash IS NOT NULL
                   AND pd.cpagestado IN ('EN_REVISION', 'APROBADO')
                 ORDER BY pd.cpagfecha_registro DESC, pd.cpagid DESC
                 LIMIT 1
             ) dup ON true
             WHERE {$whereSql}
             ORDER BY p.cpagfecha_registro DESC, p.cpagid DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $statement->execute();

        return [
            'rows' => $statement->fetchAll(),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => $pages,
        ];
    }

    public function approveReceipt(int $paymentId, int $periodId, int $userId, float $approvedValue, string $observation = '', string $externalNumber = '', bool $allowDuplicate = false): void
    {
        $approvedValue = round($approvedValue, 2);
        $observation = trim($observation);
        $externalNumber = trim($externalNumber);

        if ($approvedValue <= 0) {
            throw new RuntimeException('Ingrese un valor aprobado mayor a cero.');
        }

        $this->db->beginTransaction();

        try {
            $payment = $this->findPeriodPaymentForUpdate($paymentId, $periodId);

            if ($payment === false) {
                throw new RuntimeException('El comprobante seleccionado no existe.');
            }

            if ((string) $payment['cpagestado'] !== 'EN_REVISION') {
                throw new RuntimeException('Solo se pueden aprobar comprobantes en revision.');
            }

            $duplicateId = $this->duplicatePaymentIdByHash($paymentId, (string) ($payment['cpagarchivo_hash'] ?? ''));

            if ($duplicateId > 0 && !$allowDuplicate) {
                throw new RuntimeException('Revise el posible duplicado #' . $duplicateId . ' antes de aprobar el comprobante.');
            }

            if ($duplicateId > 0 && $observation === '') {
                throw new RuntimeException('Debe ingresar una observacion para aprobar un comprobante marcado como duplicado.');
            }

            $remaining = $approvedValue;

            $updatePayment = $this->db->prepare(
                "UPDATE contabilidad_pago
                 SET cpagestado = 'APROBADO',
                     cpagvalor_aprobado = :approved_value,
                     cpagobservacion_interna = :observation,
                     cpagdocumento_externo_sistema = :external_system,
                     cpagdocumento_externo_numero = :external_number,
                     usuid_revision = :user_id,
                     cpagfecha_revision = CURRENT_TIMESTAMP
                 WHERE cpagid = :payment_id"
            );
            $updatePayment->execute([
                'approved_value' => number_format($approvedValue, 2, '.', ''),
                'observation' => $observation !== '' ? $observation : null,
                'external_system' => $externalNumber !== '' ? 'PERSEO' : null,
                'external_number' => $externalNumber !== '' ? $externalNumber : null,
                'user_id' => $userId,
                'payment_id' => $paymentId,
            ]);

            $remaining = $this->applyPaymentToObligation($paymentId, $payment, $remaining);

            if ($remaining > 0.0) {
                foreach ($this->nextPendingObligationsForPayment((int) $payment['matid'], $periodId, (int) $payment['cobid_sugerido']) as $nextObligation) {
                    $remaining = $this->applyPaymentToObligation($paymentId, $nextObligation, $remaining);

                    if ($remaining <= 0.0) {
                        break;
                    }
                }
            }

            if ($remaining > 0.0) {
                $this->db->prepare(
                    "INSERT INTO contabilidad_saldo_favor (
                        matid,
                        cpagid_origen,
                        csfvalor_inicial,
                        csfvalor_disponible,
                        csfobservacion
                     ) VALUES (
                        :matid,
                        :payment_id,
                        :value,
                        :value,
                        :observation
                     )"
                )->execute([
                    'matid' => (int) $payment['matid'],
                    'payment_id' => $paymentId,
                    'value' => number_format($remaining, 2, '.', ''),
                    'observation' => $observation !== '' ? $observation : 'Saldo a favor generado despues de abonar obligaciones futuras.',
                ]);
            }

            $this->audit(
                'contabilidad_pago',
                $paymentId,
                'APROBAR',
                ['cpagestado' => $payment['cpagestado'] ?? null, 'cobsaldo_pendiente' => $payment['cobsaldo_pendiente'] ?? null],
                [
                    'cpagestado' => 'APROBADO',
                    'cpagvalor_aprobado' => number_format($approvedValue, 2, '.', ''),
                    'saldo_favor_generado' => $remaining > 0.0 ? number_format($remaining, 2, '.', '') : null,
                    'documento_externo_numero' => $externalNumber !== '' ? $externalNumber : null,
                ],
                $userId,
                $observation
            );

            if ($duplicateId > 0 && $allowDuplicate) {
                $this->audit(
                    'contabilidad_pago',
                    $paymentId,
                    'DUPLICADO_ACEPTADO',
                    ['duplicado_cpagid' => $duplicateId],
                    ['observacion' => $observation],
                    $userId,
                    $observation
                );
            }

            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function rejectReceipt(int $paymentId, int $periodId, int $userId, string $reason): void
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new RuntimeException('Debe ingresar el motivo de rechazo.');
        }

        $this->db->beginTransaction();

        try {
            $payment = $this->findPeriodPaymentForUpdate($paymentId, $periodId);

            if ($payment === false) {
                throw new RuntimeException('El comprobante seleccionado no existe.');
            }

            if ((string) $payment['cpagestado'] !== 'EN_REVISION') {
                throw new RuntimeException('Solo se pueden rechazar comprobantes en revision.');
            }

            $this->db->prepare(
                "UPDATE contabilidad_pago
                 SET cpagestado = 'RECHAZADO',
                     cpagmotivo_rechazo = :reason,
                     usuid_revision = :user_id,
                     cpagfecha_revision = CURRENT_TIMESTAMP
                 WHERE cpagid = :payment_id"
            )->execute([
                'reason' => $reason,
                'user_id' => $userId,
                'payment_id' => $paymentId,
            ]);

            $paid = (float) ($payment['cobvalor_pagado'] ?? 0);
            $balance = (float) ($payment['cobsaldo_pendiente'] ?? 0);
            $status = $paid > 0 && $balance > 0 ? 'PAGO_PARCIAL' : 'PENDIENTE';

            $this->db->prepare(
                "UPDATE contabilidad_obligacion
                 SET cobestado = :status,
                     cobfecha_modificacion = CURRENT_TIMESTAMP
                 WHERE cobid = :obligation_id
                   AND cobestado = 'EN_REVISION'"
            )->execute([
                'status' => $status,
                'obligation_id' => (int) $payment['cobid_sugerido'],
            ]);

            $this->audit(
                'contabilidad_pago',
                $paymentId,
                'RECHAZAR',
                ['cpagestado' => $payment['cpagestado'] ?? null],
                ['cpagestado' => 'RECHAZADO', 'motivo' => $reason],
                $userId,
                $reason
            );

            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function reversePayment(int $paymentId, int $periodId, int $userId, string $reason): void
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new RuntimeException('Debe ingresar el motivo del reverso.');
        }

        $this->db->beginTransaction();

        try {
            $payment = $this->findPaymentForReverse($paymentId, $periodId);

            if ($payment === false) {
                throw new RuntimeException('El pago seleccionado no existe.');
            }

            if ((string) $payment['cpagestado'] !== 'APROBADO') {
                throw new RuntimeException('Solo se pueden reversar pagos aprobados.');
            }

            $this->reversePaymentBalances($paymentId);
            $this->reversePaymentObligations($paymentId);
            $this->reversePaymentAdditionalItems($paymentId);

            $this->db->prepare(
                "UPDATE contabilidad_pago
                 SET cpagestado = 'REVERSADO',
                     usuid_reverso = :user_id,
                     cpagfecha_reverso = CURRENT_TIMESTAMP,
                     cpagmotivo_reverso = :reason
                 WHERE cpagid = :payment_id"
            )->execute([
                'user_id' => $userId,
                'reason' => $reason,
                'payment_id' => $paymentId,
            ]);

            $this->audit(
                'contabilidad_pago',
                $paymentId,
                'REVERSAR',
                ['cpagestado' => $payment['cpagestado'] ?? null],
                ['cpagestado' => 'REVERSADO', 'motivo' => $reason],
                $userId,
                $reason
            );

            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
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

    private function paymentMonthOptions(int $periodId): array
    {
        $statement = $this->db->prepare(
            "SELECT DISTINCT
                    to_char(make_date(o.cobanio, o.cobmes, 1), 'YYYY-MM') AS value,
                    to_char(make_date(o.cobanio, o.cobmes, 1), 'TMMonth YYYY') AS label
             FROM contabilidad_obligacion o
             INNER JOIN matricula m ON m.matid = o.matid
             INNER JOIN curso c ON c.curid = m.curid
             WHERE c.pleid = :period_id
               AND o.cobtipo = 'PENSION'
               AND o.cobanio IS NOT NULL
               AND o.cobmes IS NOT NULL
               AND o.cobestado <> 'ANULADO'
             ORDER BY value"
        );
        $statement->execute(['period_id' => $periodId]);

        return $statement->fetchAll();
    }

    private function normalizeChartMonth(?string $month): ?string
    {
        $month = trim((string) ($month ?? ''));

        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            return null;
        }

        return $month;
    }

    private function receiptStatusChart(int $periodId): array
    {
        $statement = $this->db->prepare(
            "SELECT p.cpagestado AS label, COUNT(*) AS value
             FROM contabilidad_pago p
             INNER JOIN matricula m ON m.matid = p.matid
             INNER JOIN curso c ON c.curid = m.curid
             WHERE c.pleid = :period_id
             GROUP BY p.cpagestado
             ORDER BY p.cpagestado"
        );
        $statement->execute(['period_id' => $periodId]);

        return $statement->fetchAll();
    }

    private function obligationStatusChart(int $periodId): array
    {
        $statement = $this->db->prepare(
            "SELECT
                    o.cobestado AS label,
                    COUNT(*) AS value,
                    COALESCE(SUM(o.cobsaldo_pendiente), 0) AS amount
             FROM contabilidad_obligacion o
             INNER JOIN matricula m ON m.matid = o.matid
             INNER JOIN curso c ON c.curid = m.curid
             WHERE c.pleid = :period_id
               AND o.cobestado <> 'ANULADO'
             GROUP BY o.cobestado
             ORDER BY o.cobestado"
        );
        $statement->execute(['period_id' => $periodId]);

        return $statement->fetchAll();
    }

    private function approvedPaymentsMonthlyChart(int $periodId): array
    {
        $statement = $this->db->prepare(
            "SELECT
                    to_char(date_trunc('month', p.cpagfecha_revision), 'YYYY-MM') AS label,
                    COALESCE(SUM(p.cpagvalor_aprobado), 0) AS value
             FROM contabilidad_pago p
             INNER JOIN matricula m ON m.matid = p.matid
             INNER JOIN curso c ON c.curid = m.curid
             WHERE c.pleid = :period_id
               AND p.cpagestado = 'APROBADO'
               AND p.cpagfecha_revision IS NOT NULL
               AND p.cpagfecha_revision >= date_trunc('month', CURRENT_DATE) - interval '5 months'
             GROUP BY date_trunc('month', p.cpagfecha_revision)
             ORDER BY date_trunc('month', p.cpagfecha_revision)"
        );
        $statement->execute(['period_id' => $periodId]);

        return $statement->fetchAll();
    }

    private function pendingByCourseChart(int $periodId): array
    {
        $statement = $this->db->prepare(
            "SELECT
                    trim(g.granombre || ' ' || pr.prlnombre) AS label,
                    COALESCE(SUM(o.cobsaldo_pendiente), 0) AS value
             FROM contabilidad_obligacion o
             INNER JOIN matricula m ON m.matid = o.matid
             INNER JOIN curso c ON c.curid = m.curid
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN paralelo pr ON pr.prlid = c.prlid
             WHERE c.pleid = :period_id
               AND o.cobestado IN ('PENDIENTE', 'EN_REVISION', 'PAGO_PARCIAL', 'VENCIDO')
               AND o.cobsaldo_pendiente > 0
             GROUP BY g.graid, g.granombre, pr.prlid, pr.prlnombre
             ORDER BY SUM(o.cobsaldo_pendiente) DESC
             LIMIT 8"
        );
        $statement->execute(['period_id' => $periodId]);

        return $statement->fetchAll();
    }

    private function obligationFilterWhere(int $periodId, array $filters): array
    {
        $conditions = ['c.pleid = :period_id'];
        $params = ['period_id' => $periodId];
        $search = trim((string) ($filters['q'] ?? ''));
        $levelId = (int) ($filters['nivel'] ?? 0);
        $courseId = (int) ($filters['curso'] ?? 0);

        if ($search !== '') {
            $conditions[] = "(lower(pe.pernombres || ' ' || pe.perapellidos) LIKE lower(:search)
                OR lower(pe.perapellidos || ' ' || pe.pernombres) LIKE lower(:search)
                OR lower(COALESCE(pe.percedula, '')) LIKE lower(:search))";
            $params['search'] = '%' . $search . '%';
        }

        if ($levelId > 0) {
            $conditions[] = 'n.nedid = :level_id';
            $params['level_id'] = $levelId;
        }

        if ($courseId > 0) {
            $conditions[] = 'c.curid = :course_id';
            $params['course_id'] = $courseId;
        }

        return [implode(' AND ', $conditions), $params];
    }

    private function emptyObligationReference(): array
    {
        return [
            'matricula' => null,
            'matricula_label' => 'Sin datos',
            'pension' => null,
            'pension_label' => 'Sin datos',
            'meses' => null,
            'meses_label' => 'Sin datos',
        ];
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

    private function matriculationAccountingContext(int $matriculationId, int $periodId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT
                    m.matid,
                    c.pleid,
                    g.graid,
                    n.nedid
             FROM matricula m
             INNER JOIN curso c ON c.curid = m.curid
             INNER JOIN grado g ON g.graid = c.graid
             INNER JOIN nivel_educativo n ON n.nedid = g.nedid
             WHERE m.matid = :matid
               AND c.pleid = :period_id
             LIMIT 1"
        );
        $statement->execute([
            'matid' => $matriculationId,
            'period_id' => $periodId,
        ]);

        return $statement->fetch();
    }

    private function findPeriodObligation(int $obligationId, int $periodId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT o.*
             FROM contabilidad_obligacion o
             INNER JOIN matricula m ON m.matid = o.matid
             INNER JOIN curso c ON c.curid = m.curid
             WHERE o.cobid = :id
               AND c.pleid = :period_id
             LIMIT 1"
        );
        $statement->execute([
            'id' => $obligationId,
            'period_id' => $periodId,
        ]);

        return $statement->fetch();
    }

    private function findRepresentativeObligation(int $representativePersonId, int $periodId, int $obligationId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT
                    o.*,
                    pe.pernombres,
                    pe.perapellidos
             FROM contabilidad_obligacion o
             INNER JOIN matricula m ON m.matid = o.matid
             INNER JOIN matricula_representante mr ON mr.matid = m.matid
             INNER JOIN curso c ON c.curid = m.curid
             INNER JOIN estudiante e ON e.estid = m.estid
             INNER JOIN persona pe ON pe.perid = e.perid
             WHERE o.cobid = :obligation_id
               AND mr.perid = :representative_person_id
               AND c.pleid = :period_id
             LIMIT 1"
        );
        $statement->execute([
            'obligation_id' => $obligationId,
            'representative_person_id' => $representativePersonId,
            'period_id' => $periodId,
        ]);

        return $statement->fetch();
    }

    private function hasPendingReceiptForObligation(int $obligationId): bool
    {
        $statement = $this->db->prepare(
            "SELECT 1
             FROM contabilidad_pago
             WHERE cobid_sugerido = :obligation_id
               AND cpagestado = 'EN_REVISION'
             LIMIT 1"
        );
        $statement->execute(['obligation_id' => $obligationId]);

        return $statement->fetchColumn() !== false;
    }

    private function materializeAdditionalItemAssignments(int $itemId, int $periodId, string $scope, int $levelId, int $courseId, int $matriculationId, float $value, string $deadline, int $userId): void
    {
        $conditions = ['c.pleid = :period_id'];
        $params = [
            'item_id' => $itemId,
            'period_id' => $periodId,
            'value' => number_format($value, 2, '.', ''),
            'deadline' => $deadline !== '' ? $deadline : null,
            'user_id' => $userId,
        ];

        if ($scope === 'NIVEL') {
            $conditions[] = 'g.nedid = :level_id';
            $params['level_id'] = $levelId;
        } elseif ($scope === 'CURSO') {
            $conditions[] = 'c.curid = :course_id';
            $params['course_id'] = $courseId;
        } elseif ($scope === 'ESTUDIANTE') {
            $conditions[] = 'm.matid = :matriculation_id';
            $params['matriculation_id'] = $matriculationId;
        }

        $sql = "INSERT INTO contabilidad_rubro_estudiante (
                    cruid,
                    matid,
                    crevalor,
                    crefecha_limite,
                    usuid_registro
                )
                SELECT
                    :item_id,
                    m.matid,
                    :value,
                    :deadline,
                    :user_id
                FROM matricula m
                INNER JOIN curso c ON c.curid = m.curid
                INNER JOIN grado g ON g.graid = c.graid
                WHERE " . implode(' AND ', $conditions) . "
                ON CONFLICT (cruid, matid) DO NOTHING";

        $statement = $this->db->prepare($sql);
        foreach ($params as $key => $paramValue) {
            if (is_int($paramValue)) {
                $statement->bindValue(':' . $key, $paramValue, PDO::PARAM_INT);
            } elseif ($paramValue === null) {
                $statement->bindValue(':' . $key, null, PDO::PARAM_NULL);
            } else {
                $statement->bindValue(':' . $key, $paramValue);
            }
        }
        $statement->execute();

        if ($statement->rowCount() === 0) {
            throw new RuntimeException('El alcance seleccionado no encontro estudiantes para asignar.');
        }
    }

    private function updateAdditionalItemConceptStatus(int $conceptId, bool $active): void
    {
        $statement = $this->db->prepare(
            "UPDATE contabilidad_concepto
             SET ccoestado = :active,
                 ccofecha_modificacion = CURRENT_TIMESTAMP
             WHERE ccoid = :id
               AND ccocategoria = 'RUBRO'"
        );
        $statement->bindValue(':active', $active, PDO::PARAM_BOOL);
        $statement->bindValue(':id', $conceptId, PDO::PARAM_INT);
        $statement->execute();
    }

    private function assertAdditionalItemConcept(int $conceptId): void
    {
        if ($conceptId <= 0) {
            throw new RuntimeException('Seleccione un concepto valido para el rubro.');
        }

        $statement = $this->db->prepare(
            "SELECT 1
             FROM contabilidad_concepto
             WHERE ccoid = :id
               AND ccocategoria = 'RUBRO'
               AND ccoestado = true
             LIMIT 1"
        );
        $statement->execute(['id' => $conceptId]);

        if ($statement->fetchColumn() === false) {
            throw new RuntimeException('Seleccione un concepto activo de rubro.');
        }
    }

    private function conceptCodeFromName(string $name): string
    {
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        $normalized = strtoupper((string) $normalized);
        $normalized = preg_replace('/[^A-Z0-9]+/', '_', $normalized) ?? '';
        $normalized = trim($normalized, '_');

        return $normalized !== '' ? substr($normalized, 0, 40) : 'RUBRO_' . date('YmdHis');
    }

    private function findAdditionalItemAssignmentForUpdate(int $assignmentId, int $periodId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT
                    re.creid,
                    re.cruid,
                    re.matid,
                    re.crevalor,
                    re.creestado,
                    r.pleid
             FROM contabilidad_rubro_estudiante re
             INNER JOIN contabilidad_rubro r ON r.cruid = re.cruid
             WHERE re.creid = :assignment_id
               AND r.pleid = :period_id
             LIMIT 1
             FOR UPDATE OF re"
        );
        $statement->execute([
            'assignment_id' => $assignmentId,
            'period_id' => $periodId,
        ]);

        return $statement->fetch();
    }

    private function registerAdditionalItemPayment(array $assignment, int $userId, int $paymentMethodId, string $reference, string $observation): void
    {
        $value = round((float) ($assignment['crevalor'] ?? 0), 2);

        if ($value <= 0.0) {
            throw new RuntimeException('El valor del rubro no permite registrar pago.');
        }

        $payment = $this->db->prepare(
            "INSERT INTO contabilidad_pago (
                matid,
                creid_sugerido,
                cpagorigen,
                cpagestado,
                cpagvalor_reportado,
                cpagvalor_aprobado,
                cmpid,
                cpagreferencia,
                cpagobservacion_interna,
                usuid_registro,
                usuid_revision,
                cpagfecha_revision
             ) VALUES (
                :matid,
                :assignment_id,
                'INTERNO',
                'APROBADO',
                :value,
                :value,
                :method_id,
                :reference,
                :observation,
                :user_id,
                :user_id,
                CURRENT_TIMESTAMP
             )
             RETURNING cpagid"
        );
        $payment->bindValue(':matid', (int) $assignment['matid'], PDO::PARAM_INT);
        $payment->bindValue(':assignment_id', (int) $assignment['creid'], PDO::PARAM_INT);
        $payment->bindValue(':value', number_format($value, 2, '.', ''));
        $this->bindNullableInt($payment, ':method_id', $paymentMethodId > 0 ? $paymentMethodId : null);
        $this->bindNullableString($payment, ':reference', $reference);
        $this->bindNullableString($payment, ':observation', $observation);
        $payment->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $payment->execute();
        $paymentId = (int) $payment->fetchColumn();

        $this->db->prepare(
            "INSERT INTO contabilidad_pago_rubro (cpagid, creid, cprvalor_aplicado)
             VALUES (:payment_id, :assignment_id, :value)"
        )->execute([
            'payment_id' => $paymentId,
            'assignment_id' => (int) $assignment['creid'],
            'value' => number_format($value, 2, '.', ''),
        ]);
    }

    private function findPaymentForReverse(int $paymentId, int $periodId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT p.*
             FROM contabilidad_pago p
             INNER JOIN matricula m ON m.matid = p.matid
             INNER JOIN curso c ON c.curid = m.curid
             WHERE p.cpagid = :payment_id
               AND c.pleid = :period_id
             LIMIT 1
             FOR UPDATE OF p"
        );
        $statement->execute([
            'payment_id' => $paymentId,
            'period_id' => $periodId,
        ]);

        return $statement->fetch();
    }

    private function reversePaymentBalances(int $paymentId): void
    {
        $statement = $this->db->prepare(
            "SELECT csfid, csfvalor_inicial, csfvalor_disponible
             FROM contabilidad_saldo_favor
             WHERE cpagid_origen = :payment_id
               AND csfestado = 'ACTIVO'
             FOR UPDATE"
        );
        $statement->execute(['payment_id' => $paymentId]);

        foreach ($statement->fetchAll() as $balance) {
            if (round((float) $balance['csfvalor_inicial'], 2) !== round((float) $balance['csfvalor_disponible'], 2)) {
                throw new RuntimeException('No se puede reversar el pago porque el saldo a favor ya fue aplicado.');
            }

            $this->db->prepare(
                "UPDATE contabilidad_saldo_favor
                 SET csfestado = 'REVERSADO',
                     csffecha_modificacion = CURRENT_TIMESTAMP
                 WHERE csfid = :balance_id"
            )->execute(['balance_id' => (int) $balance['csfid']]);
        }
    }

    private function reversePaymentObligations(int $paymentId): void
    {
        $statement = $this->db->prepare(
            "SELECT cobid
             FROM contabilidad_pago_obligacion
             WHERE cpagid = :payment_id
               AND cpoestado = 'ACTIVO'
             FOR UPDATE"
        );
        $statement->execute(['payment_id' => $paymentId]);
        $obligationIds = array_values(array_unique(array_map('intval', array_column($statement->fetchAll(), 'cobid'))));

        if ($obligationIds === []) {
            return;
        }

        $this->db->prepare(
            "UPDATE contabilidad_pago_obligacion
             SET cpoestado = 'REVERSADO'
             WHERE cpagid = :payment_id
               AND cpoestado = 'ACTIVO'"
        )->execute(['payment_id' => $paymentId]);

        foreach ($obligationIds as $obligationId) {
            $this->recalculateObligationPaymentState($obligationId);
        }
    }

    private function recalculateObligationPaymentState(int $obligationId): void
    {
        $statement = $this->db->prepare(
            "SELECT cobvalor_final, cobestado
             FROM contabilidad_obligacion
             WHERE cobid = :obligation_id
             FOR UPDATE"
        );
        $statement->execute(['obligation_id' => $obligationId]);
        $obligation = $statement->fetch();

        if ($obligation === false || (string) $obligation['cobestado'] === 'ANULADO') {
            return;
        }

        $paidStatement = $this->db->prepare(
            "SELECT COALESCE(SUM(cpovalor_aplicado), 0)
             FROM contabilidad_pago_obligacion
             WHERE cobid = :obligation_id
               AND cpoestado = 'ACTIVO'"
        );
        $paidStatement->execute(['obligation_id' => $obligationId]);

        $paid = round((float) $paidStatement->fetchColumn(), 2);
        $finalValue = round((float) $obligation['cobvalor_final'], 2);
        $balance = max(0.0, round($finalValue - $paid, 2));
        $status = $paid <= 0.0 ? 'PENDIENTE' : ($balance <= 0.0 ? 'PAGADO' : 'PAGO_PARCIAL');

        $this->db->prepare(
            "UPDATE contabilidad_obligacion
             SET cobvalor_pagado = :paid,
                 cobsaldo_pendiente = :balance,
                 cobestado = :status,
                 cobfecha_modificacion = CURRENT_TIMESTAMP
             WHERE cobid = :obligation_id"
        )->execute([
            'paid' => number_format($paid, 2, '.', ''),
            'balance' => number_format($balance, 2, '.', ''),
            'status' => $status,
            'obligation_id' => $obligationId,
        ]);
    }

    private function reversePaymentAdditionalItems(int $paymentId): void
    {
        $statement = $this->db->prepare(
            "SELECT creid
             FROM contabilidad_pago_rubro
             WHERE cpagid = :payment_id
               AND cprestado = 'ACTIVO'
             FOR UPDATE"
        );
        $statement->execute(['payment_id' => $paymentId]);
        $assignmentIds = array_map('intval', array_column($statement->fetchAll(), 'creid'));

        if ($assignmentIds === []) {
            return;
        }

        $this->db->prepare(
            "UPDATE contabilidad_pago_rubro
             SET cprestado = 'REVERSADO'
             WHERE cpagid = :payment_id
               AND cprestado = 'ACTIVO'"
        )->execute(['payment_id' => $paymentId]);

        foreach ($assignmentIds as $assignmentId) {
            $this->db->prepare(
                "UPDATE contabilidad_rubro_estudiante
                 SET creestado = CASE
                         WHEN crefecha_limite IS NOT NULL AND crefecha_limite < CURRENT_DATE THEN 'VENCIDO'
                         ELSE 'PENDIENTE'
                     END,
                     usuid_cierre = NULL,
                     crefecha_cierre = NULL,
                     cremotivo_cierre = NULL,
                     crefecha_modificacion = CURRENT_TIMESTAMP
                 WHERE creid = :assignment_id
                   AND creestado = 'PAGADO'"
            )->execute(['assignment_id' => $assignmentId]);
        }
    }

    private function findPeriodPaymentForUpdate(int $paymentId, int $periodId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT
                    p.*,
                    o.cobid,
                    o.coborden,
                    o.cobanio,
                    o.cobmes,
                    o.cobvalor_final,
                    o.cobvalor_pagado,
                    o.cobsaldo_pendiente
             FROM contabilidad_pago p
             INNER JOIN matricula m ON m.matid = p.matid
             INNER JOIN curso c ON c.curid = m.curid
             INNER JOIN contabilidad_obligacion o ON o.cobid = p.cobid_sugerido
             WHERE p.cpagid = :payment_id
               AND c.pleid = :period_id
             LIMIT 1
             FOR UPDATE"
        );
        $statement->execute([
            'payment_id' => $paymentId,
            'period_id' => $periodId,
        ]);

        return $statement->fetch();
    }

    private function applyPaymentToObligation(int $paymentId, array $obligation, float $availableValue): float
    {
        $availableValue = round(max(0.0, $availableValue), 2);
        $pending = max(0.0, (float) ($obligation['cobsaldo_pendiente'] ?? 0));
        $applied = min($availableValue, $pending);

        if ($applied <= 0.0) {
            return $availableValue;
        }

        $this->db->prepare(
            "INSERT INTO contabilidad_pago_obligacion (cpagid, cobid, cpovalor_aplicado)
             VALUES (:payment_id, :obligation_id, :applied_value)"
        )->execute([
            'payment_id' => $paymentId,
            'obligation_id' => (int) $obligation['cobid'],
            'applied_value' => number_format($applied, 2, '.', ''),
        ]);

        $newPaid = round((float) $obligation['cobvalor_pagado'] + $applied, 2);
        $newBalance = max(0.0, round((float) $obligation['cobvalor_final'] - $newPaid, 2));
        $newStatus = $newBalance <= 0.0 ? 'PAGADO' : 'PAGO_PARCIAL';

        $this->db->prepare(
            "UPDATE contabilidad_obligacion
             SET cobvalor_pagado = :paid_value,
                 cobsaldo_pendiente = :balance,
                 cobestado = :status,
                 cobfecha_modificacion = CURRENT_TIMESTAMP
             WHERE cobid = :obligation_id"
        )->execute([
            'paid_value' => number_format($newPaid, 2, '.', ''),
            'balance' => number_format($newBalance, 2, '.', ''),
            'status' => $newStatus,
            'obligation_id' => (int) $obligation['cobid'],
        ]);

        return round($availableValue - $applied, 2);
    }

    private function nextPendingObligationsForPayment(int $matriculationId, int $periodId, int $currentObligationId): array
    {
        $statement = $this->db->prepare(
            "WITH actual AS (
                SELECT
                    coborden,
                    COALESCE(cobanio, 0) AS cobanio,
                    COALESCE(cobmes, 0) AS cobmes,
                    cobid
                FROM contabilidad_obligacion
                WHERE cobid = :obligation_id
                  AND matid = :matid
                LIMIT 1
             )
             SELECT
                    o.cobid,
                    o.coborden,
                    o.cobanio,
                    o.cobmes,
                    o.cobvalor_final,
                    o.cobvalor_pagado,
                    o.cobsaldo_pendiente
             FROM contabilidad_obligacion o
             INNER JOIN matricula m ON m.matid = o.matid
             INNER JOIN curso c ON c.curid = m.curid
             CROSS JOIN actual a
             WHERE o.matid = :matid
               AND c.pleid = :period_id
               AND o.cobestado NOT IN ('EN_REVISION', 'PAGADO', 'ANULADO')
               AND o.cobsaldo_pendiente > 0
               AND (o.coborden, COALESCE(o.cobanio, 0), COALESCE(o.cobmes, 0), o.cobid)
                   > (a.coborden, a.cobanio, a.cobmes, a.cobid)
               AND NOT EXISTS (
                   SELECT 1
                   FROM contabilidad_obligacion blocker
                   WHERE blocker.matid = o.matid
                     AND blocker.cobestado = 'EN_REVISION'
                     AND (blocker.coborden, COALESCE(blocker.cobanio, 0), COALESCE(blocker.cobmes, 0), blocker.cobid)
                         > (a.coborden, a.cobanio, a.cobmes, a.cobid)
                     AND (blocker.coborden, COALESCE(blocker.cobanio, 0), COALESCE(blocker.cobmes, 0), blocker.cobid)
                         <= (o.coborden, COALESCE(o.cobanio, 0), COALESCE(o.cobmes, 0), o.cobid)
               )
             ORDER BY o.coborden ASC, o.cobanio NULLS FIRST, o.cobmes NULLS FIRST, o.cobid ASC
             FOR UPDATE OF o"
        );
        $statement->execute([
            'obligation_id' => $currentObligationId,
            'matid' => $matriculationId,
            'period_id' => $periodId,
        ]);

        return $statement->fetchAll();
    }

    private function duplicatePaymentIdByHash(int $paymentId, string $hash): int
    {
        $hash = trim($hash);

        if ($hash === '') {
            return 0;
        }

        $statement = $this->db->prepare(
            "SELECT cpagid
             FROM contabilidad_pago
             WHERE cpagid <> :payment_id
               AND cpagarchivo_hash = :hash
               AND cpagestado IN ('EN_REVISION', 'APROBADO')
             ORDER BY cpagfecha_registro DESC, cpagid DESC
             LIMIT 1"
        );
        $statement->execute([
            'payment_id' => $paymentId,
            'hash' => $hash,
        ]);

        return (int) ($statement->fetchColumn() ?: 0);
    }

    private function applicableObligationConfiguration(int $periodId, int $levelId, int $gradeId, string $type): array|false
    {
        $statement = $this->db->prepare(
            "SELECT *
             FROM contabilidad_configuracion_obligacion
             WHERE pleid = :period_id
               AND cfotipo = :type
               AND cfoestado = true
               AND (
                   cfoalcance = 'INSTITUCION'
                   OR (cfoalcance = 'NIVEL' AND nedid = :level_id)
                   OR (cfoalcance = 'GRADO' AND graid = :grade_id)
               )
             ORDER BY CASE cfoalcance WHEN 'GRADO' THEN 1 WHEN 'NIVEL' THEN 2 ELSE 3 END
             LIMIT 1"
        );
        $statement->execute([
            'period_id' => $periodId,
            'type' => $type,
            'level_id' => $levelId,
            'grade_id' => $gradeId,
        ]);

        return $statement->fetch();
    }

    private function insertMatriculationObligationIfMissing(int $matriculationId, array $config, int $userId): int
    {
        if ($this->obligationExists($matriculationId, 'MATRICULA')) {
            return 0;
        }

        $this->insertObligation([
            'matid' => $matriculationId,
            'ccoid' => (int) $config['ccoid'],
            'cfoid' => (int) $config['cfoid'],
            'cobtipo' => 'MATRICULA',
            'cobdescripcion' => 'Matricula',
            'cobanio' => null,
            'cobmes' => null,
            'coborden' => 1,
            'cobfecha_vencimiento' => null,
            'cobvalor_base' => (string) $config['cfovalor_oficial'],
            'cobvalor_final' => (string) $config['cfovalor_oficial'],
            'cobsaldo_pendiente' => (string) $config['cfovalor_oficial'],
            'cobgenera_mora' => false,
            'cobmora_tipo' => null,
            'cobmora_valor' => null,
            'usuid_registro' => $userId,
        ]);

        return 1;
    }

    private function insertPensionObligationsIfMissing(int $matriculationId, array $config, int $userId, array $discount): int
    {
        $created = 0;
        $startMonth = (int) $config['cfomes_inicio'];
        $monthsCount = (int) $config['cfocantidad_pensiones'];
        $year = (int) $config['cfoanio_inicio'];

        for ($index = 0; $index < $monthsCount; $index++) {
            $month = (($startMonth - 1 + $index) % 12) + 1;
            $obligationYear = $year + intdiv($startMonth - 1 + $index, 12);

            if ($this->obligationExists($matriculationId, 'PENSION', $obligationYear, $month)) {
                continue;
            }

            $dueDate = sprintf('%04d-%02d-%02d', $obligationYear, $month, (int) $config['cfodia_vencimiento']);
            $this->insertObligation([
                'matid' => $matriculationId,
                'ccoid' => (int) $config['ccoid'],
                'cfoid' => (int) $config['cfoid'],
                'cobtipo' => 'PENSION',
                'cobdescripcion' => 'Pension ' . (self::MONTH_NAMES[$month] ?? (string) $month) . ' ' . $obligationYear,
                'cobanio' => $obligationYear,
                'cobmes' => $month,
                'coborden' => $index + 2,
                'cobfecha_vencimiento' => $dueDate,
                'cobvalor_base' => (string) $config['cfovalor_oficial'],
                'cobdescuento_tipo' => $discount['type'],
                'cobdescuento_valor' => $discount['source_value'],
                'cobvalor_descuento' => $discount['amount'],
                'cobvalor_final' => $discount['final_value'],
                'cobsaldo_pendiente' => $discount['final_value'],
                'cobgenera_mora' => (bool) $config['cfogenera_mora'],
                'cobmora_tipo' => $config['cfomora_tipo'] ?? null,
                'cobmora_valor' => $config['cfomora_valor'] ?? null,
                'usuid_registro' => $userId,
            ]);
            $created++;
        }

        return $created;
    }

    private function updateExistingPensionValues(int $matriculationId, array $config, array $discount): void
    {
        $paidCheck = $this->db->prepare(
            "SELECT 1
             FROM contabilidad_obligacion
             WHERE matid = :matid
               AND cobtipo = 'PENSION'
               AND cobestado <> 'ANULADO'
               AND cobvalor_pagado > :final_value
             LIMIT 1"
        );
        $paidCheck->execute([
            'matid' => $matriculationId,
            'final_value' => $discount['final_value'],
        ]);

        if ($paidCheck->fetchColumn() !== false) {
            throw new RuntimeException('No se puede aplicar ese descuento porque una pension ya tiene pagos superiores al valor final.');
        }

        $statement = $this->db->prepare(
            "UPDATE contabilidad_obligacion
             SET cobvalor_base = :base_value,
                 cobdescuento_tipo = :discount_type,
                 cobdescuento_valor = :discount_source_value,
                 cobvalor_descuento = :discount_amount,
                 cobvalor_final = :final_value,
                 cobsaldo_pendiente = :final_value - cobvalor_pagado,
                 cfoid = :configuration_id,
                 cobfecha_modificacion = CURRENT_TIMESTAMP
             WHERE matid = :matid
               AND cobtipo = 'PENSION'
               AND cobestado <> 'ANULADO'"
        );
        $statement->bindValue(':base_value', (string) $config['cfovalor_oficial']);
        $this->bindNullableString($statement, ':discount_type', $discount['type']);
        $this->bindNullableString($statement, ':discount_source_value', $discount['source_value']);
        $statement->bindValue(':discount_amount', $discount['amount']);
        $statement->bindValue(':final_value', $discount['final_value']);
        $statement->bindValue(':configuration_id', (int) $config['cfoid'], PDO::PARAM_INT);
        $statement->bindValue(':matid', $matriculationId, PDO::PARAM_INT);
        $statement->execute();
    }

    private function obligationExists(int $matriculationId, string $type, ?int $year = null, ?int $month = null): bool
    {
        if ($type === 'MATRICULA') {
            $statement = $this->db->prepare(
                "SELECT 1
                 FROM contabilidad_obligacion
                 WHERE matid = :matid
                   AND cobtipo = 'MATRICULA'
                 LIMIT 1"
            );
            $statement->execute(['matid' => $matriculationId]);

            return $statement->fetchColumn() !== false;
        }

        $statement = $this->db->prepare(
            "SELECT 1
             FROM contabilidad_obligacion
             WHERE matid = :matid
               AND cobtipo = 'PENSION'
               AND cobanio = :year
               AND cobmes = :month
             LIMIT 1"
        );
        $statement->execute([
            'matid' => $matriculationId,
            'year' => $year,
            'month' => $month,
        ]);

        return $statement->fetchColumn() !== false;
    }

    private function insertObligation(array $data): void
    {
        $statement = $this->db->prepare(
            "INSERT INTO contabilidad_obligacion (
                matid,
                ccoid,
                cfoid,
                cobtipo,
                cobdescripcion,
                cobanio,
                cobmes,
                coborden,
                cobfecha_vencimiento,
                cobvalor_base,
                cobvalor_final,
                cobsaldo_pendiente,
                cobdescuento_tipo,
                cobdescuento_valor,
                cobvalor_descuento,
                cobgenera_mora,
                cobmora_tipo,
                cobmora_valor,
                usuid_registro
             ) VALUES (
                :matid,
                :ccoid,
                :cfoid,
                :tipo,
                :descripcion,
                :anio,
                :mes,
                :orden,
                :vencimiento,
                :valor_base,
                :valor_final,
                :saldo_pendiente,
                :descuento_tipo,
                :descuento_valor,
                :valor_descuento,
                :genera_mora,
                :mora_tipo,
                :mora_valor,
                :usuario
             )"
        );

        $statement->bindValue(':matid', (int) $data['matid'], PDO::PARAM_INT);
        $statement->bindValue(':ccoid', (int) $data['ccoid'], PDO::PARAM_INT);
        $statement->bindValue(':cfoid', (int) $data['cfoid'], PDO::PARAM_INT);
        $statement->bindValue(':tipo', (string) $data['cobtipo']);
        $statement->bindValue(':descripcion', (string) $data['cobdescripcion']);
        $this->bindNullableInt($statement, ':anio', $data['cobanio'] ?? null);
        $this->bindNullableInt($statement, ':mes', $data['cobmes'] ?? null);
        $statement->bindValue(':orden', (int) $data['coborden'], PDO::PARAM_INT);
        $this->bindNullableString($statement, ':vencimiento', $data['cobfecha_vencimiento'] ?? null);
        $statement->bindValue(':valor_base', (string) $data['cobvalor_base']);
        $statement->bindValue(':valor_final', (string) $data['cobvalor_final']);
        $statement->bindValue(':saldo_pendiente', (string) $data['cobsaldo_pendiente']);
        $this->bindNullableString($statement, ':descuento_tipo', $data['cobdescuento_tipo'] ?? null);
        $this->bindNullableString($statement, ':descuento_valor', $data['cobdescuento_valor'] ?? null);
        $statement->bindValue(':valor_descuento', (string) ($data['cobvalor_descuento'] ?? '0.00'));
        $statement->bindValue(':genera_mora', (bool) $data['cobgenera_mora'], PDO::PARAM_BOOL);
        $this->bindNullableString($statement, ':mora_tipo', $data['cobmora_tipo'] ?? null);
        $this->bindNullableString($statement, ':mora_valor', $data['cobmora_valor'] ?? null);
        $statement->bindValue(':usuario', (int) $data['usuid_registro'], PDO::PARAM_INT);
        $statement->execute();
    }

    private function normalizePensionDiscount(float $baseValue, float $percent, float $amount): array
    {
        $baseValue = max(0.0, $baseValue);
        $percent = max(0.0, min(100.0, $percent));
        $amount = max(0.0, $amount);

        if ($percent > 0.0) {
            $amount = round($baseValue * ($percent / 100), 2);
            $type = 'PORCENTAJE';
            $sourceValue = number_format($percent, 2, '.', '');
        } elseif ($amount > 0.0) {
            $amount = min(round($amount, 2), $baseValue);
            $type = 'VALOR_FIJO';
            $sourceValue = number_format($amount, 2, '.', '');
            $percent = $baseValue > 0 ? round(($amount / $baseValue) * 100, 2) : 0.0;
        } else {
            $amount = 0.0;
            $type = null;
            $sourceValue = null;
        }

        $amount = min($amount, $baseValue);

        return [
            'type' => $type,
            'source_value' => $sourceValue,
            'percent' => number_format($percent, 2, '.', ''),
            'amount' => number_format($amount, 2, '.', ''),
            'final_value' => number_format($baseValue - $amount, 2, '.', ''),
        ];
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
