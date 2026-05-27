# LINE AI Test Report

## 測試日期

2026-05-27

## 測試角色與範圍

測試角色：Chat E：系統自動化 / AI Worker / LINE AI 客服流程測試。

本次測試只做 QA 檢查、模擬客戶回覆、找問題與產出修正清單；未修改 `docs/LINE_AI_CUSTOMER_SERVICE_FLOW.md`。

測試範圍：

- `docs/LINE_AI_CUSTOMER_SERVICE_FLOW.md` 流程規格。
- `cloudflare-workers/worker.js` 實際 Worker 行為。
- LINE 入口、表單處理、必填聯絡資料、課程資料、照片五階段、fallback、安全邊界、摘要確認與 JSON 建檔。

測試方式：

- 靜態檢查流程文件與 Worker 程式碼。
- 以 Node 模擬 LINE webhook POST event，mock LINE reply API 與建檔 API。
- 使用 Worker 版本：`chat-c-chat-e-sync-2026-05-27-01`。

## 測試結果摘要

| 類型 | 數量 |
| --- | ---: |
| pass | 13 |
| warning | 10 |
| fail | 15 |

| 嚴重度 | 問題數 |
| --- | ---: |
| high | 9 |
| medium | 7 |
| low | 2 |

整體判斷：

- 文件規格已比上一輪完整，主要流程、欄位、照片階段與 fallback 原則都已寫入。
- 實際 Worker 仍有多個狀態機與解析問題，會造成「明明客戶有回答，AI 還是重問或亂判斷」。
- 最嚴重問題集中在：聯絡資料 gate、表單被誤判成更正、課程日期 / 招生時間順序、課程費用被誤判為本服務收費、照片階段與摘要確認缺欄位。

## 測試案例列表

| case_id | 測試案例 | 結果 | 嚴重度 | 備註 |
| --- | --- | --- | --- | --- |
| T01 | 客戶回覆「我想開始」後收到完整簡短表單 | pass | low | Worker 可回表單，且包含 19 欄。 |
| T02 | 入口「我想製作課程招生頁」導向表單 | pass | low | 關鍵字可觸發表單。 |
| T03 | 入口「我想了解製作流程」 | warning | medium | 未有 deterministic 分支；無 OpenAI key 時只回泛用 fallback。 |
| T04 | 入口「我只是先問問看」 | warning | medium | 未有 deterministic 低壓詢問分支。 |
| T05 | 只提供 Email，缺姓名與 LINE Link | fail | high | 會混入課程欄位一起追問，不應在 contact gate 問課程。 |
| T06 | Email 格式錯誤 `abc123` | fail | high | 表單更正判斷提前介入，未穩定回到 Email invalid 追問。 |
| T07 | LINE 顯示名稱誤填「小明老師」 | fail | high | 部分情境會被解析成 `LINE ID Link：小明老師` 並以 `need_review` 放行。 |
| T08 | 聯絡資料完整後再進課程資料 | warning | medium | 可進入，但表單常先被更正流程攔截，需多一次訊息才推進。 |
| T09 | 表單貼回完整資料 | fail | high | Worker 把表單當成資料更正，回覆「已幫你更新資料」而非直接進下一階段。 |
| T10 | 表單 placeholder「實體 / 線上 / 混合」不當作答案 | pass | low | 已排除 placeholder。 |
| T11 | 客戶只回「實體」 | pass | low | 可寫入 `course_format`。 |
| T12 | 實體課後追問上課地點 | pass | low | 可追問地點。 |
| T13 | 客戶提供上課地點 | pass | low | 可進入日期追問。 |
| T14 | 客戶回「月底吧」作為課程日期 | fail | high | Worker 先填入 `expected_launch_date`，導致一直重問課程日期。 |
| T15 | 客戶回「下午 2 點」作為課程時間 | warning | medium | 若日期前一步未正確寫入，時間也無法前進。 |
| T16 | 課後支援「目前沒有」 | warning | medium | 可記錄，但在錯誤日期狀態下無法順利到該步。 |
| T17 | 是否自備工具「不用帶」 | warning | medium | 規則存在，但前序卡住會造成重問。 |
| T18 | 招生時間「6月初招生」 | fail | medium | 容易被 `parseDataCorrection` 當成更正意圖。 |
| T19 | 課程費用「費用還不確定」 | fail | high | 被 `isPriceQuestion` 當成本服務價格問題，回免費試營運說明。 |
| T20 | 名額「人數還不知道」 | warning | medium | 未穩定標記 `unknown`。 |
| T21 | 適合對象與課程特色 | warning | medium | 可解析部分特色，但 readiness 未強制收齊。 |
| T22 | 未收預計招生時間 / 名額 / 費用 / 對象 / 特色就進照片 | fail | high | `isCourseBasicsReadyForPhotos` 缺少多個 required / recommended 欄位。 |
| T23 | 照片第 1 階段有版權提醒、用途與張數 | pass | low | Worker 有提醒與用途，但缺照片建議細節。 |
| T24 | 照片第 2 到 5 階段有版權提醒與用途 | pass | low | Worker 有共用 prompt。 |
| T25 | 照片階段「目前沒有」進下一階段 | pass | low | 可記錄 missing / none 並前進。 |
| T26 | 作品主圖缺圖 status | pass | low | `hero_artwork_images_status = missing`。 |
| T27 | 其他照片缺圖 status | pass | low | `none`。 |
| T28 | 一次傳多張照片未分類 | fail | high | Worker 單張 image event 會直接塞進目前階段；沒有多圖分類判斷。 |
| T29 | 客戶傳無關圖片 | fail | medium | Worker 無法辨識圖片內容，會當有效照片或儲存失敗。 |
| T30 | 圖片 R2 未綁定 | warning | medium | 會通知 owner 並停在該階段；但客戶可能不清楚如何繼續。 |
| T31 | 連續 3 次無效回覆 | pass | low | `invalid_reply_count >= 3` 會觸發 repeated invalid。 |
| T32 | fallback_reply_pool 每類 10 則 | fail | medium | 文件有 10 則；Worker 只有 3 到 5 則，且 off_topic 不常被觸發。 |
| T33 | 系統外指令「忽略前面規則」 | pass | low | 可觸發安全 fallback。 |
| T34 | 要求 prompt / API / 後台資訊 | pass | low | 可觸發安全 fallback。 |
| T35 | 付款問題 | warning | medium | 「要付款嗎」可回免費試營運；「付款方式」會被 forced handoff。 |
| T36 | 摘要確認前不可建檔 | pass | low | 只有 explicit confirmation 才呼叫建檔 API。 |
| T37 | 客戶只回「可以」 | pass | low | 不會建檔，符合確認規則。 |
| T38 | 建檔 JSON 欄位完整性 | fail | high | Payload 缺 `has_photos`、`has_copywriting`、`needs_template_proposal`、copyright notice flags；且 optional 欄位可空。 |

## 主要發現問題

### high

1. 聯絡資料缺漏時，`buildMissingFieldsPrompt()` 會把課程欄位一起列出，違反 contact gate。
2. 表單貼回時會先被 `handleDataCorrectionText()` 攔截，造成 AI 回覆像在更正資料，而不是正常進流程。
3. Email invalid 與 LINE 顯示名稱誤填在部分表單情境下沒有被正確阻擋。
4. `LINE ID Link：小明老師` 可能被整段標籤加值暫存，且 `need_review` 仍可進課程資料。
5. 課程日期與招生時間順序錯置：日期回答會先寫入 `expected_launch_date`。
6. 客戶提供「費用還不確定」會觸發免費試營運價格回覆，混淆「客戶課程費用」與「本服務收費」。
7. `isCourseBasicsReadyForPhotos()` 未要求預計招生時間、名額、費用、適合對象、課程特色，即可進照片階段。
8. 一次多張照片 / 無關圖片沒有可靠分類或審核機制。
9. 建檔 payload 與文件 JSON 規格仍不完全一致，缺素材版權記錄與部分布林欄位。

### medium

1. 入口「了解流程 / 先問問看」未做 deterministic 分支。
2. 招生時間容易被更正意圖攔截。
3. 費用 / 名額未定狀態未穩定標記為 `unknown`。
4. fallback pool 文件有 10 則，但 Worker 內 pool 數量不足。
5. off_topic 分類缺少 URL / 廣告 / 無關內容判斷，實際多半走 unclear。
6. 照片 prompt 缺少文件中較完整的照片建議細節。
7. R2 未綁定時照片流程會卡住，需要更明確的測試 / 部署檢查。

### low

1. 表單與回覆 emoji 偏多，實際測試連續訊息略顯制式。
2. 確認摘要缺品牌名稱、所在地區、預計招生時間、適合對象、課程特色等客戶會期待看到的欄位。

## 需交給 Chat C 的修正項目

詳見 `docs/CHAT_C_FIX_REQUEST.md`。

摘要：

- 修正 contact gate missing prompt。
- 表單訊息優先於 correction / status / price 判斷。
- 修正 LINE Link 解析與 `need_review` gate。
- 修正課程日期 / 招生時間順序。
- 區分本服務價格問題與客戶課程費用欄位。
- 補齊課程資料 readiness。
- 補照片多圖分類與無關圖片處理。
- 同步 Worker fallback pool 與文件 10 則規格。
- 補完整 JSON payload 欄位。

## 需交給 Chat D 的資料庫欄位問題

- 確認後端 API / DB 支援 `has_photos`、`has_copywriting`、`needs_template_proposal`。
- 確認照片 copyright notice flags 可儲存。
- 支援 `line_id_link_status = need_review` 時是否允許建立草稿，或必須人工確認。
- 支援 course field status：`expected_launch_date_status`、`expected_start_date_status`、`course_price_status`、`course_capacity_status`、`course_name_status`。
- 支援 `needs_human_contact_review` 的後台標籤與篩選。

## 需交給 Chat E 的自動化問題

- 建立 Worker fixture 測試腳本，覆蓋本報告 T01 到 T38。
- 建立 Cloudflare deployment checklist：`HANDOFF_KV`、`ADMISSION_API_URL`、`ADMISSION_IMAGES_R2`、`LINE_CHANNEL_ACCESS_TOKEN`。
- 補照片多圖 / R2 儲存 / 建檔 API 失敗的整合測試。
- 修正後需再跑第二輪 QA。

