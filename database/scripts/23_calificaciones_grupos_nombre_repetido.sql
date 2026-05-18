-- Permite repetir el nombre visible de grupos de materias dentro de un perfil.
--
-- Ejemplo: usar "Ingles" como nombre de libreta para grupos separados de
-- 8vo, 9no y 10mo, diferenciados por materias, descripcion y orden.

ALTER TABLE grupo_materia_calificacion
    DROP CONSTRAINT IF EXISTS uq_gmc_perfil_nombre;
