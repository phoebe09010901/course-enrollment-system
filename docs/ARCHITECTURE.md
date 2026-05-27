# Architecture

## 文件目的

本文件定義「課程招生 - 系統」的世界觀、workflow 與 Router 架構。內容屬於初始化規格，用來建立後續實作邊界；目前 repo 仍尚未包含應用程式碼、Router 實作、資料庫、API 或前端介面。

## 世界觀

本系統的核心不是單一招生頁，也不是泛用內容產生器，而是一套「課程招生作業系統」。它應協助人類與 AI Agent 將課程定位、品牌風格、招生內容、素材模板、訊息回覆與後台管理串成可追蹤、可重複、可演進的流程。

所有功能應服務三個目標：

- 讓課程價值被清楚整理與呈現。
- 讓招生素材能維持一致的品牌與視覺規則。
- 讓 AI Agent 在長期協作中知道目前資料、模板與系統功能的真實狀態。

## 核心物件

| 物件 | 預期責任 | 目前狀態 |
| --- | --- | --- |
| Course | 課程基本資料、受眾、賣點、課綱、講師與招生目標 | 尚未建立 |
| Brand Style | 色彩、字體、語氣、圖片方向、版型規則 | 尚未建立 |
| Template | 招生頁、社群圖文、LINE 訊息、簡報等輸出結構 | 尚未建立 |
| Asset | 圖片、文案、影片、簡報、靜態資源 | 尚未建立 |
| Lead | 潛在學員、互動紀錄、報名狀態 | 尚未建立 |
| Workflow | 從需求輸入到素材產出與追蹤的流程定義 | 規劃中 |
| Router | 依任務類型分派到對應流程或模組 | 規劃中 |

## Workflow 架構

Workflow 是系統的工作流層，負責把「我要招生」拆成可執行步驟。它不應只是一串 prompt，而應能清楚描述輸入、處理、輸出與狀態。

建議的主要 workflow：

| Workflow | 輸入 | 輸出 | 目標模組 |
| --- | --- | --- | --- |
| Course Intake | 課程主題、受眾、價格、時程、講師資料 | 結構化 Course 資料 | `admin/` |
| Brand Setup | 課程定位、受眾、風格偏好 | Brand Style 設定 | `styles/` |
| Style Selection | Course、素材用途、既有風格限制 | 選定 style id 與理由 | `skills/style-selector-skill/` |
| Template Compose | Course、Brand Style、輸出用途 | 可渲染模板資料 | `templates/` |
| Public Publish | 模板資料、靜態資源 | 公開招生頁或素材 | `public/` |
| LINE Reply | LINE event、Lead 狀態、課程資料 | 回覆訊息或導流動作 | `line-webhook/` |
| Background Sync | 排程、外部事件、狀態變化 | 非同步任務結果 | `worker/` |

## Router 架構

Router 是任務分派層，負責判斷一個請求應進入哪個 workflow 或模組。它不應承擔所有業務邏輯，而應保持薄層：辨識意圖、檢查必要資料、分派任務、回傳結果。

### Router 預期責任

- 解析任務類型，例如建立課程、選擇風格、產生模板、處理 LINE event。
- 檢查必要輸入是否足夠。
- 選擇對應 workflow。
- 將 workflow 結果轉交給前台、後台、webhook 或 worker。
- 在資料不足時回傳明確缺口，而不是自行發明資料。

### Router 不應負責

- 不應直接定義品牌風格規則。
- 不應直接生成所有文案與設計。
- 不應直接操作外部平台而沒有 workflow 紀錄。
- 不應把不存在的目錄或功能視為已完成。

## 建議分層

```text
Request / Event
  -> Router
    -> Workflow
      -> Domain Data
      -> Style System
      -> Template System
      -> Integration
  -> Response / Artifact / Job
```

### Request / Event

系統入口。可能來自 admin 操作、LINE webhook、排程 worker、AI Agent 指令或未來 API。

### Router

負責辨識任務與選擇 workflow。未來可存在於後端服務或 AI Agent 協作規則中，但目前尚未實作。

### Workflow

負責任務步驟與狀態。每個 workflow 應可被文件化、測試與重複執行。

### Domain Data

課程、講師、受眾、價格、場次、Lead 等招生核心資料。

### Style System

品牌與視覺規則來源。Router 與 workflow 只能讀取或引用 style，不應臨時發明不一致的規則。

### Template System

將資料與 style 套入固定輸出結構。模板應將內容欄位、視覺 token 與輸出格式分離。

### Integration

LINE、公開頁面、背景任務、外部表單或未來 CRM 等整合層。

## 初始路由表草案

| 任務 | Router 判斷 | 對應 workflow | 預期輸出 |
| --- | --- | --- | --- |
| 新增一門課程 | 有課程名稱或課程描述 | Course Intake | Course draft |
| 建立品牌風格 | 有受眾、調性或視覺偏好 | Brand Setup | Brand Style draft |
| 選擇素材風格 | 有素材用途與課程資料 | Style Selection | style id 與使用限制 |
| 產生招生頁 | 要求頁面、landing page 或公開內容 | Template Compose + Public Publish | 頁面資料或靜態頁 |
| 產生 LINE 回覆 | 來源是 LINE event 或對話情境 | LINE Reply | reply message |
| 執行排程任務 | 來源是時間或背景 job | Background Sync | job result |

## 目錄責任邊界

| 目錄 | 責任 | 目前狀態 |
| --- | --- | --- |
| `docs/` | 長期記憶、架構、協作規則 | 已建立 |
| `styles/` | Brand Style 與視覺 token | 尚未建立 |
| `skills/` | AI Agent 可讀取與執行的 skill | 尚未建立 |
| `templates/` | 課程品牌模板與輸出結構 | 尚未建立 |
| `public/` | 公開頁面與靜態素材 | 尚未建立 |
| `line-webhook/` | LINE webhook event handling | 尚未建立 |
| `admin/` | 課程、品牌、素材與 Lead 管理 | 尚未建立 |
| `worker/` | 排程與背景工作 | 尚未建立 |

## 實作順序建議

1. 建立 `styles/README.md`，先定義 Brand Style 資料格式。
2. 建立 `templates/course-brand-template-v1/README.md`，定義模板資料結構。
3. 建立 `skills/style-selector-skill/README.md`，定義 style 選擇輸入與輸出。
4. 建立 `docs/WORKFLOWS.md`，逐一展開 Course Intake、Style Selection、Template Compose。
5. 決定 Router 是先以文件與 AI 協作規則存在，或直接落地成後端程式碼。
6. 再決定 `admin/`、`line-webhook/`、`worker/` 的技術棧與 monorepo 分工。

## 待決策

- 第一個實際課程案例是什麼？
- Router 要先服務 AI Agent 工作流，還是先服務應用程式 API？
- Course、Brand Style、Template 的資料格式要使用 JSON、Markdown front matter，還是資料庫 schema？
- LINE webhook 是否是第一個外部整合？
- `public/` 是靜態網站輸出，還是前端 app？
