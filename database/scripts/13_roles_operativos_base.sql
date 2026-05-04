-- Roles operativos base para usuarios no administradores.
-- Ejecutar en bases existentes despues de database/scripts/05_seguridad.sql
-- y despues de haber cargado los permisos base.

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

INSERT INTO rol (rolnombre, roldescripcion, rolestado)
SELECT
    'Docente',
    'Acceso base para personal docente de la institucion',
    true
WHERE NOT EXISTS (
    SELECT 1
    FROM rol
    WHERE rolnombre = 'Docente'
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

INSERT INTO rol_permiso (rolid, prmid, rpeestado)
SELECT
    r.rolid,
    p.prmid,
    true
FROM rol r
INNER JOIN permiso p ON p.prmcodigo = 'dashboard.ver'
WHERE r.rolnombre = 'Docente'
  AND NOT EXISTS (
      SELECT 1
      FROM rol_permiso rp
      WHERE rp.rolid = r.rolid
        AND rp.prmid = p.prmid
  );
