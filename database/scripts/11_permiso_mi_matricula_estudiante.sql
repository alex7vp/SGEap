-- Agrega el permiso para que usuarios con rol Estudiante consulten su propia matricula.
-- Ejecutar una sola vez sobre bases existentes y luego asignar el permiso al rol Estudiante desde Seguridad.

INSERT INTO permiso (prmnombre, prmcodigo, prmdescripcion, prmestado)
SELECT 'Mi matricula', 'estudiante.mi_matricula', 'Consulta de la matricula propia del estudiante', true
WHERE NOT EXISTS (
    SELECT 1
    FROM permiso
    WHERE prmcodigo = 'estudiante.mi_matricula'
);

INSERT INTO rol_permiso (rolid, prmid, rpeestado)
SELECT r.rolid, p.prmid, true
FROM rol r
INNER JOIN permiso p ON p.prmcodigo = 'estudiante.mi_matricula'
WHERE lower(r.rolnombre) = 'estudiante'
  AND NOT EXISTS (
      SELECT 1
      FROM rol_permiso rp
      WHERE rp.rolid = r.rolid
        AND rp.prmid = p.prmid
  );
