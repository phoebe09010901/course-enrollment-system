# Style System

## 文件目的

本文件記錄「美術風格系統」的目前狀態、預期邊界與後續建立方向。

目前 repo 內尚未存在 `styles/` 目錄，也沒有可讀取的風格 token、品牌規範、圖片規範或模板設定。因此以下內容是初始化規格，不代表已完成的美術系統。

## 目前狀態

| 項目 | 狀態 |
| --- | --- |
| `styles/` | 尚未建立 |
| 風格 token | 尚未建立 |
| 色彩系統 | 尚未建立 |
| 字體系統 | 尚未建立 |
| 圖像風格 | 尚未建立 |
| 版面規則 | 尚未建立 |
| style-selector-skill | 尚未建立 |
| course-brand-template-v1 | 尚未建立 |

## 預期目標

美術風格系統應讓 AI Agent 與人類協作者能穩定回答：

- 這個課程品牌看起來應該像什麼？
- 不同課程類型是否有不同風格？
- 招生頁、社群圖文、LINE 訊息、廣告素材是否共享同一套品牌規則？
- 當 AI 需要產生新素材時，應該選哪個 style？
- 當 template 重構時，哪些視覺 token 可以被保留或替換？

## 建議的 `styles/` 結構

未來可考慮建立：

```text
styles/
  README.md
  style-index.json
  course-brand-template-v1.json
  palettes/
  typography/
  image-directions/
  layout-rules/
```

這只是建議結構，尚未實作。

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

目前模板尚未建立。

## 設計原則

- 先定義資料格式，再產生大量素材。
- 風格選擇應可追溯，不應只靠單次 prompt 印象。
- 視覺規則要能被人讀懂，也要能被 AI Agent 讀懂。
- 所有尚未存在的 style、skill、template 都必須標記為規劃中。

## 待補資料

- 第一個實際課程品牌案例。
- 第一組色彩與字體 token。
- 風格命名規則。
- 圖片與插畫方向。
- style-selector-skill 的實作位置與使用方式。
- course-brand-template-v1 的資料格式與範例。
