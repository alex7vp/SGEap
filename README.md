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
