<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function admission_normalize_intake(array $payload): array
{
    $client = is_array($payload['client'] ?? null) ? $payload['client'] : [];
    $course = is_array($payload['course'] ?? null) ? $payload['course'] : [];
    $line = is_array($payload['line'] ?? null) ? $payload['line'] : [];

    return [
        'client_name' => string_or_null($client['client_name'] ?? $client['name'] ?? $payload['client_name'] ?? null),
        'phone' => string_or_null($client['phone'] ?? $payload['phone'] ?? null),
        'email' => string_or_null($client['email'] ?? $payload['email'] ?? null),
        'line_user_id' => string_or_null($line['user_id'] ?? $client['line_user_id'] ?? $payload['line_user_id'] ?? null),
        'line_display_name' => string_or_null($line['display_name'] ?? $client['line_display_name'] ?? $payload['line_display_name'] ?? null),
        'brand_name' => string_or_null($client['brand_name'] ?? $payload['brand_name'] ?? null),
        'location_area' => string_or_null($client['location_area'] ?? $payload['location_area'] ?? null),
        'course_name' => string_or_null($course['course_name'] ?? $payload['course_name'] ?? null),
        'course_type' => string_or_null($course['course_type'] ?? $payload['course_type'] ?? null),
        'course_format' => string_or_null($course['course_format'] ?? $payload['course_format'] ?? null),
        'course_location' => string_or_null($course['course_location'] ?? $payload['course_location'] ?? null),
        'target_audience' => string_or_null($course['target_audience'] ?? $payload['target_audience'] ?? null),
        'course_features' => string_or_null($course['course_features'] ?? $payload['course_features'] ?? null),
        'source' => string_or_null($payload['source'] ?? 'line_ai'),
        'raw_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ];
}

function admission_validate_intake(array $intake): array
{
    $missing = [];

    if (!$intake['client_name'] && !$intake['line_display_name']) {
        $missing[] = 'client_name';
    }

    if (!$intake['line_user_id'] && !$intake['phone'] && !$intake['email']) {
        $missing[] = 'line_user_id_or_contact';
    }

    if (!$intake['course_name']) {
        $missing[] = 'course_name';
    }

    return $missing;
}

function admission_store_intake(array $payload): array
{
    $intake = admission_normalize_intake($payload);
    $missing = admission_validate_intake($intake);

    if ($missing) {
        return [
            'ok' => false,
            'status' => 422,
            'error' => 'missing_required_fields',
            'missing_fields' => $missing,
        ];
    }

    $pdo = admission_db();
    $pdo->beginTransaction();

    try {
        $clientId = admission_upsert_client($pdo, $intake);
        $intakeId = admission_insert_course_intake($pdo, $clientId, $intake);
        $pdo->commit();

        return [
            'ok' => true,
            'status' => 201,
            'client_id' => $clientId,
            'intake_id' => $intakeId,
        ];
    } catch (Throwable $error) {
        $pdo->rollBack();
        throw $error;
    }
}

function admission_upsert_client(PDO $pdo, array $intake): int
{
    if ($intake['line_user_id']) {
        $stmt = $pdo->prepare('SELECT client_id FROM clients WHERE line_user_id = :line_user_id LIMIT 1');
        $stmt->execute(['line_user_id' => $intake['line_user_id']]);
        $existing = $stmt->fetch();

        if ($existing) {
            $clientId = (int) $existing['client_id'];
            $stmt = $pdo->prepare(
                'UPDATE clients
                 SET client_name = COALESCE(:client_name, client_name),
                     phone = COALESCE(:phone, phone),
                     email = COALESCE(:email, email),
                     line_display_name = COALESCE(:line_display_name, line_display_name),
                     brand_name = COALESCE(:brand_name, brand_name),
                     location_area = COALESCE(:location_area, location_area),
                     client_status = :client_status,
                     updated_at = NOW()
                 WHERE client_id = :client_id'
            );
            $stmt->execute([
                'client_id' => $clientId,
                'client_name' => $intake['client_name'] ?: $intake['line_display_name'],
                'phone' => $intake['phone'],
                'email' => $intake['email'],
                'line_display_name' => $intake['line_display_name'],
                'brand_name' => $intake['brand_name'],
                'location_area' => $intake['location_area'],
                'client_status' => 'active',
            ]);

            return $clientId;
        }
    }

    $stmt = $pdo->prepare(
        'INSERT INTO clients (
            client_name, phone, email, line_user_id, line_display_name,
            brand_name, location_area, client_status, created_at, updated_at
         ) VALUES (
            :client_name, :phone, :email, :line_user_id, :line_display_name,
            :brand_name, :location_area, :client_status, NOW(), NOW()
         )'
    );
    $stmt->execute([
        'client_name' => $intake['client_name'] ?: $intake['line_display_name'],
        'phone' => $intake['phone'],
        'email' => $intake['email'],
        'line_user_id' => $intake['line_user_id'],
        'line_display_name' => $intake['line_display_name'],
        'brand_name' => $intake['brand_name'],
        'location_area' => $intake['location_area'],
        'client_status' => 'active',
    ]);

    return (int) $pdo->lastInsertId();
}

function admission_insert_course_intake(PDO $pdo, int $clientId, array $intake): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO course_intakes (
            client_id, source, course_name, course_type, course_format,
            course_location, target_audience, course_features, intake_status,
            raw_payload, created_at, updated_at
         ) VALUES (
            :client_id, :source, :course_name, :course_type, :course_format,
            :course_location, :target_audience, :course_features, :intake_status,
            :raw_payload, NOW(), NOW()
         )'
    );
    $stmt->execute([
        'client_id' => $clientId,
        'source' => $intake['source'],
        'course_name' => $intake['course_name'],
        'course_type' => $intake['course_type'],
        'course_format' => $intake['course_format'],
        'course_location' => $intake['course_location'],
        'target_audience' => $intake['target_audience'],
        'course_features' => $intake['course_features'],
        'intake_status' => '已建檔',
        'raw_payload' => $intake['raw_payload'],
    ]);

    return (int) $pdo->lastInsertId();
}

function string_or_null($value): ?string
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
