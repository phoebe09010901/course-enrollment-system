<?php

require_once dirname(__FILE__) . '/template_proposal_flow.php';

function chat_a_trigger_for_project($projectId)
{
    $projectId = trim((string) $projectId);
    if ($projectId === '') {
        return array('ok' => false, 'status' => 'skipped', 'message' => 'project_id_empty');
    }

    $project = chat_d_project_by_id($projectId);
    if (!$project) {
        return array('ok' => false, 'status' => 'skipped', 'message' => 'project_not_found');
    }

    $workerRunId = chat_d_generate_worker_run_id();
    $payload = chat_a_trigger_payload($project);
    $payload['worker_run_id'] = $workerRunId;

    $webhookUrl = chat_a_trigger_config('CHAT_A_TRIGGER_WEBHOOK_URL');
    if ($webhookUrl === '') {
        db_exec(
            'UPDATE course_projects
             SET project_status = ?, template_status = ?, updated_at = ?
             WHERE project_id = ?',
            'ssss',
            array('待樣板提案', 'pending_template', now(), $projectId)
        );
        chat_a_trigger_log(
            $project,
            'chat_a_trigger_queued',
            'queued',
            "Chat A trigger 尚未設定 webhook，專案已排入待觸發狀態。\n\n" . chat_a_trigger_prompt_summary($payload)
        );
        return array('ok' => false, 'status' => 'queued', 'message' => 'webhook_not_configured');
    }

    $response = chat_a_trigger_post($webhookUrl, $payload);

    if ($response['ok']) {
        $sql = 'UPDATE course_projects SET project_status = ?, template_status = ?, updated_at = ?';
        $types = 'sss';
        $params = array('樣板製作中', 'processing_template', now());

        if (chat_d_column_exists('course_projects', 'template_processing_started_at')
            && chat_d_column_exists('course_projects', 'template_processing_by')
            && chat_d_column_exists('course_projects', 'worker_run_id')) {
            $sql .= ', template_processing_started_at = ?, template_processing_by = ?, worker_run_id = ?';
            $types .= 'sss';
            $params[] = now();
            $params[] = 'webhook';
            $params[] = $workerRunId;
        }

        if (chat_d_column_exists('course_projects', 'template_error_code')
            && chat_d_column_exists('course_projects', 'template_error_message')) {
            $sql .= ', template_error_code = NULL, template_error_message = NULL';
        }

        $sql .= ' WHERE project_id = ?';
        $types .= 's';
        $params[] = $projectId;
        db_exec($sql, $types, $params);

        chat_a_trigger_log($project, 'chat_a_triggered', 'sent', '公開表單已送出，系統已自動觸發 Chat A 開始產生三款 Canva 樣板。');
        return $response;
    }

    chat_d_mark_template_failed($projectId, $workerRunId, 'worker_exception', $response['message']);
    chat_a_trigger_log(
        $project,
        'chat_a_trigger_failed',
        'failed',
        'Chat A trigger 呼叫失敗：' . $response['message']
    );

    return $response;
}

function chat_a_trigger_payload($project)
{
    $rawPayload = array();
    if (!empty($project['raw_payload'])) {
        $decoded = json_decode((string) $project['raw_payload'], true);
        if (is_array($decoded)) {
            $rawPayload = $decoded;
        }
    }

    $projectId = isset($project['project_id']) ? $project['project_id'] : '';
    $proposalBatchId = $projectId !== '' ? chat_d_project_proposal_batch_id($projectId) : '';
    $expiresAt = date('Y-m-d H:i:s', strtotime('+3 days'));
    $prompt = chat_a_trigger_prompt($project, $rawPayload, $expiresAt);

    return array(
        'event' => 'course_project.created',
        'source' => 'admission-system',
        'project_id' => $projectId,
        'proposal_batch_id' => $proposalBatchId,
        'selection_url' => chat_d_project_selection_url($projectId),
        'expires_at' => $expiresAt,
        'project' => array(
            'course_name' => isset($project['course_name']) ? $project['course_name'] : '',
            'course_type' => isset($project['course_type']) ? $project['course_type'] : '',
            'course_format' => isset($project['course_format']) ? $project['course_format'] : '',
            'course_location' => isset($project['course_location']) ? $project['course_location'] : '',
            'template_status' => isset($project['template_status']) ? $project['template_status'] : '',
        ),
        'raw_payload' => $rawPayload,
        'chat_a_prompt' => $prompt,
        'required_output' => array(
            'project_id' => $projectId,
            'proposal_batch_id' => $proposalBatchId,
            'expires_at' => $expiresAt,
            'proposals' => chat_a_trigger_required_proposal_schema($expiresAt),
        ),
        'skill_context' => array(
            'skill' => 'course-canva-template-proposal',
            'skill_path' => '/Users/phoebe/.codex/skills/course-canva-template-proposal/SKILL.md',
            'required_docs' => array(
                'docs/STYLE_SYSTEM.md',
                'docs/TEMPLATE_REFERENCE.md',
                'docs/CLIENT_SELECTION_FLOW.md',
                'docs/COLLABORATION_SETUP.md',
                'docs/PROJECT_STATUS.md',
            ),
            'chat_d_reference' => '/Users/phoebe/.codex/skills/course-canva-template-proposal/references/chat-d-integration.md',
        ),
        'chat_d_callback' => array(
            'template_proposals_endpoint' => app_url('api/template-proposals/'),
            'failure_endpoint' => app_url('api/chat-a-trigger/fail.php'),
            'required_proposal_count' => 3,
            'auth' => 'Send ADMISSION_API_KEY as X-Admission-Api-Key or Bearer token.',
        ),
        'created_at' => now(),
    );
}

function chat_a_trigger_project_payload($project)
{
    $payload = chat_a_trigger_payload($project);
    $payload['worker_run_id'] = isset($project['worker_run_id']) ? $project['worker_run_id'] : '';
    $payload['proposal_batch_id'] = isset($project['proposal_batch_id']) ? $project['proposal_batch_id'] : $payload['proposal_batch_id'];
    $payload['template_processing_started_at'] = isset($project['template_processing_started_at']) ? $project['template_processing_started_at'] : '';
    $payload['client'] = array(
        'client_name' => isset($project['client_name']) ? $project['client_name'] : '',
        'contact_name' => isset($project['contact_name']) ? $project['contact_name'] : '',
        'email' => isset($project['email']) ? $project['email'] : '',
        'line_id' => isset($project['line_id']) ? $project['line_id'] : '',
        'line_id_link' => isset($project['line_id_link']) ? $project['line_id_link'] : '',
    );

    return $payload;
}

function chat_a_trigger_required_proposal_schema($expiresAt)
{
    $items = array();
    foreach (array('A', 'B', 'C') as $code) {
        $items[] = array(
            'proposal_id' => $code,
            'proposal_code' => $code,
            'proposal_name' => '',
            'primary_template_id' => '',
            'secondary_template_id' => '',
            'source_url' => '',
            'secondary_source_url' => '',
            'visual_direction' => '',
            'suitable_reason' => '',
            'canva_url' => '',
            'screenshot_url' => '',
            'status' => 'proposal_ready',
            'expires_at' => $expiresAt,
        );
    }

    return $items;
}

function chat_a_trigger_prompt($project, $rawPayload, $expiresAt)
{
    $projectId = isset($project['project_id']) ? $project['project_id'] : '';
    $courseName = isset($project['course_name']) ? $project['course_name'] : '';
    $courseType = isset($project['course_type']) ? $project['course_type'] : '';
    $courseFormat = isset($project['course_format']) ? $project['course_format'] : '';
    $courseLocation = isset($project['course_location']) ? $project['course_location'] : '';

    return implode("\n", array(
        '你是本專案的 Chat A。',
        '請依照 course-canva-template-proposal skill 與 repo docs/TEMPLATE_REFERENCE.md、docs/STYLE_SYSTEM.md，針對目前待處理專案產生三款 Canva-first 單頁招生樣板提案。',
        '每款提案必須從 docs/TEMPLATE_REFERENCE.md 登錄的 TPL-001 到 TPL-010 中挑選 primary_template_id 與 secondary_template_id，並使用文件中的真實 source_url / secondary_source_url。',
        '嚴禁使用 example.com、localhost、空白網址、臨時假網址或未登錄的 template_id；若無法取得真實來源網址，請回報 template_reference_missing，不要回填 proposal_ready。',
        '專案：' . $projectId . '（' . $courseName . '，' . $courseType . $courseFormat . '課，' . $courseLocation . '）',
        '課程資料與 R2 圖片 URLs 已放在 payload.raw_payload，請優先使用課程主題照、作品照與教室照。',
        '若 Canva 工具可用，請建立或準備三個 Canva 設計並回傳 canva_url；若工具無法建立實際 Canva，請明確說明阻礙，不要捏造連結。',
        '輸出請只給可被 Chat D 寫入 template_proposals 的 JSON：project_id、expires_at、proposals[3]。',
        '每筆 proposal 必須包含 proposal_id、proposal_code、proposal_name、primary_template_id、secondary_template_id、source_url、secondary_source_url、visual_direction、suitable_reason、canva_url、screenshot_url、status、expires_at。',
        'expires_at 請使用：' . $expiresAt,
        '不要改動後台或資料庫檔案。Chat D 只負責資料庫、後台、狀態、通知紀錄，不改動版型設計方向。',
        '',
        'payload.raw_payload:',
        chat_d_json($rawPayload),
    ));
}

function chat_a_trigger_prompt_summary($payload)
{
    return '已建立 Chat A prompt payload：project_id='
        . (isset($payload['project_id']) ? $payload['project_id'] : '')
        . '，callback='
        . (isset($payload['chat_d_callback']['template_proposals_endpoint']) ? $payload['chat_d_callback']['template_proposals_endpoint'] : '');
}

function chat_a_trigger_post($webhookUrl, $payload)
{
    $ch = curl_init($webhookUrl);
    if (!$ch) {
        return array('ok' => false, 'status' => 'failed', 'message' => 'curl_init_failed');
    }

    $headers = array('Content-Type: application/json');
    $secret = chat_a_trigger_config('CHAT_A_TRIGGER_SECRET');
    if ($secret !== '') {
        $headers[] = 'X-Chat-A-Trigger-Secret: ' . $secret;
    }

    $timeout = (int) chat_a_trigger_config('CHAT_A_TRIGGER_TIMEOUT');
    if ($timeout <= 0) {
        $timeout = 3;
    }

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($body === false || $status < 200 || $status >= 300) {
        return array(
            'ok' => false,
            'status' => 'failed',
            'http_status' => $status,
            'message' => $error !== '' ? $error : 'http_' . $status,
        );
    }

    return array('ok' => true, 'status' => 'sent', 'http_status' => $status);
}

function chat_a_trigger_log($project, $type, $status, $message)
{
    if (!is_array($project) || empty($project['project_id'])) {
        return;
    }

    chat_d_log_notification(
        $project['project_id'],
        isset($project['client_id']) ? (int) $project['client_id'] : null,
        $type,
        'chat_a',
        '',
        array(),
        $message,
        $status
    );
}

function chat_a_trigger_config($name)
{
    if (defined($name)) {
        return trim((string) constant($name));
    }

    $value = getenv($name);
    if ($value !== false && trim((string) $value) !== '') {
        return trim((string) $value);
    }

    if (function_exists('admission_config')) {
        $config = admission_config();
        $key = strtolower($name);
        if (isset($config[$key])) {
            return trim((string) $config[$key]);
        }
    }

    return '';
}
