# Chat C Fix Request

## 文件目的

本文件整理 Chat E / QA 回歸測試後，需要交給 Chat C 修正的 LINE AI 客服流程與 Worker 行為問題。

## 本輪測試狀態

2026-05-27 重新檢查後：

- Worker 版本：`chat-c-receptionist-form-link-2026-05-27-20`
- 測試腳本：`tests/line-ai-worker-scenarios.test.mjs`
- 測試結果：pass 11 / fail 0
- 目前沒有需要 Chat C 立即修正的 open issue。

## 已確認的新版行為

- LINE AI 已改為「課程招生頁系統接待助理」。
- LINE AI 不再逐步收集姓名、Email、LINE ID Link、課程資料或照片素材。
- LINE AI 不再輸出 `client_intake_confirmed` JSON。
- LINE AI 不再呼叫 Admission API 建檔。
- LINE AI 會提供課程資料表連結。
- LINE AI 會說明免費試營運、製作流程、三款預覽與三天選擇期限。
- LINE AI 會防護 prompt / token / API / 後台等系統外指令。

## Chat C 後續若需修正

新增 issue 時請使用以下格式：

```md
### C-FIX-XXX

- issue_id：
- 問題描述：
- 出現在哪個流程階段：
- 目前錯誤行為：
- 期望行為：
- 建議修正話術或規則：
- 是否影響表單導向：
- 是否影響客戶體驗：
- 優先級：
```

## 交給 Chat D / Chat B 的問題

目前主要風險已不在 LINE AI Worker，而在表單與後端：

- 表單欄位與必填驗證尚未由 Chat D / Chat B 確認。
- 表單送出後是否寫入資料庫尚未確認。
- Email 通知、三款預覽網址與三天過期流程尚未確認。

## 交給 Chat E 的問題

- 繼續比對 Cloudflare 線上 Worker 版本與本地 `DEPLOY_VERSION`。
- 用 LINE App 實測四個接待入口。
- 等表單與後端完成後，補表單送出後自動化測試。
