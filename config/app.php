<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'ALBANY SCHOOL'),
    'env' => env('APP_ENV', 'development'),
    'debug' => filter_var(env('APP_DEBUG', true), FILTER_VALIDATE_BOOL),
    'url' => env('APP_URL', 'http://localhost/SGEap/public'),
    'timezone' => env('APP_TIMEZONE', 'America/Guayaquil'),
];
