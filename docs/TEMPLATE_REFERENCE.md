# Template Reference

## 文件目的

本文件記錄三款招生頁預覽提案的命名、定位與通知用語參考。內容屬於規劃規格，目前 repo 尚未存在實際模板、頁面程式碼、預覽網址產生器或 style token。

## 三款樣板提案

| proposal_id | 客戶顯示名稱 | 定位 | 適合情境 |
| --- | --- | --- | --- |
| `proposal_a` | A 款｜清爽藝術風 | 乾淨、明亮、親切、有手作與教學溫度 | 藝術、手作、兒童、生活風格課程 |
| `proposal_b` | B 款｜高級品牌風 | 精緻、專業、沉穩、有高單價信任感 | 顧問、證照、專業培訓、高價課程 |
| `proposal_c` | C 款｜雜誌作品集風 | 視覺感強、故事性高、重作品展示 | 攝影、設計、花藝、美學、創作者課程 |

## 預覽資料欄位

三款預覽完成後，系統預期提供以下資料給 LINE 通知流程：

```json
{
  "client_id": "client_001",
  "course_id": "course_001",
  "proposal_batch_id": "proposal_batch_001",
  "expires_at": "2026-05-30T18:00:00+08:00",
  "proposals": [
    {
      "proposal_id": "proposal_a",
      "label": "A 款",
      "name": "清爽藝術風",
      "preview_url": "https://example.com/preview/a"
    },
    {
      "proposal_id": "proposal_b",
      "label": "B 款",
      "name": "高級品牌風",
      "preview_url": "https://example.com/preview/b"
    },
    {
      "proposal_id": "proposal_c",
      "label": "C 款",
      "name": "雜誌作品集風",
      "preview_url": "https://example.com/preview/c"
    }
  ]
}
```

## 重要規則

- 三款預覽網址應有明確失效時間。
- LINE 通知需同時寫入通知紀錄，避免重複發送或漏發。
- 客戶選款後，系統需記錄 `selected_proposal_id`。
- 若逾期未選，預覽網址應標記為 expired，不應繼續使用。
- 實際視覺 token 與模板檔案尚未建立，未來需由 Chat D 或 Chat E 接續。
