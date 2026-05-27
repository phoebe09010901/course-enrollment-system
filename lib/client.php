<?php

function client_all()
{
    return client_search('');
}

function client_search($keyword)
{
    $types = '';
    $params = array();
    $where = '';

    if (trim($keyword) !== '') {
        $where = ' WHERE name LIKE ? OR contact_name LIKE ? OR phone LIKE ? OR email LIKE ? OR line_user_id LIKE ? OR line_id LIKE ? OR line_id_link LIKE ?';
        $types = 'sssssss';
        $like = '%' . trim($keyword) . '%';
        $params = array($like, $like, $like, $like, $like, $like, $like);
    }

    return db_all('SELECT * FROM admission_clients' . $where . ' ORDER BY id DESC', $types, $params);
}

function client_find($id)
{
    return db_one('SELECT * FROM admission_clients WHERE id = ?', 'i', array((int) $id));
}

function client_save($id, $data)
{
    $lineIdLinkStatus = !empty($data['line_id_link']) ? 'provided' : 'missing';

    if ($id) {
        db_exec(
            'UPDATE admission_clients SET name = ?, contact_name = ?, phone = ?, email = ?, line_id_link = ?, line_id_link_status = ?, note = ?, status = ?, project_limit = ?, updated_at = ? WHERE id = ?',
            'ssssssssisi',
            array($data['name'], $data['contact_name'], $data['phone'], $data['email'], $data['line_id_link'], $lineIdLinkStatus, $data['note'], $data['status'], $data['project_limit'], now(), (int) $id)
        );
        return $id;
    }

    return db_exec(
        'INSERT INTO admission_clients (name, contact_name, phone, email, line_id_link, line_id_link_status, note, status, project_limit, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        'ssssssssis',
        array($data['name'], $data['contact_name'], $data['phone'], $data['email'], $data['line_id_link'], $lineIdLinkStatus, $data['note'], $data['status'], $data['project_limit'], now())
    );
}


function client_project_count($clientId)
{
    $row = db_one(
        'SELECT COUNT(*) AS total FROM admission_projects WHERE client_id = ?',
        'i',
        array((int) $clientId)
    );

    return $row ? (int) $row['total'] : 0;
}

function client_delete($id)
{
    $id = (int) $id;
    if (!$id) {
        return array('ok' => false, 'message' => '找不到要刪除的客戶。');
    }

    $client = client_find($id);
    if (!$client) {
        return array('ok' => false, 'message' => '找不到要刪除的客戶。');
    }

    if (client_project_count($id) > 0) {
        return array('ok' => false, 'message' => '此客戶已有專案，請先處理或改派專案後再刪除。');
    }

    db_exec('DELETE FROM course_intakes WHERE client_id = ?', 'i', array($id));
    db_exec('DELETE FROM admission_clients WHERE id = ?', 'i', array($id));

    return array('ok' => true, 'message' => '客戶已刪除。');
}

function client_active_project_count($clientId)
{
    $row = db_one(
        "SELECT COUNT(*) AS total FROM admission_projects WHERE client_id = ? AND status <> 'archived'",
        'i',
        array((int) $clientId)
    );

    return $row ? (int) $row['total'] : 0;
}

function client_can_create_project($clientId, $currentProjectId)
{
    if (!$clientId) {
        return array('ok' => true, 'message' => '');
    }

    $client = client_find($clientId);
    if (!$client || empty($client['project_limit'])) {
        return array('ok' => true, 'message' => '');
    }

    $count = client_active_project_count($clientId);

    if ($currentProjectId) {
        $current = project_find($currentProjectId);
        if ($current && (int) $current['client_id'] === (int) $clientId && $current['status'] !== 'archived') {
            $count = max(0, $count - 1);
        }
    }

    if ($count >= (int) $client['project_limit']) {
        return array('ok' => false, 'message' => '此客戶已達可建立招生頁數量上限。封存不使用的專案後，才可再建立新的招生頁。');
    }

    return array('ok' => true, 'message' => '');
}

function client_status_options()
{
    return array(
        'active' => '啟用',
        'paused' => '暫停',
    );
}

function client_status_label($status)
{
    $labels = client_status_options();
    return isset($labels[$status]) ? $labels[$status] : $status;
}
