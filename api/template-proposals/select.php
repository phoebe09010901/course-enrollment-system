<?php

require_once dirname(__FILE__) . '/../../lib/bootstrap.php';
require_once dirname(__FILE__) . '/../../lib/template_proposal_flow.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        chat_d_select_api_response(405, array('ok' => false, 'error' => 'method_not_allowed'));
    }

    chat_d_select_api_assert_key();

    $payload = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        chat_d_select_api_response(400, array('ok' => false, 'error' => 'invalid_json'));
    }

    $projectId = isset($payload['project_id']) ? trim((string) $payload['project_id']) : '';
    $proposalId = isset($payload['proposal_id']) ? trim((string) $payload['proposal_id']) : '';
    if ($proposalId === '' && isset($payload['proposal_code'])) {
        $proposalId = trim((string) $payload['proposal_code']);
    }

    if ($projectId === '' || $proposalId === '') {
        chat_d_select_api_response(422, array('ok' => false, 'error' => 'project_id_and_proposal_required'));
    }

    $proposal = chat_d_select_template_proposal($projectId, $proposalId);

    chat_d_select_api_response(200, array(
        'ok' => true,
        'project_id' => $projectId,
        'selected_proposal_id' => $proposal['proposal_id'],
        'selected_template_id' => $proposal['primary_template_id'],
        'selected_secondary_template_id' => $proposal['secondary_template_id'],
        'selected_canva_direction' => $proposal['visual_direction'],
        'selected_canva_url' => $proposal['canva_url'],
        'template_status' => 'template_ready',
    ));
} catch (Exception $error) {
    error_log('[template-proposals-select] ' . $error->getMessage());
    chat_d_select_api_response(500, array('ok' => false, 'error' => $error->getMessage()));
}

function chat_d_select_api_assert_key()
{
    $expected = chat_d_select_api_expected_key();
    if ($expected === '') {
        chat_d_select_api_response(500, array('ok' => false, 'error' => 'api_key_not_configured'));
    }

    $provided = isset($_SERVER['HTTP_X_ADMISSION_API_KEY']) ? (string) $_SERVER['HTTP_X_ADMISSION_API_KEY'] : '';
    $authorization = isset($_SERVER['HTTP_AUTHORIZATION']) ? (string) $_SERVER['HTTP_AUTHORIZATION'] : '';
    if ($provided === '' && preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
        $provided = trim($matches[1]);
    }

    if (!function_exists('hash_equals')) {
        if ($provided !== $expected) {
            chat_d_select_api_response(401, array('ok' => false, 'error' => 'unauthorized'));
        }
        return;
    }

    if (!hash_equals($expected, $provided)) {
        chat_d_select_api_response(401, array('ok' => false, 'error' => 'unauthorized'));
    }
}

function chat_d_select_api_expected_key()
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

function chat_d_select_api_response($status, $body)
{
    http_response_code((int) $status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
