<?php

require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_once dirname(__FILE__) . '/../lib/template_proposal_flow.php';

function chat_d_admin_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$projects = array();
$notifications = array();
$missingTables = array();

foreach (array('course_projects', 'template_proposals', 'notification_logs') as $tableName) {
    if (!chat_d_table_exists($tableName)) {
        $missingTables[] = $tableName;
    }
}

if (empty($missingTables)) {
    $projects = db_all(
        'SELECT
            p.project_id,
            p.client_id,
            p.intake_id,
            p.course_name,
            p.project_status,
            p.template_status,
            p.selected_proposal_id,
            p.selected_template_id,
            p.selected_secondary_template_id,
            p.selected_canva_url,
            p.template_selected_at,
            p.preview_expires_at,
            p.updated_at,
            COUNT(tp.id) AS proposal_count
         FROM course_projects p
         LEFT JOIN template_proposals tp ON tp.project_id = p.project_id
         GROUP BY p.project_id
         ORDER BY p.updated_at DESC
         LIMIT 100',
        '',
        array()
    );

    $notifications = db_all(
        'SELECT project_id, notification_type, channel, sent_status, sent_at, created_at
         FROM notification_logs
         ORDER BY created_at DESC
         LIMIT 20',
        '',
        array()
    );
}
?>
<!doctype html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Canva 樣板提案狀態 - 課程招生系統</title>
  <style>
    body {
      margin: 0;
      background: #f6f7f9;
      color: #1f2933;
      font-family: Arial, "Noto Sans TC", sans-serif;
    }
    main {
      max-width: 1180px;
      margin: 0 auto;
      padding: 32px 20px;
    }
    h1 {
      margin: 0 0 18px;
      font-size: 24px;
    }
    h2 {
      margin: 28px 0 12px;
      font-size: 18px;
    }
    .notice {
      margin: 0 0 18px;
      padding: 12px 14px;
      border: 1px solid #f2c94c;
      background: #fff9db;
      border-radius: 8px;
    }
    .table-wrap {
      overflow-x: auto;
      background: #fff;
      border: 1px solid #d9e2ec;
      border-radius: 8px;
    }
    table {
      width: 100%;
      min-width: 980px;
      border-collapse: collapse;
      font-size: 14px;
    }
    th,
    td {
      padding: 12px 14px;
      border-bottom: 1px solid #e4e7eb;
      text-align: left;
      vertical-align: top;
    }
    th {
      background: #f0f3f7;
      color: #334e68;
      white-space: nowrap;
    }
    tr:last-child td {
      border-bottom: 0;
    }
    .muted {
      color: #7b8794;
    }
    .status {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 999px;
      background: #e3f8ff;
      color: #0b7285;
      font-size: 12px;
      white-space: nowrap;
    }
    a {
      color: #1d4ed8;
    }
  </style>
</head>
<body>
<main>
  <h1>Canva 樣板提案狀態</h1>

  <?php if (!empty($missingTables)) { ?>
    <div class="notice">尚未建立資料表：<?= chat_d_admin_h(implode(', ', $missingTables)) ?>。請先執行 migration 002。</div>
  <?php } ?>

  <div class="table-wrap">
    <table>
      <thead>
      <tr>
        <th>Project</th>
        <th>課程</th>
        <th>狀態</th>
        <th>提案數</th>
        <th>選定樣板</th>
        <th>到期 / 更新</th>
      </tr>
      </thead>
      <tbody>
      <?php if (empty($projects)) { ?>
        <tr><td colspan="6" class="muted">目前沒有 Canva 樣板提案資料。</td></tr>
      <?php } ?>
      <?php foreach ($projects as $project) { ?>
        <tr>
          <td>
            <?= chat_d_admin_h($project['project_id']) ?><br>
            <span class="muted">Client <?= (int) $project['client_id'] ?> / Intake <?= (int) $project['intake_id'] ?></span>
          </td>
          <td><?= chat_d_admin_h($project['course_name']) ?></td>
          <td>
            <span class="status"><?= chat_d_admin_h($project['template_status']) ?></span><br>
            <span class="muted"><?= chat_d_admin_h($project['project_status']) ?></span>
          </td>
          <td><?= (int) $project['proposal_count'] ?></td>
          <td>
            <?= chat_d_admin_h($project['selected_proposal_id']) ?><br>
            <span class="muted"><?= chat_d_admin_h($project['selected_template_id']) ?></span><br>
            <span class="muted"><?= chat_d_admin_h($project['selected_secondary_template_id']) ?></span>
            <?php if (!empty($project['selected_canva_url'])) { ?><br><a href="<?= chat_d_admin_h($project['selected_canva_url']) ?>" target="_blank">Canva</a><?php } ?>
          </td>
          <td>
            <?= chat_d_admin_h($project['preview_expires_at']) ?><br>
            <span class="muted"><?= chat_d_admin_h($project['updated_at']) ?></span>
          </td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
  </div>

  <h2>通知紀錄</h2>
  <div class="table-wrap">
    <table>
      <thead>
      <tr>
        <th>Project</th>
        <th>類型</th>
        <th>Channel</th>
        <th>狀態</th>
        <th>時間</th>
      </tr>
      </thead>
      <tbody>
      <?php if (empty($notifications)) { ?>
        <tr><td colspan="5" class="muted">目前沒有通知紀錄。</td></tr>
      <?php } ?>
      <?php foreach ($notifications as $notice) { ?>
        <tr>
          <td><?= chat_d_admin_h($notice['project_id']) ?></td>
          <td><?= chat_d_admin_h($notice['notification_type']) ?></td>
          <td><?= chat_d_admin_h($notice['channel']) ?></td>
          <td><?= chat_d_admin_h($notice['sent_status']) ?></td>
          <td><?= chat_d_admin_h($notice['sent_at'] ? $notice['sent_at'] : $notice['created_at']) ?></td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
  </div>
</main>
</body>
</html>
