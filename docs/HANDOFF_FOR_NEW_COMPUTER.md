# Handoff For New Computer

## 目的

本文件讓另一台電腦或另一位協作者可以快速接手「課程招生 - 系統」專案。

目前專案重點是 LINE AI 客服 / AI Worker 的資料收集流程、狀態機測試與後續後台 / 自動化規格。尚未建立正式前端、後台、資料庫 migration 或部署流水線。

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

若要以 `main` 為基準重新開工作分支：

```bash
git switch main
git pull origin main
git switch -c codex/<your-task-name>
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

目前仍缺：

- `docs/TEMPLATE_REFERENCE.md`
- `docs/CLIENT_SELECTION_FLOW.md`

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

## 本地驗證

目前不需要安裝 npm 套件，測試使用 Node.js 內建 `node:test`。

建議 Node 版本：Node 18+。

執行：

```bash
node --test tests/line-ai-worker-scenarios.test.mjs
```

目前預期：

- 嚴格矩陣前 S01 到 S18 全部通過；新增 S19-S24 後，目前 S19 / S20 / S22 / S23 失敗，S21 / S24 通過。
- 測試範圍包含入口分流、contact gate、欄位污染防護、欄位說明、照片階段、建檔 gate、confirmed payload。

## 目前已知狀態

- MVP 階段是免費試營運。
- 報價、付款、訂閱、續約都先列為 future phase，不放入目前主流程。
- Worker 目前已修正多個 QA issue，包括確認詞 guard、Email-only、contact fallback、unknown-like input、日期 status、圖片 `need_review`、欄位說明與空白 label 污染。
- 目前 Worker 版本：`chat-c-contact-update-field-fix-2026-05-27-09`。
- 要貼到 Cloudflare 的檔案：`cloudflare-workers/worker.js`。
- 線上 Worker GET health check 的 JSON `version` 應為：`chat-c-contact-update-field-fix-2026-05-27-09`。
- 最新重要測試：S14 真實 LINE 事故重放，確認空白 `LINE ID Link：` 不會污染 `line_id_link`。
- S16 已通過：contact gate 未完成時，`實體` 不會被當成姓名。
- S17 已通過：短 LINE 代碼 `URZ8z2U` 不會被當成姓名，Email 在 LINE ID 補問來回中不會消失。
- S18 已通過：客戶說「我要更新 LINE ID Link」時，系統會要求貼新的 LINE 連結，不會改問 Email。
- 新增嚴格 contact update matrix：S19 / S20 / S22 / S23 是新的 blocker，詳見 `docs/CHAT_C_FIX_REQUEST.md`。

## 下一步建議

1. 確認 `tests/line-ai-worker-scenarios.test.mjs` 在新電腦可通過。
2. 比對 Cloudflare 線上 Worker health check 版本是否等於 repo 中 `DEPLOY_VERSION`。
3. 若 LINE App 實測仍與本地測試不同，優先檢查：
   - LINE webhook URL 是否指向最新 Worker。
   - Cloudflare 是否已部署最新檔案。
   - KV 是否殘留舊 user state。
   - 測試 LINE userId 是否需要清除狀態。
4. 補 `docs/TEMPLATE_REFERENCE.md` 與 `docs/CLIENT_SELECTION_FLOW.md`。
5. 將 `docs/BACKEND_AUTOMATION_FLOW.md` 拆成 API contract、database schema 與 admin wireframe。

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

## 給下一位 Chat 的一句話

這個 repo 目前最有價值的可執行資產是 `cloudflare-workers/workers.js` 與 `tests/line-ai-worker-scenarios.test.mjs`。接手時先跑測試；目前預期是 S19 / S20 / S22 / S23 失敗並等待 Chat C 修 contact update matrix。
