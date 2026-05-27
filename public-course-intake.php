<?php
require_once dirname(__FILE__) . '/lib/bootstrap.php';

$errors = array();
$success = false;
$recordId = '';

$values = array(
    'user_name' => '',
    'email' => '',
    'line_id_link' => '',
    'course_name' => '',
    'course_type' => '',
    'course_format' => '',
    'course_location' => '',
    'expected_launch_date' => '',
    'expected_start_date' => '',
    'course_capacity' => '',
    'course_price' => '',
    'target_audience' => '',
    'course_features' => '',
    'post_course_support' => '',
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    foreach ($values as $key => $default) {
        $values[$key] = post($key, $default);
    }

    $errors = validate_course_intake_form($values);

    if (empty($errors)) {
        $saveResult = save_course_intake_form($values);
        $success = true;
        $recordId = $saveResult['record_id'];

        foreach ($values as $key => $default) {
            $values[$key] = '';
        }
    }
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
        'expected_launch_date' => '請選擇預計招生日期。',
        'expected_start_date' => '請選擇預計開課日期。',
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
    if ($values['expected_launch_date'] !== '' && $values['expected_launch_date'] <= $today) {
        $errors[] = '預計招生日期必須是未來日期。';
    }

    if ($values['expected_start_date'] !== '' && $values['expected_start_date'] <= $today) {
        $errors[] = '預計開課日期必須是未來日期。';
    }

    if (
        $values['expected_launch_date'] !== ''
        && $values['expected_start_date'] !== ''
        && $values['expected_launch_date'] === $values['expected_start_date']
    ) {
        $errors[] = '預計開課日期不可等於預計招生日期。';
    }

    if ($values['course_capacity'] !== '' && !ctype_digit($values['course_capacity'])) {
        $errors[] = '課程名額只能填寫數字。';
    }

    if ($values['course_price'] !== '' && !is_numeric($values['course_price'])) {
        $errors[] = '課程費用只能填寫數字。';
    }

    return $errors;
}

function save_course_intake_form($values)
{
    db()->autocommit(false);

    try {
        $clientId = upsert_form_client($values);
        $recordId = ensure_form_client_record_id($clientId);
        $intakeId = insert_form_course_intake($clientId, $recordId, $values);

        db()->commit();
        db()->autocommit(true);

        return array(
            'client_id' => $clientId,
            'record_id' => $recordId,
            'intake_id' => $intakeId,
        );
    } catch (Exception $e) {
        db()->rollback();
        db()->autocommit(true);
        throw $e;
    }
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
    $rawPayload = array(
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
            'expected_launch_date' => $values['expected_launch_date'],
            'expected_launch_date_status' => 'provided',
            'expected_start_date' => $values['expected_start_date'],
            'expected_start_date_status' => 'provided',
            'course_capacity' => $values['course_capacity'],
            'course_price' => $values['course_price'],
            'target_audience' => $values['target_audience'],
            'course_features' => $values['course_features'],
            'post_course_support' => $values['post_course_support'],
        ),
    );

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
            $values['expected_launch_date'],
            'provided',
            $values['expected_start_date'],
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

$tomorrow = date('Y-m-d', strtotime('+1 day'));
?>
<!doctype html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>課程招生資料表單｜菲兔麥</title>
  <link rel="stylesheet" href="public/assets/css/admin.css?v=2026052607">
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
      width: min(980px, 100%);
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
      font-weight: 700;
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
      font-weight: 700;
    }

    .form-card input,
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

    .form-card textarea {
      min-height: 118px;
      resize: vertical;
    }

    .form-card input:focus,
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

    .form-submit {
      min-width: 180px;
      min-height: 48px;
      border-color: rgba(255, 255, 255, .82);
      background: linear-gradient(135deg, rgba(118, 221, 167, .94), rgba(255, 198, 109, .86));
      color: #15201b;
      font-weight: 700;
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

    @media (max-width: 760px) {
      .form-screen {
        padding: 24px 16px;
      }

      .form-card {
        padding: 22px;
        border-radius: 24px;
      }

      .form-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body class="form-body">
  <main class="form-screen">
    <form class="form-card" method="post" id="courseIntakeForm" novalidate>
      <div class="form-heading">
        <span>Course Intake</span>
        <h1>課程招生資料表單</h1>
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
            <input type="text" name="course_type" value="<?php echo h($values['course_type']); ?>" required placeholder="水彩、色鉛筆、營養宣導、瑜珈、烘焙">
            <p class="form-hint">例如：水彩、壓克力、油畫、色鉛、色鉛筆、書法、手作、烘焙、瑜珈、舞蹈、音樂、語言、親子、營養宣導、企業內訓、講座等。</p>
          </label>
          <label class="form-field">課程形式
            <input type="text" name="course_format" value="<?php echo h($values['course_format']); ?>" required placeholder="線上、實體、兩者都有">
            <p class="form-hint">例如：線上、實體、兩者都有。</p>
          </label>
          <label class="form-field">上課地點
            <input type="text" name="course_location" value="<?php echo h($values['course_location']); ?>" required>
          </label>
          <label class="form-field">預計招生日期
            <input type="date" name="expected_launch_date" value="<?php echo h($values['expected_launch_date']); ?>" min="<?php echo h($tomorrow); ?>" required>
            <p class="form-hint">限定未來日期。</p>
          </label>
          <label class="form-field">預計開課日期
            <input type="date" name="expected_start_date" value="<?php echo h($values['expected_start_date']); ?>" min="<?php echo h($tomorrow); ?>" required>
            <p class="form-hint">限定未來日期，且不可等於招生日期。</p>
          </label>
          <label class="form-field">課程名額
            <input type="number" name="course_capacity" value="<?php echo h($values['course_capacity']); ?>" min="1" step="1" inputmode="numeric" required>
          </label>
          <label class="form-field">課程費用
            <input type="number" name="course_price" value="<?php echo h($values['course_price']); ?>" min="0" step="1" inputmode="numeric" required>
          </label>
          <label class="form-field full">適合對象
            <textarea name="target_audience" required><?php echo h($values['target_audience']); ?></textarea>
          </label>
          <label class="form-field full">課程特色說明
            <textarea name="course_features" required><?php echo h($values['course_features']); ?></textarea>
          </label>
          <label class="form-field full">課後支援
            <textarea name="post_course_support" required placeholder="是否有影片提供或是作品帶回"><?php echo h($values['post_course_support']); ?></textarea>
            <p class="form-hint">例如：是否有影片提供、作品帶回、課後問答等。</p>
          </label>
        </div>
      </section>

      <div class="form-actions">
        <button class="form-submit" type="submit">送出資料</button>
        <button class="button secondary form-reset" type="reset">清除重填</button>
      </div>
    </form>
  </main>

  <script>
    (function () {
      var form = document.getElementById('courseIntakeForm');
      var launchDate = form.elements.expected_launch_date;
      var startDate = form.elements.expected_start_date;
      var lineLink = form.elements.line_id_link;

      function setDateMessage() {
        startDate.setCustomValidity('');
        if (launchDate.value && startDate.value && launchDate.value === startDate.value) {
          startDate.setCustomValidity('預計開課日期不可等於預計招生日期。');
        }
      }

      function setLineLinkMessage() {
        lineLink.setCustomValidity('');
        if (lineLink.value && lineLink.value.indexOf('https://') !== 0) {
          lineLink.setCustomValidity('LINE ID Link 必須以 https:// 開頭。');
        }
      }

      launchDate.addEventListener('change', setDateMessage);
      startDate.addEventListener('change', setDateMessage);
      lineLink.addEventListener('input', setLineLinkMessage);

      form.addEventListener('submit', function (event) {
        setDateMessage();
        setLineLinkMessage();

        if (!form.checkValidity()) {
          event.preventDefault();
          form.reportValidity();
        }
      });
    })();
  </script>
</body>
</html>
