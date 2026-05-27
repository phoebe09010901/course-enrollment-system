# Project Status

## 狀態摘要

目前專案處於初始化階段。repo 尚未包含實際應用程式碼、風格資料、skill 實作、模板、公開資源或後端服務。

目前已建立基礎文件、AI worker workflow 規格、後端自動化流程規格與 `worker/` 入口 README，作為後續 AI 協作與專案記憶的起點。

## 已確認存在

- `.git`
- `docs/PROJECT_CONTEXT.md`
- `docs/PROJECT_STATUS.md`
- `docs/STYLE_SYSTEM.md`
- `docs/COLLABORATION_SETUP.md`
- `docs/AI_WORKER_WORKFLOW.md`
- `docs/BACKEND_AUTOMATION_FLOW.md`
- `docs/LINE_AI_CUSTOMER_SERVICE_FLOW.md`
- `cloudflare-workers/worker.js`
- `worker/README.md`

## 尚未存在的目錄與狀態

| 目錄 | 目前狀態 | 備註 |
| --- | --- | --- |
| `styles/` | 尚未建立 | 尚無色彩、字體、構圖、品牌 token 或風格定義 |
| `skills/` | 尚未建立 | 尚無 style-selector-skill 或其他 AI skill |
| `templates/` | 尚未建立 | 尚無 course-brand-template-v1 |
| `public/` | 尚未建立 | 尚無公開素材、圖片、靜態資源 |
| `line-webhook/` | 尚未建立 | 尚無 LINE webhook 程式碼或設定 |
| `cloudflare-workers/` | 已建立 | 包含 Chat C LINE AI 客服 Worker 可部署版本 |
| `admin/` | 尚未建立 | 尚無管理介面 |
| `worker/` | 已建立初始 README | 尚無背景工作程式碼、排程或任務佇列 |

## 已完成

- 建立專案背景文件。
- 建立專案狀態文件。
- 建立美術風格系統初始文件。
- 建立 AI 協作規則文件。
- 建立 AI worker workflow 初始規格。
- 建立後端自動化流程規格，涵蓋資料庫、後台、AI Worker、通知與三天作廢邏輯。
- 建立 `docs/LINE_AI_CUSTOMER_SERVICE_FLOW.md`，作為 Chat E 驗證 LINE AI 客服流程的核心依據。
- 同步 Chat C 可部署 Worker：`cloudflare-workers/workers.js`、`cloudflare-workers/worker.js`、`cloudflare-workers/line-webhook-worker.js`。
- Worker 版本：`chat-c-contact-course-token-fix-2026-05-27-07`。
- 已依 Chat E 實測修正 C-FIX-001～C-FIX-005、C-FIX-007 的主要 Worker 行為：入口 2 / 3 先分流、初次非明確 intent 顯示開場、單獨姓名可寫入 `user_name`、Email-only 不誤判 LINE ID Link、課程形式「還沒確定」可暫存為 `未定`、付款問題明確由 service price guard 處理。
- 已在 `docs/LINE_AI_CUSTOMER_SERVICE_FLOW.md` 補上 `line_id_link_status = need_review` 通過 gate 的決策、Email-only 解析限制、單獨姓名規則、課程 unknown 處理與付款 guard 例外說明。
- 已依 Chat E 最新回歸修正 C-FIX-001～C-FIX-007：`ready_for_confirmation` 前確認詞不可污染欄位；contact fallback 改為聯絡資料導向；unknown-like 欄位成功寫入後不再混 fallback；泛稱課程名稱會追問是否作為暫定課名；Email 拒答會標記 `email_status = declined` 與 `needs_human_contact_review = true`；模糊日期會標記 `expected_launch_date_status` / `expected_start_date_status = tentative`；圖片素材因無法自動辨識內容先標 `need_review`。
- 已修正欄位說明情境：客戶問「課程類型是什麼 / 怎麼填 / 看不懂」時，Worker 會先解釋課程類型是主題分類，並提供例子；不會把問題句寫入 `course_type`，也不會跳到下一步。
- 已修正 C-FIX-008 / S14：空白 `LINE ID Link：` 或 `3. LINE ID Link：` 不會污染 `line_id_link`；聯絡資料未補齊時，課程欄位文字不會被推測成 `user_name`。
- 已修正 C-FIX-009 / S16：聯絡資料未補齊時，`實體`、`線上`、`混合`、`畫畫`、`色鉛筆` 等課程形式 / 類型詞不會被推測成 `user_name`。`node --test tests/line-ai-worker-scenarios.test.mjs` 已通過 S01 到 S16。
- 新增 C-FIX-010 / S17：聯絡資料未補齊時，短 LINE 代碼如 `URZ8z2U` 會被推測成 `user_name`，導致系統只剩補問 LINE ID Link；需 Chat C 修正短英數代碼污染姓名的問題。
- 將 MVP 流程定位為免費試營運階段，報價、付款、訂閱與續約先列為 future phase。
- 建立 `worker/README.md`，定義 worker 預期責任與目前缺口。
- 明確記錄目前 repo 仍沒有實體系統功能。

## 尚未完成

- 已建立 LINE AI 客服流程規格；其他產品需求與實際使用者流程仍待補完整。
- 尚未建立應用架構。
- 尚未選定前端、後端、資料庫或部署技術。
- 尚未建立 style-selector-skill 的 skill 目錄與規格。
- 尚未建立 course-brand-template-v1 的模板結構。
- 尚未建立任何測試、CI 或部署流程。
- 已有 Cloudflare Worker 參考實作；LINE webhook 與 admin 的正式責任分工仍待補完整。
- 尚未建立 worker 的任務 schema、執行環境、佇列、儲存或 AI 串接。
- 尚未建立 `docs/TEMPLATE_REFERENCE.md`，因此三款樣板提案的實際 template id 來源仍待補。
- 尚未建立 `docs/CLIENT_SELECTION_FLOW.md`，因此客戶選款的前台互動細節仍待補。
- Chat E 需使用 `docs/LINE_AI_CUSTOMER_SERVICE_FLOW.md` 與 `cloudflare-workers/worker.js` 重新進行下一輪 LINE AI 客服流程測試，優先回歸確認詞 guard、contact fallback、課程形式 unknown、泛稱課名、欄位說明 guard、Email 拒答、模糊日期 status、圖片 `need_review`。

## 目前風險

- 專案意圖已有關鍵名詞，但 repo 尚無實作，容易在不同 chat 中被誤判為已有系統。
- 如果後續先寫程式碼而不補規格，AI Agent 可能會各自發明不一致的目錄結構。
- 美術風格系統與模板系統若沒有資料格式約定，之後會難以自動選擇、套用或重構。

## 下一步建議

1. 建立 `styles/README.md`，定義風格資料格式。
2. 建立 `skills/style-selector-skill/README.md`，描述 skill 輸入、輸出與選擇邏輯。
3. 建立 `templates/course-brand-template-v1/README.md`，定義模板目標與資料結構。
4. 決定 `public/`、`line-webhook/`、`admin/`、`worker/` 是否屬於同一 repo 的 monorepo 架構。
5. 新增 `docs/ARCHITECTURE.md` 與 `docs/ROADMAP.md`，補上系統架構與短期里程碑。
6. 為第一個 AI worker 任務定義 JSON schema 與輸入輸出範例。
7. 建立 `docs/TEMPLATE_REFERENCE.md` 與 `docs/CLIENT_SELECTION_FLOW.md`。
8. 將 `docs/BACKEND_AUTOMATION_FLOW.md` 轉成資料庫 migration、API contract 與後台 wireframe。
