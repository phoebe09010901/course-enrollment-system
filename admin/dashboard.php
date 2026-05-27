<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_login();

function dashboard_table_exists($tableName)
{
    $row = db_one(
        'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1',
        's',
        array($tableName)
    );
    return !empty($row);
}

function dashboard_count($tableName, $where)
{
    if (!dashboard_table_exists($tableName)) {
        return array('total' => 0);
    }

    $sql = 'SELECT COUNT(*) AS total FROM `' . $tableName . '`';
    if ($where !== '') {
        $sql .= ' WHERE ' . $where;
    }

    return db_one($sql, '', array());
}

$courseProjectCount = dashboard_count('course_projects', '');
$legacyProjectCount = dashboard_count('admission_projects', '');
$projectCount = array('total' => (int) $courseProjectCount['total'] + (int) $legacyProjectCount['total']);

$registrationCount = dashboard_count('admission_registrations', '');
$newRegistrationCount = dashboard_count('admission_registrations', "status = 'new'");
$paymentReviewCount = dashboard_count('admission_registrations', "status = 'payment_review'");
$paidCount = dashboard_count('admission_registrations', "status = 'paid'");
$publishedCount = dashboard_count('admission_projects', "public_enabled = 1 AND status = 'published'");

$recentRegistrations = array();
if (dashboard_table_exists('admission_registrations') && dashboard_table_exists('admission_projects')) {
    $recentRegistrations = db_all(
        'SELECT r.*, p.project_name FROM admission_registrations r INNER JOIN admission_projects p ON p.id = r.project_id ORDER BY r.id DESC LIMIT 8',
        '',
        array()
    );
}

$recentProjects = array();
if (dashboard_table_exists('course_projects')) {
    $recentProjects = db_all(
        'SELECT p.*, c.name AS client_name
         FROM course_projects p
         LEFT JOIN admission_clients c ON c.id = p.client_id
         ORDER BY p.id DESC
         LIMIT 6',
        '',
        array()
    );
}

include dirname(__FILE__) . '/../templates/admin-header.php';
?>
<style>
  .dashboard-table th,
  .dashboard-table td,
  .dashboard-table .muted,
  .dashboard-table .status {
    font-size: 14px;
  }
  .dashboard-table .project-name {
    color: #151a24;
    font-size: 14px;
    line-height: 1.45;
  }
  .dashboard-table .project-id,
  .dashboard-table .status-note,
  .dashboard-table .empty-note {
    font-size: 14px;
    line-height: 1.45;
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
    cursor: pointer;
  }
  .action-pill:hover {
    background: rgba(255, 255, 255, .90);
    color: #151a24;
  }
</style>
<section class="dashboard-hero">
  <div>
    <p class="dashboard-kicker">Admin Overview</p>
    <h1>系統總覽</h1>
    <p>快速掌握招生專案、公開頁與報名狀態，維持每一門課的營運節奏。</p>
  </div>
  <div class="dashboard-quick-actions">
    <a class="button" href="client-edit.php">新增客戶</a>
    <a class="button" href="project-edit.php">新增專案</a>
    <a class="button secondary" href="registrations.php">查看報名</a>
  </div>
</section>
<div class="metric-grid">
  <a class="metric-card" href="projects.php"><strong><?php echo (int) $projectCount['total']; ?></strong><span>招生專案</span></a>
  <a class="metric-card" href="projects.php"><strong><?php echo (int) $publishedCount['total']; ?></strong><span>已發布頁面</span></a>
  <a class="metric-card" href="registrations.php"><strong><?php echo (int) $registrationCount['total']; ?></strong><span>總報名數</span></a>
  <a class="metric-card" href="registrations.php?status=new"><strong><?php echo (int) $newRegistrationCount['total']; ?></strong><span>新報名</span></a>
  <a class="metric-card" href="registrations.php?status=payment_review"><strong><?php echo (int) $paymentReviewCount['total']; ?></strong><span>待對帳</span></a>
  <a class="metric-card" href="registrations.php?status=paid"><strong><?php echo (int) $paidCount['total']; ?></strong><span>已付款</span></a>
</div>
<div class="grid">
  <div class="panel">
    <h2>最近報名</h2>
    <table class="dashboard-table">
      <tr><th>時間</th><th>課程</th><th>姓名</th><th>電話</th><th>狀態</th></tr>
      <?php foreach ($recentRegistrations as $row) { ?>
        <tr>
          <td><?php echo h($row['created_at']); ?></td>
          <td><?php echo h($row['project_name']); ?></td>
          <td><a href="registration-view.php?id=<?php echo (int) $row['id']; ?>"><?php echo h($row['name']); ?></a></td>
          <td><?php echo h($row['phone']); ?></td>
          <td><span class="status <?php echo h(registration_status_class($row['status'])); ?>"><?php echo h(registration_status_label($row['status'])); ?></span></td>
        </tr>
      <?php } ?>
      <?php if (empty($recentRegistrations)) { ?><tr><td colspan="5" class="muted empty-note">目前尚無報名資料。</td></tr><?php } ?>
    </table>
  </div>
  <div class="panel">
    <h2>最近專案</h2>
    <table class="dashboard-table">
      <tr><th>專案</th><th>客戶</th><th>狀態</th><th>選版頁</th></tr>
      <?php foreach ($recentProjects as $project) { ?>
        <tr>
          <td><span class="project-name"><?php echo h($project['course_name']); ?></span><br><span class="muted project-id"><?php echo h($project['project_id']); ?></span></td>
          <td><?php echo h($project['client_name']); ?></td>
          <td><span class="status"><?php echo h($project['template_status']); ?></span><br><span class="muted status-note"><?php echo h($project['project_status']); ?></span></td>
          <td><?php if (!empty($project['selection_token'])) { ?><a class="action-pill" target="_blank" href="../course-template-proposals.php?t=<?php echo h($project['selection_token']); ?>">開啟</a><?php } else { ?><span class="muted">尚未建立</span><?php } ?></td>
        </tr>
      <?php } ?>
      <?php if (empty($recentProjects)) { ?><tr><td colspan="4" class="muted empty-note">目前尚無專案。</td></tr><?php } ?>
    </table>
  </div>
</div>
<?php include dirname(__FILE__) . '/../templates/admin-footer.php'; ?>
