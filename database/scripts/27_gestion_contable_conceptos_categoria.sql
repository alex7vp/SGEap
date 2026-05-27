-- Ajuste incremental para bases ya instaladas.
-- Permite que conceptos de obligaciones y rubros compartan codigo/nombre
-- sin mezclarse entre categorias.

ALTER TABLE contabilidad_concepto
    DROP CONSTRAINT IF EXISTS uq_contabilidad_concepto_codigo;

ALTER TABLE contabilidad_concepto
    DROP CONSTRAINT IF EXISTS uq_contabilidad_concepto_nombre;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'uq_contabilidad_concepto_categoria_codigo'
    ) THEN
        ALTER TABLE contabilidad_concepto
            ADD CONSTRAINT uq_contabilidad_concepto_categoria_codigo
            UNIQUE (ccocategoria, ccocodigo);
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'uq_contabilidad_concepto_categoria_nombre'
    ) THEN
        ALTER TABLE contabilidad_concepto
            ADD CONSTRAINT uq_contabilidad_concepto_categoria_nombre
            UNIQUE (ccocategoria, cconombre);
    END IF;
END $$;
