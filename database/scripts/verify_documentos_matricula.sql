-- Verificacion de estructura para documentos de matricula y documentos generados.
-- Este script no modifica la base de datos; solo reporta el estado actual.

SELECT
    'tabla:documento_matricula' AS objeto,
    CASE WHEN EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = 'public'
          AND table_name = 'documento_matricula'
    ) THEN 'OK' ELSE 'FALTA' END AS estado;

SELECT
    'tabla:matricula_documento_generado' AS objeto,
    CASE WHEN EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = 'public'
          AND table_name = 'matricula_documento_generado'
    ) THEN 'OK' ELSE 'FALTA' END AS estado;

SELECT
    'tabla:matricula_aceptacion_documentos' AS objeto,
    CASE WHEN EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = 'public'
          AND table_name = 'matricula_aceptacion_documentos'
    ) THEN 'OK' ELSE 'FALTA' END AS estado;

SELECT
    c.column_name AS objeto,
    CASE WHEN EXISTS (
        SELECT 1
        FROM information_schema.columns ic
        WHERE ic.table_schema = 'public'
          AND ic.table_name = 'documento_matricula'
          AND ic.column_name = c.column_name
    ) THEN 'OK' ELSE 'FALTA' END AS estado
FROM (
    VALUES
        ('domtipo'),
        ('domorigen'),
        ('domurl'),
        ('domplantilla_archivo'),
        ('domplantilla_extension'),
        ('domobligatorio'),
        ('domactivo')
) AS c(column_name);

SELECT
    c.column_name AS objeto,
    CASE WHEN EXISTS (
        SELECT 1
        FROM information_schema.columns ic
        WHERE ic.table_schema = 'public'
          AND ic.table_name = 'matricula_documento_generado'
          AND ic.column_name = c.column_name
    ) THEN 'OK' ELSE 'FALTA' END AS estado
FROM (
    VALUES
        ('mdgid'),
        ('matid'),
        ('domid'),
        ('mdgnombre'),
        ('mdgruta_archivo'),
        ('mdgextension'),
        ('mdgfecha_generacion'),
        ('mdghash'),
        ('mdgobservacion')
) AS c(column_name);

SELECT
    c.column_name AS objeto,
    CASE WHEN EXISTS (
        SELECT 1
        FROM information_schema.columns ic
        WHERE ic.table_schema = 'public'
          AND ic.table_name = 'matricula_aceptacion_documentos'
          AND ic.column_name = c.column_name
    ) THEN 'OK' ELSE 'FALTA' END AS estado
FROM (
    VALUES
        ('madid'),
        ('matid'),
        ('domid'),
        ('mdgid'),
        ('madaceptado'),
        ('madfecha_aceptacion'),
        ('madobservacion')
) AS c(column_name);

SELECT
    conname AS objeto,
    CASE WHEN EXISTS (
        SELECT 1
        FROM pg_constraint pc
        WHERE pc.conname = expected.conname
    ) THEN 'OK' ELSE 'FALTA' END AS estado
FROM (
    VALUES
        ('ck_documento_matricula_domtipo'),
        ('ck_documento_matricula_domorigen'),
        ('ck_documento_matricula_domplantilla_extension'),
        ('ck_documento_matricula_contenido'),
        ('fk_matricula_documento_generado_matricula'),
        ('fk_matricula_documento_generado_documento'),
        ('ck_matricula_documento_generado_extension'),
        ('fk_matricula_aceptacion_documentos_generado')
) AS expected(conname);
