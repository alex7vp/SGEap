-- Restringe la gestion administrativa de matriculas a roles institucionales autorizados.
-- Ejecutar en bases existentes si algun rol no administrativo conserva matriculas.gestionar.

INSERT INTO rol_permiso (rolid, prmid, rpeestado)
SELECT r.rolid, p.prmid, true
FROM rol r
INNER JOIN permiso p ON p.prmcodigo = 'matriculas.gestionar'
WHERE r.rolnombre IN (
    'Administrador',
    'Rector',
    'Vicerrector',
    'Coordinador',
    'Inspector',
    'Secretaria'
)
ON CONFLICT (rolid, prmid) DO UPDATE
SET rpeestado = true,
    rpefecha_modificacion = CURRENT_TIMESTAMP;

DELETE FROM rol_permiso rp
USING rol r, permiso p
WHERE rp.rolid = r.rolid
  AND rp.prmid = p.prmid
  AND p.prmcodigo = 'matriculas.gestionar'
  AND r.rolnombre NOT IN (
      'Administrador',
      'Rector',
      'Vicerrector',
      'Coordinador',
      'Inspector',
      'Secretaria'
  );
