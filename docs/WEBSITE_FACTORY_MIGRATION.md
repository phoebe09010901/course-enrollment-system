# Website Factory Migration

## 文件目的

本文件定義如何把 Replit 匯出的 website factory 移植到 Codex 協作流程中，並說明應該放進哪幾個 chat 分工處理。

目前 Replit 匯出檔放在：

```text
/Users/phoebe/Documents/Codex/2026-05-28/website-factory-migration/templates-monorepo (5).zip
```

初步盤點結果：此 zip 是舊式 PHP / 靜態模板 monorepo，不是目前 repo 內已建立的課程招生頁模板。內容包含：

- `build.py`
- `preview.php`
- `export-get.php`
- `export-zip.php`
- `booking-form.php`
- `shared/blocks/`
- `template-001/`
- `template-002/`
- `template-003/`
- `themes/`
- `clients/`
- `dist/`

目前不應直接把整包 zip 解壓進主 repo，也不應直接覆蓋 `templates/course-brand-template-v1/`。正確做法是先建立 migration sandbox，再把可用概念整理成新 repo 架構。

## 目標主機規格

整個 website factory migration 必須以外部租用主機規格為正式執行環境。本機環境只用於開發、檢查與輔助測試，不可作為正式相容性判斷依據。

| 環境 | 外部租用主機 | 本機主機 |
| --- | --- | --- |
| PHP | 5.6 | 8.4.21 |
| MySQL | 5.7.44 | 8.0.46 |
| Web Server | cPanel 主機 | Apache 2.4.67 |
| 風險 | 舊語法可跑 | 舊語法可能壞掉 |

正式可交付的 PHP / MySQL / export / admin / worker 設計，都必須優先符合：

- PHP 5.6 相容。
- MySQL 5.7.44 相容。
- cPanel shared hosting 可部署。
- 不假設外部主機能跑 Node.js、長駐 worker、queue daemon 或現代 build server。
- 不使用 PHP 7 / PHP 8 only 語法，例如 scalar type declarations、return type declarations、nullable types、`??`、`<=>`、typed properties、arrow functions、attributes、constructor property promotion。
- 不使用 MySQL 8 only 功能，例如 CTE、window functions、`CHECK` constraints、`JSON_TABLE`。
- 背景任務應先設計成 cPanel cron 可觸發的短任務，而不是長駐程序。
- 前端若需要 build step，輸出結果必須能以靜態檔或 PHP 5.6 可執行檔部署到 cPanel。

## 建議工作方式

## 正式開啟方式

當使用者說「開啟網站工廠」或「正式開啟網站工廠」時，AI Agent 應進入 Chat M：網站工廠模式。

開啟後必須先做：

1. 閱讀 `docs/PROJECT_CONTEXT.md`、`docs/PROJECT_STATUS.md`、`docs/COLLABORATION_SETUP.md`、`docs/WEBSITE_FACTORY_MIGRATION.md`、`docs/WEBSITE_FACTORY_INVENTORY.md`。
2. 確認正式部署限制：外部 cPanel 主機、PHP 5.6、MySQL 5.7.44。
3. 確認 Replit sandbox 是否存在：

```text
/Users/phoebe/Documents/Codex/2026-05-28/website-factory-migration/extracted/templates-monorepo/
```

4. 回報目前網站工廠狀態：已盤點、尚未建立正式 `factory/`、`admin/`、`worker/`。
5. 依使用者目標選擇下一條線：Chat M-A 網站工廠核心 / Zip 匯出、Chat M-B 課程招生資料注入格式、Chat M-C Zip 驗收 / 品管、Chat M-D 樣式系統 / 模板視覺規格。

建議開啟 prompt：

```text
正式開啟網站工廠。
你是 Chat M：網站工廠。
請先閱讀 docs/PROJECT_CONTEXT.md、docs/PROJECT_STATUS.md、docs/COLLABORATION_SETUP.md、docs/WEBSITE_FACTORY_MIGRATION.md、docs/WEBSITE_FACTORY_INVENTORY.md。
正式部署環境是外部 cPanel 主機：PHP 5.6、MySQL 5.7.44，不以本機 PHP 8.4 / MySQL 8 為準。
請回報目前網站工廠狀態，並建議下一步應交給 Chat M-A、Chat M-B、Chat M-C 或 Chat M-D 的哪一條線。
```

開啟網站工廠不代表可以直接把 Replit code 搬入 repo。任何新增正式目錄前，都必須先建立 README 或規格文件說明責任邊界。

## 獨立網站工廠分工

網站工廠正式拆成四條獨立子線。這四條線服務於「可獨立運作的網站工廠」，不直接等同於原本課程招生大專案的 Chat A / B / D / E。

### Chat M-A：網站工廠核心 / Zip 匯出

Chat M-A 負責讓網站工廠能產出可部署 zip。

責任範圍：

- `factory/` 的核心目錄責任設計。
- block registry、template assembly、preview source、export scope。
- 將 Replit `build.py`、`export-zip.php`、`export-get.php` 的概念轉成新架構。
- 確保匯出結果符合 cPanel shared hosting、PHP 5.6、MySQL 5.7.44 限制。
- 定義 zip 內應包含與不應包含的檔案。
- 不負責課程招生資料欄位語意，不負責最終視覺選版，不負責驗收報告。

建議 prompt：

```text
你是 Chat M-A：網站工廠核心 / Zip 匯出。
請閱讀 docs/WEBSITE_FACTORY_MIGRATION.md 與 docs/WEBSITE_FACTORY_INVENTORY.md。
正式部署環境是外部 cPanel 主機：PHP 5.6、MySQL 5.7.44。
請根據 Replit sandbox 裡 build.py、export-zip.php、export-get.php 的盤點結果，設計可獨立運作的網站工廠核心與 zip 匯出架構。
不要處理課程招生資料欄位語意，也不要做 zip 驗收品管。
```

### Chat M-B：課程招生資料注入格式

Chat M-B 負責定義課程招生資料如何餵給網站工廠。

責任範圍：

- 從 `clients/massage-amy/data.json` 萃取「資料注入」概念，但轉成課程招生 domain。
- 定義課程、講師、受眾、痛點、成果、課綱、方案、FAQ、CTA、聯絡方式、圖片需求等欄位。
- 定義每個 block 需要的 data contract。
- 協調與 `docs/CLIENT_SELECTION_FLOW.md`、`docs/TEMPLATE_REFERENCE.md` 的關係。
- 輸出可給 Chat M-A 組頁的 JSON/schema 草案。
- 不負責 zip 打包實作，不負責驗收壓縮檔。

建議 prompt：

```text
你是 Chat M-B：課程招生資料注入格式。
請閱讀 docs/WEBSITE_FACTORY_MIGRATION.md、docs/WEBSITE_FACTORY_INVENTORY.md、docs/CLIENT_SELECTION_FLOW.md。
正式部署環境是外部 cPanel 主機：PHP 5.6、MySQL 5.7.44。
請把 Replit clients/massage-amy/data.json 的資料驅動概念，轉成課程招生網站可用的資料注入格式與 block data contract。
不要處理 zip 匯出實作，也不要做最終 zip 驗收。
```

### Chat M-C：Zip 驗收 / 品管

Chat M-C 負責驗收網站工廠輸出的 zip 是否可交付。

責任範圍：

- 檢查 zip 內容是否符合 export scope。
- 檢查是否誤包 `dist/`、巢狀 zip、未使用 vendor bundle、demo links、`localhost` references。
- 檢查 PHP 5.6 / MySQL 5.7.44 相容性風險。
- 檢查首頁是否可離線打開、路徑是否正確、圖片/字型/CSS/JS 是否缺漏。
- 檢查 cPanel 部署風險、檔案大小、影片資產、外部 CDN 依賴。
- 輸出驗收報告與退回修正清單。
- 不負責設計資料格式，不負責修改網站工廠核心。

建議 prompt：

```text
你是 Chat M-C：Zip 驗收 / 品管。
請閱讀 docs/WEBSITE_FACTORY_MIGRATION.md 與 docs/WEBSITE_FACTORY_INVENTORY.md。
正式部署環境是外部 cPanel 主機：PHP 5.6、MySQL 5.7.44。
請針對網站工廠輸出的 zip 設計驗收清單與品管流程，重點檢查 cPanel 可部署性、路徑完整性、PHP 5.6 相容性、檔案大小與不該被打包的內容。
不要修改網站工廠核心，也不要設計課程招生資料 schema。
```

### Chat M-D：樣式系統 / 模板視覺規格

Chat M-D 負責網站工廠可套用的樣式系統、模板視覺分類與風格規格。

責任範圍：

- 分析 Replit `themes/theme-soft.css`、`themes/theme-craft.css`、`themes/theme-edgy.css`。
- 分析 `template-001`、`template-002`、`template-003` 的視覺語言、section composition、hero 形式、色彩、字體、圖片比例與動態節奏。
- 定義網站工廠 style preset / theme preset / template family 的命名與欄位。
- 定義樣式如何被 Chat M-B 的資料注入格式引用，例如 `style_preset_id`、`template_family_id`、`hero_variant`。
- 定義樣式如何交給 Chat M-A 組頁與輸出，例如 CSS token、theme CSS、template assets。
- 協調與既有 `docs/TEMPLATE_REFERENCE.md`、`docs/STYLE_SYSTEM.md`、`styles/` 的關係，但不直接取代大專案 Chat A。
- 不負責 zip 匯出實作，不負責課程資料內容欄位，不負責最終 zip 驗收。

建議 prompt：

```text
你是 Chat M-D：樣式系統 / 模板視覺規格。
請閱讀 docs/WEBSITE_FACTORY_MIGRATION.md、docs/WEBSITE_FACTORY_INVENTORY.md、docs/STYLE_SYSTEM.md、docs/TEMPLATE_REFERENCE.md。
請分析 Replit sandbox 裡 themes/theme-soft.css、themes/theme-craft.css、themes/theme-edgy.css，以及 template-001/002/003 的視覺結構。
請產出網站工廠可獨立使用的 style preset / theme preset / template family 規格，並說明如何被 Chat M-B 的資料注入格式引用、如何交給 Chat M-A 組頁輸出。
不要處理 zip 匯出實作，也不要做 zip 驗收品管。
```

### 第一階段：Chat M，網站工廠

Chat M 正式命名為「網站工廠」。它負責 Replit website factory 的整體移植盤點與新系統工廠架構，不直接做大量重構。

任務：

- 解壓 zip 到獨立暫存目錄。
- 建立檔案與模組盤點。
- 判斷哪些是「可保留資產」、哪些是「舊 Replit runtime」、哪些是「需要重寫」。
- 產出 migration inventory。
- 決定新 repo 中應建立哪些目錄，例如 `factory/`、`public/`、`admin/`、`worker/`。

建議第一句 prompt：

```text
你是 Chat M：網站工廠。
請先閱讀 docs/PROJECT_CONTEXT.md、docs/PROJECT_STATUS.md、docs/COLLABORATION_SETUP.md、docs/WEBSITE_FACTORY_MIGRATION.md。
Replit zip 位於 /Users/phoebe/Documents/Codex/2026-05-28/website-factory-migration/templates-monorepo (5).zip。
請只做盤點與 migration inventory，不要直接覆蓋現有 templates/course-brand-template-v1。
正式部署環境是外部 cPanel 主機：PHP 5.6、MySQL 5.7.44。請用這個規格判斷可移植性，本機 PHP 8.4 / MySQL 8 只能當輔助檢查。
```

### 第二階段：Chat A，Style / Template Reference

Chat A 只處理美術與模板規則，不處理 PHP、後台或 worker。

任務：

- 從 Replit `template-001`、`template-002`、`template-003` 與 `themes/` 抽取可用的視覺分類。
- 判斷哪些模板可以轉成 `docs/TEMPLATE_REFERENCE.md` 的第 11 套以後樣板。
- 將可用風格轉成課程招生用 template reference，而不是原封不動搬舊網站模板。

建議 prompt：

```text
你是 Chat A：美術風格系統 / Template Reference。
請閱讀 docs/WEBSITE_FACTORY_MIGRATION.md 與 docs/TEMPLATE_REFERENCE.md。
你的任務是分析 Replit website factory 裡 template 與 themes 的視覺價值，提出哪些能轉成課程招生 template reference。
不要修改前端 HTML/CSS，也不要處理 PHP 或 worker。
```

### 第三階段：Chat B，Frontend Factory Adapter

Chat B 負責把可用的靜態區塊轉成目前 repo 可維護的前端結構。

任務：

- 將 `shared/blocks/` 中可用區塊轉成新前端規格。
- 判斷是否建立 `factory/blocks/`、`templates/` 或 `public/`。
- 避免把舊 CSS / JS vendor 包整包搬入。
- 只在 Chat A 已確認 template direction 後，才實作課程招生頁。

建議 prompt：

```text
你是 Chat B：Frontend Factory Adapter。
請閱讀 docs/WEBSITE_FACTORY_MIGRATION.md、docs/COLLABORATION_SETUP.md、docs/STYLE_SYSTEM.md。
請根據 Chat M：網站工廠的 migration inventory 與 Chat A 的 template direction，把 Replit shared blocks 轉成目前 repo 可維護的前端架構。
不要新增 SQL、webhook、admin 或 worker。
```

### 第四階段：Chat D，Data / Admin（大專案資料線）

Chat D 負責把 Replit 的 client data、booking form 與後台需求轉成正式資料結構。

任務：

- 分析 `clients/*/data.json`。
- 分析 `booking-form.php` 與 `template.php` 使用的欄位。
- 設計 `clients`、`course_projects`、`template_proposals`、`notification_logs` 等資料結構。
- 建立後台需求文件或實作草案。

建議 prompt：

```text
你是 Chat D：資料庫與後台。
請閱讀 docs/WEBSITE_FACTORY_MIGRATION.md、docs/PROJECT_STATUS.md。
請分析 Replit zip 裡 clients data 與 PHP 表單欄位，轉成正式資料模型與 admin 規格。
正式資料庫必須相容 MySQL 5.7.44，PHP 必須相容 5.6，部署目標是 cPanel shared hosting。
不要處理美術風格，也不要直接重構前端模板。
```

### 第五階段：Chat E，Automation / Export Worker（大專案自動化線）

Chat E 負責把 Replit 的 build/export 機制轉成 Codex 可維護的自動化流程。

任務：

- 分析 `build.py`、`export-get.php`、`export-zip.php`。
- 決定哪些輸出流程可保留，哪些要改寫。
- 設計產生預覽頁、匯出 zip、通知客戶、三天到期的 worker 流程。

建議 prompt：

```text
你是 Chat E：自動化與 AI Worker。
請閱讀 docs/WEBSITE_FACTORY_MIGRATION.md、docs/CLIENT_SELECTION_FLOW.md。
請分析 Replit zip 裡 build/export 的流程，提出 Codex repo 中可維護的 worker 與 export pipeline。
正式環境是 cPanel 主機，請優先設計 cron 可觸發的短任務，不要依賴長駐 worker、Node runtime 或 MySQL 8 功能。
不要自行新增新視覺模板，也不要修改 Chat A/B 的前端規則。
```

## 第一輪最小可行流程

第一輪不要五個 chat 同時開工。建議順序：

1. Chat M：網站工廠，解壓到 sandbox，做 migration inventory。
2. Chat M-D：定義樣式系統、theme preset、template family 與視覺規格。
3. Chat M-B：定義課程招生資料注入格式與 block data contract，並引用 M-D 的樣式欄位。
4. Chat M-A：建立網站工廠核心、組頁流程與 zip 匯出架構。
5. Chat M-C：建立 zip 驗收與品管流程。
6. 視需要再交給原本 Chat A / B / D / E，處理大專案美術規範、前端實作、資料庫或通知自動化。

## Sandbox 規則

建議 Chat M：網站工廠使用以下位置解壓：

```text
/Users/phoebe/Documents/Codex/2026-05-28/website-factory-migration/extracted/
```

不得直接解壓到目前 repo 根目錄。若要把內容搬入 repo，必須先建立明確目錄與 README，說明責任邊界。

## Repo 內可能新增的目錄

以下只是建議，需等 Chat M：網站工廠盤點後確認：

- `factory/`：網站工廠核心邏輯、block registry、template assembly。
- `factory/blocks/`：從 Replit `shared/blocks/` 整理出的可重用區塊。
- `factory/export/`：預覽與 zip 匯出流程。
- `public/`：公開靜態資源。
- `admin/`：後台管理介面。
- `worker/`：排程、提醒、通知、自動產生。

## 不要做的事

- 不要把 zip 直接解壓進 repo 根目錄。
- 不要把 Replit `dist/` 當成新系統原始碼。
- 不要把 `template-001/components/` 的 vendor CSS / JS 全部搬進新前端。
- 不要把 PHP 檔案直接當作未來正式 backend。
- 不要使用只支援 PHP 7 / PHP 8 或 MySQL 8 的語法與資料庫功能。
- 不要以本機 PHP 8.4.21、MySQL 8.0.46 測試通過，就判斷外部主機可部署。
- 不要讓 Chat B 在 Chat A 確認視覺方向前直接改招生頁。
- 不要把 migration 討論混進 `course-brand-template-v1` 的既有前端規則。

## 完成定義

本 migration 完成時，repo 應該至少具備：

- Replit zip 盤點文件，例如 `docs/WEBSITE_FACTORY_INVENTORY.md`。
- 清楚標註哪些舊模組被保留、改寫或淘汰。
- 新的 website factory 目錄責任說明。
- 可重用 block / template registry 的初版。
- client data 到正式資料模型的對照。
- preview / export pipeline 的初版。
- 與既有 Chat A/B/C/D/E 分工一致的後續工作入口。
- 與 Chat M-A / M-B / M-C / M-D 獨立分工一致的後續工作入口。
