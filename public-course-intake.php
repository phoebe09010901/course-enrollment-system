<?php
require_once dirname(__FILE__) . '/lib/bootstrap.php';
require_once dirname(__FILE__) . '/lib/template_proposal_flow.php';
require_once dirname(__FILE__) . '/lib/chat_a_trigger.php';

$errors = array();
$success = false;
$recordId = '';
$security = course_form_security_state();

$values = array(
    'user_name' => '',
    'email' => '',
    'line_id_link' => '',
    'course_name' => '',
    'course_type' => '',
    'course_format' => '',
    'course_location' => '',
    'expected_launch_start_date' => '',
    'expected_launch_end_date' => '',
    'expected_course_start_date' => '',
    'expected_course_end_date' => '',
    'course_capacity' => '',
    'course_price' => '',
    'target_audience' => '',
    'course_features' => '',
    'post_course_support' => '',
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $default) {
        $values[$key] = post($key, $default);
    }

    if (!course_public_csrf_is_valid()) {
        $errors[] = '表單已逾時或頁面資料已更新，請重新整理頁面後再送出。';
        $security = reset_course_form_security_state();
        $_SESSION['csrf_token'] = sha1(uniqid('', true) . mt_rand());
    } else {
        $errors = validate_course_form_security();
        register_course_form_attempt();

        if (empty($errors)) {
            $errors = validate_course_intake_form($values);
            $errors = array_merge($errors, validate_course_photo_uploads());
        }

        if (empty($errors)) {
            $saveResult = save_course_intake_form($values);
            try {
                chat_a_trigger_for_project($saveResult['project_id']);
            } catch (Exception $triggerError) {
                error_log('[public-course-intake] chat_a_trigger_failed ' . $triggerError->getMessage());
            }
            register_course_form_success();
            $selectionUrl = chat_d_project_selection_url($saveResult['project_id']);
            if ($selectionUrl !== '') {
                header('Location: ' . $selectionUrl);
                exit;
            }

            $success = true;
            $recordId = $saveResult['record_id'];
            $security = reset_course_form_security_state();

            foreach ($values as $key => $default) {
                $values[$key] = '';
            }
        }
    }
}

function course_public_csrf_is_valid()
{
    $token = post('csrf_token', '');
    return $token !== ''
        && !empty($_SESSION['csrf_token'])
        && safe_equals($_SESSION['csrf_token'], $token);
}

function course_form_security_state()
{
    if (empty($_SESSION['course_form_token']) || empty($_SESSION['course_form_started_at'])) {
        return reset_course_form_security_state();
    }

    return array(
        'token' => $_SESSION['course_form_token'],
        'started_at' => (int) $_SESSION['course_form_started_at'],
    );
}

function reset_course_form_security_state()
{
    $_SESSION['course_form_token'] = sha1(uniqid('', true) . mt_rand());
    $_SESSION['course_form_started_at'] = time();

    return array(
        'token' => $_SESSION['course_form_token'],
        'started_at' => (int) $_SESSION['course_form_started_at'],
    );
}

function validate_course_form_security()
{
    $errors = array();
    $postedToken = post('course_form_token', '');
    $honeypot = post('website_url', '');

    if ($honeypot !== '') {
        $errors[] = '送出失敗，請重新整理頁面後再試。';
    }

    if (
        $postedToken === ''
        || empty($_SESSION['course_form_token'])
        || !safe_equals($_SESSION['course_form_token'], $postedToken)
    ) {
        $errors[] = '表單已逾時，請重新整理頁面後再送出。';
    }

    $startedAt = empty($_SESSION['course_form_started_at']) ? 0 : (int) $_SESSION['course_form_started_at'];
    if ($startedAt <= 0 || time() - $startedAt < 5) {
        $errors[] = '送出速度過快，請確認資料後再送出。';
    }

    if (course_form_recent_attempt_count() >= 8) {
        $errors[] = '短時間送出次數過多，請稍後再試。';
    }

    if (!empty($_SESSION['course_form_last_success_at']) && time() - (int) $_SESSION['course_form_last_success_at'] < 120) {
        $errors[] = '表單已送出，請稍候再送下一筆。';
    }

    if (course_turnstile_is_enabled() && !course_turnstile_verify()) {
        $errors[] = '安全驗證未通過，請重新整理頁面後再試。';
    }

    return array_values(array_unique($errors));
}

function course_turnstile_is_enabled()
{
    return course_config_value('CLOUDFLARE_TURNSTILE_SITE_KEY') !== ''
        && course_config_value('CLOUDFLARE_TURNSTILE_SECRET_KEY') !== '';
}

function course_turnstile_site_key()
{
    return course_config_value('CLOUDFLARE_TURNSTILE_SITE_KEY');
}

function course_turnstile_verify()
{
    $token = post('cf-turnstile-response', '');
    if ($token === '') {
        return false;
    }

    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    if (!$ch) {
        return false;
    }

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
        'secret' => course_config_value('CLOUDFLARE_TURNSTILE_SECRET_KEY'),
        'response' => $token,
        'remoteip' => course_form_client_ip(),
    )));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false || $status < 200 || $status >= 300) {
        error_log('[course-form-turnstile] verify failed status=' . $status . ' error=' . $error);
        return false;
    }

    $result = json_decode($response, true);
    return is_array($result) && !empty($result['success']);
}

function register_course_form_attempt()
{
    $events = course_form_rate_events();
    $events[] = time();
    course_form_write_rate_events($events);
}

function register_course_form_success()
{
    $_SESSION['course_form_last_success_at'] = time();
}

function course_form_recent_attempt_count()
{
    return count(course_form_rate_events());
}

function course_form_rate_events()
{
    $file = course_form_rate_file();
    if (!file_exists($file)) {
        return array();
    }

    $data = json_decode((string) file_get_contents($file), true);
    if (!is_array($data)) {
        return array();
    }

    $cutoff = time() - 1800;
    $events = array();
    foreach ($data as $timestamp) {
        if ((int) $timestamp >= $cutoff) {
            $events[] = (int) $timestamp;
        }
    }

    return $events;
}

function course_form_write_rate_events($events)
{
    $file = course_form_rate_file();
    $dir = dirname($file);

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $cutoff = time() - 1800;
    $fresh = array();
    foreach ($events as $timestamp) {
        if ((int) $timestamp >= $cutoff) {
            $fresh[] = (int) $timestamp;
        }
    }

    file_put_contents($file, json_encode($fresh), LOCK_EX);
}

function course_form_rate_file()
{
    return sys_get_temp_dir() . '/admission-course-form-rate/' . sha1(course_form_client_ip()) . '.json';
}

function course_form_client_ip()
{
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($parts[0]);
    }

    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
}

function validate_course_intake_form($values)
{
    $errors = array();
    $required = array(
        'user_name' => '請填寫姓名。',
        'email' => '請填寫 Email。',
        'line_id_link' => '請填寫 LINE ID Link。',
        'course_name' => '請填寫課程名稱。',
        'course_type' => '請填寫課程類型。',
        'course_format' => '請填寫課程形式。',
        'course_location' => '請填寫上課地點。',
        'expected_launch_start_date' => '請選擇招生日期開始。',
        'expected_launch_end_date' => '請選擇招生日期結束。',
        'expected_course_start_date' => '請選擇上課日期開始。',
        'expected_course_end_date' => '請選擇上課日期結束。',
        'course_capacity' => '請填寫課程名額。',
        'course_price' => '請填寫課程費用。',
        'target_audience' => '請填寫適合對象。',
        'course_features' => '請填寫課程特色說明。',
        'post_course_support' => '請填寫課後支援。',
    );

    foreach ($required as $key => $message) {
        if ($values[$key] === '') {
            $errors[] = $message;
        }
    }

    if ($values['email'] !== '' && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email 格式不正確。';
    }

    if ($values['line_id_link'] !== '' && strpos($values['line_id_link'], 'https://') !== 0) {
        $errors[] = 'LINE ID Link 必須以 https:// 開頭。';
    }

    $today = date('Y-m-d');
    if ($values['expected_launch_start_date'] !== '' && $values['expected_launch_start_date'] <= $today) {
        $errors[] = '招生日期開始必須是未來日期。';
    }

    if ($values['expected_launch_end_date'] !== '' && $values['expected_launch_end_date'] <= $today) {
        $errors[] = '招生日期結束必須是未來日期。';
    }

    if ($values['expected_course_start_date'] !== '' && $values['expected_course_start_date'] <= $today) {
        $errors[] = '上課日期開始必須是未來日期。';
    }

    if ($values['expected_course_end_date'] !== '' && $values['expected_course_end_date'] <= $today) {
        $errors[] = '上課日期結束必須是未來日期。';
    }

    if ($values['expected_launch_start_date'] !== '' && $values['expected_launch_end_date'] !== ''
        && $values['expected_launch_end_date'] < $values['expected_launch_start_date']) {
        $errors[] = '招生日期結束不可早於招生日期開始。';
    }

    if ($values['expected_course_start_date'] !== '' && $values['expected_course_end_date'] !== ''
        && $values['expected_course_end_date'] < $values['expected_course_start_date']) {
        $errors[] = '上課日期結束不可早於上課日期開始。';
    }

    if ($values['expected_launch_end_date'] !== '' && $values['expected_course_start_date'] !== ''
        && $values['expected_course_start_date'] < $values['expected_launch_end_date']) {
        $errors[] = '上課日期開始不可早於招生日期結束。';
    }

    if ($values['course_capacity'] !== '' && !ctype_digit($values['course_capacity'])) {
        $errors[] = '課程名額只能填寫數字。';
    }

    if ($values['course_price'] !== '' && !is_numeric($values['course_price'])) {
        $errors[] = '課程費用只能填寫數字。';
    }

    return $errors;
}

function validate_course_photo_uploads()
{
    $errors = array();
    $rules = course_photo_upload_rules();

    foreach ($rules as $field => $rule) {
        $count = uploaded_photo_count($field);

        if ($count < $rule['min']) {
            $errors[] = $rule['label'] . '至少需要 ' . $rule['min'] . ' 張。';
        }

        if ($count > $rule['max']) {
            $errors[] = $rule['label'] . '最多只能上傳 ' . $rule['max'] . ' 張。';
        }

        if (!isset($_FILES[$field])) {
            continue;
        }

        $fileCount = count($_FILES[$field]['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            $errorCode = (int) $_FILES[$field]['error'][$i];

            if ($errorCode === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($errorCode !== UPLOAD_ERR_OK) {
                $errors[] = $rule['label'] . '第 ' . ($i + 1) . ' 張上傳失敗，請重新選擇。';
                continue;
            }

            if ((int) $_FILES[$field]['size'][$i] > 5 * 1024 * 1024) {
                $errors[] = $rule['label'] . '第 ' . ($i + 1) . ' 張超過 5MB。';
            }

            if (!is_uploaded_file($_FILES[$field]['tmp_name'][$i]) || !getimagesize($_FILES[$field]['tmp_name'][$i])) {
                $errors[] = $rule['label'] . '第 ' . ($i + 1) . ' 張不是可辨識的圖片。';
            }
        }
    }

    return $errors;
}

function course_photo_upload_rules()
{
    return array(
        'topic_photo' => array(
            'label' => '課程主題照',
            'category' => 'topic',
            'min' => 1,
            'max' => 1,
        ),
        'teacher_photos' => array(
            'label' => '老師的照片',
            'category' => 'teacher',
            'min' => 1,
            'max' => 3,
        ),
        'work_photos' => array(
            'label' => '作品的照片',
            'category' => 'works',
            'min' => 1,
            'max' => 10,
        ),
        'classroom_photos' => array(
            'label' => '教室的照片',
            'category' => 'classroom',
            'min' => 1,
            'max' => 5,
        ),
    );
}

function uploaded_photo_count($field)
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field]['name'])) {
        return 0;
    }

    $count = 0;
    foreach ($_FILES[$field]['error'] as $errorCode) {
        if ((int) $errorCode !== UPLOAD_ERR_NO_FILE) {
            $count++;
        }
    }

    return $count;
}

function save_course_intake_form($values)
{
    db()->autocommit(false);

    try {
        $clientId = upsert_form_client($values);
        $recordId = ensure_form_client_record_id($clientId);
        $intakeId = insert_form_course_intake($clientId, $recordId, $values);
        $photoAssets = store_course_photo_uploads($recordId, $intakeId);
        update_form_course_intake_assets($intakeId, $values, $photoAssets);
        $projectId = chat_d_ensure_project_from_intake($clientId, $intakeId, $recordId, $values, $photoAssets);

        db()->commit();
        db()->autocommit(true);

        return array(
            'client_id' => $clientId,
            'record_id' => $recordId,
            'intake_id' => $intakeId,
            'project_id' => $projectId,
        );
    } catch (Exception $e) {
        db()->rollback();
        db()->autocommit(true);
        throw $e;
    }
}

function store_course_photo_uploads($recordId, $intakeId)
{
    $assets = array();
    $baseDir = dirname(__FILE__) . '/public/uploads/course-intakes/' . safe_path_segment($recordId) . '/' . (int) $intakeId;
    $baseUrl = app_url('public/uploads/course-intakes/' . rawurlencode($recordId) . '/' . (int) $intakeId);
    $rules = course_photo_upload_rules();

    foreach ($rules as $field => $rule) {
        $items = array();

        if (!isset($_FILES[$field])) {
            $assets[$field] = $items;
            continue;
        }

        $targetDir = $baseDir . '/' . $rule['category'];
        ensure_upload_dir($targetDir);
        $fileCount = count($_FILES[$field]['name']);
        $photoIndex = 1;

        for ($i = 0; $i < $fileCount; $i++) {
            if ((int) $_FILES[$field]['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $extension = image_extension_from_upload($_FILES[$field]['tmp_name'][$i], $_FILES[$field]['name'][$i]);
            $fileName = $rule['category'] . '-' . str_pad((string) $photoIndex, 2, '0', STR_PAD_LEFT) . '-' . date('His') . '.' . $extension;
            $storage = store_course_photo_file(
                $_FILES[$field]['tmp_name'][$i],
                $targetDir,
                $baseUrl,
                $recordId,
                (int) $intakeId,
                $rule['category'],
                $fileName,
                photo_content_type($extension)
            );

            $items[] = array(
                'original_name' => $_FILES[$field]['name'][$i],
                'file_name' => $fileName,
                'url' => $storage['url'],
                'storage' => $storage['storage'],
                'object_key' => $storage['object_key'],
                'size' => (int) $_FILES[$field]['size'][$i],
                'status' => 'provided',
            );
            $photoIndex++;
        }

        $assets[$field] = $items;
    }

    return $assets;
}

function store_course_photo_file($tmpName, $targetDir, $baseUrl, $recordId, $intakeId, $category, $fileName, $contentType)
{
    if (course_r2_is_configured()) {
        $objectKey = course_r2_object_key($recordId, $intakeId, $category, $fileName);
        course_r2_put_object($objectKey, $tmpName, $contentType);

        return array(
            'storage' => 'cloudflare_r2',
            'object_key' => $objectKey,
            'url' => course_r2_public_url($objectKey),
        );
    }

    ensure_upload_dir($targetDir);
    $targetPath = $targetDir . '/' . $fileName;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        throw new Exception('photo_upload_failed');
    }

    return array(
        'storage' => 'local',
        'object_key' => '',
        'url' => $baseUrl . '/' . rawurlencode($category) . '/' . rawurlencode($fileName),
    );
}

function course_r2_is_configured()
{
    return course_config_value('CLOUDFLARE_R2_ACCOUNT_ID') !== ''
        && course_config_value('CLOUDFLARE_R2_ACCESS_KEY_ID') !== ''
        && course_config_value('CLOUDFLARE_R2_SECRET_ACCESS_KEY') !== ''
        && course_config_value('CLOUDFLARE_R2_BUCKET') !== ''
        && course_config_value('CLOUDFLARE_R2_PUBLIC_BASE_URL') !== '';
}

function course_config_value($name)
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

function course_r2_object_key($recordId, $intakeId, $category, $fileName)
{
    return 'admission-system/course-intakes/'
        . safe_path_segment($recordId) . '/'
        . (int) $intakeId . '/'
        . safe_path_segment($category) . '/'
        . $fileName;
}

function course_r2_public_url($objectKey)
{
    return rtrim(course_config_value('CLOUDFLARE_R2_PUBLIC_BASE_URL'), '/') . '/' . str_replace('%2F', '/', rawurlencode($objectKey));
}

function course_r2_put_object($objectKey, $filePath, $contentType)
{
    $accountId = course_config_value('CLOUDFLARE_R2_ACCOUNT_ID');
    $accessKey = course_config_value('CLOUDFLARE_R2_ACCESS_KEY_ID');
    $secretKey = course_config_value('CLOUDFLARE_R2_SECRET_ACCESS_KEY');
    $bucket = course_config_value('CLOUDFLARE_R2_BUCKET');
    $host = $accountId . '.r2.cloudflarestorage.com';
    $encodedPath = '/' . rawurlencode($bucket) . '/' . str_replace('%2F', '/', rawurlencode($objectKey));
    $url = 'https://' . $host . $encodedPath;
    $amzDate = gmdate('Ymd\THis\Z');
    $dateStamp = gmdate('Ymd');
    $payloadHash = hash_file('sha256', $filePath);
    $canonicalHeaders = 'content-type:' . $contentType . "\n"
        . 'host:' . $host . "\n"
        . 'x-amz-content-sha256:' . $payloadHash . "\n"
        . 'x-amz-date:' . $amzDate . "\n";
    $signedHeaders = 'content-type;host;x-amz-content-sha256;x-amz-date';
    $canonicalRequest = "PUT\n"
        . $encodedPath . "\n\n"
        . $canonicalHeaders . "\n"
        . $signedHeaders . "\n"
        . $payloadHash;
    $credentialScope = $dateStamp . '/auto/s3/aws4_request';
    $stringToSign = "AWS4-HMAC-SHA256\n"
        . $amzDate . "\n"
        . $credentialScope . "\n"
        . hash('sha256', $canonicalRequest);
    $signingKey = course_r2_signature_key($secretKey, $dateStamp);
    $signature = hash_hmac('sha256', $stringToSign, $signingKey);
    $authorization = 'AWS4-HMAC-SHA256 Credential=' . $accessKey . '/' . $credentialScope
        . ', SignedHeaders=' . $signedHeaders
        . ', Signature=' . $signature;

    $ch = curl_init($url);
    $fileHandle = fopen($filePath, 'rb');

    if (!$ch || !$fileHandle) {
        throw new Exception('r2_upload_init_failed');
    }

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_UPLOAD, true);
    curl_setopt($ch, CURLOPT_INFILE, $fileHandle);
    curl_setopt($ch, CURLOPT_INFILESIZE, filesize($filePath));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: ' . $authorization,
        'Content-Type: ' . $contentType,
        'Host: ' . $host,
        'x-amz-content-sha256: ' . $payloadHash,
        'x-amz-date: ' . $amzDate,
    ));

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    fclose($fileHandle);

    if ($response === false || $status < 200 || $status >= 300) {
        error_log('[course-form-r2] upload failed status=' . $status . ' error=' . $error . ' key=' . $objectKey);
        throw new Exception('r2_upload_failed');
    }
}

function course_r2_signature_key($secretKey, $dateStamp)
{
    $dateKey = hash_hmac('sha256', $dateStamp, 'AWS4' . $secretKey, true);
    $dateRegionKey = hash_hmac('sha256', 'auto', $dateKey, true);
    $dateRegionServiceKey = hash_hmac('sha256', 's3', $dateRegionKey, true);

    return hash_hmac('sha256', 'aws4_request', $dateRegionServiceKey, true);
}

function ensure_upload_dir($dir)
{
    if (is_dir($dir)) {
        return;
    }

    if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new Exception('upload_dir_failed');
    }
}

function safe_path_segment($value)
{
    $value = preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) $value);
    $value = trim($value, '-');

    return $value === '' ? 'intake-' . date('YmdHis') : $value;
}

function image_extension_from_upload($tmpName, $originalName)
{
    $image = getimagesize($tmpName);
    if ($image && isset($image[2])) {
        if ((int) $image[2] === IMAGETYPE_JPEG) {
            return 'jpg';
        }
        if ((int) $image[2] === IMAGETYPE_PNG) {
            return 'png';
        }
        if ((int) $image[2] === IMAGETYPE_GIF) {
            return 'gif';
        }
        if (defined('IMAGETYPE_WEBP') && (int) $image[2] === IMAGETYPE_WEBP) {
            return 'webp';
        }
    }

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return preg_match('/^(jpg|jpeg|png|gif|webp)$/', $extension) ? ($extension === 'jpeg' ? 'jpg' : $extension) : 'jpg';
}

function photo_content_type($extension)
{
    $types = array(
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    );

    return isset($types[$extension]) ? $types[$extension] : 'image/jpeg';
}

function update_form_course_intake_assets($intakeId, $values, $photoAssets)
{
    $imageFields = build_form_image_fields($photoAssets);
    $photoStatuses = build_form_photo_statuses($photoAssets);
    $rawPayload = build_form_raw_payload($values, $photoAssets);
    $primaryKey = course_intakes_primary_key();

    db_exec(
        'UPDATE course_intakes
         SET image_fields_json = ?, photo_asset_statuses_json = ?, course_assets_json = ?, raw_payload = ?, updated_at = ?
         WHERE ' . $primaryKey . ' = ?',
        'sssssi',
        array(
            json_encode($imageFields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode($photoStatuses, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode($photoAssets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode($rawPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            now(),
            (int) $intakeId,
        )
    );
}

function course_intakes_primary_key()
{
    $rows = db_all('SHOW COLUMNS FROM course_intakes', '', array());

    foreach ($rows as $row) {
        if (isset($row['Field']) && $row['Field'] === 'intake_id') {
            return 'intake_id';
        }
    }

    return 'id';
}

function build_form_image_fields($photoAssets)
{
    $rules = course_photo_upload_rules();
    $fields = array();

    foreach ($rules as $field => $rule) {
        $items = isset($photoAssets[$field]) ? $photoAssets[$field] : array();
        $fields[$field] = array(
            'label' => $rule['label'],
            'status' => count($items) ? 'provided' : ($rule['min'] > 0 ? 'missing' : 'none'),
            'min' => $rule['min'],
            'max' => $rule['max'],
            'files' => $items,
        );
    }

    return $fields;
}

function build_form_photo_statuses($photoAssets)
{
    $rules = course_photo_upload_rules();
    $statuses = array();

    foreach ($rules as $field => $rule) {
        $items = isset($photoAssets[$field]) ? $photoAssets[$field] : array();
        $statuses[$field] = count($items) ? 'provided' : ($rule['min'] > 0 ? 'missing' : 'none');
    }

    return $statuses;
}

function upsert_form_client($values)
{
    $existing = db_one(
        'SELECT id FROM admission_clients WHERE email = ? OR line_id_link = ? LIMIT 1',
        'ss',
        array($values['email'], $values['line_id_link'])
    );

    $note = "\n\n[表單送出 " . now() . "]\n"
        . '課程名稱：' . $values['course_name'] . "\n"
        . '課程形式：' . $values['course_format'] . "\n";

    if ($existing) {
        $clientId = (int) $existing['id'];
        db_exec(
            'UPDATE admission_clients
             SET name = ?, contact_name = ?, email = ?, email_status = ?, line_id_link = ?, line_id_link_status = ?, note = CONCAT(COALESCE(note, ""), ?), status = ?, updated_at = ?
             WHERE id = ?',
            'sssssssssi',
            array($values['user_name'], $values['user_name'], $values['email'], 'provided', $values['line_id_link'], 'provided', $note, 'active', now(), $clientId)
        );
        return $clientId;
    }

    return db_exec(
        'INSERT INTO admission_clients (record_id, name, contact_name, phone, email, email_status, line_id, line_id_link, line_id_link_status, needs_human_contact_review, note, status, project_limit, created_at, updated_at)
         VALUES (NULL, ?, ?, "", ?, ?, "", ?, ?, 0, ?, ?, NULL, ?, ?)',
        'ssssssssss',
        array($values['user_name'], $values['user_name'], $values['email'], 'provided', $values['line_id_link'], 'provided', $note, 'active', now(), now())
    );
}

function ensure_form_client_record_id($clientId)
{
    $client = db_one('SELECT record_id FROM admission_clients WHERE id = ?', 'i', array((int) $clientId));
    if ($client && !empty($client['record_id'])) {
        return $client['record_id'];
    }

    $recordId = 'CLI-' . date('Ymd') . '-' . str_pad((string) $clientId, 3, '0', STR_PAD_LEFT);
    db_exec('UPDATE admission_clients SET record_id = ?, updated_at = ? WHERE id = ?', 'ssi', array($recordId, now(), (int) $clientId));
    return $recordId;
}

function insert_form_course_intake($clientId, $recordId, $values)
{
    $rawPayload = build_form_raw_payload($values, array());

    return db_exec(
        'INSERT INTO course_intakes (
            client_id, record_id, source, user_name, email, email_status, line_user_id, line_id, line_id_link, line_id_link_status, needs_human_contact_review,
            course_name, course_type, course_format, course_location, expected_launch_date, expected_launch_date_status, expected_start_date, expected_start_date_status, target_audience, course_features,
            image_fields_json, photo_asset_statuses_json, course_assets_json, intake_status, raw_payload, created_at, updated_at
         ) VALUES (?, ?, ?, ?, ?, ?, "", "", ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "[]", "[]", "[]", ?, ?, ?, ?)',
        'isssssssssssssssssssss',
        array(
            (int) $clientId,
            $recordId,
            'public_form',
            $values['user_name'],
            $values['email'],
            'provided',
            $values['line_id_link'],
            'provided',
            $values['course_name'],
            $values['course_type'],
            $values['course_format'],
            $values['course_location'],
            $values['expected_launch_start_date'],
            'provided',
            $values['expected_course_start_date'],
            'provided',
            $values['target_audience'],
            $values['course_features'] . "\n\n課後支援：" . $values['post_course_support'],
            'confirmed',
            json_encode($rawPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            now(),
            now(),
        )
    );
}

function build_form_raw_payload($values, $photoAssets)
{
    return array(
        'type' => 'public_course_intake_form',
        'client' => array(
            'user_name' => $values['user_name'],
            'email' => $values['email'],
            'email_status' => 'provided',
            'line_id_link' => $values['line_id_link'],
            'line_id_link_status' => 'provided',
        ),
        'course_project' => array(
            'course_name' => $values['course_name'],
            'course_type' => $values['course_type'],
            'course_format' => $values['course_format'],
            'course_location' => $values['course_location'],
            'expected_launch_start_date' => $values['expected_launch_start_date'],
            'expected_launch_end_date' => $values['expected_launch_end_date'],
            'expected_course_start_date' => $values['expected_course_start_date'],
            'expected_course_end_date' => $values['expected_course_end_date'],
            'expected_launch_date' => $values['expected_launch_start_date'],
            'expected_launch_date_status' => 'provided',
            'expected_start_date' => $values['expected_course_start_date'],
            'expected_start_date_status' => 'provided',
            'course_capacity' => $values['course_capacity'],
            'course_price' => $values['course_price'],
            'target_audience' => $values['target_audience'],
            'course_features' => $values['course_features'],
            'post_course_support' => $values['post_course_support'],
        ),
        'image_fields' => build_form_image_fields($photoAssets),
        'photo_asset_statuses' => build_form_photo_statuses($photoAssets),
        'course_assets' => $photoAssets,
    );
}

$tomorrow = date('Y-m-d', strtotime('+1 day'));
?>
<!doctype html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>課程資料表單｜菲兔麥 課程招生 - 系統 V.1</title>
  <link rel="stylesheet" href="public/assets/css/admin.css?v=2026052607">
  <?php if (course_turnstile_is_enabled()) { ?><script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script><?php } ?>
  <style>
    body.form-body {
      min-height: 100vh;
      color: #15201b;
      background:
        radial-gradient(circle at 15% 12%, rgba(118, 221, 167, .30), transparent 30%),
        radial-gradient(circle at 88% 8%, rgba(255, 198, 109, .24), transparent 28%),
        radial-gradient(circle at 74% 82%, rgba(116, 166, 255, .18), transparent 36%),
        #f5f7f2;
      font-family: Arial, "Noto Sans TC", sans-serif;
    }

    .form-screen {
      width: min(1175px, 100%);
      margin: 0 auto;
      padding: 44px 22px 34px;
    }

    .form-card {
      border: 1px solid rgba(255, 255, 255, .82);
      border-radius: 28px;
      padding: 30px;
      background:
        radial-gradient(circle at 18% 0%, rgba(175, 230, 181, .74), transparent 31%),
        radial-gradient(circle at 94% 18%, rgba(255, 210, 134, .58), transparent 28%),
        linear-gradient(145deg, rgba(255, 255, 255, .92), rgba(255, 255, 255, .66));
      box-shadow: 0 24px 70px rgba(72, 92, 82, .13);
    }

    .form-heading {
      display: grid;
      gap: 8px;
      margin-bottom: 24px;
    }

    .form-heading span {
      color: #497b48;
      font-size: 12px;
      text-transform: uppercase;
    }

    .form-heading h1 {
      margin: 0;
      color: #15201b;
      font-size: 30px;
      line-height: 1.2;
    }

    .form-heading p {
      max-width: 680px;
      margin: 0;
      color: rgba(21, 32, 27, .64);
      font-size: 14px;
    }

    .form-section {
      margin-top: 18px;
      padding-top: 18px;
      border-top: 1px solid rgba(62, 93, 72, .13);
    }

    .form-section h2 {
      margin: 0 0 12px;
      color: #20362a;
      font-size: 18px;
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 16px;
    }

    .form-field.full {
      grid-column: 1 / -1;
    }

    .form-card label {
      margin-top: 0;
      color: rgba(21, 32, 27, .78);
      font-size: 13px;
    }

    .form-card input,
    .form-card select,
    .form-card textarea {
      margin-top: 8px;
      min-height: 46px;
      border: 1px solid rgba(87, 116, 94, .22);
      border-radius: 15px;
      padding: 12px 14px;
      background: rgba(255, 255, 255, .74);
      color: #15201b;
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, .82);
    }

    .form-card input[type="file"] {
      padding: 10px;
      background: rgba(255, 255, 255, .62);
    }

    .form-card textarea {
      min-height: 118px;
      resize: vertical;
    }

    .form-card input:focus,
    .form-card select:focus,
    .form-card textarea:focus {
      outline: 3px solid rgba(113, 190, 126, .24);
      border-color: rgba(77, 145, 91, .58);
    }

    .form-hint {
      margin: 7px 0 0;
      color: rgba(21, 32, 27, .56);
      font-size: 12px;
      line-height: 1.45;
    }

    .form-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      align-items: center;
      margin-top: 24px;
    }

    .turnstile-wrap {
      margin-top: 20px;
    }

    .form-submit {
      min-width: 180px;
      min-height: 48px;
      border-color: rgba(255, 255, 255, .82);
      background: linear-gradient(135deg, rgba(118, 221, 167, .94), rgba(255, 198, 109, .86));
      color: #15201b;
    }

    .form-reset {
      color: rgba(21, 32, 27, .62);
      background: rgba(255, 255, 255, .76);
    }

    .form-notice {
      border: 1px solid rgba(59, 130, 83, .22);
      background: rgba(237, 252, 240, .86);
      color: #235c36;
    }

    .form-error {
      border: 1px solid rgba(190, 78, 42, .28);
      background: linear-gradient(135deg, rgba(255, 247, 237, .96), rgba(254, 226, 226, .88));
      color: #7c2d12;
    }

    .form-error ul {
      margin: 8px 0 0;
      padding-left: 20px;
    }

    .bot-field {
      position: absolute;
      left: -10000px;
      width: 1px;
      height: 1px;
      overflow: hidden;
    }

    .asset-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 14px;
      margin-top: 8px;
    }

    .asset-field {
      min-height: 156px;
      padding: 14px;
      border: 1px solid rgba(87, 116, 94, .16);
      border-radius: 18px;
      background: rgba(255, 255, 255, .46);
    }

    .asset-field strong {
      display: block;
      color: #20362a;
      font-size: 14px;
    }

    @media (max-width: 760px) {
      .form-screen {
        padding: 24px 16px;
      }

      .form-card {
        padding: 22px;
        border-radius: 24px;
      }

      .form-grid,
      .asset-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body class="form-body">
  <main class="form-screen">
    <form class="form-card" method="post" id="courseIntakeForm" enctype="multipart/form-data" novalidate>
      <div class="form-heading">
        <span>Course Intake Form</span>
        <h1>課程資料表單</h1>
        <p>請填寫課程與聯絡資料，我們會依據內容建立案件並安排後續招生頁製作流程。</p>
      </div>

      <?php if ($success) { ?>
        <div class="notice form-notice">資料已送出，案件編號：<?php echo h($recordId); ?>。</div>
      <?php } ?>

      <?php if (!empty($errors)) { ?>
        <div class="notice form-error">
          請確認以下欄位：
          <ul>
            <?php foreach ($errors as $error) { ?><li><?php echo h($error); ?></li><?php } ?>
          </ul>
        </div>
      <?php } ?>

      <?php echo csrf_field(); ?>
      <input type="hidden" name="course_form_token" value="<?php echo h($security['token']); ?>">
      <label class="bot-field" aria-hidden="true">Website
        <input type="text" name="website_url" tabindex="-1" autocomplete="off">
      </label>

      <section class="form-section" aria-labelledby="contactTitle">
        <h2 id="contactTitle">基本聯絡資料</h2>
        <div class="form-grid">
          <label class="form-field">姓名
            <input type="text" name="user_name" value="<?php echo h($values['user_name']); ?>" required autocomplete="name">
          </label>
          <label class="form-field">Email
            <input type="email" name="email" value="<?php echo h($values['email']); ?>" required autocomplete="email">
            <p class="form-hint">請填寫可收信的 Email。</p>
          </label>
          <label class="form-field full">LINE ID Link
            <input type="url" name="line_id_link" value="<?php echo h($values['line_id_link']); ?>" required pattern="https://.*" placeholder="https://line.me/ti/p/xxxx">
            <p class="form-hint">必須以 https:// 開頭，將來可用於 LINE AI 客服串接與案件追蹤。</p>
          </label>
        </div>
      </section>

      <section class="form-section" aria-labelledby="courseTitle">
        <h2 id="courseTitle">課程基本資料</h2>
        <div class="form-grid">
          <label class="form-field">課程名稱
            <input type="text" name="course_name" value="<?php echo h($values['course_name']); ?>" required>
          </label>
          <label class="form-field">課程類型
            <input type="text" name="course_type" value="<?php echo h($values['course_type']); ?>" required>
            <p class="form-hint">例如：水彩、壓克力、油畫、色鉛、色鉛筆、書法、手作、烘焙、瑜珈、舞蹈、音樂、語言、親子、營養宣導、企業內訓、講座等。</p>
          </label>
          <label class="form-field">課程形式
            <select name="course_format" required>
              <option value="">請選擇課程形式</option>
              <option value="線上" <?php echo $values['course_format'] === '線上' ? 'selected' : ''; ?>>線上</option>
              <option value="實體" <?php echo $values['course_format'] === '實體' ? 'selected' : ''; ?>>實體</option>
              <option value="兩者都有" <?php echo $values['course_format'] === '兩者都有' ? 'selected' : ''; ?>>兩者都有</option>
            </select>
          </label>
          <label class="form-field">上課地點
            <input type="text" name="course_location" value="<?php echo h($values['course_location']); ?>" required>
          </label>
          <label class="form-field">招生日期 開始
            <input type="date" name="expected_launch_start_date" value="<?php echo h($values['expected_launch_start_date']); ?>" min="<?php echo h($tomorrow); ?>" required>
            <p class="form-hint">限定未來日期。</p>
          </label>
          <label class="form-field">招生日期 結束
            <input type="date" name="expected_launch_end_date" value="<?php echo h($values['expected_launch_end_date']); ?>" min="<?php echo h($tomorrow); ?>" required>
            <p class="form-hint">不可早於招生日期開始。</p>
          </label>
          <label class="form-field">上課日期 開始
            <input type="date" name="expected_course_start_date" value="<?php echo h($values['expected_course_start_date']); ?>" min="<?php echo h($tomorrow); ?>" required>
            <p class="form-hint">限定未來日期，且不可早於招生日期結束。</p>
          </label>
          <label class="form-field">上課日期 結束
            <input type="date" name="expected_course_end_date" value="<?php echo h($values['expected_course_end_date']); ?>" min="<?php echo h($tomorrow); ?>" required>
            <p class="form-hint">客戶招生網頁會在課程結束後兩天下架。</p>
          </label>
          <label class="form-field">課程名額
            <input type="number" name="course_capacity" value="<?php echo h($values['course_capacity']); ?>" min="1" step="1" inputmode="numeric" required>
          </label>
          <label class="form-field">課程費用
            <input type="number" name="course_price" value="<?php echo h($values['course_price']); ?>" min="0" step="1" inputmode="numeric" required>
          </label>
          <label class="form-field full">適合對象
            <textarea name="target_audience" required><?php echo h($values['target_audience']); ?></textarea>
            <p class="form-hint">請盡量詳細描述適合的年齡、程度、需求、學習目標或常見情境，越詳盡越好。</p>
          </label>
          <label class="form-field full">課程特色說明
            <textarea name="course_features" required><?php echo h($values['course_features']); ?></textarea>
            <p class="form-hint">請盡量詳細說明課程亮點、教學方式、作品成果、材料特色或與其他課程不同之處，越詳盡越好。</p>
          </label>
          <label class="form-field full">課後支援
            <textarea name="post_course_support" required><?php echo h($values['post_course_support']); ?></textarea>
            <p class="form-hint">例如：是否有影片提供、作品帶回、課後問答等。</p>
          </label>
        </div>
      </section>

      <section class="form-section" aria-labelledby="assetTitle">
        <h2 id="assetTitle">照片素材</h2>
        <div class="asset-grid">
          <label class="asset-field">課程主題照
            <strong>1 張</strong>
            <input type="file" name="topic_photo[]" accept="image/*" required data-min-files="1" data-max-files="1" data-label="課程主題照">
            <p class="form-hint">請上傳最能代表課程氛圍或主視覺的照片，每張 5MB 以內。</p>
          </label>
          <label class="asset-field">老師的照片
            <strong>1-3 張</strong>
            <input type="file" name="teacher_photos[]" accept="image/*" multiple required data-min-files="1" data-max-files="3" data-label="老師的照片">
            <p class="form-hint">請上傳老師個人照、授課照或形象照，每張 5MB 以內。</p>
          </label>
          <label class="asset-field">作品的照片
            <strong>1-10 張</strong>
            <input type="file" name="work_photos[]" accept="image/*" multiple required data-min-files="1" data-max-files="10" data-label="作品的照片">
            <p class="form-hint">請上傳成品、學生作品或課程成果照，每張 5MB 以內。</p>
          </label>
          <label class="asset-field">教室的照片
            <strong>1-5 張</strong>
            <input type="file" name="classroom_photos[]" accept="image/*" multiple required data-min-files="1" data-max-files="5" data-label="教室的照片">
            <p class="form-hint">可上傳教室環境、座位、設備或入口照片，每張 5MB 以內。</p>
          </label>
        </div>
      </section>

      <?php if (course_turnstile_is_enabled()) { ?>
        <div class="turnstile-wrap">
          <div class="cf-turnstile" data-sitekey="<?php echo h(course_turnstile_site_key()); ?>" data-action="course-intake"></div>
        </div>
      <?php } ?>

      <div class="form-actions">
        <button class="form-submit" type="submit">送出資料</button>
        <button class="button secondary form-reset" type="reset">清除重填</button>
      </div>
    </form>
    <p class="login-footer-title">菲兔麥 課程招生 - 系統 V.1</p>
  </main>

  <script>
    (function () {
      var form = document.getElementById('courseIntakeForm');
      var launchStartDate = form.elements.expected_launch_start_date;
      var launchEndDate = form.elements.expected_launch_end_date;
      var courseStartDate = form.elements.expected_course_start_date;
      var courseEndDate = form.elements.expected_course_end_date;
      var lineLink = form.elements.line_id_link;
      var fileInputs = form.querySelectorAll('input[type="file"][data-max-files]');

      function setDateMessage() {
        launchEndDate.setCustomValidity('');
        courseStartDate.setCustomValidity('');
        courseEndDate.setCustomValidity('');
        if (launchStartDate.value && launchEndDate.value && launchEndDate.value < launchStartDate.value) {
          launchEndDate.setCustomValidity('招生日期結束不可早於招生日期開始。');
        }
        if (launchEndDate.value && courseStartDate.value && courseStartDate.value < launchEndDate.value) {
          courseStartDate.setCustomValidity('上課日期開始不可早於招生日期結束。');
        }
        if (courseStartDate.value && courseEndDate.value && courseEndDate.value < courseStartDate.value) {
          courseEndDate.setCustomValidity('上課日期結束不可早於上課日期開始。');
        }
      }

      function setLineLinkMessage() {
        lineLink.setCustomValidity('');
        if (lineLink.value && lineLink.value.indexOf('https://') !== 0) {
          lineLink.setCustomValidity('LINE ID Link 必須以 https:// 開頭。');
        }
      }

      function setFileMessage(input) {
        var min = parseInt(input.getAttribute('data-min-files'), 10);
        var max = parseInt(input.getAttribute('data-max-files'), 10);
        var label = input.getAttribute('data-label');
        var count = input.files ? input.files.length : 0;

        input.setCustomValidity('');
        if (count < min) {
          input.setCustomValidity(label + '至少需要 ' + min + ' 張。');
        } else if (count > max) {
          input.setCustomValidity(label + '最多只能上傳 ' + max + ' 張。');
        }
      }

      launchStartDate.addEventListener('change', setDateMessage);
      launchEndDate.addEventListener('change', setDateMessage);
      courseStartDate.addEventListener('change', setDateMessage);
      courseEndDate.addEventListener('change', setDateMessage);
      lineLink.addEventListener('input', setLineLinkMessage);
      for (var i = 0; i < fileInputs.length; i++) {
        fileInputs[i].addEventListener('change', function () {
          setFileMessage(this);
        });
      }

      form.addEventListener('submit', function (event) {
        setDateMessage();
        setLineLinkMessage();
        for (var i = 0; i < fileInputs.length; i++) {
          setFileMessage(fileInputs[i]);
        }

        if (!form.checkValidity()) {
          event.preventDefault();
          form.reportValidity();
        }
      });
    })();
  </script>
</body>
</html>
