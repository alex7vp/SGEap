-- Permisos funcionales y asignaciones base por rol.
--
-- Centraliza los permisos que antes estaban mezclados en scripts de estructura.

INSERT INTO permiso (prmnombre, prmcodigo, prmdescripcion, prmestado)
VALUES
    ('Asistencia - calendario', 'asistencia.calendario.gestionar', 'Gestion de dias, jornadas y horas habilitadas para asistencia', true),
    ('Asistencia - registrar', 'asistencia.registrar', 'Registro docente de asistencia por materia y hora', true),
    ('Asistencia - supervisar', 'asistencia.supervisar', 'Supervision y anulacion de sesiones de asistencia', true),
    ('Justificaciones - gestionar', 'justificaciones.gestionar', 'Registro, aprobacion, rechazo y anulacion de justificaciones', true),
    ('Asistencia - propia', 'asistencia.ver_propia', 'Consulta de asistencia propia del estudiante', true),
    ('Asistencia - representante', 'asistencia.representante.ver', 'Consulta de asistencia de estudiantes representados', true),
    ('Novedades - registrar', 'novedades.registrar', 'Registro de novedades de estudiantes en clase o fuera de clase', true),
    ('Novedades - supervisar', 'novedades.supervisar', 'Consulta y anulacion de novedades registradas', true),
    ('Novedades - propia', 'novedades.ver_propia', 'Consulta de novedades propias del estudiante', true),
    ('Novedades - representante', 'novedades.representante.ver', 'Consulta de novedades de estudiantes representados', true),
    ('Calificaciones - configurar', 'calificaciones.configurar', 'Gestion de perfiles, escalas y reglas de calificacion', true),
    ('Calificaciones - registrar', 'calificaciones.registrar', 'Registro de actividades y notas por docente', true),
    ('Calificaciones - editar', 'calificaciones.editar', 'Edicion global de actividades y notas por curso/materia', true),
    ('Calificaciones - validar', 'calificaciones.validar', 'Cierre y validacion academica de calificaciones', true),
    ('Calificaciones - publicar', 'calificaciones.publicar', 'Publicacion y bloqueo de visualizacion de libretas', true),
    ('Calificaciones - ver propia', 'calificaciones.ver_propia', 'Consulta de calificaciones propias del estudiante', true),
    ('Calificaciones - representante ver', 'calificaciones.representante.ver', 'Consulta de calificaciones de estudiantes representados', true),
    ('Calificaciones - auditoria ver', 'calificaciones.auditoria.ver', 'Consulta de auditoria academica de calificaciones', true),
    ('Calificaciones - auditoria exportar', 'calificaciones.auditoria.exportar', 'Exportacion de auditoria academica de calificaciones', true),
    ('Calificaciones - promocion configurar', 'calificaciones.promocion.configurar', 'Gestion de reglas de promocion e instancias extraordinarias', true),
    ('Calificaciones - promocion calcular', 'calificaciones.promocion.calcular', 'Calculo de promocion academica del estudiante', true),
    ('Calificaciones - extraordinarias registrar', 'calificaciones.extraordinarias.registrar', 'Registro de notas de supletorio, recuperacion o examen de gracia', true),
    ('Calificaciones - plantillas', 'calificaciones.plantillas.gestionar', 'Gestion de plantillas base de configuracion de calificaciones', true),
    ('Calificaciones - grupos de materias', 'calificaciones.grupos_materias.configurar', 'Configuracion de materias que se calculan como una sola nota', true)
ON CONFLICT (prmcodigo) DO UPDATE
SET prmnombre = EXCLUDED.prmnombre,
    prmdescripcion = EXCLUDED.prmdescripcion,
    prmestado = EXCLUDED.prmestado,
    prmfecha_modificacion = CURRENT_TIMESTAMP;

INSERT INTO rol_permiso (rolid, prmid, rpeestado)
SELECT r.rolid, p.prmid, true
FROM rol r
INNER JOIN permiso p ON p.prmcodigo IN (
    'asistencia.calendario.gestionar',
    'asistencia.registrar',
    'asistencia.supervisar',
    'justificaciones.gestionar',
    'asistencia.ver_propia',
    'asistencia.representante.ver',
    'novedades.registrar',
    'novedades.supervisar',
    'novedades.ver_propia',
    'novedades.representante.ver',
    'calificaciones.configurar',
    'calificaciones.registrar',
    'calificaciones.editar',
    'calificaciones.validar',
    'calificaciones.publicar',
    'calificaciones.ver_propia',
    'calificaciones.representante.ver',
    'calificaciones.auditoria.ver',
    'calificaciones.auditoria.exportar',
    'calificaciones.promocion.configurar',
    'calificaciones.promocion.calcular',
    'calificaciones.extraordinarias.registrar',
    'calificaciones.plantillas.gestionar',
    'calificaciones.grupos_materias.configurar'
)
WHERE r.rolnombre = 'Administrador'
ON CONFLICT (rolid, prmid) DO UPDATE
SET rpeestado = EXCLUDED.rpeestado,
    rpefecha_modificacion = CURRENT_TIMESTAMP;

INSERT INTO rol_permiso (rolid, prmid, rpeestado)
SELECT r.rolid, p.prmid, true
FROM rol r
INNER JOIN permiso p ON p.prmcodigo IN (
    'dashboard.ver',
    'asistencia.calendario.gestionar',
    'asistencia.supervisar',
    'justificaciones.gestionar',
    'novedades.registrar',
    'novedades.supervisar',
    'calificaciones.configurar',
    'calificaciones.registrar',
    'calificaciones.validar',
    'calificaciones.publicar',
    'calificaciones.auditoria.ver',
    'calificaciones.promocion.configurar',
    'calificaciones.promocion.calcular',
    'calificaciones.extraordinarias.registrar',
    'calificaciones.plantillas.gestionar',
    'calificaciones.grupos_materias.configurar'
)
WHERE r.rolnombre IN ('Rector', 'Vicerrector', 'Coordinador')
ON CONFLICT (rolid, prmid) DO UPDATE
SET rpeestado = EXCLUDED.rpeestado,
    rpefecha_modificacion = CURRENT_TIMESTAMP;

INSERT INTO rol_permiso (rolid, prmid, rpeestado)
SELECT r.rolid, p.prmid, true
FROM rol r
INNER JOIN permiso p ON p.prmcodigo IN (
    'asistencia.calendario.gestionar',
    'asistencia.supervisar',
    'justificaciones.gestionar',
    'novedades.registrar',
    'novedades.supervisar',
    'calificaciones.configurar',
    'calificaciones.registrar',
    'calificaciones.validar',
    'calificaciones.publicar',
    'calificaciones.auditoria.ver',
    'calificaciones.promocion.configurar',
    'calificaciones.promocion.calcular',
    'calificaciones.extraordinarias.registrar'
)
WHERE r.rolnombre = 'Secretaria'
ON CONFLICT (rolid, prmid) DO UPDATE
SET rpeestado = EXCLUDED.rpeestado,
    rpefecha_modificacion = CURRENT_TIMESTAMP;

INSERT INTO rol_permiso (rolid, prmid, rpeestado)
SELECT r.rolid, p.prmid, true
FROM rol r
INNER JOIN permiso p ON p.prmcodigo IN (
    'asistencia.calendario.gestionar',
    'asistencia.supervisar',
    'justificaciones.gestionar',
    'novedades.registrar',
    'novedades.supervisar'
)
WHERE r.rolnombre = 'Inspector'
ON CONFLICT (rolid, prmid) DO UPDATE
SET rpeestado = EXCLUDED.rpeestado,
    rpefecha_modificacion = CURRENT_TIMESTAMP;

INSERT INTO rol_permiso (rolid, prmid, rpeestado)
SELECT r.rolid, p.prmid, true
FROM rol r
INNER JOIN permiso p ON p.prmcodigo IN (
    'asistencia.registrar',
    'novedades.registrar',
    'calificaciones.registrar',
    'calificaciones.extraordinarias.registrar'
)
WHERE r.rolnombre = 'Docente'
ON CONFLICT (rolid, prmid) DO UPDATE
SET rpeestado = EXCLUDED.rpeestado,
    rpefecha_modificacion = CURRENT_TIMESTAMP;

INSERT INTO rol_permiso (rolid, prmid, rpeestado)
SELECT r.rolid, p.prmid, true
FROM rol r
INNER JOIN permiso p ON p.prmcodigo IN (
    'asistencia.ver_propia',
    'novedades.ver_propia',
    'calificaciones.ver_propia'
)
WHERE r.rolnombre = 'Estudiante'
ON CONFLICT (rolid, prmid) DO UPDATE
SET rpeestado = EXCLUDED.rpeestado,
    rpefecha_modificacion = CURRENT_TIMESTAMP;

INSERT INTO rol_permiso (rolid, prmid, rpeestado)
SELECT r.rolid, p.prmid, true
FROM rol r
INNER JOIN permiso p ON p.prmcodigo IN (
    'dashboard.ver',
    'representante.estudiantes',
    'asistencia.representante.ver',
    'novedades.representante.ver',
    'calificaciones.representante.ver'
)
WHERE r.rolnombre = 'Representante'
ON CONFLICT (rolid, prmid) DO UPDATE
SET rpeestado = EXCLUDED.rpeestado,
    rpefecha_modificacion = CURRENT_TIMESTAMP;
