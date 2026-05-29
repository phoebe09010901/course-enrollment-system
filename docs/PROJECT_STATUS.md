# Project Status

## 狀態摘要

目前專案已從初始化階段進入第一版前端模板階段。repo 已包含 `styles/` design foundation、`templates/course-brand-template-v1/` 靜態招生頁模板，以及 `docs/TEMPLATE_REFERENCE.md` 版型參考資料庫；尚未包含 skill 實作、公開資源或後端服務。

目前 `styles/` design foundation 用來支撐品牌型課程頁的 Hero、typography、spacing、motion、gallery rhythm、visual hierarchy 與 section choreography。

目前 `docs/TEMPLATE_REFERENCE.md` 收錄 10 份網站風格分析報告，作為 Chat B 與 Codex 產生招生頁時的正式版型依據，不是一般靈感參考。

目前流程已正式調整：所有招生頁新樣板不得直接由 Codex 進入 HTML/CSS，必須先由 Chat A 使用 Canva 製作視覺樣板草案，確認方向後才交給 Chat B / Codex 前端實作。

目前已確認正式部署目標是外部租用主機，而不是本機主機。外部主機規格為 PHP 5.6、MySQL 5.7.44、cPanel 主機；本機 PHP 8.4.21、MySQL 8.0.46、Apache 2.4.67 僅作為開發輔助。

## 已確認存在

- `.git`
- `README.md`
- `docs/PROJECT_CONTEXT.md`
- `docs/PROJECT_STATUS.md`
- `docs/STYLE_SYSTEM.md`
- `docs/COLLABORATION_SETUP.md`
- `docs/TEMPLATE_REFERENCE.md`
- `docs/CLIENT_SELECTION_FLOW.md`
- `docs/WEBSITE_FACTORY_MIGRATION.md`
- `docs/WEBSITE_FACTORY_INVENTORY.md`
- `docs/WEBSITE_FACTORY_TEMPLATE_REGISTRY.md`
- `styles/README.md`
- `styles/course-brand-template-v1.json`
- `styles/tokens/course-brand.css`
- `styles/layout-rules/landing-page.md`
- `styles/typography/course-brand.md`
- `styles/motion/animation-style.md`
- `templates/course-brand-template-v1/README.md`
- `templates/course-brand-template-v1/index.html`
- `templates/course-brand-template-v1/css/page.css`
- `templates/course-brand-template-v1/js/page.js`
- `templates/course-brand-template-v1/assets/course-workspace.svg`

## 目錄狀態

| 目錄 | 目前狀態 | 備註 |
| --- | --- | --- |
| `docs/` | 已建立 | 已有專案記憶、協作規則、Style System 與 Template Reference System |
| `styles/` | 已建立 frontend-ready foundation | 已有 CSS tokens、Hero、typography、spacing、motion、layout rules，並已被模板引用 |
| `templates/` | 已建立 | 已有 `templates/course-brand-template-v1/` 靜態前端模板 |
| `skills/` | 尚未建立 | 尚無 style-selector-skill 或其他 AI skill |
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
- 建立 course-brand-template-v1 的機讀設計規格。
- 建立 `styles/tokens/course-brand.css`，提供前端可引用的 CSS custom properties 與穩定 class contract。
- 建立 `templates/course-brand-template-v1/`，完成 HTML、CSS、JS、responsive、Hero、section composition 與前端動畫。
- 建立根目錄 `README.md`，提供另一台電腦 clone 與接手方式。
- 補充 course-brand-template-v1 全域設計規則：中文優先、桌機字級下修、不新增獨立聯絡區塊。
- 補充 Typography / 字型系統全域規範：Google Fonts 可用但需由 Chat A 統一定義，預設 `Noto Serif TC` + `Noto Sans TC`。
- 建立 `docs/TEMPLATE_REFERENCE.md`，整理 10 份網站風格分析報告為正式版型參考資料庫。
- 補充 10 組 Template Reference Pairing Rules，規範 Canva 樣板製作前的 primary / secondary template 配對。
- 建立 `docs/CLIENT_SELECTION_FLOW.md`，規範三款樣板提案必須從 10 套 template reference 中挑選。
- 補充 Chat A / Chat B 合作規則：Chat B 產生招生頁前必須先指定 `template_id`。
- 補充 Canva 樣板到 Chat B / Codex 前端實作的交接欄位與限制。
- 正式停止新樣板直接前端改版流程，改為先 Canva 視覺確認，再進入 Chat B / Codex 前端實作。
- 補充三款樣板提案與限時選版流程：待樣板提案、三款 Canva 核心區塊、三天選擇期限、一次小幅調整、不允許無限修改。
- 補充 Chat A / B / C / D / E 分工。
- 建立 `docs/WEBSITE_FACTORY_MIGRATION.md`，定義 Replit website factory zip 的移植分工與第一輪 chat 插入方式。
- 補充正式部署環境限制：外部 cPanel 主機、PHP 5.6、MySQL 5.7.44 優先於本機 PHP 8.4 / MySQL 8。
- 建立 `docs/WEBSITE_FACTORY_INVENTORY.md`，完成 Replit website factory zip 的第一輪盤點與模組分類。
- 補充「正式開啟網站工廠」口令與 Chat M：網站工廠啟動流程。
- 補充網站工廠獨立分工：Chat M-A 網站工廠核心 / Zip 匯出、Chat M-B 課程招生資料注入格式、Chat M-C Zip 驗收 / 品管、Chat M-D 樣式系統 / 模板視覺規格。
- 建立 `docs/WEBSITE_FACTORY_TEMPLATE_REGISTRY.md`，登記 `style-001` 包含 `template-001`、`template-002`、`template-003`，以及 `style-002` 包含 draft `template-004`、`template-005`、`template-006`。

## 尚未完成

- 尚未定義完整產品需求與實際使用者流程。
- 尚未建立 style-selector-skill 的 skill 目錄與規格。
- 尚未將最新全域設計規則與 Typography / 字型系統同步到 `styles/tokens/course-brand.css` 與 `styles/course-brand-template-v1.json`。
- 尚未將 `docs/TEMPLATE_REFERENCE.md` 轉成可機讀 JSON 或 selector skill。
- 尚未將 `docs/CLIENT_SELECTION_FLOW.md` 轉成自動選版流程或表單化輸出。
- 尚未選定任何正式 Canva 方向，尚無 `selected_template_id`、`selected_secondary_template_id` 或 `selected_canva_direction`。
- 尚未建立第一版 Canva 視覺樣板草案。
- 尚未建立 `clients`、`course_projects`、`template_proposals`、`notification_logs` 等資料表或後台。
- 尚未建立三天選擇期限、逾期提醒或自動作廢 worker。
- 尚未以真實課程案例替換模板示範文案。
- 尚未建立正式圖片、攝影或生成圖資產流程。
- 尚未建立任何測試、CI 或部署流程。
- 尚未建立 LINE webhook、admin、worker 的責任分工。
- 尚未依 `docs/WEBSITE_FACTORY_INVENTORY.md` 建立正式 `factory/`、`admin/`、`worker/` 目錄或規格。
- 尚未建立 Chat M-A / M-B / M-C / M-D 對應的正式規格文件與目錄責任。
- 尚未將 `docs/WEBSITE_FACTORY_TEMPLATE_REGISTRY.md` 轉成正式 `factory/template-registry.json`。
- 尚未建立 PHP 5.6 / MySQL 5.7.44 相容性檢查流程。

## 目前風險

- 專案已有前端模板實作，但 SQL、webhook、backend、admin、worker 等關鍵名詞仍只是未建立方向，容易被誤判為已有系統。
- 如果 Chat B 不先查詢 `docs/TEMPLATE_REFERENCE.md` 就直接做前端，可能會回到自由發揮與模板感過重的問題。
- 現有模板使用示範文案與示意視覺資產，正式招生前仍需替換成真實課程資料。
- 本機環境版本高於正式外部主機；若用本機測試通過就判斷可部署，可能引入 PHP 7/8 或 MySQL 8 only 語法，導致 cPanel 主機無法執行。

## 下一步建議

1. 指定第一個真實課程品牌案例的 primary `template_id` 與 optional `secondary_template_id`。
2. 將 `docs/TEMPLATE_REFERENCE.md` 的欄位整理成 `styles/style-index.json` 或 style-selector-skill 可讀格式。
3. 建立 `skills/style-selector-skill/README.md`，描述 skill 輸入、輸出與選擇邏輯。
4. 依據全域規則調整 `styles/tokens/course-brand.css` 的桌機字級 token。
5. 建立正式圖片、攝影或生成圖資產流程。
