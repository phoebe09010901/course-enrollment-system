# Client Selection Flow

## 文件目的

本文件定義 Chat G 半自動 Canva 提案流程中，A / B / C 三案的最小交接格式與選款規則。

它的主要用途是：

- 提供 worker 可檢查的 proposal 結構
- 讓 Chat A / Chat G / Chat D 對 proposal batch 有一致欄位
- 避免缺少 `proposal_code`、template metadata 或有效期時，案件仍被誤判為 ready

## 三案基本規則

1. 每個 project 一次只能有一組有效的 A / B / C proposal batch。
2. proposal 數量必須剛好是 3。
3. `proposal_code` 必須剛好是 `A`、`B`、`C`。
4. 每案都必須有真實 `canva_url` 才能進入 ready。
5. 每案都必須保存：
   - `primary_template_id`
   - `secondary_template_id`
   - `source_url`
   - `secondary_source_url`
6. 若任一案缺少上述欄位，整批不可 ready。

## Worker 可接受的輸出欄位

每一案至少應包含：

- `proposal_code`
- `proposal_name`
- `primary_template_id`
- `secondary_template_id`
- `source_url`
- `secondary_source_url`
- `visual_direction`
- `suitable_reason`
- `canva_url`
- `expires_at`

## Proposal Batch Example

```json
{
  "project_id": "CP-20260528-00001",
  "worker_run_id": "chat-g-local-20260528123000",
  "proposals": [
    {
      "proposal_code": "A",
      "proposal_name": "清爽藝術風",
      "primary_template_id": "TPL-001",
      "secondary_template_id": "TPL-004",
      "source_url": "https://example.com/template/clean-studio",
      "secondary_source_url": "https://example.com/template/warm-lifestyle",
      "visual_direction": "清爽留白、生活感照片、柔和 CTA",
      "suitable_reason": "適合作品與空間感都需要被看見的藝術課程",
      "canva_url": "https://www.canva.com/design/EXAMPLE-A",
      "expires_at": "2026-06-01T23:59:59+08:00"
    },
    {
      "proposal_code": "B",
      "proposal_name": "高級品牌風",
      "primary_template_id": "TPL-002",
      "secondary_template_id": "TPL-005",
      "source_url": "https://example.com/template/premium-brand",
      "secondary_source_url": "https://example.com/template/structured-info",
      "visual_direction": "品牌形象大圖、中央敘事、高留白",
      "suitable_reason": "適合需要品牌感與較高信任感的課程頁",
      "canva_url": "https://www.canva.com/design/EXAMPLE-B",
      "expires_at": "2026-06-01T23:59:59+08:00"
    },
    {
      "proposal_code": "C",
      "proposal_name": "作品展示風",
      "primary_template_id": "TPL-003",
      "secondary_template_id": "TPL-001",
      "source_url": "https://example.com/template/editorial-gallery",
      "secondary_source_url": "https://example.com/template/clean-studio",
      "visual_direction": "雜誌節奏、案例展示、圖像優先",
      "suitable_reason": "適合作品成果是報名關鍵的課程",
      "canva_url": "https://www.canva.com/design/EXAMPLE-C",
      "expires_at": "2026-06-01T23:59:59+08:00"
    }
  ]
}
```

## 半自動模式補充

目前正式 operating model 為 `semi_automated_canva_proposals`。

這代表：

- proposal plumbing、claim、callback 可以自動化
- 但若 Canva 工具無法 unattended 產出合格 proposal，worker 可以在 claim 後將案件標記為：
  - `manual_canva_required`
  - `canva_generation_unavailable`
  - `template_reference_missing`

此時不可偽造 `canva_url`，也不可用空資料湊 A / B / C。

## Ready Gate Summary

整批 proposal 只有在以下條件都成立時才能 ready：

1. proposal 剛好 3 筆
2. `proposal_code` 剛好為 A / B / C
3. 三案都有真實 `canva_url`
4. 三案都有完整 template/source metadata
5. 沒有第二批有效 proposal batch 與同一 `project_id` 衝突

## Client Selection Rule

客戶只能選其中一款，選定後：

- 被選案：`selected`
- 其他兩案：`not_selected` 或 `cancelled`

若三天內未選擇：

- A / B / C 全部標記為 `expired`

