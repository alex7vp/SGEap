<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class MessageDeliveryService
{
    public function processPendingEmailDeliveries(int $limit = 25): array
    {
        $db = Database::connection();
        $configuration = $this->configuration();

        if ($configuration === false || empty($configuration['mcoactivo'])) {
            return [
                'processed' => 0,
                'sent' => 0,
                'failed' => 0,
                'skipped' => 0,
                'message' => 'El servicio SMTP aun no esta configurado.',
            ];
        }

        $provider = strtoupper(trim((string) ($configuration['mcoproveedor'] ?? 'SMTP')));

        if ($provider === 'SMTP') {
            return [
                'processed' => 0,
                'sent' => 0,
                'failed' => 0,
                'skipped' => 0,
                'message' => 'Las entregas de correo quedaron pendientes hasta definir el servicio SMTP.',
            ];
        }

        $limit = max(1, min($limit, (int) ($configuration['mcolote_maximo'] ?? 25)));
        $statement = $db->prepare(
            "SELECT ce.cenid
             FROM comunicado_entrega ce
             WHERE ce.cencanal = 'EMAIL'
               AND ce.cenestado = 'PENDIENTE'
               AND ce.cenfecha_programada <= CURRENT_TIMESTAMP
               AND ce.cenintentos < ce.cenmax_intentos
             ORDER BY ce.cenfecha_programada ASC, ce.cenid ASC
             LIMIT {$limit}"
        );
        $statement->execute();
        $deliveryIds = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));

        if ($deliveryIds === []) {
            return [
                'processed' => 0,
                'sent' => 0,
                'failed' => 0,
                'skipped' => 0,
                'message' => 'No existen correos pendientes.',
            ];
        }

        $failed = 0;

        foreach ($deliveryIds as $deliveryId) {
            $failed++;
            $error = 'Proveedor SMTP pendiente de definicion por propietarios.';
            $update = $db->prepare(
                "UPDATE comunicado_entrega
                 SET cenestado = CASE WHEN cenintentos + 1 >= cenmax_intentos THEN 'FALLIDO' ELSE 'PENDIENTE' END,
                     cenintentos = cenintentos + 1,
                     cenfecha_procesamiento = CURRENT_TIMESTAMP,
                     cenultimo_error = :error,
                     cenfecha_modificacion = CURRENT_TIMESTAMP
                 WHERE cenid = :delivery_id"
            );
            $update->execute([
                'delivery_id' => $deliveryId,
                'error' => $error,
            ]);
        }

        return [
            'processed' => count($deliveryIds),
            'sent' => 0,
            'failed' => $failed,
            'skipped' => 0,
            'message' => 'Las entregas quedaron listas para SMTP; falta definir proveedor.',
        ];
    }

    public function configuration(): array|false
    {
        $statement = Database::connection()->query(
            "SELECT *
             FROM mensajeria_configuracion
             ORDER BY mcoid ASC
             LIMIT 1"
        );

        return $statement->fetch();
    }
}
