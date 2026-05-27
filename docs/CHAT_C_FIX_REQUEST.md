# Chat C Fix Request

## 文件目的

本文件整理 Chat E / QA 回歸測試後，需要交給 Chat C 修正的 LINE AI 客服流程與 worker 行為問題。

請注意：Chat E 本次未修改 `docs/LINE_AI_CUSTOMER_SERVICE_FLOW.md`，也未修改 `cloudflare-workers/workers.js`。以下內容保留修正歷史與目前回歸狀態，供 Chat C / Chat E 後續追蹤。

## 本輪測試狀態

2026-05-27 重新檢查後：

- `docs/LINE_AI_CUSTOMER_SERVICE_FLOW.md` 已存在。
- `cloudflare-workers/workers.js` 已存在。
- Worker 版本：`chat-c-course-type-help-fix-2026-05-27-12`。
- `docs/TEMPLATE_REFERENCE.md` 尚未存在。
- `docs/CLIENT_SELECTION_FLOW.md` 尚未存在。

Chat E 已用 Node 模擬 LINE webhook 實測 worker 回覆。Chat C 修正後，`node --test tests/line-ai-worker-scenarios.test.mjs` 目前 S01-S24 全部通過。

目前 C-FIX-010、C-FIX-012、C-FIX-013、C-FIX-014、C-FIX-015 已由 Chat C 修正並納入 regression。以下 C-FIX 項目保留作為修正歷史與 regression 對照。

## 目前回歸狀態

| issue_id | 狀態 | 對應測試 |
| --- | --- | --- |
| C-FIX-001 | resolved | S04 / S08 / S09 |
| C-FIX-002 | resolved | S07 |
| C-FIX-003 | resolved | S06 |
| C-FIX-004 | resolved | S15 |
| C-FIX-005 | resolved | S09 confirmed payload gate |
| C-FIX-006 | resolved | confirmed payload date status coverage |
| C-FIX-007 | resolved | S11 |
| C-FIX-008 | resolved | S14 / S16 |
| C-FIX-009 | resolved | S16 |
| C-FIX-010 | fixed | S17 |
| S18 contact update intent | fixed | S18 |
| S19 update email validation | fixed | S19 |
| S20 update name intent | fixed | S20 |
| S21 update LINE wrong-field guard | pass | S21 |
| S22 update contact before confirmation | fixed | S22 |
| S23 multiple contact updates payload | fixed | S23 |
| S24 line id alias | fixed | S24 |

### C-FIX-012

- issue_id：C-FIX-012
- 問題描述：更新 Email 時，客戶輸入錯誤 Email `abc123`，系統只回一般缺 Email fallback，沒有明確提示 Email 格式錯誤。
- 出現在哪個流程階段：聯絡資料更新 / Email update validation
- 目前錯誤行為：已進入「更新 Email」後，輸入 `abc123`，回覆要求補 Email，但未說明格式錯誤。
- 期望行為：更新 Email 模式下，非 Email 字串應回覆 Email 格式錯誤，且不可清掉姓名與 LINE ID Link。
- 建議修正話術或規則：若 `pending_contact_update_field = email` 且輸入未通過 email regex，設定 `email_status = invalid` 並回覆格式提示。
- 是否影響建檔：是
- 是否影響客戶體驗：是
- 優先級：high
- Chat C 修正狀態：已修正於 Worker 版本 `chat-c-contact-update-resume-fix-2026-05-27-11`；S19 regression 通過。

### C-FIX-013

- issue_id：C-FIX-013
- 問題描述：課程資料收集中，客戶回「我要改姓名」沒有命中姓名更新意圖。
- 出現在哪個流程階段：課程資料收集 / contact field update intent
- 目前錯誤行為：`我要改姓名` 被當成課程資料 fallback，沒有進入姓名更新。
- 期望行為：`改姓名`、`改名字`、`改稱呼`、`我要改姓名` 都應命中 `user_name` 更新。
- 建議修正話術或規則：擴充 `detectContactUpdateRequest()` 的更新詞，加入單字 `改` 搭配欄位詞的判斷。
- 是否影響建檔：是
- 是否影響客戶體驗：是
- 優先級：high
- Chat C 修正狀態：已修正於 Worker 版本 `chat-c-contact-update-resume-fix-2026-05-27-11`；S20 regression 通過。

### C-FIX-014

- issue_id：C-FIX-014
- 問題描述：已到摘要確認階段時更新 LINE ID Link，更新完成後會跳回照片第 1 階段，而不是回到摘要確認。
- 出現在哪個流程階段：ready_for_confirmation / contact update resume
- 目前錯誤行為：`ready_for_confirmation` 階段回「我要更新LINE ID Link」並貼新 line.me 後，系統回作品主圖照片階段。
- 期望行為：摘要確認前更新 contact 欄位後，應重新輸出摘要並等待「確認」，不可跳回照片流程，不可自動建檔。
- 建議修正話術或規則：開始 contact update 時保存 `resume_intake_step = ready_for_confirmation`；更新成功後若原步驟為 ready，呼叫 `buildIntakeSummary()`。
- 是否影響建檔：是
- 是否影響客戶體驗：是
- 優先級：high
- Chat C 修正狀態：已修正於 Worker 版本 `chat-c-contact-update-resume-fix-2026-05-27-11`；S22 regression 通過。

### C-FIX-015

- issue_id：C-FIX-015
- 問題描述：摘要確認前連續更新 Email、姓名、LINE ID Link 後，回覆「確認」沒有建檔，payload 無法驗證最新 contact 值。
- 出現在哪個流程階段：ready_for_confirmation / multiple contact updates / confirmed payload
- 目前錯誤行為：連續更新多個 contact 欄位後，系統沒有回到 ready_for_confirmation，導致 `確認` 不會送建檔 API。
- 期望行為：連續更新後仍應回到摘要確認；確認後 payload 使用最新 `user_name`、`email`、`line_id_link`。
- 建議修正話術或規則：同 C-FIX-014，所有 contact update 完成後需依原流程恢復；若原本已 ready，更新成功即重建摘要。
- 是否影響建檔：是
- 是否影響客戶體驗：是
- 優先級：high
- Chat C 修正狀態：已修正於 Worker 版本 `chat-c-contact-update-resume-fix-2026-05-27-11`；S23 regression 通過。

### C-FIX-010

- issue_id：C-FIX-010
- 問題描述：短 LINE 代碼會被誤當成 `user_name`；真實 LINE 對話中同時出現 Email 已提供後又被判缺的狀態錯亂。
- 出現在哪個流程階段：必填聯絡資料 / Email persistence / LINE ID retry
- 目前錯誤行為：本地 S17 中，客戶提供 valid Email 後，回覆短代碼 `URZ8z2U`，worker 只補問 LINE ID Link，代表 `user_name` 被誤判已填。真實 LINE 對話中則進一步出現補 line.me URL 後又回頭要求 Email 的狀態錯亂。
- 期望行為：Email 一旦以 valid 狀態寫入，後續 LINE ID 補問、短代碼、line.me URL 都不可清掉 Email。短代碼如 `URZ8z2U` 不可推測為姓名；若不符合 LINE Link，應繼續要求姓名與 LINE ID Link。
- 建議修正話術或規則：確認 `applyContactText()` / `applyFormFields()` 不會用空白或無關訊息覆蓋 valid email；將純英數短碼列為 contact 階段不可推測姓名，可回覆「這看起來像 LINE ID，但不是完整連結」並繼續要求姓名與 LINE ID Link。保留 S17 作為 regression test。
- 是否影響建檔：是
- 是否影響客戶體驗：是
- 優先級：high
- Chat C 修正狀態：已修正於 Worker 版本 `chat-c-short-line-code-fix-2026-05-27-08`；短英數 LINE 代碼不再通過 `user_name` 推測，S17 regression 通過。

## Chat C 修正項目

### C-FIX-009

- issue_id：C-FIX-009
- 問題描述：contact gate 未完成時，客戶回「實體」會被誤存成 `user_name`。
- 出現在哪個流程階段：必填聯絡資料 / 課程形式詞彙污染姓名
- 目前錯誤行為：客戶貼空白表單、單獨提供 Email、詢問課程類型後，再回「實體」，worker 回覆只剩缺 LINE ID Link，代表 `user_name` 被誤填；很可能把「實體」當成姓名。
- 期望行為：在 `collecting_required_contact` 階段，`實體`、`線上`、`混合`、`畫畫`、`色鉛筆`、`課程類型` 等課程欄位值或課程相關詞，不可被推測成 `user_name`。若 contact gate 未完成，應繼續要求補姓名與 LINE ID Link。
- 建議修正話術或規則：擴充 `isClearlyInvalidContactReply()` 或 `looksLikeCourseFieldText()`，將課程形式 / 課程類型詞列為 contact 階段不可寫入姓名的值。保留 S16 作為 regression test。
- 是否影響建檔：是
- 是否影響客戶體驗：是
- 優先級：high

### C-FIX-008

- issue_id：C-FIX-008
- 問題描述：貼空白表單後，空白 `LINE ID Link：` label 會污染 `line_id_link`，讓 LINE link gate 被誤判通過。
- 出現在哪個流程階段：必填聯絡資料 / 空白表單解析
- 目前錯誤行為：客戶貼回空白表單後，再單獨提供 Email，系統只補問姓名，沒有補問 LINE ID Link；代表空白 `LINE ID Link：` 被存成某種 `need_review` 值。
- 期望行為：空白 label 不得寫入任何欄位；`LINE ID Link：`、`3. LINE ID Link：`、`LINE ID：` 後方沒有實際值時，`line_id_link` 必須維持空值或 `missing`。
- 建議修正話術或規則：調整 `extractLineLink()` / `cleanLabeledValue()`，先移除數字編號與 label；若清理後沒有有效內容，回傳空字串，不得標 `need_review`。另外將空白表單重貼加入 regression test。
- 是否影響建檔：是
- 是否影響客戶體驗：是
- 優先級：high

### C-FIX-001

- issue_id：C-FIX-001
- 問題描述：contact 階段回覆「確認」會被誤存成 `user_name`。
- 出現在哪個流程階段：必填聯絡資料 / 確認前 gate
- 目前錯誤行為：客戶剛收到表單後回「確認」，worker 不會建檔，但會把 `user_name` 設成「確認」，接著只補問 Email 與 LINE ID Link。
- 期望行為：在 `ready_for_confirmation` 之前，`確認`、`好`、`可以`、`嗯`、`OK` 等確認詞不可寫入任何資料欄位，也不可建檔；應提示目前還缺必填聯絡資料。
- 建議修正話術或規則：新增 confirmation-like guard。若 `current_intake_step !== ready_for_confirmation` 且收到確認詞，回覆：「還沒到確認送出喔，目前還需要先補姓名、Email、LINE ID Link。」
- 是否影響建檔：是
- 是否影響客戶體驗：是
- 優先級：high

### C-FIX-002

- issue_id：C-FIX-002
- 問題描述：課程形式回覆「還沒確定」雖能前進，但回覆混入不相關 fallback。
- 出現在哪個流程階段：課程資料收集 / 課程形式
- 目前錯誤行為：worker 會前進到招生時間，但回覆先出現「請問這堂課主要是什麼主題呢？」之類 unclear fallback。
- 期望行為：將 `course_format = 未定` 後，用自然語氣說明已先記為未定，接著問預計招生時間。
- 建議修正話術或規則：unknown-like input 被成功寫入欄位時，不應增加 invalid count 或套 fallback；直接回下一欄 prompt。
- 是否影響建檔：否
- 是否影響客戶體驗：是
- 優先級：medium

### C-FIX-003

- issue_id：C-FIX-003
- 問題描述：必填聯絡資料階段的 fallback 文案偏課程資料。
- 出現在哪個流程階段：必填聯絡資料 / 無效回覆
- 目前錯誤行為：在 contact 階段回「哈哈哈」或「隨便」，fallback 會問課程名稱、課程類型，但目前真正需要的是姓名、Email、LINE ID Link。
- 期望行為：contact 階段 fallback 應拉回聯絡資料，不問課程方向。
- 建議修正話術或規則：新增 contact-specific fallback pool，內容聚焦「我先需要姓名、Email、LINE ID Link，補齊後才進課程資料」。
- 是否影響建檔：否
- 是否影響客戶體驗：是
- 優先級：medium

### C-FIX-004

- issue_id：C-FIX-004
- 問題描述：泛稱「畫畫課」可能同時被當成課程名稱與課程類型。
- 出現在哪個流程階段：課程名稱 / 類型收集
- 目前錯誤行為：實測可直接推進，但資料可能不夠精準。
- 期望行為：若輸入太泛，應確認是否作為暫定課名，並補問更正式名稱。
- 建議修正話術或規則：「我先把類型記成畫畫；課程名稱要暫定為『畫畫課』嗎？還是有正式名稱？」
- 是否影響建檔：是
- 是否影響客戶體驗：中
- 優先級：medium

### C-FIX-005

- issue_id：C-FIX-005
- 問題描述：客戶長期不提供 Email 時缺少 declined / 人工接手策略。
- 出現在哪個流程階段：必填聯絡資料
- 目前錯誤行為：不會跳階段，但只能持續補問。
- 期望行為：多次拒答 Email 後，標記 `email_status = declined` 或 `needs_human_contact_review = true`，並提示可由人工接手；仍不可自動建檔。
- 建議修正話術或規則：連續兩次拒答 Email 後回覆：「Email 會作為登入與通知用途；如果現在真的不方便提供，我先幫你保留到人工確認，不會直接建檔。」
- 是否影響建檔：是
- 是否影響客戶體驗：中
- 優先級：medium

### C-FIX-006

- issue_id：C-FIX-006
- 問題描述：日期可保存 raw text，但缺少狀態標記。
- 出現在哪個流程階段：課程資料收集 / 招生時間 / 開課時間
- 目前錯誤行為：「月底吧」可保存，但沒有 `expected_launch_date_status` 或 confidence。
- 期望行為：模糊日期應標記 `tentative` 或 `need_review`。
- 建議修正話術或規則：若日期包含「月底、月初、大概、可能、左右」等詞，保留 raw text 並標記 date status。
- 是否影響建檔：是
- 是否影響客戶體驗：中
- 優先級：medium

### C-FIX-007

- issue_id：C-FIX-007
- 問題描述：圖片內容無法判斷是否無關。
- 出現在哪個流程階段：照片素材收集
- 目前錯誤行為：圖片事件會被收進當前照片階段；若內容其實無關，worker 無法辨識。
- 期望行為：若無法辨識圖片內容，至少標記 `need_review` 或交給人工 / 圖像審核確認。
- 建議修正話術或規則：在圖片無法驗證時回覆「已收到，後續會確認是否適合此區塊」；必要時所有圖片先標 `need_review`。
- 是否影響建檔：否
- 是否影響客戶體驗：中
- 優先級：low

## 已回歸通過項目

以下上一輪問題本輪已通過：

- 入口 2「我想了解製作流程」。
- 入口 3「我只是先問問看」。
- 初次未知訊息顯示開場三選項。
- 單獨姓名可保存為 `user_name`。
- Email-only 不誤判 LINE ID Link。
- 錯誤 Email 會要求重給。
- LINE 顯示名稱會要求補 link。
- 費用 / 人數不確定可標記 unknown。
- 五階段照片與「目前沒有」流程。
- 確認後 confirmed JSON payload。
- 確認前不建檔。
- 系統外指令不透露內部資料。
- 付款問題回免費試營運並拉回流程。

## 需交給 Chat D 的欄位對齊

以下不是 Chat C 直接修話術，但會影響建檔：

- `user_name` 對應 `clients.client_name`
- `line_id_link` 是否新增到 `clients`
- `line_user_id` 是否仍由 LINE source userId 寫入
- `email_status`
- `line_id_link_status`
- `needs_human_contact_review`
- `after_class_support`
- `course_price_status`
- `course_capacity_status`
- `expected_launch_date_status`
- `expected_start_date_status`
- 照片 assets 欄位或獨立素材表

## 建議 Chat C 交付物

Chat C 修正後，請至少交付：

- 更新後的 `docs/LINE_AI_CUSTOMER_SERVICE_FLOW.md`
- 更新後的 `cloudflare-workers/workers.js`
- 說明 confirmation-like guard 修正方式
- 說明 contact-specific fallback 修正方式
- 說明 unknown-like input 不觸發 fallback 的修正方式
- 提供 Chat E 可重測的案例清單
