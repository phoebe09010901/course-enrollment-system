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
6. `docs/CLIENT_SELECTION_FLOW.md`
7. `styles/course-brand-template-v1.json`
8. `styles/layout-rules/landing-page.md`
9. `styles/typography/course-brand.md`
10. `styles/motion/animation-style.md`
11. `styles/tokens/course-brand.css`

## AI Agent 工作規則

- 先讀 repo，再做判斷。
- 不要根據檔名或使用者提到的方向，假設功能已存在。
- 若目錄不存在，應明確寫「尚未建立」。
- 所有招生頁新樣板不得直接進入 HTML/CSS 改版；必須先由 Chat A 使用 Canva 製作視覺樣板草案，確認方向後才交給 Chat B / Codex 前端實作。
- 目前優先問題是樣板美感不足，不是功能不足；未完成 Canva 視覺確認前，應停止直接前端改版。
- 對 `course-brand-template-v1` 的前端工作應優先沿用 `styles/` 中既有 foundation，不重新發明風格。
- 本 front-end chat 範圍限於 HTML、CSS、JS、responsive、hero、section composition 與 animation。
- 不得在本範圍內新增 SQL、webhook、backend 或 form schema。
- 若功能只是規劃，應明確寫「預期」、「建議」、「待建立」。
- 修改文件時要同步更新狀態與缺口。
- 新增大型功能前，先建立 README 或規格文件，說明目錄責任。
- 不要把美術風格、模板、skill、webhook、admin、worker 混在同一層未命名邏輯中。

## Chat A / Chat B / Chat C / Chat D / Chat E 合作方式

### Chat A：美術風格系統 / 設計規範維護

Chat A 負責維護：

- `docs/STYLE_SYSTEM.md`
- `docs/TEMPLATE_REFERENCE.md`
- `styles/`
- 與設計規範、版型資料庫、template selection 相關的長期規則
- Canva 樣板製作前的 template pairing 與視覺方向規格
- 三款樣板提案流程與客戶選版交接規格

Chat A 的工作重點：

- 整理全域視覺規則。
- 維護 10 份網站風格分析報告形成的 Template Reference System。
- 定義哪些規則是所有招生頁都必須遵守。
- 避免把單一模板細節塞進 `STYLE_SYSTEM.md`；單一模板細節應放在 `TEMPLATE_REFERENCE.md`。
- 使用 Canva 製作樣板前，先指定 `primary_template_id`、`secondary_template_id`、`source_url`、`secondary_source_url`。
- 為新課程產生三款樣板時，先依 `docs/CLIENT_SELECTION_FLOW.md` 從 `docs/TEMPLATE_REFERENCE.md` 選出三組 proposal。
- Canva 樣板若被選為正式方向，需回寫 `docs/PROJECT_STATUS.md`。

### Chat B：前端實作 / 招生頁重構

Chat B 負責依照 Chat A 維護的規範實作前端：

- 在新招生頁樣板尚未完成 Canva 視覺草案前，Chat B 不得直接進行 HTML/CSS 改版。
- 產生或重構招生頁前，必須先查詢 `docs/TEMPLATE_REFERENCE.md`。
- 產生或重構招生頁前，必須指定 primary `template_id`。
- 可指定一個 `secondary_template_id` 作為輔助風格。
- 不可以在未查詢 `docs/TEMPLATE_REFERENCE.md` 的情況下自行發明新版型。
- 不可以違反 `docs/STYLE_SYSTEM.md` 的全域規則。
- 如果 Chat A 已完成 Canva 樣板，Chat B 必須依照 Canva 樣板與 template id 實作，不可自行改成另一種視覺方向。
- Chat B / Codex 不可自行跳回舊版 sunshine-golden-pencil 的視覺邏輯，除非 Chat A 明確指定。

Chat B 宣告格式：

```text
本頁採用 TPL-001 LifeTime 作為主要版型依據。
secondary_template_id: TPL-005 Interior Design
```

如果使用者沒有指定 template，Chat B 應先根據課程類型、產業、品牌調性從 `docs/TEMPLATE_REFERENCE.md` 提出候選，而不是直接實作。

### Chat C：LINE AI 客服 / 客戶通知

Chat C 負責客戶溝通與通知流程：

- LINE AI 客服資料收集。
- LINE 通知客戶三款預覽網址。
- 提醒三天選擇期限。
- 處理客戶選款回覆。
- 將客戶回覆同步回專案狀態或後台資料。

### Chat D：資料庫與後台

Chat D 負責資料結構、後台與案件狀態管理：

- `clients`
- `course_projects`
- `template_proposals`
- `notification_logs`
- 後台查看案件、樣板狀態、選款狀態、逾期狀態。

Chat D 不負責美術風格決策；樣板方向需依 Chat A 的 template reference 與 Canva 選版結果。

### Chat E：自動化與 AI Worker

Chat E 負責自動化與排程：

- 客戶確認後自動建檔。
- 觸發樣板提案流程。
- 寄 Email 與 LINE 通知。
- 三天後自動作廢。
- 逾期前提醒。

Chat E 不負責自由生成新樣板；只能觸發 Chat A 定義的選版流程。

### 三款樣板提案流程

Chat A 為新課程產生三款樣板時，必須遵守 `docs/CLIENT_SELECTION_FLOW.md`：

- 三款提案必須來自 `docs/TEMPLATE_REFERENCE.md` 的 10 套樣板資料或合理組合。
- 每款提案都要保留 `proposal_id`、`proposal_name`、`primary_template_id`、`secondary_template_id`、`source_url`、`secondary_source_url`、適合原因與視覺方向。
- 每款提案都要保留 Canva 樣板連結或截圖、是否入選、有效期限。
- 客戶看到簡化後的方向名稱；內部紀錄必須保留 template id 與 source URL。
- 若要新增第 11 套樣板，必須先更新 `docs/TEMPLATE_REFERENCE.md`。

### Canva 到前端交接內容

Chat A 完成 Canva 樣板後，必須交接給 Chat B：

- `selected_primary_template_id`
- `selected_secondary_template_id`
- `source_url`
- `secondary_source_url`
- Canva 樣板連結或截圖
- Hero 結構說明
- About 結構說明
- Gallery 結構說明
- CTA 結構說明
- 字體規則
- 色彩規則
- 圖片比例規則
- 禁止事項

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
