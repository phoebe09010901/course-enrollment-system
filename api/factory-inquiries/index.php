<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../lib/factory_repository.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        factory_respond_json(405, array('ok' => false, 'error' => 'method_not_allowed'));
    }

    $payload = factory_read_request_payload();
    $result = factory_store_inquiry($payload, $_SERVER);
    $status = (int) $result['status'];
    unset($result['status']);

    factory_respond_json($status, $result);
} catch (Exception $error) {
    error_log('[factory-inquiries] ' . $error->getMessage());
    factory_respond_json(500, array('ok' => false, 'error' => 'server_error'));
}

function factory_read_request_payload()
{
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? strtolower((string) $_SERVER['CONTENT_TYPE']) : '';

    if (strpos($contentType, 'application/json') !== false) {
        $rawBody = file_get_contents('php://input');
        $payload = json_decode($rawBody ? $rawBody : '', true);

        if (!is_array($payload)) {
            factory_respond_json(400, array('ok' => false, 'error' => 'invalid_json'));
        }

        return $payload;
    }

    return $_POST;
}

function factory_respond_json($status, $body)
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
