<?php

require_once __DIR__ . '/../config/config.php';

function factory_db()
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = admission_config();
    $dsn = isset($config['db_dsn']) ? $config['db_dsn'] : null;

    if (!$dsn) {
        if (empty($config['db_name']) || empty($config['db_user'])) {
            throw new RuntimeException('Database configuration is incomplete.');
        }

        $charset = !empty($config['db_charset']) ? $config['db_charset'] : 'utf8mb4';
        $host = !empty($config['db_host']) ? $config['db_host'] : 'localhost';
        $dsn = 'mysql:host=' . $host . ';dbname=' . $config['db_name'] . ';charset=' . $charset;
    }

    $pdo = new PDO($dsn, $config['db_user'], isset($config['db_pass']) ? $config['db_pass'] : '', array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ));

    return $pdo;
}

function factory_string_or_null($value)
{
    if ($value === null) {
        return null;
    }

    if (is_bool($value) || is_int($value) || is_float($value)) {
        $value = (string) $value;
    }

    if (!is_string($value)) {
        return null;
    }

    $value = trim($value);

    return $value === '' ? null : $value;
}

function factory_normalize_inquiry($payload, $server)
{
    if (!is_array($payload)) {
        $payload = array();
    }

    $factoryProjectId = factory_string_or_null(isset($payload['factory_project_id']) ? $payload['factory_project_id'] : null);
    $name = factory_string_or_null(isset($payload['name']) ? $payload['name'] : null);
    $phone = factory_string_or_null(isset($payload['phone']) ? $payload['phone'] : null);
    $email = factory_string_or_null(isset($payload['email']) ? $payload['email'] : null);
    $websiteType = factory_string_or_null(isset($payload['website_type']) ? $payload['website_type'] : (isset($payload['type']) ? $payload['type'] : null));
    $budgetRange = factory_string_or_null(isset($payload['budget_range']) ? $payload['budget_range'] : (isset($payload['budget']) ? $payload['budget'] : null));
    $message = factory_string_or_null(isset($payload['message']) ? $payload['message'] : (isset($payload['note']) ? $payload['note'] : null));
    $source = factory_string_or_null(isset($payload['source']) ? $payload['source'] : 'booking_form');

    $ipAddress = factory_string_or_null(isset($server['REMOTE_ADDR']) ? $server['REMOTE_ADDR'] : null);
    $userAgent = factory_string_or_null(isset($server['HTTP_USER_AGENT']) ? $server['HTTP_USER_AGENT'] : null);

    return array(
        'factory_project_id' => $factoryProjectId,
        'client_id' => null,
        'source' => $source ?: 'booking_form',
        'name' => $name,
        'phone' => $phone,
        'email' => $email,
        'website_type' => $websiteType,
        'budget_range' => $budgetRange,
        'message' => $message,
        'inquiry_status' => 'new',
        'ip_address' => $ipAddress,
        'user_agent' => $userAgent,
        'raw_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    );
}

function factory_validate_inquiry($inquiry)
{
    $missing = array();

    if (!$inquiry['name']) {
        $missing[] = 'name';
    }

    if (!$inquiry['phone']) {
        $missing[] = 'phone';
    }

    return $missing;
}

function factory_find_client_id_for_inquiry($pdo, $inquiry)
{
    if ($inquiry['phone']) {
        $stmt = $pdo->prepare('SELECT client_id FROM clients WHERE phone = :phone LIMIT 1');
        $stmt->execute(array('phone' => $inquiry['phone']));
        $row = $stmt->fetch();
        if ($row) {
            return (int) $row['client_id'];
        }
    }

    if ($inquiry['email']) {
        $stmt = $pdo->prepare('SELECT client_id FROM clients WHERE email = :email LIMIT 1');
        $stmt->execute(array('email' => $inquiry['email']));
        $row = $stmt->fetch();
        if ($row) {
            return (int) $row['client_id'];
        }
    }

    return null;
}

function factory_store_inquiry($payload, $server)
{
    $inquiry = factory_normalize_inquiry($payload, $server);
    $missing = factory_validate_inquiry($inquiry);

    if ($missing) {
        return array(
            'ok' => false,
            'status' => 422,
            'error' => 'missing_required_fields',
            'missing_fields' => $missing,
        );
    }

    $pdo = factory_db();
    $clientId = factory_find_client_id_for_inquiry($pdo, $inquiry);
    $inquiry['client_id'] = $clientId;

    $stmt = $pdo->prepare(
        'INSERT INTO factory_inquiries (
            factory_project_id, client_id, source, name, phone, email,
            website_type, budget_range, message, inquiry_status,
            ip_address, user_agent, raw_payload, created_at, updated_at
         ) VALUES (
            :factory_project_id, :client_id, :source, :name, :phone, :email,
            :website_type, :budget_range, :message, :inquiry_status,
            :ip_address, :user_agent, :raw_payload, NOW(), NOW()
         )'
    );

    $stmt->execute(array(
        'factory_project_id' => $inquiry['factory_project_id'],
        'client_id' => $inquiry['client_id'],
        'source' => $inquiry['source'],
        'name' => $inquiry['name'],
        'phone' => $inquiry['phone'],
        'email' => $inquiry['email'],
        'website_type' => $inquiry['website_type'],
        'budget_range' => $inquiry['budget_range'],
        'message' => $inquiry['message'],
        'inquiry_status' => $inquiry['inquiry_status'],
        'ip_address' => $inquiry['ip_address'],
        'user_agent' => $inquiry['user_agent'],
        'raw_payload' => $inquiry['raw_payload'],
    ));

    return array(
        'ok' => true,
        'status' => 201,
        'inquiry_id' => (int) $pdo->lastInsertId(),
        'client_id' => $clientId,
    );
}
