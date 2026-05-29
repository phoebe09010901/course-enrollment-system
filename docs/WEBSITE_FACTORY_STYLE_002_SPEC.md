# Website Factory Style-002 Visual Spec

## 文件目的

本文件由 Chat M-D 維護，用來定義網站工廠 `style-002` 的樣式系統與模板視覺規格。

本文件只處理視覺呈現層：

- Hero / Header / Section composition / Footer 規則。
- template-004、template-005、template-006 的樣式差異。
- CSS / HTML 命名規則。
- 需要交給 Chat M-E 拆解的 block。
- 需要交給 Chat M-B 定義的資料欄位與內容格式。

本文件不處理 zip 匯出、不處理 zip 驗收、不覆蓋 `templates/course-brand-template-v1`。

正式部署環境仍以外部 cPanel 主機為準：PHP 5.6、MySQL 5.7.44。本文件不應引入需要 Node runtime、現代 PHP 或 MySQL 8 才能成立的視覺依賴。

## 目前狀態

依 `WEBSITE_FACTORY_TEMPLATE_REGISTRY.md`：

| style_family_id | template_id | 狀態 | 說明 |
| --- | --- | --- | --- |
| `style-002` | `template-004` | `draft` | 第二個風格第一款，尚待 M-E 拆解與 M-D 實作規格確認。 |
| `style-002` | `template-005` | `draft` | 第二個風格第二款，尚待 M-E 拆解與 M-D 實作規格確認。 |
| `style-002` | `template-006` | `draft` | 第二個風格第三款，尚待 M-E 拆解與 M-D 實作規格確認。 |

目前在本工作樹中尚未看到 `template-004`、`template-005`、`template-006` 實體資料夾。本文件先定義 draft 規格，不宣稱樣板已完成或可輸出。

## Style-002 風格定位

`style-002` 建議定位為「清透編輯式課程品牌風」。

核心語感：

- 清楚、精緻、留白大。
- 有課程招生頁的轉換效率，但不做強銷售壓迫感。
- 適合藝術課、手作課、品牌顧問課、生活風格課、個人工作室。
- 圖片與內容並重，不使用過度模板化的浮誇裝飾。

和 `style-001` 的差異：

- `style-001` 偏網站工廠展示與區塊組合，較像「功能展示型」。
- `style-002` 偏客戶可選的品牌型模板，重點是「課程/服務被包裝成一頁有質感的網站」。
- `style-002` 應減少大型 vendor template 的殘留感，使用更穩定的 block class contract。

## Style-002 共用視覺規則

### 色彩

共用色彩角色使用語意 token，不直接在 block 寫死用途：

- `--wf-s002-color-ink`：主文字。
- `--wf-s002-color-muted`：次文字。
- `--wf-s002-color-paper`：頁面底色。
- `--wf-s002-color-surface`：柔和區塊底。
- `--wf-s002-color-brand`：主 CTA / 重點。
- `--wf-s002-color-accent`：小標、標籤、局部提示。
- `--wf-s002-color-line`：分隔線。

建議三組 theme preset：

| theme_preset_id | 名稱 | 適合 | 視覺方向 |
| --- | --- | --- | --- |
| `s002-light-editorial` | 清透編輯 | 多數課程與個人品牌 | 米白、墨黑、低飽和品牌色。 |
| `s002-warm-studio` | 溫暖工作室 | 手作、花藝、生活風格 | 暖米、陶土、奶油色、木質感。 |
| `s002-quiet-premium` | 靜奢質感 | 高單價課程、顧問、藝術品牌 | 淡灰白、深墨、霧金或乾燥綠。 |

### 字體

沿用大專案字體方向：

- Heading：`Noto Serif TC` 優先。
- Body：`Noto Sans TC` 優先。
- 可選局部柔和字：`Zen Maru Gothic`，只用於小標或輔助情緒，不整頁濫用。

桌機字級不要過大：

- Hero H1：建議 44px 到 60px。
- Section title：建議 28px 到 38px。
- Body：16px 到 18px，行高 1.65 到 1.8。

### 圖片

圖片策略：

- 以真實課程情境、作品細節、桌面工作場景、講師形象為主。
- Hero 圖片必須有明確主體與可放文字的安全區。
- Gallery 圖片比例要穩定，避免同一區塊混用過多比例。

建議比例：

- Hero：桌機 16:10、4:3 或不規則遮罩；手機可改為 4:5 或隱藏裝飾圖。
- 作品：4:5 或 1:1。
- 講師 / 品牌形象：3:4。
- 橫幅 / CTA：16:9 或 21:9。

### 動態

以低調 reveal 為主：

- Hero：文字先進，圖片後進。
- Section：每個 section 最多一組 reveal，不要每個 paragraph 都動畫。
- 手機版減少位移與 parallax。
- 必須支援 `prefers-reduced-motion`。

## Template 視覺差異

### template-004：清爽左右分欄型

定位：

- 最穩定、最通用的 style-002 基礎款。
- 適合初次建立品牌頁、服務介紹頁、課程招生頁。

Hero：

- 左文右圖。
- 左側包含 eyebrow、H1、說明段、2 到 3 個信任指標、雙 CTA。
- 右側為一張大圖或圖片疊層，圖片可帶小標籤但不要做複雜 UI。
- 桌機文字安全區約 42% 到 48% 寬，圖片區 52% 到 58%。
- 768px 可維持雙欄或改成上下堆疊，依實際圖片主體判斷。
- 375px 優先文字在前，圖片可縮成單張 4:5 或移到 Hero 後半。

Header：

- 白底或半透明白底。
- Logo 左、nav 右，CTA 可放在 nav 最右。
- sticky 可選，但高度不要超過 72px。

Section composition：

- About：一段品牌說明 + 3 個重點。
- Service / Features：3 到 6 張輕卡片。
- Workflow：橫向步驟，手機改直向。
- Gallery：2 欄或 3 欄作品格。
- Feedback：單列 testimonial 或 2 欄卡片。
- FAQ：簡潔 accordion。

Footer：

- 淺色 footer。
- 左側品牌/說明，右側聯絡與社群。
- 不做過厚深色 footer。

### template-005：滿版形象故事型

定位：

- 情緒與品牌感最強。
- 適合攝影、花藝、生活風格、高單價體驗課、藝術品牌。

Hero：

- 大圖滿版或近滿版。
- 文字可置中偏左，必須落在圖片暗部或柔和遮罩安全區。
- H1 字數需短，副標不超過 2 行。
- CTA 放在文字群組下方，不漂浮在圖片主體上。
- 桌機可用滿版 Hero；768px 需檢查主體裁切；375px 可改為圖片上方 / 文字下方，避免文字蓋住臉或作品。

Header：

- 初始可透明，滾動後變實底。
- Nav 不做過多按鈕，避免壓住 Hero。

Section composition：

- Story：長圖 + 短文，營造品牌故事。
- Works：大圖穿插小圖，雜誌感。
- Course value：3 個核心價值，文字短。
- Instructor：講師照片 + 信任資歷。
- CTA：使用大圖或色塊，不新增獨立聯絡區塊。

Footer：

- 可使用深色或高級低飽和背景。
- 保留足夠留白，社群 icon 不搶主視覺。

### template-006：模組轉換型

定位：

- 最偏 conversion 與資訊整理。
- 適合課程方案、服務包、顧問方案、需要清楚比較與快速報名的頁面。

Hero：

- 左側主張 + 右側資訊卡 / 報名卡 / 課程摘要卡。
- 可包含日期、名額、適合對象、方案起價、CTA。
- 不要把右側卡片做成資料注入欄位樣貌；M-D 只定義視覺結構。
- 桌機雙欄，768px 改堆疊，375px 卡片移到主文後。

Header：

- 實底 header，CTA 明確。
- 適合保留「課程介紹 / 適合對象 / 課綱 / FAQ」錨點。

Section composition：

- Audience：痛點與適合對象。
- Outcome：學完可以做到什麼。
- Curriculum：課綱或模組列表。
- Pricing / Plan：方案卡，可 2 到 3 欄。
- FAQ：必備。
- Final CTA：與方案或報名卡相連。

Footer：

- 功能型 footer。
- 清楚放聯絡方式、LINE、營業資訊或品牌資訊。

## CSS / HTML 命名規則

### 前綴

網站工廠新增 class 應使用穩定前綴：

- `wf-`：Website Factory 共用。
- `wf-s002-`：style-002 共用樣式。
- `wf-t004-`、`wf-t005-`、`wf-t006-`：單一 template 專用樣式。

避免：

- 直接大量覆蓋 `.section`、`.container`、`.btn` 等 vendor class。
- 在不同 template 用同名 class 表示不同結構。
- 用 `style1`、`new-card`、`test-hero`、`template-box` 這類不穩定命名。

### Block root

每個 block root 建議：

```html
<section class="wf-block wf-block--hero wf-s002-hero wf-t004-hero" data-block="hero" data-template="template-004">
```

基本欄位：

- `data-block`：`header`、`hero`、`about`、`gallery`、`workflow`、`feedback`、`faq`、`contact`、`footer`。
- `data-template`：`template-004`、`template-005`、`template-006`。
- `data-style-family`：可放在 page root，例如 `style-002`。

### Element naming

使用 BEM-like 命名：

- `wf-s002-hero__content`
- `wf-s002-hero__visual`
- `wf-s002-hero__eyebrow`
- `wf-s002-hero__title`
- `wf-s002-hero__actions`
- `wf-s002-section__header`
- `wf-s002-card`

template-specific only：

- `wf-t005-hero__scrim`
- `wf-t006-plan-card`
- `wf-t004-gallery-grid`

### CSS tokens

Style-002 token 範例：

```css
:root {
  --wf-s002-color-ink: #1b1816;
  --wf-s002-color-muted: #6f6760;
  --wf-s002-color-paper: #fffaf4;
  --wf-s002-color-surface: #f4eee7;
  --wf-s002-color-brand: #8f5a46;
  --wf-s002-color-accent: #d6a86a;
  --wf-s002-radius-card: 12px;
  --wf-s002-section-y: clamp(64px, 9vw, 128px);
}
```

規則：

- `style-002` 可以有自己的 token，但不應破壞既有 `--cb-*` foundation。
- 未來若接入大專案課程模板，可做 token mapping，而不是直接覆蓋 `course-brand-template-v1`。
- Theme preset 只覆蓋 token，不應塞大量 layout CSS。

## 給 Chat M-E 的拆解清單

`template-004`、`template-005`、`template-006` 尚待 Chat M-E 新網站拆解 / Block 萃取。M-E 應提供每個 block 的來源 HTML、CSS、JS、圖片資產、可重用程度與風險。

必拆 block：

- Header：logo、nav、CTA、sticky/transparent 行為。
- Hero：主視覺結構、圖片/影片依賴、文字安全區、RWD 裁切規則。
- About：品牌故事、講師/服務介紹、圖片比例。
- Service / Feature：卡片結構、icon 或圖片依賴。
- Workflow：步驟數、橫向/直向規則。
- Gallery / Works：圖片比例、lightbox 或 slider 依賴。
- Feedback：評價卡、頭像、星等或文字結構。
- FAQ：accordion JS 依賴與無 JS fallback。
- CTA / Contact：LINE、表單、地圖、按鈕組。
- Child Themes：若保留，需明確是否是工廠可切換主題，不可混成正式內容。
- Footer：品牌、聯絡、社群、版權。
- Loader：是否保留；若只是舊模板 loader，建議淘汰或簡化。

M-E 需特別標註：

- 是否依賴大型 vendor bundle。
- 是否使用舊 TemplateMonster demo link。
- 是否有影片或高容量圖片。
- 是否有需要 PHP 才能顯示的片段。
- 是否適合轉成 `shared/blocks` 或應作為 template-specific block。

## 給 Chat M-B 的資料欄位 / 內容格式需求

以下不是 M-D 要定義的 schema，而是 M-D 需要 M-B 補齊的資料契約。

全頁必要欄位：

- `style_family_id`：例如 `style-002`。
- `template_id`：例如 `template-004`。
- `theme_preset_id`：例如 `s002-light-editorial`。
- `language`：預設 `zh-Hant`。
- `brand_name`、`site_title`、`site_description`。

Hero data：

- eyebrow / badge 文字。
- H1 主標。
- 副標或短說明。
- primary CTA 文字與連結。
- secondary CTA 文字與連結。
- Hero 指標：數量、label、輔助說明。
- Hero 圖片：用途、alt、裁切焦點、手機是否顯示。
- 若 template-006 使用右側資訊卡：卡片標題、摘要、日期/價格/名額等欄位需由 M-B 定義。

Section data：

- About：短版說明、長版說明、重點列表、圖片。
- Features / Services：項目標題、描述、icon/image、排序。
- Workflow：步驟標題、描述、排序。
- Gallery：圖片、caption、分類、alt、比例。
- Feedback：姓名/稱呼、內容、課程或服務、頭像是否需要。
- FAQ：問題、答案、排序。
- CTA：標題、描述、按鈕、備註。
- Footer：品牌資訊、聯絡方式、LINE URL、社群連結、營業資訊、版權。

M-B 需定義的格式問題：

- 圖片資料是否只存 URL，還是包含 focal point / crop ratio / alt。
- CTA link 是否允許外部連結、錨點、LINE、表單頁。
- Gallery 是否支援分類與排序。
- Feedback 是否要分「評價」與「成果案例」兩種內容。
- FAQ 是否允許富文字，或只允許純文字。
- template draft 是否可被專案選用，或必須 `active` 才可用。

## 與 Chat M-A 的銜接規則

M-A 接入工廠時，應讀取 registry，不得只靠資料夾名稱判斷。

建議組頁資料：

```json
{
  "style_family_id": "style-002",
  "template_id": "template-004",
  "template_status": "draft",
  "theme_preset_id": "s002-light-editorial",
  "blocks": ["header", "hero", "about", "service", "workflow", "gallery", "feedback", "faq", "footer"]
}
```

M-A 不應將 `template-004/005/006` 當成 active 匯出，直到 M-D 規格、M-E 拆解與 M-C 檢查流程都完成。

## 不做事項

- 不修改或覆蓋 `templates/course-brand-template-v1`。
- 不搬移整包 Replit template vendor components。
- 不設計 PHP zip 匯出。
- 不做 zip 驗收。
- 不把 `template-004/005/006` 寫成已完成。
- 不直接定義 M-B 的正式資料 schema，只列出 M-D 需要的資料契約。
