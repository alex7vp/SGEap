<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = config('database');
        $default = $config['default'] ?? 'pgsql';
        $connection = $config['connections'][$default] ?? null;

        if ($connection === null) {
            throw new RuntimeException('No se encontró la configuración de base de datos.');
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;options=--search_path=%s',
            $connection['host'],
            $connection['port'],
            $connection['database'],
            $connection['schema']
        );

        try {
            self::$connection = new PDO(
                $dsn,
                $connection['username'],
                $connection['password'],
                $connection['options'] ?? []
            );
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'Error al conectar con PostgreSQL: ' . $exception->getMessage(),
                (int) $exception->getCode(),
                $exception
            );
        }

        return self::$connection;
    }
}
