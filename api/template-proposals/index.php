<?php

require_once dirname(__FILE__) . '/../../lib/bootstrap.php';
require_once dirname(__FILE__) . '/../../lib/template_proposal_flow.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        chat_d_api_response(405, array('ok' => false, 'error' => 'method_not_allowed'));
    }

    chat_d_api_assert_key();

    $payload = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        chat_d_api_response(400, array('ok' => false, 'error' => 'invalid_json'));
    }

    $projectId = isset($payload['project_id']) ? trim((string) $payload['project_id']) : '';
    if ($projectId === '' && !empty($payload['intake_id'])) {
        $projectId = chat_d_project_id_for_intake((int) $payload['intake_id']);
    }

    if ($projectId === '') {
        chat_d_api_response(422, array('ok' => false, 'error' => 'project_id_required'));
    }

    $proposals = isset($payload['proposals']) && is_array($payload['proposals']) ? $payload['proposals'] : array();

    $expiresAt = isset($payload['expires_at']) ? trim((string) $payload['expires_at']) : '';
    if ($expiresAt === '') {
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
    }

    $workerRunId = isset($payload['worker_run_id']) ? trim((string) $payload['worker_run_id']) : '';
    $proposalBatchId = isset($payload['proposal_batch_id']) ? trim((string) $payload['proposal_batch_id']) : '';
    $allowRegenerate = !empty($payload['regenerate']) || !empty($payload['force_regenerate']);
    $saved = chat_d_sync_template_proposals($projectId, $proposals, $expiresAt, $proposalBatchId, $allowRegenerate);

    chat_d_api_response(200, array(
        'ok' => true,
        'project_id' => $projectId,
        'saved_proposal_ids' => $saved,
        'template_status' => 'template_ready',
        'expires_at' => $expiresAt,
    ));
} catch (Exception $error) {
    error_log('[template-proposals] ' . $error->getMessage());
    $errorCode = 'api_writeback_failed';
    $statusCode = 500;
    if (strpos($error->getMessage(), 'template_proposals_invalid:') === 0) {
        $errorCode = 'template_proposals_invalid';
        $statusCode = 422;
    } elseif (strpos($error->getMessage(), 'template_batch_locked:') === 0) {
        $errorCode = 'template_batch_locked';
        $statusCode = 409;
    }
    if (isset($projectId) && $projectId !== '') {
        $workerRunId = isset($workerRunId) ? $workerRunId : '';
        chat_d_mark_template_failed($projectId, $workerRunId, $errorCode, $error->getMessage());
    }
    chat_d_api_response($statusCode, array('ok' => false, 'error' => $error->getMessage()));
}

function chat_d_api_assert_key()
{
    $expected = chat_d_api_expected_key();
    if ($expected === '') {
        chat_d_api_response(500, array('ok' => false, 'error' => 'api_key_not_configured'));
    }

    $provided = isset($_SERVER['HTTP_X_ADMISSION_API_KEY']) ? (string) $_SERVER['HTTP_X_ADMISSION_API_KEY'] : '';
    $authorization = isset($_SERVER['HTTP_AUTHORIZATION']) ? (string) $_SERVER['HTTP_AUTHORIZATION'] : '';
    if ($provided === '' && preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
        $provided = trim($matches[1]);
    }

    if (!function_exists('hash_equals')) {
        if ($provided !== $expected) {
            chat_d_api_response(401, array('ok' => false, 'error' => 'unauthorized'));
        }
        return;
    }

    if (!hash_equals($expected, $provided)) {
        chat_d_api_response(401, array('ok' => false, 'error' => 'unauthorized'));
    }
}

function chat_d_api_expected_key()
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

function chat_d_api_response($status, $body)
{
    http_response_code((int) $status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
