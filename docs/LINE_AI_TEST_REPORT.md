# LINE AI Test Report

## 測試日期

2026-05-27

## 測試目的

本文件從 Chat E / AI Worker / QA 測試角度，檢查 LINE AI 客服在課程招生頁表單與資料收集流程中的完整性、錯誤處理、JSON 整理能力與建檔安全條件。

本次不是修改客服話術，也未修改 `docs/LINE_AI_CUSTOMER_SERVICE_FLOW.md` 或 `cloudflare-workers/workers.js`。

## 測試依據

已讀取並檢查：

- `docs/PROJECT_CONTEXT.md`
- `docs/PROJECT_STATUS.md`
- `docs/STYLE_SYSTEM.md`
- `docs/COLLABORATION_SETUP.md`
- `docs/LINE_AI_CUSTOMER_SERVICE_FLOW.md`
- `cloudflare-workers/workers.js`

仍缺少：

- `docs/TEMPLATE_REFERENCE.md`
- `docs/CLIENT_SELECTION_FLOW.md`

因此本輪可以測 LINE AI 客服收件與資料收集流程，但無法測三款 template 來源或客戶選款前台流程。

## 測試方式

使用 Node 直接載入 `cloudflare-workers/workers.js`，模擬 LINE webhook POST、LINE 簽章、不同 userId、文字與圖片事件，並攔截 LINE reply 內容。另用 mock `ADMISSION_API_URL` 驗證確認後 payload。

本輪是 Chat C 修正後的回歸測試，優先回歸：

- 入口 2 / 3 分流。
- 初次未知訊息顯示開場三選項。
- 單獨姓名。
- Email-only 不誤判 LINE ID Link。
- 課程形式 unknown。
- 完整流程到 confirmed JSON。

## 自動化回歸測試

已新增可重跑測試：

- `docs/LINE_AI_WORKER_TEST_SCENARIOS.md`
- `tests/line-ai-worker-scenarios.test.mjs`

執行方式：

```bash
node --test tests/line-ai-worker-scenarios.test.mjs
```

最近一次執行結果：

- tests：17
- pass：16
- fail：1

本腳本專門測 LINE AI Worker 的 state machine、建檔 gate、欄位污染防護、照片階段與 confirmed payload。

新增回歸案例：

- `S14 真實 LINE 事故重放：Email 已給後不可再被判未填`
- 結果：pass
- 已驗證：貼空白表單後，空白 `LINE ID Link：` 或 `3. LINE ID Link：` 不會污染 `line_id_link`。
- Worker 版本：`chat-c-short-line-code-fix-2026-05-27-08`
- `S16 contact gate 未完成時不可收課程形式`：pass
- `S17 Email 在 LINE ID 補問來回中不可消失`：pass
- 新增 `S18 明確要求更新 LINE ID Link 時不可改問 Email`：fail
- 發現：客戶回 `我要更新LINE ID Link` 時，系統沒有進入 LINE ID Link 更新流程，反而回到課程類型提問。

## 測試結果摘要

| 等級 | 數量 |
| --- | ---: |
| high | 1 |
| medium | 6 |
| low | 2 |

測試案例結果：

- pass：21
- warning：7
- fail：2

整體判斷：

- Chat C 前輪 high 風險問題大多已修正。
- 入口分流、單獨姓名、Email-only、照片五階段、摘要確認、confirmed payload 目前可通過。
- 仍不建議完全放行，因為「確認」在 contact 階段可能被誤收為姓名，會造成資料污染。

## 測試案例列表

| case_id | 測試案例 | 結果 | 嚴重度 | 實測結論 |
| --- | --- | --- | --- | --- |
| T01 | 新客戶初次未知訊息顯示三個入口選項 | pass | high | 回覆開場三選項，已修正。 |
| T02 | 客戶選「我想製作課程招生頁」 | pass | low | 直接回覆表單，能進入必填資料收集。 |
| T03 | 客戶選「我想了解製作流程」 | pass | high | 正確顯示五步流程並引導「我想開始」。 |
| T04 | 客戶選「我只是先問問看」 | pass | medium | 正確低壓詢問，保留開始入口。 |
| T05 | 客戶回覆「我想開始」後先收姓名、Email、LINE ID Link | pass | low | 表單包含三項必填資料與用途說明。 |
| T06 | 必填資料完整前不進入課程資料收集 | pass | high | 單獨姓名與 Email-only 都不跳階段。 |
| T07 | 客戶只給姓名 | pass | high | `小美` 可被保存，補問 Email 與 LINE ID Link。 |
| T08 | 客戶只給 Email | pass | high | 只補問姓名與 LINE ID Link，未再誤判成 LINE link。 |
| T09 | 錯誤 Email `abc123` | pass | high | 正確回覆 Email 格式不完整，要求重新提供。 |
| T10 | LINE 顯示名稱不是 LINE Link | pass | high | 正確要求補 LINE 個人連結或可聯繫 link。 |
| T11 | 客戶一直不提供 Email | warning | medium | 不會進下一階段，但缺少 `declined` / 人工接手策略。 |
| T12 | 課程名稱 + 類型基本收集 | pass | low | `畫畫課` 可推進到課程形式階段。 |
| T13 | 只說「畫畫課」沒有正式課名 | warning | medium | 可推進，但仍可能把泛稱同時當 course_name / course_type。 |
| T14 | 課程形式說「還沒確定」 | warning | medium | 可暫存並前進，但回覆混入 unclear fallback 文案，體驗不自然。 |
| T15 | 日期模糊如「月底吧」 | warning | medium | 可保存 raw text，但沒有 date status / confidence。 |
| T16 | 費用不確定 | pass | medium | 可存「費用還不確定」，並有 `course_price_status = unknown`。 |
| T17 | 人數不確定 | pass | medium | 可存「人數還不確定」，並有 `course_capacity_status = unknown`。 |
| T18 | 五階段照片素材流程 | pass | low | 作品主圖、細節圖、老師、教室、上課過程依序出現。 |
| T19 | 每個照片階段有版權提醒、用途、建議張數、允許目前沒有 | pass | medium | 實測每階段都有提醒與可跳過語句。 |
| T20 | 客戶沒有各階段照片 | pass | medium | 會記錄 missing / none 並進下一階段。 |
| T21 | 一次傳很多照片但沒有分類 | pass | medium | 在照片階段可進入 `need_review` 並要求分類。 |
| T22 | 客戶傳無關圖片 | warning | low | 圖片事件內容仍無法自動判斷是否無關，需人工或圖像審核。 |
| T23 | 最後整理資料摘要 | pass | high | 五階段照片結束後會輸出完整摘要，要求明確回覆「確認」。 |
| T24 | 客戶確認後輸出標準 JSON / 送建檔 | pass | high | mock API 收到 client、course_project、assets、confirmed_at。 |
| T25 | 客戶確認前不建檔 | warning | high | 未建檔，但在 contact 階段回「確認」會被當成 `user_name`，資料會被污染。 |
| T26 | 亂回 / 無效回覆 fallback | pass | medium | 可 fallback 並拉回目前步驟。 |
| T27 | 連續 3 次無效回覆 | pass | high | 會切到 `repeated_invalid_reply`，並保留目前步驟。 |
| T28 | 系統外指令、prompt/API/後台、修改設定 | pass | high | 會走 `unsafe_or_system_instruction`，不透露內部資料、不執行指令。 |
| T29 | 付款問題 | pass | medium | 會說明目前免費試營運，並拉回目前資料整理。 |
| T30 | AI 感 / Dynamic Reply Mode | warning | low | 是規則型動態回覆，不是完全罐頭；contact 階段 fallback 仍偏課程方向，不夠貼合。 |

## 發現問題

| issue_id | 問題 | 嚴重度 | 建議修正 |
| --- | --- | --- | --- |
| QA-001 | contact 階段回覆「確認」會被當作 `user_name` | high | 在 `collecting_required_contact` 階段，`確認`、`好`、`可以`、`嗯` 等確認詞不得寫入姓名，應提示尚未可確認。 |
| QA-002 | 課程形式 unknown 雖可前進，但回覆混入 unclear fallback | medium | unknown 應走正常欄位暫存回覆，不應輸出「請問課程主題」這類不相關 fallback。 |
| QA-003 | contact 階段 fallback 文案偏課程資料 | medium | 必填聯絡資料階段 fallback 應補問姓名、Email、LINE Link，不應問課程名稱 / 類型。 |
| QA-004 | 泛稱「畫畫課」可能同時被當成課名與類型 | medium | 若輸入太泛，應補問「正式課程名稱要暫定為這個嗎？」 |
| QA-005 | 缺少 Email 的長期拒答 / 人工接手狀態 | medium | 增加 `email_status = declined` 或 `needs_human_contact_review` 規則。 |
| QA-006 | 日期 raw text 可保存，但缺少 date status / confidence | medium | Chat D / Chat C 對齊日期狀態欄位。 |
| QA-007 | `docs/TEMPLATE_REFERENCE.md` 與 `docs/CLIENT_SELECTION_FLOW.md` 仍不存在 | medium | 補文件後才能測 template 來源與客戶選款流程。 |
| QA-008 | 圖片內容無法判斷是否無關 | low | 未來加入圖片審核或人工確認流程。 |
| QA-009 | 尚無可重跑自動化 fixture | low | Chat E 建立 worker scenario tests。 |

## 主要流程測試結論

### 入口流程

結果：pass

上一輪的入口分支 bug 已修正：

- 初次未知訊息會顯示開場三選項。
- `我想了解製作流程` 會顯示五步流程。
- `我只是先問問看` 會低壓詢問。
- `我想製作課程招生頁` 會進表單。

### 必填聯絡資料

結果：warning

已修正：

- 單獨姓名可保存。
- Email-only 不再誤判 LINE ID Link。
- 錯誤 Email 會要求重給。
- LINE 顯示名稱會要求補 Link。

仍需修正：

- `確認` 在 contact 階段會被當作姓名。
- contact 階段 fallback 應改成聯絡資料導向。

### 課程資料收集

結果：warning

課程資料主流程可運作，完整表單可直接跳到照片素材。未知費用 / 名額可標記 unknown。課程形式「還沒確定」已不再卡住，但回覆仍混入不相關 fallback。

### 照片素材五階段

結果：pass

五階段、版權提醒、用途說明、建議張數、允許目前沒有、不強迫提供、不生成假圖、不硬做區塊，都已在文件與 worker 中出現。

限制：

- 無關圖片內容無法靠目前 worker 判斷，只能靠人工或後續圖片審核。

### fallback / 安全

結果：pass / warning

安全指令拒絕、prompt/API/後台不透露、連續 3 次 invalid、付款拉回免費試營運，都可測到。

提醒：

- fallback 文案在 contact 階段仍不夠貼合目前欄位。

### 摘要、確認、JSON

結果：pass / warning

五階段照片完成後，會整理摘要。客戶明確回覆「確認」後，mock API 收到標準 payload。

仍需修正：

- 未到摘要階段時，「確認」不可被當成姓名或其他欄位值。

## 需交給 Chat C 的修正項目

詳見 `docs/CHAT_C_FIX_REQUEST.md`。

優先修：

1. contact 階段確認詞不可寫入 `user_name`。
2. 課程形式 unknown 回覆不可混入 unclear fallback。
3. contact 階段 fallback 改成聯絡資料導向。
4. 泛稱課程名稱補確認。

## 需交給 Chat D 的資料庫欄位問題

- `user_name` 對應 `clients.client_name`
- `line_id_link` 是否新增到 `clients`
- `line_user_id` 是否仍由 LINE source userId 寫入
- `email_status`
- `line_id_link_status`
- `needs_human_contact_review`
- `after_class_support`
- `course_price_status`
- `course_capacity_status`
- `expected_launch_date_status` / `expected_start_date_status`
- 照片 assets 欄位或獨立素材表

## 需交給 Chat E 的自動化問題

- 將本輪 Node 模擬測試固化成可重跑 fixture。
- 增加 state snapshot 驗證，不只檢查 reply 文字。
- 增加 regression test：contact 階段「確認」不得污染姓名。
- 增加圖片事件與多圖分類測試。
- 增加 confirmed payload schema 驗證。

## 結論

目前 LINE AI 客服流程已可完成大多數 MVP 資料收集工作。入口、必填資料 gate、照片五階段、fallback、安全拒絕、確認後 JSON 都比上一輪穩定。

剩餘 blocker 是 contact 階段的確認詞污染欄位。修完後，建議 Chat E 建立自動化 fixture，避免之後每次靠人工重測。
