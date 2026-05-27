# LINE AI Test Report

## 測試日期

2026-05-27

## 測試目的

本文件從 Chat E / AI Worker / QA 角度，檢查最新版 LINE AI Worker 是否符合「課程招生頁系統接待助理」定位。

本輪不再測舊版 LINE 逐步填表、照片收集、摘要確認或 JSON 建檔流程，因為這些責任已移到網頁表單與後端。

## 測試依據

已讀取並檢查：

- `docs/PROJECT_CONTEXT.md`
- `docs/PROJECT_STATUS.md`
- `docs/COLLABORATION_SETUP.md`
- `docs/LINE_AI_CUSTOMER_SERVICE_FLOW.md`
- `docs/LINE_AI_WORKER_TEST_SCENARIOS.md`
- `cloudflare-workers/workers.js`

## 測試方式

使用 Node 直接載入 `cloudflare-workers/workers.js`，模擬 LINE webhook POST、文字事件、圖片事件與 LINE reply API，並確認 Worker 不會呼叫舊版 Admission API。

執行方式：

```bash
node --test tests/line-ai-worker-scenarios.test.mjs
```

## 最近一次執行結果

- Worker 版本：`chat-c-receptionist-form-link-2026-05-27-20`
- tests：11
- pass：11
- fail：0

## 測試案例列表

| case_id | 測試案例 | 結果 |
| --- | --- | --- |
| R01 | GET health reports receptionist worker and FORM_URL status | pass |
| R02 | opening reply shows four receptionist options | pass |
| R03 | start form intent returns form URL placeholder and does not ask fields | pass |
| R04 | start form uses FORM_URL when configured | pass |
| R05 | process intent explains full flow and three-day preview window | pass |
| R06 | free trial intent says no upfront payment | pass |
| R07 | tech support intent asks issue category | pass |
| R08 | preview intent explains email previews and three-day expiry | pass |
| R09 | unsafe instruction is guarded and does not reveal internals | pass |
| R10 | customer data text does not trigger intake state or admission API | pass |
| R11 | non-text message is treated as support flow, not photo collection | pass |

## 結論

- LINE AI 已正確改為接待助理。
- 客戶貼資料時，不會觸發 LINE 內建檔或摘要確認。
- 圖片訊息不會進入舊版照片收集流程。
- 表單網址可使用預設值，也可由 Cloudflare `FORM_URL` 環境變數覆蓋。

## 後續風險

- 仍需確認表單頁本身的欄位、必填驗證與送出後資料庫寫入。
- 仍需確認 Cloudflare 線上部署版本與 LINE webhook 指向。
- 仍需測試表單送出後 Email 通知、三款預覽通知、三天選款期限與過期處理。
