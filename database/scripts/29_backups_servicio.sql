-- Servicio de backups del sistema.

INSERT INTO permiso (prmnombre, prmcodigo, prmdescripcion, prmestado)
VALUES (
    'Backups - gestionar',
    'backups.gestionar',
    'Generacion, descarga y eliminacion de respaldos del sistema',
    true
)
ON CONFLICT (prmcodigo) DO UPDATE
SET prmnombre = EXCLUDED.prmnombre,
    prmdescripcion = EXCLUDED.prmdescripcion,
    prmestado = EXCLUDED.prmestado,
    prmfecha_modificacion = CURRENT_TIMESTAMP;

INSERT INTO rol_permiso (rolid, prmid, rpeestado)
SELECT r.rolid, p.prmid, true
FROM rol r
INNER JOIN permiso p ON p.prmcodigo = 'backups.gestionar'
WHERE r.rolnombre = 'Administrador'
ON CONFLICT (rolid, prmid) DO UPDATE
SET rpeestado = EXCLUDED.rpeestado,
    rpefecha_modificacion = CURRENT_TIMESTAMP;
