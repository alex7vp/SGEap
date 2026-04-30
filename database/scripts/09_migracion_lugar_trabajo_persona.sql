-- Migra el lugar de trabajo desde familiar hacia persona.
-- Ejecutar una sola vez sobre bases existentes antes de usar la version actualizada.

ALTER TABLE persona
ADD COLUMN IF NOT EXISTS perlugardetrabajo varchar(150);

DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_name = 'familiar'
          AND column_name = 'famlugardetrabajo'
    ) THEN
        UPDATE persona p
        SET perlugardetrabajo = source.famlugardetrabajo
        FROM (
            SELECT DISTINCT ON (perid)
                perid,
                famlugardetrabajo
            FROM familiar
            WHERE famlugardetrabajo IS NOT NULL
              AND btrim(famlugardetrabajo) <> ''
            ORDER BY perid, famid DESC
        ) source
        WHERE p.perid = source.perid
          AND (p.perlugardetrabajo IS NULL OR btrim(p.perlugardetrabajo) = '');
    END IF;
END $$;

ALTER TABLE familiar
DROP COLUMN IF EXISTS famlugardetrabajo;
