# Style System

## 文件目的

本文件記錄「美術風格系統」的目前狀態、預期邊界與後續建立方向。

目前 repo 內已建立 `styles/` frontend-ready design foundation，作為品牌型課程頁的初始視覺規格。它定義 Hero、typography、spacing、motion、layout rules、section choreography，以及可供前端引用的 CSS token contract。

這些文件是 foundation；目前也已建立 `templates/course-brand-template-v1/` 靜態招生頁模板，用來把 foundation 套到實際前端頁面。尚未有真實課程品牌案例。

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
| course-brand-template-v1 | 已建立 `styles/course-brand-template-v1.json` foundation 與 `templates/course-brand-template-v1/` 靜態前端模板 |

## 預期目標

美術風格系統應讓 AI Agent 與人類協作者能穩定回答：

- 這個課程品牌看起來應該像什麼？
- 不同課程類型是否有不同風格？
- 招生頁、社群圖文、LINE 訊息、廣告素材是否共享同一套品牌規則？
- 當 AI 需要產生新素材時，應該選哪個 style？
- 當 template 重構時，哪些視覺 token 可以被保留或替換？

## 目前 `styles/` 結構

目前已建立：

```text
styles/
  README.md
  course-brand-template-v1.json
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

## 目前 `templates/` 結構

```text
templates/
  course-brand-template-v1/
    README.md
    index.html
    css/
      page.css
    js/
      page.js
    assets/
      course-workspace.svg
```

模板 CSS 會先 import `styles/tokens/course-brand.css`，再以頁面層級 alias 消費既有 `--cb-*` token，不另行建立與 foundation 斷開的風格系統。

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

## course-brand-template-v1 的重構方向

course-brand-template-v1 預期應成為課程品牌模板的第一個穩定版本。它應避免只是一組靜態頁面，而應將品牌、內容欄位與版型規則分離。

建議拆分：

- 品牌資料：課程名稱、品牌語氣、受眾、賣點。
- 視覺 token：顏色、字體、間距、圖片規則。
- 內容結構：hero、課程亮點、講師、課綱、FAQ、CTA。
- 輸出目標：招生頁、圖文、LINE 訊息、簡報或其他素材。

目前已有 `styles/course-brand-template-v1.json` 作為設計 foundation，並已建立 `templates/course-brand-template-v1/` 的實體靜態模板。此模板只處理 HTML、CSS、JS、responsive、Hero、section composition 與 animation，不包含 SQL、webhook、backend 或 form schema。

## 設計原則

- 先定義資料格式，再產生大量素材。
- 風格選擇應可追溯，不應只靠單次 prompt 印象。
- 視覺規則要能被人讀懂，也要能被 AI Agent 讀懂。
- 所有尚未存在的 style、skill、template 都必須標記為規劃中；已存在項目需同步標出實際檔案位置。

## 待補資料

- 第一個實際課程品牌案例。
- 風格命名規則。
- 正式圖片、攝影或生成圖資產流程。
- style-selector-skill 的實作位置與使用方式。
- course-brand-template-v1 的真實課程內容資料來源。
