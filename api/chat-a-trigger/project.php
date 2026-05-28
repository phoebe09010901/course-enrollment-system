<?php

require_once dirname(__FILE__) . '/../../lib/bootstrap.php';
require_once dirname(__FILE__) . '/../../lib/template_proposal_flow.php';

header('Content-Type: application/json; charset=utf-8');

try {
    chat_a_project_api_assert_key();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $projectId = isset($_GET['project_id']) ? trim((string) $_GET['project_id']) : '';
        if ($projectId === '') {
            chat_a_project_api_response(422, array('ok' => false, 'error' => 'project_id_required'));
        }

        $project = chat_d_project_by_id($projectId);
        if (!$project) {
            chat_a_project_api_response(404, array('ok' => false, 'error' => 'project_not_found'));
        }

        chat_a_project_api_response(200, chat_a_project_api_status_body($project));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $payload = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            chat_a_project_api_response(400, array('ok' => false, 'error' => 'invalid_json'));
        }

        $projectId = isset($payload['project_id']) ? trim((string) $payload['project_id']) : '';
        $action = isset($payload['action']) ? trim((string) $payload['action']) : '';
        if ($projectId === '') {
            chat_a_project_api_response(422, array('ok' => false, 'error' => 'project_id_required'));
        }

        $project = chat_d_project_by_id($projectId);
        if (!$project) {
            chat_a_project_api_response(404, array('ok' => false, 'error' => 'project_not_found'));
        }

        if ($action !== 'release_processing') {
            chat_a_project_api_response(422, array('ok' => false, 'error' => 'unsupported_action'));
        }

        $status = isset($project['template_status']) ? (string) $project['template_status'] : '';
        if ($status !== 'processing_template' && $status !== 'chat_a_triggered') {
            chat_a_project_api_response(409, array(
                'ok' => false,
                'error' => 'project_not_processing',
                'project' => chat_a_project_api_status_body($project),
            ));
        }

        $workerRunId = isset($payload['worker_run_id']) ? trim((string) $payload['worker_run_id']) : '';
        $errorCode = isset($payload['error_code']) ? trim((string) $payload['error_code']) : 'worker_callback_invalid_payload';
        $errorMessage = isset($payload['error_message']) ? trim((string) $payload['error_message']) : 'Processing lock released by Chat D project ops.';
        chat_d_mark_template_failed($projectId, $workerRunId, $errorCode, $errorMessage);

        $project = chat_d_project_by_id($projectId);
        chat_a_project_api_response(200, array(
            'ok' => true,
            'action' => 'release_processing',
            'project' => chat_a_project_api_status_body($project),
        ));
    }

    chat_a_project_api_response(405, array('ok' => false, 'error' => 'method_not_allowed'));
} catch (Exception $error) {
    error_log('[chat-a-project] ' . $error->getMessage());
    chat_a_project_api_response(500, array('ok' => false, 'error' => $error->getMessage()));
}

function chat_a_project_api_status_body($project)
{
    $startedAt = isset($project['template_processing_started_at']) ? trim((string) $project['template_processing_started_at']) : '';
    $lockExpiresAt = '';
    $lockActive = false;
    $status = isset($project['template_status']) ? (string) $project['template_status'] : '';
    if (($status === 'processing_template' || $status === 'chat_a_triggered') && $startedAt !== '') {
        $startedTime = strtotime($startedAt);
        if ($startedTime !== false) {
            $lockExpiresAt = date('Y-m-d H:i:s', $startedTime + 3600);
            $lockActive = $startedTime >= strtotime('-60 minutes');
        }
    }

    return array(
        'ok' => true,
        'project' => array(
            'project_id' => isset($project['project_id']) ? $project['project_id'] : '',
            'project_status' => isset($project['project_status']) ? $project['project_status'] : '',
            'template_status' => $status,
            'needs_template_proposal' => isset($project['needs_template_proposal']) ? (int) $project['needs_template_proposal'] : null,
            'proposal_batch_id' => isset($project['proposal_batch_id']) ? $project['proposal_batch_id'] : '',
            'selected_proposal_id' => isset($project['selected_proposal_id']) ? $project['selected_proposal_id'] : '',
            'selected_template_id' => isset($project['selected_template_id']) ? $project['selected_template_id'] : '',
            'selected_secondary_template_id' => isset($project['selected_secondary_template_id']) ? $project['selected_secondary_template_id'] : '',
            'selected_canva_url' => isset($project['selected_canva_url']) ? $project['selected_canva_url'] : '',
            'template_selected_at' => isset($project['template_selected_at']) ? $project['template_selected_at'] : '',
            'template_processing_started_at' => $startedAt,
            'template_processing_by' => isset($project['template_processing_by']) ? $project['template_processing_by'] : '',
            'worker_run_id' => isset($project['worker_run_id']) ? $project['worker_run_id'] : '',
            'lock_expires_at' => $lockExpiresAt,
            'lock_active' => $lockActive,
            'is_claimable' => chat_d_project_is_claimable($project),
            'template_error_code' => isset($project['template_error_code']) ? $project['template_error_code'] : '',
            'template_error_message' => isset($project['template_error_message']) ? $project['template_error_message'] : '',
            'updated_at' => isset($project['updated_at']) ? $project['updated_at'] : '',
        ),
    );
}

function chat_a_project_api_assert_key()
{
    $expected = chat_a_project_api_expected_key();
    if ($expected === '') {
        chat_a_project_api_response(500, array('ok' => false, 'error' => 'api_key_not_configured'));
    }

    $provided = isset($_SERVER['HTTP_X_ADMISSION_API_KEY']) ? (string) $_SERVER['HTTP_X_ADMISSION_API_KEY'] : '';
    $authorization = isset($_SERVER['HTTP_AUTHORIZATION']) ? (string) $_SERVER['HTTP_AUTHORIZATION'] : '';
    if ($provided === '' && preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
        $provided = trim($matches[1]);
    }

    if (!function_exists('hash_equals')) {
        if ($provided !== $expected) {
            chat_a_project_api_response(401, array('ok' => false, 'error' => 'unauthorized'));
        }
        return;
    }

    if (!hash_equals($expected, $provided)) {
        chat_a_project_api_response(401, array('ok' => false, 'error' => 'unauthorized'));
    }
}

function chat_a_project_api_expected_key()
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

function chat_a_project_api_response($status, $body)
{
    http_response_code((int) $status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
