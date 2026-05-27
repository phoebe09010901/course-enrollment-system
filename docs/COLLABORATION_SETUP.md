# Collaboration Setup

## 文件目的

本文件定義人類協作者與 AI Agent 在本 repo 中的協作規則。目標是讓新 chat 接手時能快速理解目前狀態，並避免把尚未存在的功能寫成已完成事實。

## 目前協作基準

目前 repo 是初始化狀態。除 `docs/` 以外，尚無應用程式碼或專案目錄。

AI Agent 在開始任何工作前，應先閱讀：

1. `docs/PROJECT_CONTEXT.md`
2. `docs/PROJECT_STATUS.md`
3. `docs/STYLE_SYSTEM.md`
4. `docs/TEMPLATE_REFERENCE.md`
5. `docs/CLIENT_SELECTION_FLOW.md`
6. `docs/FORM_SCHEMA.md`
7. `docs/BACKEND_AUTOMATION_FLOW.md`
8. `docs/COLLABORATION_SETUP.md`

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
find styles schemas skills templates public line-webhook admin worker -maxdepth 3 -type f
```

如果目錄不存在，這是重要狀態，不是錯誤。

## 文件更新規則

### `PROJECT_CONTEXT.md`

用來記錄專案定位、世界觀、核心名詞與長期背景。

當專案目標、使用者、產品形態或核心模組改變時更新。

### `PROJECT_STATUS.md`

用來記錄目前完成度、已存在目錄、缺口、風險與下一步。

每次新增主要目錄、功能、模板或 skill 後應更新。

### `STYLE_SYSTEM.md`

用來記錄美術風格系統、style-selector-skill 與 course-brand-template-v1 的規則。

每次新增 style token、模板、品牌案例或風格選擇邏輯後應更新。

### `FORM_SCHEMA.md`

用來記錄表單 schema、資料規則與欄位系統的初始規格。

每次新增 field registry、表單版本、資料驗證規則、admin 欄位或 webhook 寫入流程後應更新。

### `TEMPLATE_REFERENCE.md`

用來記錄三款樣板提案的挑選規則、提案欄位與課程類型對應。

每次新增 Canva template、模板 id 或樣板挑選規則後應更新。

### `CLIENT_SELECTION_FLOW.md`

用來記錄客戶選擇 A / B / C 樣板、逾期作廢與重新開啟預覽的流程。

每次新增預覽頁、選款 API、LINE 選款語句或逾期邏輯後應更新。

### `BACKEND_AUTOMATION_FLOW.md`

用來記錄資料庫、後台畫面、AI Worker、自動通知與三天作廢流程。

每次新增資料表、migration、worker、notification 或 admin 狀態欄位後應更新。

### `COLLABORATION_SETUP.md`

用來記錄 AI 協作流程、repo 探查規則與文件維護規則。

當團隊協作方式、工具、branch 流程或 Agent 分工改變時更新。

## 建議分工

未來可以將 repo 拆成以下責任區：

- `styles/`：美術風格、品牌 token、視覺規範。
- `schemas/`：表單 schema、欄位 registry、資料規則。
- `skills/`：AI Agent 使用的 skills。
- `templates/`：可重複使用的課程品牌模板。
- `public/`：公開靜態資源。
- `line-webhook/`：LINE webhook 接收與回應邏輯。
- `admin/`：管理介面。
- `worker/`：背景工作、排程、非同步任務。
- `docs/`：長期記憶、規格、協作規則。

以上目錄目前多數尚未建立。

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
