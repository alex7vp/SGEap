# Instalacion limpia de base de datos

Para crear la base de datos desde cero, ejecutar los scripts en este orden:

1. `database/scripts/01_catalogos.sql`
2. `database/scripts/02_academico.sql`
3. `database/scripts/03_personas.sql`
4. `database/scripts/04_matriculacion.sql`
5. `database/scripts/05_seguridad.sql`
6. `database/scripts/06_triggers_reglas_negocio.sql`
7. `database/seeds/01_periodos_base.sql`
8. `database/seeds/02_catalogos_base.sql`
9. `database/seeds/03_cursos_base.sql`
10. `database/seeds/04_seguridad_admin_inicial.sql`
11. `database/scripts/07_asistencia.sql`
12. `database/seeds/05_asistencia_academica_base.sql`
13. `database/scripts/17_novedades.sql`

El script `07_asistencia.sql` se ejecuta despues de las semillas de seguridad porque, ademas de crear la estructura de asistencia, registra permisos y los asigna a roles existentes. El script `17_novedades.sql` depende de asistencia, matriculas y seguridad porque reutiliza `sesion_clase`, `matricula`, `usuario`, `rol` y `permiso`.

Como alternativa, se puede ejecutar directamente el consolidado:

1. `database/scripts/sgeap.sql`

Los scripts adicionales fuera de este orden son regularizaciones o utilidades para bases existentes. No forman parte del flujo normal de instalacion limpia.

`database/scripts/14_regularizar_usuarios_estudiantes_importados.sql` se ejecuta solo despues de importar estudiantes historicos, si se necesitan crear sus usuarios inactivos automaticamente.

## Flujo con importacion historica de matriculas

Si se va a cargar el Excel historico de matriculas 2025 2026, usar este orden:

1. Crear la base limpia con `database/scripts/sgeap.sql`.
2. Revisar la simulacion del importador:

   ```powershell
   php database\imports\import_matriculas_2025_2026.php
   ```

3. Ejecutar la importacion real:

   ```powershell
   php database\imports\import_matriculas_2025_2026.php --commit
   ```

4. Ejecutar la regularizacion de usuarios de estudiantes y representantes:

   ```sql
   database/scripts/14_regularizar_usuarios_estudiantes_importados.sql
   ```

El script `14_regularizar_usuarios_estudiantes_importados.sql` crea usuarios inactivos para los estudiantes y representantes importados que no tengan cuenta. A los estudiantes les asigna el rol `Estudiante`; a los representantes formales de `matricula_representante` les asigna el rol `Representante`. Si el usuario del estudiante ya esta activo, tambien activa al representante vinculado.

La clave inicial queda como la cedula de la persona. Si no existe cedula, usa el nombre de usuario generado.

Las cuentas quedan inactivas por seguridad. Secretaria o administracion debe activar cada usuario antes de entregarlo.
