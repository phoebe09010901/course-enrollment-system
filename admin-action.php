<?php
require_once dirname(__FILE__) . '/lib/bootstrap.php';
require_once dirname(__FILE__) . '/lib/template_proposal_flow.php';
require_once dirname(__FILE__) . '/lib/chat_a_trigger.php';

$action = isset($_GET['action']) ? trim((string) $_GET['action']) : '';
$projectId = isset($_GET['project_id']) ? trim((string) $_GET['project_id']) : '';
$token = isset($_GET['token']) ? trim((string) $_GET['token']) : '';

$statusCode = 200;
$title = '操作完成';
$message = '';
$ok = false;

try {
    $result = chat_d_admin_action_execute($projectId, $action, $token);
    $ok = !empty($result['ok']);
    $title = isset($result['title']) ? $result['title'] : '操作完成';
    $message = isset($result['message']) ? $result['message'] : '';
} catch (Exception $error) {
    $statusCode = 403;
    $title = '連結無法使用';
    $errorCode = $error->getMessage();
    $messages = array(
        'action_token_required' => '缺少必要參數，請確認通知中的連結是否完整。',
        'unsupported_action' => '這個操作不在允許清單內。',
        'project_not_found' => '找不到這筆案件。',
        'action_token_invalid' => '這個連結無效，可能已被替換或不是系統產生的連結。',
        'action_token_used' => '這個連結已經使用過。',
        'action_token_expired' => '這個連結已超過有效期限。',
        'project_id_required' => '缺少案件編號。',
    );
    $message = isset($messages[$errorCode]) ? $messages[$errorCode] : '操作失敗：' . $errorCode;
}

http_response_code($statusCode);

function admin_action_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo admin_action_h($title); ?>｜菲兔麥 課程招生</title>
  <style>
    body {
      min-height: 100vh;
      margin: 0;
      display: grid;
      place-items: center;
      background: linear-gradient(110deg, #fff7f0 0%, #f7fbff 52%, #e9fbfb 100%);
      color: #151a24;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }
    .result {
      width: min(560px, calc(100% - 40px));
      padding: 34px 32px;
      border-radius: 28px;
      background: rgba(255, 255, 255, .82);
      border: 1px solid rgba(255, 255, 255, .9);
      box-shadow: 0 28px 70px rgba(26, 32, 44, .12);
    }
    .eyebrow {
      margin: 0 0 12px;
      color: <?php echo $ok ? '#0f766e' : '#be123c'; ?>;
      font-size: 13px;
      letter-spacing: .08em;
      text-transform: uppercase;
    }
    h1 {
      margin: 0;
      font-size: clamp(28px, 5vw, 42px);
      line-height: 1.15;
    }
    p {
      margin: 18px 0 0;
      color: #59616f;
      font-size: 17px;
      line-height: 1.75;
    }
    .meta {
      margin-top: 22px;
      padding-top: 18px;
      border-top: 1px solid rgba(89, 97, 111, .16);
      color: #6b7280;
      font-size: 14px;
    }
  </style>
</head>
<body>
  <main class="result">
    <div class="eyebrow"><?php echo $ok ? 'Action completed' : 'Action unavailable'; ?></div>
    <h1><?php echo admin_action_h($title); ?></h1>
    <?php if ($message !== '') { ?>
      <p><?php echo admin_action_h($message); ?></p>
    <?php } ?>
    <?php if ($projectId !== '') { ?>
      <div class="meta">案件編號：<?php echo admin_action_h($projectId); ?></div>
    <?php } ?>
  </main>
</body>
</html>
