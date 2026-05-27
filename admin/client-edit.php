<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_login();

$id = (int) get('id', 0);
$client = $id ? client_find($id) : array('name' => '', 'contact_name' => '', 'phone' => '', 'email' => '', 'line_id' => '', 'line_id_link' => '', 'note' => '', 'status' => 'active', 'project_limit' => '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    client_save($id, array(
        'name' => post('name', ''),
        'contact_name' => post('contact_name', ''),
        'phone' => post('phone', ''),
        'email' => post('email', ''),
        'line_id_link' => post('line_id_link', ''),
        'note' => post('note', ''),
        'status' => post('status', 'active'),
        'project_limit' => post('project_limit', '') === '' ? null : (int) post('project_limit', ''),
    ));
    $_SESSION['flash'] = '客戶資料已儲存。';
    redirect('clients.php');
}

include dirname(__FILE__) . '/../templates/admin-header.php';
?>
<h1><?php echo $id ? '編輯客戶' : '新增客戶'; ?></h1>
<form class="panel" method="post">
  <?php echo csrf_field(); ?>
  <label>客戶名稱</label><input name="name" value="<?php echo h($client['name']); ?>" required>
  <div class="grid">
    <div><label>聯絡人</label><input name="contact_name" value="<?php echo h($client['contact_name']); ?>"></div>
    <div><label>電話</label><input name="phone" value="<?php echo h($client['phone']); ?>"></div>
    <div><label>Email</label><input name="email" value="<?php echo h($client['email']); ?>"></div>
    <div><label>LINE ID Link</label><input name="line_id_link" value="<?php echo h(isset($client['line_id_link']) ? $client['line_id_link'] : ''); ?>"></div>
    <div><label>可建立招生頁數量</label><input type="number" min="0" name="project_limit" value="<?php echo h($client['project_limit']); ?>" placeholder="留空或 0 表示不限制"></div>
  </div>
  <label>狀態</label>
  <select name="status">
    <?php foreach (client_status_options() as $value => $label) { ?>
      <option value="<?php echo h($value); ?>" <?php echo $client['status'] === $value ? 'selected' : ''; ?>><?php echo h($label); ?></option>
    <?php } ?>
  </select>
  <label>備註</label><textarea name="note"><?php echo h($client['note']); ?></textarea>
  <div class="actions"><button type="submit">儲存</button><a class="button secondary" href="clients.php">返回</a></div>
</form>
<?php include dirname(__FILE__) . '/../templates/admin-footer.php'; ?>
