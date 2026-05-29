# Website Factory Inventory

## 盤點基準

本文件由 Chat M：網站工廠建立，用於記錄 Replit website factory zip 的第一輪 migration inventory。

來源 zip：

```text
/Users/phoebe/Documents/Codex/2026-05-28/website-factory-migration/templates-monorepo (5).zip
```

已解壓 sandbox：

```text
/Users/phoebe/Documents/Codex/2026-05-28/website-factory-migration/extracted/templates-monorepo/
```

本次未解壓到 repo 根目錄，未覆蓋 `templates/course-brand-template-v1/`，也未搬移任何 Replit 原始碼進正式 repo。

## 正式部署限制

正式環境以外部租用主機為準：

| 項目 | 正式外部主機 | 本機 |
| --- | --- | --- |
| PHP | 5.6 | 8.4.21 |
| MySQL | 5.7.44 | 8.0.46 |
| Web Server | cPanel 主機 | Apache 2.4.67 |

本 inventory 中所有「可保留 / 需改寫」判斷，都以 PHP 5.6、MySQL 5.7.44、cPanel shared hosting 為準。

## 整體統計

zip 原始清單顯示：

- 1048 個 zip items。
- 解壓後 sandbox 內約 701 files、347 dirs。
- 主要檔案類型：CSS 301、JS 151、HTML 64、JPG 68、PNG 51、SVG 15、PHP 6、Python 1、JSON 1、MP4 3、字型檔 35。
- 內含一份重複巢狀 zip：`templates-monorepo (1).zip`，約 59 MB，內容接近同一份 monorepo，應視為重複包，不作為新系統原始碼。

## 第一層結構

| 路徑 | 大小 / 數量 | 初步判斷 |
| --- | ---: | --- |
| `build.py` | 8 KB | Python / Jinja2 靜態組裝器，可作為流程參考；不適合直接部署到 cPanel runtime。 |
| `preview.php` | 36 KB | 實際是 HTML + 大量前端 JS 的預覽 iframe，不是後端 preview engine。 |
| `block-gallery.html` | 104 KB | 區塊選擇 UI，含 localStorage、iframe preview、export 呼叫。可作為 admin/editor 原型參考。 |
| `text-editor.html` | 40 KB | 文字編輯 UI，讀取與重組同一套 block state。可作為 admin/editor 原型參考。 |
| `export-zip.php` | 8 KB | zip 匯出 API，但使用 PHP 7+ 語法；PHP 5.6 需重寫。 |
| `export-get.php` | 4 KB | 下載暫存 zip，但使用 PHP 7+ 語法；PHP 5.6 需重寫。 |
| `template.php` | 4 KB | SSI include processor，但使用 PHP 7+ 語法；PHP 5.6 需重寫。 |
| `booking-form.php` | 12 KB | 表單頁與 curl POST；使用 PHP 7+ `??`，且指向 `localhost:8080`，需重寫。 |
| `shared/` | 244 KB / 50 files | Jinja2 block library，最有移植價值。 |
| `template-001/` | 16 MB / 272 files | 完整舊模板與 vendor components，視覺/section 可參考，不建議整包搬。 |
| `template-002/` | 36 MB / 244 files | 完整舊模板，含大型 video；視覺/section 可參考，不建議整包搬。 |
| `template-003/` | 19 MB / 104 files | 完整舊模板，components 較少；視覺/section 可參考，不建議整包搬。 |
| `themes/` | 12 KB / 3 files | 三組主題 CSS：soft、craft、edgy；可交給 Chat A 分析。 |
| `clients/` | 44 KB / 9 files | 目前只有 `massage-amy` 範例資料；可交給 Chat D 做 data model 對照。 |
| `dist/` | 72 KB / 9 files | 已輸出的範例靜態頁，應視為 build artifact，不作為原始碼。 |

## Shared Blocks

`shared/blocks/` 是最接近「網站工廠核心積木」的部分。共有 14 種 block，每種通常包含 `.html`、`.css`、`.js`：

- `loader`
- `header`
- `hero`：含 `hero-a`、`hero-b`、`hero-c` 三個 variant。
- `about`
- `gallery`
- `service`
- `workflow`
- `swiper`
- `feedback`
- `faq`
- `contact_line`
- `contact_google`
- `child_themes`
- `footer`

初步判斷：

- 可保留概念：block registry、block ordering、variant、client data 驅動渲染。
- 需重寫形式：目前 block 使用 Jinja2 template 與相對路徑，未與現有 `styles/` token 系統整合。
- 不宜直接搬入 `templates/course-brand-template-v1/`，應先設計 `factory/blocks/` 或等價架構。

## Template Families

### `template-001`

包含約 130 個 component 目錄，屬於大型舊 HTML template / vendor component bundle。

可辨識 section：

- Header
- Hero
- About
- Swiper
- Gallery
- Workflow
- Feedback / Testimonials
- Contact Line
- Contact Google
- Service
- Child Themes
- Footer
- Loader

初步判斷：適合作為 Chat A 的視覺參考與 Chat B 的 section behavior 參考，不宜整包搬移。

### `template-002`

包含約 129 個 component 目錄，且有大型 video assets。

可辨識 section：

- Header / Navigation
- Hero with video background
- Statistics / Counter
- Clients / Partners
- Why Choose Us
- Swiper
- Gallery
- Skills / Progress Bar
- CTA
- Contact Google
- Services
- FAQ / Accordion
- Contact Line with video background
- Child Themes
- Footer

初步判斷：適合萃取「影音 hero、FAQ、統計、CTA」的可用概念；video asset 較大，正式輸出需特別控制 cPanel 空間與流量。

### `template-003`

包含約 37 個 component 目錄，較輕量。

可辨識 section：

- Header
- Hero / Intro
- Child Themes
- Why Choose Us
- About
- Partners / Clients
- Testimonials
- Case Studies
- CTA
- Contact Google
- Advantages / Features Tabs
- Approach / Service
- Team
- Footer
- Loader

初步判斷：適合萃取「案例、tabs、team、testimonials」等 section pattern。

## Client Data

目前只有一組 client：

```text
clients/massage-amy/data.json
```

資料形狀：

- `site`：語言、title、description、favicon。
- `theme`：例如 `theme-soft`。
- `blocks[]`：依序定義頁面區塊。
- 每個 block 含 `type`、`enabled`、optional `variant`、`data`。
- block data 內包含 menu、hero、about、service items、gallery、workflow、reviews、faq、LINE、Google Map、theme cards、footer 等欄位。

初步判斷：

- 可作為 Chat D 設計 `clients`、`course_projects`、`page_blocks` 或 `template_proposals` 的輸入參考。
- 不應直接把 JSON 欄位視為正式 schema；需轉成課程招生 domain，而不是按摩工作室 domain。

## Build / Preview / Export Flow

### `build.py`

用途：

1. 讀 `clients/{id}/data.json`。
2. 依 `blocks[]` 讀取 `shared/blocks`。
3. 用 Jinja2 渲染 HTML。
4. 組裝完整頁面。
5. 輸出到 `dist/{id}/index.html`。
6. 複製 `clients/{id}/images/` 到 `dist/{id}/images/`。

判斷：

- 流程概念可保留。
- Python/Jinja2 不應假設可在 cPanel 正式 runtime 執行。
- 若未來保留 Python build，應定位為本機或 Codex build step，輸出靜態檔後再上傳 cPanel。

### `preview.php` / `block-gallery.html`

用途：

- 用 iframe 生成組合預覽。
- 從 `template-001/002/003/index.html` 依註解 keyword 擷取 section。
- 使用 `localStorage` 保存 nav、hero、content、interactive、cta、footer、theme、blockOrder。
- 前端即時組合 HTML，並可呼叫 `export-zip.php`。

判斷：

- 這是 editor/admin prototype，不是正式 backend。
- keyword 擷取 HTML section 的方式脆弱，正式工廠應改成明確 block registry。
- 可交給 Chat B 參考互動模式，交給 Chat D/Admin 參考 UI 需求。

### `export-zip.php` / `export-get.php`

用途：

- 接收組合後 HTML。
- 重寫路徑。
- 用 `ZipArchive` 打包 `index.html`、template assets、shared assets、booking form、clients、dist。
- 直接串流 zip 給瀏覽器，或用 token 下載暫存檔。

判斷：

- 匯出概念可保留。
- 目前程式不可直接部署到 PHP 5.6，因為使用 PHP 7+ 語法。
- 依賴 `ZipArchive`，需確認 cPanel PHP 5.6 是否啟用 zip extension。
- 不應把完整 `clients/`、`dist/` 全包進每次客戶匯出；需重新定義 export scope。

## PHP 5.6 相容性風險

以下檔案含 PHP 7+ only 語法，需改寫才可上外部主機：

- `export-zip.php`
- `export-get.php`
- `template.php`
- `booking-form.php`

已觀察到的問題：

- `declare(strict_types=1)`。
- function parameter / return type declarations。
- null coalescing operator `??`。
- `str_starts_with()` 是 PHP 8 function，不存在於 PHP 5.6。
- short array syntax `[]` 在 PHP 5.4+ 可用，PHP 5.6 可跑，但仍需統一風格與相容性檢查。
- `booking-form.php` POST 到 `http://localhost:8080/api/booking`，不符合 cPanel shared hosting 正式部署假設。

本機 `php -l` 只能用 PHP 8.4 檢查語法，不能代表 PHP 5.6 可部署。

## 外部依賴與部署風險

已觀察到：

- CDN：Bootstrap 5.3.3、Font Awesome、Owl Carousel、jQuery。
- Google Fonts：`Noto Serif TC`、`Noto Sans TC`、`Bebas Neue`、`Inter` 等。
- Google Map embed。
- LINE URL。
- Google reCAPTCHA references。
- TemplateMonster 連結與舊模板殘留頁面。
- 大型影片：`template-002/video/bg-video-2.mp4` 約 15 MB，另有約 9.9 MB mp4。

風險：

- cPanel 空間與流量需控管影片與 vendor asset。
- 外部 CDN 可能影響正式客戶頁穩定性；需決定是否本地化。
- 舊 template vendor components 很大，且含大量未使用 CSS / JS，不宜整包搬入正式架構。

## 初步分類

### 可保留概念

- block registry / block ordering。
- block variant，例如 `hero-a`、`hero-b`、`hero-c`。
- client data 驅動頁面產生。
- 預覽與匯出流程。
- 三組 themes：soft、craft、edgy。
- 一頁式網站 section 組合邏輯。

### 可保留素材但需整理

- `shared/blocks/` 的 HTML/CSS/JS。
- `template-001/002/003` 的 section 結構與視覺方向。
- `clients/massage-amy/data.json` 作為資料範例。
- `dist/massage-amy/index.html` 作為輸出結果對照。

### 需要重寫

- PHP export endpoints。
- PHP template SSI processor。
- booking form submit flow。
- block-gallery 的 keyword-based section extraction。
- 正式 admin/editor data persistence。
- cPanel cron-compatible export / cleanup / notification workflow。

### 應淘汰或隔離

- `dist/` 作為原始碼。
- 巢狀 `templates-monorepo (1).zip`。
- 未使用 vendor components 整包搬移。
- TemplateMonster demo links 與 demo-only pages。
- 依賴 `localhost:8080` 的 booking API。

## 建議後續交接

### 給 Chat D：資料庫與後台

優先分析：

- `clients/massage-amy/data.json`
- `booking-form.php`

輸出：

- PHP 5.6 / MySQL 5.7.44 相容的資料模型草案。
- `clients`、`course_projects`、`page_blocks`、`template_proposals`、`notification_logs` 欄位建議。
- booking form 欄位與正式表單 schema 對照。

### 給 Chat E：自動化與 AI Worker

優先分析：

- `build.py`
- `export-zip.php`
- `export-get.php`

輸出：

- cPanel cron 可執行的短任務設計。
- zip export scope。
- export tmp cleanup。
- preview / publish / download pipeline。

### 給 Chat A：美術風格系統

優先分析：

- `themes/theme-soft.css`
- `themes/theme-craft.css`
- `themes/theme-edgy.css`
- `template-001/002/003` 的 section screenshots 或 rendered pages。

輸出：

- 是否新增到 `docs/TEMPLATE_REFERENCE.md`。
- 哪些 template family 可轉成課程招生方向。
- 哪些視覺 pattern 禁止原樣搬用。

### 給 Chat B：前端工廠轉接

需等待 Chat A template direction 後再做。

優先分析：

- `shared/blocks/`
- `block-gallery.html`
- `preview.php`
- `template-001/002/003/index.html`

輸出：

- `factory/blocks/` 目錄責任草案。
- 明確 block registry，而不是 keyword 擷取。
- 與現有 `styles/` token system 的接合方式。

## 建議新增 repo 目錄

目前只是 inventory 建議，尚未建立：

- `factory/README.md`
- `factory/blocks/`
- `factory/schemas/`
- `factory/export/`
- `admin/`
- `worker/`
- `public/`

每個目錄建立前都應先有 README 說明責任邊界，避免把舊 Replit 工廠、課程招生模板、後台與 worker 混在一起。
