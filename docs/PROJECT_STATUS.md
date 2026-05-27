# Project Status

## 狀態摘要

目前專案仍處於 MVP 初期，但已開始出現第一批 PHP 實作：LINE intake API、客戶列表後台、DB 設定與資料庫 migration。

目前產品流程以免費試營運 MVP 為前提，報價、付款與待付款不列入主流程，僅保留為 future phase。

本次工作建立基礎文件，作為後續 AI 協作與專案記憶的起點。

## 已確認存在

- `.git`
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
- `config/config.php`
- `config/local.example.php`
- `lib/db.php`
- `lib/intake_repository.php`
- `database/migrations/001_create_line_intakes.sql`

## 尚未存在的目錄與狀態

| 目錄 | 目前狀態 | 備註 |
| --- | --- | --- |
| `styles/` | 尚未建立 | 尚無色彩、字體、構圖、品牌 token 或風格定義 |
| `schemas/` | 尚未建立 | 尚無 field registry、表單 schema 或資料規則實作 |
| `skills/` | 尚未建立 | 尚無 style-selector-skill 或其他 AI skill |
| `templates/` | 尚未建立 | 尚無 course-brand-template-v1 |
| `public/` | 尚未建立 | 尚無公開素材、圖片、靜態資源 |
| `line-webhook/` | 尚未建立 | 尚無 LINE webhook 程式碼或設定 |
| `admin/` | 已建立初版 | 目前只有 `admin/clients.php` 客戶列表 |
| `api/` | 已建立初版 | 目前只有 `api/line-intakes/` 接收 Cloudflare Worker intake JSON |
| `config/` | 已建立初版 | 使用環境變數或 ignored `config/local.php` 管理 DB 與 API key |
| `lib/` | 已建立初版 | DB 連線與 intake repository |
| `database/` | 已建立初版 | 目前只有 clients / course_intakes migration |
| `worker/` | 尚未建立 | 尚無背景工作或排程 |

## 已完成

- 建立專案背景文件。
- 建立專案狀態文件。
- 建立美術風格系統初始文件。
- 建立 form schema、資料規則與欄位系統初始文件。
- 建立模板參考、客戶選款流程與後端自動化流程規格。
- 修正後端流程為免費試營運 MVP，將報價與付款移到 future phase。
- 建立 `api/line-intakes/`，接收 Cloudflare Worker 送來的 LINE intake JSON。
- 建立 `ADMISSION_API_KEY` 驗證機制。
- 建立 `clients` / `course_intakes` migration 與寫入流程。
- 建立 `admin/clients.php`，可查看 API 寫入的客戶資料。
- 建立 AI 協作規則文件。

## 尚未完成

- 尚未建立應用架構。
- 尚未選定前端、後端、資料庫或部署技術。
- 尚未建立 style-selector-skill 的 skill 目錄與規格。
- 尚未建立 course-brand-template-v1 的模板結構。
- 尚未建立 `schemas/`、field registry、表單 schema 或 validation 程式碼。
- 尚未建立任何測試、CI 或部署流程。
- 尚未建立完整 LINE webhook、admin、worker 的責任分工。
- 尚未建立通知寄送程式或預覽網址 token 機制。
- 尚未將遠端 PHP 後台完整同步回本機 repo。

## 目前風險

- 專案意圖已有關鍵名詞，但目前只有局部 MVP 實作，容易在不同 chat 中被誤判為完整系統。
- 如果後續先寫程式碼而不補規格，AI Agent 可能會各自發明不一致的目錄結構。
- 美術風格系統與模板系統若沒有資料格式約定，之後會難以自動選擇、套用或重構。
- 表單欄位與資料規則若未集中定義，LINE webhook、admin 與模板可能會寫出互不相容的資料。
- 遠端已存在 PHP 後台網址線索，但本機 repo 尚未同步實作檔案，後續需盤點遠端檔案再決定是否匯入。

## 下一步建議

1. 建立 `styles/README.md`，定義風格資料格式。
2. 建立 `schemas/README.md` 與 `schemas/field-registry.json`，定義第一版欄位格式。
3. 將 `course_projects`、`template_proposals`、`notification_logs`、`line_conversations` 補成 migration。
4. 盤點遠端 PHP 後台與本機 repo 差異，決定是否匯入 `admin/`。
5. 建立 `skills/style-selector-skill/README.md`，描述 skill 輸入、輸出與選擇邏輯。
6. 建立 `templates/course-brand-template-v1/README.md`，定義模板目標與資料結構。
7. 決定 `public/`、`line-webhook/`、`admin/`、`worker/` 是否屬於同一 repo 的 monorepo 架構。
8. 新增 `docs/ARCHITECTURE.md` 與 `docs/ROADMAP.md`，補上系統架構與短期里程碑。
