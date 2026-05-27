# Gestion Contable

## Proposito

El modulo de Gestion Contable administra cobros escolares internos, evidencias de pago, revision administrativa y reportes. No reemplaza al sistema externo de facturacion ni emite documentos fiscales.

El sistema externo actual puede ser Perseo, pero el modulo no debe acoplarse a ese proveedor. Si se registra un numero de factura o documento externo, sera un dato administrativo editable por Secretaria/Contabilidad y no sera visible para representantes.

## Alcance

El modulo debe cubrir:

- Obligaciones obligatorias del periodo lectivo: matricula y pensiones.
- Rubros adicionales: salidas pedagogicas, carnet, materiales, eventos, reposiciones u otros cobros eventuales.
- Carga de comprobantes por representantes solo para obligaciones.
- Registro interno de pagos por Secretaria/Contabilidad.
- Revision, aprobacion, rechazo, anulacion y reverso de pagos.
- Reportes y exportaciones CSV.
- Notificaciones internas.
- Auditoria de cambios relevantes.

## Roles y permisos

El modulo debe depender de permisos funcionales, no de roles fijos. Secretaria puede aprobar o rechazar pagos si tiene permisos asignados desde Seguridad, y esos permisos deben poder retirarse sin cambiar codigo.

Permisos sugeridos:

```text
contabilidad.ver
contabilidad.configurar
contabilidad.obligaciones.ver
contabilidad.obligaciones.generar
contabilidad.obligaciones.editar
contabilidad.rubros.ver
contabilidad.rubros.crear
contabilidad.rubros.editar
contabilidad.comprobantes.revisar
contabilidad.comprobantes.aprobar
contabilidad.comprobantes.rechazar
contabilidad.pagos.registrar
contabilidad.pagos.reversar
contabilidad.pagos.documento_externo.editar
contabilidad.reportes.ver
contabilidad.reportes.exportar
contabilidad.auditoria.ver
contabilidad.representante.obligaciones.ver
contabilidad.representante.comprobantes.subir
contabilidad.representante.pagos.ver
contabilidad.representante.rubros.ver
```

## Obligaciones

Las obligaciones son cobros obligatorios y planificados del periodo:

- Matricula.
- Pensiones.

Reglas:

- Pueden manejar pagos parciales.
- Pueden generar saldo interno a favor.
- El saldo a favor solo es visible para Secretaria/Contabilidad.
- La matricula es la primera obligacion, pero no bloquea el pago de pensiones.
- Las pensiones se aplican cronologicamente por defecto.
- Si un representante intenta registrar enero y diciembre esta pendiente, el sistema debe advertir que el pago se registrara para diciembre.
- Secretaria/Contabilidad puede aplicar un pago a otra obligacion, pero si salta el orden cronologico debe registrar observacion interna.
- Las obligaciones futuras pendientes se anulan cuando un estudiante se retira.
- Las obligaciones ya pagadas se conservan.
- Las obligaciones pagadas no se editan directamente; requieren reverso/anulacion con permiso especial.

Estados sugeridos:

```text
PENDIENTE
EN_REVISION
PAGO_PARCIAL
PAGADO
VENCIDO
ANULADO
```

## Configuracion de pensiones y matricula

Los valores cambian por nivel educativo y tambien pueden variar por estudiante.

Cada configuracion debe permitir:

- Periodo lectivo.
- Nivel educativo.
- Valor oficial.
- Cantidad de pensiones.
- Mes y anio de inicio.
- Dia de vencimiento, normalmente dia 5.
- Si genera mora o no.
- Tipo y valor de mora si aplica.

La institucion normalmente cobra pensiones desde septiembre hasta junio del siguiente anio, pero la cantidad de pensiones debe ser configurable.

Los descuentos o becas deben soportar:

```text
PORCENTAJE
VALOR_FIJO
```

Cada obligacion generada debe congelar:

```text
valor_base
descuento_tipo
descuento_valor
valor_descuento
valor_final
motivo_descuento
```

La mora debe estar desactivada por defecto para pensiones. Si se activa, puede ser fija o porcentual.

## Rubros adicionales

La segunda seccion del modulo se llamara Rubros adicionales.

Ejemplos:

- Salidas pedagogicas.
- Carnet.
- Materiales.
- Eventos.
- Reposiciones.
- Otros.

Reglas:

- No son obligaciones obligatorias del periodo.
- No generan saldo a favor.
- No admiten pago parcial.
- Se pagan completos.
- Pueden tener fecha limite.
- Pueden marcarse como vencidos.
- El representante no sube comprobantes para rubros adicionales.
- Secretaria/Contabilidad registra los pagos recibidos por la institucion.
- La visualizacion de rubros por representantes depende de dos condiciones: el permiso `contabilidad.representante.rubros.ver` y la activacion del servicio en Configuracion contable para el periodo lectivo.

Los rubros adicionales deben poder asignarse a:

- Todos los estudiantes activos del periodo.
- Un nivel educativo.
- Un curso.
- Estudiantes especificos.

Estados sugeridos:

```text
PENDIENTE
PAGADO
VENCIDO
EXONERADO
NO_APLICA
ANULADO
```

## Flujo del representante

El flujo debe ser simple.

El representante solo puede subir comprobantes para:

- Matricula.
- Pensiones.

Campos que ingresa:

```text
obligacion seleccionada
valor reportado
archivo comprobante
```

El representante no ingresa:

- Metodo de pago.
- Numero de referencia o transaccion.
- Fecha de pago.
- Observaciones.

El sistema registra automaticamente:

```text
fecha_registro
hora_registro
usuario_registro
estado EN_REVISION
```

El representante puede visualizar los comprobantes que subio, siempre que correspondan a estudiantes que representa.

Si un comprobante es rechazado:

- No se registra como pago valido.
- No se aplica a la obligacion.
- No reduce saldo pendiente.
- No genera saldo a favor.
- La obligacion vuelve a `PENDIENTE`.
- El intento queda en historial con motivo de rechazo.
- El representante debe registrar nuevamente el pago.

Mensaje sugerido:

```text
Su registro de pago fue rechazado por una inconsistencia. Revise la observacion y registre nuevamente el pago.
```

## Flujo de Secretaria y Contabilidad

Secretaria/Contabilidad puede:

- Revisar comprobantes subidos por representantes.
- Registrar pagos internos recibidos por efectivo, deposito, transferencia u otro medio.
- Aprobar un valor distinto al valor reportado por el representante.
- Rechazar comprobantes con motivo obligatorio.
- Registrar observacion interna.
- Registrar metodo de pago.
- Registrar numero de referencia/transaccion si aplica.
- Registrar o editar posteriormente el numero de factura/documento externo.
- Reversar o anular pagos aprobados con permiso especial y motivo obligatorio.
- Al reversar un pago aprobado se deben reversar sus aplicaciones activas sobre obligaciones, rubros adicionales y saldos internos asociados.

Campos administrativos:

```text
metodo_pago
numero_referencia
valor_reportado
valor_aprobado
observacion_interna
motivo_rechazo
documento_externo_sistema
documento_externo_numero
documento_externo_fecha
```

El numero de factura/documento externo:

- No es obligatorio al aprobar.
- Puede agregarse en una edicion posterior.
- Solo es visible para Secretaria/Contabilidad.
- Debe incluirse en reportes internos/exportables.
- Debe quedar en auditoria si se edita.

## Pagos parciales y saldo interno

Las obligaciones pueden tener pagos parciales.

Ejemplo:

```text
Valor obligacion: 60.00
Valor aprobado: 50.00
Saldo pendiente: 10.00
```

Si se aprueba un valor mayor que la obligacion seleccionada, el excedente se abona automaticamente a la siguiente obligacion pendiente del estudiante, respetando el orden cronologico. El sistema no debe saltar una obligacion que tenga comprobante en revision; en ese caso detiene la aplicacion automatica y conserva el excedente como saldo interno. Si despues de cubrir obligaciones futuras todavia queda excedente, ese valor queda como saldo interno a favor. Ese saldo solo lo ve Secretaria/Contabilidad y puede aplicarse a obligaciones futuras.

Los rubros adicionales no manejan pagos parciales ni saldo a favor.

## Deteccion de duplicados

Al aprobar un comprobante, el sistema debe validar posibles duplicados.

Criterios sugeridos:

- Mismo hash de archivo.
- Mismo numero de referencia si fue registrado.
- Mismo valor aprobado.
- Mismo estudiante o representante.
- Fechas cercanas de registro o aprobacion.

Si encuentra coincidencias, debe mostrar el registro relacionado:

- Estudiante.
- Obligacion.
- Valor aprobado.
- Fecha de registro/aprobacion.
- Referencia si existe.
- Usuario aprobador.
- Enlace para visualizar el comprobante anterior.

Opciones:

- Cancelar y revisar.
- Rechazar el nuevo comprobante.
- Continuar a pesar del duplicado.

Si se continua a pesar del duplicado, la observacion interna es obligatoria.

## Archivos de comprobantes

Formatos permitidos:

```text
PDF
JPG
JPEG
PNG
```

Tamanio maximo:

```text
2 MB
```

Reglas:

- Validar extension.
- Validar MIME real.
- Guardar fuera de `public`.
- Servir archivos mediante controlador con permisos.
- No sobrescribir comprobantes anteriores.

## Notificaciones

Debe existir notificacion interna en el sistema.

Para Secretaria/Contabilidad:

- Nuevo comprobante pendiente de revision.
- Pago registrado por representante.
- Obligacion vencida.
- Posible comprobante duplicado.

Para representantes:

- Pago aprobado.
- Pago rechazado con observacion.
- Obligacion proxima a vencer.
- Obligacion vencida.
- Nuevo rubro visible, si tiene permiso para verlo.

Correo o WhatsApp quedan fuera de la primera implementacion.

## Reportes y dashboard

Debe existir un dashboard de Gestion Contable con indicadores:

- Comprobantes pendientes de revision.
- Pagos aprobados del mes.
- Obligaciones vencidas.
- Valor pendiente por pensiones.
- Rubros adicionales pendientes.
- Rubros adicionales vencidos.
- Pagos rechazados recientes.

Reportes exportables iniciales en CSV:

- Obligaciones pendientes.
- Pagos contables aprobados, rechazados y reversados.
- Rubros adicionales por estudiante y estado.
- Comprobantes en revision.
- Morosidad por curso.
- Estado de cuenta por estudiante.

Formato minimo:

```text
CSV
```

Los reportes internos deben incluir el numero de factura/documento externo cuando este registrado.

## Moneda

Todos los valores se manejan en dolares con dos decimales.

En base de datos:

```text
numeric(10,2)
```

No se debe usar `float` para valores monetarios.

## Auditoria

Deben auditarse, como minimo:

- Creacion de obligaciones.
- Edicion/anulacion de obligaciones.
- Creacion de rubros adicionales.
- Cierre de rubros adicionales como pagado, exonerado, no aplica o anulado.
- Registro de pagos.
- Aprobacion/rechazo de comprobantes.
- Reversos/anulaciones de pagos aprobados.
- Cambios en valores aprobados.
- Cambios en documento externo.
- Continuacion a pesar de duplicado.
- Cambios de configuracion contable.
- Activacion/desactivacion de servicios visibles para representantes.
- Observaciones internas obligatorias.

La auditoria debe registrar:

```text
usuario
fecha_hora
accion
entidad
entidad_id
valor_anterior
valor_nuevo
motivo_observacion
```

## Decisiones confirmadas

- El modulo visible se llamara Gestion Contable.
- Secretaria y Contabilidad pueden aprobar/rechazar si tienen permisos.
- Habra pagos parciales para obligaciones.
- El saldo a favor sera interno y no visible para representantes.
- Pensiones y matricula cambian por nivel y pueden variar por estudiante.
- Becas/descuentos soportan porcentaje y valor fijo.
- Las pensiones se generan al inicio del periodo.
- La cantidad de pensiones es configurable.
- El vencimiento normal es el dia 5, configurable.
- La mora existe como opcion, desactivada por defecto para pensiones.
- Rubros adicionales pueden asignarse por todos, nivel, curso o estudiantes.
- Representantes solo suben comprobantes de obligaciones.
- Secretaria/Contabilidad registra pagos de rubros adicionales.
- Los comprobantes rechazados no se aplican ni registran como pago valido.
- Habra notificaciones internas.
- No habra cierre mensual bloqueante.
- Habra reverso/anulacion de pagos aprobados con permiso especial.
- Obligaciones futuras pendientes se anulan al retirar un estudiante.
- Cambios de curso/nivel se manejan editando obligaciones, no recalculando automaticamente.
- Rubros adicionales se pagan completos.
- Matricula no bloquea pago de pensiones.
- Representante no ingresa metodo, referencia, fecha ni observacion.
- Secretaria/Contabilidad puede aprobar valor distinto al reportado.
- La validacion de duplicados ocurre al aprobar.
- El numero de factura externa se agrega despues, no al aprobar obligatoriamente.
- El numero de factura externa solo lo ve Secretaria/Contabilidad.
- Los reportes internos incluyen documento externo.
- Valores monetarios en USD con dos decimales.
