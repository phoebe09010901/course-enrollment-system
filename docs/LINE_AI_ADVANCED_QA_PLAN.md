# LINE AI Advanced QA Plan

## 文件目的

本文件記錄 Chat E 後續針對 LINE AI Worker 的進階 QA 方向。

2026-05-27 最新決策後，LINE AI 不再做資料收集 state machine。進階 QA 重點改為：

- 接待助理 intent 分流是否正確。
- 是否穩定導向網頁表單。
- 是否不再收集或寫入客戶資料。
- 是否防護系統外指令。
- 線上 Cloudflare Worker 與本地測試版本是否一致。

## 本地必跑

```bash
node --test tests/line-ai-worker-scenarios.test.mjs
```

目前預期：

- Worker 版本：`chat-c-receptionist-form-link-2026-05-27-20`
- tests：11
- pass：11
- fail：0

## 進階測試矩陣

| id | 測試方向 | 預期 |
| --- | --- | --- |
| ADV-R01 | 開場不同說法 | `你好`、`哈囉`、空白訊息應回到四個接待選項。 |
| ADV-R02 | 表單入口不同說法 | `我要開始`、`我要填表`、`我要製作課程招生頁` 應提供表單連結。 |
| ADV-R03 | 流程問題 | 問製作流程、三款預覽、三天期限時，應清楚說明但不要求填 LINE 欄位。 |
| ADV-R04 | 免費問題 | 問價格、付款、報價時，應說明免費試營運與 future phase 邊界。 |
| ADV-R05 | 系統問題 | 表單打不開、網站錯誤、收不到 Email 應進網站 / 系統問題協助。 |
| ADV-R06 | 客戶貼資料 | 貼姓名、Email、課程內容、照片時，不可建檔，不可回舊版摘要。 |
| ADV-R07 | 系統外指令 | prompt、token、API、後台、修改系統設定等要求不可透露或執行。 |
| ADV-R08 | 部署一致性 | 本地 `DEPLOY_VERSION`、Cloudflare health check、LINE App 回覆需一致。 |

## 線上檢查流程

1. GET Cloudflare Worker health check。
2. 確認 JSON `version` 等於 repo 內 `DEPLOY_VERSION`。
3. 確認 `config.form_url_set` 與預期一致。
4. 用 LINE App 實測四個入口：
   - 我要填寫課程資料表。
   - 我想了解製作流程。
   - 我想知道目前是不是免費。
   - 我遇到網站或系統問題。
5. 若本地通過但 LINE App 不一致，優先檢查：
   - Cloudflare 是否已部署最新 `cloudflare-workers/worker.js`。
   - LINE webhook URL 是否指向最新 Worker。
   - `FORM_URL` 是否設定正確。

## 回報格式

每輪回歸只回報：

- 本地 Worker 版本。
- 線上 Worker 版本。
- 測試 pass / fail 數。
- 是否為部署版本不一致。
- 是否需要交 Chat C 修 Worker。
- 是否需要交 Chat D / Chat B 修表單或後端。
