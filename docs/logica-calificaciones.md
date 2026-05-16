# Logica de negocio del modulo de calificaciones

Este documento resume las decisiones de negocio definidas para construir el modulo de calificaciones. La regla principal es que las politicas de evaluacion, calculo, publicacion y promocion deben ser configurables por periodo lectivo y no deben quedar quemadas en codigo.

## Principio General

El sistema debe trabajar con perfiles de calificacion configurables por periodo lectivo. Los niveles educativos y grados existentes sirven como ayuda para crear y asignar perfiles, pero no deben ser la unica fuente de regla.

Una configuracion completa debe responder:

- que perfil aplica al estudiante, curso o materia;
- como se registran las calificaciones;
- como se calculan los promedios;
- que escala cuantitativa y cualitativa se usa;
- que materias aportan al promedio;
- que resultados ve el estudiante o representante;
- que reglas de promocion aplican;
- que auditoria se registra.

## Niveles Y Perfiles

Los niveles y subniveles base de Ecuador se usaran como referencia:

- Educacion Inicial:
  - Inicial 1.
  - Inicial 2.
- Educacion General Basica:
  - Preparatoria.
  - Elemental: 2do, 3ro y 4to.
  - Media: 5to, 6to y 7mo.
  - Superior: 8vo, 9no y 10mo.
- Bachillerato:
  - 1ro, 2do y 3ro BGU u otras variantes.

Los perfiles sugeridos por defecto son:

- Inicial.
- Preparatoria.
- Basica Elemental.
- Basica Media.
- Basica Superior.
- Bachillerato.

Cada perfil se crea para un periodo lectivo especifico y puede cambiar de un anio a otro.

## Alcance De Aplicacion

El perfil debe poder asignarse con distintos niveles de alcance:

- nivel educativo;
- grado;
- curso;
- materia del curso.

La prioridad recomendada es:

```text
Materia del curso > Curso > Grado > Nivel educativo
```

Esto permite que un nivel tenga una regla general, pero que un grado, curso o materia tenga una excepcion.

Ejemplo:

- Basica Media aplica a 5to, 6to y 7mo.
- Preparatoria puede tener perfil propio aunque en catalogos este relacionado con otro nivel.
- Comportamiento puede mostrarse como cualitativa aunque el curso maneje notas cuantitativas.

## Motores Del Modulo

La logica debe separarse en tres motores.

### Motor De Evaluacion

Registra y calcula:

- perfiles;
- subperiodos;
- componentes;
- actividades;
- notas;
- ambitos;
- destrezas;
- escalas;
- equivalencias cualitativas;
- redondeo;
- promedios por componente;
- promedios por subperiodo;
- promedios por materia.
- promedios por grupo de materias cuando varias asignaturas se reportan como una sola nota.

### Motor De Promocion

Determina el resultado academico del anio:

- promovido;
- supletorio;
- recuperacion;
- examen de gracia;
- no promovido;
- nota final ajustada;
- instancias extraordinarias configuradas.

### Motor De Reportes

Presenta resultados, pero no decide calculos:

- libreta parcial;
- libreta final;
- cuadro final;
- certificado de promocion;
- reportes por curso;
- resumen por estudiante.

El motor de reportes debe consumir resultados ya calculados por evaluacion y promocion.

## Versionamiento Por Periodo Lectivo

Una configuracion usada para registrar notas no debe cambiarse libremente sin trazabilidad.

Estados recomendados:

- BORRADOR: editable libremente antes de usarse.
- ACTIVA: usada para registro; cambios sensibles requieren control.
- EN_REVISION: propuesta de cambio a mitad de anio.
- BLOQUEADA: no admite cambios directos porque ya existen cierres o publicaciones.
- ARCHIVADA: configuracion historica cerrada.

Si cambian directrices a mitad de anio, debe crearse una nueva version de configuracion con:

- fecha de vigencia;
- alcance;
- modo de aplicacion;
- motivo;
- usuario autorizador.

Ejemplo:

```text
Version 1: Trimestre 1 y 2, formativa 70%, sumativa 30%.
Version 2: Trimestre 3, formativa 60%, sumativa 40%.
```

## Plantillas

Para no configurar todo desde cero cada anio, el sistema debe permitir plantillas.

Plantillas sugeridas:

- Inicial / Preparatoria por ambitos y destrezas.
- Basica Elemental.
- Basica Media trimestral.
- Basica Superior trimestral.
- Bachillerato trimestral.

Una plantilla es un modelo base. Al crear la configuracion de un periodo lectivo, se copia la plantilla. La copia pertenece al anio lectivo y puede ajustarse sin afectar la plantilla original ni periodos anteriores.

## Subperiodos

Un subperiodo es una division evaluable del periodo lectivo.

Ejemplos:

- Trimestre 1.
- Trimestre 2.
- Trimestre 3.
- Quimestre 1.
- Quimestre 2.

Cada subperiodo debe poder configurar:

- nombre;
- orden;
- fecha de inicio;
- fecha de fin;
- estado;
- si participa en el promedio final;
- peso final, si el calculo es ponderado.

Para un esquema trimestral normal, el promedio anual recomendado es:

```text
(T1 + T2 + T3) / 3
```

Para un esquema quimestral normal:

```text
(Q1 + Q2) / 2
```

El uso de pesos como 33.33% debe evitarse cuando los periodos son equivalentes, para no acumular errores de redondeo.

## Componentes Y Actividades

Cada subperiodo puede dividirse en componentes configurables.

Ejemplo:

```text
Trimestre 1
- Evaluacion formativa: 70%
- Evaluacion sumativa: 30%
```

Cada componente puede contener actividades.

Ejemplos de actividades:

- tareas;
- lecciones;
- exposiciones;
- proyectos;
- evaluaciones;
- participaciones.

El promedio del componente puede calcularse por:

- promedio simple de actividades;
- promedio ponderado de actividades.

Para el caso actual observado en Excel, la regla base es:

```text
Promedio componente = promedio simple de sus actividades.
Promedio trimestre = formativa * 70% + sumativa * 30%.
```

Los nombres y pesos de los componentes deben ser configurables por perfil y periodo lectivo.

## Escala Cuantitativa

Cada perfil debe configurar:

- nota minima;
- nota maxima;
- nota minima de aprobacion;
- cantidad de decimales;
- metodo decimal.

Ejemplo:

```text
Minima: 0.00
Maxima: 10.00
Aprobacion: 7.00
Decimales: 2
Metodo: redondeo
```

El redondeo debe aplicarse antes de buscar equivalencias cualitativas.

Ejemplo:

```text
9.006 -> 9.01 -> A+
9.004 -> 9.00 -> A
```

## Escala Cualitativa

Cada perfil debe poder configurar equivalencias cualitativas.

Ejemplo:

```text
9.01 - 10.00 = A+
8.01 - 9.00  = A
7.00 - 8.00  = B
0.00 - 6.99  = C
```

Reglas:

- los rangos no deben solaparse;
- los rangos deben estar dentro de la escala cuantitativa;
- la equivalencia se determina despues de redondear o truncar;
- la escala pertenece al periodo lectivo y perfil.

## Tipos De Evaluacion Por Perfil

### Inicial Y Preparatoria

Por defecto:

- trabajan con ambitos y destrezas;
- usan calificacion cualitativa;
- no calculan promedio numerico;
- no tienen regla de perdida de anio.

La estructura es:

```text
Ambito
└── Destreza
    └── Valoracion cualitativa
```

Aunque este sea el comportamiento base, debe poder cambiar en otro periodo lectivo.

### Basica Elemental

Por defecto:

- puede registrar notas cuantitativas;
- calcula promedio simple;
- muestra equivalencia cualitativa;
- no aplica perdida de anio;
- las materias pueden ser promediables o no.

### Basica Media

Por defecto:

- usa notas cuantitativas;
- puede manejar formativa y sumativa;
- componentes y pesos son configurables;
- muestra equivalencia cualitativa;
- materias promediables y no promediables.

### Basica Superior Y Bachillerato

Por defecto:

- usa notas cuantitativas;
- maneja componentes configurables;
- muestra equivalencia cualitativa;
- genera promedio final;
- aplica reglas de promocion;
- puede tener supletorio, recuperacion y examen de gracia segun configuracion del periodo.

## Materias Promediables Y No Promediables

Cada materia del curso debe poder configurar:

- tipo de registro: cuantitativo, cualitativo o ambitos-destrezas;
- tipo de visualizacion: cuantitativa, cualitativa o mixta;
- si aporta al promedio general;
- si aparece en libreta;
- si usa equivalencia cualitativa.

Ejemplos:

```text
Matematica:
- docente registra nota cuantitativa;
- estudiante ve nota y equivalencia;
- aporta al promedio.
```

```text
Comportamiento:
- docente registra nota cuantitativa;
- estudiante ve equivalencia cualitativa;
- no aporta al promedio.
```

El promedio general solo debe usar materias configuradas como promediables.

## Grupos De Materias

Algunas instituciones reportan varias materias como una sola nota academica. Un caso comun es:

```text
Ingles + Science + Language = una sola nota de Ingles / Area bilingue
```

La solucion no debe borrar las materias reales ni mezclar los registros docentes. Cada materia conserva sus actividades y notas propias, pero el motor de calculo produce un resultado adicional de grupo.

Cada grupo debe configurar:

- perfil de calificacion al que pertenece;
- area academica a la que pertenece para reportes y libretas;
- nombre visible del grupo;
- materias integrantes;
- materia representante, si la libreta debe mostrar el nombre de una materia principal;
- modo de calculo: promedio simple, promedio ponderado o suma;
- peso de cada materia cuando el calculo sea ponderado;
- si el grupo aporta al promedio general;
- si se muestran o no las materias integrantes como detalle en la libreta.

Regla recomendada:

```text
Las materias integrantes no aportan individualmente al promedio general cuando pertenecen a un grupo activo.
El promedio general usa la nota final del grupo.
```

## Datos Incompletos

Regla definida:

```text
Toda nota obligatoria faltante cuenta como 0 en el calculo.
```

Sin embargo, no conviene guardar automaticamente 0. La nota faltante debe seguir como NULL y el motor de calculo la interpreta como 0 cuando corresponde.

Esto permite distinguir:

- 0 real registrado;
- nota sin registrar que computa como 0.

Estados recomendados de actividad:

- obligatoria;
- opcional;
- anulada;
- exonerada.

Reglas:

- actividad obligatoria sin nota: cuenta como 0;
- actividad opcional sin nota: no afecta;
- actividad anulada: no afecta a nadie;
- exoneracion individual: no afecta al estudiante exonerado.

## Auditoria

Toda accion que cambie un resultado academico debe dejar trazabilidad.

Debe registrarse:

- usuario;
- fecha y hora;
- tipo de accion;
- entidad afectada;
- valor anterior;
- valor nuevo;
- motivo, cuando aplique.

Eventos importantes:

- nota registrada;
- nota corregida;
- nota anulada;
- actividad creada o editada;
- subperiodo cerrado;
- subperiodo reabierto;
- configuracion activada;
- configuracion versionada;
- libreta publicada;
- libreta anulada;
- promocion calculada;
- promocion modificada;
- supletorio registrado;
- recuperacion registrada;
- examen de gracia registrado.

La auditoria debe registrarse siempre, pero solo debe ser visible para:

- Administrador;
- Rector;
- Coordinadores.

Permisos sugeridos:

- calificaciones.auditoria.ver;
- calificaciones.auditoria.exportar.

## Publicacion Y Visualizacion

Calcular no es lo mismo que publicar.

Estados academicos recomendados:

- EN_REGISTRO;
- CERRADO_DOCENTE;
- VALIDADO_COORDINACION;
- PUBLICADO.

Solo los resultados academicos publicados pueden ser visibles para estudiante o representante, pero ademas se necesita control administrativo de visualizacion.

Estados de visualizacion:

- NO_DISPONIBLE;
- HABILITADA;
- BLOQUEADA.

Regla:

```text
Estudiante o representante ve la libreta solo si:
estado academico = PUBLICADO
y visualizacion = HABILITADA
y no existe bloqueo vigente.
```

Secretaria o Administracion debe poder bloquear visualizacion por razones administrativas, por ejemplo pagos pendientes u otros inconvenientes.

El bloqueo puede ser:

- global por curso o subperiodo;
- individual por estudiante.

## Casos Especiales

### Ingreso Tardio

Si el estudiante ingresa cuando un subperiodo ya esta avanzado o cerrado, se permite registrar una nota de integracion u homologacion.

Puede registrarse como:

- promedio formativa;
- promedio sumativa;
- promedio del subperiodo.

Debe quedar claro que esta nota no proviene de actividades normales.

### Retiro

Si el estudiante se retira, se entregan las notas generadas hasta la fecha. No se fuerza completar el anio.

### Cambio De Curso

Si el estudiante cambia de curso dentro del mismo grado, conserva sus notas.

Las notas deben poder trasladarse por estudiante, subperiodo y asignatura equivalente, no depender solamente del curso anterior.

### Materia Exonerada

Por ahora no se define una exoneracion formal automatica. El docente completa o registra las notas segun el criterio academico correspondiente.

### Estudiante Sin Nota

La nota obligatoria faltante cuenta como 0, pero el docente puede editar y llenar la nota dentro de la ventana permitida o con autorizacion.

### Materias Cualitativas

Inicial y Preparatoria trabajan con evaluacion cualitativa por ambitos y destrezas.

Desde Basica Elemental hasta Bachillerato se registra cuantitativo y el sistema muestra su equivalencia cualitativa cuando corresponda.

Algunas materias pueden mostrarse solo cualitativamente al estudiante, aunque el docente registre nota cuantitativa.

## Promocion

Las reglas de promocion son configurables por periodo lectivo y perfil.

Para el anio actual propuesto:

```text
Promedio final >= 7.00 -> promovido.
Promedio final >= 4.00 y < 7.00 -> supletorio.
Promedio final < 4.00 -> no promovido.
```

Si aprueba supletorio:

```text
nota supletorio >= 7.00
promedio final ajustado = 7.00
estado = promovido
```

Si no aprueba:

```text
estado = no promovido
```

## Instancias Extraordinarias

Supletorio, recuperacion y examen de gracia deben configurarse o crearse para cada periodo lectivo y perfil.

No deben existir como regla global del sistema.

Cada instancia debe configurar:

- nombre;
- orden;
- estado;
- rango que habilita;
- nota minima para aprobar;
- nota final asignada si aprueba;
- si permite siguiente instancia si reprueba;
- fecha inicio;
- fecha fin;
- si aplica a materias o promedio general.

Ejemplo:

```text
Supletorio:
- rango habilitante: 4.00 a 6.99;
- nota minima para aprobar: 7.00;
- nota final si aprueba: 7.00;
- permite siguiente instancia: no.
```

Otro periodo lectivo podria tener:

```text
1. Supletorio
2. Recuperacion
3. Examen de gracia
```

El motor de promocion debe consultar las instancias configuradas para el periodo y perfil antes de decidir el resultado.

## Flujo General Del Docente

Para registrar notas:

1. El docente entra al modulo de calificaciones.
2. El sistema identifica el periodo lectivo actual.
3. El sistema resuelve el perfil aplicable al curso o materia.
4. El sistema valida si existe subperiodo abierto por fecha y estado.
5. El docente selecciona materia y curso asignado.
6. Selecciona agregar nota.
7. Selecciona componente, por ejemplo formativa o sumativa.
8. Crea o selecciona una actividad.
9. Registra notas por estudiante.
10. El sistema valida escala, decimales, permisos, fechas y estado.
11. El sistema recalcula promedios segun configuracion.

El docente solo puede registrar en materias asignadas y dentro de la ventana permitida, salvo autorizacion especial.

## Flujo De Calculo

Para perfiles cuantitativos:

```text
Actividad -> promedio de componente
Componente -> promedio de subperiodo
Subperiodos -> promedio anual de materia
Materias promediables -> promedio general del estudiante
Promedio cuantitativo -> equivalencia cualitativa
Promedio final -> regla de promocion
```

Para perfiles por ambitos y destrezas:

```text
Ambito -> destrezas -> valoracion cualitativa -> reporte cualitativo
```

## Pendientes Para Diseno De Tablas

Antes de implementar se debe traducir esta logica en tablas para:

- perfiles de calificacion;
- asignacion de perfiles;
- versionamiento;
- subperiodos;
- componentes;
- actividades;
- notas;
- ambitos;
- destrezas;
- escalas cualitativas;
- reglas de materias;
- resultados calculados;
- reglas de promocion;
- instancias extraordinarias;
- publicaciones;
- bloqueos de visualizacion;
- auditoria academica.

La primera propuesta de tablas quedo documentada en `docs/diseno-tablas-calificaciones.md` y materializada en `database/scripts/18_calificaciones.sql`, `database/scripts/19_calificaciones_promocion.sql` y `database/scripts/20_calificaciones_soporte.sql`. Las plantillas iniciales se cargan desde `database/seeds/06_calificaciones_plantillas_base.sql`.

