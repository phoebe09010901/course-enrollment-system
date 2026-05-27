# Backend Automation Flow

## 文件目的

本文件規劃「課程招生 - 系統」從 LINE AI 客服收件、資料庫建檔、後台查看、三款樣板預覽、通知客戶，到三天後預覽網址作廢的完整後端與自動化流程。

目前 repo 尚未包含實作程式碼、資料庫 migration、LINE webhook、Email sender、AI Worker 或 admin 後台程式。本文件是 Chat D / Chat E 的後端與自動化規格。

## MVP 階段範圍

目前課程招生系統為免費試營運階段，MVP 不處理報價、付款或待付款流程。

本階段重點：

- LINE AI 收件與資料確認。
- 客戶與課程專案建檔。
- 三款樣板提案與預覽。
- 客戶選定樣板。
- 免費製作正式頁。
- 正式頁預覽與客戶確認。
- 進入試營運或已上線。

價格、報價、付款、發票與金流相關欄位先標記為 future phase，不列入 MVP 必做，也不作為流程必填。

## 系統流程總覽

```text
官網 / 招生頁
  -> LINE AI 客服詢問客戶資料
  -> AI 整理成標準 JSON
  -> 客戶確認資料
  -> 建立或更新 clients
  -> 建立 course_projects
  -> project_status = 待樣板提案
  -> Chat A / Canva 產生三款樣板
  -> Chat B / Codex 產生三個預覽網址
  -> Email 與 LINE 通知客戶
  -> 客戶三天內選擇 A / B / C
  -> 寫入 selected_template_id / selected_proposal_id
  -> 免費製作正式頁
  -> 正式頁預覽
  -> 客戶確認
  -> 試營運 / 已上線
  -> 若三天內未選擇，預覽網址顯示逾期提示
```

## 一、資料庫設計

建議以 MySQL / MariaDB 為第一版落地目標，符合目前 PHP 後台與 cPanel 部署情境。欄位命名先使用 snake_case，與資料庫慣例一致。

### `clients`

客戶主檔。`line_user_id` 是避免同一位 LINE 客戶重複建檔的主要鍵；Email 與 phone 可作為輔助比對，但不應取代 LINE 身分。

```sql
CREATE TABLE clients (
  client_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_name VARCHAR(120) NOT NULL,
  phone VARCHAR(40) NULL,
  email VARCHAR(190) NULL,
  line_user_id VARCHAR(190) NULL,
  line_display_name VARCHAR(190) NULL,
  brand_name VARCHAR(190) NULL,
  location_area VARCHAR(190) NULL,
  client_status VARCHAR(40) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  notes TEXT NULL,
  UNIQUE KEY uq_clients_line_user_id (line_user_id),
  KEY idx_clients_email (email),
  KEY idx_clients_phone (phone),
  KEY idx_clients_status (client_status)
);
```

規則：

- 若 `line_user_id` 已存在，更新既有 `clients`，不要新增重複客戶。
- 若 `line_user_id` 不存在，但 email 或 phone 命中疑似同一人，後台應標示「可能重複」，由人工確認。
- `client_status` 建議值：`active`、`needs_review`、`inactive`、`blocked`。

### `course_projects`

一筆客戶需求對應一個課程招生專案。此表是後台案件列表、專案狀態與樣板選擇結果的核心。

```sql
CREATE TABLE course_projects (
  project_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id BIGINT UNSIGNED NOT NULL,
  course_name VARCHAR(190) NOT NULL,
  course_type VARCHAR(120) NULL,
  course_format VARCHAR(120) NULL,
  course_location VARCHAR(190) NULL,
  expected_launch_date DATE NULL,
  expected_start_date DATE NULL,
  course_price DECIMAL(12, 2) NULL,
  course_capacity INT UNSIGNED NULL,
  target_audience TEXT NULL,
  course_features TEXT NULL,
  has_photos TINYINT(1) NOT NULL DEFAULT 0,
  has_copywriting TINYINT(1) NOT NULL DEFAULT 0,
  needs_template_proposal TINYINT(1) NOT NULL DEFAULT 1,
  project_status VARCHAR(40) NOT NULL DEFAULT '待資料確認',
  selected_proposal_id BIGINT UNSIGNED NULL,
  selected_primary_template_id VARCHAR(120) NULL,
  selected_secondary_template_id VARCHAR(120) NULL,
  preview_expires_at DATETIME NULL,
  formal_preview_url TEXT NULL,
  formal_page_url TEXT NULL,
  formal_page_status VARCHAR(40) NULL,
  client_confirmed_at DATETIME NULL,
  trial_started_at DATETIME NULL,
  launched_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  notes TEXT NULL,
  CONSTRAINT fk_course_projects_client
    FOREIGN KEY (client_id) REFERENCES clients(client_id),
  KEY idx_course_projects_client (client_id),
  KEY idx_course_projects_status (project_status),
  KEY idx_course_projects_preview_expires_at (preview_expires_at)
);
```

`course_price` 是 future phase 的可選欄位。免費試營運 MVP 不以價格作為必填，也不以付款狀態推動流程。

### `course_intakes`

MVP 初版使用 `course_intakes` 接住 Cloudflare Worker 送來的 LINE intake JSON。它是 LINE AI 收件後的原始課程需求紀錄，後續可再由 Worker 或人工轉成正式 `course_projects`。

```sql
CREATE TABLE course_intakes (
  intake_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id BIGINT UNSIGNED NOT NULL,
  source VARCHAR(40) NOT NULL DEFAULT 'line_ai',
  course_name VARCHAR(190) NOT NULL,
  course_type VARCHAR(120) NULL,
  course_format VARCHAR(120) NULL,
  course_location VARCHAR(190) NULL,
  target_audience TEXT NULL,
  course_features TEXT NULL,
  intake_status VARCHAR(40) NOT NULL DEFAULT '已建檔',
  raw_payload JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

目前 `api/line-intakes/` 會先寫入 `clients` 與 `course_intakes`。完整專案流程再接續建立 `course_projects`。

`project_status` 建議值：

- `待資料確認`
- `已建檔`
- `待樣板提案`
- `樣板製作中`
- `樣板預覽中`
- `已通知客戶選版`
- `已選定樣板`
- `樣板逾期`
- `免費製作中`
- `正式頁預覽中`
- `待客戶確認`
- `試營運中`
- `已上線`
- `已取消`

### `template_proposals`

每個 project 預設建立 A / B / C 三筆提案，各自記錄 Canva 來源、模板 id、預覽網址、token 與到期時間。

```sql
CREATE TABLE template_proposals (
  proposal_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id BIGINT UNSIGNED NOT NULL,
  proposal_code CHAR(1) NOT NULL,
  proposal_name VARCHAR(190) NOT NULL,
  primary_template_id VARCHAR(120) NOT NULL,
  secondary_template_id VARCHAR(120) NULL,
  source_url TEXT NULL,
  secondary_source_url TEXT NULL,
  canva_url TEXT NULL,
  preview_url TEXT NULL,
  preview_token CHAR(64) NOT NULL,
  preview_status VARCHAR(40) NOT NULL DEFAULT 'draft',
  expires_at DATETIME NULL,
  selected_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  notes TEXT NULL,
  CONSTRAINT fk_template_proposals_project
    FOREIGN KEY (project_id) REFERENCES course_projects(project_id),
  UNIQUE KEY uq_template_proposals_project_code (project_id, proposal_code),
  UNIQUE KEY uq_template_proposals_preview_token (preview_token),
  KEY idx_template_proposals_project (project_id),
  KEY idx_template_proposals_status (preview_status),
  KEY idx_template_proposals_expires_at (expires_at)
);
```

`preview_status` 建議值：

- `draft`
- `preview_ready`
- `sent`
- `selected`
- `not_selected`
- `expired`
- `cancelled`

規則：

- 每個 `project_id` 預設產生 3 筆 `template_proposals`。
- `proposal_code` 固定為 `A`、`B`、`C`。
- 客戶選定其中一款後，選中的提案改為 `selected`，其餘兩款改為 `not_selected` 或 `cancelled`。
- 三天內未選擇時，三款全部標記為 `expired`。

### `notification_logs`

記錄 Email 與 LINE 的通知內容、結果與錯誤訊息。所有自動通知都要寫入此表，方便後台追蹤與重寄。

```sql
CREATE TABLE notification_logs (
  notification_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id BIGINT UNSIGNED NULL,
  client_id BIGINT UNSIGNED NULL,
  channel VARCHAR(20) NOT NULL,
  notification_type VARCHAR(40) NOT NULL,
  recipient VARCHAR(190) NOT NULL,
  message_content MEDIUMTEXT NOT NULL,
  sent_status VARCHAR(40) NOT NULL DEFAULT 'pending',
  sent_at DATETIME NULL,
  error_message TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notification_logs_project
    FOREIGN KEY (project_id) REFERENCES course_projects(project_id),
  CONSTRAINT fk_notification_logs_client
    FOREIGN KEY (client_id) REFERENCES clients(client_id),
  KEY idx_notification_logs_project (project_id),
  KEY idx_notification_logs_client (client_id),
  KEY idx_notification_logs_type (notification_type),
  KEY idx_notification_logs_status (sent_status)
);
```

`channel` 建議值：

- `email`
- `line`

`notification_type` 建議值：

- `data_confirmed`
- `preview_ready`
- `expiry_reminder`
- `preview_expired`
- `proposal_selected`
- `formal_preview_ready`
- `client_confirmed`
- `trial_started`
- `site_launched`

`sent_status` 建議值：

- `pending`
- `sent`
- `failed`
- `skipped`

### `line_conversations`

保留 LINE AI 對話與標準化 JSON，方便追蹤資料來源、除錯與人工接手。

```sql
CREATE TABLE line_conversations (
  conversation_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id BIGINT UNSIGNED NULL,
  project_id BIGINT UNSIGNED NULL,
  line_user_id VARCHAR(190) NOT NULL,
  direction VARCHAR(20) NOT NULL,
  message_type VARCHAR(40) NOT NULL DEFAULT 'text',
  raw_message MEDIUMTEXT NULL,
  normalized_json JSON NULL,
  ai_intent VARCHAR(80) NULL,
  ai_confidence DECIMAL(5, 4) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_line_conversations_client
    FOREIGN KEY (client_id) REFERENCES clients(client_id),
  CONSTRAINT fk_line_conversations_project
    FOREIGN KEY (project_id) REFERENCES course_projects(project_id),
  KEY idx_line_conversations_line_user_id (line_user_id),
  KEY idx_line_conversations_project (project_id),
  KEY idx_line_conversations_created_at (created_at)
);
```

## 二、後台畫面需求

免費試營運 MVP 的後台重點不是付款追蹤，而是案件是否完成資料確認、是否完成選版、正式頁是否完成、客戶是否確認，以及是否進入試營運或已上線。

### 1. 新案件列表

用途：集中查看 LINE AI 收件後建立的新專案。

需要欄位：

- 專案 ID
- 客戶名稱
- LINE 顯示名稱
- 課程名稱
- 課程類型
- 專案狀態
- 建立時間
- 下一步狀態
- 通知狀態
- 客戶是否已選款
- 預覽是否逾期
- 正式頁是否完成
- 客戶是否確認
- 是否進入試營運 / 已上線

建議篩選：

- `待資料確認`
- `待樣板提案`
- `樣板製作中`
- `樣板預覽中`
- `已通知客戶選版`
- `已選定樣板`
- `樣板逾期`
- `免費製作中`
- `正式頁預覽中`
- `待客戶確認`
- `試營運中`
- `已上線`

### 2. 客戶資料頁

顯示 `clients` 主檔與此客戶所有 `course_projects`。若同電話或 email 有多筆客戶，顯示疑似重複提示。

### 3. 課程專案詳情頁

顯示課程資料、目標受眾、課程特色、照片/文案狀態、預計上線與開課日期、目前專案狀態與備註。

`course_price` 可顯示為選填欄位，但應標示為 future phase，不作為 MVP 流程判斷。

### 4. 樣板提案區塊

每個 project 顯示 A / B / C 三款：

- `proposal_code`
- `proposal_name`
- `primary_template_id`
- `secondary_template_id`
- `source_url`
- `secondary_source_url`
- `canva_url`
- `preview_url`
- `preview_status`
- `expires_at`
- 是否已選款

### 5. 通知紀錄區塊

顯示 Email / LINE 是否已寄出、寄送時間、錯誤訊息與重寄按鈕。

### 6. 正式頁與試營運區塊

顯示：

- 正式頁預覽網址 `formal_preview_url`
- 正式頁網址 `formal_page_url`
- 正式頁狀態 `formal_page_status`
- 客戶確認時間 `client_confirmed_at`
- 試營運開始時間 `trial_started_at`
- 上線時間 `launched_at`

### 7. 預覽管理

後台需要：

- 重新開啟預覽
- 延長到期時間
- 手動標記某款為 selected
- 手動取消提案
- 重寄三款預覽通知

## 三、狀態流程

### `course_projects.project_status`

```text
待資料確認
  -> 已建檔
  -> 待樣板提案
  -> 樣板製作中
  -> 樣板預覽中
  -> 已通知客戶選版
  -> 已選定樣板
  -> 免費製作中
  -> 正式頁預覽中
  -> 待客戶確認
  -> 試營運中
  -> 已上線
```

例外流程：

- 任一階段可人工改為 `已取消`。
- `樣板預覽中` 或 `已通知客戶選版` 超過期限且未選款時，改為 `樣板逾期`。
- `樣板逾期` 可由後台重新開啟預覽，回到 `樣板預覽中`。
- 報價、付款、待付款不屬於 MVP 主流程，未來若商業化再新增 future phase 狀態。

### `template_proposals.preview_status`

```text
draft
  -> preview_ready
  -> sent
  -> selected
```

旁支：

- `sent` 且其他款式被選中時，改為 `not_selected` 或 `cancelled`。
- `sent` 且逾期未選時，改為 `expired`。
- 管理員手動取消時，改為 `cancelled`。

## 四、AI Worker 自動化流程

### Worker 1：客戶確認資料後

觸發條件：LINE AI 收到客戶回覆「確認」並傳入標準 JSON。

流程：

1. 接收標準 JSON。
2. 寫入 `line_conversations.normalized_json`。
3. 檢查必填欄位：`client_name`、至少一種聯絡方式、`course_name`、`course_type`。
4. 若缺少欄位，回傳缺漏欄位給 LINE AI 繼續追問。
5. 若完整，以 `line_user_id` 查找 `clients`。
6. 若找到既有客戶，更新客戶資料；若未找到，建立新 `clients`。
7. 建立 `course_projects`。
8. `project_status` 設為 `待樣板提案`。
9. 寫入 `notification_logs`，類型為 `data_confirmed`。
10. 通知後台有新案件。

### Worker 2：觸發三款樣板提案

觸發條件：`project_status = 待樣板提案`。

流程：

1. 讀取 `course_projects` 與客戶品牌資料。
2. 根據 `course_type`、`target_audience`、`course_format` 參考 `docs/TEMPLATE_REFERENCE.md`。
3. 交給 Chat A / Canva 產生三款 proposal。
4. 建立三筆 `template_proposals`：A / B / C。
5. 寫入 `proposal_name`、`primary_template_id`、`secondary_template_id`、`source_url`、`secondary_source_url`、`canva_url`。
6. 將 `project_status` 改為 `樣板製作中`。

### Worker 3：產生三個預覽網址

觸發條件：Canva 樣板確認，Chat B / Codex 已產生三個前端預覽頁。

流程：

1. 為 A / B / C 各產生 `preview_token`。
2. 建立 `preview_url`。
3. 更新三筆 `template_proposals.preview_url`。
4. `preview_status` 設為 `preview_ready`。
5. `expires_at` 設為目前時間加 3 天。
6. `course_projects.preview_expires_at` 同步寫入三款最早到期時間。
7. `project_status` 改為 `樣板預覽中`。
8. 觸發通知 Worker。

### Worker 4：自動通知客戶

觸發條件：三款 proposal 都是 `preview_ready`。

流程：

1. 組出 A / B / C 三款樣板名稱與預覽網址。
2. 寄 Email 給客戶。
3. 透過 LINE 通知客戶。
4. 通知內容包含三天期限與回覆方式。
5. 成功寄送後將 `preview_status` 改為 `sent`。
6. `project_status` 改為 `已通知客戶選版`。
7. 寫入 `notification_logs`，類型為 `preview_ready`。

### Worker 5：逾期前一天提醒

觸發條件：每天排程檢查。

條件：

- `project_status IN (樣板預覽中, 已通知客戶選版)`
- `preview_expires_at` 介於未來 24 到 30 小時內
- 沒有任何 proposal 是 `selected`
- 尚未寄過 `expiry_reminder`

流程：

1. 發送 Email 與 LINE 提醒。
2. 寫入 `notification_logs`，類型為 `expiry_reminder`。

### Worker 6：客戶選款

觸發條件：LINE AI 或預覽頁收到「我要 A 款 / 我要 B 款 / 我要 C 款」。

流程：

1. 找到對應 `project_id`。
2. 找到 `proposal_code`。
3. 確認未逾期。
4. 將選中的 proposal 設為 `selected` 並寫入 `selected_at`。
5. 其他 proposal 設為 `not_selected` 或 `cancelled`。
6. 寫入 `course_projects.selected_proposal_id`。
7. 寫入 `selected_primary_template_id` 與 `selected_secondary_template_id`。
8. `project_status` 改為 `已選定樣板`。
9. 自動通知你與客戶。
10. 後台或 Worker 接續改為 `免費製作中`，進入正式頁製作。

### Worker 7：三天後作廢

觸發條件：每天或每小時排程檢查。

條件：

- `project_status IN (樣板預覽中, 已通知客戶選版)`
- `preview_expires_at < NOW()`
- 沒有任何 proposal 是 `selected`

流程：

1. 三筆 `template_proposals.preview_status` 改為 `expired`。
2. `course_projects.project_status` 改為 `樣板逾期`。
3. 前台預覽頁不刪檔，但根據 token 與狀態顯示逾期訊息。
4. 發送 LINE 與 Email 逾期通知。
5. 寫入 `notification_logs`，類型為 `preview_expired`。

### Worker 8：正式頁完成與客戶確認

觸發條件：Chat B / Codex 或人工完成正式招生頁。

流程：

1. 寫入 `course_projects.formal_preview_url`。
2. `formal_page_status` 設為 `preview_ready`。
3. `project_status` 改為 `正式頁預覽中`。
4. 發送 LINE 與 Email 通知客戶查看正式頁。
5. 通知完成後 `project_status` 改為 `待客戶確認`。
6. 客戶確認後寫入 `client_confirmed_at`。
7. 客戶確認當下或後台人工切換時，`project_status` 改為 `試營運中`。
8. 試營運開始時寫入 `trial_started_at`。
9. 正式上線時寫入 `formal_page_url`、`launched_at`，並將 `project_status` 改為 `已上線`。

## 五、預覽網址規則

建議使用 token 版本，不直接暴露連續流水號：

```text
/demo/admission-system/preview/{preview_token}
```

資料庫仍可保留人類可讀的 A / B / C：

```text
proposal_code = A
proposal_code = B
proposal_code = C
```

如果需要內部除錯，也可支援後台可見但不對客戶公開的格式：

```text
/demo/admission-system/preview/{project_id}/a
/demo/admission-system/preview/{project_id}/b
/demo/admission-system/preview/{project_id}/c
```

規則：

- 對客戶發送的 URL 一律使用 `preview_token`。
- `preview_token` 使用不可猜測的隨機值，例如 32 bytes 轉 hex。
- 預覽頁載入時查詢 token 是否存在、是否 expired、是否 cancelled。
- 逾期後不要回 404，顯示固定逾期頁。
- 逾期提示文案：

```text
此樣板預覽已超過選擇期限。請聯繫我們重新開啟預覽或安排重新提案。
```

## 六、Email 與 LINE 通知模板

以下模板使用變數：

- `{client_name}`
- `{course_name}`
- `{expires_at}`
- `{proposal_a_name}`
- `{proposal_b_name}`
- `{proposal_c_name}`
- `{proposal_a_url}`
- `{proposal_b_url}`
- `{proposal_c_url}`
- `{selected_proposal_code}`
- `{selected_proposal_name}`

### 三款樣板完成通知

Email subject:

```text
{course_name} 的三款招生頁樣板預覽已完成
```

Email body:

```text
{client_name} 您好，

我們已完成 {course_name} 的三款招生頁樣板預覽，請在 {expires_at} 前查看並選擇一款。

A 款：{proposal_a_name}
{proposal_a_url}

B 款：{proposal_b_name}
{proposal_b_url}

C 款：{proposal_c_name}
{proposal_c_url}

您可以直接回覆：「我要 A 款」、「我要 B 款」或「我要 C 款」。
提醒您，預覽網址將在三天後作廢。
```

LINE:

```text
{client_name} 您好，{course_name} 的三款招生頁樣板預覽完成了：

A 款：{proposal_a_name}
{proposal_a_url}

B 款：{proposal_b_name}
{proposal_b_url}

C 款：{proposal_c_name}
{proposal_c_url}

請在 {expires_at} 前回覆「我要 A 款 / 我要 B 款 / 我要 C 款」。三天後預覽網址會作廢。
```

### 逾期前一天提醒

Email subject:

```text
提醒：{course_name} 的樣板預覽將於明天到期
```

Email body:

```text
{client_name} 您好，

提醒您，{course_name} 的三款樣板預覽將於 {expires_at} 到期。

若您已經有偏好的款式，可以回覆「我要 A 款」、「我要 B 款」或「我要 C 款」。
若需要我們協助比較，也可以直接回覆這封信。
```

LINE:

```text
提醒您，{course_name} 的樣板預覽將於 {expires_at} 到期。

請回覆「我要 A 款 / 我要 B 款 / 我要 C 款」完成選擇。需要協助比較也可以直接告訴我們。
```

### 客戶已選款通知

Email subject:

```text
已收到您選擇的 {selected_proposal_code} 款樣板
```

Email body:

```text
{client_name} 您好，

我們已收到您的選擇：{selected_proposal_code} 款，{selected_proposal_name}。

接下來會進入免費試營運製作流程，我們會再與您確認正式頁內容與時程。
```

LINE:

```text
已收到您的選擇：{selected_proposal_code} 款，{selected_proposal_name}。

接下來會進入免費試營運製作流程，我們會再和您確認正式頁內容。
```

### 預覽已作廢通知

Email subject:

```text
{course_name} 的樣板預覽已超過選擇期限
```

Email body:

```text
{client_name} 您好，

{course_name} 的三款樣板預覽已超過選擇期限，原預覽網址目前已顯示逾期提示。

若您仍想查看或重新選擇，請回覆此信，我們可以協助重新開啟預覽或安排重新提案。
```

LINE:

```text
{course_name} 的樣板預覽已超過選擇期限，原預覽網址目前已作廢。

若您想重新查看或選擇，請直接回覆我們，我們會協助重新開啟預覽或安排重新提案。
```

## 七、Chat B 前端實作範圍

需要交給 Chat B / Codex 實作：

- 三款招生頁預覽頁。
- token 型預覽路由。
- 預覽頁狀態判斷：有效、已選、已取消、已逾期。
- 逾期提示頁。
- 客戶選款 CTA，送出 A / B / C 選擇。
- 正式頁預覽頁。
- 客戶確認正式頁的 CTA 或表單。
- 避免在前端暴露連續 project id。
- 可選：後台內嵌預覽 iframe 或開新分頁。

## 八、待確認事項

- 正式資料庫版本與 cPanel 主機可用的 MySQL 版本。
- Email 寄送方式：主機 mail、SMTP、或第三方服務。
- LINE Messaging API channel 與 webhook URL。
- 後台是否需要多帳號權限與角色。
- `免費製作中`、`正式頁預覽中`、`試營運中` 要由系統自動進入，或由後台人工按鈕切換。
- 逾期後重新開啟預覽時，是否沿用原 token 或重發新 token。

## 九、Future Phase

以下項目不列入目前免費試營運 MVP：

- 報價流程。
- 付款流程。
- 待付款狀態。
- 金流串接。
- 發票與收據。
- 付費方案、合約與續約。

若未來進入商業化階段，再新增 quotation、payment、invoice 相關資料表與狀態，不應混入目前 MVP 主流程。
