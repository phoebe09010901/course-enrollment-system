# LINE AI Advanced QA Plan

## 背景

真實 LINE 對話出現兩個新問題：

1. 客戶已單獨提供 Email，但後續回覆仍被判斷 Email 未填。
2. 客戶問「課程類型是指什麼我看不懂？」時，系統沒有正確解釋欄位，反而像是跳到下一欄或狀態錯亂。

這代表原本只測「單一回覆是否能解析」已不夠。後續要改測「多輪狀態保持、多意圖訊息、欄位說明、線上部署一致性」。

## 新檢測目標

- 驗證單獨 Email 寫入後不會在下一輪消失。
- 驗證空白表單不會清掉已填資料。
- 驗證空白欄位標籤不會被當成有效資料，例如 `LINE ID Link：` 不可被標記為 `need_review` 後通過 gate。
- 驗證客戶問欄位意思時，AI 會先解釋欄位，不會把問題句寫入資料。
- 驗證 contact 未完成時，不可跳到課程形式或照片階段。
- 驗證本地 worker 與線上 Cloudflare worker 版本一致。
- 驗證 LINE App 實際回覆與 webhook replay 結果一致。

## 檢測層級

### L1 本地 Worker 黑箱測試

用 `node --test tests/line-ai-worker-scenarios.test.mjs` 直接測 `cloudflare-workers/workers.js`。

必要檢查：

- LINE signature 驗證。
- LINE reply payload。
- Admission API 是否被呼叫。
- confirmed payload 欄位狀態。

### L2 真實事故重放測試

用截圖中的真實對話順序重放：

1. `我想開始`
2. 貼空白表單
3. `cat0704520@gmail.com`
4. `課程名稱 鄭阿貓色鉛筆一微笑的黃金獵犬\n課程類型 這是指什麼我看不懂？`
5. `實體`

預期：

- 第 3 步後 Email 應保存。
- 第 4 步若 contact 仍缺姓名 / LINE ID Link，應提醒補姓名與 LINE ID Link，但不可再說 Email 未填。
- 第 4 步不應跳到「實體、線上、混合」。
- 第 5 步不可被當作課程形式，因為 contact gate 尚未完成。

### L3 欄位說明測試

在 contact 已完整的前提下測：

1. 客戶提供課程名稱。
2. 客戶問「課程類型是什麼 / 怎麼填 / 看不懂」。

預期：

- 回覆課程類型定義：主題分類，不是正式課名。
- 提供例子：色鉛筆、畫畫、水彩、手作、花藝等。
- 若同一句有課程名稱，應保存課程名稱。
- 不得把「這是指什麼我看不懂」寫入 `course_type`。
- 下一輪客戶回 `課程類型：色鉛筆` 後，才進入課程形式。

### L4 多意圖訊息測試

測同一句同時包含：

- 欄位值 + 問題
- 多個欄位 + 一個缺漏
- 說明需求 + 無效文字
- 修改資料 + 確認詞

預期：

- 先保存明確欄位值。
- 對問題做說明。
- 不跳過缺漏 gate。
- 不因為一句話內有「確認」就建檔。

### L5 部署一致性測試

若本地測試通過但 LINE App 失敗，需檢查：

- Cloudflare Worker GET health check 的 `version`。
- LINE webhook URL 是否指向最新 worker。
- Cloudflare 環境變數是否一致。
- KV 是否殘留舊 state。
- LINE App 對話是否使用同一個 LINE userId 導致舊狀態干擾。

## 新增必測案例

| case_id | 名稱 | 風險 | 預期 |
| --- | --- | --- | --- |
| ADV-001 | 空白表單後單獨 Email | high | Email 保存，不再列為缺漏。 |
| ADV-002 | Email 後問課程類型 | high | 不丟 Email；若 contact 未完成，不跳課程階段。 |
| ADV-003 | contact 完成後問課程類型 | high | 解釋欄位，不污染 `course_type`。 |
| ADV-004 | 問完欄位後補答案 | medium | 補 `課程類型：色鉛筆` 後才進下一步。 |
| ADV-005 | contact 未完成時回答 `實體` | high | 不當作 course_format；仍要求補 contact。 |
| ADV-006 | 同一句有課名 + 欄位問題 | medium | 保存課名，回答問題，不跳階段。 |
| ADV-007 | 重新貼空白表單 | medium | 不清除已填 email/name/link。 |
| ADV-008 | 舊狀態污染 | high | 清除狀態後結果應一致；未清除時要能查看目前 step。 |
| ADV-009 | 空白 LINE ID Link label 污染 | high | `LINE ID Link：` 不可寫入 `line_id_link`，不可讓 LINE link gate 通過。 |
| ADV-010 | contact gate 未完成時回答課程形式 | high | `實體`、`線上`、`混合` 不可在缺姓名 / LINE ID Link 時寫入 `course_format` 或推進到課程流程。 |
| ADV-011 | Email 在 LINE ID 補問來回中消失 | high | 重複提供 Email 與 LINE 相關資訊時，已驗證 Email 不可被清空；短 LINE 代碼不可被當成姓名。 |
| ADV-012 | 欄位更新意圖被導到錯誤欄位 | high | 客戶說「我要更新 LINE ID Link」時，應進入 LINE ID Link 更新，不可改問 Email 或課程資料。 |

## 本輪新增檢測結果

2026-05-27 新增 `S14` 與 `S15` 後，曾抓到空白 label 污染問題。Chat C 已於 Worker 版本 `chat-c-blank-label-parser-fix-2026-05-27-06` 修正。

2026-05-27 新增 `S16` 後，曾抓到課程形式詞 `實體` 被誤推測為姓名。Chat C 已於 Worker 版本 `chat-c-contact-course-token-fix-2026-05-27-07` 修正。

最新本地測試結果：

- `S14` pass：貼空白表單後，空白 `LINE ID Link：` / `3. LINE ID Link：` 不會污染 `line_id_link`。
- `S15` pass：contact 完成後問「課程類型是什麼」可正確回覆欄位說明，且下一輪補 `課程類型：色鉛筆` 後才前進。
- `node --test tests/line-ai-worker-scenarios.test.mjs`：S01 到 S17 通過，S18 失敗。
- `S16` pass：contact gate 未完成時，客戶回 `實體` 不會污染 `user_name`。
- `S17` pass：短 LINE 代碼 `URZ8z2U` 不會污染 `user_name`，Email 在 LINE ID 補問來回中不會消失。
- `S18` fail：客戶回 `我要更新LINE ID Link` 時，系統沒有進入 LINE ID Link 更新流程，反而回到課程類型提問。

判斷：

- 原問題是 state machine / parser bug。
- 問題不在 Email regex，而在空白 label 與 `extractLineLink()` / `cleanLabeledValue()` 的解析防護不足。
- 空白 label 污染、課程形式詞污染、短 LINE 代碼污染目前已修正；S18 顯示欄位更新意圖尚未被 state machine 正確攔截，需交 Chat C 修正。

## 回報格式

每輪回歸只回報：

- 本地測試版本。
- 線上 worker 版本。
- 通過 / 失敗案例。
- 是否為部署版本不一致。
- 是否為 state machine bug。
- 是否要交 Chat C 修 worker。
- 是否要交 Chat E 補測試。
