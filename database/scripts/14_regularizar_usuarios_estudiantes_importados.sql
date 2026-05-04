-- Regulariza estudiantes ya existentes, incluidos los importados historicamente.
--
-- Crea usuarios INACTIVOS para estudiantes sin cuenta, generando el nombre de
-- usuario con las dos primeras letras de cada nombre y apellido. Ejemplo:
-- Alex Vinicio Procel Barriga -> alviprba.
--
-- La clave queda como marcador interno y debe ser restablecida por Secretaria
-- antes de activar el usuario.

INSERT INTO permiso (prmnombre, prmcodigo, prmdescripcion, prmestado)
SELECT
    'Mi matricula',
    'estudiante.mi_matricula',
    'Consulta de la matricula propia del estudiante',
    true
WHERE NOT EXISTS (
    SELECT 1
    FROM permiso
    WHERE prmcodigo = 'estudiante.mi_matricula'
);

INSERT INTO rol (rolnombre, roldescripcion, rolestado)
SELECT
    'Estudiante',
    'Acceso del estudiante a su informacion academica y matricula propia',
    true
WHERE NOT EXISTS (
    SELECT 1
    FROM rol
    WHERE rolnombre = 'Estudiante'
);

INSERT INTO rol_permiso (rolid, prmid, rpeestado)
SELECT
    r.rolid,
    p.prmid,
    true
FROM rol r
INNER JOIN permiso p ON p.prmcodigo = 'estudiante.mi_matricula'
WHERE r.rolnombre = 'Estudiante'
  AND NOT EXISTS (
      SELECT 1
      FROM rol_permiso rp
      WHERE rp.rolid = r.rolid
        AND rp.prmid = p.prmid
  );

INSERT INTO usuario (
    perid,
    usunombre,
    usuclave,
    usuestado
)
SELECT
    candidate.perid,
    candidate.usunombre,
    '$2y$10$AAH8xUvasxfsIG/4hJrypung.vg1B46bn9VB9xAsdUxiGe6fL6.Gq',
    false
FROM (
    WITH base AS (
        SELECT
            e.estid,
            p.perid,
            translate(
                lower(
                    concat(
                        substring((regexp_split_to_array(trim(p.pernombres), '\s+'))[1] from 1 for 2),
                        substring((regexp_split_to_array(trim(p.pernombres), '\s+'))[2] from 1 for 2),
                        substring((regexp_split_to_array(trim(p.perapellidos), '\s+'))[1] from 1 for 2),
                        substring((regexp_split_to_array(trim(p.perapellidos), '\s+'))[2] from 1 for 2)
                    )
                ),
                'áéíóúüñ',
                'aeiouun'
            ) AS base_username
        FROM estudiante e
        INNER JOIN persona p ON p.perid = e.perid
        WHERE NOT EXISTS (
            SELECT 1
            FROM usuario u
            WHERE u.perid = p.perid
        )
    ),
    numbered AS (
        SELECT
            base.*,
            ROW_NUMBER() OVER (PARTITION BY base_username ORDER BY estid) AS duplicate_order
        FROM base
    )
    SELECT
        numbered.perid,
        CASE
            WHEN numbered.duplicate_order = 1
             AND NOT EXISTS (
                 SELECT 1
                 FROM usuario u
                 WHERE u.usunombre = numbered.base_username
             )
                THEN numbered.base_username
            ELSE numbered.base_username || numbered.estid::text
        END AS usunombre
    FROM numbered
) candidate
WHERE candidate.usunombre <> ''
  AND NOT EXISTS (
      SELECT 1
      FROM usuario u
      WHERE u.usunombre = candidate.usunombre
  );

-- Corrige usuarios inactivos creados previamente con la cedula como username.
UPDATE usuario u
SET usunombre = candidate.usunombre,
    usufecha_modificacion = CURRENT_TIMESTAMP
FROM (
    WITH base AS (
        SELECT
            e.estid,
            p.perid,
            translate(
                lower(
                    concat(
                        substring((regexp_split_to_array(trim(p.pernombres), '\s+'))[1] from 1 for 2),
                        substring((regexp_split_to_array(trim(p.pernombres), '\s+'))[2] from 1 for 2),
                        substring((regexp_split_to_array(trim(p.perapellidos), '\s+'))[1] from 1 for 2),
                        substring((regexp_split_to_array(trim(p.perapellidos), '\s+'))[2] from 1 for 2)
                    )
                ),
                'áéíóúüñ',
                'aeiouun'
            ) AS base_username
        FROM estudiante e
        INNER JOIN persona p ON p.perid = e.perid
        INNER JOIN usuario u_existing ON u_existing.perid = p.perid
        WHERE u_existing.usuestado = false
          AND u_existing.usunombre = p.percedula
    ),
    numbered AS (
        SELECT
            base.*,
            ROW_NUMBER() OVER (PARTITION BY base_username ORDER BY estid) AS duplicate_order
        FROM base
    )
    SELECT
        numbered.perid,
        CASE
            WHEN numbered.duplicate_order = 1
             AND NOT EXISTS (
                 SELECT 1
                 FROM usuario u_conflict
                 WHERE u_conflict.usunombre = numbered.base_username
                   AND u_conflict.perid <> numbered.perid
             )
                THEN numbered.base_username
            ELSE numbered.base_username || numbered.estid::text
        END AS usunombre
    FROM numbered
) candidate
WHERE u.perid = candidate.perid
  AND candidate.usunombre <> ''
  AND NOT EXISTS (
      SELECT 1
      FROM usuario u_conflict
      WHERE u_conflict.usunombre = candidate.usunombre
        AND u_conflict.usuid <> u.usuid
  );

INSERT INTO usuario_rol (
    usuid,
    rolid,
    usrestado
)
SELECT
    u.usuid,
    r.rolid,
    true
FROM estudiante e
INNER JOIN usuario u ON u.perid = e.perid
INNER JOIN rol r ON r.rolnombre = 'Estudiante'
WHERE NOT EXISTS (
    SELECT 1
    FROM usuario_rol ur
    WHERE ur.usuid = u.usuid
      AND ur.rolid = r.rolid
);

-- Registros que no pudieron recibir usuario automatico por conflicto de username.
SELECT
    e.estid,
    p.perid,
    p.percedula,
    p.perapellidos,
    p.pernombres
FROM estudiante e
INNER JOIN persona p ON p.perid = e.perid
WHERE NOT EXISTS (
    SELECT 1
    FROM usuario u
    WHERE u.perid = p.perid
)
ORDER BY p.perapellidos ASC, p.pernombres ASC;
