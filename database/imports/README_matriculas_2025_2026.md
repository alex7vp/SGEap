# Importacion de matriculas 2025 2026

Este importador carga el archivo Excel historico de estudiantes como matriculas del periodo lectivo `2025 2026`.

Script:

```powershell
php database\imports\import_matriculas_2025_2026.php
```

Archivo Excel por defecto:

```text
C:\Users\Alex\Downloads\Matriculas (1).xlsx
```

## Antes de importar

En una base nueva, ejecutar primero la instalacion limpia:

```sql
database/scripts/sgeap.sql
```

Luego confirmar que existan:

- periodo lectivo `2025 2026`;
- paralelo `A`;
- grados y cursos base;
- catalogos base;
- roles y permisos base.

## Reglas aplicadas

- Periodo destino: `2025 2026`.
- Paralelo destino: `A`.
- Fecha de matricula por defecto: `2025-09-01`.
- Duplicados por cedula de estudiante: se importa la primera fila y se omiten las siguientes.
- Cedula de estudiante vacia o invalida: se reemplaza por una cedula artificial desde `0000000001`.
- Grupo sanguineo: se normaliza si es posible. Si no se reconoce, se deja vacio.
- Nivel del Excel: se mapea al grado del sistema. Si no se puede mapear, se omite la fila.
- Representante sin cedula: se intenta inferir desde padre o madre cuando el Excel indica `PADRE` o `MADRE`, usando la cedula del familiar correspondiente.
- Representante no inferible: se reporta y no se inserta en `matricula_representante`.
- Padre y madre: se importan como familiares solo si tienen cedula valida de 10 digitos.
- El script genera un CSV de reporte en `storage\temp\import_matriculas_2025_2026_report.csv`.

## Requisitos

Ejecutar desde la raiz del proyecto:

```powershell
cd C:\xampp\htdocs\SGEap
```

PHP CLI debe tener habilitadas estas extensiones:

```text
zip
pdo_pgsql
pgsql
```

Verificar:

```powershell
php -m | findstr /i "zip pgsql"
php -r "echo extension_loaded('pdo_pgsql') ? 'pdo_pgsql=on' : 'pdo_pgsql=off';"
```

Si `pdo_pgsql=off`, abrir el `php.ini` usado por consola:

```powershell
php --ini
```

Habilitar quitando `;`:

```ini
extension=pgsql
extension=pdo_pgsql
```

## Simulacion

La simulacion no escribe en la base de datos.

```powershell
php database\imports\import_matriculas_2025_2026.php
```

La salida muestra:

- filas reales leidas;
- filas importables;
- filas omitidas;
- insertadas, si se ejecuto con `--commit`;
- cedulas artificiales;
- duplicados omitidos;
- niveles no mapeados;
- cursos no encontrados;
- grupos sanguineos no importados;
- representantes sin cedula no insertados.

Revisar el reporte:

```text
storage\temp\import_matriculas_2025_2026_report.csv
```

## Importacion real

Ejecutar solo despues de revisar la simulacion:

```powershell
php database\imports\import_matriculas_2025_2026.php --commit
```

El script usa una transaccion. Si ocurre un error, PostgreSQL revierte la importacion completa.

## Reparar representantes despues de importar

Si la importacion ya se ejecuto antes de la regla de inferencia de representantes, usar:

```powershell
php database\imports\import_matriculas_2025_2026.php --commit --repair-representatives
```

Este modo no duplica matriculas. Revisa las matriculas existentes del periodo `2025 2026` y agrega representante cuando pueda inferirlo desde padre o madre.

## Opciones

Usar otro archivo:

```powershell
php database\imports\import_matriculas_2025_2026.php --file="C:\ruta\archivo.xlsx"
```

Cambiar periodo:

```powershell
php database\imports\import_matriculas_2025_2026.php --period="2025 2026" --commit
```

Cambiar paralelo:

```powershell
php database\imports\import_matriculas_2025_2026.php --parallel=A --commit
```

Cambiar fecha de matricula:

```powershell
php database\imports\import_matriculas_2025_2026.php --date=2025-09-01 --commit
```

Cambiar reporte:

```powershell
php database\imports\import_matriculas_2025_2026.php --report="C:\ruta\reporte.csv"
```

Ver ayuda:

```powershell
php database\imports\import_matriculas_2025_2026.php --help
```

## Errores comunes

### pdo_pgsql=off

PHP CLI no puede conectarse a PostgreSQL.

Solucion: habilitar `extension=pgsql` y `extension=pdo_pgsql` en el `php.ini` de consola.

### No existe el archivo Excel

Validar la ruta por defecto o pasar otra ruta con `--file`.

### Invalid text representation para boolean

El script ya fue ajustado para enviar booleanos como `true`/`false`.

### Invalid parameter number

El script ya fue ajustado para separar parametros de `INSERT` y `UPDATE`.

### value too long for character varying

El script ya recorta los textos a los limites definidos por la base.

### Muchos registros omitidos por curso_no_encontrado

Significa que el grado fue reconocido, pero no existe un curso para ese grado, periodo y paralelo.

Solucion: ejecutar o revisar el seed de cursos base:

```sql
database/seeds/03_cursos_base.sql
```

Luego volver a correr el importador con `--commit`. Las matriculas ya insertadas se omiten y se intentan importar las pendientes.

## Despues de importar

Revisar en el sistema:

- Dashboard: cantidad de matriculas del periodo.
- Modulo Matriculas: gestion de matriculas del periodo `2025 2026`.
- Modulo Estudiantes: fichas, salud, representante, documentos y recursos.
- Reporte CSV: filas omitidas, cedulas artificiales y grupos sanguineos no importados.

## Regularizar usuarios de estudiantes importados

Despues de importar estudiantes historicos, ejecutar si se necesitan crear sus cuentas de usuario:

```sql
database/scripts/14_regularizar_usuarios_estudiantes_importados.sql
```

Este script:

- crea el permiso `estudiante.mi_matricula`, si no existe;
- crea el rol `Estudiante`, si no existe;
- asigna el permiso `estudiante.mi_matricula` al rol `Estudiante`;
- crea usuarios inactivos para estudiantes sin cuenta;
- genera el usuario con las dos primeras letras de cada nombre y apellido;
- corrige usuarios inactivos que hayan quedado con la cedula como nombre;
- asigna el rol `Estudiante`.

Las cuentas quedan inactivas. La clave inicial queda como la cedula del estudiante; si no hay cedula, queda como el nombre de usuario generado.

Secretaria o administracion debe activar cada usuario antes de entregarlo.
