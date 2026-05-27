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
                'pending_canva_proposals',
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
                'pending_canva_proposals',
                chat_d_json($rawPayload),
                now(),
                now(),
            )
        );
    }

    chat_d_project_selection_token($projectId);

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

function chat_d_sync_template_proposals($projectId, $proposals, $expiresAt)
{
    if (!chat_d_table_exists('template_proposals') || !chat_d_table_exists('course_projects')) {
        throw new Exception('template_flow_tables_missing');
    }

    $project = chat_d_project_by_id($projectId);
    if (!$project) {
        throw new Exception('project_not_found');
    }

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

        $existing = db_one(
            'SELECT id FROM template_proposals WHERE project_id = ? AND proposal_id = ? LIMIT 1',
            'ss',
            array($projectId, $proposalId)
        );

        $params = array(
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
        );

        if ($existing) {
            db_exec(
                'UPDATE template_proposals
                 SET proposal_code = ?, proposal_name = ?, primary_template_id = ?, secondary_template_id = ?,
                     source_url = ?, secondary_source_url = ?, visual_direction = ?, suitable_reason = ?,
                     canva_url = ?, screenshot_url = ?, status = ?, expires_at = ?, updated_at = ?
                 WHERE project_id = ? AND proposal_id = ?',
                'sssssssssssssss',
                array(
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
                    $projectId,
                    $proposalId,
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
                array_merge($params, array(now()))
            );
        }

        $saved[] = $proposalId;
        if ($proposalExpiresAt !== '') {
            $proposalExpires[] = $proposalExpiresAt;
        }
    }

    sort($proposalExpires);
    $projectExpiresAt = count($proposalExpires) ? $proposalExpires[0] : $expiresAt;

    db_exec(
        'UPDATE course_projects
         SET project_status = ?, template_status = ?, preview_expires_at = ?, updated_at = ?
         WHERE project_id = ?',
        'sssss',
        array('Canva 樣板提案完成', 'canva_proposals_ready', $projectExpiresAt, now(), $projectId)
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
            'canva_template_selected',
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
