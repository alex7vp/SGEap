# Instalacion limpia de base de datos

## Opcion recomendada

Para crear la estructura desde cero, ejecutar primero el consolidado:

1. `database/scripts/sgeap.sql`

Luego ejecutar las semillas, en este orden:

1. `database/seeds/01_periodos_base.sql`
2. `database/seeds/02_catalogos_base.sql`
3. `database/seeds/03_cursos_base.sql`
4. `database/seeds/04_seguridad_admin_inicial.sql`
5. `database/seeds/05_asistencia_academica_base.sql`
6. `database/seeds/06_calificaciones_plantillas_base.sql`
7. `database/seeds/07_novedades_base.sql`
8. `database/seeds/08_permisos_funcionales_base.sql`
9. `database/seeds/09_gestion_contable_base.sql`

`sgeap.sql` es el esquema consolidado. No contiene semillas de catalogos, permisos ni datos iniciales; esos inserts viven en `database/seeds`.

Para bases ya instaladas antes de los ultimos ajustes de configuracion contable, ejecutar:

1. `database/scripts/25_configuracion_contable_alcance.sql`
2. `database/scripts/26_configuracion_contable_mes_final.sql`
3. `database/scripts/27_gestion_contable_conceptos_categoria.sql`
4. `database/scripts/28_gestion_contable_configuracion_modulo.sql`
5. `database/scripts/30_matriculas_permiso_roles_administrativos.sql`
6. `database/scripts/31_comunicados.sql`
7. `database/scripts/32_comunicados_whatsapp.sql`

No hace falta en instalaciones limpias porque el consolidado ya incluye ese estado.

## Opcion modular

Si se prefiere revisar o ejecutar la estructura por bloques, usar este orden:

1. `database/scripts/01_catalogos.sql`
2. `database/scripts/02_academico.sql`
3. `database/scripts/03_personas.sql`
4. `database/scripts/04_matriculacion.sql`
5. `database/scripts/05_seguridad.sql`
6. `database/scripts/06_triggers_reglas_negocio.sql`
7. `database/scripts/07_asistencia.sql`
8. `database/scripts/17_novedades.sql`
9. `database/scripts/18_calificaciones.sql`
10. `database/scripts/19_calificaciones_promocion.sql`
11. `database/scripts/20_calificaciones_soporte.sql`
12. `database/scripts/21_calificaciones_grupos_materia.sql`
13. `database/scripts/23_calificaciones_habilitacion_registro.sql`
14. `database/scripts/24_gestion_contable.sql`
15. `database/scripts/31_comunicados.sql`

Despues de la estructura modular, ejecutar las mismas semillas listadas en la opcion recomendada.

## Notas

Los scripts `22` a `26` antiguos fueron absorbidos en el estado final de `21_calificaciones_grupos_materia.sql` y `08_permisos_funcionales_base.sql`. Los scripts posteriores que existan en esta carpeta son ajustes incrementales para bases ya instaladas, salvo que tambien esten listados en la opcion modular.

`database/scripts/14_regularizar_usuarios_estudiantes_importados.sql` sigue siendo una utilidad para bases con importacion historica. No forma parte de la instalacion limpia normal.

## Flujo con importacion historica de matriculas

Si se va a cargar el Excel historico de matriculas 2025 2026, usar este orden:

1. Crear la base limpia con `database/scripts/sgeap.sql`.
2. Ejecutar las semillas base.
3. Revisar la simulacion del importador:

   ```powershell
   php database\imports\import_matriculas_2025_2026.php
   ```

4. Ejecutar la importacion real:

   ```powershell
   php database\imports\import_matriculas_2025_2026.php --commit
   ```

5. Ejecutar la regularizacion de usuarios de estudiantes y representantes:

   ```sql
   database/scripts/14_regularizar_usuarios_estudiantes_importados.sql
   ```

El script `14_regularizar_usuarios_estudiantes_importados.sql` crea usuarios inactivos para estudiantes y representantes importados que no tengan cuenta. La clave inicial queda como la cedula de la persona; si no existe cedula, usa el nombre de usuario generado.
