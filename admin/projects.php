<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_login();

function projects_table_exists($tableName)
{
    $row = db_one(
        'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1',
        's',
        array($tableName)
    );
    return !empty($row);
}

function course_project_delete($projectId)
{
    if ($projectId === '' || !projects_table_exists('course_projects')) {
        return '找不到要刪除的專案。';
    }

    $project = db_one('SELECT project_id FROM course_projects WHERE project_id = ? LIMIT 1', 's', array($projectId));
    if (!$project) {
        return '找不到要刪除的專案。';
    }

    if (projects_table_exists('template_proposals')) {
        db_exec('DELETE FROM template_proposals WHERE project_id = ?', 's', array($projectId));
    }

    if (projects_table_exists('notification_logs')) {
        db_exec('DELETE FROM notification_logs WHERE project_id = ?', 's', array($projectId));
    }

    db_exec('DELETE FROM course_projects WHERE project_id = ?', 's', array($projectId));
    return '專案已刪除。';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action', '') === 'delete') {
    verify_csrf();
    $_SESSION['flash'] = course_project_delete(post('project_id', ''));
    redirect('projects.php');
}

$q = trim(get('q', ''));
$projects = array();

if (projects_table_exists('course_projects')) {
    if ($q !== '') {
        $like = '%' . $q . '%';
        $projects = db_all(
            'SELECT p.*, c.name AS client_name
             FROM course_projects p
             LEFT JOIN admission_clients c ON c.id = p.client_id
             WHERE p.project_id LIKE ? OR p.course_name LIKE ? OR p.course_type LIKE ? OR c.name LIKE ?
             ORDER BY p.id DESC',
            'ssss',
            array($like, $like, $like, $like)
        );
    } else {
        $projects = db_all(
            'SELECT p.*, c.name AS client_name
             FROM course_projects p
             LEFT JOIN admission_clients c ON c.id = p.client_id
             ORDER BY p.id DESC',
            '',
            array()
        );
    }
}

include dirname(__FILE__) . '/../templates/admin-header.php';
?>
<h1>招生專案</h1>
<style>
  .row-actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
  .inline-action { display: inline; margin: 0; }
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
    font-size: 13px;
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
      <label>搜尋專案</label>
      <input name="q" value="<?php echo h($q); ?>" placeholder="專案、課程、客戶、類型">
    </div>
    <div class="filter-actions">
      <button type="submit">搜尋</button>
      <a class="button secondary" href="projects.php">清除</a>
    </div>
  </div>
</form>
<div class="actions"><a class="button" href="project-edit.php">新增專案</a></div>
<table>
  <tr><th>專案</th><th>客戶</th><th>課程</th><th>選定樣板</th><th>選版頁</th><th>操作</th></tr>
  <?php foreach ($projects as $project) { ?>
    <tr>
      <td><?php echo h($project['course_name']); ?><br><span class="muted"><?php echo h($project['project_id']); ?></span></td>
      <td><?php echo h($project['client_name']); ?></td>
      <td>
        <?php echo h($project['course_type']); ?><br>
        <span class="muted"><?php echo h($project['course_format']); ?> / <?php echo h($project['course_location']); ?></span>
      </td>
      <td>
        <?php echo h($project['selected_proposal_id']); ?><br>
        <span class="muted"><?php echo h($project['selected_template_id']); ?></span>
      </td>
      <td><?php if (!empty($project['selection_token'])) { ?><a class="action-pill" target="_blank" href="../course-template-proposals.php?t=<?php echo h($project['selection_token']); ?>">開啟</a><?php } else { ?><span class="muted">尚未建立</span><?php } ?></td>
      <td>
        <div class="row-actions">
          <?php if (!empty($project['selected_canva_url'])) { ?><a class="action-pill" target="_blank" href="<?php echo h($project['selected_canva_url']); ?>">Canva</a><?php } ?>
          <form class="inline-action" method="post" data-confirm="<?php echo h('確定要刪除專案「' . $project['course_name'] . '」嗎？此動作會同步刪除樣板提案與通知紀錄，且無法復原。'); ?>" onsubmit="return confirm(this.getAttribute('data-confirm'));">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="project_id" value="<?php echo h($project['project_id']); ?>">
            <button class="action-pill action-pill-danger" type="submit">刪除</button>
          </form>
        </div>
      </td>
    </tr>
  <?php } ?>
  <?php if (empty($projects)) { ?><tr><td colspan="6" class="muted">目前沒有符合條件的專案。</td></tr><?php } ?>
</table>
<?php include dirname(__FILE__) . '/../templates/admin-footer.php'; ?>
