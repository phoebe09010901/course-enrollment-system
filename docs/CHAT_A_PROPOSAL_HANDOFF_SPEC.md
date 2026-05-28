# Chat A Proposal Handoff Spec

## 文件目的

本文件定義案件送進「待樣板提案」前，必須提供給 Chat A 的最小資料規格。

若資料不足，Chat A 可以先做方向判斷，但不應把 proposal 標記為 `ready`，也不應捏造 Canva 設計連結或圖片素材。

## 流程位置

```text
客戶填寫課程資料表
↓
表單系統檢查必填欄位
↓
資料寫入資料庫
↓
整理 Chat A proposal handoff payload
↓
狀態進入：待樣板提案
↓
Chat A 選擇三款 template / 建立 proposal
```

## 重要分工

LINE AI 客服目前不再逐步收集客戶資料，也不直接產生 Chat A payload。

Chat C 目前負責：

- LINE AI 接待助理流程。
- 導向課程資料表。
- 說明免費試營運、流程、三天期限與系統問題狀態。

Chat A proposal handoff payload 應由表單送出後的後端 / 自動化流程整理，並交給 Chat A。

## 最小必要欄位

| 欄位 | 必要性 | 說明 |
| --- | --- | --- |
| `project_id` | 必填 | 課程專案 ID，例如 `PJT-20260528-001` |
| `course_name` | 必填 | 課程名稱 |
| `course_type` | 必填 | 課程類型，例如花藝課、水彩課、兒童美術、iPad 繪圖、銀飾手作 |
| `target_audience` | 必填 | 適合對象，例如成人初學者、親子、兒童、進階學員、品牌創作者 |
| `brand_mood` | 必填 | 品牌風格方向，例如清爽藝術感、高級品牌感、雜誌作品集感、溫柔療癒感 |
| `course_summary` | 必填 | 1 到 3 句，說明這門課在教什麼、賣點是什麼 |
| `cta_goal` | 必填 | 行動目標，例如立即報名、預約體驗、加入 LINE 詢問 |
| `hero_copy` | 必填 | 主視覺文字，至少包含主標、次標、CTA 文案 |
| `about_copy` | 必填 | 課程介紹短文，至少一段 |
| `gallery_materials` | 必填 | 作品展示要用哪些內容；若沒有實際圖，也需說明預計展示成品、上課過程、空間或講師 |
| `r2_images` | 條件必填 | 可直接存取的圖片 URL 陣列；若要建立 ready proposal，必須提供 |
| `image_rights_confirmed` | 必填 | 是否已確認圖片可用於招生頁提案 |
| `expires_at` | 必填 | 提案有效期限，供 Chat A 原樣寫回 |

## `r2_images` 規格

`r2_images` 是 Chat A 能否安全建立可預覽 proposal 的關鍵欄位。

每張圖片至少需包含：

| 欄位 | 說明 |
| --- | --- |
| `url` | 可直接存取的圖片 URL |
| `role` | 圖片用途，例如 `hero`、`gallery`、`teacher`、`space`、`activity` |
| `source` | 建議填寫來源，例如 `r2`、`form_upload` |
| `filename` | 原始檔名或儲存檔名 |
| `notes` | 選填，補充圖片內容 |

## 標準 payload 範例

```json
{
  "project_id": "PJT-20260528-001",
  "course_name": "輕水彩花卉入門班",
  "course_type": "水彩課",
  "target_audience": "成人初學者",
  "brand_mood": "清爽藝術感、日系留白、溫柔質感",
  "course_summary": "從基礎筆觸到花卉構圖，帶學員完成可展示的水彩作品。",
  "cta_goal": "立即報名",
  "hero_copy": {
    "headline": "把喜歡的花，畫成自己的第一張作品",
    "subheadline": "適合零基礎，從觀察、配色到完成練習一次帶你上手",
    "cta_label": "立即預約名額"
  },
  "about_copy": "課程以初學者也能安心跟上的節奏設計，重視作品完成度與上課體驗。",
  "gallery_materials": [
    "學員作品照",
    "上課過程照",
    "教室空間照"
  ],
  "r2_images": [
    {
      "url": "https://example.r2.dev/hero-01.jpg",
      "role": "hero",
      "source": "r2",
      "filename": "hero-01.jpg",
      "notes": "主視覺作品照"
    },
    {
      "url": "https://example.r2.dev/gallery-01.jpg",
      "role": "gallery",
      "source": "r2",
      "filename": "gallery-01.jpg",
      "notes": "作品展示照"
    }
  ],
  "image_rights_confirmed": true,
  "expires_at": "2026-05-31T23:59:59+08:00"
}
```

## Chat A 處理規則

### 圖片不足

如果沒有 `r2_images`，Chat A 仍可先選三組 `template_id` 方向，但不應自動建立正式 proposal 到 `ready`。

建議狀態：

```text
proposal_status = pending_assets
```

### Canva 連結不足

如果拿不到真實 Canva 設計連結，Chat A 必須保留 pending，不能捏造 `canva_url`。

建議狀態：

```text
proposal_status = pending_canva_url
```

### 圖片授權未確認

如果 `image_rights_confirmed !== true`，Chat A 不應建立 ready proposal。

建議狀態：

```text
proposal_status = pending_image_rights
```

## 進入 ready 的最低條件

Chat A 要把三款 proposal 標記為 `ready`，至少需滿足：

1. `project_id` 存在。
2. `course_name` 存在。
3. `course_type` 存在。
4. `target_audience` 存在。
5. `brand_mood` 存在。
6. `course_summary` 存在。
7. `cta_goal` 存在。
8. `hero_copy.headline`、`hero_copy.subheadline`、`hero_copy.cta_label` 存在。
9. `about_copy` 存在。
10. `gallery_materials` 至少一項。
11. `r2_images` 至少一張可直接存取圖片。
12. `image_rights_confirmed === true`。
13. `expires_at` 存在。
14. 每款 proposal 若需要 Canva 預覽，必須有真實 `canva_url`；不可捏造。

## 需交給 Chat D / Chat E 接續

Chat D 需要確認：

- 表單欄位是否足以產出本 payload。
- 後台資料庫是否有對應欄位。
- 圖片上傳後是否能產生可直接存取的 R2 URL。
- `image_rights_confirmed` 是否在表單中明確勾選。

Chat E 需要確認：

- 表單送出後是否能自動整理本 payload。
- payload 缺欄位時是否阻止進入 `ready`。
- `expires_at` 是否正確計算三天期限。
- Chat A 回寫 proposal 狀態時是否遵守 pending / ready 規則。
