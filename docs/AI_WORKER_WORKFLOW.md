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
- `source`

## 建議任務類型

初期可先規劃以下任務類型，但尚不建立程式碼：

- `intake_confirmed_line_payload`：處理 LINE AI 已確認的標準 JSON，建立或更新客戶與課程專案。
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

- `docs/BACKEND_AUTOMATION_FLOW.md`：規劃 LINE AI 收件、資料庫建檔、後台、三款樣板預覽、通知與三天作廢流程。

## 實作前檢查

在新增 worker 程式碼前，應先確認：

- `worker/README.md` 是否仍符合本文件。
- 是否已有至少一個明確任務類型與輸入輸出格式。
- 是否已決定狀態儲存方式。
- 是否知道產出會被哪個模組讀取。
- 是否有避免把 webhook、admin 或模板邏輯塞進 worker。
