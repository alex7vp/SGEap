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

    public function updateObligationFinalValue(int $obligationId, int $periodId, float $finalValue): void
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
                    lp.cpagmotivo_rechazo
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

    public function receiptsForReview(int $periodId, string $status = 'EN_REVISION'): array
    {
        if ($periodId <= 0) {
            return [];
        }

        $validStatuses = ['EN_REVISION', 'APROBADO', 'RECHAZADO'];
        $status = in_array($status, $validStatuses, true) ? $status : 'EN_REVISION';

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
             WHERE c.pleid = :period_id
               AND p.cpagestado = :status
             ORDER BY p.cpagfecha_registro DESC, p.cpagid DESC"
        );
        $statement->execute([
            'period_id' => $periodId,
            'status' => $status,
        ]);

        return $statement->fetchAll();
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

            $pending = max(0.0, (float) ($payment['cobsaldo_pendiente'] ?? 0));
            $applied = min($approvedValue, $pending);
            $excess = round($approvedValue - $applied, 2);

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

            if ($applied > 0) {
                $this->db->prepare(
                    "INSERT INTO contabilidad_pago_obligacion (cpagid, cobid, cpovalor_aplicado)
                     VALUES (:payment_id, :obligation_id, :applied_value)"
                )->execute([
                    'payment_id' => $paymentId,
                    'obligation_id' => (int) $payment['cobid_sugerido'],
                    'applied_value' => number_format($applied, 2, '.', ''),
                ]);

                $newPaid = round((float) $payment['cobvalor_pagado'] + $applied, 2);
                $newBalance = max(0.0, round((float) $payment['cobvalor_final'] - $newPaid, 2));
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
                    'obligation_id' => (int) $payment['cobid_sugerido'],
                ]);
            }

            if ($excess > 0) {
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
                    'value' => number_format($excess, 2, '.', ''),
                    'observation' => $observation !== '' ? $observation : 'Saldo a favor generado por comprobante aprobado.',
                ]);
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

    private function findPeriodPaymentForUpdate(int $paymentId, int $periodId): array|false
    {
        $statement = $this->db->prepare(
            "SELECT
                    p.*,
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
}
