-- Sincroniza reglas operativas entre tipos de personal y roles de usuario.
-- Ejecutar en bases existentes despues de cargar permisos y roles base.
-- No crea rol Servicios por decision funcional actual.

INSERT INTO tipo_personal (tpnombre, tpdescripcion, tpestado)
VALUES
    ('Rector', 'Maxima autoridad institucional', true),
    ('Vicerrector', 'Autoridad academica institucional', true),
    ('Secretaria', 'Personal responsable de procesos administrativos y de secretaria', true),
    ('Coordinador', 'Personal responsable de coordinacion academica u operativa', true),
    ('Docente', 'Personal academico responsable de la ensenanza', true),
    ('DECE', 'Personal del departamento de consejeria estudiantil', true),
    ('Inspector', 'Personal responsable de control y convivencia', true),
    ('Servicios', 'Personal operativo y de apoyo general', true),
    ('Otro', 'Otro tipo de personal institucional', true)
ON CONFLICT (tpnombre) DO NOTHING;

INSERT INTO rol (rolnombre, roldescripcion, rolestado)
SELECT source.rolnombre, source.roldescripcion, true
FROM (
    VALUES
        ('Rector', 'Acceso institucional para rectorado'),
        ('Vicerrector', 'Acceso institucional para vicerrectorado'),
        ('Secretaria', 'Gestion operativa de personas, estudiantes, matriculas y accesos temporales'),
        ('Coordinador', 'Acceso institucional para coordinacion'),
        ('Docente', 'Acceso base para personal docente de la institucion'),
        ('DECE', 'Acceso institucional para consejeria estudiantil'),
        ('Inspector', 'Acceso institucional para inspeccion'),
        ('Estudiante', 'Acceso del estudiante a su informacion academica y matricula propia'),
        ('Representante', 'Acceso para representantes legales de estudiantes'),
        ('Representante temporal', 'Acceso limitado para representantes durante la matricula de alumnos nuevos')
) AS source (rolnombre, roldescripcion)
WHERE NOT EXISTS (
    SELECT 1
    FROM rol r
    WHERE r.rolnombre = source.rolnombre
);

INSERT INTO usuario_rol (usuid, rolid, usrestado)
SELECT
    u.usuid,
    r.rolid,
    true
FROM personal ps
INNER JOIN asignacion_tipo_personal atp ON atp.psnid = ps.psnid
INNER JOIN tipo_personal tp ON tp.tpid = atp.tpid
INNER JOIN usuario u ON u.perid = ps.perid
INNER JOIN rol r ON r.rolnombre = CASE
    WHEN tp.tpnombre = 'Rector' THEN 'Rector'
    WHEN tp.tpnombre = 'Vicerrector' THEN 'Vicerrector'
    WHEN tp.tpnombre = 'Secretaria' THEN 'Secretaria'
    WHEN tp.tpnombre = 'Coordinador' THEN 'Coordinador'
    WHEN tp.tpnombre = 'Docente' THEN 'Docente'
    WHEN tp.tpnombre = 'DECE' THEN 'DECE'
    WHEN tp.tpnombre IN ('Inspector', 'Inspeccion') THEN 'Inspector'
END
WHERE ps.psnestado = true
  AND atp.atpestado = true
  AND tp.tpnombre IN ('Rector', 'Vicerrector', 'Secretaria', 'Coordinador', 'Docente', 'DECE', 'Inspector', 'Inspeccion')
  AND NOT EXISTS (
      SELECT 1
      FROM usuario_rol ur
      WHERE ur.usuid = u.usuid
        AND ur.rolid = r.rolid
  );
