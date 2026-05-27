# Handoff For New Computer

## 目的

本文件讓另一台電腦或另一位協作者可以快速接手「課程招生 - 系統」專案。

目前專案重點是 LINE AI 客服 / AI Worker 已改為「課程招生頁系統接待助理」，資料收集改由網頁表單處理。尚未建立正式前台、後台、資料庫 migration 或完整部署流水線。

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

這個 repo 目前最有價值的可執行資產是 `cloudflare-workers/workers.js` 與 `tests/line-ai-worker-scenarios.test.mjs`。接手時先跑測試；目前預期是 11 個新版接待助理測試全部通過，若 LINE App 實測不同，優先檢查 Cloudflare 部署版本、`FORM_URL` 與 LINE webhook 指向。
