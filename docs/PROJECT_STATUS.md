# Project Status

## 狀態摘要

目前專案已從初始化階段進入第一版前端模板階段。repo 已包含 `styles/` design foundation 與 `templates/course-brand-template-v1/` 靜態招生頁模板，但尚未包含 skill 實作、公開資源或後端服務。

目前已新增 `styles/` design foundation，用來支撐品牌型課程頁的 Hero、typography、spacing、motion、gallery rhythm、visual hierarchy 與 section choreography。

本次工作建立四份基礎文件、第一版 design foundation，以及可直接在瀏覽器開啟的靜態招生頁模板。

## 已確認存在

- `.git`
- `docs/PROJECT_CONTEXT.md`
- `docs/PROJECT_STATUS.md`
- `docs/STYLE_SYSTEM.md`
- `docs/COLLABORATION_SETUP.md`
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

## 尚未存在的目錄與狀態

| 目錄 | 目前狀態 | 備註 |
| --- | --- | --- |
| `styles/` | 已建立 frontend-ready foundation | 已有 CSS tokens、Hero、typography、spacing、motion、layout rules，並已被模板引用 |
| `skills/` | 尚未建立 | 尚無 style-selector-skill 或其他 AI skill |
| `templates/` | 已建立 | 已有 `templates/course-brand-template-v1/` 靜態前端模板 |
| `public/` | 尚未建立 | 尚無公開素材、圖片、靜態資源 |
| `line-webhook/` | 尚未建立 | 尚無 LINE webhook 程式碼或設定 |
| `admin/` | 尚未建立 | 尚無管理介面 |
| `worker/` | 尚未建立 | 尚無背景工作或排程 |

## 已完成

- 建立專案背景文件。
- 建立專案狀態文件。
- 建立美術風格系統初始文件。
- 建立 AI 協作規則文件。
- 明確記錄目前 repo 仍沒有實體系統功能。
- 建立品牌型課程頁 frontend-ready design foundation。
- 建立 course-brand-template-v1 的機讀設計規格。
- 建立 `styles/tokens/course-brand.css`，提供前端可引用的 CSS custom properties 與穩定 class contract。
- 建立 `templates/course-brand-template-v1/`，完成 HTML、CSS、JS、responsive、Hero、section composition 與前端動畫。
- 建立根目錄 `README.md`，提供另一台電腦 clone 與接手方式。

## 尚未完成

- 尚未定義產品需求與實際使用者流程。
- 尚未建立應用架構。
- 尚未選定前端、後端、資料庫或部署技術。
- 尚未建立 style-selector-skill 的 skill 目錄與規格。
- 尚未建立任何測試、CI 或部署流程。
- 尚未建立 LINE webhook、admin、worker 的責任分工。
- 尚未以真實課程案例替換模板示範文案。
- 尚未建立正式圖片、攝影或生成圖資產流程。

## 目前風險

- 專案已有前端模板實作，但 SQL、webhook、backend、admin、worker 等關鍵名詞仍只是未建立方向，容易被誤判為已有系統。
- 如果後續先寫程式碼而不補規格，AI Agent 可能會各自發明不一致的目錄結構。
- 現有模板使用示範文案與示意視覺資產，正式招生前仍需替換成真實課程資料。

## 下一步建議

1. 補第一個真實課程品牌案例，用來驗證 Hero、typography、spacing 與 motion 規則。
2. 建立 `skills/style-selector-skill/README.md`，描述 skill 輸入、輸出與選擇邏輯。
3. 建立正式圖片、攝影或生成圖資產流程。
4. 決定 `public/`、`line-webhook/`、`admin/`、`worker/` 是否屬於同一 repo 的 monorepo 架構。
5. 新增 `docs/ARCHITECTURE.md` 與 `docs/ROADMAP.md`，補上系統架構與短期里程碑。
