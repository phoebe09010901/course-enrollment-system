# Project Status

## 狀態摘要

目前專案保留 `styles/` design foundation、協作文件，以及三個全新視覺方向範例。先前建立的兩個前端樣板已依使用者要求刪除，不再作為目前狀態或未來參考基準。

目前 `styles/` design foundation 用來支撐品牌型課程頁的 Hero、typography、spacing、motion、gallery rhythm、visual hierarchy 與 section choreography。

目前 `docs/TEMPLATE_REFERENCE.md` 收錄 10 份網站風格分析報告，作為 Chat B 與 Codex 產生招生頁時的正式版型依據，不是一般靈感參考。

目前 repo 有三個 sunshine-golden-pencil alternative examples，供確認視覺方向；尚未選定正式模板。正式定稿前仍應依 `docs/CLIENT_SELECTION_FLOW.md` 或使用者確認的方向進行。

## 已確認存在

- `.git`
- `README.md`
- `docs/PROJECT_CONTEXT.md`
- `docs/PROJECT_STATUS.md`
- `docs/STYLE_SYSTEM.md`
- `docs/COLLABORATION_SETUP.md`
- `docs/TEMPLATE_REFERENCE.md`
- `docs/CLIENT_SELECTION_FLOW.md`
- `styles/README.md`
- `styles/tokens/course-brand.css`
- `styles/layout-rules/landing-page.md`
- `styles/typography/course-brand.md`
- `styles/motion/animation-style.md`
- `templates/sunshine-golden-pencil-alt-a/index.html`
- `templates/sunshine-golden-pencil-alt-a/css/page.css`
- `templates/sunshine-golden-pencil-alt-a/js/page.js`
- `templates/sunshine-golden-pencil-alt-b/index.html`
- `templates/sunshine-golden-pencil-alt-b/css/page.css`
- `templates/sunshine-golden-pencil-alt-b/js/page.js`
- `templates/sunshine-golden-pencil-alt-c/index.html`
- `templates/sunshine-golden-pencil-alt-c/css/page.css`
- `templates/sunshine-golden-pencil-alt-c/js/page.js`

## 目錄狀態

| 目錄 | 目前狀態 | 備註 |
| --- | --- | --- |
| `docs/` | 已建立 | 已有專案記憶、協作規則、Style System 與 Template Reference System |
| `styles/` | 已建立 frontend-ready foundation | 已有 CSS tokens、Hero、typography、spacing、motion、layout rules，並已被模板引用 |
| `skills/` | 尚未建立 | 尚無 style-selector-skill 或其他 AI skill |
| `templates/` | 已有三個候選範例 | `alt-a`、`alt-b`、`alt-c` 是視覺方向稿，尚未確認為正式模板 |
| `public/` | 尚未建立 | 尚無公開素材、圖片、靜態資源 |
| `line-webhook/` | 尚未建立 | 尚無 LINE webhook 程式碼或設定 |
| `admin/` | 尚未建立 | 尚無管理介面 |
| `worker/` | 尚未建立 | 尚無背景工作或排程 |

## 已完成

- 建立專案背景文件。
- 建立專案狀態文件。
- 建立美術風格系統初始文件。
- 建立 AI 協作規則文件。
- 建立品牌型課程頁 frontend-ready design foundation。
- 建立 `styles/tokens/course-brand.css`，提供前端可引用的 CSS custom properties 與穩定 class contract。
- 建立根目錄 `README.md`，提供另一台電腦 clone 與接手方式。
- 補充 course-brand-template-v1 全域設計規則：中文優先、桌機字級下修、不新增獨立聯絡區塊。
- 補充 Typography / 字型系統全域規範：Google Fonts 可用但需由 Chat A 統一定義，預設 `Noto Serif TC` + `Noto Sans TC`。
- 建立 `docs/TEMPLATE_REFERENCE.md`，整理 10 份網站風格分析報告為正式版型參考資料庫。
- 補充 10 組 Template Reference Pairing Rules，規範 Canva 樣板製作前的 primary / secondary template 配對。
- 建立 `docs/CLIENT_SELECTION_FLOW.md`，規範三款樣板提案必須從 10 套 template reference 中挑選。
- 補充 Chat A / Chat B 合作規則：Chat B 產生招生頁前必須先指定 `template_id`。
- 補充 Canva 樣板到 Chat B / Codex 前端實作的交接欄位與限制。
- 正式停止新樣板直接前端改版流程，改為先 Canva 視覺確認，再進入 Chat B / Codex 前端實作。
- 刪除先前建立的 v1/v2 前端樣板，避免後續沿用不符合期待的設計方向。
- 新增三個 sunshine-golden-pencil alternative examples：日系工作室型錄、暗色策展導覽、雜誌索引作品。

## 尚未完成

- 尚未定義完整產品需求與實際使用者流程。
- 尚未建立 style-selector-skill 的 skill 目錄與規格。
- 尚未將最新全域設計規則與 Typography / 字型系統同步到 `styles/tokens/course-brand.css`。
- 尚未將 `docs/TEMPLATE_REFERENCE.md` 轉成可機讀 JSON 或 selector skill。
- 尚未將 `docs/CLIENT_SELECTION_FLOW.md` 轉成自動選版流程或表單化輸出。
- 尚未選定任何正式 Canva 方向，尚無 `selected_template_id`、`selected_secondary_template_id` 或 `selected_canva_direction`。
- 尚未建立第一版 Canva 視覺樣板草案。
- 尚未從三個候選範例中選定正式前端招生頁模板。
- 尚未建立正式圖片、攝影或生成圖資產流程。
- 尚未建立任何測試、CI 或部署流程。
- 尚未建立 LINE webhook、admin、worker 的責任分工。

## 目前風險

- 專案已有前端候選範例，但 SQL、webhook、backend、admin、worker 等關鍵名詞仍只是未建立方向，容易被誤判為已有系統。
- 如果 Chat B 不先查詢 `docs/TEMPLATE_REFERENCE.md` 就直接做前端，可能會回到自由發揮與模板感過重的問題。
- 目前三個候選範例仍待使用者確認；不得把任一範例視為已定稿模板。

## 下一步建議

1. 指定第一個真實課程品牌案例的 primary `template_id` 與 optional `secondary_template_id`。
2. 將 `docs/TEMPLATE_REFERENCE.md` 的欄位整理成 `styles/style-index.json` 或 style-selector-skill 可讀格式。
3. 建立 `skills/style-selector-skill/README.md`，描述 skill 輸入、輸出與選擇邏輯。
4. 依據全域規則調整 `styles/tokens/course-brand.css` 的桌機字級 token。
5. 建立正式圖片、攝影或生成圖資產流程。
