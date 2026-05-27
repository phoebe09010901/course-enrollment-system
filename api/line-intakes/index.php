<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../lib/intake_repository.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond_json(405, ['ok' => false, 'error' => 'method_not_allowed']);
    }

    assert_api_key();

    $rawBody = file_get_contents('php://input');
    $payload = json_decode($rawBody ?: '', true);

    if (!is_array($payload)) {
        respond_json(400, ['ok' => false, 'error' => 'invalid_json']);
    }

    $result = admission_store_intake($payload);
    $status = (int) $result['status'];
    unset($result['status']);

    respond_json($status, $result);
} catch (Throwable $error) {
    error_log('[line-intakes] ' . $error->getMessage());
    respond_json(500, ['ok' => false, 'error' => 'server_error']);
}

function assert_api_key(): void
{
    $config = admission_config();
    $expected = (string) ($config['api_key'] ?? '');

    if ($expected === '') {
        respond_json(500, ['ok' => false, 'error' => 'api_key_not_configured']);
    }

    $provided = $_SERVER['HTTP_X_ADMISSION_API_KEY'] ?? '';
    $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (!$provided && preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
        $provided = trim($matches[1]);
    }

    if (!is_string($provided) || !hash_equals($expected, $provided)) {
        respond_json(401, ['ok' => false, 'error' => 'unauthorized']);
    }
}

function respond_json(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
