-- Datos base de cursos por periodo lectivo
-- Genera cursos base para todos los periodos existentes usando el paralelo A
-- o, si no existe, el primer paralelo disponible.

INSERT INTO curso (pleid, graid, prlid, curestado)
SELECT
    p.pleid,
    g.graid,
    pr.prlid,
    true
FROM periodo_lectivo p
CROSS JOIN grado g
CROSS JOIN LATERAL (
    SELECT prlid
    FROM paralelo
    ORDER BY
        CASE WHEN UPPER(prlnombre) = 'A' THEN 0 ELSE 1 END,
        prlnombre ASC,
        prlid ASC
    LIMIT 1
) pr
WHERE NOT EXISTS (
    SELECT 1
    FROM curso c
    WHERE c.pleid = p.pleid
      AND c.graid = g.graid
      AND c.prlid = pr.prlid
);
