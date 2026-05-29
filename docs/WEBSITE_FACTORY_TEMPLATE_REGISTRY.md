# Website Factory Template Registry

## 文件目的

本文件是網站工廠的樣板登記表，用來讓系統與各 chat 明確知道：

- 哪些 template 屬於同一個風格系列。
- 新增 template 時應登記在哪個 style family。
- Chat M-D 如何定義樣式規格。
- Chat M-B 如何在資料注入格式中引用樣式。
- Chat M-A 如何依登記表組頁與匯出。

目前這是文件版 registry。未來建立正式 `factory/` 後，可轉成：

```text
factory/template-registry.json
```

## 命名規則

### `style_family_id`

用來表示一組視覺風格家族。

格式：

```text
style-001
style-002
style-003
```

### `template_id`

用來表示可被網站工廠使用的樣板資料夾。

格式：

```text
template-001
template-002
template-003
template-004
template-005
template-006
```

### `template_status`

可用值：

- `active`：可正式被網站工廠使用。
- `draft`：正在製作或尚未驗收。
- `candidate`：已匯入但尚未決定是否使用。
- `deprecated`：不建議繼續使用。

## 目前樣板登記

### `style-001`：第一個風格

目前第一個風格包含：

| template_id | 狀態 | 備註 |
| --- | --- | --- |
| `template-001` | `active` | 既有 Replit website factory 樣板之一。 |
| `template-002` | `active` | 既有 Replit website factory 樣板之一，含影片 Hero。 |
| `template-003` | `active` | 既有 Replit website factory 樣板之一。 |

registry 表示：

```json
{
  "style_family_id": "style-001",
  "style_family_name": "第一個風格",
  "template_ids": ["template-001", "template-002", "template-003"],
  "status": "active"
}
```

### `style-002`：第二個風格

目前第二個風格預計包含：

| template_id | 狀態 | 備註 |
| --- | --- | --- |
| `template-004` | `draft` | 第二個風格的第一個樣板，尚待製作與驗收。 |
| `template-005` | `draft` | 第二個風格的第二個樣板，尚待製作與驗收。 |
| `template-006` | `draft` | 第二個風格的第三個樣板，尚待製作與驗收。 |

registry 表示：

```json
{
  "style_family_id": "style-002",
  "style_family_name": "第二個風格",
  "template_ids": ["template-004", "template-005", "template-006"],
  "status": "draft"
}
```

## 單一 Template 登記格式

每個 template 至少應記錄。以下以 `template-004` 為例：

```json
{
  "template_id": "template-004",
  "style_family_id": "style-002",
  "template_status": "draft",
  "source": "new-import",
  "roles": {
    "header": true,
    "hero": true,
    "about": true,
    "swiper": true,
    "gallery": true,
    "workflow": true,
    "feedback": true,
    "contact": true,
    "service": true,
    "child_themes": true,
    "footer": true,
    "loader": true
  },
  "notes": "template-004 屬於第二個風格，尚待 Chat M-E 拆解與 Chat M-D 視覺規格確認。template-005、template-006 也應使用同一個 style_family_id。"
}
```

## 與 Chat M-D 的關係

Chat M-D 負責維護本 registry 的樣式分類與視覺規格。

Chat M-D 需要定義：

- `style_family_id`
- `style_family_name`
- `template_id`
- 每個 template 的視覺特色。
- 每個 style family 可用的 theme preset。
- 每個 style family 的字體、色彩、圖片比例、Hero 結構與 section composition。
- `template-004`、`template-005`、`template-006` 是否正式屬於 `style-002`，以及何時從 `draft` 改為 `active`。

## 與 Chat M-E 的關係

Chat M-E 負責新網站拆解 / Block 萃取。

當新增 `style-002` 的 `template-004`、`template-005`、`template-006` 時，Chat M-E 應先拆出：

- Header 導覽列
- Hero 主視覺
- About / Novi Builder 關於我們
- Swiper 輪播廣告
- Gallery 作品集
- Workflow 作業流程
- Feedback 評價
- Contact 聯絡我們 / 預約
- Service 功能列表
- Child Themes 子視覺
- Footer 頁尾
- Loader 下載中

Chat M-E 拆解後交給 Chat M-D 判斷樣式分類，再交給 Chat M-A 接入工廠。

## 與 Chat M-B 的關係

Chat M-B 的資料注入格式應引用 registry，而不是靠人腦記憶。

範例：

```json
{
  "project_id": "course-project-001",
  "style_family_id": "style-002",
  "template_id": "template-004",
  "hero_variant": "default",
  "data": {}
}
```

如果資料指定：

```text
style_family_id = style-002
template_id = template-004
```

網站工廠就應知道：這個專案使用第二個風格的 `template-004`。

## 與 Chat M-A 的關係

Chat M-A 組頁與 zip 匯出時，必須查 registry：

1. 找到 `template_id`。
2. 確認 `style_family_id`。
3. 確認 `template_status` 是否可輸出。
4. 確認該 template 有哪些 block roles。
5. 依 M-D 樣式規格與 M-B data contract 組頁。

Chat M-A 不應只憑 `template-004`、`template-005`、`template-006` 的資料夾名稱判斷它們屬於哪個風格。

## 與 Chat M-C 的關係

Chat M-C 驗收 zip 時，應檢查：

- zip 中的 template 是否符合 registry。
- `style_family_id` 和 `template_id` 是否一致。
- draft template 是否被誤當成 active 正式輸出。
- 是否混入其他 style family 的資產。

## 交接指令

### 給 Chat M-D

```text
你是 Chat M-D：樣式系統 / 模板視覺規格。
請閱讀 docs/WEBSITE_FACTORY_TEMPLATE_REGISTRY.md、docs/WEBSITE_FACTORY_MIGRATION.md、docs/WEBSITE_FACTORY_INVENTORY.md。
目前 template-001、template-002、template-003 屬於 style-001「第一個風格」。
接下來要做的 template-004、template-005、template-006 屬於 style-002「第二個風格」，目前狀態是 draft。
請為 style-002 / template-004、template-005、template-006 定義視覺規格、樣式命名、theme preset、Hero / section composition 規則，並指出需要 Chat M-E 拆解哪些 HTML/CSS/JS。
不要做 zip 匯出，也不要做 zip 驗收。
```

### 給 Chat M-E

```text
你是 Chat M-E：新網站拆解 / Block 萃取。
請閱讀 docs/WEBSITE_FACTORY_TEMPLATE_REGISTRY.md、docs/WEBSITE_FACTORY_MIGRATION.md、docs/WEBSITE_FACTORY_INVENTORY.md。
我要新增 template-004、template-005、template-006，並且它們屬於 style-002「第二個風格」。
請專注拆解新網站的 HTML / CSS / JS，拆出 Header、Hero、About、Swiper、Gallery、Workflow、Feedback、Contact、Service、Child Themes、Footer、Loader。
請標註每個 block 的來源檔案、CSS 依賴、JS 依賴、圖片資產、可重用程度，以及需要交給 Chat M-D 判斷的樣式問題。
不要做 zip 匯出，不要設計資料 schema，不要做最終驗收。
```

### 給 Chat M-B

```text
你是 Chat M-B：課程招生資料注入格式。
請閱讀 docs/WEBSITE_FACTORY_TEMPLATE_REGISTRY.md、docs/WEBSITE_FACTORY_MIGRATION.md、docs/WEBSITE_FACTORY_INVENTORY.md。
請更新資料注入格式，讓每個課程專案可以指定 style_family_id 與 template_id。
目前 style-001 包含 template-001、template-002、template-003；style-002 包含 draft template-004、template-005、template-006。
請設計資料格式如何指定 style-002 / template-004、template-005、template-006，並定義 block data contract 如何與樣式 registry 對接。
不要做 zip 匯出，也不要做 zip 驗收。
```

### 給 Chat M-A

```text
你是 Chat M-A：網站工廠核心 / Zip 匯出。
請閱讀 docs/WEBSITE_FACTORY_TEMPLATE_REGISTRY.md、docs/WEBSITE_FACTORY_MIGRATION.md、docs/WEBSITE_FACTORY_INVENTORY.md。
請設計網站工廠組頁與 zip 匯出時如何查 template registry。
目前 style-001 包含 template-001、template-002、template-003；style-002 包含 draft template-004、template-005、template-006。
請確保系統不只靠資料夾名稱判斷 template 歸屬，而是使用 style_family_id / template_id / template_status。
不要設計課程資料語意，也不要做 zip 驗收。
```

### 給 Chat M-C

```text
你是 Chat M-C：Zip 驗收 / 品管。
請閱讀 docs/WEBSITE_FACTORY_TEMPLATE_REGISTRY.md、docs/WEBSITE_FACTORY_MIGRATION.md、docs/WEBSITE_FACTORY_INVENTORY.md。
請把 template registry 納入 zip 驗收清單。
驗收時要檢查 style_family_id、template_id、template_status 是否一致，並確認沒有混入其他 style family 的資產。
目前 style-002 / template-004、template-005、template-006 還是 draft，若被當作正式 active 輸出，需要列為風險。
不要修改網站工廠核心，也不要設計資料 schema。
```
