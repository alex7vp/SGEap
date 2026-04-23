-- Reglas de negocio implementadas con triggers.
-- Este archivo complementa las validaciones de la aplicacion y protege la integridad
-- cuando existan inserciones o actualizaciones directas sobre la base de datos.

-- ============================================================================
-- 1. Evitar que el estudiante sea registrado como su propio familiar
-- ============================================================================

DROP TRIGGER IF EXISTS tg_validar_familiar_no_es_estudiante ON familiar;
DROP FUNCTION IF EXISTS fn_validar_familiar_no_es_estudiante();

CREATE OR REPLACE FUNCTION fn_validar_familiar_no_es_estudiante()
RETURNS trigger
LANGUAGE plpgsql
AS $$
DECLARE
    v_perid_estudiante integer;
BEGIN
    SELECT e.perid
    INTO v_perid_estudiante
    FROM estudiante e
    WHERE e.estid = NEW.estid;

    IF v_perid_estudiante IS NOT NULL AND v_perid_estudiante = NEW.perid THEN
        RAISE EXCEPTION 'La misma persona no puede registrarse como familiar del estudiante.';
    END IF;

    RETURN NEW;
END;
$$;

CREATE TRIGGER tg_validar_familiar_no_es_estudiante
BEFORE INSERT OR UPDATE ON familiar
FOR EACH ROW
EXECUTE FUNCTION fn_validar_familiar_no_es_estudiante();

-- ============================================================================
-- 2. Evitar que el estudiante sea registrado como su propio representante
-- ============================================================================

DROP TRIGGER IF EXISTS tg_validar_representante_no_es_estudiante ON matricula_representante;
DROP FUNCTION IF EXISTS fn_validar_representante_no_es_estudiante();

CREATE OR REPLACE FUNCTION fn_validar_representante_no_es_estudiante()
RETURNS trigger
LANGUAGE plpgsql
AS $$
DECLARE
    v_estid integer;
    v_perid_estudiante integer;
BEGIN
    SELECT m.estid
    INTO v_estid
    FROM matricula m
    WHERE m.matid = NEW.matid;

    SELECT e.perid
    INTO v_perid_estudiante
    FROM estudiante e
    WHERE e.estid = v_estid;

    IF v_perid_estudiante IS NOT NULL AND v_perid_estudiante = NEW.perid THEN
        RAISE EXCEPTION 'El estudiante no puede registrarse como su propio representante.';
    END IF;

    RETURN NEW;
END;
$$;

CREATE TRIGGER tg_validar_representante_no_es_estudiante
BEFORE INSERT OR UPDATE ON matricula_representante
FOR EACH ROW
EXECUTE FUNCTION fn_validar_representante_no_es_estudiante();

-- ============================================================================
-- 3. Ejecucion sugerida
-- ============================================================================

-- Ejecutar este archivo despues de:
-- - 03_personas.sql
-- - 04_matriculacion.sql
-- o al final del consolidado sgeap.sql
