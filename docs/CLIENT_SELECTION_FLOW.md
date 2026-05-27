# Client Selection Flow

## 文件目的

本文件定義客戶收到三款樣板預覽後，如何在三天內選款、系統如何記錄結果，以及逾期時如何作廢預覽。

目前 repo 尚未有前端預覽頁、LINE webhook 或後台實作，本文件是流程規格。

## 客戶選款入口

客戶可透過兩種方式選款：

1. LINE 回覆：「我要 A 款」、「我要 B 款」、「我要 C 款」。
2. 預覽頁 CTA 按鈕送出 A / B / C 選擇。

兩種方式最後都應進入同一個後端選款 API，避免狀態分裂。

## 選款驗證

收到選款後，後端需檢查：

- `project_id` 或 `preview_token` 是否有效。
- project 是否仍為 `樣板預覽中` 或 `已通知客戶選版`。
- 對應 proposal 是否存在。
- proposal 是否未逾期。
- project 是否尚未有 `selected_proposal_id`。

若已逾期，回覆客戶：

```text
這組樣板預覽已超過選擇期限。請聯繫我們重新開啟預覽或安排重新提案。
```

若已選過，回覆客戶目前選擇，並提示如需更改請聯繫人工。

## 選款寫入規則

客戶選定 A / B / C 後：

1. 選中的 `template_proposals.preview_status` 改為 `selected`。
2. 選中的 `template_proposals.selected_at` 寫入目前時間。
3. 其他兩筆 proposal 改為 `not_selected` 或 `cancelled`。
4. `course_projects.selected_proposal_id` 寫入選中 proposal。
5. `course_projects.selected_template_id` 寫入選中 proposal 的 primary template。
6. `course_projects.selected_secondary_template_id` 寫入選中 proposal 的 secondary template。
7. `course_projects.project_status` 改為 `已選定樣板`。
8. 寫入 `notification_logs`，類型為 `proposal_selected`。
9. 後續進入 `免費製作中`，不進入報價或付款流程。

## 逾期作廢規則

排程 Worker 檢查：

- `course_projects.project_status IN (樣板預覽中, 已通知客戶選版)`
- `course_projects.preview_expires_at < NOW()`
- 沒有 proposal 是 `selected`

符合條件時：

1. 三筆 proposal 改為 `expired`。
2. project 改為 `樣板逾期`。
3. 預覽頁保留 URL，但顯示逾期提示。
4. 發送 Email 與 LINE 作廢通知。
5. 寫入 `notification_logs`，類型為 `preview_expired`。

## 重新開啟預覽

後台可提供「重新開啟預覽」按鈕：

1. 管理員選擇新的到期時間。
2. 系統將 proposal 從 `expired` 改回 `sent` 或 `preview_ready`。
3. 更新三筆 proposal 的 `expires_at`。
4. 更新 project 的 `preview_expires_at`。
5. project 改回 `樣板預覽中`。
6. 重新寄送預覽通知。

建議重新開啟時產生新的 `preview_token`，避免舊連結被轉傳後長期有效。

## 免費試營運後續狀態

客戶選定樣板後，目前 MVP 的後續流程是：

```text
已選定樣板
  -> 免費製作中
  -> 正式頁預覽中
  -> 待客戶確認
  -> 試營運中
  -> 已上線
```

報價、付款與待付款屬於 future phase，不列入目前選款後的主流程。
