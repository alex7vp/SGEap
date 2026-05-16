-- Agrega area academica a los grupos de materias ya creados.
--
-- Ejecutar solo en bases donde `21_calificaciones_grupos_materia.sql` ya fue
-- aplicado antes de incorporar la columna `areaid`.

ALTER TABLE grupo_materia_calificacion
    ADD COLUMN IF NOT EXISTS areaid integer;

UPDATE grupo_materia_calificacion g
SET areaid = origen.areaid
FROM (
    SELECT DISTINCT ON (d.gmcid)
        d.gmcid,
        a.areaid
    FROM grupo_materia_calificacion_detalle d
    INNER JOIN materia_curso mc ON mc.mtcid = d.mtcid
    INNER JOIN asignatura a ON a.asgid = mc.asgid
    WHERE d.gmcdestado = true
    ORDER BY d.gmcid, d.gmcdorden ASC, d.gmcdid ASC
) origen
WHERE origen.gmcid = g.gmcid
  AND g.areaid IS NULL;

UPDATE grupo_materia_calificacion g
SET areaid = primera_area.areaid
FROM (
    SELECT areaid
    FROM area_academica
    ORDER BY areaid ASC
    LIMIT 1
) primera_area
WHERE g.areaid IS NULL;

ALTER TABLE grupo_materia_calificacion
    ALTER COLUMN areaid SET NOT NULL;

ALTER TABLE grupo_materia_calificacion
    DROP CONSTRAINT IF EXISTS fk_gmc_area;

ALTER TABLE grupo_materia_calificacion
    ADD CONSTRAINT fk_gmc_area FOREIGN KEY (areaid)
        REFERENCES area_academica (areaid);

DROP VIEW IF EXISTS vw_calificacion_materia_config_agrupada;
DROP VIEW IF EXISTS vw_calificacion_grupo_materia_detalle;

CREATE VIEW vw_calificacion_grupo_materia_detalle AS
SELECT
    g.gmcid,
    g.pcaid,
    g.areaid,
    ga.areanombre AS grupo_areanombre,
    g.gmcnombre,
    g.gmcdescripcion,
    g.gmcmodo_calculo,
    g.gmcmtcid_representante,
    g.gmcvisualizacion,
    g.gmcpromediable,
    g.gmcvisible_libreta,
    g.gmcestado,
    g.gmcorden,
    d.gmcdid,
    d.mtcid,
    d.gmcdpeso,
    d.gmcdorden,
    d.gmcdincluye_calculo,
    d.gmcdvisible_detalle,
    d.gmcdestado,
    v.curid,
    v.pleid,
    v.graid,
    v.prlid,
    v.areaid AS materia_areaid,
    v.areanombre AS materia_areanombre,
    v.asgnombre,
    v.granombre,
    v.prlnombre,
    v.mtcnombre_mostrar
FROM grupo_materia_calificacion g
INNER JOIN area_academica ga ON ga.areaid = g.areaid
INNER JOIN grupo_materia_calificacion_detalle d
    ON d.gmcid = g.gmcid
    AND d.pcaid = g.pcaid
INNER JOIN vw_materia_curso v ON v.mtcid = d.mtcid;

CREATE VIEW vw_calificacion_materia_config_agrupada AS
SELECT
    c.*,
    gd.gmcid,
    gd.gmcnombre,
    gd.gmcmodo_calculo,
    gd.gmcvisualizacion,
    gd.gmcpromediable AS grupo_promediable,
    gd.gmcvisible_libreta AS grupo_visible_libreta,
    gd.gmcorden AS grupo_orden,
    gd.gmcdpeso AS grupo_materia_peso,
    gd.gmcdorden AS grupo_materia_orden,
    gd.gmcdincluye_calculo,
    gd.gmcdvisible_detalle,
    CASE
        WHEN gd.gmcid IS NULL THEN c.promediable
        ELSE false
    END AS promedia_como_materia_individual,
    CASE
        WHEN gd.gmcid IS NULL THEN c.visible_libreta
        ELSE gd.gmcdvisible_detalle
    END AS visible_como_materia_individual
FROM vw_calificacion_materia_config_efectiva c
LEFT JOIN vw_calificacion_grupo_materia_detalle gd
    ON gd.pcaid = c.pcaid
    AND gd.mtcid = c.mtcid
    AND gd.gmcestado = true
    AND gd.gmcdestado = true;
