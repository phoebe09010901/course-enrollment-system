<?php

require_once dirname(__FILE__) . '/../../lib/bootstrap.php';
require_once dirname(__FILE__) . '/../../lib/chat_a_trigger.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        chat_a_pending_api_response(405, array('ok' => false, 'error' => 'method_not_allowed'));
    }

    chat_a_pending_api_assert_key();

    if (!chat_d_table_exists('course_projects')) {
        chat_a_pending_api_response(200, array('ok' => true, 'projects' => array()));
    }

    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 3;
    if ($limit <= 0 || $limit > 10) {
        $limit = 3;
    }

    $processingCondition = '';
    if (chat_d_column_exists('course_projects', 'template_processing_started_at')) {
        $processingCondition = "OR (
                    p.template_status IN ('processing_template', 'chat_a_triggered')
                    AND p.template_processing_started_at IS NOT NULL
                    AND p.template_processing_started_at < DATE_SUB(NOW(), INTERVAL 60 MINUTE)
                )";
    }

    $rows = db_all(
        "SELECT p.*, c.name AS client_name, c.contact_name, c.email, c.line_id, c.line_id_link
         FROM course_projects p
         LEFT JOIN admission_clients c ON c.id = p.client_id
         WHERE p.needs_template_proposal = 1
           AND (
                p.template_status = 'pending_template'
                OR p.template_status IS NULL
                OR p.template_status IN ('pending_canva_proposals', 'chat_a_trigger_queued')
                " . $processingCondition . "
           )
         ORDER BY p.updated_at ASC, p.id ASC
         LIMIT " . $limit,
        '',
        array()
    );

    $projects = array();
    foreach ($rows as $row) {
        $projects[] = chat_a_trigger_project_payload($row);
    }

    chat_a_pending_api_response(200, array('ok' => true, 'projects' => $projects));
} catch (Exception $error) {
    error_log('[chat-a-pending] ' . $error->getMessage());
    chat_a_pending_api_response(500, array('ok' => false, 'error' => $error->getMessage()));
}

function chat_a_pending_api_assert_key()
{
    $expected = chat_a_pending_api_expected_key();
    if ($expected === '') {
        chat_a_pending_api_response(500, array('ok' => false, 'error' => 'api_key_not_configured'));
    }

    $provided = isset($_SERVER['HTTP_X_ADMISSION_API_KEY']) ? (string) $_SERVER['HTTP_X_ADMISSION_API_KEY'] : '';
    $authorization = isset($_SERVER['HTTP_AUTHORIZATION']) ? (string) $_SERVER['HTTP_AUTHORIZATION'] : '';
    if ($provided === '' && preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
        $provided = trim($matches[1]);
    }

    if (!function_exists('hash_equals')) {
        if ($provided !== $expected) {
            chat_a_pending_api_response(401, array('ok' => false, 'error' => 'unauthorized'));
        }
        return;
    }

    if (!hash_equals($expected, $provided)) {
        chat_a_pending_api_response(401, array('ok' => false, 'error' => 'unauthorized'));
    }
}

function chat_a_pending_api_expected_key()
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

function chat_a_pending_api_response($status, $body)
{
    http_response_code((int) $status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
