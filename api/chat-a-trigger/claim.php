<?php

require_once dirname(__FILE__) . '/../../lib/bootstrap.php';
require_once dirname(__FILE__) . '/../../lib/chat_a_trigger.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        chat_a_claim_api_response(405, array('ok' => false, 'error' => 'method_not_allowed'));
    }

    chat_a_claim_api_assert_key();

    $payload = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        $payload = array();
    }

    $limit = isset($payload['limit']) ? (int) $payload['limit'] : 3;
    $workerRunId = isset($payload['worker_run_id']) ? trim((string) $payload['worker_run_id']) : '';
    if ($workerRunId === '') {
        $workerRunId = chat_d_generate_worker_run_id();
    }
    $workerName = isset($payload['worker_name']) ? trim((string) $payload['worker_name']) : 'chat-a-canva-cron';

    $claimed = chat_d_claim_template_projects($limit, $workerRunId, $workerName);
    $projects = array();
    foreach ($claimed as $project) {
        $projects[] = chat_a_trigger_project_payload($project);
    }

    chat_a_claim_api_response(200, array(
        'ok' => true,
        'worker_run_id' => $workerRunId,
        'claimed_count' => count($projects),
        'projects' => $projects,
    ));
} catch (Exception $error) {
    error_log('[chat-a-claim] ' . $error->getMessage());
    chat_a_claim_api_response(500, array('ok' => false, 'error' => $error->getMessage()));
}

function chat_a_claim_api_assert_key()
{
    $expected = chat_a_claim_api_expected_key();
    if ($expected === '') {
        chat_a_claim_api_response(500, array('ok' => false, 'error' => 'api_key_not_configured'));
    }

    $provided = isset($_SERVER['HTTP_X_ADMISSION_API_KEY']) ? (string) $_SERVER['HTTP_X_ADMISSION_API_KEY'] : '';
    $authorization = isset($_SERVER['HTTP_AUTHORIZATION']) ? (string) $_SERVER['HTTP_AUTHORIZATION'] : '';
    if ($provided === '' && preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
        $provided = trim($matches[1]);
    }

    if (!function_exists('hash_equals')) {
        if ($provided !== $expected) {
            chat_a_claim_api_response(401, array('ok' => false, 'error' => 'unauthorized'));
        }
        return;
    }

    if (!hash_equals($expected, $provided)) {
        chat_a_claim_api_response(401, array('ok' => false, 'error' => 'unauthorized'));
    }
}

function chat_a_claim_api_expected_key()
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

function chat_a_claim_api_response($status, $body)
{
    http_response_code((int) $status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
