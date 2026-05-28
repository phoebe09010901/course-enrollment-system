# AI Worker Workflow

## 文件目的

本文件定義 `worker/` 的預期責任、AI worker 的任務流程與目前尚未實作的邊界。它是初始化規格，不代表 repo 已有背景工作程式、排程服務、佇列或 AI 產生流程。

## 目前狀態

| 項目 | 狀態 |
| --- | --- |
| `worker/README.md` | 已建立初始規格 |
| worker 程式碼 | 尚未建立 |
| 排程器 | 尚未建立 |
| 任務佇列 | 尚未建立 |
| AI provider 串接 | 尚未建立 |
| 儲存層 | 尚未建立 |
| 重試與錯誤處理 | 尚未建立 |
| 鎖定與 worker run log | 尚未建立，已補規格 |

## Worker 預期角色

`worker/` 應負責非同步、可重試、可追蹤的背景任務。它不應直接承擔前台頁面、LINE webhook 或 admin UI 的職責。

預期任務包含：

- 產生課程招生素材草稿。
- 根據 style 定義套用品牌規則。
- 批次整理或轉換素材資料。
- 處理需要較長時間的 AI 生成工作。
- 將任務狀態回寫給 admin 或其他服務讀取。

目前以上都只是預期任務，尚未有任何實作。

## 與其他模組的邊界

| 模組 | 與 worker 的關係 |
| --- | --- |
| `styles/` | 提供 worker 可讀取的品牌與視覺 token，尚未建立 |
| `skills/` | 提供 AI Agent 選擇或套用規則的操作邏輯，尚未建立 |
| `templates/` | 提供 worker 可填入資料的輸出結構，尚未建立 |
| `line-webhook/` | 可建立任務或接收狀態，但不應執行長時間工作，尚未建立 |
| `admin/` | 可檢視、建立、重跑或取消任務，尚未建立 |
| `public/` | 可承載 worker 產出的公開素材，尚未建立 |

## 任務生命週期

未來 worker 任務應至少能描述以下狀態：

1. `queued`：任務已建立，等待執行。
2. `running`：worker 正在處理。
3. `needs_input`：缺少必要資料，需要人類或 admin 補充。
4. `completed`：任務完成並有可讀輸出。
5. `failed`：任務失敗，需記錄原因。
6. `cancelled`：任務被取消。
7. `retry_scheduled`：暫時性錯誤後等待冷卻時間再重試。

每個任務建議記錄：

- `id`
- `type`
- `status`
- `input`
- `output`
- `error`
- `createdAt`
- `updatedAt`
- `attempts`
- `worker_run_id`
- `lockedAt`
- `lockedBy`
- `lockExpiresAt`
- `lastRunStartedAt`
- `lastRunFinishedAt`
- `nextRetryAt`
- `source`

## 取件鎖定規則

排程 worker 不可以只靠 `limit = 3` 或單純輪詢挑案件。任何會處理 project 的任務都必須支援「取到就立刻鎖定」。

基本規則：

- 每次 worker 啟動先產生唯一 `worker_run_id`。
- 取件與鎖定必須是同一個 atomic operation。
- 同一個 `project_id` 在鎖定期間只能被一個 `worker_run_id` 處理。
- 鎖定欄位至少包含 `locked_at`、`locked_by`、`lock_expires_at`、`worker_run_id`。
- `locked_by` 可使用部署名稱、instance id 或排程名稱。
- `lock_expires_at` 建議設定為 `now + 20 至 30 分鐘`。

建議 atomic claim 條件：

```sql
UPDATE course_projects
SET
  project_status = '樣板製作中',
  locked_at = now(),
  locked_by = :worker_id,
  lock_expires_at = now() + interval '30 minutes',
  worker_run_id = :worker_run_id,
  updated_at = now()
WHERE project_id IN (
  SELECT project_id
  FROM course_projects
  WHERE project_status = '待樣板提案'
    AND needs_template_proposal = true
    AND (
      lock_expires_at IS NULL
      OR lock_expires_at < now()
    )
    AND (
      next_retry_at IS NULL
      OR next_retry_at <= now()
    )
  ORDER BY created_at ASC
  LIMIT 3
  FOR UPDATE SKIP LOCKED
)
RETURNING *;
```

若使用的資料庫不支援 `FOR UPDATE SKIP LOCKED`，必須用等效的 compare-and-set 條件實作，不能先 select 再慢慢 update。

## 鎖定逾時回收

Chat A / Canva 或外部 API 可能中斷，因此 `processing_template` / `樣板製作中` 不可永遠卡住。

回收規則：

- 若 `project_status = 樣板製作中` 且 `lock_expires_at < now()`，視為鎖定逾時。
- 逾時後可重新排入 `待樣板提案` 或 `retry_scheduled`，但必須增加 attempts 並記錄上一個 `worker_run_id`。
- 建議 processing 逾時為 20 到 30 分鐘。
- 若同一 project 多次逾時，應升級為 `template_failed` 或 `needs_human_review`，不要無限重試。

## 錯誤分類與冷卻

錯誤需分成不可重試、可重試與需要補資料。

不可重試或需要補資料：

- 缺少 `course_name`。
- 缺少 `course_summary` 或足以產生樣板文案的課程描述。
- 缺少必要圖片集合，例如 `r2_images`，且該樣板流程要求圖片。
- 找不到足夠三款樣板來源或 template id。

處理方式：

- 標記 `needs_data`、`template_failed` 或 `needs_human_review`。
- 寫入缺少欄位與修正建議。
- 不可每 10 分鐘重新重跑。

可重試錯誤：

- Canva API timeout / rate limit / 暫時性 5xx。
- 寫回 API timeout。
- DNS / host resolution 暫時失敗，例如 `curl: (6) Could not resolve host: ftm.com.tw`。
- 網路中斷。
- 暫時性儲存服務錯誤。

處理方式：

- 狀態設為 `retry_scheduled` 或保留原任務狀態並設定 `next_retry_at`。
- 建議冷卻時間 30 分鐘到 1 小時。
- 重試時仍需重新 atomic claim。
- 超過最大嘗試次數後改為 `template_failed`，並保留最後錯誤。
- 對 claim / success / fail callback 這類 direct API POST，可針對 DNS / host resolution 做短間隔 bounded retry，例如最多 3 次；不可無限重試，也不可改用 browser automation 當 fallback。

## Automation 執行環境巡檢

排程 worker 除了 API 邏輯，也要檢查執行上下文是否穩定。

- automation 的 `cwds` 必須指向目前存在的 worktree。
- 若 worker 規則要求 actual cwd 必須完全等於 configured cwd，cron automation 應使用 `local` execution environment；`worktree` execution environment 可能建立每輪 generated worktree，導致 actual cwd 變成非 configured cwd。
- 若 run log 出現不存在或非 configured cwd 的 worktree，例如 `/Users/phoebe/.codex/worktrees/4938/課程招生 - 系統`、`/Users/phoebe/.codex/worktrees/4d27/課程招生 - 系統` 或 `/Users/phoebe/.codex/worktrees/1210/課程招生 - 系統`，應視為 stale worktree drift。
- stale worktree drift 發生時，必須在 DNS / health check / claim / callback / Canva generation 前停止，並記錄 `stale_worktree_context`。
- stale worktree drift 發生時，先檢查 automation 設定檔、scheduler run context、cache、session 或殘留 automation。
- 不同 Chat 名稱可存在於 `worker_run_id`，但後端 claim 的 `worker_name` / `locked_by` 應使用穩定功能名，例如 `course-canva-proposal-worker`。
- 若同一 automation 多次在 direct claim 前 DNS 解析失敗，應標記為 infra blocker，而不是 Canva generation failure。
- Chat G automation 在 claim 前必須先執行 `scripts/chat-g-network-preflight.sh`，固定記錄 `pwd`、DNS resolver、system DNS probe、public DNS probe、health curl timing 與 health response。
- `scripts/chat-g-network-preflight.sh` 保留 3 次 bounded retry；若三次皆為 `curl: (6) Could not resolve host`，必須寫入 `dns_resolution_failed` 並停止，不可進入 claim、success/fail callback 或 Canva generation。

## Worker Run Log

每次 worker 執行都必須建立 `worker_run_id`，並把取件、Chat A / Canva 建稿、API POST 結果串起來。

log 至少包含：

- `worker_run_id`
- `project_id`
- `task_type`
- `worker_id`
- `claimed_at`
- `started_at`
- `finished_at`
- `result`
- `failure_reason`
- `retryable`
- `next_retry_at`
- `attempt`
- `external_request_id`

建議 `result`：

- `claimed`
- `skipped`
- `completed`
- `needs_data`
- `retry_scheduled`
- `failed`
- `lock_expired`

## 建議任務類型

初期可先規劃以下任務類型，但尚不建立程式碼：

- `course_intake_form_submitted`：處理網頁表單送出 payload，建立或更新客戶與課程專案。
- `generate_course_copy`：產生招生文案草稿。
- `select_style`：根據課程與受眾選擇 style。
- `render_template_payload`：將課程資料轉成模板可用資料。
- `generate_asset_prompt`：產生圖片或素材生成 prompt。
- `batch_review_outputs`：批次檢查產出是否符合品牌規則。
- `create_three_template_proposals`：為課程專案建立 A / B / C 三款樣板提案。
- `publish_template_preview_urls`：寫入三個前端預覽網址並設定三天期限。
- `send_preview_ready_notifications`：通知客戶三款樣板已可預覽。
- `send_preview_expiry_reminder`：在預覽到期前一天提醒客戶。
- `record_client_template_selection`：記錄客戶選擇的樣板。
- `start_free_production_after_selection`：選款後將專案推進到免費製作中。
- `start_final_page_production`：觸發 Chat B / Codex 依照選定樣板製作正式招生頁。
- `send_final_preview_ready_notifications`：正式頁預覽完成後通知客戶確認。
- `mark_project_live`：客戶確認正式頁後將專案標記為試營運中或已上線。
- `expire_unselected_template_previews`：將逾期且未選款的三款預覽標記作廢。

任務命名應保持穩定，避免每次 chat 依照描述臨時發明新名稱。

## Chat A / Canva Proposal Ready Gate

`create_three_template_proposals` 完成前，必須通過以下 ready gate：

1. proposal 數量必須剛好是 3。
2. 三筆 `proposal_code` 必須剛好是 `A`、`B`、`C`。
3. A / B / C 三款都必須有真實可開啟的 `canva_url`。
4. 任一 proposal 缺 `canva_url` 時，整個 batch 不可 ready，只能維持 `pending` 或標記 `failed`。
5. 三款都必須保存 `primary_template_id`、`secondary_template_id`、`source_url`、`secondary_source_url`。
6. 不可只保存客戶顯示名稱、Canva 顯示名稱或 proposal 顯示名稱。
7. 同一 `project_id` 若已有有效 proposal batch，除非有明確重產指令，不可自動建立第二批。

未通過 ready gate 時，不可觸發 `publish_template_preview_urls`，也不可寄送 preview ready 通知。

## AI Worker 標準流程

未來每個 AI worker 任務建議依照以下流程：

1. 讀取任務輸入與既有專案資料。
2. 驗證必要欄位是否存在。
3. 若缺資料，將任務標記為 `needs_input`，並列出缺口。
4. 讀取相關 style、template 或 skill 規格。
5. 執行 AI 推理或內容生成。
6. 產出結構化結果，而不是只輸出自由文字。
7. 驗證結果是否符合規格與品牌限制。
8. 儲存輸出、錯誤、嘗試次數與狀態。

## 錯誤與重試原則

- 可重試錯誤應記錄原因與嘗試次數。
- 不可重試錯誤應標記為 `failed`，並保留可讀錯誤訊息。
- 缺少資料不應被視為系統錯誤，應標記為 `needs_input`。
- 缺少必要資料時不得靠排程每 10 分鐘重撞，必須停止自動重試並等待補資料。
- 暫時性錯誤必須有 `next_retry_at` 冷卻時間，建議 30 分鐘到 1 小時。
- 每次嘗試都必須可用 `worker_run_id` 串查。
- AI 輸出不符合格式時，應先嘗試修正或重新要求結構化輸出。
- 不應靜默吞掉錯誤。

## 待決策

- worker 是否使用 Node.js、Python 或其他執行環境。
- 是否需要資料庫、檔案系統或外部佇列。
- 是否與 LINE webhook、admin 共用同一個部署單位。
- AI provider、模型與成本限制。
- 任務輸入與輸出的 JSON schema。
- 實際第一個課程招生任務案例。

## 相關流程文件

- `docs/BACKEND_AUTOMATION_FLOW.md`：規劃網頁表單送出、資料庫建檔、後台、三款樣板預覽、通知與三天作廢流程。

## 實作前檢查

在新增 worker 程式碼前，應先確認：

- `worker/README.md` 是否仍符合本文件。
- 是否已有至少一個明確任務類型與輸入輸出格式。
- 是否已決定狀態儲存方式。
- 是否知道產出會被哪個模組讀取。
- 是否有避免把 webhook、admin 或模板邏輯塞進 worker。
