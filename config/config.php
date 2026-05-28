<?php

function admission_config()
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
        'app_name' => getenv('APP_NAME') ?: 'AI 招生頁產生系統',
        'app_base_url' => getenv('APP_BASE_URL') ?: '',
        'app_timezone' => getenv('APP_TIMEZONE') ?: 'Asia/Taipei',
        'session_name' => getenv('SESSION_NAME') ?: 'admission_system_session',
        'install_key' => getenv('INSTALL_KEY') ?: '',
        'api_key' => getenv('ADMISSION_API_KEY') ?: '',
        'admin_notification_email' => getenv('ADMIN_NOTIFICATION_EMAIL') ?: 'phy4175@gmail.com',
        'cloudflare_r2_account_id' => getenv('CLOUDFLARE_R2_ACCOUNT_ID') ?: '',
        'cloudflare_r2_access_key_id' => getenv('CLOUDFLARE_R2_ACCESS_KEY_ID') ?: '',
        'cloudflare_r2_secret_access_key' => getenv('CLOUDFLARE_R2_SECRET_ACCESS_KEY') ?: '',
        'cloudflare_r2_bucket' => getenv('CLOUDFLARE_R2_BUCKET') ?: '',
        'cloudflare_r2_public_base_url' => getenv('CLOUDFLARE_R2_PUBLIC_BASE_URL') ?: '',
        'cloudflare_turnstile_site_key' => getenv('CLOUDFLARE_TURNSTILE_SITE_KEY') ?: '',
        'cloudflare_turnstile_secret_key' => getenv('CLOUDFLARE_TURNSTILE_SECRET_KEY') ?: '',
        'chat_a_trigger_webhook_url' => getenv('CHAT_A_TRIGGER_WEBHOOK_URL') ?: '',
        'chat_a_trigger_secret' => getenv('CHAT_A_TRIGGER_SECRET') ?: '',
        'chat_a_trigger_timeout' => getenv('CHAT_A_TRIGGER_TIMEOUT') ?: '3',
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

function admission_define_config_constant($name, $value)
{
    if (!defined($name)) {
        define($name, $value);
    }
}

$admissionConfig = admission_config();

admission_define_config_constant('APP_TIMEZONE', isset($admissionConfig['app_timezone']) && $admissionConfig['app_timezone'] !== '' ? $admissionConfig['app_timezone'] : 'Asia/Taipei');
admission_define_config_constant('APP_NAME', isset($admissionConfig['app_name']) && $admissionConfig['app_name'] !== '' ? $admissionConfig['app_name'] : 'AI 招生頁產生系統');
admission_define_config_constant('APP_BASE_URL', isset($admissionConfig['app_base_url']) ? $admissionConfig['app_base_url'] : '');
admission_define_config_constant('SESSION_NAME', isset($admissionConfig['session_name']) && $admissionConfig['session_name'] !== '' ? $admissionConfig['session_name'] : 'admission_system_session');
admission_define_config_constant('INSTALL_KEY', isset($admissionConfig['install_key']) ? $admissionConfig['install_key'] : '');
admission_define_config_constant('DB_HOST', isset($admissionConfig['db_host']) ? $admissionConfig['db_host'] : 'localhost');
admission_define_config_constant('DB_NAME', isset($admissionConfig['db_name']) ? $admissionConfig['db_name'] : '');
admission_define_config_constant('DB_USER', isset($admissionConfig['db_user']) ? $admissionConfig['db_user'] : '');
admission_define_config_constant('DB_PASS', isset($admissionConfig['db_pass']) ? $admissionConfig['db_pass'] : '');
admission_define_config_constant('DB_CHARSET', isset($admissionConfig['db_charset']) && $admissionConfig['db_charset'] !== '' ? $admissionConfig['db_charset'] : 'utf8mb4');
admission_define_config_constant('ADMISSION_API_KEY', isset($admissionConfig['api_key']) ? $admissionConfig['api_key'] : '');
admission_define_config_constant('ADMIN_NOTIFICATION_EMAIL', isset($admissionConfig['admin_notification_email']) ? $admissionConfig['admin_notification_email'] : '');
admission_define_config_constant('CLOUDFLARE_R2_ACCOUNT_ID', isset($admissionConfig['cloudflare_r2_account_id']) ? $admissionConfig['cloudflare_r2_account_id'] : '');
admission_define_config_constant('CLOUDFLARE_R2_ACCESS_KEY_ID', isset($admissionConfig['cloudflare_r2_access_key_id']) ? $admissionConfig['cloudflare_r2_access_key_id'] : '');
admission_define_config_constant('CLOUDFLARE_R2_SECRET_ACCESS_KEY', isset($admissionConfig['cloudflare_r2_secret_access_key']) ? $admissionConfig['cloudflare_r2_secret_access_key'] : '');
admission_define_config_constant('CLOUDFLARE_R2_BUCKET', isset($admissionConfig['cloudflare_r2_bucket']) ? $admissionConfig['cloudflare_r2_bucket'] : '');
admission_define_config_constant('CLOUDFLARE_R2_PUBLIC_BASE_URL', isset($admissionConfig['cloudflare_r2_public_base_url']) ? $admissionConfig['cloudflare_r2_public_base_url'] : '');
admission_define_config_constant('CLOUDFLARE_TURNSTILE_SITE_KEY', isset($admissionConfig['cloudflare_turnstile_site_key']) ? $admissionConfig['cloudflare_turnstile_site_key'] : '');
admission_define_config_constant('CLOUDFLARE_TURNSTILE_SECRET_KEY', isset($admissionConfig['cloudflare_turnstile_secret_key']) ? $admissionConfig['cloudflare_turnstile_secret_key'] : '');
admission_define_config_constant('CHAT_A_TRIGGER_WEBHOOK_URL', isset($admissionConfig['chat_a_trigger_webhook_url']) ? $admissionConfig['chat_a_trigger_webhook_url'] : '');
admission_define_config_constant('CHAT_A_TRIGGER_SECRET', isset($admissionConfig['chat_a_trigger_secret']) ? $admissionConfig['chat_a_trigger_secret'] : '');
admission_define_config_constant('CHAT_A_TRIGGER_TIMEOUT', isset($admissionConfig['chat_a_trigger_timeout']) ? $admissionConfig['chat_a_trigger_timeout'] : '3');
