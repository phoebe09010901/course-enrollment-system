# Style System

## 文件目的

本文件記錄「美術風格系統」的目前狀態、預期邊界與後續建立方向。

目前 repo 內已建立 `styles/` frontend-ready design foundation，作為品牌型課程頁的初始視覺規格。它定義 Hero、typography、spacing、motion、layout rules、section choreography，以及可供前端引用的 CSS token contract。

這些文件是 foundation；先前建立的 v1/v2 靜態招生頁模板已依使用者要求刪除，不再作為可沿用設計基準。目前已有三個新的 sunshine-golden-pencil 視覺方向範例，但尚未定稿為正式模板。

## 目前狀態

| 項目 | 狀態 |
| --- | --- |
| `styles/` | 已建立 frontend-ready foundation |
| 風格 token | 已有 CSS custom properties、spacing、typography、motion 初始規格 |
| 色彩系統 | 尚未建立 |
| 字體系統 | 已有 hierarchy 規格，尚未選定實際字體 |
| 圖像風格 | 尚未建立 |
| 版面規則 | 已有 landing page layout rules |
| style-selector-skill | 尚未建立 |
| course landing examples | 已建立候選稿 | `alt-a`、`alt-b`、`alt-c`，尚未定稿 |

## 預期目標

美術風格系統應讓 AI Agent 與人類協作者能穩定回答：

- 這個課程品牌看起來應該像什麼？
- 不同課程類型是否有不同風格？
- 招生頁、社群圖文、LINE 訊息、廣告素材是否共享同一套品牌規則？
- 當 AI 需要產生新素材時，應該選哪個 style？
- 當 template 重構時，哪些視覺 token 可以被保留或替換？
- 當 Chat B 產生招生頁時，應該依據哪一個 `template_id` 進行版型設計？

## Template Reference System

`docs/TEMPLATE_REFERENCE.md` 是招生頁版型產生依據，不是一般靈感參考。

正式規則：

- 所有招生頁新樣板不再直接由 Codex 進入 HTML/CSS。
- 必須先由 Chat A 使用 Canva 製作視覺樣板草案，確認方向後，才交給 Chat B / Codex 前端實作。
- 目前主要問題是樣板美感不足，不是功能不足；未完成 Canva 視覺確認前，停止直接前端改版。
- 所有招生頁產生前，Chat B 必須先查詢 `docs/TEMPLATE_REFERENCE.md`。
- 所有招生頁產生前，Chat B 必須指定一個 primary `template_id`。
- 可選擇一個 `secondary_template_id` 作為輔助風格，但不得混用過多模板。
- 未指定 `template_id` 時，不應直接產生新版型或重構主要版面。
- 版型參考資料庫中的 10 份報告應作為 layout、hero、image strategy、typography、button、animation 與 avoid rules 的正式依據。
- `docs/STYLE_SYSTEM.md` 保存全域視覺規則；單一模板細節應放在 `docs/TEMPLATE_REFERENCE.md`。
- Chat A 製作 Canva 樣板前，也必須先指定 `primary_template_id` 與 `secondary_template_id`。
- 樣板設計應先經過 Canva 視覺確認，再交給 Chat B / Codex 進入前端實作。
- 新課程的三款樣板提案必須依 `docs/CLIENT_SELECTION_FLOW.md`，從 `docs/TEMPLATE_REFERENCE.md` 的 10 套樣板資料中挑選，不可憑空產生。

引用格式範例：

```text
本頁採用 TPL-001 LifeTime 作為主要版型依據。
secondary_template_id: TPL-005 Interior Design
```

## Typography / 字型系統

所有課程招生頁可以使用 Google Fonts，但字型系統必須由 Chat A 統一定義。Chat B 不可以讓每個頁面、每個模板或每個區塊自行亂選字型。

### 字型系統原則

- 所有招生頁以中文內容為主，不使用英文裝飾標題。
- 主要字型必須支援繁體中文。
- 字型風格需符合：精緻、舒服、清楚、藝術課程感、小工作室感、品牌感。
- 桌機版字級不可過大，需維持留白與閱讀舒適。
- Google Fonts 可以使用，但必須依照本節規範集中管理。
- 若使用外部 Google Fonts，Chat B 實作時需注意載入效能；未來正式商用可評估自託管字型。

### 指定字型角色

| 字型 | 角色 | 用途 | 風格 |
| --- | --- | --- | --- |
| `Noto Sans TC` | 主要內文字體 | 一般內文、表單、課程資訊、FAQ、按鈕文字 | 清楚、穩定、易讀 |
| `Noto Serif TC` | 主要標題字體 | Hero 主標、區塊標題、品牌感標題 | 精緻、藝術感、較有質感 |
| `Zen Maru Gothic` | 柔和輔助字體 | 兒童課程、手作課、療癒風格課程、小標或局部點綴 | 圓潤、親和、可愛但不要幼稚 |

### 課程類型對應

#### 預設版型

- heading font: `Noto Serif TC`
- body font: `Noto Sans TC`

#### 手作 / 兒童 / 可愛風課程

- heading font: `Zen Maru Gothic` 或 `Noto Serif TC`
- body font: `Noto Sans TC`
- `Zen Maru Gothic` 應用於局部標題、小標、溫柔提示或較輕鬆的課程氛圍，不應整頁過度可愛化。

#### 高級藝術 / 畫室 / 品牌感課程

- heading font: `Noto Serif TC`
- body font: `Noto Sans TC`
- 以字重、留白、圖片比例和版面節奏建立質感，不靠過大字級製造氣勢。

### 不允許

- 使用過度可愛、過度卡通、過度科技、過度商業簡報感的字型。
- 每個區塊混用太多字型。
- 使用不支援繁體中文的字型作為主要字體。
- 用英文 Google Font 做英文裝飾標題。
- 因為換字型就把桌機版標題放大。

### Chat B 實作規則

- 實作招生頁前，必須先查看本節 Typography 規範。
- 每個招生頁最多使用 2 組主要字型；必要時最多 3 組。
- Google Fonts 載入要集中在共用 CSS 或 layout，不要每個區塊重複引入。
- 字級、行高、字重需要跟 `STYLE_SYSTEM.md` 的全域 typography 規則一致。
- 若沿用 `styles/tokens/course-brand.css`，應透過 `--cb-font-display` 與 `--cb-font-sans` 等 token 管理字型，不要在區塊內寫死新的 font-family。
- 不要因為換字型就把桌機版標題放大；桌機字級仍需遵守精緻、穩重、留白感原則。

## 目前 `styles/` 結構

目前已建立：

```text
styles/
  README.md
  tokens/
    course-brand.css
  layout-rules/
    landing-page.md
  typography/
    course-brand.md
  motion/
    animation-style.md
```

尚未建立：

- `styles/style-index.json`
- `styles/palettes/`
- `styles/image-directions/`

## 目前 `templates/` 狀態

目前有三個候選視覺方向稿：

```text
templates/
  sunshine-golden-pencil-alt-a/
  sunshine-golden-pencil-alt-b/
  sunshine-golden-pencil-alt-c/
```

這些範例都應先視為方向稿，等使用者確認後再整理成正式可沿用模板。未來若重建或定稿模板，CSS 應先 import `styles/tokens/course-brand.css`，再以頁面層級 alias 消費既有 `--cb-*` token，不另行建立與 foundation 斷開的風格系統。

目前另有客戶選版用 preview prototype：

```text
previews/
  sunshine-golden-pencil/
    index.html
    proposal.html
    proposals.json
```

preview prototype 不等於正式模板；它用於呈現 `template_proposals` 的 A/B/C 預覽、逾期狀態與選定狀態。

## 建議的 style 定義欄位

未來每個 style 可以包含：

- `id`：穩定識別碼。
- `name`：人類可讀名稱。
- `audience`：目標受眾。
- `tone`：品牌語氣。
- `palette`：主色、輔色、背景色、警示色。
- `typography`：字體、字級層級、行高。
- `layout`：卡片、區塊、留白、格線規則。
- `imagery`：圖片、插畫或生成圖方向。
- `avoid`：禁止或避免使用的視覺元素。
- `useCases`：招生頁、社群貼文、LINE 訊息、簡報、廣告等用途。

## style-selector-skill 的預期角色

style-selector-skill 應該是一個讀取 style 定義並協助選擇風格的 AI skill。它不應自行發明品牌規則，而應根據 repo 內已有資料做出選擇。

建議輸入：

- 課程主題
- 受眾
- 素材用途
- 品牌限制
- 既有 style id

建議輸出：

- 選中的 style id
- 選擇理由
- 可使用的設計 token
- 不應使用的元素
- 若資料不足，應明確列出缺口

目前 skill 尚未建立。

## 未來課程模板的重構方向

未來課程品牌模板應避免只是一組靜態頁面，而應將品牌、內容欄位與版型規則分離。

建議拆分：

- 品牌資料：課程名稱、品牌語氣、受眾、賣點。
- 視覺 token：顏色、字體、間距、圖片規則。
- 內容結構：hero、課程亮點、講師、課綱、FAQ、CTA。
- 輸出目標：招生頁、圖文、LINE 訊息、簡報或其他素材。

目前已有三個候選視覺方向稿，尚未選定正式模板。已刪除的 v1/v2 不應被視為可沿用方向。

## course-brand-template-v1 全域設計規則

以下規則適用於所有品牌型課程招生頁，不只針對單一頁面。Chat A 維護這些設計規範；Chat B 進行前端重構時必須遵守。

### 中文優先，不使用英文裝飾標題

所有招生頁版型都不要使用英文大字作為背景字、裝飾字或區塊氛圍字。

禁止作為裝飾排版使用的英文包含但不限於：

- `WORKSHOP`
- `COURSE`
- `ART CLASS`
- `GALLERY`
- `ABOUT`
- `OFFER`
- `REGISTER`

正式規則：

- 主標、副標、小標、區塊標題都以中文為主。
- 不使用大型英文單字作為背景水印、裝飾標題、hero 氛圍字或 section label。
- 若需要裝飾文字，優先使用中文短句。
- 若中文短句仍造成干擾，改用抽象圖形、線條、留白、圖片節奏或色塊建立視覺層次。
- 英文只能在必要內容中出現，例如品牌名稱、專有名詞、課程原文名稱或真實素材內容，不應作為純裝飾。

### 桌機版字級下修，走精緻穩重路線

course-brand-template-v1 的桌機版 typography 不走巨大標題風格。整體比例應更精緻、穩重、有留白感，避免標題壓迫畫面。

桌機版建議字級方向：

| 角色 | 桌機建議 | 使用規則 |
| --- | --- | --- |
| Hero 主標 | `44-60px` | 可以大，但不可佔滿畫面；避免超過 3 行造成閱讀壓力 |
| Hero 副標 / lead | `18-22px` | 用來承接價值主張，不搶主標 |
| 區塊標題 | `28-38px` | 建立清楚段落，不與 Hero 主標競爭 |
| 卡片 / 模組標題 | `20-26px` | 支撐掃讀與資訊分組 |
| 內文 | `16-18px` | 以可讀性為主，行高保持舒展 |
| 表單文字 | `15-17px` | 清楚、安定，不放大成銷售標語 |
| caption / label | `12-14px` | 只做導覽與輔助資訊 |

正式規則：

- Hero 主標可以是全頁最大字級，但不能讓首屏只剩巨大文字。
- 區塊標題、卡片標題、內文、表單文字必須有明確層級，但整體字級要克制。
- 桌機版以留白、構圖、圖片比例、section rhythm 建立品牌重量，不依賴超大字體。
- 若某段內容需要提高權重，優先使用位置、留白、圖片、色塊或對比，不直接放大字級。
- 前端實作若沿用 `styles/tokens/course-brand.css`，應將 display 與 section title token 下修到上述範圍。

### 不新增獨立「聯絡我們」區塊

品牌型課程招生頁不需要額外新增 `Contact`、`聯絡我們`、`快速諮詢` 等獨立區塊。

正式規則：

- 招生頁已有報名表、CTA、FAQ、Footer 即可完成行動與疑問處理。
- 不新增獨立聯絡區塊，以免稀釋報名主線。
- 若需要聯絡資訊，整合在課程資訊、報名 CTA、FAQ 或 Footer 中。
- Footer 可以保留基本資訊，但不要設計成另一個大型聯絡區。
- 前端重構時若看到獨立 Contact section，應移除或合併到既有 CTA / FAQ / Footer，而不是美化它。

### 產生版型前必須指定 template_id

所有招生頁版型都必須從 `docs/TEMPLATE_REFERENCE.md` 選定正式依據。

正式規則：

- 必須指定 `template_id`，例如 `TPL-001 LifeTime`。
- 可以指定 `secondary_template_id` 作為輔助風格，但只能補充局部節奏、圖片策略或氛圍。
- 不可以未查詢 `docs/TEMPLATE_REFERENCE.md` 就自行發明新版型。
- 不可以把 10 份報告當成「靈感」後自由發揮；它們是版型產生依據。
- 若使用者未指定 template，Chat B 應先根據課程類型與產業從 `docs/TEMPLATE_REFERENCE.md` 提出建議，而不是直接開做。

### Canva 樣板到前端的設計順序

正式課程頁設計應先完成 Canva 視覺方向，再進入前端實作。

正式規則：

- 新招生頁樣板禁止跳過 Canva 直接做 HTML/CSS。
- Chat A 使用 Canva 製作樣板前，必須指定 `primary_template_id`、`secondary_template_id`、`source_url`、`secondary_source_url`。
- Canva 樣板應根據 primary template 決定 Hero 架構、主要排版節奏、頁面氣質與圖片使用方式。
- Canva 樣板可根據 secondary template 補強作品展示、色彩比例、CTA 形式、圖片裁切與留白方式。
- Chat B / Codex 只能依照 Chat A 輸出的 Canva 樣板與 template id 進行前端，不可自行跳回舊版 sunshine-golden-pencil 的視覺邏輯。
- 若 Canva 樣板被選為正式方向，需回寫 `docs/PROJECT_STATUS.md`，標記 `selected_template_id`、`selected_secondary_template_id`、`selected_canva_direction`、適用課程類型，以及是否可納入未來共用樣板。
- 三款樣板提案流程必須保留內部 template id 與 source URL；客戶只需看到簡化後的方向名稱。

### 全域版型美感優先級

- 所有版型不要出現英文裝飾標題。
- 所有主標、副標、區塊標題以中文為主。
- 桌機版字級不要過大。
- 不要額外新增「聯絡我們」獨立區塊。
- 不要資訊塞滿。
- 圖片要大，留白要夠。
- 動畫不是第一優先，版型美感才是第一優先。
- 使用 Google Fonts 時，需遵守統一 Typography 規範。
- 每個頁面最多使用 2 組主要字型，必要時最多 3 組。
- 樣板設計先經過 Canva 視覺確認，再交給 Chat B / Codex 進入前端實作。

## 設計原則

- 先定義資料格式，再產生大量素材。
- 風格選擇應可追溯，不應只靠單次 prompt 印象。
- 視覺規則要能被人讀懂，也要能被 AI Agent 讀懂。
- 所有尚未存在的 style、skill、template 都必須標記為規劃中；已存在項目需同步標出實際檔案位置。
- 課程招生頁的可見 UI 預設使用中文，不使用 About、Offer、Gallery、Register、Contact Us 等英文 section label。
- 課程招生頁不額外新增 contact us 區塊；轉換終點應回到既有報名 CTA 與報名表單。
- 桌機版 section 大標題必須節制，不能放大到與 Hero 主標題互相競爭。
- 未來 template 修改應先保留既有課程內容、圖片資料、報名欄位與既有功能，再調整視覺層、排版、CSS 與動畫。

## 待補資料

- 第一個實際課程品牌案例。
- 風格命名規則。
- 正式圖片、攝影或生成圖資產流程。
- style-selector-skill 的實作位置與使用方式。
- 新模板的真實課程內容資料來源。
