<?php

declare(strict_types=1);

function loadEnvironment(string $envFile): void
{
    if (!file_exists($envFile)) {
        return;
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

function env(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
}

function config(string $file): array
{
    static $configs = [];

    if (!isset($configs[$file])) {
        $path = BASE_PATH . '/config/' . $file . '.php';
        $configs[$file] = file_exists($path) ? require $path : [];
    }

    return $configs[$file];
}

function baseUrl(string $path = ''): string
{
    $baseUrl = rtrim((string) env('APP_URL', ''), '/');
    $path = ltrim($path, '/');

    return $path === '' ? $baseUrl : $baseUrl . '/' . $path;
}

function asset(string $path): string
{
    return baseUrl('assets/' . ltrim($path, '/'));
}

function currentPath(): string
{
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($requestUri, PHP_URL_PATH) ?: '/';
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

    if ($basePath !== '' && $basePath !== '/' && str_starts_with($path, $basePath)) {
        $path = substr($path, strlen($basePath));
    }

    return $path === '' ? '/' : $path;
}

function redirect(string $path): void
{
    header('Location: ' . baseUrl($path));
    exit;
}

function sessionFlash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }

    $message = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);

    return $message;
}

function setCurrentAcademicPeriod(?array $period): void
{
    if ($period === null) {
        unset($_SESSION['current_period']);
        return;
    }

    $_SESSION['current_period'] = [
        'pleid' => (int) ($period['pleid'] ?? 0),
        'pledescripcion' => (string) ($period['pledescripcion'] ?? ''),
        'pleactivo' => !empty($period['pleactivo']),
    ];
}

function currentAcademicPeriod(): ?array
{
    if (!empty($_SESSION['current_period'])) {
        return $_SESSION['current_period'];
    }

    try {
        $model = new \App\Models\PeriodModel();
        $period = $model->active();

        if ($period !== false) {
            setCurrentAcademicPeriod($period);
            return $_SESSION['current_period'];
        }
    } catch (\Throwable) {
        return null;
    }

    return null;
}

function availableAcademicPeriods(): array
{
    try {
        $model = new \App\Models\PeriodModel();
        return $model->allOrdered();
    } catch (\Throwable) {
        return [];
    }
}
