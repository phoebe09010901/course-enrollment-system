<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_once dirname(__FILE__) . '/../lib/template_proposal_flow.php';
require_login();

function course_project_edit_table_exists($tableName)
{
    $row = db_one(
        'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1',
        's',
        array($tableName)
    );
    return !empty($row);
}

function course_project_edit_json($value)
{
    $decoded = json_decode((string) $value, true);
    return is_array($decoded) ? $decoded : array();
}

function course_project_edit_value($payload, $key, $default)
{
    if (isset($payload['course_project']) && is_array($payload['course_project']) && isset($payload['course_project'][$key])) {
        return (string) $payload['course_project'][$key];
    }
    return $default;
}

function course_project_edit_assets($payload)
{
    if (isset($payload['course_assets']) && is_array($payload['course_assets'])) {
        return $payload['course_assets'];
    }
    if (isset($payload['course_project']['course_assets']) && is_array($payload['course_project']['course_assets'])) {
        return $payload['course_project']['course_assets'];
    }
    return array();
}

function course_project_edit_asset_label($field)
{
    $labels = array(
        'topic_photo' => '課程主題照',
        'teacher_photos' => '老師照片',
        'work_photos' => '作品照片',
        'classroom_photos' => '教室照片',
    );

    return isset($labels[$field]) ? $labels[$field] : $field;
}

function course_project_edit_asset_url($asset)
{
    foreach (array('url', 'public_url', 'publicUrl', 'source_url', 'sourceUrl') as $key) {
        if (isset($asset[$key]) && trim((string) $asset[$key]) !== '') {
            return trim((string) $asset[$key]);
        }
    }

    return '';
}

function course_project_edit_asset_name($asset, $index)
{
    foreach (array('original_name', 'originalName', 'file_name', 'fileName') as $key) {
        if (isset($asset[$key]) && trim((string) $asset[$key]) !== '') {
            return trim((string) $asset[$key]);
        }
    }

    return '圖片 ' . (string) $index;
}

function course_project_edit_validate_dates($values)
{
    $errors = array();
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

    return $errors;
}

$projectId = trim(get('project_id', ''));
$project = $projectId !== '' ? chat_d_project_by_id($projectId) : null;
if (!$project) {
    $_SESSION['flash'] = '找不到專案。';
    redirect('projects.php');
}

$payload = course_project_edit_json(isset($project['raw_payload']) ? $project['raw_payload'] : '');
$values = array(
    'course_name' => isset($project['course_name']) ? $project['course_name'] : '',
    'course_type' => isset($project['course_type']) ? $project['course_type'] : '',
    'course_format' => isset($project['course_format']) ? $project['course_format'] : '',
    'course_location' => isset($project['course_location']) ? $project['course_location'] : '',
    'expected_launch_start_date' => course_project_edit_value($payload, 'expected_launch_start_date', course_project_edit_value($payload, 'expected_launch_date', '')),
    'expected_launch_end_date' => course_project_edit_value($payload, 'expected_launch_end_date', ''),
    'expected_course_start_date' => course_project_edit_value($payload, 'expected_course_start_date', course_project_edit_value($payload, 'expected_start_date', '')),
    'expected_course_end_date' => course_project_edit_value($payload, 'expected_course_end_date', ''),
    'course_capacity' => course_project_edit_value($payload, 'course_capacity', ''),
    'course_price' => course_project_edit_value($payload, 'course_price', ''),
    'target_audience' => course_project_edit_value($payload, 'target_audience', ''),
    'course_features' => course_project_edit_value($payload, 'course_features', ''),
    'post_course_support' => course_project_edit_value($payload, 'post_course_support', ''),
);
$formErrors = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = post('_action', 'save');
    if ($action === 'regenerate_canva') {
        chat_d_reset_template_generation($projectId);
        $_SESSION['flash'] = '已送出重新產圖要求，專案狀態已改為待樣板提案。';
        redirect('course-project-edit.php?project_id=' . rawurlencode($projectId));
    }

    if ($action === 'send_action_email') {
        $sent = chat_d_send_new_project_admin_email($projectId);
        $_SESSION['flash'] = $sent ? '已寄出操作 Email。' : '操作 Email 未寄出，請確認後台通知 Email 設定或主機 mail() 功能。';
        redirect('course-project-edit.php?project_id=' . rawurlencode($projectId));
    }

    if ($action === 'return_to_selection') {
        try {
            chat_d_return_to_template_selection_stage($projectId);
            $_SESSION['flash'] = '已回到選樣板階段，三款提案仍保留，可重新開啟選版頁。';
        } catch (Exception $error) {
            $_SESSION['flash'] = '無法回到選樣板階段：請確認已有完整三款樣板提案。';
        }
        redirect('course-project-edit.php?project_id=' . rawurlencode($projectId));
    }

    foreach ($values as $key => $default) {
        $values[$key] = post($key, $default);
    }

    if (!isset($payload['course_project']) || !is_array($payload['course_project'])) {
        $payload['course_project'] = array();
    }
    foreach ($values as $key => $value) {
        $payload['course_project'][$key] = $value;
    }
    $payload['course_project']['expected_launch_date'] = $values['expected_launch_start_date'];
    $payload['course_project']['expected_start_date'] = $values['expected_course_start_date'];
    $previewExpiresAt = chat_d_preview_expires_at_from_values($values);

    $formErrors = course_project_edit_validate_dates($values);
    if (empty($formErrors)) {
        db_exec(
            'UPDATE course_projects
             SET course_name = ?, course_type = ?, course_format = ?, course_location = ?, preview_expires_at = ?, raw_payload = ?, updated_at = ?
             WHERE project_id = ?',
            'ssssssss',
            array(
                $values['course_name'],
                $values['course_type'],
                $values['course_format'],
                $values['course_location'],
                $previewExpiresAt,
                chat_d_json($payload),
                now(),
                $projectId,
            )
        );

        $_SESSION['flash'] = '專案資料已儲存。';
        redirect('course-project-edit.php?project_id=' . rawurlencode($projectId));
    }
}

$client = null;
if (!empty($project['client_id']) && course_project_edit_table_exists('admission_clients')) {
    $client = db_one('SELECT * FROM admission_clients WHERE id = ? LIMIT 1', 'i', array((int) $project['client_id']));
}

$proposals = chat_d_project_proposals($projectId);
$assets = course_project_edit_assets($payload);
$adminActionLinks = chat_d_admin_action_create_links($projectId, 24);

include dirname(__FILE__) . '/../templates/admin-header.php';
?>
<h1>專案詳細資料</h1>
<style>
  .detail-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
  .detail-list { display: grid; gap: 8px; margin: 0; }
  .detail-row { display: grid; grid-template-columns: 130px 1fr; gap: 12px; font-size: 14px; line-height: 1.55; }
  .detail-label { color: rgba(21, 26, 36, .56); }
  .detail-value { color: #151a24; word-break: break-word; }
  .form-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
  .asset-list { display: grid; gap: 8px; margin: 0; padding: 0; list-style: none; }
  .asset-list li { font-size: 14px; line-height: 1.55; }
  .inline-action-form { display: inline-flex; margin: 0; }
  .quick-action-links { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
  .quick-action-links .button { text-decoration: none; }
  .proposal-table th,
  .proposal-table td,
  .proposal-table .muted,
  .proposal-table a { font-size: 14px; line-height: 1.45; }
  .asset-group { display: grid; gap: 6px; margin-bottom: 14px; }
  .asset-group-title { color: rgba(21, 26, 36, .70); font-size: 14px; }
  @media (max-width: 760px) {
    .detail-grid { grid-template-columns: 1fr; }
    .detail-row { grid-template-columns: 1fr; gap: 2px; }
  }
</style>

<div class="actions">
  <a class="button secondary" href="projects.php">返回專案列表</a>
  <?php if (!empty($project['selection_token'])) { ?><a class="button secondary" target="_blank" href="../course-template-proposals.php?t=<?php echo h($project['selection_token']); ?>">開啟選版頁</a><?php } ?>
  <?php if (!empty($project['selected_canva_url'])) { ?><a class="button secondary" target="_blank" href="<?php echo h($project['selected_canva_url']); ?>">開啟 Canva</a><?php } ?>
  <form class="inline-action-form" method="post" onsubmit="return confirm('確定要重新產圖嗎？舊的三款 Canva 提案會被移除，專案會回到待樣板提案。');">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="_action" value="regenerate_canva">
    <button type="submit" class="secondary">重新產圖</button>
  </form>
  <form class="inline-action-form" method="post">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="_action" value="send_action_email">
    <button type="submit" class="secondary">寄送操作 Email</button>
  </form>
  <?php if (!empty($project['selected_proposal_id'])) { ?>
    <form class="inline-action-form" method="post" onsubmit="return confirm('確定要回到選樣板階段嗎？目前選定紀錄會清除，但三款提案會保留。');">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="_action" value="return_to_selection">
      <button type="submit" class="secondary">回到選樣板階段</button>
    </form>
  <?php } ?>
</div>

<section class="panel">
  <h2>免登入通知操作連結</h2>
  <p class="muted">這三個連結可放進 LINE / Email 通知；每個連結只對應此專案，24 小時有效，使用後會失效。</p>
  <div class="quick-action-links">
    <?php foreach ($adminActionLinks as $actionName => $actionLink) { ?>
      <a class="button secondary" target="_blank" href="<?php echo h($actionLink['url']); ?>"><?php echo h($actionLink['label']); ?></a>
    <?php } ?>
  </div>
</section>

<?php if (!empty($formErrors)) { ?>
  <div class="alert error"><?php echo h(implode(' ', $formErrors)); ?></div>
<?php } ?>

<section class="panel">
  <h2>流程節點</h2>
  <div class="detail-grid">
    <dl class="detail-list">
      <div class="detail-row"><dt class="detail-label">目前節點</dt><dd class="detail-value"><?php echo h(chat_d_template_status_label(isset($project['template_status']) ? $project['template_status'] : '')); ?></dd></div>
      <div class="detail-row"><dt class="detail-label">選定款式</dt><dd class="detail-value"><?php echo h(isset($project['selected_proposal_id']) ? $project['selected_proposal_id'] : ''); ?></dd></div>
      <div class="detail-row"><dt class="detail-label">選定時間</dt><dd class="detail-value"><?php echo h(isset($project['template_selected_at']) ? $project['template_selected_at'] : ''); ?></dd></div>
    </dl>
    <dl class="detail-list">
      <div class="detail-row"><dt class="detail-label">主樣板</dt><dd class="detail-value"><?php echo h(isset($project['selected_template_id']) ? $project['selected_template_id'] : ''); ?></dd></div>
      <div class="detail-row"><dt class="detail-label">輔助樣板</dt><dd class="detail-value"><?php echo h(isset($project['selected_secondary_template_id']) ? $project['selected_secondary_template_id'] : ''); ?></dd></div>
      <div class="detail-row"><dt class="detail-label">選定 Canva</dt><dd class="detail-value"><?php if (!empty($project['selected_canva_url'])) { ?><a target="_blank" href="<?php echo h($project['selected_canva_url']); ?>"><?php echo h($project['selected_canva_url']); ?></a><?php } ?></dd></div>
    </dl>
  </div>
  <p class="muted">若要回到此階段前一步，請按上方「回到選樣板階段」。系統會保留三款提案，清除選定欄位，狀態回到「樣板已完成」。</p>
</section>

<section class="panel">
  <h2>專案狀態</h2>
  <div class="detail-grid">
    <dl class="detail-list">
      <div class="detail-row"><dt class="detail-label">專案編號</dt><dd class="detail-value"><?php echo h($project['project_id']); ?></dd></div>
      <div class="detail-row"><dt class="detail-label">樣板狀態</dt><dd class="detail-value"><?php echo h(chat_d_template_status_label(isset($project['template_status']) ? $project['template_status'] : '')); ?></dd></div>
      <div class="detail-row"><dt class="detail-label">專案狀態</dt><dd class="detail-value"><?php echo h($project['project_status']); ?></dd></div>
      <div class="detail-row"><dt class="detail-label">批次</dt><dd class="detail-value"><?php echo h(isset($project['proposal_batch_id']) ? $project['proposal_batch_id'] : ''); ?></dd></div>
    </dl>
    <dl class="detail-list">
      <div class="detail-row"><dt class="detail-label">Worker</dt><dd class="detail-value"><?php echo h(isset($project['worker_run_id']) ? $project['worker_run_id'] : ''); ?></dd></div>
      <div class="detail-row"><dt class="detail-label">開始處理</dt><dd class="detail-value"><?php echo h(isset($project['template_processing_started_at']) ? $project['template_processing_started_at'] : ''); ?></dd></div>
      <div class="detail-row"><dt class="detail-label">錯誤</dt><dd class="detail-value"><?php echo h(chat_d_template_error_label(isset($project['template_error_code']) ? $project['template_error_code'] : '')); ?><?php echo !empty($project['template_error_message']) ? '：' . h($project['template_error_message']) : ''; ?></dd></div>
      <div class="detail-row"><dt class="detail-label">招生頁下架</dt><dd class="detail-value"><?php echo h(isset($project['preview_expires_at']) ? $project['preview_expires_at'] : ''); ?></dd></div>
      <div class="detail-row"><dt class="detail-label">更新時間</dt><dd class="detail-value"><?php echo h($project['updated_at']); ?></dd></div>
    </dl>
  </div>
</section>

<form class="panel" method="post">
  <?php echo csrf_field(); ?>
  <h2>課程資料</h2>
  <div class="grid">
    <div><label>課程名稱</label><input name="course_name" value="<?php echo h($values['course_name']); ?>" required></div>
    <div><label>課程類型</label><input name="course_type" value="<?php echo h($values['course_type']); ?>"></div>
    <div><label>課程形式</label>
      <select name="course_format">
        <?php foreach (array('', '實體', '線上', '混合', '到府', '企業內訓') as $option) { ?>
          <option value="<?php echo h($option); ?>" <?php echo $values['course_format'] === $option ? 'selected' : ''; ?>><?php echo $option === '' ? '未設定' : h($option); ?></option>
        <?php } ?>
      </select>
    </div>
    <div><label>上課地點</label><input name="course_location" value="<?php echo h($values['course_location']); ?>"></div>
    <div><label>招生日期 開始</label><input type="date" name="expected_launch_start_date" value="<?php echo h($values['expected_launch_start_date']); ?>"></div>
    <div><label>招生日期 結束</label><input type="date" name="expected_launch_end_date" value="<?php echo h($values['expected_launch_end_date']); ?>"></div>
    <div><label>上課日期 開始</label><input type="date" name="expected_course_start_date" value="<?php echo h($values['expected_course_start_date']); ?>"></div>
    <div><label>上課日期 結束</label><input type="date" name="expected_course_end_date" value="<?php echo h($values['expected_course_end_date']); ?>"></div>
    <div><label>課程名額</label><input name="course_capacity" value="<?php echo h($values['course_capacity']); ?>"></div>
    <div><label>課程費用</label><input name="course_price" value="<?php echo h($values['course_price']); ?>"></div>
  </div>
  <label>適合對象</label><textarea name="target_audience"><?php echo h($values['target_audience']); ?></textarea>
  <label>課程特色說明</label><textarea name="course_features"><?php echo h($values['course_features']); ?></textarea>
  <label>課後支援</label><textarea name="post_course_support"><?php echo h($values['post_course_support']); ?></textarea>
  <div class="form-actions">
    <button type="submit">儲存</button>
    <a class="button secondary" href="projects.php">返回</a>
  </div>
</form>

<section class="panel">
  <h2>客戶資料</h2>
  <?php if ($client) { ?>
    <div class="detail-grid">
      <dl class="detail-list">
        <div class="detail-row"><dt class="detail-label">客戶</dt><dd class="detail-value"><?php echo h($client['name']); ?></dd></div>
        <div class="detail-row"><dt class="detail-label">聯絡人</dt><dd class="detail-value"><?php echo h($client['contact_name']); ?></dd></div>
        <div class="detail-row"><dt class="detail-label">Email</dt><dd class="detail-value"><?php echo h($client['email']); ?></dd></div>
      </dl>
      <dl class="detail-list">
        <div class="detail-row"><dt class="detail-label">電話</dt><dd class="detail-value"><?php echo h($client['phone']); ?></dd></div>
        <div class="detail-row"><dt class="detail-label">LINE ID Link</dt><dd class="detail-value"><?php if (!empty($client['line_id_link'])) { ?><a target="_blank" href="<?php echo h($client['line_id_link']); ?>"><?php echo h($client['line_id_link']); ?></a><?php } ?></dd></div>
        <div class="detail-row"><dt class="detail-label">客戶備註</dt><dd class="detail-value"><?php echo nl2br(h($client['note'])); ?></dd></div>
      </dl>
    </div>
  <?php } else { ?>
    <p class="muted">尚未連結客戶。</p>
  <?php } ?>
</section>

<section class="panel">
  <h2>圖片素材</h2>
  <?php if (empty($assets)) { ?>
    <p class="muted">目前沒有圖片素材。</p>
  <?php } else { ?>
    <?php foreach ($assets as $field => $items) {
      if (!is_array($items)) { continue; }
      $isSingleAsset = isset($items['url']) || isset($items['public_url']) || isset($items['publicUrl']);
      $assetItems = $isSingleAsset ? array($items) : $items;
      if (empty($assetItems)) { continue; }
      ?>
      <div class="asset-group">
        <div class="asset-group-title"><?php echo h(course_project_edit_asset_label($field)); ?></div>
        <ul class="asset-list">
          <?php $assetIndex = 1; foreach ($assetItems as $asset) {
            if (!is_array($asset)) { continue; }
            $url = course_project_edit_asset_url($asset);
            $name = course_project_edit_asset_name($asset, $assetIndex);
          ?>
            <li><?php if ($url !== '') { ?><a target="_blank" href="<?php echo h($url); ?>"><?php echo h($name); ?></a><?php } else { ?><span class="muted"><?php echo h($name); ?>：未取得網址</span><?php } ?></li>
          <?php $assetIndex++; } ?>
        </ul>
      </div>
    <?php } ?>
  <?php } ?>
</section>

<section class="panel">
  <h2>Canva 樣板提案</h2>
  <table class="proposal-table">
    <tr><th>款式</th><th>名稱</th><th>樣板編碼</th><th>Canva</th><th>狀態</th></tr>
    <?php foreach ($proposals as $proposal) { ?>
      <tr>
        <td><?php echo h($proposal['proposal_code']); ?></td>
        <td><?php echo h($proposal['proposal_name']); ?></td>
        <td>
          主樣板：<?php echo h($proposal['primary_template_id']); ?><br>
          <span class="muted">輔助樣板：<?php echo h($proposal['secondary_template_id']); ?></span>
        </td>
        <td><?php if (!empty($proposal['canva_url'])) { ?><a target="_blank" href="<?php echo h($proposal['canva_url']); ?>">Canva</a><?php } else { ?><span class="muted">尚未提供</span><?php } ?></td>
        <td><?php echo h(chat_d_proposal_status_label(isset($proposal['status']) ? $proposal['status'] : '')); ?></td>
      </tr>
    <?php } ?>
    <?php if (empty($proposals)) { ?><tr><td colspan="5" class="muted">尚未有樣板提案。</td></tr><?php } ?>
  </table>
</section>
<?php include dirname(__FILE__) . '/../templates/admin-footer.php'; ?>
