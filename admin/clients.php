<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action', '') === 'delete') {
    verify_csrf();
    $result = client_delete((int) post('client_id', 0));
    $_SESSION['flash'] = $result['message'];
    redirect('clients.php');
}

$q = trim(get('q', ''));
$clients = client_search($q);
include dirname(__FILE__) . '/../templates/admin-header.php';
?>
<h1>客戶</h1>
<style>
  .clients-table th,
  .clients-table td,
  .clients-table .muted,
  .clients-table a,
  .clients-table .status {
    font-size: 14px;
    line-height: 1.45;
  }
  .row-actions { display: flex; gap: 8px; align-items: center; white-space: nowrap; }
  .inline-delete { display: inline; margin: 0; }
  .action-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 24px;
    padding: 3px 9px;
    border: 0;
    border-radius: 999px;
    background: rgba(255, 255, 255, .70);
    color: #28323b;
    box-shadow: none;
    white-space: nowrap;
    font: inherit;
    font-size: 14px;
    line-height: 1.5;
    text-decoration: none;
    cursor: pointer;
  }
  .action-pill:hover { background: rgba(255, 255, 255, .90); color: #151a24; }
  .action-pill-danger { color: #b42318; }
  .action-pill-danger:hover { color: #7a271a; }
</style>

<form class="panel filters" method="get">
  <div class="filter-grid compact">
    <div>
      <label>搜尋客戶</label>
      <input name="q" value="<?php echo h($q); ?>" placeholder="客戶、聯絡人、電話、Email、LINE、LINE ID Link">
    </div>
    <div class="filter-actions">
      <button type="submit">搜尋</button>
      <a class="button secondary" href="clients.php">清除</a>
    </div>
  </div>
</form>
<div class="actions"><a class="button" href="client-edit.php">新增客戶</a></div>
<table class="clients-table">
  <tr><th>紀錄</th><th>名稱</th><th>聯絡人</th><th>電話</th><th>Email</th><th>LINE</th><th>LINE ID Link</th><th>覆核</th><th>額度</th><th>狀態</th><th></th></tr>
  <?php foreach ($clients as $client) { ?>
    <tr>
      <td><?php echo h(isset($client['record_id']) ? $client['record_id'] : ''); ?></td>
      <td><?php echo h($client['name']); ?></td>
      <td><?php echo h($client['contact_name']); ?></td>
      <td><?php echo h($client['phone']); ?></td>
      <td><?php echo h($client['email']); ?></td>
      <td>
        <?php if (!empty($client['line_id'])) { ?>
          <?php echo h($client['line_id']); ?>
        <?php } elseif (!empty($client['line_user_id'])) { ?>
          <span class="muted"><?php echo h($client['line_user_id']); ?></span>
        <?php } else { ?>
          <span class="muted">-</span>
        <?php } ?>
      </td>
      <td><?php if (!empty($client['line_id_link'])) { ?><a target="_blank" rel="noopener" href="<?php echo h($client['line_id_link']); ?>">LINE ID Link</a><?php } else { ?><span class="muted">未填</span><?php } ?></td>
      <td><?php echo !empty($client['needs_human_contact_review']) ? '需人工覆核' : '-'; ?></td>
      <td><?php echo empty($client['project_limit']) ? '不限制' : h($client['project_limit']); ?></td>
      <td><span class="status"><?php echo h(client_status_label($client['status'])); ?></span></td>
      <td>
        <div class="row-actions">
          <a class="action-pill" href="client-edit.php?id=<?php echo (int) $client['id']; ?>">編輯</a>
          <form method="post" class="inline-delete" data-confirm="<?php echo h('確定要刪除客戶「' . $client['name'] . '」嗎？此動作無法復原。'); ?>" onsubmit="return confirm(this.getAttribute('data-confirm'));">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="client_id" value="<?php echo (int) $client['id']; ?>">
            <button type="submit" class="action-pill action-pill-danger">刪除</button>
          </form>
        </div>
      </td>
    </tr>
  <?php } ?>
  <?php if (empty($clients)) { ?><tr><td colspan="11" class="muted">目前沒有符合條件的客戶。</td></tr><?php } ?>
</table>
<?php include dirname(__FILE__) . '/../templates/admin-footer.php'; ?>
