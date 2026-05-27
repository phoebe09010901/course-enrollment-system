<?php

function chat_d_table_exists($tableName)
{
    $row = db_one(
        'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1',
        's',
        array($tableName)
    );
    return !empty($row);
}

function chat_d_column_exists($tableName, $columnName)
{
    $row = db_one(
        'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
        'ss',
        array($tableName, $columnName)
    );
    return !empty($row);
}

function chat_d_json($value)
{
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function chat_d_project_id_for_intake($intakeId)
{
    return 'CP-' . date('Ymd') . '-' . str_pad((string) (int) $intakeId, 5, '0', STR_PAD_LEFT);
}

function chat_d_random_token()
{
    if (function_exists('openssl_random_pseudo_bytes')) {
        $bytes = openssl_random_pseudo_bytes(32);
        if ($bytes !== false) {
            return bin2hex($bytes);
        }
    }

    return sha1(uniqid('', true) . mt_rand() . microtime(true)) . sha1(mt_rand() . uniqid('', true));
}

function chat_d_project_proposal_batch_id($projectId)
{
    if (!chat_d_column_exists('course_projects', 'proposal_batch_id')) {
        return '';
    }

    $project = db_one('SELECT proposal_batch_id FROM course_projects WHERE project_id = ? LIMIT 1', 's', array($projectId));
    if ($project && !empty($project['proposal_batch_id'])) {
        return $project['proposal_batch_id'];
    }

    $batchId = 'batch_' . substr(chat_d_random_token(), 0, 24);
    db_exec('UPDATE course_projects SET proposal_batch_id = ?, updated_at = ? WHERE project_id = ?', 'sss', array($batchId, now(), $projectId));
    return $batchId;
}

function chat_d_ensure_project_from_intake($clientId, $intakeId, $recordId, $values, $photoAssets)
{
    if (!chat_d_table_exists('course_projects')) {
        error_log('[chat-d] course_projects table is missing; project was not created.');
        return '';
    }

    $existing = db_one(
        'SELECT project_id FROM course_projects WHERE intake_id = ? LIMIT 1',
        'i',
        array((int) $intakeId)
    );

    $projectId = $existing && !empty($existing['project_id'])
        ? $existing['project_id']
        : chat_d_project_id_for_intake($intakeId);

    $rawPayload = array(
        'source' => 'public_course_intake_form',
        'record_id' => $recordId,
        'course_project' => array(
            'course_name' => isset($values['course_name']) ? $values['course_name'] : '',
            'course_type' => isset($values['course_type']) ? $values['course_type'] : '',
            'course_format' => isset($values['course_format']) ? $values['course_format'] : '',
            'course_location' => isset($values['course_location']) ? $values['course_location'] : '',
            'expected_launch_date' => isset($values['expected_launch_date']) ? $values['expected_launch_date'] : '',
            'expected_start_date' => isset($values['expected_start_date']) ? $values['expected_start_date'] : '',
            'course_capacity' => isset($values['course_capacity']) ? $values['course_capacity'] : '',
            'course_price' => isset($values['course_price']) ? $values['course_price'] : '',
            'target_audience' => isset($values['target_audience']) ? $values['target_audience'] : '',
            'course_features' => isset($values['course_features']) ? $values['course_features'] : '',
            'post_course_support' => isset($values['post_course_support']) ? $values['post_course_support'] : '',
        ),
        'course_assets' => $photoAssets,
    );

    if ($existing) {
        db_exec(
            'UPDATE course_projects
             SET client_id = ?, record_id = ?, source = ?, course_name = ?, course_type = ?, course_format = ?, course_location = ?,
                 project_status = ?, template_status = ?, needs_template_proposal = 1, raw_payload = ?, updated_at = ?
             WHERE project_id = ?',
            'isssssssssss',
            array(
                (int) $clientId,
                $recordId,
                'public_form',
                $values['course_name'],
                $values['course_type'],
                $values['course_format'],
                $values['course_location'],
                '待樣板提案',
                'pending_template',
                chat_d_json($rawPayload),
                now(),
                $projectId,
            )
        );
    } else {
        db_exec(
            'INSERT INTO course_projects (
                project_id, client_id, intake_id, record_id, source, course_name, course_type, course_format, course_location,
                project_status, template_status, needs_template_proposal, raw_payload, created_at, updated_at
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)',
            'siisssssssssss',
            array(
                $projectId,
                (int) $clientId,
                (int) $intakeId,
                $recordId,
                'public_form',
                $values['course_name'],
                $values['course_type'],
                $values['course_format'],
                $values['course_location'],
                '待樣板提案',
                'pending_template',
                chat_d_json($rawPayload),
                now(),
                now(),
            )
        );
    }

    chat_d_project_selection_token($projectId);
    chat_d_project_proposal_batch_id($projectId);

    if (chat_d_table_exists('notification_logs')) {
        chat_d_log_notification($projectId, $clientId, 'data_confirmed', 'system', '', array(), '公開表單已送出，等待 Chat A / Canva 樣板提案。', 'recorded');
    }

    db_exec(
        'UPDATE course_intakes SET intake_status = ?, updated_at = ? WHERE ' . chat_d_course_intakes_primary_key() . ' = ?',
        'ssi',
        array('待樣板提案', now(), (int) $intakeId)
    );

    return $projectId;
}

function chat_d_project_selection_token($projectId)
{
    if (!chat_d_column_exists('course_projects', 'selection_token')) {
        return '';
    }

    $project = db_one('SELECT selection_token FROM course_projects WHERE project_id = ? LIMIT 1', 's', array($projectId));
    if ($project && !empty($project['selection_token'])) {
        return $project['selection_token'];
    }

    $token = chat_d_random_token();
    db_exec('UPDATE course_projects SET selection_token = ?, updated_at = ? WHERE project_id = ?', 'sss', array($token, now(), $projectId));
    return $token;
}

function chat_d_project_selection_url($projectId)
{
    $token = chat_d_project_selection_token($projectId);
    if ($token === '') {
        return '';
    }

    return app_url('course-template-proposals.php?t=' . rawurlencode($token));
}

function chat_d_project_by_selection_token($token)
{
    if ($token === '' || !chat_d_table_exists('course_projects') || !chat_d_column_exists('course_projects', 'selection_token')) {
        return null;
    }

    return db_one('SELECT * FROM course_projects WHERE selection_token = ? LIMIT 1', 's', array($token));
}

function chat_d_project_by_id($projectId)
{
    if (!chat_d_table_exists('course_projects')) {
        return null;
    }

    return db_one('SELECT * FROM course_projects WHERE project_id = ? LIMIT 1', 's', array($projectId));
}

function chat_d_template_status_label($status)
{
    $labels = array(
        'pending_template' => '待樣板提案',
        'processing_template' => '樣板製作中',
        'template_ready' => '樣板已完成',
        'template_failed' => '樣板產生失敗',
        'template_expired' => '樣板已逾期',
        'canva_template_selected' => '已選定樣板',
        'canva_proposals_ready' => '樣板已完成',
        'pending_canva_proposals' => '待樣板提案',
        'chat_a_trigger_queued' => '待樣板提案',
        'chat_a_triggered' => '樣板製作中',
    );

    if (isset($labels[$status])) {
        return $labels[$status];
    }

    return $status === '' ? '狀態未設定' : $status;
}

function chat_d_template_error_label($errorCode)
{
    $labels = array(
        'missing_course_data' => '缺少課程資料',
        'canva_generation_failed' => 'Canva 產生失敗',
        'api_writeback_failed' => 'API 回寫失敗',
        'worker_exception' => '自動流程失敗',
        'template_proposals_invalid' => '提案資料不完整',
        'template_batch_locked' => '已有有效樣板批次',
    );

    if (isset($labels[$errorCode])) {
        return $labels[$errorCode];
    }

    return $errorCode === '' ? '' : $errorCode;
}

function chat_d_proposal_status_label($status)
{
    $labels = array(
        'proposal_ready' => '提案已完成',
        'sent_to_client' => '已送給客戶',
        'selected' => '已選定',
        'draft' => '草稿',
        'pending' => '等待中',
        'failed' => '提案失敗',
        'expired' => '已逾期',
    );

    if (isset($labels[$status])) {
        return $labels[$status];
    }

    return $status === '' ? '狀態未設定' : $status;
}

function chat_d_generate_worker_run_id()
{
    return 'worker_' . date('YmdHis') . '_' . substr(chat_d_random_token(), 0, 12);
}

function chat_d_reset_template_generation($projectId)
{
    if (!chat_d_table_exists('course_projects')) {
        throw new Exception('course_projects_table_missing');
    }

    $project = chat_d_project_by_id($projectId);
    if (!$project) {
        throw new Exception('project_not_found');
    }

    $newBatchId = 'batch_' . substr(chat_d_random_token(), 0, 24);

    db()->autocommit(false);
    try {
        if (chat_d_table_exists('template_proposals')) {
            db_exec('DELETE FROM template_proposals WHERE project_id = ?', 's', array($projectId));
        }

        $sql = 'UPDATE course_projects
                SET project_status = ?, template_status = ?, needs_template_proposal = 1, updated_at = ?';
        $types = 'sss';
        $params = array('待樣板提案', 'pending_template', now());

        $optionalNullColumns = array(
            'template_processing_started_at',
            'template_processing_by',
            'worker_run_id',
            'template_error_code',
            'template_error_message',
            'selected_proposal_id',
            'selected_template_id',
            'selected_secondary_template_id',
            'selected_canva_direction',
            'selected_canva_url',
            'template_selected_at',
            'preview_expires_at',
        );

        if (chat_d_column_exists('course_projects', 'proposal_batch_id')) {
            $sql .= ', proposal_batch_id = ?';
            $types .= 's';
            $params[] = $newBatchId;
        }

        foreach ($optionalNullColumns as $columnName) {
            if (chat_d_column_exists('course_projects', $columnName)) {
                $sql .= ', ' . $columnName . ' = NULL';
            }
        }

        $sql .= ' WHERE project_id = ?';
        $types .= 's';
        $params[] = $projectId;

        db_exec($sql, $types, $params);

        if (!empty($project['intake_id']) && chat_d_table_exists('course_intakes')) {
            db_exec(
                'UPDATE course_intakes SET intake_status = ?, updated_at = ? WHERE ' . chat_d_course_intakes_primary_key() . ' = ?',
                'ssi',
                array('待樣板提案', now(), (int) $project['intake_id'])
            );
        }

        chat_d_log_notification(
            $projectId,
            isset($project['client_id']) ? (int) $project['client_id'] : null,
            'canva_regeneration_requested',
            'system',
            '',
            array(),
            '後台已要求重新產生 Canva 樣板提案。',
            'recorded'
        );

        db()->commit();
        db()->autocommit(true);
    } catch (Exception $error) {
        db()->rollback();
        db()->autocommit(true);
        throw $error;
    }

    return $newBatchId;
}

function chat_d_claim_template_projects($limit, $workerRunId, $workerName)
{
    if (!chat_d_table_exists('course_projects')) {
        return array();
    }

    $limit = (int) $limit;
    if ($limit <= 0 || $limit > 10) {
        $limit = 3;
    }

    $workerRunId = trim((string) $workerRunId);
    if ($workerRunId === '') {
        $workerRunId = chat_d_generate_worker_run_id();
    }

    $workerName = trim((string) $workerName);
    if ($workerName === '') {
        $workerName = 'chat-a-worker';
    }

    $hasProcessingFields = chat_d_column_exists('course_projects', 'template_processing_started_at')
        && chat_d_column_exists('course_projects', 'template_processing_by')
        && chat_d_column_exists('course_projects', 'worker_run_id');
    $hasBatchField = chat_d_column_exists('course_projects', 'proposal_batch_id');
    $hasErrorFields = chat_d_column_exists('course_projects', 'template_error_code')
        && chat_d_column_exists('course_projects', 'template_error_message');

    $processingCondition = '';
    if ($hasProcessingFields) {
        $processingCondition = "OR (
                        p.template_status IN ('processing_template', 'chat_a_triggered')
                        AND p.template_processing_started_at IS NOT NULL
                        AND p.template_processing_started_at < DATE_SUB(NOW(), INTERVAL 60 MINUTE)
                    )";
    }

    db()->autocommit(false);
    try {
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
             LIMIT " . $limit . " FOR UPDATE",
            '',
            array()
        );

        $claimed = array();
        foreach ($rows as $row) {
            $projectId = isset($row['project_id']) ? $row['project_id'] : '';
            if ($projectId === '') {
                continue;
            }

            if (chat_d_project_has_ready_proposal_batch($projectId)) {
                continue;
            }

            $proposalBatchId = '';
            if ($hasBatchField) {
                $proposalBatchId = !empty($row['proposal_batch_id']) ? $row['proposal_batch_id'] : 'batch_' . substr(chat_d_random_token(), 0, 24);
                $row['proposal_batch_id'] = $proposalBatchId;
            }

            $sql = 'UPDATE course_projects SET project_status = ?, template_status = ?, updated_at = ?';
            $types = 'sss';
            $params = array('樣板製作中', 'processing_template', now());

            if ($hasBatchField) {
                $sql .= ', proposal_batch_id = ?';
                $types .= 's';
                $params[] = $proposalBatchId;
            }

            if ($hasProcessingFields) {
                $sql .= ', template_processing_started_at = ?, template_processing_by = ?, worker_run_id = ?';
                $types .= 'sss';
                $params[] = now();
                $params[] = $workerName;
                $params[] = $workerRunId;
                $row['template_processing_started_at'] = now();
                $row['template_processing_by'] = $workerName;
                $row['worker_run_id'] = $workerRunId;
            }

            if ($hasErrorFields) {
                $sql .= ', template_error_code = NULL, template_error_message = NULL';
                $row['template_error_code'] = '';
                $row['template_error_message'] = '';
            }

            $sql .= ' WHERE project_id = ?';
            $types .= 's';
            $params[] = $projectId;
            db_exec($sql, $types, $params);

            $row['project_status'] = '樣板製作中';
            $row['template_status'] = 'processing_template';
            $claimed[] = $row;
        }

        db()->commit();
        db()->autocommit(true);
        return $claimed;
    } catch (Exception $error) {
        db()->rollback();
        db()->autocommit(true);
        throw $error;
    }
}

function chat_d_mark_template_failed($projectId, $workerRunId, $errorCode, $errorMessage)
{
    $projectId = trim((string) $projectId);
    if ($projectId === '' || !chat_d_table_exists('course_projects')) {
        return false;
    }

    $workerRunId = trim((string) $workerRunId);
    $errorCode = trim((string) $errorCode);
    $errorMessage = trim((string) $errorMessage);
    if ($errorCode === '') {
        $errorCode = 'worker_exception';
    }

    $hasWorkerField = chat_d_column_exists('course_projects', 'worker_run_id');
    $hasErrorFields = chat_d_column_exists('course_projects', 'template_error_code')
        && chat_d_column_exists('course_projects', 'template_error_message');

    $sql = 'UPDATE course_projects SET project_status = ?, template_status = ?, needs_template_proposal = 1, updated_at = ?';
    $types = 'sss';
    $params = array('樣板產生失敗', 'template_failed', now());

    if ($hasErrorFields) {
        $sql .= ', template_error_code = ?, template_error_message = ?';
        $types .= 'ss';
        $params[] = $errorCode;
        $params[] = function_exists('mb_substr') ? mb_substr($errorMessage, 0, 1000, 'UTF-8') : substr($errorMessage, 0, 1000);
    }

    $sql .= ' WHERE project_id = ?';
    $types .= 's';
    $params[] = $projectId;

    if ($hasWorkerField && $workerRunId !== '') {
        $sql .= ' AND (worker_run_id = ? OR worker_run_id IS NULL OR worker_run_id = \'\')';
        $types .= 's';
        $params[] = $workerRunId;
    }

    db_exec($sql, $types, $params);

    $project = chat_d_project_by_id($projectId);
    chat_d_log_notification(
        $projectId,
        $project && isset($project['client_id']) ? (int) $project['client_id'] : null,
        'canva_proposals_failed',
        'chat_a',
        '',
        array(),
        chat_d_template_error_label($errorCode) . ($errorMessage !== '' ? '：' . $errorMessage : ''),
        'failed'
    );

    return true;
}

function chat_d_project_proposals($projectId)
{
    if (!chat_d_table_exists('template_proposals')) {
        return array();
    }

    return db_all(
        'SELECT * FROM template_proposals WHERE project_id = ? ORDER BY proposal_code ASC, id ASC',
        's',
        array($projectId)
    );
}

function chat_d_project_has_ready_proposal_batch($projectId)
{
    if (!chat_d_table_exists('template_proposals')) {
        return false;
    }

    $project = chat_d_project_by_id($projectId);
    if (!$project) {
        return false;
    }

    $proposalBatchId = isset($project['proposal_batch_id']) ? (string) $project['proposal_batch_id'] : '';
    if ($proposalBatchId === '' || !chat_d_column_exists('template_proposals', 'proposal_batch_id')) {
        $row = db_one(
            "SELECT COUNT(*) AS total
             FROM template_proposals
             WHERE project_id = ?
               AND status IN ('proposal_ready', 'sent_to_client', 'selected')",
            's',
            array($projectId)
        );
        return $row && (int) $row['total'] >= 3;
    }

    $row = db_one(
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN canva_url IS NOT NULL AND canva_url <> ''
                 AND primary_template_id IS NOT NULL AND primary_template_id <> ''
                 AND secondary_template_id IS NOT NULL AND secondary_template_id <> ''
                 AND source_url IS NOT NULL AND source_url <> ''
                 AND secondary_source_url IS NOT NULL AND secondary_source_url <> ''
                THEN 1 ELSE 0 END) AS valid_total
         FROM template_proposals
         WHERE project_id = ?
           AND proposal_batch_id = ?
           AND proposal_code IN ('A', 'B', 'C')",
        'ss',
        array($projectId, $proposalBatchId)
    );

    return $row && (int) $row['total'] === 3 && (int) $row['valid_total'] === 3;
}

function chat_d_is_http_url($value)
{
    $value = trim((string) $value);
    return preg_match('/^https?:\/\/[^\s]+$/i', $value) === 1;
}

function chat_d_is_canva_url($value)
{
    $value = trim((string) $value);
    return chat_d_is_http_url($value) && preg_match('/(^https?:\/\/|\.)(canva\.com)\//i', $value) === 1;
}

function chat_d_validate_template_proposals_ready($projectId, $project, $proposals, $proposalBatchId, $allowRegenerate)
{
    if (count($proposals) !== 3) {
        throw new Exception('template_proposals_invalid: proposal_count_must_be_exactly_3');
    }

    if (!$allowRegenerate && chat_d_project_has_ready_proposal_batch($projectId)) {
        throw new Exception('template_batch_locked: project_already_has_valid_proposal_batch');
    }

    $requiredCodes = array('A' => false, 'B' => false, 'C' => false);
    foreach ($proposals as $index => $proposal) {
        if (!is_array($proposal)) {
            throw new Exception('template_proposals_invalid: proposal_' . ($index + 1) . '_must_be_object');
        }

        $proposalCode = strtoupper(trim(chat_d_value($proposal, 'proposal_code', chr(65 + (int) $index))));
        if (!isset($requiredCodes[$proposalCode])) {
            throw new Exception('template_proposals_invalid: proposal_code_must_be_A_B_C');
        }
        if ($requiredCodes[$proposalCode]) {
            throw new Exception('template_proposals_invalid: duplicate_proposal_code_' . $proposalCode);
        }
        $requiredCodes[$proposalCode] = true;

        $missing = array();
        foreach (array('primary_template_id', 'secondary_template_id', 'source_url', 'secondary_source_url', 'canva_url') as $field) {
            if (trim(chat_d_value($proposal, $field, '')) === '') {
                $missing[] = $field;
            }
        }
        if (!empty($missing)) {
            throw new Exception('template_proposals_invalid: proposal_' . $proposalCode . '_missing_' . implode('_', $missing));
        }

        if (!chat_d_is_http_url(chat_d_value($proposal, 'source_url', ''))) {
            throw new Exception('template_proposals_invalid: proposal_' . $proposalCode . '_source_url_invalid');
        }
        if (!chat_d_is_http_url(chat_d_value($proposal, 'secondary_source_url', ''))) {
            throw new Exception('template_proposals_invalid: proposal_' . $proposalCode . '_secondary_source_url_invalid');
        }
        if (!chat_d_is_canva_url(chat_d_value($proposal, 'canva_url', ''))) {
            throw new Exception('template_proposals_invalid: proposal_' . $proposalCode . '_canva_url_required');
        }
    }

    foreach ($requiredCodes as $code => $seen) {
        if (!$seen) {
            throw new Exception('template_proposals_invalid: missing_proposal_' . $code);
        }
    }

    return true;
}

function chat_d_course_intakes_primary_key()
{
    $rows = db_all('SHOW COLUMNS FROM course_intakes', '', array());

    foreach ($rows as $row) {
        if (isset($row['Field']) && $row['Field'] === 'intake_id') {
            return 'intake_id';
        }
    }

    return 'id';
}

function chat_d_sync_template_proposals($projectId, $proposals, $expiresAt, $incomingProposalBatchId = '', $allowRegenerate = false)
{
    if (!chat_d_table_exists('template_proposals') || !chat_d_table_exists('course_projects')) {
        throw new Exception('template_flow_tables_missing');
    }

    $project = chat_d_project_by_id($projectId);
    if (!$project) {
        throw new Exception('project_not_found');
    }

    $proposalBatchId = '';
    if (chat_d_column_exists('course_projects', 'proposal_batch_id')) {
        $proposalBatchId = !empty($project['proposal_batch_id']) ? $project['proposal_batch_id'] : chat_d_project_proposal_batch_id($projectId);
    }
    $incomingProposalBatchId = trim((string) $incomingProposalBatchId);
    if ($incomingProposalBatchId !== '' && $proposalBatchId !== '' && $incomingProposalBatchId !== $proposalBatchId) {
        if (!$allowRegenerate) {
            throw new Exception('template_batch_locked: incoming_batch_does_not_match_project_batch');
        }
    }

    chat_d_validate_template_proposals_ready($projectId, $project, $proposals, $proposalBatchId, $allowRegenerate);

    $saved = array();
    $proposalExpires = array();
    foreach ($proposals as $index => $proposal) {
        if (!is_array($proposal)) {
            continue;
        }

        $proposalCode = chat_d_value($proposal, 'proposal_code', chr(65 + (int) $index));
        $proposalId = chat_d_value($proposal, 'proposal_id', $proposalCode);
        $proposalExpiresAt = chat_d_value($proposal, 'expires_at', $expiresAt);
        if ($proposalId === '') {
            $proposalId = $proposalCode;
        }

        if ($proposalBatchId !== '' && chat_d_column_exists('template_proposals', 'proposal_batch_id')) {
            $existing = db_one(
                'SELECT id FROM template_proposals WHERE project_id = ? AND proposal_batch_id = ? AND proposal_code = ? LIMIT 1',
                'sss',
                array($projectId, $proposalBatchId, $proposalCode)
            );
        } else {
            $existing = db_one(
                'SELECT id FROM template_proposals WHERE project_id = ? AND proposal_id = ? LIMIT 1',
                'ss',
                array($projectId, $proposalId)
            );
        }

        if ($existing) {
            $sql = 'UPDATE template_proposals
                    SET proposal_code = ?, proposal_name = ?, primary_template_id = ?, secondary_template_id = ?,
                        source_url = ?, secondary_source_url = ?, visual_direction = ?, suitable_reason = ?,
                        canva_url = ?, screenshot_url = ?, status = ?, expires_at = ?, updated_at = ?';
            $types = 'sssssssssssss';
            $params = array(
                $proposalCode,
                chat_d_value($proposal, 'proposal_name', ''),
                chat_d_value($proposal, 'primary_template_id', ''),
                chat_d_value($proposal, 'secondary_template_id', ''),
                chat_d_value($proposal, 'source_url', ''),
                chat_d_value($proposal, 'secondary_source_url', ''),
                chat_d_value($proposal, 'visual_direction', ''),
                chat_d_value($proposal, 'suitable_reason', ''),
                chat_d_value($proposal, 'canva_url', ''),
                chat_d_value($proposal, 'screenshot_url', ''),
                chat_d_value($proposal, 'status', 'proposal_ready'),
                $proposalExpiresAt,
                now(),
            );
            if ($proposalBatchId !== '' && chat_d_column_exists('template_proposals', 'proposal_batch_id')) {
                $sql .= ' WHERE project_id = ? AND proposal_batch_id = ? AND proposal_code = ?';
                $types .= 'sss';
                $params[] = $projectId;
                $params[] = $proposalBatchId;
                $params[] = $proposalCode;
            } else {
                $sql .= ' WHERE project_id = ? AND proposal_id = ?';
                $types .= 'ss';
                $params[] = $projectId;
                $params[] = $proposalId;
            }
            db_exec($sql, $types, $params);
        } else {
            if ($proposalBatchId !== '' && chat_d_column_exists('template_proposals', 'proposal_batch_id')) {
                db_exec(
                    'INSERT INTO template_proposals (
                        project_id, proposal_batch_id, proposal_id, proposal_code, proposal_name, primary_template_id, secondary_template_id,
                        source_url, secondary_source_url, visual_direction, suitable_reason, canva_url, screenshot_url,
                        status, expires_at, created_at, updated_at
                     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    'sssssssssssssssss',
                    array(
                        $projectId,
                        $proposalBatchId,
                        $proposalId,
                        $proposalCode,
                        chat_d_value($proposal, 'proposal_name', ''),
                        chat_d_value($proposal, 'primary_template_id', ''),
                        chat_d_value($proposal, 'secondary_template_id', ''),
                        chat_d_value($proposal, 'source_url', ''),
                        chat_d_value($proposal, 'secondary_source_url', ''),
                        chat_d_value($proposal, 'visual_direction', ''),
                        chat_d_value($proposal, 'suitable_reason', ''),
                        chat_d_value($proposal, 'canva_url', ''),
                        chat_d_value($proposal, 'screenshot_url', ''),
                        chat_d_value($proposal, 'status', 'proposal_ready'),
                        $proposalExpiresAt,
                        now(),
                        now(),
                    )
                );
            } else {
                db_exec(
                    'INSERT INTO template_proposals (
                        project_id, proposal_id, proposal_code, proposal_name, primary_template_id, secondary_template_id,
                        source_url, secondary_source_url, visual_direction, suitable_reason, canva_url, screenshot_url,
                        status, expires_at, created_at, updated_at
                     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    'ssssssssssssssss',
                    array(
                        $projectId,
                        $proposalId,
                        $proposalCode,
                        chat_d_value($proposal, 'proposal_name', ''),
                        chat_d_value($proposal, 'primary_template_id', ''),
                        chat_d_value($proposal, 'secondary_template_id', ''),
                        chat_d_value($proposal, 'source_url', ''),
                        chat_d_value($proposal, 'secondary_source_url', ''),
                        chat_d_value($proposal, 'visual_direction', ''),
                        chat_d_value($proposal, 'suitable_reason', ''),
                        chat_d_value($proposal, 'canva_url', ''),
                        chat_d_value($proposal, 'screenshot_url', ''),
                        chat_d_value($proposal, 'status', 'proposal_ready'),
                        $proposalExpiresAt,
                        now(),
                        now(),
                    )
                );
            }
        }

        $saved[] = $proposalId;
        if ($proposalExpiresAt !== '') {
            $proposalExpires[] = $proposalExpiresAt;
        }
    }

    sort($proposalExpires);
    $projectExpiresAt = count($proposalExpires) ? $proposalExpires[0] : $expiresAt;

    $projectUpdateSql = 'UPDATE course_projects
         SET project_status = ?, template_status = ?, needs_template_proposal = 0, preview_expires_at = ?, updated_at = ?';
    if (chat_d_column_exists('course_projects', 'template_error_code')
        && chat_d_column_exists('course_projects', 'template_error_message')) {
        $projectUpdateSql .= ', template_error_code = NULL, template_error_message = NULL';
    }
    $projectUpdateSql .= ' WHERE project_id = ?';
    db_exec(
        $projectUpdateSql,
        'sssss',
        array('Canva 樣板提案完成', 'template_ready', $projectExpiresAt, now(), $projectId)
    );

    chat_d_log_notification(
        $projectId,
        isset($project['client_id']) ? (int) $project['client_id'] : null,
        'canva_proposals_recorded',
        'system',
        '',
        $saved,
        'Chat A / Canva 三款樣板提案已寫入 template_proposals。',
        'recorded'
    );

    return $saved;
}

function chat_d_select_template_proposal($projectId, $proposalId)
{
    if (!chat_d_table_exists('template_proposals') || !chat_d_table_exists('course_projects')) {
        throw new Exception('template_flow_tables_missing');
    }

    $project = chat_d_project_by_id($projectId);
    if (!$project) {
        throw new Exception('project_not_found');
    }

    $proposal = db_one(
        'SELECT * FROM template_proposals WHERE project_id = ? AND (proposal_id = ? OR proposal_code = ?) LIMIT 1',
        'sss',
        array($projectId, $proposalId, strtoupper($proposalId))
    );

    if (!$proposal) {
        throw new Exception('proposal_not_found');
    }

    db_exec(
        'UPDATE template_proposals
         SET is_selected = 0, status = ?, selected_at = NULL, updated_at = ?
         WHERE project_id = ?',
        'sss',
        array('not_selected', now(), $projectId)
    );

    db_exec(
        'UPDATE template_proposals
         SET is_selected = 1, status = ?, selected_at = ?, updated_at = ?
         WHERE id = ?',
        'sssi',
        array('selected', now(), now(), (int) $proposal['id'])
    );

    db_exec(
        'UPDATE course_projects
         SET selected_proposal_id = ?, selected_template_id = ?, selected_secondary_template_id = ?,
            selected_canva_direction = ?, selected_canva_url = ?, template_selected_at = ?,
             project_status = ?, template_status = ?, needs_template_proposal = 0, updated_at = ?
         WHERE project_id = ?',
        'ssssssssss',
        array(
            $proposal['proposal_id'],
            $proposal['primary_template_id'],
            $proposal['secondary_template_id'],
            $proposal['visual_direction'],
            $proposal['canva_url'],
            now(),
            '已選定 Canva 樣板',
            'template_ready',
            now(),
            $projectId,
        )
    );

    chat_d_log_notification(
        $projectId,
        isset($project['client_id']) ? (int) $project['client_id'] : null,
        'proposal_selected',
        'system',
        '',
        array($proposal['proposal_id']),
        '客戶已選定 ' . $proposal['proposal_id'] . ' 款 Canva 樣板。',
        'recorded'
    );

    return $proposal;
}

function chat_d_value($array, $key, $default)
{
    if (isset($array[$key]) && $array[$key] !== null) {
        return (string) $array[$key];
    }

    return $default;
}

function chat_d_log_notification($projectId, $clientId, $type, $channel, $recipient, $proposalIds, $message, $status)
{
    if (!chat_d_table_exists('notification_logs')) {
        return;
    }

    db_exec(
        'INSERT INTO notification_logs (
            project_id, client_id, notification_type, channel, recipient, proposal_ids,
            message_content, sent_status, sent_at, created_at, updated_at
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        'sisssssssss',
        array(
            $projectId,
            $clientId === null ? 0 : (int) $clientId,
            $type,
            $channel,
            $recipient,
            chat_d_json($proposalIds),
            $message,
            $status,
            now(),
            now(),
            now(),
        )
    );
}
