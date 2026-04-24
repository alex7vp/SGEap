-- Usuario administrador inicial
-- Si se usa la secuencia modular, ejecutar despues de:
--   database/scripts/01_catalogos.sql
--   database/scripts/02_academico.sql
--   database/scripts/03_personas.sql
--   database/scripts/04_matriculacion.sql
--   database/scripts/05_seguridad.sql
--
-- Si se usa el consolidado database/scripts/sgeap.sql,
-- no ejecutar este seed porque el usuario administrador inicial
-- ya se crea dentro del script consolidado.

-- Persona administradora
INSERT INTO persona (
    percedula,
    pernombres,
    perapellidos,
    pertelefono1,
    percorreo,
    persexo
)
SELECT
    '1234567890',
    'Administrador',
    'General',
    '0999999999',
    'admin@albanyschool.edu.ec',
    'Masculino'
WHERE NOT EXISTS (
    SELECT 1
    FROM persona
    WHERE percedula = '1234567890'
);

-- Registro opcional como personal institucional
INSERT INTO personal (
    perid,
    psnfechacontratacion,
    psnestado,
    psnobservacion
)
SELECT
    p.perid,
    CURRENT_DATE,
    true,
    'Registro inicial del administrador del sistema'
FROM persona p
WHERE p.percedula = '1234567890'
  AND NOT EXISTS (
      SELECT 1
      FROM personal ps
      WHERE ps.perid = p.perid
  );

INSERT INTO asignacion_tipo_personal (
    psnid,
    tpid,
    atpestado
)
SELECT
    ps.psnid,
    tp.tpid,
    true
FROM personal ps
INNER JOIN persona p ON p.perid = ps.perid
INNER JOIN tipo_personal tp ON tp.tpnombre = 'Directivo'
WHERE p.percedula = '1234567890'
  AND NOT EXISTS (
      SELECT 1
      FROM asignacion_tipo_personal atp
      WHERE atp.psnid = ps.psnid
        AND atp.tpid = tp.tpid
  );

-- Rol administrador
INSERT INTO rol (
    rolnombre,
    roldescripcion,
    rolestado
)
SELECT
    'Administrador',
    'Acceso completo de administracion del sistema',
    true
WHERE NOT EXISTS (
    SELECT 1
    FROM rol
    WHERE rolnombre = 'Administrador'
);

-- Permisos base
INSERT INTO permiso (prmnombre, prmcodigo, prmdescripcion, prmestado)
SELECT *
FROM (
    VALUES
        ('Dashboard', 'dashboard.ver', 'Acceso al dashboard principal', true),
        ('Configuracion', 'configuracion.gestionar', 'Gestion de configuracion institucional y academica', true),
        ('Catalogos academicos', 'catalogos.gestionar', 'Administracion de catalogos academicos base', true),
        ('Personas', 'personas.gestionar', 'Registro y mantenimiento de personas', true),
        ('Estudiantes', 'estudiantes.gestionar', 'Registro y mantenimiento de estudiantes', true),
        ('Cursos', 'cursos.gestionar', 'Creacion y mantenimiento de cursos por periodo', true),
        ('Matriculas', 'matriculas.gestionar', 'Registro y gestion de matriculas', true),
        ('Documentos de matricula', 'matriculas.documentos', 'Administracion de documentos del proceso de matricula', true),
        ('Usuarios', 'seguridad.usuarios', 'Asignacion y control de usuarios', true),
        ('Roles y permisos', 'seguridad.roles_permisos', 'Gestion de roles y permisos de seguridad', true)
) AS source (prmnombre, prmcodigo, prmdescripcion, prmestado)
WHERE NOT EXISTS (
    SELECT 1
    FROM permiso p
    WHERE p.prmcodigo = source.prmcodigo
);

-- Usuario administrador
-- Credenciales iniciales:
-- usuario: admin
-- clave: 1234567890
INSERT INTO usuario (
    perid,
    usunombre,
    usuclave,
    usuestado
)
SELECT
    p.perid,
    'admin',
    '$2y$10$IY0emg0HIqhfP05t4KHtAO5lN0FdhQ1XL4LGYTBZY5iS4tJaVZ5ma',
    true
FROM persona p
WHERE p.percedula = '1234567890'
  AND NOT EXISTS (
      SELECT 1
      FROM usuario u
      WHERE u.usunombre = 'admin'
         OR u.perid = p.perid
  );

-- Asignacion del rol administrador al usuario
INSERT INTO usuario_rol (
    usuid,
    rolid,
    usrestado
)
SELECT
    u.usuid,
    r.rolid,
    true
FROM usuario u
INNER JOIN rol r ON r.rolnombre = 'Administrador'
WHERE u.usunombre = 'admin'
  AND NOT EXISTS (
      SELECT 1
      FROM usuario_rol ur
      WHERE ur.usuid = u.usuid
        AND ur.rolid = r.rolid
  );

-- Asignacion de todos los permisos base al rol administrador
INSERT INTO rol_permiso (
    rolid,
    prmid,
    rpeestado
)
SELECT
    r.rolid,
    p.prmid,
    true
FROM rol r
INNER JOIN permiso p ON p.prmcodigo IN (
    'dashboard.ver',
    'configuracion.gestionar',
    'catalogos.gestionar',
    'personas.gestionar',
    'estudiantes.gestionar',
    'cursos.gestionar',
    'matriculas.gestionar',
    'matriculas.documentos',
    'seguridad.usuarios',
    'seguridad.roles_permisos'
)
WHERE r.rolnombre = 'Administrador'
  AND NOT EXISTS (
      SELECT 1
      FROM rol_permiso rp
      WHERE rp.rolid = r.rolid
        AND rp.prmid = p.prmid
  );
