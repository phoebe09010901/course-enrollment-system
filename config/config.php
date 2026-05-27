<?php

declare(strict_types=1);

function admission_config(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $config = [
        'db_dsn' => getenv('ADMISSION_DB_DSN') ?: null,
        'db_host' => getenv('ADMISSION_DB_HOST') ?: 'localhost',
        'db_name' => getenv('ADMISSION_DB_NAME') ?: '',
        'db_user' => getenv('ADMISSION_DB_USER') ?: '',
        'db_pass' => getenv('ADMISSION_DB_PASS') ?: '',
        'db_charset' => getenv('ADMISSION_DB_CHARSET') ?: 'utf8mb4',
        'api_key' => getenv('ADMISSION_API_KEY') ?: '',
    ];

    $localConfigPath = __DIR__ . '/local.php';
    if (is_file($localConfigPath)) {
        $localConfig = require $localConfigPath;
        if (is_array($localConfig)) {
            $config = array_replace($config, $localConfig);
        }
    }

    return $config;
}
