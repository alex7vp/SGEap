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
    ('Calificaciones - grupos de materias', 'calificaciones.grupos_materias.configurar', 'Configuracion de materias que se calculan como una sola nota', true),
    ('Gestion Contable - ver', 'contabilidad.ver', 'Acceso al modulo de Gestion Contable', true),
    ('Gestion Contable - configurar', 'contabilidad.configurar', 'Configuracion de valores, conceptos y reglas de cobro', true),
    ('Gestion Contable - obligaciones ver', 'contabilidad.obligaciones.ver', 'Consulta de obligaciones de matricula y pensiones', true),
    ('Gestion Contable - obligaciones generar', 'contabilidad.obligaciones.generar', 'Generacion masiva de obligaciones por periodo y nivel', true),
    ('Gestion Contable - obligaciones editar', 'contabilidad.obligaciones.editar', 'Edicion administrativa de obligaciones pendientes', true),
    ('Gestion Contable - rubros ver', 'contabilidad.rubros.ver', 'Consulta de rubros adicionales', true),
    ('Gestion Contable - rubros crear', 'contabilidad.rubros.crear', 'Creacion y asignacion de rubros adicionales', true),
    ('Gestion Contable - rubros editar', 'contabilidad.rubros.editar', 'Edicion, exoneracion, anulacion o cierre de rubros adicionales', true),
    ('Gestion Contable - comprobantes revisar', 'contabilidad.comprobantes.revisar', 'Revision de comprobantes registrados por representantes', true),
    ('Gestion Contable - comprobantes aprobar', 'contabilidad.comprobantes.aprobar', 'Aprobacion de comprobantes y aplicacion de pagos', true),
    ('Gestion Contable - comprobantes rechazar', 'contabilidad.comprobantes.rechazar', 'Rechazo de comprobantes con motivo de revision', true),
    ('Gestion Contable - pagos registrar', 'contabilidad.pagos.registrar', 'Registro interno de pagos recibidos por la institucion', true),
    ('Gestion Contable - pagos reversar', 'contabilidad.pagos.reversar', 'Reverso o anulacion de pagos aprobados con motivo obligatorio', true),
    ('Gestion Contable - documento externo editar', 'contabilidad.pagos.documento_externo.editar', 'Registro o edicion posterior de factura o documento externo', true),
    ('Gestion Contable - reportes ver', 'contabilidad.reportes.ver', 'Consulta de reportes de Gestion Contable', true),
    ('Gestion Contable - reportes exportar', 'contabilidad.reportes.exportar', 'Exportacion de reportes de Gestion Contable', true),
    ('Gestion Contable - auditoria ver', 'contabilidad.auditoria.ver', 'Consulta de auditoria de Gestion Contable', true),
    ('Gestion Contable - representante obligaciones ver', 'contabilidad.representante.obligaciones.ver', 'Consulta de obligaciones por el representante', true),
    ('Gestion Contable - representante comprobantes subir', 'contabilidad.representante.comprobantes.subir', 'Carga de comprobantes de matricula y pensiones por el representante', true),
    ('Gestion Contable - representante pagos ver', 'contabilidad.representante.pagos.ver', 'Consulta de historial de pagos por el representante', true),
    ('Gestion Contable - representante rubros ver', 'contabilidad.representante.rubros.ver', 'Consulta de rubros adicionales por el representante', true),
    ('Backups - gestionar', 'backups.gestionar', 'Generacion, descarga y eliminacion de respaldos del sistema', true)
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
    'calificaciones.grupos_materias.configurar',
    'contabilidad.ver',
    'contabilidad.configurar',
    'contabilidad.obligaciones.ver',
    'contabilidad.obligaciones.generar',
    'contabilidad.obligaciones.editar',
    'contabilidad.rubros.ver',
    'contabilidad.rubros.crear',
    'contabilidad.rubros.editar',
    'contabilidad.comprobantes.revisar',
    'contabilidad.comprobantes.aprobar',
    'contabilidad.comprobantes.rechazar',
    'contabilidad.pagos.registrar',
    'contabilidad.pagos.reversar',
    'contabilidad.pagos.documento_externo.editar',
    'contabilidad.reportes.ver',
    'contabilidad.reportes.exportar',
    'contabilidad.auditoria.ver',
    'contabilidad.representante.obligaciones.ver',
    'contabilidad.representante.comprobantes.subir',
    'contabilidad.representante.pagos.ver',
    'contabilidad.representante.rubros.ver',
    'backups.gestionar'
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
    'calificaciones.grupos_materias.configurar',
    'contabilidad.ver',
    'contabilidad.configurar',
    'contabilidad.obligaciones.ver',
    'contabilidad.obligaciones.generar',
    'contabilidad.obligaciones.editar',
    'contabilidad.rubros.ver',
    'contabilidad.rubros.crear',
    'contabilidad.rubros.editar',
    'contabilidad.comprobantes.revisar',
    'contabilidad.comprobantes.aprobar',
    'contabilidad.comprobantes.rechazar',
    'contabilidad.pagos.registrar',
    'contabilidad.pagos.reversar',
    'contabilidad.pagos.documento_externo.editar',
    'contabilidad.reportes.ver',
    'contabilidad.reportes.exportar',
    'contabilidad.auditoria.ver'
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
    'calificaciones.extraordinarias.registrar',
    'contabilidad.ver',
    'contabilidad.obligaciones.ver',
    'contabilidad.obligaciones.generar',
    'contabilidad.obligaciones.editar',
    'contabilidad.rubros.ver',
    'contabilidad.rubros.crear',
    'contabilidad.rubros.editar',
    'contabilidad.comprobantes.revisar',
    'contabilidad.comprobantes.aprobar',
    'contabilidad.comprobantes.rechazar',
    'contabilidad.pagos.registrar',
    'contabilidad.pagos.documento_externo.editar',
    'contabilidad.reportes.ver',
    'contabilidad.reportes.exportar'
)
WHERE r.rolnombre = 'Secretaria'
ON CONFLICT (rolid, prmid) DO UPDATE
SET rpeestado = EXCLUDED.rpeestado,
    rpefecha_modificacion = CURRENT_TIMESTAMP;

INSERT INTO rol_permiso (rolid, prmid, rpeestado)
SELECT r.rolid, p.prmid, true
FROM rol r
INNER JOIN permiso p ON p.prmcodigo IN (
    'dashboard.ver',
    'contabilidad.ver',
    'contabilidad.configurar',
    'contabilidad.obligaciones.ver',
    'contabilidad.obligaciones.generar',
    'contabilidad.obligaciones.editar',
    'contabilidad.rubros.ver',
    'contabilidad.rubros.crear',
    'contabilidad.rubros.editar',
    'contabilidad.comprobantes.revisar',
    'contabilidad.comprobantes.aprobar',
    'contabilidad.comprobantes.rechazar',
    'contabilidad.pagos.registrar',
    'contabilidad.pagos.reversar',
    'contabilidad.pagos.documento_externo.editar',
    'contabilidad.reportes.ver',
    'contabilidad.reportes.exportar',
    'contabilidad.auditoria.ver'
)
WHERE r.rolnombre = 'Contabilidad'
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
    'calificaciones.representante.ver',
    'contabilidad.representante.obligaciones.ver',
    'contabilidad.representante.comprobantes.subir',
    'contabilidad.representante.pagos.ver'
)
WHERE r.rolnombre = 'Representante'
ON CONFLICT (rolid, prmid) DO UPDATE
SET rpeestado = EXCLUDED.rpeestado,
    rpefecha_modificacion = CURRENT_TIMESTAMP;
