<?php

require_once dirname(__FILE__) . '/../../lib/bootstrap.php';
require_once dirname(__FILE__) . '/../../lib/template_proposal_flow.php';

header('Content-Type: application/json; charset=utf-8');

try {
    chat_d_action_links_api_assert_key();

    $projectId = '';
    $ttlHours = 24;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $payload = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            chat_d_action_links_api_response(400, array('ok' => false, 'error' => 'invalid_json'));
        }
        $projectId = isset($payload['project_id']) ? trim((string) $payload['project_id']) : '';
        $ttlHours = isset($payload['ttl_hours']) ? (int) $payload['ttl_hours'] : 24;
    } else {
        $projectId = isset($_GET['project_id']) ? trim((string) $_GET['project_id']) : '';
        $ttlHours = isset($_GET['ttl_hours']) ? (int) $_GET['ttl_hours'] : 24;
    }

    if ($projectId === '') {
        chat_d_action_links_api_response(422, array('ok' => false, 'error' => 'project_id_required'));
    }

    $project = chat_d_project_by_id($projectId);
    if (!$project) {
        chat_d_action_links_api_response(404, array('ok' => false, 'error' => 'project_not_found'));
    }

    $links = chat_d_admin_action_create_links($projectId, $ttlHours);
    $responseLinks = array();
    foreach ($links as $action => $link) {
        $responseLinks[$action] = array(
            'label' => $link['label'],
            'url' => $link['url'],
            'expires_at' => $link['expires_at'],
        );
    }

    chat_d_action_links_api_response(200, array(
        'ok' => true,
        'project_id' => $projectId,
        'template_status' => isset($project['template_status']) ? $project['template_status'] : '',
        'template_status_label' => chat_d_template_status_label(isset($project['template_status']) ? $project['template_status'] : ''),
        'ttl_hours' => $ttlHours <= 0 ? 24 : $ttlHours,
        'links' => $responseLinks,
    ));
} catch (Exception $error) {
    error_log('[chat-d-action-links] ' . $error->getMessage());
    chat_d_action_links_api_response(500, array('ok' => false, 'error' => $error->getMessage()));
}

function chat_d_action_links_api_assert_key()
{
    $expected = chat_d_action_links_api_expected_key();
    if ($expected === '') {
        chat_d_action_links_api_response(500, array('ok' => false, 'error' => 'api_key_not_configured'));
    }

    $provided = isset($_SERVER['HTTP_X_ADMISSION_API_KEY']) ? (string) $_SERVER['HTTP_X_ADMISSION_API_KEY'] : '';
    $authorization = isset($_SERVER['HTTP_AUTHORIZATION']) ? (string) $_SERVER['HTTP_AUTHORIZATION'] : '';
    if ($provided === '' && preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
        $provided = trim($matches[1]);
    }

    if (!function_exists('hash_equals')) {
        if ($provided !== $expected) {
            chat_d_action_links_api_response(401, array('ok' => false, 'error' => 'unauthorized'));
        }
        return;
    }

    if (!hash_equals($expected, $provided)) {
        chat_d_action_links_api_response(401, array('ok' => false, 'error' => 'unauthorized'));
    }
}

function chat_d_action_links_api_expected_key()
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

function chat_d_action_links_api_response($status, $body)
{
    http_response_code((int) $status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
