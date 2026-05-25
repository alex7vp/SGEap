-- Ajuste incremental para bases ya instaladas.
-- Normaliza la configuracion contable a alcances: INSTITUCION, NIVEL y GRADO.
-- El alcance CURSO se elimina solo de contabilidad_configuracion_obligacion.

BEGIN;

DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'contabilidad_configuracion_obligacion'
          AND column_name = 'curid'
    ) AND EXISTS (
        SELECT 1
        FROM contabilidad_configuracion_obligacion
        WHERE cfoalcance = 'CURSO'
    ) THEN
        RAISE EXCEPTION 'Existen configuraciones contables con alcance CURSO. Conviertalas a NIVEL o GRADO antes de ejecutar este ajuste.';
    END IF;
END $$;

ALTER TABLE contabilidad_configuracion_obligacion
    ADD COLUMN IF NOT EXISTS graid integer;

ALTER TABLE contabilidad_configuracion_obligacion
    ALTER COLUMN nedid DROP NOT NULL;

DROP INDEX IF EXISTS uq_cfo_activa_curso;
DROP INDEX IF EXISTS uq_cfo_periodo_curso_tipo_activo;

ALTER TABLE contabilidad_configuracion_obligacion
    DROP CONSTRAINT IF EXISTS fk_cfo_curso,
    DROP CONSTRAINT IF EXISTS fk_cfo_grado,
    DROP CONSTRAINT IF EXISTS ck_cfo_alcance,
    DROP CONSTRAINT IF EXISTS ck_cfo_objetivo,
    DROP CONSTRAINT IF EXISTS ck_cfo_alcance_destino;

ALTER TABLE contabilidad_configuracion_obligacion
    ADD CONSTRAINT fk_cfo_grado FOREIGN KEY (graid)
        REFERENCES grado (graid),
    ADD CONSTRAINT ck_cfo_alcance
        CHECK (cfoalcance IN ('INSTITUCION', 'NIVEL', 'GRADO')),
    ADD CONSTRAINT ck_cfo_objetivo
        CHECK (
            (cfoalcance = 'INSTITUCION' AND nedid IS NULL AND graid IS NULL)
            OR
            (cfoalcance = 'NIVEL' AND nedid IS NOT NULL AND graid IS NULL)
            OR
            (cfoalcance = 'GRADO' AND nedid IS NULL AND graid IS NOT NULL)
        );

ALTER TABLE contabilidad_configuracion_obligacion
    DROP COLUMN IF EXISTS curid;

CREATE UNIQUE INDEX IF NOT EXISTS uq_cfo_periodo_institucion_tipo_activo
    ON contabilidad_configuracion_obligacion (pleid, cfotipo)
    WHERE cfoestado = true AND cfoalcance = 'INSTITUCION';

CREATE UNIQUE INDEX IF NOT EXISTS uq_cfo_periodo_nivel_tipo_activo
    ON contabilidad_configuracion_obligacion (pleid, nedid, cfotipo)
    WHERE cfoestado = true AND cfoalcance = 'NIVEL';

CREATE UNIQUE INDEX IF NOT EXISTS uq_cfo_periodo_grado_tipo_activo
    ON contabilidad_configuracion_obligacion (pleid, graid, cfotipo)
    WHERE cfoestado = true AND cfoalcance = 'GRADO';

COMMIT;
