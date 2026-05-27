# Form Schema

## 文件目的

本文件定義「課程招生 - 系統」未來表單資料的欄位系統與資料規則。它是初始化規格，不代表目前 repo 已經有表單引擎、資料庫 schema、admin 介面或驗證程式碼。

本文件要先回答三件事：

- 表單有哪些穩定欄位？
- 欄位之間有哪些資料規則？
- AI Agent 與未來系統應如何讀寫這些欄位？

## 目前狀態

| 項目 | 狀態 |
| --- | --- |
| 表單 schema 實作檔案 | 尚未建立 |
| 欄位 registry | 尚未建立 |
| validation 程式碼 | 尚未建立 |
| admin 表單編輯器 | 尚未建立 |
| 資料庫 schema | 尚未建立 |
| LINE webhook 寫入流程 | 尚未建立 |

## 核心原則

- 欄位定義應獨立於畫面版型，避免每個招生頁各自發明欄位。
- 表單 schema 應同時能被人讀懂、被 AI Agent 讀懂、被程式驗證。
- 資料規則應集中管理，不應散落在文案、模板或 webhook prompt 裡。
- 欄位 id 一旦用於資料儲存或外部整合，不應任意改名。
- 尚未有實作前，所有格式都應標示為建議規格。

## 建議目錄

未來可考慮建立：

```text
schemas/
  README.md
  field-registry.json
  forms/
    course-lead-v1.json
    course-application-v1.json
  rules/
    common-validation.json
    enrollment-rules.json
```

這只是建議結構，尚未實作。

## 欄位系統

欄位系統應提供穩定的欄位 registry，讓招生頁、LINE webhook、admin、worker 與素材模板能共用同一批欄位語意。

### 欄位定義建議欄位

每個欄位可以包含：

- `id`：穩定機器識別碼，例如 `studentName`。
- `label`：顯示名稱，例如 `學生姓名`。
- `description`：欄位用途與填寫說明。
- `type`：資料型別，例如 `text`、`email`、`phone`、`select`、`multiSelect`、`date`、`boolean`。
- `required`：是否必填。
- `source`：資料來源，例如 `user_input`、`system_generated`、`line_profile`、`admin_only`。
- `privacy`：資料敏感度，例如 `public`、`internal`、`personal`、`sensitive`。
- `rules`：套用的資料規則 id。
- `options`：選項型欄位的可選值。
- `defaultValue`：預設值。
- `adminVisible`：是否在 admin 介面顯示。
- `templateVisible`：是否可被素材模板引用。

### 建議共用欄位

| 欄位 id | 顯示名稱 | 型別 | 用途 |
| --- | --- | --- | --- |
| `leadId` | 名單 ID | `text` | 系統產生的招生名單識別碼 |
| `studentName` | 學生姓名 | `text` | 報名者或學員姓名 |
| `guardianName` | 家長姓名 | `text` | 適用於兒少課程或需家長聯絡的課程 |
| `phone` | 聯絡電話 | `phone` | 後續諮詢與通知 |
| `email` | 電子信箱 | `email` | 補充通知、收據或課程資料 |
| `lineUserId` | LINE 使用者 ID | `text` | LINE webhook 或 LINE OA 整合 |
| `courseId` | 課程 ID | `text` | 對應課程資料 |
| `courseName` | 課程名稱 | `text` | 人類可讀的課程名稱 |
| `preferredSession` | 偏好梯次 | `select` | 報名者想參加的班別或時段 |
| `leadSource` | 名單來源 | `select` | 廣告、社群、LINE、轉介紹等 |
| `status` | 名單狀態 | `select` | 新名單、已聯絡、已報名、已取消等 |
| `notes` | 備註 | `text` | admin 或顧問補充資訊 |
| `consentMarketing` | 行銷同意 | `boolean` | 是否同意接收後續行銷通知 |
| `createdAt` | 建立時間 | `date` | 系統建立資料時間 |
| `updatedAt` | 更新時間 | `date` | 系統最後更新資料時間 |

## 資料規則

資料規則應分為三層：

1. 欄位型別規則：例如 email 格式、電話格式、日期格式。
2. 表單情境規則：例如兒童課程必填家長姓名。
3. 流程狀態規則：例如已報名狀態必須有課程梯次與聯絡方式。

### 建議共用規則

| 規則 id | 說明 |
| --- | --- |
| `required` | 欄位不可為空 |
| `email_format` | 必須符合 email 格式 |
| `phone_tw_format` | 台灣電話或手機格式 |
| `line_user_id_format` | LINE 使用者 ID 格式 |
| `enum_value` | 值必須在選項清單內 |
| `date_iso` | 日期使用 ISO 格式 |
| `requires_guardian_for_minor` | 未成年或兒少課程需填家長資料 |
| `requires_contact_method` | 至少需要電話、email 或 LINE 其中一種聯絡方式 |
| `status_transition_valid` | 名單狀態只能依允許流程轉換 |

## 表單 schema 建議格式

招生表單 schema 應引用 field registry，而不是在每張表單重複定義欄位語意。

```json
{
  "id": "course-lead-v1",
  "name": "課程招生名單表單",
  "version": "1.0.0",
  "fields": [
    {
      "fieldId": "studentName",
      "required": true
    },
    {
      "fieldId": "phone",
      "required": true,
      "rules": ["phone_tw_format"]
    },
    {
      "fieldId": "preferredSession",
      "required": false
    }
  ],
  "formRules": ["requires_contact_method"],
  "outputs": ["admin", "line-webhook", "worker"]
}
```

## 與其他系統的關係

### `templates/`

模板應引用經過允許的欄位，例如課程名稱、梯次、CTA、聯絡方式。模板不應直接使用敏感個資欄位，除非輸出目標明確允許。

### `line-webhook/`

LINE webhook 未來應將使用者訊息轉換成 schema 欄位資料，並套用同一套資料規則。它不應只靠 prompt 自由寫入未定義欄位。

### `admin/`

admin 介面應根據 form schema 產生表單欄位、狀態篩選與資料檢視。欄位的隱私等級應影響顯示權限。

### `worker/`

worker 可根據資料規則處理補資料提醒、狀態轉換、名單分流與通知排程。

### `styles/`

style system 負責視覺語言；form schema 負責資料語意。兩者可以在模板產出時交會，但不應互相取代。

## 待補資料

- 第一個實際招生表單使用情境。
- 欄位命名規則是否使用 camelCase 或 snake_case。
- 台灣電話格式與海外電話格式是否都支援。
- 名單狀態流程。
- 個資保留、刪除與匯出規則。
- admin 權限模型。
- schema 版本管理與 migration 規則。
