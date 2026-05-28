# Handoff For New Computer

## 目的

本文件讓另一台電腦或另一位協作者可以快速接手「課程招生 - 系統」專案。

目前專案重點是 LINE AI 客服 / AI Worker 已改為「課程招生頁系統接待助理」，資料收集改由網頁表單處理。尚未建立正式前台、後台、資料庫 migration 或完整部署流水線。

2026-05-28 補充：

- Chat G 提案流程目前採 `semi_automated_canva_proposals` 模式。
- claim / preflight / callback plumbing 已可由本機 `launchd` 自動執行。
- 真正的 Canva 三案產圖仍可能需要另一台「有 Canva 能力」的電腦接手。
- 若要在另一台電腦接手 Canva 階段，請把這份文件與 `docs/CHAT_G_LOCAL_SCHEDULER.md` 一起當成必讀。

## Repository

GitHub remote:

```bash
git@github.com:phoebe09010901/course-enrollment-system.git
```

目前協作分支：

```bash
codex/collaboration-handoff
```

新電腦建議先執行：

```bash
git clone git@github.com:phoebe09010901/course-enrollment-system.git
cd course-enrollment-system
git fetch --all
git switch codex/collaboration-handoff
```

若新電腦沒有設定 GitHub SSH key，可先用 HTTPS：

```bash
git clone https://github.com/phoebe09010901/course-enrollment-system.git
cd course-enrollment-system
git fetch --all
git switch codex/collaboration-handoff
```

若要以目前協作分支為基準重新開工作分支：

```bash
git switch codex/collaboration-handoff
git pull origin codex/collaboration-handoff
git switch -c codex/<your-task-name>
```

若新電腦是專門接手 Chat G / Canva：

```bash
git switch codex/collaboration-handoff
git pull origin codex/collaboration-handoff
git switch -c codex/chat-g-canva-handoff
```

## 必讀文件順序

新 chat / 新電腦接手時，請先依序閱讀：

1. `docs/PROJECT_CONTEXT.md`
2. `docs/PROJECT_STATUS.md`
3. `docs/COLLABORATION_SETUP.md`
4. `docs/LINE_AI_CUSTOMER_SERVICE_FLOW.md`
5. `docs/LINE_AI_WORKER_TEST_SCENARIOS.md`
6. `docs/LINE_AI_ADVANCED_QA_PLAN.md`
7. `docs/CHAT_C_FIX_REQUEST.md`
8. `docs/BACKEND_AUTOMATION_FLOW.md`
9. `docs/AI_WORKER_WORKFLOW.md`
10. `docs/STYLE_SYSTEM.md`
11. `docs/TEMPLATE_REFERENCE.md`
12. `docs/CLIENT_SELECTION_FLOW.md`
13. `docs/CHAT_G_LOCAL_SCHEDULER.md`
14. `scripts/chat-g-local-worker-prompt.md`

目前仍缺：

- `docs/ARCHITECTURE.md`

## 目前重要檔案

| 路徑 | 用途 |
| --- | --- |
| `cloudflare-workers/workers.js` | Chat C LINE AI 客服 Worker 主實作 |
| `cloudflare-workers/worker.js` | 要貼到 Cloudflare 的可部署版本 |
| `cloudflare-workers/line-webhook-worker.js` | LINE webhook worker 相關檔案 |
| `tests/line-ai-worker-scenarios.test.mjs` | Chat E 可重跑 Node 測試 |
| `docs/LINE_AI_CUSTOMER_SERVICE_FLOW.md` | LINE AI 客服流程規格 |
| `docs/LINE_AI_WORKER_TEST_SCENARIOS.md` | Worker 測試 scenario 清單 |
| `docs/LINE_AI_ADVANCED_QA_PLAN.md` | 進階 QA 計劃，含真實 LINE 事故重放方向 |
| `docs/LINE_AI_TEST_REPORT.md` | 測試報告 |
| `docs/CHAT_C_FIX_REQUEST.md` | 交給 Chat C 的修正清單 |
| `docs/BACKEND_AUTOMATION_FLOW.md` | 後端、資料庫、通知、自動化流程規格 |
| `docs/TEMPLATE_REFERENCE.md` | Chat G / Chat A 三案樣板 pairing 與 template metadata 規格 |
| `docs/CLIENT_SELECTION_FLOW.md` | A / B / C proposal batch 欄位與選款規格 |
| `docs/CHAT_G_LOCAL_SCHEDULER.md` | Chat G 本機 `launchd` 排程與 log/控制方式 |
| `scripts/chat-g-local-worker-prompt.md` | Chat G 本機 worker 的正式執行規則 |

## 本地驗證

目前不需要安裝 npm 套件，測試使用 Node.js 內建 `node:test`。

建議 Node 版本：Node 18+。

執行：

```bash
node --test tests/line-ai-worker-scenarios.test.mjs
```

目前預期：

- 新版接待助理測試共 11 個 test blocks，預期 pass 11 / fail 0。
- 測試範圍包含 health check、四個入口選項、表單連結、免費試營運說明、流程說明、三天預覽說明、網站 / 系統問題、安全防護、客戶資料文字不觸發建檔、非文字訊息不進照片收集。

## Chat G / Canva 新電腦接手

若這台電腦的任務是接手 Chat G 的 Canva 階段，請額外做以下事情：

1. 確認可登入並正常使用 Canva。
2. 確認這台電腦上的 Codex / 相關工具可存取需要的 Canva connector 或互動環境。
3. 先閱讀：
   - `docs/TEMPLATE_REFERENCE.md`
   - `docs/CLIENT_SELECTION_FLOW.md`
   - `docs/CHAT_G_LOCAL_SCHEDULER.md`
   - `scripts/chat-g-local-worker-prompt.md`
4. 先理解目前正式模式不是「保證全自動產出 Canva 三案」，而是：
   - Chat G 自動 claim
   - claim 後若 headless Canva 不足，轉人工 / 互動式 Canva 處理
5. 若要接手已 claim 案件，需先知道：
   - `project_id`
   - `proposal_batch_id`
   - `expires_at`
   - 課程資料與 R2 圖片 URL
   - 預期輸出欄位：A / B / C 三案、template metadata、真實 `canva_url`

### Chat G 目前正式 runtime

Chat G 的正式自動執行路徑目前不是 Codex automation cron，而是本機 `launchd`：

```text
/Users/phoebe/Library/LaunchAgents/com.phoebe.chat-g-canva-worker.plist
```

如果要在另一台電腦也接手同樣模式，請直接在 clone 完 repo 後執行：

```bash
chmod +x scripts/install-chat-g-launchagent.sh
scripts/install-chat-g-launchagent.sh
```

這個安裝腳本會依照「該台電腦目前的 repo 路徑」自動產生並安裝：

```text
~/Library/LaunchAgents/com.phoebe.chat-g-canva-worker.plist
```

不需要手改 `/Users/phoebe/...` 這類舊路徑。

安裝完可直接驗證：

```bash
launchctl print gui/$(id -u)/com.phoebe.chat-g-canva-worker
launchctl kickstart -k gui/$(id -u)/com.phoebe.chat-g-canva-worker
scripts/chat-g-control-app.sh
```

### 目前最重要的限制

- `claim`、`health probe`、callback 可以自動化。
- 但 `Canva` 產三案未必能在無人工介入下穩定完成。
- 因此若新電腦的目的是「真的做出 Canva 樣板」，它要被視為：
  - 互動式 Canva 執行端
  - 而不是只讀文件的被動備援機

## 目前已知狀態

- MVP 階段是免費試營運。
- 報價、付款、訂閱、續約都先列為 future phase，不放入目前主流程。
- LINE AI 已取消逐步填表、照片收集、摘要確認與建檔狀態機。
- LINE AI 目前只做接待、說明、導向表單與系統外指令防護。
- 目前 Worker 版本：`chat-c-receptionist-form-link-2026-05-27-20`。
- 要貼到 Cloudflare 的檔案：`cloudflare-workers/worker.js`。
- 線上 Worker GET health check 的 JSON `version` 應為：`chat-c-receptionist-form-link-2026-05-27-20`。
- 正式表單網址：`https://ftm.com.tw/demo/admission-system/public-course-intake.php`。
- 若 Cloudflare 未設定 `FORM_URL`，Worker 會使用上述預設表單網址。
- Chat G proposal flow 最新基準在 `codex/collaboration-handoff`。
- Chat G proposal docs `docs/TEMPLATE_REFERENCE.md` 與 `docs/CLIENT_SELECTION_FLOW.md` 已補回。
- Chat G 正式模式為 `semi_automated_canva_proposals`，不是保證 fully automated Canva generation。

## 下一步建議

1. 確認 `tests/line-ai-worker-scenarios.test.mjs` 在新電腦可通過。
2. 比對 Cloudflare 線上 Worker health check 版本是否等於 repo 中 `DEPLOY_VERSION`。
3. 若 LINE App 實測仍與本地測試不同，優先檢查：
   - LINE webhook URL 是否指向最新 Worker。
   - Cloudflare 是否已部署最新檔案。
   - `FORM_URL` 是否設定正確。
   - LINE 官方帳號是否仍接到舊 Worker。
4. Chat D / Chat B 確認表單欄位、必填驗證與送出後資料庫寫入。
5. Chat E 測試表單送出後 Email、三款預覽通知、三天選款期限與過期處理。
6. 若新電腦要接手 Canva 階段，先確認這台機器能實際打開並完成 Canva 三案，而不是只驗證本地 repo。
7. 若新電腦也要接手 Chat G 排程，請先執行 `scripts/install-chat-g-launchagent.sh` 再驗證 `launchctl` 狀態。

## 協作規則

- 每個任務開自己的 `codex/<task-name>` 分支。
- 修改 LINE AI Worker 後必跑：

```bash
node --test tests/line-ai-worker-scenarios.test.mjs
```

- 若測試失敗，先更新：
  - `docs/LINE_AI_TEST_REPORT.md`
  - `docs/CHAT_C_FIX_REQUEST.md`
- 不要把尚未存在的前端、後台、資料庫或部署流程寫成已完成。
- 若新增目錄，請同步更新 `docs/PROJECT_STATUS.md`。
- 若新電腦建立自己的 Chat G 本機排程，請同步更新 `docs/CHAT_G_LOCAL_SCHEDULER.md`。

## 給下一位 Chat 的一句話

這個 repo 目前最有價值的可執行資產是 `cloudflare-workers/workers.js` 與 `tests/line-ai-worker-scenarios.test.mjs`。接手時先跑測試；目前預期是 11 個新版接待助理測試全部通過，若 LINE App 實測不同，優先檢查 Cloudflare 部署版本、`FORM_URL` 與 LINE webhook 指向。
