<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';

$pdo = admission_db();
$stmt = $pdo->query(
    'SELECT
        c.client_id,
        c.client_name,
        c.phone,
        c.email,
        c.line_user_id,
        c.line_display_name,
        c.brand_name,
        c.location_area,
        c.client_status,
        c.created_at,
        intake_summary.latest_intake_at,
        COALESCE(intake_summary.intake_count, 0) AS intake_count
     FROM clients c
     LEFT JOIN (
        SELECT
            client_id,
            MAX(created_at) AS latest_intake_at,
            COUNT(intake_id) AS intake_count
        FROM course_intakes
        GROUP BY client_id
     ) intake_summary ON intake_summary.client_id = c.client_id
     ORDER BY c.updated_at DESC, c.created_at DESC'
);
$clients = $stmt->fetchAll();

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>客戶資料 - 課程招生系統</title>
    <style>
        body {
            margin: 0;
            background: #f6f7f9;
            color: #1f2933;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        main {
            max-width: 1180px;
            margin: 0 auto;
            padding: 32px 20px;
        }

        h1 {
            margin: 0 0 18px;
            font-size: 24px;
            font-weight: 700;
        }

        .summary {
            margin-bottom: 18px;
            color: #52606d;
            font-size: 14px;
        }

        .table-wrap {
            overflow-x: auto;
            background: #fff;
            border: 1px solid #d9e2ec;
            border-radius: 8px;
        }

        table {
            width: 100%;
            min-width: 1040px;
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
            font-weight: 700;
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
            font-weight: 700;
        }
    </style>
</head>
<body>
<main>
    <h1>客戶資料</h1>
    <div class="summary">共 <?= count($clients) ?> 位客戶。LINE intake API 寫入 `clients` 後會出現在這裡。</div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>客戶</th>
                <th>聯絡方式</th>
                <th>LINE</th>
                <th>品牌 / 地區</th>
                <th>狀態</th>
                <th>Intake</th>
                <th>建立時間</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$clients): ?>
                <tr>
                    <td colspan="8" class="muted">目前沒有客戶資料。</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($clients as $client): ?>
                <tr>
                    <td><?= (int) $client['client_id'] ?></td>
                    <td>
                        <strong><?= h($client['client_name']) ?></strong><br>
                        <span class="muted"><?= h($client['brand_name']) ?></span>
                    </td>
                    <td>
                        <?= h($client['phone']) ?><br>
                        <span class="muted"><?= h($client['email']) ?></span>
                    </td>
                    <td>
                        <?= h($client['line_display_name']) ?><br>
                        <span class="muted"><?= h($client['line_user_id']) ?></span>
                    </td>
                    <td>
                        <?= h($client['brand_name']) ?><br>
                        <span class="muted"><?= h($client['location_area']) ?></span>
                    </td>
                    <td><span class="status"><?= h($client['client_status']) ?></span></td>
                    <td>
                        <?= (int) $client['intake_count'] ?> 筆<br>
                        <span class="muted"><?= h($client['latest_intake_at']) ?></span>
                    </td>
                    <td><?= h($client['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
