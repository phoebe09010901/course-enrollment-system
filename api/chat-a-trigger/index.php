<?php

require_once dirname(__FILE__) . '/../../lib/bootstrap.php';
require_once dirname(__FILE__) . '/../../lib/chat_a_trigger.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        chat_a_trigger_api_response(405, array('ok' => false, 'error' => 'method_not_allowed'));
    }

    chat_a_trigger_api_assert_key();

    $payload = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        chat_a_trigger_api_response(400, array('ok' => false, 'error' => 'invalid_json'));
    }

    $projectId = isset($payload['project_id']) ? trim((string) $payload['project_id']) : '';
    if ($projectId === '') {
        chat_a_trigger_api_response(422, array('ok' => false, 'error' => 'project_id_required'));
    }

    $result = chat_a_trigger_for_project($projectId);
    $status = !empty($result['ok']) || (isset($result['status']) && $result['status'] === 'queued') ? 200 : 502;
    chat_a_trigger_api_response($status, array_merge(array('project_id' => $projectId), $result));
} catch (Exception $error) {
    error_log('[chat-a-trigger] ' . $error->getMessage());
    chat_a_trigger_api_response(500, array('ok' => false, 'error' => $error->getMessage()));
}

function chat_a_trigger_api_assert_key()
{
    $expected = chat_a_trigger_api_expected_key();
    if ($expected === '') {
        chat_a_trigger_api_response(500, array('ok' => false, 'error' => 'api_key_not_configured'));
    }

    $provided = isset($_SERVER['HTTP_X_ADMISSION_API_KEY']) ? (string) $_SERVER['HTTP_X_ADMISSION_API_KEY'] : '';
    $authorization = isset($_SERVER['HTTP_AUTHORIZATION']) ? (string) $_SERVER['HTTP_AUTHORIZATION'] : '';
    if ($provided === '' && preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
        $provided = trim($matches[1]);
    }

    if (!function_exists('hash_equals')) {
        if ($provided !== $expected) {
            chat_a_trigger_api_response(401, array('ok' => false, 'error' => 'unauthorized'));
        }
        return;
    }

    if (!hash_equals($expected, $provided)) {
        chat_a_trigger_api_response(401, array('ok' => false, 'error' => 'unauthorized'));
    }
}

function chat_a_trigger_api_expected_key()
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

function chat_a_trigger_api_response($status, $body)
{
    http_response_code((int) $status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
