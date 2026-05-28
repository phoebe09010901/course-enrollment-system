# Project Status

## 狀態摘要

目前專案處於初始化與流程重構階段。已建立主要文件與 Cloudflare Worker 參考實作。

2026-05-27 最新決策：LINE AI 客服正式取消「AI 幫客戶填表 / 逐步收欄位」流程，改為「課程招生頁系統接待助理」。資料收集改由網頁表單處理。

## 已確認存在

- `.git`
- `docs/PROJECT_CONTEXT.md`
- `docs/PROJECT_STATUS.md`
- `docs/STYLE_SYSTEM.md`
- `docs/COLLABORATION_SETUP.md`
- `docs/LINE_AI_CUSTOMER_SERVICE_FLOW.md`
- `docs/LINE_AI_TEST_REPORT.md`
- `docs/CHAT_C_FIX_REQUEST.md`
- `docs/AI_WORKER_WORKFLOW.md`
- `docs/BACKEND_AUTOMATION_FLOW.md`
- `docs/LINE_AI_ADVANCED_QA_PLAN.md`
- `docs/LINE_AI_WORKER_TEST_SCENARIOS.md`
- `docs/HANDOFF_FOR_NEW_COMPUTER.md`
- `cloudflare-workers/workers.js`
- `cloudflare-workers/worker.js`
- `cloudflare-workers/line-webhook-worker.js`
- `tests/line-ai-worker-scenarios.test.mjs`

## 尚未建立但曾被規劃的文件

- `docs/ARCHITECTURE.md`
- `docs/TEMPLATE_REFERENCE.md`
- `docs/CLIENT_SELECTION_FLOW.md`

## 已完成

- 建立專案背景文件。
- 建立專案狀態文件。
- 建立美術風格系統初始文件。
- 建立 AI 協作規則文件。
- 建立 LINE AI 客服流程文件。
- 建立 Cloudflare Worker 可部署版本。
- 已同步三份 Worker 檔案：
  - `cloudflare-workers/workers.js`
  - `cloudflare-workers/worker.js`
  - `cloudflare-workers/line-webhook-worker.js`
- Worker 版本：`chat-c-receptionist-form-link-2026-05-27-20`。
- 已取消 LINE AI 複雜資料收集狀態機。
- 已取消 LINE AI 詢問姓名、Email、LINE ID Link、課程資料與照片素材。
- 已取消 LINE AI `client_intake_confirmed` JSON 輸出。
- 已取消 LINE AI 對 `clients` / `course_projects` 的建檔責任。
- LINE AI 現在只負責：
  - 打招呼。
  - 說明免費試營運。
  - 說明完整流程。
  - 提供課程資料表連結。
  - 回答流程、免費、三款預覽、三天期限與網站 / 系統問題。
  - 防護系統外指令。
- 已更新 `docs/LINE_AI_CUSTOMER_SERVICE_FLOW.md` 為新版「表單導向」流程。
- 已更新 `docs/COLLABORATION_SETUP.md` 的 Chat C / Chat D / Chat E 分工。
- 已補上 Chat F 提醒的排程 / Worker 安全規格：取件 atomic lock、processing 20 到 30 分鐘逾時回收、可重試 / 不可重試錯誤分類、30 到 60 分鐘 retry cooldown、`worker_run_id` 與 `worker_runs` log schema。
- 已補上 Chat A / Canva proposal ready gate：proposal 必須剛好 3 筆且為 A / B / C，三筆都要有真實 `canva_url`、`primary_template_id`、`secondary_template_id`、`source_url`、`secondary_source_url`，缺任一欄不可 ready，也不可在同一 project 自動建立第二批有效 proposal batch。
- 已建立 `docs/CHAT_D_INFRA_REQUEST.md`，整理 Chat G direct claim 前 DNS / host resolution 失敗與後端健康檢查需求。
- 已建立 `docs/CHAT_E_AUTOMATION_INFRA_REPORT.md`，整理 Chat G stale worktree context 事件；Chat G automation configured cwd 已由舊的 `945c` 改為目前最新且存在的 `e89a`。

## 目前 FORM_URL 狀態

正式報名表網址已確認：

```text
https://ftm.com.tw/demo/admission-system/public-course-intake.php
```

Cloudflare Worker 目前若未設定環境變數 `FORM_URL`，會預設回覆此正式報名表網址。

若未來要改表單網址，可設定 Cloudflare Worker 環境變數：

```text
FORM_URL
```

## 尚未完成

- 尚未確認表單頁面欄位與必填驗證。
- 尚未確認表單送出後寫入資料庫的 API / 後端流程。
- 尚未確認 Email 通知流程。
- 尚未確認三款預覽網址產生與通知流程。
- 尚未確認三天選款期限與過期處理的自動化。
- 尚未實作實際排程 worker、atomic claim SQL、`worker_runs` 資料表與 retry runner。
- 尚未實作 Chat A / Canva proposal ready gate 的程式檢查與資料庫 unique / batch 約束。
- 尚未建立完整前台、後台或 admin 管理介面。
- 尚未建立 `styles/`、`skills/`、`templates/`、`public/` 等實作目錄。

## Chat 分工

### Chat C

負責 LINE AI 接待流程與 Cloudflare Worker 回覆邏輯。

目前 Chat C 已完成：

- LINE AI 改為接待助理。
- LINE AI 導向網頁表單。
- Worker 取消逐步填表與建檔。

### Chat D / Chat B

需要接續：

- 確認正式課程資料表頁面欄位與送出行為。
- 如未來表單網址變更，提供新的 `FORM_URL`。
- 表單必填欄位檢查。
- 表單送出後資料寫入 `clients`、`course_projects` 與素材資料。
- 後台可查看客戶資料與課程資料。

### Chat E

需要接續：

- 表單送出後自動化測試。
- Email 通知流程。
- 三款預覽網址通知。
- 三天選款期限與過期處理。
- Cloudflare Worker 線上版本與 LINE App 實測。

## 目前風險

- 表單尚未接上正式資料庫前，LINE AI 導向表單後仍無法完成整體資料建檔閉環。
- 舊測試若仍以「AI 逐步收資料」為期待，需由 Chat E 改成新版接待助理測試。
- 樣板提案 worker 若未依規格實作鎖定，兩輪排程或兩台 worker 可能同時處理同一個 `project_id`。
- 若缺資料仍被排程重試，可能造成每 10 分鐘重撞 Chat A / Canva；需用 `needs_data` / `template_failed` 停止自動重試。
- Chat G：Canva 三案提案自動化曾遇到 `ftm.com.tw` 間歇性 DNS / host resolution 失敗；目前 direct API POST 應針對 `curl: (6) Could not resolve host` 做最多 3 次 bounded retry，仍失敗時標記 infra blocker。
- 若排程 run context 指向不存在或非 configured cwd 的 worktree，例如 `/Users/phoebe/.codex/worktrees/4d27/課程招生 - 系統` 或 `/Users/phoebe/.codex/worktrees/1210/課程招生 - 系統`，必須在 DNS / claim / Canva generation 前停止，並記錄 `stale_worktree_context`。

## 下一步建議

1. Chat D / Chat B 確認課程資料表欄位、必填驗證與送出流程。
2. 如需改網址，將新的 `FORM_URL` 設定到 Cloudflare Worker。
3. Chat E 重新測 LINE AI 四大入口：
   - 填寫課程資料表。
   - 了解製作流程。
   - 了解免費試營運。
   - 回報網站 / 系統問題。
4. Chat D 確認表單送出後能寫入資料庫。
5. Chat E 測試表單送出後 Email 與三款預覽通知。
6. Chat E / Chat D 實作排程 worker 前，先建立 `worker_runs` 與 `course_projects` 鎖定欄位 migration。
7. 每日巡檢 Chat G automation 的 `cwds` 是否仍指向 `/Users/phoebe/.codex/worktrees/e89a/課程招生 - 系統`，並檢查 direct claim DNS 失敗是否重複發生。
8. Chat D 檢查 `ftm.com.tw` API access log、DNS 穩定性與 health endpoint，詳見 `docs/CHAT_D_INFRA_REQUEST.md`。
