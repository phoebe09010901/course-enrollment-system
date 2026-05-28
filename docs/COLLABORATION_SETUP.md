# Collaboration Setup

## 文件目的

本文件定義人類協作者與 AI Agent 在本 repo 中的協作規則。目標是讓新 chat 接手時能快速理解目前狀態，並避免把尚未存在的功能寫成已完成事實。

## 目前協作基準

目前 repo 是初始化狀態。除 `docs/` 以外，尚無應用程式碼或專案目錄。

AI Agent 在開始任何工作前，應先閱讀：

1. `docs/PROJECT_CONTEXT.md`
2. `docs/PROJECT_STATUS.md`
3. `docs/ARCHITECTURE.md`
4. `docs/STYLE_SYSTEM.md`
5. `docs/TEMPLATE_REFERENCE.md`
6. `docs/CLIENT_SELECTION_FLOW.md`
7. `docs/LINE_AI_CUSTOMER_SERVICE_FLOW.md`
8. `docs/CHAT_A_PROPOSAL_HANDOFF_SPEC.md`
9. `docs/LINE_AI_TEST_REPORT.md`
10. `docs/CHAT_C_FIX_REQUEST.md`
11. `docs/COLLABORATION_SETUP.md`

## AI Agent 工作規則

- 先讀 repo，再做判斷。
- 不要根據檔名或使用者提到的方向，假設功能已存在。
- 若目錄不存在，應明確寫「尚未建立」。
- 若功能只是規劃，應明確寫「預期」、「建議」、「待建立」。
- 修改文件時要同步更新狀態與缺口。
- 新增大型功能前，先建立 README 或規格文件，說明目錄責任。
- 不要把美術風格、模板、skill、webhook、admin、worker 混在同一層未命名邏輯中。

## Repo 探查規則

接手工作時建議先執行：

```bash
pwd
rg --files
git status --short --branch
find . -maxdepth 3 -type d
```

若要檢查指定區塊：

```bash
find styles skills templates public line-webhook admin worker -maxdepth 3 -type f
```

如果目錄不存在，這是重要狀態，不是錯誤。

## 文件更新規則

### `PROJECT_CONTEXT.md`

用來記錄專案定位、世界觀、核心名詞與長期背景。

當專案目標、使用者、產品形態或核心模組改變時更新。

### `PROJECT_STATUS.md`

用來記錄目前完成度、已存在目錄、缺口、風險與下一步。

每次新增主要目錄、功能、模板或 skill 後應更新。

### `ARCHITECTURE.md`

用來記錄世界觀、workflow、Router 架構與目錄責任邊界。

當系統分層、Router 行為、workflow 或模組責任改變時更新。

### `STYLE_SYSTEM.md`

用來記錄美術風格系統、style-selector-skill 與 course-brand-template-v1 的規則。

每次新增 style token、模板、品牌案例或風格選擇邏輯後應更新。

### `TEMPLATE_REFERENCE.md`

用來記錄三款招生頁預覽提案的命名、定位與預覽資料欄位。

當模板名稱、proposal id 或預覽資料格式改變時更新。

### `CLIENT_SELECTION_FLOW.md`

用來記錄客戶收到三款預覽後的選版、提醒、逾期與 selected_proposal_id 紀錄流程。

當選版狀態、到期規則或通知話術改變時更新。

### `LINE_AI_CUSTOMER_SERVICE_FLOW.md`

用來記錄 LINE AI 客服接待流程、免費試營運說明、表單導向、流程問題、網站 / 系統問題與安全邊界。

2026-05-27 起，LINE AI 不再負責資料收集、填表、照片收集、確認摘要、JSON 建檔或 `clients` / `course_projects` 建立。這些責任改由網頁表單與後端流程處理。

當 LINE AI 入口選項、表單連結、免費試營運說明、流程說明或系統問題處理方式改變時更新。

### `CHAT_A_PROPOSAL_HANDOFF_SPEC.md`

用來記錄案件送進「待樣板提案」前，必須提供給 Chat A 的最小 payload。

當表單欄位、R2 圖片規格、proposal ready / pending 規則、Chat A 交接格式改變時更新。

### `COLLABORATION_SETUP.md`

用來記錄 AI 協作流程、repo 探查規則與文件維護規則。

當團隊協作方式、工具、branch 流程或 Agent 分工改變時更新。

## 建議分工

未來可以將 repo 拆成以下責任區：

- `styles/`：美術風格、品牌 token、視覺規範。
- `skills/`：AI Agent 使用的 skills。
- `templates/`：可重複使用的課程品牌模板。
- `public/`：公開靜態資源。
- `line-webhook/`：LINE webhook 接收與回應邏輯。
- `admin/`：管理介面。
- `worker/`：背景工作、排程、非同步任務。
- `docs/`：長期記憶、規格、協作規則。

以上目錄目前多數尚未建立。

## Chat 分工補充

### Chat C：LINE AI 客服

Chat C 負責 LINE AI 接待助理與 Cloudflare Worker 回覆邏輯。

目前 LINE AI 只做：

- 打招呼。
- 說明免費試營運。
- 說明製作流程。
- 提供課程資料表連結。
- 回答表單、Email、三款預覽、三天期限與網站 / 系統問題。
- 防護系統外指令。

LINE AI 不再收集姓名、Email、LINE ID Link、課程資料或照片素材。

### Chat D / Chat B：表單與資料庫

Chat D / Chat B 需要提供：

- 確認正式課程資料表頁面與欄位。
- 如果表單網址變更，提供 Cloudflare Worker 環境變數 `FORM_URL` 的新值。
- 表單必填欄位檢查。
- 表單送出後寫入資料庫。
- 表單送出後可整理 Chat A proposal handoff payload。
- 圖片上傳後產生可直接存取的 R2 URL。
- 後台查看客戶與課程資料。

### Chat E：自動化與 QA

Chat E 需要接續：

- 新版 LINE AI 接待助理流程測試。
- 表單送出後自動化。
- Chat A proposal handoff payload 驗證。
- Email 通知。
- 三款預覽網址通知。
- 三天選款期限與過期處理。
- 線上 Cloudflare Worker / LINE App 實測。

## 提交前檢查

在完成一次修改前，AI Agent 應確認：

- 是否有把不存在的功能寫成已完成？
- 是否有更新對應的 status 或 docs？
- 是否有清楚標記待補資料？
- 是否有避免不必要的架構發明？
- 是否能讓下一個 chat 只靠 docs 理解目前狀態？

## 待建立協作規則

- branch 命名規則。
- commit message 格式。
- 測試與驗證標準。
- 文件審查流程。
- style/template/skill 的版本管理規則。
