-- Ajuste incremental para bases ya instaladas.
-- Agrega el mes final de pensiones para calcular automaticamente la cantidad de mensualidades.

BEGIN;

ALTER TABLE contabilidad_configuracion_obligacion
    ADD COLUMN IF NOT EXISTS cfomes_fin integer;

UPDATE contabilidad_configuracion_obligacion
SET cfomes_fin = ((cfomes_inicio + cfocantidad_pensiones - 2) % 12) + 1
WHERE cfotipo = 'PENSION'
  AND cfomes_inicio IS NOT NULL
  AND cfocantidad_pensiones IS NOT NULL
  AND cfomes_fin IS NULL;

ALTER TABLE contabilidad_configuracion_obligacion
    DROP CONSTRAINT IF EXISTS ck_cfo_mes_fin,
    DROP CONSTRAINT IF EXISTS ck_cfo_pension_config;

ALTER TABLE contabilidad_configuracion_obligacion
    ADD CONSTRAINT ck_cfo_mes_fin
        CHECK (cfomes_fin IS NULL OR cfomes_fin BETWEEN 1 AND 12),
    ADD CONSTRAINT ck_cfo_pension_config
        CHECK (
            (
                cfotipo = 'PENSION'
                AND cfocantidad_pensiones IS NOT NULL
                AND cfomes_inicio IS NOT NULL
                AND cfomes_fin IS NOT NULL
                AND cfoanio_inicio IS NOT NULL
            )
            OR
            (
                cfotipo = 'MATRICULA'
                AND cfocantidad_pensiones IS NULL
                AND cfomes_inicio IS NULL
                AND cfomes_fin IS NULL
                AND cfoanio_inicio IS NULL
            )
        );

COMMIT;
