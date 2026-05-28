<?php
require_once dirname(__FILE__) . '/lib/bootstrap.php';
require_once dirname(__FILE__) . '/lib/template_proposal_flow.php';

$token = isset($_GET['t']) ? trim((string) $_GET['t']) : '';
$project = chat_d_project_by_selection_token($token);
$errors = array();
$selected = false;
$selectedProposal = null;
$isExpired = false;

if (!$project) {
    http_response_code(404);
} elseif (chat_d_project_is_preview_expired($project)) {
    $isExpired = true;
    chat_d_mark_project_preview_expired($project['project_id']);
    $project = chat_d_project_by_selection_token($token);
    http_response_code(410);
}

if ($project && !$isExpired && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $proposalId = post('proposal_id', '');
    if ($proposalId === '') {
        $errors[] = '請選擇一款樣板。';
    }

    if (empty($errors)) {
        try {
            $selectedProposal = chat_d_select_template_proposal($project['project_id'], $proposalId);
            $selected = true;
            $project = chat_d_project_by_selection_token($token);
        } catch (Exception $error) {
            $errors[] = '樣板選擇失敗，請稍後再試。';
        }
    }
}

$proposals = $project ? chat_d_project_proposals($project['project_id']) : array();

function proposal_page_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function proposal_page_text($value, $fallback)
{
    $value = trim((string) $value);
    return $value === '' ? $fallback : $value;
}
?>
<!doctype html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>選擇 Canva 樣板｜菲兔麥 課程招生</title>
  <link rel="stylesheet" href="public/assets/css/admin.css?v=2026052607">
  <style>
    body.proposal-body {
      min-height: 100vh;
      margin: 0;
      color: #15201b;
      background:
        radial-gradient(circle at 12% 10%, rgba(118, 221, 167, .26), transparent 30%),
        radial-gradient(circle at 90% 6%, rgba(255, 198, 109, .26), transparent 29%),
        #f5f7f2;
      font-family: Arial, "Noto Sans TC", sans-serif;
    }
    .proposal-screen {
      width: min(1080px, 100%);
      margin: 0 auto;
      padding: 44px 22px;
    }
    .proposal-heading {
      display: grid;
      gap: 10px;
      margin-bottom: 24px;
    }
    .proposal-heading span {
      color: #497b48;
      font-size: 12px;
      text-transform: uppercase;
    }
    .proposal-heading h1 {
      margin: 0;
      font-size: 30px;
      line-height: 1.2;
    }
    .proposal-heading p {
      max-width: 720px;
      margin: 0;
      color: rgba(21, 32, 27, .66);
      font-size: 14px;
      line-height: 1.6;
    }
    .notice {
      margin: 0 0 18px;
      padding: 14px 16px;
      border-radius: 16px;
      border: 1px solid rgba(87, 116, 94, .18);
      background: rgba(255, 255, 255, .72);
    }
    .notice.ok {
      border-color: rgba(59, 130, 83, .22);
      background: rgba(237, 252, 240, .86);
      color: #235c36;
    }
    .notice.error {
      border-color: rgba(190, 78, 42, .28);
      background: rgba(254, 226, 226, .88);
      color: #7c2d12;
    }
    .proposal-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 16px;
    }
    .proposal-card {
      display: grid;
      gap: 12px;
      min-height: 280px;
      padding: 18px;
      border: 1px solid rgba(87, 116, 94, .18);
      border-radius: 22px;
      background: rgba(255, 255, 255, .76);
      box-shadow: 0 18px 48px rgba(72, 92, 82, .10);
    }
    .proposal-card h2 {
      margin: 0;
      font-size: 20px;
    }
    .proposal-card p {
      margin: 0;
      color: rgba(21, 32, 27, .68);
      font-size: 14px;
      line-height: 1.55;
    }
    .proposal-meta {
      display: grid;
      gap: 5px;
      color: rgba(21, 32, 27, .58);
      font-size: 12px;
    }
    .proposal-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      align-items: center;
      margin-top: auto;
    }
    .proposal-actions a,
    .proposal-actions button {
      min-height: 42px;
      border: 1px solid rgba(87, 116, 94, .22);
      border-radius: 999px;
      padding: 10px 14px;
      background: rgba(255, 255, 255, .82);
      color: #15201b;
      text-decoration: none;
      cursor: pointer;
    }
    .proposal-actions button {
      background: linear-gradient(135deg, rgba(118, 221, 167, .94), rgba(255, 198, 109, .86));
    }
    .placeholder {
      opacity: .72;
    }
    @media (max-width: 860px) {
      .proposal-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body class="proposal-body">
  <main class="proposal-screen">
    <div class="proposal-heading">
      <span>Canva Template Proposals</span>
      <h1>選擇課程招生頁樣板</h1>
      <?php if ($project) { ?>
        <p><?php echo proposal_page_h($project['course_name']); ?> 的三款 Canva 樣板會顯示在這裡。若目前還沒出現，代表系統已收到資料，正在等待 Chat A / Canva 回填提案。</p>
      <?php } else { ?>
        <p>這個選版連結不存在或已失效。</p>
      <?php } ?>
    </div>

    <?php if (!$project) { ?>
      <div class="notice error">找不到此案件的選版資料，請回到表單重新送出或聯絡客服。</div>
    <?php } else { ?>
      <?php if ($isExpired) { ?>
        <div class="notice error">這個課程招生頁已於課程結束後兩天下架。若需要重新開放，請聯絡客服協助。</div>
      <?php } elseif ($selected) { ?>
        <div class="notice ok">已收到你的選擇：<?php echo proposal_page_h($selectedProposal['proposal_name']); ?>。</div>
      <?php } ?>

      <?php if (!$isExpired && !empty($errors)) { ?>
        <div class="notice error"><?php echo proposal_page_h(implode(' ', $errors)); ?></div>
      <?php } ?>

      <?php if (!$isExpired && count($proposals) < 3) { ?>
        <div class="notice">資料已送出成功。三款 Canva 樣板尚在準備中，完成後會在這個頁面顯示。</div>
      <?php } ?>

      <?php if (!$isExpired) { ?><div class="proposal-grid">
        <?php if (count($proposals) >= 1) { ?>
          <?php foreach ($proposals as $proposal) { ?>
            <article class="proposal-card">
              <h2><?php echo proposal_page_h(proposal_page_text($proposal['proposal_name'], $proposal['proposal_code'] . ' 款')); ?></h2>
              <p><?php echo nl2br(proposal_page_h(proposal_page_text($proposal['visual_direction'], 'Canva 視覺方向待回填。'))); ?></p>
              <p><?php echo nl2br(proposal_page_h(proposal_page_text($proposal['suitable_reason'], '適用理由待回填。'))); ?></p>
              <div class="proposal-meta">
                <span>Primary: <?php echo proposal_page_h($proposal['primary_template_id']); ?></span>
                <span>Secondary: <?php echo proposal_page_h($proposal['secondary_template_id']); ?></span>
                <span>Status: <?php echo proposal_page_h($proposal['status']); ?></span>
              </div>
              <div class="proposal-actions">
                <?php if (!empty($proposal['canva_url'])) { ?><a href="<?php echo proposal_page_h($proposal['canva_url']); ?>" target="_blank">查看 Canva</a><?php } ?>
                <form method="post">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="proposal_id" value="<?php echo proposal_page_h($proposal['proposal_id']); ?>">
                  <button type="submit">選擇這款</button>
                </form>
              </div>
            </article>
          <?php } ?>
        <?php } else { ?>
          <?php foreach (array('A', 'B', 'C') as $code) { ?>
            <article class="proposal-card placeholder">
              <h2><?php echo proposal_page_h($code); ?> 款 Canva 樣板</h2>
              <p>等待 Chat A 依照規則產生 Canva 樣板提案。</p>
              <p>提案完成後，這裡會顯示樣板方向、Canva 連結與選擇按鈕。</p>
            </article>
          <?php } ?>
        <?php } ?>
      </div><?php } ?>
    <?php } ?>
  </main>
</body>
</html>
