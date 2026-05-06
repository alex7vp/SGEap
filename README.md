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
- `database/seeds/01_periodos_base.sql`
- `database/seeds/02_catalogos_base.sql`
- `database/seeds/03_cursos_base.sql`
- `database/seeds/04_seguridad_admin_inicial.sql`

Tambien se puede usar el consolidado:

- `database/scripts/sgeap.sql`

Ver tambien `database/scripts/README_instalacion_limpia.md`.

## Documentacion funcional

- `docs/regla-persona-y-roles.md`: regla de negocio de persona, diagrama y validacion de restricciones.
