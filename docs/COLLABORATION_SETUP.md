# Collaboration Setup

## 文件目的

本文件定義人類協作者與 AI Agent 在本 repo 中的協作規則。目標是讓新 chat 接手時能快速理解目前狀態，並避免把尚未存在的功能寫成已完成事實。

## 目前協作基準

目前 repo 已有 `docs/`、`styles/` 與 `templates/course-brand-template-v1/`。前端模板已落地第一版；後端、SQL、webhook、admin、worker 與 form schema 仍未建立。

AI Agent 在開始任何工作前，應先閱讀：

1. `docs/PROJECT_CONTEXT.md`
2. `docs/PROJECT_STATUS.md`
3. `docs/STYLE_SYSTEM.md`
4. `docs/COLLABORATION_SETUP.md`
5. `docs/TEMPLATE_REFERENCE.md`
6. `styles/course-brand-template-v1.json`
7. `styles/layout-rules/landing-page.md`
8. `styles/typography/course-brand.md`
9. `styles/motion/animation-style.md`
10. `styles/tokens/course-brand.css`

## AI Agent 工作規則

- 先讀 repo，再做判斷。
- 不要根據檔名或使用者提到的方向，假設功能已存在。
- 若目錄不存在，應明確寫「尚未建立」。
- 對 `course-brand-template-v1` 的前端工作應優先沿用 `styles/` 中既有 foundation，不重新發明風格。
- 本 front-end chat 範圍限於 HTML、CSS、JS、responsive、hero、section composition 與 animation。
- 不得在本範圍內新增 SQL、webhook、backend 或 form schema。
- 若功能只是規劃，應明確寫「預期」、「建議」、「待建立」。
- 修改文件時要同步更新狀態與缺口。
- 新增大型功能前，先建立 README 或規格文件，說明目錄責任。
- 不要把美術風格、模板、skill、webhook、admin、worker 混在同一層未命名邏輯中。

## Chat A / Chat B 合作方式

### Chat A：美術風格系統 / 設計規範維護

Chat A 負責維護：

- `docs/STYLE_SYSTEM.md`
- `docs/TEMPLATE_REFERENCE.md`
- `styles/`
- 與設計規範、版型資料庫、template selection 相關的長期規則

Chat A 的工作重點：

- 整理全域視覺規則。
- 維護 10 份網站風格分析報告形成的 Template Reference System。
- 定義哪些規則是所有招生頁都必須遵守。
- 避免把單一模板細節塞進 `STYLE_SYSTEM.md`；單一模板細節應放在 `TEMPLATE_REFERENCE.md`。

### Chat B：前端實作 / 招生頁重構

Chat B 負責依照 Chat A 維護的規範實作前端：

- 產生或重構招生頁前，必須先查詢 `docs/TEMPLATE_REFERENCE.md`。
- 產生或重構招生頁前，必須指定 primary `template_id`。
- 可指定一個 `secondary_template_id` 作為輔助風格。
- 不可以在未查詢 `docs/TEMPLATE_REFERENCE.md` 的情況下自行發明新版型。
- 不可以違反 `docs/STYLE_SYSTEM.md` 的全域規則。

Chat B 宣告格式：

```text
本頁採用 TPL-001 LifeTime 作為主要版型依據。
secondary_template_id: TPL-005 Interior Design
```

如果使用者沒有指定 template，Chat B 應先根據課程類型、產業、品牌調性從 `docs/TEMPLATE_REFERENCE.md` 提出候選，而不是直接實作。

## 專案開啟與結束口令

當使用者說「開啟專案」時，AI Agent 應先同步遠端狀態：

```bash
git pull
```

完成後回報目前 branch、最新 commit，以及是否有衝突或本機未提交變更。

當使用者說「結束專案」時，AI Agent 應協助把目前工作保存到 git：

```bash
git status --short --branch
git add <相關檔案>
git commit -m "<本次修改摘要>"
git push
```

如果沒有任何變更，應回報「目前沒有需要提交的變更」。如果有未確認的變更範圍，應先列出檔案並確認 commit 訊息或自行使用清楚、保守的訊息。

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

### `STYLE_SYSTEM.md`

用來記錄美術風格系統、style-selector-skill 與 course-brand-template-v1 的規則。

每次新增 style token、模板、品牌案例或風格選擇邏輯後應更新。

### `COLLABORATION_SETUP.md`

用來記錄 AI 協作流程、repo 探查規則與文件維護規則。

當團隊協作方式、工具、branch 流程或 Agent 分工改變時更新。

## 建議分工

未來可以將 repo 拆成以下責任區：

- `styles/`：美術風格、品牌 token、視覺規範。已建立第一版 course brand foundation。
- `skills/`：AI Agent 使用的 skills。
- `templates/`：可重複使用的課程品牌模板。已建立 `course-brand-template-v1` 靜態前端模板。
- `public/`：公開靜態資源。
- `line-webhook/`：LINE webhook 接收與回應邏輯。
- `admin/`：管理介面。
- `worker/`：背景工作、排程、非同步任務。
- `docs/`：長期記憶、規格、協作規則。

以上目錄目前僅 `styles/`、`templates/` 與 `docs/` 已建立。

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
