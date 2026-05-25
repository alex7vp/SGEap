# SGEap

Base inicial para un sistema web institucional en PHP nativo con arquitectura MVC simple y PostgreSQL.

## Estructura

```text
proyecto/
├── app/
│   ├── controllers/
│   ├── models/
│   ├── views/
│   └── core/
├── config/
├── public/
│   └── assets/
├── database/
│   ├── scripts/
│   └── seeds/
├── storage/
│   ├── logs/
│   └── temp/
├── composer.json
├── .gitignore
├── .env.example
└── README.md
```

## Requisitos

- PHP 8.0 o superior
- PostgreSQL
- Extensión PDO_PGSQL habilitada

## Inicio rápido

1. Copiar `.env.example` a `.env`
2. Ajustar credenciales de PostgreSQL
3. Ejecutar `composer dump-autoload`
4. Configurar Apache para apuntar a `public/` o abrir `http://localhost/SGEap/public`

## Rutas iniciales

- `GET /` redirige a `/login`
- `GET /login` muestra el formulario
- `POST /login` procesa el formulario de ejemplo

## Base de datos

Los scripts SQL y semillas pueden ubicarse en:

- `database/scripts/`
- `database/seeds/`

Orden sugerido de ejecucion de scripts modulares en una base nueva:

- `database/scripts/01_catalogos.sql`
- `database/scripts/02_academico.sql`
- `database/scripts/03_personas.sql`
- `database/scripts/04_matriculacion.sql`
- `database/scripts/05_seguridad.sql`
- `database/scripts/06_triggers_reglas_negocio.sql`
- `database/scripts/07_asistencia.sql`
- `database/scripts/17_novedades.sql`
- `database/scripts/18_calificaciones.sql`
- `database/scripts/19_calificaciones_promocion.sql`
- `database/scripts/20_calificaciones_soporte.sql`
- `database/scripts/21_calificaciones_grupos_materia.sql`
- `database/scripts/23_calificaciones_habilitacion_registro.sql`
- `database/scripts/24_gestion_contable.sql`
- `database/seeds/01_periodos_base.sql`
- `database/seeds/02_catalogos_base.sql`
- `database/seeds/03_cursos_base.sql`
- `database/seeds/04_seguridad_admin_inicial.sql`
- `database/seeds/05_asistencia_academica_base.sql`
- `database/seeds/06_calificaciones_plantillas_base.sql`
- `database/seeds/07_novedades_base.sql`
- `database/seeds/08_permisos_funcionales_base.sql`
- `database/seeds/09_gestion_contable_base.sql`

Tambien se puede usar el consolidado:

- `database/scripts/sgeap.sql`

Ver tambien `database/scripts/README_instalacion_limpia.md`.

## Documentacion funcional

- `docs/regla-persona-y-roles.md`: regla de negocio de persona, diagrama y validacion de restricciones.
- `docs/gestion_contable.md`: decisiones funcionales para el modulo de Gestion Contable, obligaciones, rubros adicionales, comprobantes y reportes.
