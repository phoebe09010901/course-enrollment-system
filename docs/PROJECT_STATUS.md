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
- `docs/ARCHITECTURE.md`
- `docs/TEMPLATE_REFERENCE.md`
- `docs/CLIENT_SELECTION_FLOW.md`
- `docs/LINE_AI_CUSTOMER_SERVICE_FLOW.md`
- `docs/CHAT_A_PROPOSAL_HANDOFF_SPEC.md`
- `docs/LINE_AI_TEST_REPORT.md`
- `docs/CHAT_C_FIX_REQUEST.md`
- `cloudflare-workers/workers.js`
- `cloudflare-workers/worker.js`
- `cloudflare-workers/line-webhook-worker.js`
- `tests/line-ai-worker-scenarios.test.mjs`

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
- 已新增 `docs/CHAT_A_PROPOSAL_HANDOFF_SPEC.md`，定義案件送進「待樣板提案」前提供給 Chat A 的最小 payload。
- 已更新 `docs/COLLABORATION_SETUP.md` 的 Chat C / Chat D / Chat E 分工。

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
- 尚未確認表單送出後是否能整理 Chat A proposal handoff payload。
- 尚未確認圖片上傳後是否能產生可直接存取的 R2 URL。
- 尚未確認 Email 通知流程。
- 尚未確認三款預覽網址產生與通知流程。
- 尚未確認三天選款期限與過期處理的自動化。
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
- 確認表單欄位可產出 Chat A proposal handoff payload。
- 確認圖片素材可上傳到 R2 並產生可直接存取 URL。
- 確認 `image_rights_confirmed` 可由表單明確收集。
- 表單必填欄位檢查。
- 表單送出後資料寫入 `clients`、`course_projects` 與素材資料。
- 後台可查看客戶資料與課程資料。

### Chat E

需要接續：

- 表單送出後自動化測試。
- Chat A proposal handoff payload 驗證。
- 缺少 `r2_images`、`image_rights_confirmed` 或 Canva URL 時，proposal 必須維持 pending 的自動化測試。
- Email 通知流程。
- 三款預覽網址通知。
- 三天選款期限與過期處理。
- Cloudflare Worker 線上版本與 LINE App 實測。

## 目前風險

- 表單尚未接上正式資料庫前，LINE AI 導向表單後仍無法完成整體資料建檔閉環。
- 舊測試若仍以「AI 逐步收資料」為期待，需由 Chat E 改成新版接待助理測試。

## 下一步建議

1. Chat D / Chat B 確認課程資料表欄位、必填驗證與送出流程。
2. 如需改網址，將新的 `FORM_URL` 設定到 Cloudflare Worker。
3. Chat E 重新測 LINE AI 四大入口：
   - 填寫課程資料表。
   - 了解製作流程。
   - 了解免費試營運。
   - 回報網站 / 系統問題。
4. Chat D 確認表單送出後能寫入資料庫。
5. Chat D / Chat E 依 `docs/CHAT_A_PROPOSAL_HANDOFF_SPEC.md` 補齊 Chat A 交接 payload。
6. Chat E 測試表單送出後 Email 與三款預覽通知。
