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
        'cloudflare_r2_account_id' => getenv('CLOUDFLARE_R2_ACCOUNT_ID') ?: '',
        'cloudflare_r2_access_key_id' => getenv('CLOUDFLARE_R2_ACCESS_KEY_ID') ?: '',
        'cloudflare_r2_secret_access_key' => getenv('CLOUDFLARE_R2_SECRET_ACCESS_KEY') ?: '',
        'cloudflare_r2_bucket' => getenv('CLOUDFLARE_R2_BUCKET') ?: '',
        'cloudflare_r2_public_base_url' => getenv('CLOUDFLARE_R2_PUBLIC_BASE_URL') ?: '',
        'cloudflare_turnstile_site_key' => getenv('CLOUDFLARE_TURNSTILE_SITE_KEY') ?: '',
        'cloudflare_turnstile_secret_key' => getenv('CLOUDFLARE_TURNSTILE_SECRET_KEY') ?: '',
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
