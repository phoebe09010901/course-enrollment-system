# 課程招生 - 系統

## 協作接手

另一台電腦接手請先看：

```text
docs/REMOTE_COLLABORATION.md
docs/PROJECT_STATUS.md
docs/COLLABORATION_SETUP.md
```

目前測試環境：

```text
https://ftm.com.tw/demo/admission-system
```

公開課程資料表單：

```text
https://ftm.com.tw/demo/admission-system/public-course-intake.php
```

照片素材可設定 Cloudflare R2 儲存。啟用後 MySQL 只保留圖片瀏覽網址；未設定 R2 時會使用本機 `public/uploads/course-intakes/` 作為測試備援。

公開表單可設定 Cloudflare Turnstile。啟用後，後端會驗證 Turnstile token，驗證失敗不會寫入 MySQL 或處理圖片。

## LINE Intake API

Endpoint:

```text
POST /api/line-intakes
```

Auth:

```text
X-Admission-Api-Key: {ADMISSION_API_KEY}
```

也支援：

```text
Authorization: Bearer {ADMISSION_API_KEY}
```

設定方式：

1. 套用 `database/migrations/001_create_line_intakes.sql`。
2. 複製 `config/local.example.php` 為 `config/local.php`。
3. 填入 DB 連線與 `api_key`。
4. Cloudflare Worker 將 LINE intake JSON POST 到 `/api/line-intakes`。
5. 後台 `admin/clients.php` 會讀取 `clients` 與 `course_intakes` 顯示客戶資料。
6. Chat D 的 Canva 樣板資料流使用 `course_projects`、`template_proposals`、`notification_logs`，migration 在 `database/migrations/002_create_chat_d_template_flow.sql`；既有環境請再套用 `database/migrations/004_harden_chat_a_template_queue.sql`。公開課程表單送出後會建立 `course_projects`，狀態會進入 `pending_template`，等待雲端 Chat A worker 原子化領取。

## Chat A 自動觸發

公開課程表單儲存成功後會呼叫 `lib/chat_a_trigger.php`。請在 `config/local.php` 或環境變數設定：

```text
CHAT_A_TRIGGER_WEBHOOK_URL=https://your-chat-a-worker.example.com/trigger
CHAT_A_TRIGGER_SECRET=replace-with-chat-a-webhook-secret
CHAT_A_TRIGGER_TIMEOUT=3
```

Webhook 會收到 `project_id`、客戶選版頁、課程資料、圖片資產 raw payload，以及 Chat D 回填端點 `api/template-proposals/`。Chat A 完成 Canva 三款樣板後，仍需依照下方 API 寫回 `template_proposals`。

需要補觸發既有專案時，可 POST 到：

```text
POST /api/chat-a-trigger/
X-Admission-Api-Key: {ADMISSION_API_KEY}

{"project_id":"CP-20260528-00011"}
```

雲端 Chat A worker 需先用同一把 API key 原子化領取待處理專案；領取成功時系統會把案件更新為 `processing_template`，並記錄 `worker_run_id`、`template_processing_started_at`：

```text
POST /api/chat-a-trigger/claim.php
X-Admission-Api-Key: {ADMISSION_API_KEY}

{"limit":3,"worker_run_id":"chat-a-20260528-001","worker_name":"chat-a-canva-cron"}
```

回傳的每筆 project payload 已包含課程資料、圖片 raw payload、客戶選版頁、`proposal_batch_id`、`worker_run_id` 與 Chat D 回填端點。`GET /api/chat-a-trigger/pending.php?limit=3` 只作為檢查待處理資料使用，不應作為正式執行入口。

若 Canva 產生或 worker 執行失敗，請回寫：

```text
POST /api/chat-a-trigger/fail.php
X-Admission-Api-Key: {ADMISSION_API_KEY}

{"project_id":"CP-20260528-00011","worker_run_id":"chat-a-20260528-001","error_code":"canva_generation_failed","error_message":"Canva API returned ..."}
```

## Canva 樣板提案 API

Chat A 產出三款 Canva 樣板後，POST 到 `api/template-proposals/`，payload 保留 `primary_template_id`、`secondary_template_id`、`source_url`、`secondary_source_url`、`canva_url`、`expires_at`：

```json
{
  "project_id": "CP-20260527-00001",
  "proposal_batch_id": "batch_xxxxxxxxxxxxxxxxxxxxxxxx",
  "worker_run_id": "chat-a-20260528-001",
  "expires_at": "2026-06-03 23:59:59",
  "proposals": [
    {
      "proposal_id": "A",
      "proposal_code": "A",
      "proposal_name": "A 款",
      "primary_template_id": "...",
      "secondary_template_id": "...",
      "source_url": "https://www.canva.com/...",
      "secondary_source_url": "https://www.canva.com/...",
      "visual_direction": "Chat A 原始方向",
      "suitable_reason": "Chat A 原始理由",
      "canva_url": "https://www.canva.com/design/..."
    }
  ]
}
```

客戶選定後，POST 到 `api/template-proposals/select.php`：

```json
{
  "project_id": "CP-20260527-00001",
  "proposal_id": "A"
}
```

系統會寫入 `selected_template_id`、`selected_secondary_template_id`、`selected_canva_direction`、`selected_canva_url`、`template_selected_at`，並在 `notification_logs` 記錄 `proposal_selected`。

範例 payload:

```json
{
  "source": "line_ai",
  "line": {
    "user_id": "Uxxxxxxxx",
    "display_name": "王小明"
  },
  "client": {
    "client_name": "王小明",
    "phone": "0912345678",
    "email": "demo@example.com",
    "brand_name": "小明英文教室",
    "location_area": "台北"
  },
  "course": {
    "course_name": "兒童英語口說班",
    "course_type": "親子兒童",
    "course_format": "實體課",
    "course_location": "台北市",
    "target_audience": "國小三到六年級",
    "course_features": "小班制、口說練習、成果發表"
  }
}
```
