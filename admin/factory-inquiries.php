<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_login();

function factory_inquiries_table_exists($tableName)
{
    $row = db_one(
        'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1',
        's',
        array($tableName)
    );
    return !empty($row);
}

function factory_inquiry_status_label($status)
{
    $labels = array(
        'new' => '新諮詢',
        'contacted' => '已聯絡',
        'closed' => '已結案',
        'spam' => '垃圾訊息',
    );

    return isset($labels[$status]) ? $labels[$status] : $status;
}

$q = trim(get('q', ''));
$inquiries = array();
$hasTable = factory_inquiries_table_exists('factory_inquiries');

if ($hasTable) {
    if ($q !== '') {
        $like = '%' . $q . '%';
        $inquiries = db_all(
            'SELECT *
             FROM factory_inquiries
             WHERE factory_project_id LIKE ?
                OR name LIKE ?
                OR phone LIKE ?
                OR email LIKE ?
                OR website_type LIKE ?
                OR budget_range LIKE ?
                OR message LIKE ?
             ORDER BY id DESC',
            'sssssss',
            array($like, $like, $like, $like, $like, $like, $like)
        );
    } else {
        $inquiries = db_all(
            'SELECT *
             FROM factory_inquiries
             ORDER BY id DESC
             LIMIT 200',
            '',
            array()
        );
    }
}

include dirname(__FILE__) . '/../templates/admin-header.php';
?>
<h1>網站工廠諮詢</h1>
<style>
  .factory-inquiry-table th,
  .factory-inquiry-table td,
  .factory-inquiry-table .muted,
  .factory-inquiry-table .status {
    font-size: 14px;
    line-height: 1.45;
  }
  .inquiry-message {
    max-width: 360px;
    white-space: normal;
  }
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
  }
</style>

<?php if (!$hasTable) { ?>
  <div class="notice">尚未建立資料表：factory_inquiries。請先執行 migration 005。</div>
<?php } ?>

<form class="panel filters" method="get">
  <div class="filter-grid compact">
    <div>
      <label>搜尋諮詢</label>
      <input name="q" value="<?php echo h($q); ?>" placeholder="專案、姓名、電話、Email、網站類型、預算、需求">
    </div>
    <div class="filter-actions">
      <button type="submit">搜尋</button>
      <a class="button secondary" href="factory-inquiries.php">清除</a>
    </div>
  </div>
</form>

<table class="factory-inquiry-table">
  <tr>
    <th>時間</th>
    <th>專案</th>
    <th>姓名</th>
    <th>電話</th>
    <th>Email</th>
    <th>網站類型</th>
    <th>預算</th>
    <th>需求</th>
    <th>狀態</th>
  </tr>
  <?php foreach ($inquiries as $inquiry) { ?>
    <tr>
      <td><?php echo h(isset($inquiry['created_at']) ? $inquiry['created_at'] : ''); ?></td>
      <td>
        <?php if (!empty($inquiry['factory_project_id'])) { ?>
          <?php echo h($inquiry['factory_project_id']); ?>
        <?php } else { ?>
          <span class="muted">未指定</span>
        <?php } ?>
      </td>
      <td><?php echo h($inquiry['name']); ?></td>
      <td><?php echo h($inquiry['phone']); ?></td>
      <td><?php echo h($inquiry['email']); ?></td>
      <td><?php echo h($inquiry['website_type']); ?></td>
      <td><?php echo h($inquiry['budget_range']); ?></td>
      <td class="inquiry-message"><?php echo nl2br(h($inquiry['message'])); ?></td>
      <td><span class="action-pill"><?php echo h(factory_inquiry_status_label($inquiry['inquiry_status'])); ?></span></td>
    </tr>
  <?php } ?>
  <?php if ($hasTable && empty($inquiries)) { ?><tr><td colspan="9" class="muted">目前沒有符合條件的網站工廠諮詢。</td></tr><?php } ?>
</table>
<?php include dirname(__FILE__) . '/../templates/admin-footer.php'; ?>
