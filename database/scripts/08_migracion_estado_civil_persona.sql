-- Migracion para mover estado civil desde familiar hacia persona.
-- Ejecutar solo en bases ya existentes. En bases nuevas, persona.eciid se crea
-- desde 03_personas.sql o desde el consolidado sgeap.sql.

ALTER TABLE persona
ADD COLUMN IF NOT EXISTS eciid integer;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'fk_persona_estado_civil'
    ) THEN
        ALTER TABLE persona
        ADD CONSTRAINT fk_persona_estado_civil
        FOREIGN KEY (eciid)
        REFERENCES estado_civil (eciid);
    END IF;
END;
$$;

DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_name = 'familiar'
          AND column_name = 'eciid'
    ) THEN
        UPDATE persona p
        SET eciid = f.eciid
        FROM (
            SELECT DISTINCT ON (perid)
                perid,
                eciid
            FROM familiar
            WHERE eciid IS NOT NULL
            ORDER BY perid, famid
        ) f
        WHERE p.perid = f.perid
          AND p.eciid IS NULL;
    END IF;
END;
$$;

ALTER TABLE familiar
DROP CONSTRAINT IF EXISTS fk_familiar_estado_civil;

ALTER TABLE familiar
DROP COLUMN IF EXISTS eciid;
