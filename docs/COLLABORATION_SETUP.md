# Collaboration Setup

## 文件目的

本文件定義人類協作者與 AI Agent 在本 repo 中的協作規則。目標是讓新 chat 接手時能快速理解目前狀態，並避免把尚未存在的功能寫成已完成事實。

## 目前協作基準

目前 repo 仍屬早期 MVP 規格與 Worker 驗證階段，但已包含 Cloudflare Worker 參考實作與可重跑 Node 測試。尚未建立正式資料庫、後台、前端預覽頁或完整部署流水線。

AI Agent 在開始任何工作前，應先閱讀：

1. `docs/PROJECT_CONTEXT.md`
2. `docs/PROJECT_STATUS.md`
3. `docs/STYLE_SYSTEM.md`
4. `docs/COLLABORATION_SETUP.md`
5. `docs/AI_WORKER_WORKFLOW.md`
6. `docs/BACKEND_AUTOMATION_FLOW.md`
7. `docs/LINE_AI_CUSTOMER_SERVICE_FLOW.md`
8. `docs/LINE_AI_WORKER_TEST_SCENARIOS.md`
9. `docs/LINE_AI_ADVANCED_QA_PLAN.md`
10. `docs/HANDOFF_FOR_NEW_COMPUTER.md`

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

## 新電腦協作流程

建議新電腦先使用 GitHub remote：

```bash
git clone git@github.com:phoebe09010901/course-enrollment-system.git
cd course-enrollment-system
git fetch --all
git switch codex/collaboration-handoff
node --test tests/line-ai-worker-scenarios.test.mjs
```

若要開始新任務，從最新基準開分支：

```bash
git switch main
git pull origin main
git switch -c codex/<task-name>
```

目前若要接續本輪交接內容，請先閱讀 `docs/HANDOFF_FOR_NEW_COMPUTER.md`。

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

## 目前已採用的臨時規則

- 分支使用 `codex/<task-name>`。
- LINE AI Worker 修改後必跑：

```bash
node --test tests/line-ai-worker-scenarios.test.mjs
```

- 測試或 LINE 實測發現問題時，先更新：
  - `docs/LINE_AI_TEST_REPORT.md`
  - `docs/CHAT_C_FIX_REQUEST.md`
- 新增主要目錄或狀態改變時，同步更新 `docs/PROJECT_STATUS.md`。
