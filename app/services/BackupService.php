<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use RuntimeException;
use ZipArchive;

class BackupService
{
    private string $backupDirectory;

    public function __construct()
    {
        $this->backupDirectory = BASE_PATH . '/storage/backups';
    }

    public function all(): array
    {
        $this->ensureBackupDirectory();
        $files = glob($this->backupDirectory . '/*.zip') ?: [];
        $backups = [];

        foreach ($files as $path) {
            if (!is_file($path)) {
                continue;
            }

            $backups[] = [
                'name' => basename($path),
                'path' => $path,
                'size' => filesize($path) ?: 0,
                'created_at' => filemtime($path) ?: 0,
            ];
        }

        usort($backups, static fn (array $a, array $b): int => (int) $b['created_at'] <=> (int) $a['created_at']);

        return $backups;
    }

    public function create(int $userId): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('La extension ZipArchive de PHP no esta habilitada.');
        }

        $this->ensureBackupDirectory();

        $createdAt = date('Ymd-His');
        $baseName = 'sgeap-backup-' . $createdAt;
        $workDirectory = BASE_PATH . '/storage/temp/backups/' . $baseName;
        $sqlPath = $workDirectory . '/database.sql';
        $metadataPath = $workDirectory . '/metadata.json';
        $zipPath = $this->backupDirectory . '/' . $baseName . '.zip';

        if (!is_dir($workDirectory) && !mkdir($workDirectory, 0775, true) && !is_dir($workDirectory)) {
            throw new RuntimeException('No se pudo preparar el directorio temporal del respaldo.');
        }

        $dumpMethod = 'pg_dump';

        try {
            $this->dumpDatabase($sqlPath);
        } catch (\Throwable $exception) {
            $dumpMethod = 'pdo-data';
            $this->dumpDatabaseWithPdo($sqlPath, $exception->getMessage());
        }

        file_put_contents($metadataPath, json_encode([
            'generated_at' => date('c'),
            'generated_by_user_id' => $userId,
            'app' => config('app')['name'] ?? 'SGEap',
            'database_dump_method' => $dumpMethod,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->removeDirectory($workDirectory);
            throw new RuntimeException('No se pudo crear el archivo ZIP del respaldo.');
        }

        $zip->addFile($sqlPath, 'database.sql');
        $zip->addFile($metadataPath, 'metadata.json');
        $this->addDirectoryToZip($zip, BASE_PATH . '/public/assets/docs', 'assets/docs');
        $this->addDirectoryToZip($zip, BASE_PATH . '/public/assets/photos', 'assets/photos');
        $this->addDirectoryToZip($zip, BASE_PATH . '/public/assets/images', 'assets/images');
        $zip->close();

        $this->removeDirectory($workDirectory);

        return [
            'name' => basename($zipPath),
            'path' => $zipPath,
            'size' => filesize($zipPath) ?: 0,
            'created_at' => filemtime($zipPath) ?: time(),
        ];
    }

    public function resolve(string $fileName): string
    {
        $safeName = $this->safeFileName($fileName);
        $path = $this->backupDirectory . '/' . $safeName;
        $realDirectory = realpath($this->backupDirectory);
        $realPath = is_file($path) ? realpath($path) : false;

        if ($realDirectory === false || $realPath === false || !str_starts_with($realPath, $realDirectory)) {
            throw new RuntimeException('El respaldo solicitado no existe.');
        }

        return $realPath;
    }

    public function delete(string $fileName): void
    {
        $path = $this->resolve($fileName);

        if (!unlink($path)) {
            throw new RuntimeException('No se pudo eliminar el respaldo.');
        }
    }

    private function dumpDatabase(string $targetPath): void
    {
        $connection = config('database')['connections'][config('database')['default'] ?? 'pgsql'] ?? null;

        if (!is_array($connection)) {
            throw new RuntimeException('No existe configuracion de base de datos.');
        }

        $binary = $this->pgDumpBinary();

        if ($binary === null) {
            throw new RuntimeException('No se encontro pg_dump en el servidor.');
        }

        $command = implode(' ', [
            escapeshellarg($binary),
            '--host=' . escapeshellarg((string) $connection['host']),
            '--port=' . escapeshellarg((string) $connection['port']),
            '--username=' . escapeshellarg((string) $connection['username']),
            '--dbname=' . escapeshellarg((string) $connection['database']),
            '--schema=' . escapeshellarg((string) $connection['schema']),
            '--format=plain',
            '--no-owner',
            '--no-privileges',
            '--encoding=UTF8',
            '--file=' . escapeshellarg($targetPath),
        ]);

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $env = array_merge($_SERVER, $_ENV);
        $env['PGPASSWORD'] = (string) ($connection['password'] ?? '');

        if (!isset($env['PATH']) && getenv('PATH') !== false) {
            $env['PATH'] = (string) getenv('PATH');
        }
        $process = proc_open($command, $descriptorSpec, $pipes, BASE_PATH, $env);

        if (!is_resource($process)) {
            throw new RuntimeException('No se pudo ejecutar pg_dump.');
        }

        fclose($pipes[0]);
        stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $errorOutput = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0 || !is_file($targetPath) || filesize($targetPath) === 0) {
            throw new RuntimeException(trim($errorOutput) !== '' ? trim($errorOutput) : 'pg_dump no genero un respaldo valido.');
        }
    }

    private function dumpDatabaseWithPdo(string $targetPath, string $reason): void
    {
        $db = Database::connection();
        $connection = config('database')['connections'][config('database')['default'] ?? 'pgsql'] ?? [];
        $schema = (string) ($connection['schema'] ?? 'public');
        $handle = fopen($targetPath, 'wb');

        if ($handle === false) {
            throw new RuntimeException('No se pudo crear el archivo SQL del respaldo.');
        }

        fwrite($handle, "-- Respaldo de datos generado por SGEap\n");
        fwrite($handle, "-- pg_dump no estuvo disponible: " . str_replace(["\r", "\n"], ' ', $reason) . "\n");
        fwrite($handle, "SET search_path TO " . $this->quoteIdentifier($schema) . ";\n\n");

        $tablesStatement = $db->prepare(
            "SELECT table_name
             FROM information_schema.tables
             WHERE table_schema = :schema
               AND table_type = 'BASE TABLE'
             ORDER BY table_name"
        );
        $tablesStatement->execute(['schema' => $schema]);

        foreach ($tablesStatement->fetchAll(PDO::FETCH_COLUMN) as $table) {
            $table = (string) $table;
            $columnsStatement = $db->prepare(
                "SELECT column_name
                 FROM information_schema.columns
                 WHERE table_schema = :schema
                   AND table_name = :table
                 ORDER BY ordinal_position"
            );
            $columnsStatement->execute(['schema' => $schema, 'table' => $table]);
            $columns = array_map('strval', $columnsStatement->fetchAll(PDO::FETCH_COLUMN));

            if ($columns === []) {
                continue;
            }

            fwrite($handle, "TRUNCATE TABLE " . $this->quoteIdentifier($table) . " RESTART IDENTITY CASCADE;\n");
            $rows = $db->query('SELECT * FROM ' . $this->quoteIdentifier($schema) . '.' . $this->quoteIdentifier($table));
            $columnSql = implode(', ', array_map(fn (string $column): string => $this->quoteIdentifier($column), $columns));

            while (($row = $rows->fetch(PDO::FETCH_ASSOC)) !== false) {
                $values = [];

                foreach ($columns as $column) {
                    $values[] = $this->quoteValue($row[$column] ?? null, $db);
                }

                fwrite($handle, 'INSERT INTO ' . $this->quoteIdentifier($table) . ' (' . $columnSql . ') VALUES (' . implode(', ', $values) . ");\n");
            }

            foreach ($columns as $column) {
                $sequenceStatement = $db->query(
                    'SELECT pg_get_serial_sequence(' .
                    $db->quote($schema . '.' . $table) .
                    ', ' .
                    $db->quote($column) .
                    ')'
                );
                $sequence = $sequenceStatement !== false ? $sequenceStatement->fetchColumn() : false;

                if (is_string($sequence) && $sequence !== '') {
                    fwrite(
                        $handle,
                        'SELECT setval(' .
                        $db->quote($sequence) .
                        ', COALESCE((SELECT MAX(' . $this->quoteIdentifier($column) . ') FROM ' . $this->quoteIdentifier($table) . '), 1), ' .
                        '(SELECT MAX(' . $this->quoteIdentifier($column) . ') FROM ' . $this->quoteIdentifier($table) . ") IS NOT NULL);\n"
                    );
                }
            }

            fwrite($handle, "\n");
        }

        fclose($handle);
    }

    private function pgDumpBinary(): ?string
    {
        $candidates = ['pg_dump'];

        foreach (glob('C:/Program Files/PostgreSQL/*/bin/pg_dump.exe') ?: [] as $candidate) {
            $candidates[] = str_replace('\\', '/', $candidate);
        }

        foreach ($candidates as $candidate) {
            $command = escapeshellarg($candidate) . ' --version';
            exec($command, $output, $exitCode);

            if ($exitCode === 0) {
                return $candidate;
            }
        }

        return null;
    }

    private function addDirectoryToZip(ZipArchive $zip, string $directory, string $prefix): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $path = (string) $file->getPathname();
            $relativePath = str_replace('\\', '/', substr($path, strlen($directory) + 1));
            $zip->addFile($path, $prefix . '/' . $relativePath);
        }
    }

    private function safeFileName(string $fileName): string
    {
        $safeName = basename($fileName);

        if (!preg_match('/^sgeap-backup-\d{8}-\d{6}\.zip$/', $safeName)) {
            throw new RuntimeException('El nombre del respaldo no es valido.');
        }

        return $safeName;
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    private function quoteValue(mixed $value, PDO $db): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        return $db->quote((string) $value);
    }

    private function ensureBackupDirectory(): void
    {
        if (!is_dir($this->backupDirectory) && !mkdir($this->backupDirectory, 0775, true) && !is_dir($this->backupDirectory)) {
            throw new RuntimeException('No se pudo preparar el directorio de respaldos.');
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            $file->isDir() ? rmdir((string) $file->getPathname()) : unlink((string) $file->getPathname());
        }

        rmdir($directory);
    }
}
