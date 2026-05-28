<?php

require_once dirname(__FILE__) . '/../../lib/bootstrap.php';
require_once dirname(__FILE__) . '/../../lib/template_proposal_flow.php';

header('Content-Type: application/json; charset=utf-8');

$status = 200;
$dbOk = false;
$dbError = '';

try {
    db_one('SELECT 1 AS ok');
    $dbOk = true;
} catch (Exception $error) {
    $dbError = $error->getMessage();
    $status = 503;
}

$providedKey = chat_a_health_provided_key();
$expectedKey = chat_a_health_expected_key();
$authenticated = $expectedKey !== '' && $providedKey !== '' && chat_a_health_equals($expectedKey, $providedKey);

$body = array(
    'ok' => $dbOk,
    'service' => 'admission-system-chat-d',
    'endpoint' => 'chat-a-trigger-health',
    'server_time' => date('Y-m-d H:i:s'),
    'timezone' => date_default_timezone_get(),
    'host' => isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '',
    'php_version' => PHP_VERSION,
    'auth_configured' => $expectedKey !== '',
    'authenticated' => $authenticated,
    'db_ok' => $dbOk,
    'db_error' => $dbError,
    'tables' => array(
        'course_projects' => chat_d_table_exists('course_projects'),
        'template_proposals' => chat_d_table_exists('template_proposals'),
        'notification_logs' => chat_d_table_exists('notification_logs'),
    ),
    'contract' => array(
        'claim_endpoint' => app_url('api/chat-a-trigger/claim.php'),
        'success_endpoint' => app_url('api/template-proposals/'),
        'failure_endpoint' => app_url('api/chat-a-trigger/fail.php'),
        'required_auth_header' => 'X-Admission-Api-Key',
        'worker_name' => 'course-canva-proposal-worker',
        'worker_run_id_prefix' => 'chat-g-',
    ),
);

if (!$dbOk) {
    $body['ok'] = false;
}

http_response_code($status);
echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

function chat_a_health_provided_key()
{
    $provided = isset($_SERVER['HTTP_X_ADMISSION_API_KEY']) ? (string) $_SERVER['HTTP_X_ADMISSION_API_KEY'] : '';
    $authorization = isset($_SERVER['HTTP_AUTHORIZATION']) ? (string) $_SERVER['HTTP_AUTHORIZATION'] : '';
    if ($provided === '' && preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
        $provided = trim($matches[1]);
    }

    return $provided;
}

function chat_a_health_expected_key()
{
    if (defined('ADMISSION_API_KEY')) {
        return trim((string) constant('ADMISSION_API_KEY'));
    }

    $value = getenv('ADMISSION_API_KEY');
    if ($value !== false && trim((string) $value) !== '') {
        return trim((string) $value);
    }

    if (function_exists('admission_config')) {
        $config = admission_config();
        if (isset($config['api_key'])) {
            return trim((string) $config['api_key']);
        }
    }

    return '';
}

function chat_a_health_equals($expected, $provided)
{
    if (function_exists('hash_equals')) {
        return hash_equals($expected, $provided);
    }

    return $expected === $provided;
}
