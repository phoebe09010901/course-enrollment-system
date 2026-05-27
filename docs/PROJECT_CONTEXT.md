# Project Context

## 專案定位

本專案目前命名為「課程招生 - 系統」。目前 repo 已開始落地第一批 PHP MVP：Cloudflare Worker 可呼叫的 LINE intake API、客戶資料寫入、`clients` / `course_intakes` migration，以及 `admin/clients.php` 客戶列表。

目前可推定的工作方向是建立一套支援「課程招生」相關素材或系統的基礎架構，並逐步整理：

- 美術風格系統
- style-selector-skill
- course-brand-template-v1
- form schema、資料規則與欄位系統
- 後端資料庫、自動化 Worker 與客戶選款流程
- 前台或公開素材區
- LINE webhook
- admin 管理介面
- worker 背景工作

多數模組仍在規格階段，因此本文件需清楚區分「已落地的 MVP 檔案」與「規劃中功能」。

## 目前 repo 實際狀態

目前 repo 內已有 `docs/` 文件與第一批 PHP MVP 檔案。以下目錄目前不存在：

- `styles/`
- `skills/`
- `templates/`
- `public/`
- `line-webhook/`
- `worker/`

以下目錄已出現初版實作：

- `api/line-intakes/`
- `admin/`
- `config/`
- `lib/`
- `database/migrations/`

以下文件已建立並填入初始內容：

- `docs/PROJECT_CONTEXT.md`
- `docs/PROJECT_STATUS.md`
- `docs/STYLE_SYSTEM.md`
- `docs/FORM_SCHEMA.md`
- `docs/TEMPLATE_REFERENCE.md`
- `docs/CLIENT_SELECTION_FLOW.md`
- `docs/BACKEND_AUTOMATION_FLOW.md`
- `docs/COLLABORATION_SETUP.md`
- `README.md`
- `api/line-intakes/index.php`
- `admin/clients.php`
- `database/migrations/001_create_line_intakes.sql`

## 專案世界觀

本專案的核心世界觀應以「課程招生」為中心，而不是單純的樣板網站或泛用自動化工具。未來所有美術、品牌、模板、文案、管理與 webhook 設計，都應服務於以下問題：

- 如何清楚呈現課程價值？
- 如何讓招生素材保持品牌一致？
- 如何降低製作多種招生頁、圖文、訊息模板的成本？
- 如何讓 AI Agent 能在長期協作中理解目前的品牌規則與系統邊界？

## 核心名詞

### 美術風格系統

預期用來定義課程品牌的視覺語言，例如色彩、字體、構圖、圖片風格、語氣與素材規範。

目前尚未有 `styles/` 目錄或具體風格檔案。

### style-selector-skill

預期是一個供 AI Agent 選擇或套用風格的 skill。它可能負責根據課程類型、受眾、品牌調性或素材用途，選出合適的風格配置。

目前尚未有 `skills/` 目錄或 style-selector-skill 實作。

### course-brand-template-v1

預期是一個課程品牌模板重構方向，可能用來提供穩定的招生素材結構、品牌 token、版型規則與可重複產出的模板。

目前尚未有 `templates/` 目錄或 `course-brand-template-v1` 實體檔案。

### form schema、資料規則與欄位系統

預期用來定義課程招生流程中的穩定欄位、驗證規則、表單版本與跨系統資料語意。它應支援未來招生頁、LINE webhook、admin 與 worker 共用同一套欄位定義。

目前尚未有 `schemas/` 目錄、field registry 或 validation 實作；已新增 `docs/FORM_SCHEMA.md` 作為初始化規格。

### 後端資料庫、自動化 Worker 與客戶選款流程

預期用來串接 LINE AI 客服收件、客戶資料建檔、課程專案、三款樣板提案、預覽網址、Email / LINE 通知、三天作廢與後台管理。

目前尚未有 `admin/`、`line-webhook/` 或 `worker/` 實作；已新增 `docs/BACKEND_AUTOMATION_FLOW.md` 與 `docs/CLIENT_SELECTION_FLOW.md` 作為流程規格。

### LINE intake API

目前已建立 `api/line-intakes/index.php`，用來接收 Cloudflare Worker 送來的 LINE intake JSON。此端點會驗證 `ADMISSION_API_KEY`，再寫入 `clients` 與 `course_intakes`，讓 `admin/clients.php` 可以看到客戶資料。

此 API 是 MVP 初版，不等於完整 LINE webhook 或 AI Worker。

## AI Agent 閱讀順序

新 chat 或 AI Agent 接手時，建議先依序閱讀：

1. `docs/PROJECT_CONTEXT.md`
2. `docs/PROJECT_STATUS.md`
3. `docs/STYLE_SYSTEM.md`
4. `docs/TEMPLATE_REFERENCE.md`
5. `docs/CLIENT_SELECTION_FLOW.md`
6. `docs/FORM_SCHEMA.md`
7. `docs/BACKEND_AUTOMATION_FLOW.md`
8. `docs/COLLABORATION_SETUP.md`

若未來新增實作目錄，接著再讀：

1. `styles/`
2. `schemas/`
3. `skills/`
4. `templates/`
5. `public/`
6. `line-webhook/`
7. `admin/`
8. `worker/`

## 目前重要限制

- 不得假設已存在尚未落地的功能。
- 不得將「規劃中」內容寫成「已完成」內容。
- 新增功能前應先建立對應目錄、README 或規格文件。
- 若未來 repo 中出現實作，文件需同步更新，不可讓 docs 停留在空 repo 狀態。
