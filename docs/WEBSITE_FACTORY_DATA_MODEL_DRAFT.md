# Website Factory Data Model Draft

## 文件目的

本文件由 Chat D：資料庫與後台產出，目標是把 Replit website factory sandbox 內的 client JSON 與 booking form 轉成正式資料模型草案。

本文件只處理資料庫、後台、狀態與表單資料流，不處理美術風格，也不重構前端模板。

## 讀取來源

- `docs/WEBSITE_FACTORY_MIGRATION.md`
- `docs/WEBSITE_FACTORY_INVENTORY.md`
- `docs/PROJECT_STATUS.md`
- `/Users/phoebe/Documents/Codex/2026-05-28/website-factory-migration/extracted/templates-monorepo/clients/massage-amy/data.json`
- `/Users/phoebe/Documents/Codex/2026-05-28/website-factory-migration/extracted/templates-monorepo/booking-form.php`

目前本 worktree 尚未包含 `docs/WEBSITE_FACTORY_MIGRATION.md` 與 `docs/WEBSITE_FACTORY_INVENTORY.md`，本次讀取的是另一個已盤點 worktree 中的文件版本。

## 正式部署限制

正式環境是外部 cPanel shared hosting：

- PHP 5.6
- MySQL 5.7.44
- 不假設 Node.js、長駐 worker、queue daemon 或現代 build server
- 背景任務應設計成 cPanel cron 可觸發的短任務

資料庫設計避免使用：

- MySQL 8 only：CTE、window functions、`CHECK` constraints、`JSON_TABLE`
- PHP 7 / PHP 8 only 語法：`??`、return type declarations、typed properties、arrow functions

MySQL 5.7 支援 `JSON` 型別，但為了降低 cPanel 匯入、備份與舊工具相容風險，網站工廠的彈性設定欄位建議先用 `LONGTEXT` 保存 JSON 字串，再由 PHP 5.6 用 `json_decode()` 驗證。

## Replit client data 觀察

`clients/massage-amy/data.json` 是一個 JSON-driven single page website 設定檔，資料層級如下：

| 區塊 | 說明 |
| --- | --- |
| `site` | 語言、網站標題、SEO description、favicon |
| `theme` | 目前使用 `theme-soft` |
| `blocks[]` | 頁面區塊順序、區塊類型、啟用狀態、variant、block data |

已觀察到的 block types：

- `header`
- `hero`
- `about`
- `service`
- `gallery`
- `workflow`
- `swiper`
- `feedback`
- `faq`
- `contact_line`
- `contact_google`
- `child_themes`
- `footer`

這些 block data 不應直接變成固定資料表欄位，因為不同產業、不同模板會有不同欄位。正式模型應把「專案核心資料」正規化，把「頁面區塊內容」放入可版本化的 block table。

## booking-form.php 觀察

舊表單欄位：

| 欄位 | 必填 | 說明 |
| --- | --- | --- |
| `name` | 是 | 預約 / 諮詢人姓名 |
| `phone` | 是 | 聯絡電話 |
| `type` | 否 | 網站類型：品牌形象網站、電商購物網站、活動 / Landing Page、其他 |
| `budget` | 否 | 預算範圍：3萬以下、3-8萬、8萬以上、尚未確定 |
| `note` | 否 | 需求說明 |

目前問題：

- 使用 PHP 7+ `??`，不相容 PHP 5.6。
- POST 到 `http://localhost:8080/api/booking`，不符合 cPanel 正式部署。
- 未寫入 MySQL。
- 未記錄來源頁、project、client 或通知狀態。
- 沒有 anti-spam / token / rate limit / Turnstile 設計。

正式做法應改為：booking form POST 到同站 PHP endpoint，由後端寫入 `factory_inquiries`，再依需要記錄 notification log。

## 與現有系統關係

目前 repo 已有：

- `clients`
- `course_intakes`
- `course_projects`
- `template_proposals`
- `notification_logs`

網站工廠不應直接覆蓋課程招生的 `course_projects` 語意。建議新增 factory 專屬表，並共用 `clients` 與 `notification_logs`。

原因：

- `course_projects` 已有課程欄位，例如 course name、course type、course format、course dates。
- website factory 的 project 可是按摩、餐飲、工作室、活動頁，不一定是課程。
- 共用 `clients` 可以保留客戶管理；專案主體應獨立。

## 建議正式資料模型

### 1. `clients` 延伸策略

現有 `clients` 可繼續作為共用客戶主檔。

建議保留現有欄位，必要時後續補欄：

| 欄位 | 型別 | 說明 |
| --- | --- | --- |
| `client_id` | int unsigned | 主鍵 |
| `client_name` | varchar(120) | 客戶名稱 |
| `phone` | varchar(40) | 電話 |
| `email` | varchar(190) | Email |
| `line_user_id` | varchar(190) | LINE user id |
| `line_display_name` | varchar(190) | LINE 顯示名稱 |
| `brand_name` | varchar(190) | 品牌名稱 |
| `location_area` | varchar(190) | 地區 |
| `client_status` | varchar(40) | active / inactive |
| `notes` | text | 備註 |

網站工廠需要的 `line_url`、地址、Google map、品牌描述，不建議全部塞進 `clients`，應放在 project profile 或 blocks 中。

### 2. `factory_projects`

網站工廠的專案主檔。

| 欄位 | 型別 | 說明 |
| --- | --- | --- |
| `id` | int unsigned auto increment | 主鍵 |
| `factory_project_id` | varchar(40) | 對外編號，例如 `WF-20260528-00001` |
| `client_id` | int unsigned | 對應 `clients.client_id` |
| `source` | varchar(40) | `sandbox_import` / `admin` / `booking_form` / `line_ai` |
| `site_slug` | varchar(120) | 站點 slug，例如 `massage-amy` |
| `site_lang` | varchar(20) | 例如 `zh-TW` |
| `site_title` | varchar(190) | 網站 title |
| `site_description` | text | SEO description |
| `favicon_path` | varchar(255) | favicon path |
| `theme_id` | varchar(80) | 例如 `theme-soft` |
| `business_type` | varchar(120) | 產業 / 網站類型 |
| `project_status` | varchar(40) | 後台可讀狀態 |
| `factory_status` | varchar(40) | 系統狀態 |
| `current_revision_id` | int unsigned null | 目前使用 revision |
| `published_url` | text null | 已發布網址 |
| `published_at` | datetime null | 發布時間 |
| `expires_at` | datetime null | 到期 / 下架時間 |
| `created_at` | datetime | 建立時間 |
| `updated_at` | datetime | 更新時間 |

建議索引：

- unique `factory_project_id`
- index `client_id`
- index `site_slug`
- index `factory_status`
- index `expires_at`

建議 `factory_status`：

- `draft`：草稿
- `ready_for_preview`：可產預覽
- `preview_generated`：預覽已產生
- `published`：已發布
- `exported`：已匯出
- `archived`：封存
- `failed`：處理失敗

### 3. `factory_project_profiles`

存放專案層級的品牌與聯絡資料。避免把大量 website-specific 欄位塞入 `clients`。

| 欄位 | 型別 | 說明 |
| --- | --- | --- |
| `id` | int unsigned auto increment | 主鍵 |
| `factory_project_id` | varchar(40) | 對應 project |
| `brand_name` | varchar(190) | 品牌名稱 |
| `tagline` | varchar(190) | 標語 |
| `description` | text | 品牌描述 |
| `address` | varchar(255) | 地址 |
| `phone` | varchar(40) | 顯示電話 |
| `email` | varchar(190) | 顯示 email |
| `line_url` | text | LINE 連結 |
| `google_map_embed_url` | text | Google map embed |
| `raw_profile_json` | longtext | 原始 profile JSON |
| `created_at` | datetime | 建立時間 |
| `updated_at` | datetime | 更新時間 |

`massage-amy` 對應：

- `brand_name`: Amy 舒壓工作室
- `tagline`: 讓身心回到最輕盈的狀態
- `address`: 台北市信義區信義路五段 7 號
- `line_url`: `https://line.me/R/ti/p/@amymassage`
- `google_map_embed_url`: 來自 `contact_google.data.google_map_embed_url`

### 4. `factory_site_revisions`

網站工廠必須支援回到某一版設定，因此頁面資料應版本化。

| 欄位 | 型別 | 說明 |
| --- | --- | --- |
| `id` | int unsigned auto increment | 主鍵 |
| `factory_project_id` | varchar(40) | 對應 project |
| `revision_no` | int unsigned | 版本號 |
| `revision_status` | varchar(40) | draft / active / archived |
| `source` | varchar(40) | admin / import / ai_worker |
| `site_config_json` | longtext | 包含 site/theme/block order 的完整 JSON |
| `created_by` | varchar(120) | admin / worker name |
| `created_at` | datetime | 建立時間 |

建議唯一鍵：

- unique `(factory_project_id, revision_no)`

### 5. `factory_page_blocks`

區塊內容表。正式工廠不要靠前端 localStorage 當資料來源。

| 欄位 | 型別 | 說明 |
| --- | --- | --- |
| `id` | int unsigned auto increment | 主鍵 |
| `factory_project_id` | varchar(40) | 對應 project |
| `revision_id` | int unsigned | 對應 revision |
| `block_key` | varchar(80) | 穩定 block id，例如 `hero-1` |
| `block_type` | varchar(80) | header / hero / about / service |
| `variant` | varchar(40) | a / b / c 或其他 variant |
| `sort_order` | int unsigned | 排序 |
| `is_enabled` | tinyint(1) | 是否啟用 |
| `block_data_json` | longtext | block data JSON |
| `created_at` | datetime | 建立時間 |
| `updated_at` | datetime | 更新時間 |

建議索引：

- index `(factory_project_id, revision_id, sort_order)`
- index `block_type`

`massage-amy.blocks[]` 可完整映射到此表，每個 JSON block 一列。

### 6. `factory_assets`

圖片與檔案素材。Replit 原本是 `clients/{id}/images/*`，正式系統需要可追蹤。

| 欄位 | 型別 | 說明 |
| --- | --- | --- |
| `id` | int unsigned auto increment | 主鍵 |
| `factory_project_id` | varchar(40) | 對應 project |
| `asset_type` | varchar(40) | logo / favicon / hero / gallery / avatar / other |
| `original_name` | varchar(190) | 原始檔名 |
| `file_path` | text | 本機或公開路徑 |
| `public_url` | text | 對外 URL |
| `mime_type` | varchar(80) | MIME |
| `file_size` | int unsigned | bytes |
| `width` | int unsigned null | 圖片寬 |
| `height` | int unsigned null | 圖片高 |
| `sort_order` | int unsigned | 排序 |
| `created_at` | datetime | 建立時間 |

`massage-amy` 初始 assets：

- `logo.png`
- `favicon.png`
- `hero-bg.jpg`
- `about.jpg`
- `gallery-1.jpg` to `gallery-4.jpg`
- `avatar-1.jpg`

### 7. `factory_inquiries`

取代舊 `booking-form.php` POST 到 localhost 的正式資料表。

| 欄位 | 型別 | 說明 |
| --- | --- | --- |
| `id` | int unsigned auto increment | 主鍵 |
| `factory_project_id` | varchar(40) null | 來源網站專案 |
| `client_id` | int unsigned null | 若可辨識客戶 |
| `source` | varchar(40) | booking_form / line / admin |
| `name` | varchar(120) | 姓名 |
| `phone` | varchar(40) | 電話 |
| `email` | varchar(190) null | Email，未來可加 |
| `website_type` | varchar(120) null | 舊欄位 `type` |
| `budget_range` | varchar(80) null | 舊欄位 `budget` |
| `message` | text null | 舊欄位 `note` |
| `inquiry_status` | varchar(40) | new / contacted / closed / spam |
| `ip_address` | varchar(45) null | IPv4 / IPv6 |
| `user_agent` | text null | user agent |
| `raw_payload` | longtext null | 原始 POST JSON |
| `created_at` | datetime | 建立時間 |
| `updated_at` | datetime | 更新時間 |

建議索引：

- index `factory_project_id`
- index `phone`
- index `inquiry_status`
- index `created_at`

### 8. `factory_exports`

記錄 preview / zip / published output。

| 欄位 | 型別 | 說明 |
| --- | --- | --- |
| `id` | int unsigned auto increment | 主鍵 |
| `factory_project_id` | varchar(40) | 對應 project |
| `revision_id` | int unsigned | 對應 revision |
| `export_type` | varchar(40) | preview / zip / publish |
| `export_status` | varchar(40) | pending / processing / ready / failed / expired |
| `output_path` | text null | 檔案路徑 |
| `public_url` | text null | 對外網址 |
| `download_token` | char(64) null | 下載 token |
| `expires_at` | datetime null | 到期時間 |
| `error_code` | varchar(80) null | 錯誤碼 |
| `error_message` | text null | 錯誤訊息 |
| `created_at` | datetime | 建立時間 |
| `updated_at` | datetime | 更新時間 |

建議索引：

- index `factory_project_id`
- index `revision_id`
- index `export_status`
- unique `download_token`

### 9. `notification_logs`

可共用現有表，但網站工廠需要新增通知類型：

- `factory_inquiry_received`
- `factory_preview_ready`
- `factory_export_ready`
- `factory_publish_failed`
- `factory_site_expiring`

若共用現有 `notification_logs.project_id`，建議在 message 或 future migration 中補 `project_type`，避免 `course_projects.project_id` 與 `factory_projects.factory_project_id` 混淆。

## 從 data.json 到資料表的對照

| JSON path | 建議表 / 欄位 |
| --- | --- |
| `site.lang` | `factory_projects.site_lang` |
| `site.title` | `factory_projects.site_title` |
| `site.description` | `factory_projects.site_description` |
| `site.favicon` | `factory_projects.favicon_path` + `factory_assets` |
| `theme` | `factory_projects.theme_id` |
| `blocks[]` | `factory_page_blocks` |
| `blocks[].type` | `factory_page_blocks.block_type` |
| `blocks[].variant` | `factory_page_blocks.variant` |
| `blocks[].enabled` | `factory_page_blocks.is_enabled` |
| `blocks[].data` | `factory_page_blocks.block_data_json` |
| `header.data.site_name` | `factory_project_profiles.brand_name` |
| `contact_line.data.line_url` | `factory_project_profiles.line_url` |
| `contact_google.data.map_address` | `factory_project_profiles.address` |
| `contact_google.data.google_map_embed_url` | `factory_project_profiles.google_map_embed_url` |
| image paths | `factory_assets` |

## 從 booking-form.php 到正式表單的對照

| POST 欄位 | 正式欄位 |
| --- | --- |
| `name` | `factory_inquiries.name` |
| `phone` | `factory_inquiries.phone` |
| `type` | `factory_inquiries.website_type` |
| `budget` | `factory_inquiries.budget_range` |
| `note` | `factory_inquiries.message` |

正式 endpoint 建議：

```text
POST /demo/admission-system/api/factory-inquiries/
```

PHP 5.6 實作注意：

- 使用 `isset($_POST['name']) ? trim($_POST['name']) : ''`，不要用 `??`。
- 使用 `array(...)` 或 PHP 5.4+ short array 皆可；若要最保守，採 `array(...)`。
- `json_encode($payload)` 可用，但 `JSON_UNESCAPED_UNICODE` 需要 PHP 5.4+，PHP 5.6 可用。
- 不使用 return type declarations。
- 不使用 `str_starts_with()`。

## 最小 migration 順序建議

第一階段只建立可保存網站工廠資料的基礎表：

1. `factory_projects`
2. `factory_project_profiles`
3. `factory_site_revisions`
4. `factory_page_blocks`
5. `factory_assets`
6. `factory_inquiries`
7. `factory_exports`

第二階段再建立後台：

1. 專案列表
2. 專案詳細資料
3. block JSON 檢視 / 編輯入口
4. inquiry 列表
5. preview/export 狀態

第三階段才接 Chat E export / cron：

1. preview generation
2. zip export
3. expired preview cleanup
4. notification logs

## 不建議現在做

- 不要直接把 Replit `clients/massage-amy/data.json` 當正式 schema。
- 不要把 `blocks[].data` 拆成十幾張固定表；目前 block 類型仍在遷移期，彈性 JSON 更適合。
- 不要把 `booking-form.php` 的 localhost API 搬到正式環境。
- 不要把 `course_projects` 改名或改成通用 project，會影響既有課程招生流程。
- 不要在此階段處理 template 美術分類、CSS、section HTML 或前端重構。

## 開放問題

1. 網站工廠的正式專案編號要用 `WF-YYYYMMDD-00001`，還是沿用既有 `CP-*` 流水規則？
2. `notification_logs` 是否要補 `project_type`，以便同時支援 course / factory？
3. 圖片正式儲存位置是沿用 Cloudflare R2，還是 cPanel 本機 `public/uploads`？
4. 預覽網址是否需要 24 / 72 小時到期 token？
5. booking inquiry 是否需要 Turnstile，還是先用 honeypot + rate limit？

## Chat D 建議結論

網站工廠應建立獨立 `factory_*` 資料表，並與現有 `clients` 共用客戶主檔。頁面內容以 `factory_site_revisions` + `factory_page_blocks` 版本化保存，避免把舊 Replit JSON 直接固定成欄位。舊 `booking-form.php` 應改寫成 PHP 5.6 相容的同站 POST endpoint，寫入 `factory_inquiries`，再透過 `notification_logs` 記錄後台 / Email / LINE 通知狀態。
