# Project Context

## 專案定位

本專案目前命名為「課程招生 - 系統」。目前 repo 已開始落地第一組課程品牌設計基礎與靜態招生頁模板，但尚未包含後端、資料庫、webhook、admin 或 worker 功能。

目前可推定的工作方向是建立一套支援「課程招生」相關素材或系統的基礎架構，並逐步整理：

- 美術風格系統
- style-selector-skill
- course-brand-template-v1
- 前台或公開素材區
- LINE webhook
- admin 管理介面
- worker 背景工作

其中 `styles/` 與 `templates/course-brand-template-v1/` 已有第一版前端實作。其他系統性功能仍是規劃方向，不得描述為已完成。

## 目前 repo 實際狀態

截至目前，repo 內已存在：

- `docs/`
- `styles/`
- `templates/course-brand-template-v1/`

以下目錄目前不存在：

- `skills/`
- `public/`
- `line-webhook/`
- `admin/`
- `worker/`

目前重要文件包含：

- `docs/PROJECT_CONTEXT.md`
- `docs/PROJECT_STATUS.md`
- `docs/STYLE_SYSTEM.md`
- `docs/COLLABORATION_SETUP.md`
- `styles/course-brand-template-v1.json`
- `styles/layout-rules/landing-page.md`
- `styles/typography/course-brand.md`
- `styles/motion/animation-style.md`
- `styles/tokens/course-brand.css`
- `templates/course-brand-template-v1/index.html`
- `templates/course-brand-template-v1/css/page.css`
- `templates/course-brand-template-v1/js/page.js`

## 專案世界觀

本專案的核心世界觀應以「課程招生」為中心，而不是單純的樣板網站或泛用自動化工具。未來所有美術、品牌、模板、文案、管理與 webhook 設計，都應服務於以下問題：

- 如何清楚呈現課程價值？
- 如何讓招生素材保持品牌一致？
- 如何降低製作多種招生頁、圖文、訊息模板的成本？
- 如何讓 AI Agent 能在長期協作中理解目前的品牌規則與系統邊界？

## 核心名詞

### 美術風格系統

預期用來定義課程品牌的視覺語言，例如色彩、字體、構圖、圖片風格、語氣與素材規範。

目前已有 `styles/` 目錄與 `course-brand-template-v1` 的第一版風格資料、版面規則、字體規則、動態規則與 CSS token。

### style-selector-skill

預期是一個供 AI Agent 選擇或套用風格的 skill。它可能負責根據課程類型、受眾、品牌調性或素材用途，選出合適的風格配置。

目前尚未有 `skills/` 目錄或 style-selector-skill 實作。

### course-brand-template-v1

預期是一個課程品牌模板重構方向，可能用來提供穩定的招生素材結構、品牌 token、版型規則與可重複產出的模板。

目前已有 `templates/course-brand-template-v1/` 靜態前端模板，包含 HTML、CSS、JS 與本地 hero 視覺資產。此模板不包含後端、表單 schema、SQL 或 webhook。

## AI Agent 閱讀順序

新 chat 或 AI Agent 接手時，建議先依序閱讀：

1. `docs/PROJECT_CONTEXT.md`
2. `docs/PROJECT_STATUS.md`
3. `docs/STYLE_SYSTEM.md`
4. `docs/COLLABORATION_SETUP.md`

若未來新增實作目錄，接著再讀：

1. `styles/`
2. `skills/`
3. `templates/`
4. `public/`
5. `line-webhook/`
6. `admin/`
7. `worker/`

## 目前重要限制

- 不得假設已存在尚未落地的功能。
- 不得將「規劃中」內容寫成「已完成」內容。
- 新增功能前應先建立對應目錄、README 或規格文件。
- 若未來 repo 中出現實作，文件需同步更新，不可讓 docs 停留在空 repo 狀態。
