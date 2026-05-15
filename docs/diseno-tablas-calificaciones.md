# Diseno de tablas del modulo de calificaciones

Este documento traduce la logica definida en `docs/logica-calificaciones.md` a una primera estructura relacional. La implementacion inicial esta en `database/scripts/18_calificaciones.sql`, el bloque de promocion esta en `database/scripts/19_calificaciones_promocion.sql` y el soporte de plantillas/vistas esta en `database/scripts/20_calificaciones_soporte.sql`. Las plantillas base se cargan con `database/seeds/06_calificaciones_plantillas_base.sql`.

## Criterios De Diseno

- La configuracion de calificaciones pertenece a un `periodo_lectivo`.
- El perfil aplicable se resuelve por prioridad: materia, curso, grado y nivel educativo.
- Las notas registradas por el docente no se mezclan con resultados calculados.
- Los resultados calculados se guardan como snapshots para reportes, publicaciones y auditoria.
- Las notas obligatorias faltantes permanecen como `NULL`; el motor de calculo debe interpretarlas como 0 cuando aplique.
- La base de datos valida estados, rangos simples y unicidad; reglas complejas como solapamiento de rangos o suma de pesos se validaran en servicios o triggers especificos.

## Bloques Principales

### Configuracion

- `perfil_calificacion`: define la politica por periodo, version, vigencia, escala numerica base, decimales y estado de configuracion.
- `perfil_calificacion_asignacion`: vincula un perfil con nivel, grado, curso o materia. Usa indices unicos parciales para evitar dos asignaciones activas del mismo alcance.
- `subperiodo_calificacion`: representa trimestres, quimestres u otra division evaluable.
- `componente_calificacion`: define componentes dentro de un subperiodo, por ejemplo formativa y sumativa.
- `escala_cualitativa`: guarda equivalencias cualitativas del perfil.
- `materia_calificacion_config`: define si una materia registra cuantitativo, cualitativo o ambitos-destrezas, si promedia y si se muestra en libreta.

### Plantillas

- `plantilla_calificacion`: modelo base reutilizable para crear perfiles por periodo.
- `plantilla_subperiodo`, `plantilla_componente`, `plantilla_escala_cualitativa`: estructura evaluativa que puede copiarse a un perfil real.
- `plantilla_ambito`, `plantilla_destreza`: modelo para perfiles por ambitos y destrezas.
- `plantilla_promocion_tramo`, `plantilla_instancia_extraordinaria`: modelo de reglas de promocion y supletorios/recuperaciones.

La semilla inicial incluye estas plantillas:

- Inicial.
- Preparatoria.
- Basica Elemental.
- Basica Media Trimestral.
- Basica Superior Trimestral.
- Bachillerato Trimestral.

### Registro Docente

- `actividad_calificacion`: actividad concreta creada para una materia y componente.
- `calificacion_estudiante`: nota o valoracion registrada para una actividad y matricula.
- `ambito_calificacion`, `destreza_calificacion`, `valoracion_destreza_estudiante`: estructura para Inicial y Preparatoria o materias que trabajen por destrezas.

### Resultados

- `resultado_materia_subperiodo`: promedio calculado por materia y subperiodo.
- `resultado_materia_final`: promedio anual o final por materia.
- `resultado_estudiante_final`: promedio general y resultado academico por matricula y perfil.

### Promocion

- `regla_promocion`: regla activa de promocion para un perfil.
- `regla_promocion_tramo`: rangos de nota que producen promovido, supletorio, recuperacion, examen de gracia o no promovido.
- `instancia_extraordinaria`: configuracion de supletorio, recuperacion o examen de gracia por perfil.
- `instancia_extraordinaria_registro`: nota extraordinaria registrada para una matricula, con materia opcional cuando la instancia aplica por materia.
- `promocion_estudiante`: resultado final de promocion por matricula y perfil.
- `promocion_materia`: estado de promocion por materia cuando la regla exige evaluar materias individualmente.

### Publicacion Y Visualizacion

- `publicacion_calificacion`: habilita o bloquea la visibilidad de libretas por curso/subperiodo o final.
- `bloqueo_visualizacion_calificacion`: bloqueo global por curso o individual por matricula.

### Auditoria

- `auditoria_calificacion`: bitacora general de acciones academicas sensibles. La aplicacion debe registrar aqui cambios de notas, cierres, reaperturas, publicaciones, anulaciones y recalculos.

### Vistas De Soporte

- `vw_calificacion_perfil_curso`: resuelve el perfil efectivo por curso usando prioridad de asignacion.
- `vw_calificacion_perfil_materia`: resuelve el perfil efectivo por materia de curso.
- `vw_calificacion_materia_config_efectiva`: combina perfil y configuracion especifica de materia para saber como registrar, promediar y mostrar la materia.

## Pendientes Para La Siguiente Iteracion

- Triggers o servicios para validar suma de pesos y rangos cualitativos no solapados.
- Triggers o servicios para validar rangos de promocion no solapados.
- Funciones o servicios para copiar una plantilla completa hacia un periodo lectivo.
- Indices adicionales segun consultas reales de libretas y cuadros finales.
