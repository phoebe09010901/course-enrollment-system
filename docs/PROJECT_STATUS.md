# Project Status

## 狀態摘要

目前專案處於初始化階段。repo 尚未包含實際應用程式碼、skill 實作、公開資源或後端服務。

目前已新增 `styles/` design foundation，用來支撐品牌型課程頁的 Hero、typography、spacing、motion、gallery rhythm、visual hierarchy 與 section choreography。

本次工作建立四份基礎文件，作為後續 AI 協作與專案記憶的起點。

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

## 尚未存在的目錄與狀態

| 目錄 | 目前狀態 | 備註 |
| --- | --- | --- |
| `styles/` | 已建立 frontend-ready foundation | 已有 CSS tokens、Hero、typography、spacing、motion、layout rules，尚未接入網站 |
| `skills/` | 尚未建立 | 尚無 style-selector-skill 或其他 AI skill |
| `templates/` | 尚未建立 | 尚無實體 template 目錄；目前只有 `styles/course-brand-template-v1.json` 作為 design foundation |
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

## 尚未完成

- 尚未定義產品需求與實際使用者流程。
- 尚未建立應用架構。
- 尚未選定前端、後端、資料庫或部署技術。
- 尚未建立 style-selector-skill 的 skill 目錄與規格。
- 尚未建立 `templates/course-brand-template-v1/` 的實體模板結構。
- 尚未將 `styles/` foundation 套用到任何實際網站。
- 尚未建立任何測試、CI 或部署流程。
- 尚未建立 LINE webhook、admin、worker 的責任分工。

## 目前風險

- 專案意圖已有關鍵名詞，但 repo 尚無實作，容易在不同 chat 中被誤判為已有系統。
- 如果後續先寫程式碼而不補規格，AI Agent 可能會各自發明不一致的目錄結構。
- 目前已有第一版 design foundation，但尚未有實際品牌案例與網站實作驗證，仍可能需要調整。

## 下一步建議

1. 建立 `skills/style-selector-skill/README.md`，描述 skill 輸入、輸出與選擇邏輯。
2. 建立 `templates/course-brand-template-v1/README.md`，定義模板目標與資料結構。
3. 補第一個真實課程品牌案例，用來驗證 Hero、typography、spacing 與 motion 規則。
4. 決定 `public/`、`line-webhook/`、`admin/`、`worker/` 是否屬於同一 repo 的 monorepo 架構。
5. 新增 `docs/ARCHITECTURE.md` 與 `docs/ROADMAP.md`，補上系統架構與短期里程碑。
