# Regla De Negocio: Usuario Temporal

## Objetivo

Permitir que el representante de un alumno nuevo ingrese al sistema con acceso limitado para completar el proceso de matricula sin convertirlo todavia en un usuario definitivo.

## Regla Principal

Un usuario temporal es una cuenta normal de `usuario` asociada a una `persona`, con metadatos adicionales en `usuario_temporal`.

No se crea un sistema de autenticacion paralelo.

## Estructura

- `persona`: datos del representante.
- `usuario`: credenciales de acceso.
- `usuario_temporal`: vigencia y estado del acceso temporal.
- `usuario_rol`: asignacion del rol `Representante temporal`.
- `Secretaria`: rol interno autorizado para crear y anular accesos temporales.

## Estados

- `ACTIVO`: puede iniciar sesion mientras no haya vencido.
- `EXPIRADO`: ya no puede iniciar sesion por fecha vencida.
- `ELIMINADO`: acceso anulado logicamente por secretaria o administracion.
- `CONVERTIDO`: dejo de ser temporal porque la matricula fue aprobada o el representante quedo formalizado.

## Reglas De Acceso

El login permite entrar a un usuario temporal solo si:

- `usuario.usuestado = true`
- `usuario_temporal.utestado = 'ACTIVO'`
- `usuario_temporal.utfecha_expiracion >= CURRENT_TIMESTAMP`

Si el usuario no existe en `usuario_temporal`, se trata como usuario normal.

## Rol Secretaria

El rol `Secretaria` opera el proceso institucional de matricula. Sus permisos base son:

- `dashboard.ver`
- `personas.gestionar`
- `estudiantes.gestionar`
- `matriculas.gestionar`
- `usuarios_temporales.gestionar`

No recibe permisos de `seguridad.roles_permisos` ni `configuracion.gestionar`, porque esos quedan reservados para administracion del sistema.

## Eliminacion

Eliminar un usuario temporal significa anular el acceso, no borrar la identidad:

- `usuario.usuestado = false`
- `usuario_temporal.utestado = 'ELIMINADO'`
- `usuario_temporal.utfecha_eliminacion = CURRENT_TIMESTAMP`

Esto conserva trazabilidad de quien lleno o envio informacion.

## Archivos Relacionados

- `database/scripts/05_seguridad.sql`
- `database/scripts/12_usuario_temporal.sql`
- `database/scripts/sgeap.sql`
- `app/models/UserModel.php`
- `app/models/TemporaryUserModel.php`
