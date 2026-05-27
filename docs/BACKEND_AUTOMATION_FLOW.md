# Backend Automation Flow

## 文件目的

本文件規劃「課程招生 - 系統」從 LINE AI 客服收件、資料庫建檔、後台查看、三款樣板預覽、通知客戶，到三天後預覽網址作廢的完整後端與自動化流程。

目前 repo 尚未建立資料庫 schema、LINE webhook、admin 後台、通知服務或前端預覽頁。本文件是實作前規格，不代表功能已完成。

## 參考文件狀態

本流程應參考以下文件：

- `docs/PROJECT_CONTEXT.md`
- `docs/PROJECT_STATUS.md`
- `docs/STYLE_SYSTEM.md`
- `docs/AI_WORKER_WORKFLOW.md`
- `docs/COLLABORATION_SETUP.md`

以下文件被流程需要，但目前尚未存在：

- `docs/TEMPLATE_REFERENCE.md`：未來應提供可挑選的樣板來源、template id、Canva 來源與適用課程類型。
- `docs/CLIENT_SELECTION_FLOW.md`：未來應定義客戶從收到預覽到選款的前台或 LINE 操作流程。

## 整體流程

```text
官網 / 招生頁
  ↓
LINE AI 客服詢問客戶資料
  ↓
AI 整理成標準 JSON
  ↓
客戶確認資料
  ↓
建立或更新 clients
  ↓
建立 course_projects
  ↓
project_status = 待樣板提案
  ↓
Chat A / Canva 產生三款樣板
  ↓
Chat B / Codex 產生三個招生頁預覽網址
  ↓
Email 與 LINE 通知客戶
  ↓
客戶三天內選擇 A / B / C
  ↓
系統記錄 selected_template_id / selected_proposal_id
  ↓
project_status = 免費製作中
  ↓
Chat B / Codex 依照選定樣板製作正式招生頁
  ↓
正式頁完成後通知客戶確認
  ↓
project_status = 正式頁預覽中
  ↓
客戶確認正式頁
  ↓
project_status = 試營運中 或 已上線
```

若三天內未選擇，三個預覽網址應作廢，前台改顯示逾期提示。

## MVP 階段邊界

目前本系統處於免費試營運階段，MVP 主流程不包含報價、付款、訂閱或續約。

以下流程先標記為 future phase，不放進目前主流程：

- `待報價`
- `報價已送出`
- `待付款`
- 客戶確認報價
- 自動產生報價單
- 自動寄出報價
- 付款
- 訂閱
- 續約

## 資料庫設計

以下以關聯式資料庫規格描述。實際可落在 PostgreSQL、MySQL 或其他支援唯一索引與交易的資料庫。

### `clients`

用來保存客戶與 LINE 身分。`line_user_id` 應建立唯一索引，避免同一位 LINE 客戶重複建檔。

| 欄位 | 建議型別 | 必填 | 說明 |
| --- | --- | --- | --- |
| `client_id` | uuid / text pk | 是 | 客戶主鍵 |
| `client_name` | text | 是 | 客戶姓名或窗口名稱 |
| `phone` | text | 否 | 聯絡電話 |
| `email` | text | 否 | Email |
| `line_user_id` | text unique | 否 | LINE 使用者 id，用於去重與更新既有客戶 |
| `line_display_name` | text | 否 | LINE 顯示名稱 |
| `brand_name` | text | 否 | 品牌或教室名稱 |
| `location_area` | text | 否 | 所在區域 |
| `client_status` | text | 是 | `new`、`active`、`paused`、`archived` |
| `created_at` | timestamp | 是 | 建立時間 |
| `updated_at` | timestamp | 是 | 更新時間 |
| `notes` | text | 否 | 備註 |

建議索引：

- unique index: `clients.line_user_id`
- index: `clients.email`
- index: `clients.client_status`

Upsert 規則：

1. 若 `line_user_id` 存在，依 `line_user_id` 查找既有客戶。
2. 找到既有客戶時更新 `client_name`、`phone`、`email`、`line_display_name`、`brand_name`、`location_area`、`updated_at`。
3. 找不到時新增客戶。
4. 若沒有 `line_user_id`，可用 `email` 或 `phone` 作為輔助查重，但不得取代 `line_user_id` 的主要去重角色。

### `course_projects`

用來保存每一次課程招生專案。客戶可以有多個課程專案。

| 欄位 | 建議型別 | 必填 | 說明 |
| --- | --- | --- | --- |
| `project_id` | uuid / text pk | 是 | 專案主鍵 |
| `client_id` | uuid / text fk | 是 | 對應 `clients.client_id` |
| `course_name` | text | 是 | 課程名稱 |
| `course_type` | text | 是 | 課程類型，用於挑選樣板 |
| `course_format` | text | 否 | 線上、實體、混合 |
| `course_location` | text | 否 | 上課地點 |
| `expected_launch_date` | date | 否 | 預計招生頁上線日 |
| `expected_start_date` | date | 否 | 預計開課日 |
| `course_price` | numeric | 否 | 課程價格 |
| `course_capacity` | integer | 否 | 招生人數 |
| `target_audience` | text | 否 | 目標客群 |
| `course_features` | text / json | 否 | 課程特色 |
| `has_photos` | boolean | 是 | 是否已有照片 |
| `has_copywriting` | boolean | 是 | 是否已有文案 |
| `needs_template_proposal` | boolean | 是 | 是否需要三款樣板提案 |
| `project_status` | text | 是 | 專案狀態 |
| `selected_proposal_id` | uuid / text fk | 否 | 已選定 proposal |
| `selected_primary_template_id` | text | 否 | 已選主樣板 id |
| `selected_secondary_template_id` | text | 否 | 已選輔助樣板 id |
| `preview_expires_at` | timestamp | 否 | 三款預覽最早到期時間 |
| `worker_run_id` | text | 否 | 最近一次處理此 project 的 worker run id |
| `locked_at` | timestamp | 否 | worker 取件鎖定時間 |
| `locked_by` | text | 否 | worker instance / 排程名稱 |
| `lock_expires_at` | timestamp | 否 | 鎖定逾時時間，用於中斷回收 |
| `attempt_count` | integer | 是 | 樣板提案或目前自動化任務嘗試次數，預設 0 |
| `next_retry_at` | timestamp | 否 | 暫時性錯誤後下次允許重試時間 |
| `last_worker_error` | text | 否 | 最近一次 worker 失敗原因 |
| `missing_required_fields` | json | 否 | 不可重試時缺少的必要資料 |
| `final_preview_url` | text | 否 | 正式招生頁預覽網址 |
| `live_url` | text | 否 | 試營運或正式上線網址 |
| `final_confirmed_at` | timestamp | 否 | 客戶確認正式頁時間 |
| `created_at` | timestamp | 是 | 建立時間 |
| `updated_at` | timestamp | 是 | 更新時間 |
| `notes` | text | 否 | 備註 |

建議索引：

- index: `course_projects.client_id`
- index: `course_projects.project_status`
- index: `course_projects.preview_expires_at`
- index: `course_projects.selected_proposal_id`

`project_status` 至少包含：

- `待資料確認`
- `已建檔`
- `待樣板提案`
- `樣板製作中`
- `樣板預覽中`
- `已選定樣板`
- `樣板逾期`
- `免費製作中`
- `正式頁預覽中`
- `試營運中`
- `已上線`
- `已取消`
- `needs_data`
- `template_failed`
- `needs_human_review`
- `retry_scheduled`

Future phase 可再加入：

- `待報價`
- `報價已送出`
- `待付款`

### `template_proposals`

每個 project 預設產生三筆，分別代表 A / B / C 三款樣板。

| 欄位 | 建議型別 | 必填 | 說明 |
| --- | --- | --- | --- |
| `proposal_id` | uuid / text pk | 是 | 提案主鍵 |
| `project_id` | uuid / text fk | 是 | 對應 `course_projects.project_id` |
| `proposal_code` | text | 是 | `A`、`B`、`C` |
| `proposal_name` | text | 是 | 樣板名稱 |
| `primary_template_id` | text | 是 | 主樣板 id |
| `secondary_template_id` | text | 否 | 輔助樣板 id |
| `source_url` | text | 否 | 主樣板來源 |
| `secondary_source_url` | text | 否 | 輔助樣板來源 |
| `canva_url` | text | 否 | Canva 編輯或檢視連結 |
| `preview_url` | text | 否 | 前端預覽網址 |
| `preview_token` | text unique | 是 | 不可猜測的預覽 token |
| `preview_status` | text | 是 | 預覽狀態 |
| `expires_at` | timestamp | 否 | 預覽到期時間 |
| `selected_at` | timestamp | 否 | 客戶選定時間 |
| `created_at` | timestamp | 是 | 建立時間 |
| `updated_at` | timestamp | 是 | 更新時間 |
| `notes` | text | 否 | 備註 |

建議索引：

- unique index: `template_proposals.preview_token`
- unique index: `template_proposals(project_id, proposal_code)`
- index: `template_proposals.project_id`
- index: `template_proposals.preview_status`
- index: `template_proposals.expires_at`

`preview_status` 至少包含：

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
- 客戶選定其中一款後，該筆標記為 `selected`，其餘兩筆標記為 `not_selected` 或 `cancelled`。
- 三天內未選擇時，三筆全部標記為 `expired`。

### `notification_logs`

記錄 Email 與 LINE 通知結果，避免通知是否成功只能靠外部服務查。

### `worker_runs`

記錄每次排程或 AI worker 執行結果，用來串接取件、Chat A / Canva、API POST 與錯誤追蹤。

| 欄位 | 建議型別 | 必填 | 說明 |
| --- | --- | --- | --- |
| `worker_run_id` | text pk | 是 | 每次 worker 執行的唯一 id |
| `project_id` | uuid / text fk | 否 | 對應處理的 project |
| `task_type` | text | 是 | 例如 `create_three_template_proposals` |
| `worker_id` | text | 是 | worker instance、cron 名稱或部署 id |
| `claimed_at` | timestamp | 否 | 成功取件並鎖定時間 |
| `started_at` | timestamp | 是 | run 開始時間 |
| `finished_at` | timestamp | 否 | run 結束時間 |
| `result` | text | 是 | `claimed`、`completed`、`needs_data`、`retry_scheduled`、`failed`、`lock_expired` |
| `failure_reason` | text | 否 | 失敗或跳過原因 |
| `retryable` | boolean | 是 | 是否屬於可重試錯誤 |
| `attempt` | integer | 是 | 第幾次嘗試 |
| `next_retry_at` | timestamp | 否 | 下一次可重試時間 |
| `external_request_id` | text | 否 | Canva / API / queue request id |
| `metadata` | json | 否 | 其他除錯資訊 |

建議索引：

- index: `worker_runs.project_id`
- index: `worker_runs.task_type`
- index: `worker_runs.result`
- index: `worker_runs.started_at`

### Worker 鎖定與重試欄位規則

- `worker_run_id` 必須在每次排程啟動時產生，並寫入 `worker_runs`。
- 取件時必須立刻鎖定 `course_projects`，不可只靠 `limit = 3` 查詢。
- `locked_at`、`locked_by`、`lock_expires_at` 必須與 `project_status = 樣板製作中` 同步寫入。
- 建議 `lock_expires_at = now + 20 至 30 分鐘`。
- 若 `lock_expires_at < now()` 且任務未完成，下一輪 worker 可回收該案件。
- 可重試錯誤需設定 `next_retry_at`，冷卻 30 分鐘到 1 小時。
- 不可重試錯誤或缺資料需標記 `needs_data` / `template_failed`，不可每 10 分鐘重跑。

| 欄位 | 建議型別 | 必填 | 說明 |
| --- | --- | --- | --- |
| `notification_id` | uuid / text pk | 是 | 通知主鍵 |
| `project_id` | uuid / text fk | 是 | 專案 id |
| `client_id` | uuid / text fk | 是 | 客戶 id |
| `channel` | text | 是 | `email` 或 `line` |
| `notification_type` | text | 是 | 通知類型 |
| `recipient` | text | 是 | Email address 或 LINE user id |
| `message_content` | text | 是 | 實際送出的內容 |
| `sent_status` | text | 是 | `pending`、`sent`、`failed`、`skipped` |
| `sent_at` | timestamp | 否 | 送出時間 |
| `error_message` | text | 否 | 失敗原因 |
| `created_at` | timestamp | 是 | 建立時間 |

`channel`：

- `email`
- `line`

`notification_type`：

- `data_confirmed`
- `template_proposal_started`
- `preview_ready`
- `expiry_reminder`
- `preview_expired`
- `proposal_selected`
- `production_started`
- `final_preview_ready`
- `project_live`

建議索引：

- index: `notification_logs.project_id`
- index: `notification_logs.client_id`
- index: `notification_logs.notification_type`
- index: `notification_logs.sent_status`
- index: `notification_logs.created_at`

### `line_conversations`

建議建立此表保存 LINE AI 對話與標準化資料，方便追蹤 AI 整理過程與補問缺漏。

| 欄位 | 建議型別 | 必填 | 說明 |
| --- | --- | --- | --- |
| `conversation_id` | uuid / text pk | 是 | 對話主鍵 |
| `line_user_id` | text | 是 | LINE 使用者 id |
| `client_id` | uuid / text fk | 否 | 建檔後可回填 |
| `project_id` | uuid / text fk | 否 | 建檔後可回填 |
| `message_direction` | text | 是 | `inbound` 或 `outbound` |
| `message_text` | text | 是 | 原始訊息 |
| `normalized_payload` | json | 否 | AI 整理後 JSON |
| `missing_fields` | json | 否 | 缺少欄位 |
| `ai_confidence` | numeric | 否 | AI 信心分數 |
| `created_at` | timestamp | 是 | 建立時間 |
| `notes` | text | 否 | 備註 |

## 標準 JSON 輸入

LINE AI 在客戶確認資料後，應交給 worker 類似以下 payload：

```json
{
  "line_user_id": "Uxxxxxxxx",
  "line_display_name": "王小美",
  "client": {
    "client_name": "王小美",
    "phone": "0912-345-678",
    "email": "hello@example.com",
    "brand_name": "小美瑜伽教室",
    "location_area": "台北市大安區"
  },
  "course_project": {
    "course_name": "初階瑜伽招生班",
    "course_type": "yoga",
    "course_format": "實體",
    "course_location": "台北市大安區",
    "expected_launch_date": "2026-06-10",
    "expected_start_date": "2026-07-01",
    "course_price": 6800,
    "course_capacity": 20,
    "target_audience": "初學者與久坐上班族",
    "course_features": "小班制、姿勢調整、附練習講義",
    "has_photos": true,
    "has_copywriting": false,
    "needs_template_proposal": true
  },
  "confirmed_by_client": true
}
```

最低必填欄位建議：

- `line_user_id`
- `client.client_name`
- `course_project.course_name`
- `course_project.course_type`
- `confirmed_by_client`

Email 或 phone 至少應有一個，否則通知客戶與正式頁確認會不穩定。

## `project_status` 狀態流程

```text
待資料確認
  ↓ 客戶確認資料
已建檔
  ↓ 需要樣板提案
待樣板提案
  ↓ Chat A 開始產生 Canva 樣板
樣板製作中
  ↓ Chat B 完成三個 preview_url 並已通知客戶
樣板預覽中
  ├─ 客戶三天內選款 → 已選定樣板 → 免費製作中 → 正式頁預覽中 → 試營運中 / 已上線
  └─ 三天內未選款 → 樣板逾期
```

可從任一未完成狀態進入：

- `已取消`

狀態更新原則：

- 建立 `course_projects` 後，若資料已確認且需要提案，狀態直接設為 `待樣板提案`。
- Chat A 開始執行時，狀態設為 `樣板製作中`。
- 三個 preview_url 完成並通知客戶後，狀態設為 `樣板預覽中`。
- 客戶選款時，先設為 `已選定樣板`，通知完成後推進到 `免費製作中`。
- Chat B 完成正式招生頁後，狀態設為 `正式頁預覽中`。
- 客戶確認正式頁後，狀態設為 `試營運中` 或 `已上線`。
- 逾期未選時，設為 `樣板逾期`。

## `template_proposals` 狀態流程

```text
draft
  ↓ Canva 樣板與 template id 完成
preview_ready
  ↓ Email / LINE 已通知客戶
sent
  ├─ 客戶選擇此款 → selected
  ├─ 客戶選擇其他款 → not_selected / cancelled
  └─ expires_at 已過且未選 → expired
```

狀態規則：

- A / B / C 三筆初始為 `draft`。
- preview_url 完成後改為 `preview_ready`。
- 通知成功後可改為 `sent`。
- 選款時必須在同一個交易中更新三筆 proposals 與 `course_projects`。
- 逾期 worker 應只處理沒有任何 `selected` proposal 的 project。

## AI Worker 自動化流程

### 自動化任務列表

目前 MVP 建議 worker tasks：

- `intake_confirmed_line_payload`：接收 LINE AI 確認後的標準 JSON，建立或更新客戶與課程專案。
- `create_three_template_proposals`：觸發 Chat A / Canva 並建立 A / B / C 三款樣板提案。
- `publish_template_preview_urls`：觸發 Chat B / Codex 產生三個 preview URL，設定三天期限。
- `send_preview_ready_notifications`：寄送三款樣板完成通知。
- `send_preview_expiry_reminder`：逾期前一天提醒。
- `record_client_template_selection`：記錄客戶選定 A / B / C。
- `start_free_production_after_selection`：選款後進入免費製作中，並通知內部與客戶。
- `start_final_page_production`：觸發 Chat B / Codex 製作正式招生頁。
- `send_final_preview_ready_notifications`：正式頁預覽完成後通知客戶確認。
- `mark_project_live`：客戶確認正式頁後，標記 `試營運中` 或 `已上線`。
- `expire_unselected_template_previews`：三天未選款時作廢三款樣板預覽。

Future phase tasks：

- `generate_quotation`
- `send_quotation`
- `track_payment_status`

### 1. 客戶確認資料後

觸發條件：

- LINE AI 收到客戶回覆「確認」。
- LINE AI 已整理出標準 JSON。

流程：

1. 接收標準 JSON。
2. 將原始訊息與 normalized payload 寫入 `line_conversations`。
3. 檢查必填欄位是否完整。
4. 若缺少欄位，回傳 `missing_fields` 給 LINE AI 繼續追問。
5. 若完整，用 `line_user_id` 建立或更新 `clients`。
6. 建立 `course_projects`。
7. `project_status` 設為 `待樣板提案`。
8. 寫入 `notification_logs`，類型可為 `data_confirmed`。
9. 通知後台有新案件。

建議 worker task 名稱：

- `intake_confirmed_line_payload`

### 2. 觸發三款樣板提案

觸發條件：

- `course_projects.project_status = 待樣板提案`
- `needs_template_proposal = true`
- `lock_expires_at is null or lock_expires_at < now()`
- `next_retry_at is null or next_retry_at <= now()`

流程：

1. 產生 `worker_run_id`。
2. 建立 `worker_runs`，記錄 `task_type = create_three_template_proposals`、`started_at`。
3. 用 atomic claim 取件並立刻鎖定 project，不可先查 `limit = 3` 再逐筆處理。
4. 鎖定成功時同步寫入：
   - `project_status = 樣板製作中`
   - `locked_at = now()`
   - `locked_by = worker_id`
   - `lock_expires_at = now() + 20 至 30 分鐘`
   - `worker_run_id`
5. 檢查必要資料，至少包含 `course_name`、可用於樣板提案的 `course_summary` / 課程描述、必要圖片或素材狀態。
6. 若缺少不可替代資料，標記 `needs_data` 或 `template_failed`，寫入 `missing_required_fields` 與 `last_worker_error`，結束本次 run，不進入重試循環。
7. 觸發 Chat A / Canva 樣板提案流程。
8. 寫入 `notification_logs`，通知類型為 `template_proposal_started`。
9. 根據 `course_type` 從 `docs/TEMPLATE_REFERENCE.md` 挑出 3 款 proposal。
10. 為 A / B / C 各建立或更新一筆 `template_proposals`。
11. 每筆記錄 `proposal_code`、`proposal_name`、`primary_template_id`、`secondary_template_id`、`source_url`、`secondary_source_url`、`canva_url`。
12. 三筆 proposal 初始 `preview_status = draft`。
13. 完成時清除鎖定欄位或讓狀態進入下一階段，並更新 `worker_runs.finished_at`、`result = completed`。

注意：

- `docs/TEMPLATE_REFERENCE.md` 目前尚未建立，因此此步驟目前只能作為待串接規格。
- 若找不到足夠 3 款樣板，worker 應標記任務需要人工處理，不應自行編造不存在的 template id。
- 若 Chat A / Canva API timeout、rate limit 或寫回 API timeout，屬於可重試錯誤，應設定 `next_retry_at = now() + 30 到 60 分鐘`，並記錄 `worker_runs.result = retry_scheduled`。
- 若 `樣板製作中` 超過 `lock_expires_at` 仍未完成，下一輪 worker 可回收並重新 claim，但必須保留前一次 `worker_run_id` 的失敗紀錄。

建議 worker task 名稱：

- `create_three_template_proposals`

### 3. 產生三個預覽網址

觸發條件：

- Chat A 已完成三款 Canva 樣板。
- A / B / C 三筆 `template_proposals` 已有 template id 與 Canva link。

流程：

1. 觸發 Chat B / Codex 製作三個前端預覽頁。
2. 為每筆 proposal 產生不可猜測的 `preview_token`。
3. 產生三個 `preview_url`。
4. 寫入 `template_proposals.preview_url`。
5. `preview_status` 設為 `preview_ready`。
6. `expires_at` 設為目前時間 + 3 天。
7. `course_projects.preview_expires_at` 同步寫入三筆 proposal 的最早到期時間。
8. `project_status` 改為 `樣板預覽中`。

建議 worker task 名稱：

- `publish_template_preview_urls`

### 4. 自動通知客戶

觸發條件：

- 三筆 proposal 都有 `preview_url`。
- 三筆 proposal 都是 `preview_ready`。

流程：

1. 自動寄 Email 給客戶。
2. 自動透過 LINE 通知客戶。
3. 通知內容包含 A / B / C 樣板名稱、三個 preview_url、三天期限、作廢提醒與回覆方式。
4. 寫入 `notification_logs`。
5. 通知成功後，proposal 狀態可從 `preview_ready` 改為 `sent`。

建議 worker task 名稱：

- `send_preview_ready_notifications`

### 5. 逾期前一天提醒

觸發條件：

- `project_status = 樣板預覽中`
- 尚無任何 proposal 為 `selected`
- `expires_at` 距目前時間小於等於 24 小時
- 尚未送過同一 project 的 `expiry_reminder`

流程：

1. 透過 LINE 與 Email 提醒客戶。
2. 通知類型為 `expiry_reminder`。
3. 寫入 `notification_logs`。

建議 worker task 名稱：

- `send_preview_expiry_reminder`

### 6. 客戶選款

觸發條件：

- 客戶透過 LINE 或預覽頁回覆「我要 A 款 / 我要 B 款 / 我要 C 款」。

流程：

1. 找到該客戶目前 `project_status = 樣板預覽中` 的 project。
2. 找到對應 `proposal_code`。
3. 確認 proposal 未逾期。
4. 在同一個交易中更新：
   - 被選 proposal：`preview_status = selected`、`selected_at = now`
   - 其他 proposal：`preview_status = not_selected` 或 `cancelled`
   - `course_projects.selected_proposal_id`
   - `course_projects.selected_primary_template_id`
   - `course_projects.selected_secondary_template_id`
   - `course_projects.project_status = 已選定樣板`
5. 自動通知內部與客戶。
6. 通知完成後，`course_projects.project_status` 改為 `免費製作中`。
7. 寫入 `notification_logs`，通知類型為 `production_started`。
8. 觸發 Chat B / Codex 依照選定樣板製作正式招生頁。

建議 worker task 名稱：

- `record_client_template_selection`
- `start_free_production_after_selection`

### 7. 正式招生頁製作與確認

觸發條件：

- `course_projects.project_status = 免費製作中`
- 已存在 `selected_proposal_id`
- 已存在 `selected_primary_template_id`

流程：

1. 觸發 Chat B / Codex 依照選定樣板製作正式招生頁。
2. 正式頁完成後，寫入正式頁預覽網址或交付連結。
3. 自動通知客戶正式頁可預覽確認，通知類型為 `final_preview_ready`。
4. `course_projects.project_status` 改為 `正式頁預覽中`。
5. 客戶確認正式頁後，`course_projects.project_status` 改為 `試營運中` 或 `已上線`。
6. 發送上線通知，通知類型為 `project_live`。

建議 worker task 名稱：

- `start_final_page_production`
- `send_final_preview_ready_notifications`
- `mark_project_live`

### 8. 三天後作廢

觸發條件：

- `project_status = 樣板預覽中`
- `preview_expires_at < now`
- 沒有任何 proposal 是 `selected`

流程：

1. 將三筆 `template_proposals.preview_status` 改為 `expired`。
2. 將 `course_projects.project_status` 改為 `樣板逾期`。
3. 不一定刪除 preview_url 或靜態檔案。
4. 前台預覽頁應顯示逾期訊息。
5. 自動透過 LINE 與 Email 通知客戶預覽已逾期。
6. 寫入 `notification_logs`，類型為 `preview_expired`。

逾期頁面文案：

```text
此樣板預覽已超過選擇期限。請聯繫我們重新開啟預覽或安排重新提案。
```

建議 worker task 名稱：

- `expire_unselected_template_previews`

## 三天作廢邏輯

時間設定：

- `expires_at = preview_ready_at + interval '3 days'`
- `course_projects.preview_expires_at = min(template_proposals.expires_at)`

排程建議：

- 每 15 到 60 分鐘掃描一次即將逾期與已逾期 project。
- 逾期前提醒應避免重複發送，可用 `notification_logs` 檢查是否已發過 `expiry_reminder`。
- 逾期作廢應具備 idempotent 特性，重跑不應重複通知或破壞已選款 project。

安全條件：

- 如果任何 proposal 已是 `selected`，不得將 project 改為 `樣板逾期`。
- 如果 project 已進入 `已選定樣板`、`免費製作中`、`正式頁預覽中`、`試營運中`、`已上線`，逾期 worker 不應處理。
- 若要重新開啟預覽，應由後台產生新的 `expires_at`，並記錄 notes 或另外建立 reopen log。

## 預覽網址規則

建議使用 token URL，而不是暴露連續流水號。

公開網址：

```text
/demo/admission-system/preview/{preview_token}
```

內部可讀網址或後台輔助顯示：

```text
/demo/admission-system/preview/{project_id}/a
/demo/admission-system/preview/{project_id}/b
/demo/admission-system/preview/{project_id}/c
```

建議正式給客戶的網址只使用 `preview_token`：

- 不暴露連續 project id。
- token 使用至少 128-bit 隨機值。
- token 存在 `template_proposals.preview_token`。
- 前台用 token 查 proposal，並檢查 `preview_status` 與 `expires_at`。
- 逾期後不要 404，應顯示逾期提示頁。
- 若 proposal 被取消或不是被選款項，可顯示「此預覽目前不可使用，請聯繫我們確認最新版本。」

建議 URL 範例：

```text
/demo/admission-system/preview/ptk_8X4mJ9sQ2nV7cL5aR1zY
```

## Email 與 LINE 通知模板

實際訊息應保存到 `notification_logs.message_content`。

### 樣板提案開始通知

此通知主要給內部後台或負責人，用來確認案件已進入 Chat A / Canva 樣板提案流程。

內部通知：

```text
新案件已進入樣板提案

客戶：{client_name}
課程：{course_name}
課程類型：{course_type}
project_id：{project_id}
狀態：樣板製作中
```

### 三款樣板完成通知

Email subject：

```text
您的三款招生頁樣板預覽已完成
```

Email body：

```text
您好，{client_name}：

我們已為「{course_name}」準備好三款招生頁樣板預覽，請於 {expires_at} 前選擇最喜歡的一款。

A 款：{proposal_a_name}
{proposal_a_url}

B 款：{proposal_b_name}
{proposal_b_url}

C 款：{proposal_c_name}
{proposal_c_url}

您可以直接回覆「我要 A 款」、「我要 B 款」或「我要 C 款」。

提醒您：預覽網址將於三天後作廢。若需要協助選擇，也可以直接回覆我們。
```

LINE：

```text
您好，{client_name}，您的「{course_name}」三款招生頁樣板預覽已完成：

A 款：{proposal_a_name}
{proposal_a_url}

B 款：{proposal_b_name}
{proposal_b_url}

C 款：{proposal_c_name}
{proposal_c_url}

請於 {expires_at} 前回覆「我要 A 款」、「我要 B 款」或「我要 C 款」。三天後預覽網址會作廢。
```

### 逾期前一天提醒

Email subject：

```text
提醒：您的招生頁樣板預覽將於明天到期
```

Email body：

```text
您好，{client_name}：

提醒您，「{course_name}」的三款招生頁樣板預覽將於 {expires_at} 到期。

如果已經決定款式，請回覆「我要 A 款」、「我要 B 款」或「我要 C 款」。

若需要我們協助比較三款差異，也可以直接回覆這封信。
```

LINE：

```text
提醒您，「{course_name}」的樣板預覽將於 {expires_at} 到期。

若已決定款式，請回覆「我要 A 款」、「我要 B 款」或「我要 C 款」。需要協助也可以直接告訴我們。
```

### 客戶已選款通知

Email subject：

```text
已收到您的樣板選擇：{proposal_code} 款
```

Email body：

```text
您好，{client_name}：

我們已收到您選擇「{proposal_code} 款：{proposal_name}」作為「{course_name}」的招生頁方向。

目前為免費試營運階段，接下來我們會依照您選定的方向製作正式招生頁。完成後會再通知您預覽確認。
```

LINE：

```text
已收到您的選擇：{proposal_code} 款「{proposal_name}」。

目前為免費試營運階段，接下來我們會依照您選定的方向製作正式招生頁，完成後再通知您確認。
```

內部通知：

```text
客戶已選款

客戶：{client_name}
課程：{course_name}
選擇：{proposal_code} 款 - {proposal_name}
primary_template_id：{primary_template_id}
secondary_template_id：{secondary_template_id}
```

### 免費製作開始通知

Email subject：

```text
您的正式招生頁已開始製作
```

Email body：

```text
您好，{client_name}：

我們已依照您選定的「{proposal_code} 款：{proposal_name}」開始製作「{course_name}」正式招生頁。

目前為免費試營運階段，完成後我們會提供正式頁預覽連結給您確認。
```

LINE：

```text
您的「{course_name}」正式招生頁已開始製作。

我們會依照您選定的 {proposal_code} 款方向製作，完成後再提供正式頁預覽給您確認。
```

### 正式頁預覽完成通知

Email subject：

```text
您的正式招生頁預覽已完成
```

Email body：

```text
您好，{client_name}：

「{course_name}」正式招生頁預覽已完成，請點開以下連結確認：

{final_preview_url}

若內容沒有問題，請回覆「確認上線」。若需要微調，也可以直接回覆需要調整的地方。
```

LINE：

```text
「{course_name}」正式招生頁預覽已完成：
{final_preview_url}

若內容沒有問題，請回覆「確認上線」。若需要微調，也可以直接告訴我們。
```

### 專案上線通知

Email subject：

```text
您的招生頁已進入試營運
```

Email body：

```text
您好，{client_name}：

「{course_name}」招生頁已進入試營運 / 上線階段。

頁面連結：
{live_url}

如後續需要補充內容或調整資訊，請直接回覆我們。
```

LINE：

```text
「{course_name}」招生頁已進入試營運 / 上線階段：
{live_url}

後續如果需要補充或調整，也可以直接回覆我們。
```

### 預覽已作廢通知

Email subject：

```text
您的招生頁樣板預覽已到期
```

Email body：

```text
您好，{client_name}：

「{course_name}」的三款招生頁樣板預覽已超過選擇期限，原預覽網址目前已作廢。

若您仍想繼續選擇樣板，請回覆我們，我們可以協助重新開啟預覽或安排重新提案。
```

LINE：

```text
「{course_name}」的樣板預覽已超過選擇期限，原預覽網址目前已作廢。

若您想繼續選擇，請回覆我們，我們可以協助重新開啟預覽或安排重新提案。
```

## 後台畫面需求

### 1. 新案件列表

顯示：

- project id
- 客戶名稱
- 品牌名稱
- 課程名稱
- 課程類型
- project status
- 建立時間
- 下一步狀態
- 是否需要樣板提案
- 是否已進入免費製作
- 正式頁預覽狀態

篩選：

- project status
- course type
- 建立日期
- 是否逾期
- 是否已選款

### 2. 客戶資料頁

顯示：

- `client_name`
- `phone`
- `email`
- `line_user_id`
- `line_display_name`
- `brand_name`
- `location_area`
- `client_status`
- notes
- 此客戶所有 course projects

### 3. 課程專案詳情頁

顯示：

- 課程資料
- 目標受眾
- 課程特色
- 照片與文案狀態
- project status
- selected proposal
- preview expires at
- selected primary template id
- selected secondary template id
- final preview url
- live url
- notes

### 4. 樣板提案區

顯示 A / B / C 三款：

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
- `selected_at`

操作：

- 開啟 preview_url
- 開啟 Canva URL
- 標記重新產生預覽
- 手動標記 selected
- 取消 proposal

### 5. 通知紀錄區

顯示：

- channel
- notification_type
- recipient
- sent_status
- sent_at
- error_message
- message_content

操作：

- 重新寄送失敗通知
- 手動補寄 preview_ready
- 手動補寄 expiry_reminder
- 手動補寄 final_preview_ready
- 手動補寄 project_live

### 6. 下一步與預覽重開

後台應顯示：

- 客戶是否已選款
- 預覽網址是否逾期
- 下一步建議狀態
- 是否需要重新開啟預覽
- 是否已開始免費製作
- 正式頁是否已完成
- 客戶是否已確認正式頁

重新開啟預覽建議流程：

1. 後台按下重新開啟。
2. 更新三筆未 selected proposal 的 `expires_at = now + 3 days`。
3. `preview_status` 從 `expired` 改為 `sent` 或 `preview_ready`。
4. `project_status` 改回 `樣板預覽中`。
5. 自動補寄通知。

### 7. 免費製作與正式頁區

顯示：

- selected proposal
- selected primary template id
- selected secondary template id
- production status
- final preview url
- live url
- 客戶確認時間
- 最近一次通知狀態

操作：

- 標記免費製作開始
- 填入或更新正式頁預覽網址
- 發送正式頁預覽通知
- 標記客戶已確認
- 標記試營運中
- 標記已上線

## Chat B / Codex 前端實作分工

以下部分應交給 Chat B 實作前端或預覽頁：

- 三款招生頁預覽頁產生。
- `preview_token` 對應的 preview route。
- `/demo/admission-system/preview/{preview_token}` 頁面。
- 預覽頁到期檢查與逾期提示畫面。
- 如果支援頁面內選款，需提供 A / B / C 選擇操作並呼叫後端 API。
- 依照選定 proposal 製作正式招生頁。
- 正式頁預覽 route 與客戶確認操作。
- 試營運 / 已上線頁面的展示與連結。
- 後台頁面的 UI：新案件列表、客戶資料、課程專案詳情、樣板提案區、通知紀錄區。
- 後台重新開啟預覽、重新寄送通知、手動標記選款、正式頁預覽、標記上線的操作介面。

Chat B 不應自行決定資料表狀態機；應依本文件與後端 API contract 實作。

## 待建立 API

後續可規劃以下 API：

- `POST /api/line/intake-confirmed`
- `POST /api/projects/{project_id}/template-proposals`
- `POST /api/projects/{project_id}/preview-urls`
- `POST /api/projects/{project_id}/notifications/preview-ready`
- `POST /api/projects/{project_id}/select-proposal`
- `POST /api/projects/{project_id}/production/start`
- `POST /api/projects/{project_id}/final-preview`
- `POST /api/projects/{project_id}/go-live`
- `POST /api/projects/{project_id}/reopen-preview`
- `GET /api/admin/projects`
- `GET /api/admin/projects/{project_id}`
- `GET /api/preview/{preview_token}`

## 尚未實作

- 實際資料庫 migration。
- 後端 API。
- LINE webhook。
- Email provider。
- Canva 串接。
- Chat A / Chat B 的自動觸發介面。
- admin 後台。
- 前端 preview route。

## Future phase

以下流程保留為未來商業化階段，暫不放入目前免費試營運 MVP 主流程：

- 報價
- 報價已送出
- 客戶確認報價
- 自動產生報價單
- 自動寄出報價
- 待付款
- 付款
- 訂閱
- 續約
