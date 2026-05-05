-- Roles operativos base para usuarios no administradores.
-- Ejecutar en bases existentes despues de database/scripts/05_seguridad.sql
-- y despues de haber cargado los permisos base.

INSERT INTO rol (rolnombre, roldescripcion, rolestado)
SELECT source.rolnombre, source.roldescripcion, true
FROM (
    VALUES
        ('Estudiante', 'Acceso del estudiante a su informacion academica y matricula propia'),
        ('Representante', 'Acceso para representantes legales de estudiantes'),
        ('Rector', 'Acceso institucional para rectorado'),
        ('Vicerrector', 'Acceso institucional para vicerrectorado'),
        ('Secretaria', 'Gestion operativa de personas, estudiantes, matriculas y accesos temporales'),
        ('Coordinador', 'Acceso institucional para coordinacion'),
        ('Docente', 'Acceso base para personal docente de la institucion'),
        ('DECE', 'Acceso institucional para consejeria estudiantil'),
        ('Inspector', 'Acceso institucional para inspeccion')
) AS source (rolnombre, roldescripcion)
WHERE NOT EXISTS (
    SELECT 1
    FROM rol r
    WHERE r.rolnombre = source.rolnombre
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
