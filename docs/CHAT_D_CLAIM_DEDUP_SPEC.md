# Chat D / 後端修正規格：Canva Proposal Claim 去重與鎖定

## 文件目的

本文件定義 `chat-a-trigger/claim.php` 與 `template_proposals` / `course_projects` 狀態流的修正需求，目標是避免同一個課程案件被重複 claim、重複產出 proposal batch，或在已經 `template_ready` 後再次進入自動提案流程。

本規格給 Chat D / 後端 / 資料庫線使用。  
Chat D 只負責資料庫、API、後台與狀態規則，不負責改動版型方向。

---

## 問題摘要

### 實際觀察到的錯誤

同一個案件 `CP-20260528-00012` 已經在 `2026-05-28 07:27:12 CST` 完成 proposal 建立並成功回寫為：

- `saved_proposal_ids`: `A`, `B`, `C`
- `template_status`: `template_ready`

但在 `2026-05-28 08:01:29 CST`，`claim.php` 又再次把同一個 `project_id` claim 出來，並開出新的：

- `proposal_batch_id`: `batch_578aef5ccfdace833c9c1cd7`
- `worker_run_id`: `chat-g-20260528080129`

這表示目前後端缺少至少一層去重或狀態排除機制。

---

## 修正目標

後端必須保證以下規則：

1. 同一個 `project_id` 在已有有效 proposal batch 時，不可再次被自動 claim。
2. 若案件已經進入 `template_ready`、`待客戶選版`、`selected` 或等價完成狀態，不可再自動重派。
3. 同一個案件在同一時間只能有一個有效中的 proposal batch。
4. 若需要重產 proposal，必須是明確的 regenerate 行為，不可由一般 claim 流程自動觸發。
5. worker 成功 claim 後，其他 worker 不可再拿到同一案，除非原 claim 已失效或被明確釋放。

---

## 名詞定義

### 有效 proposal batch

以下任一條件成立，視為此 project 已有有效 batch，不可再被一般 claim 流程派送：

- proposal batch 狀態為 `processing`
- proposal batch 狀態為 `pending`
- proposal batch 狀態為 `ready`
- proposal batch 狀態為 `sent_to_client`
- proposal batch 尚未過期且未被標記為 `failed` / `expired` / `archived`

### 明確重產 regenerate

只有以下來源可以建立第二批 proposal：

- 使用者在後台明確按下「重新產生 proposal」
- 內部 API 明確帶入 `regenerate=true`
- 原 proposal batch 已被人工標記為 `failed`、`expired` 或 `archived`，且允許重開

若沒有明確 regenerate 訊號，`claim.php` 不可為同一個 `project_id` 再建立新的 `proposal_batch_id`。

---

## 建議資料模型修正

若目前尚未落資料表，可依此規格設計；若已存在相近欄位，請用等價欄位實作。

### `course_projects`

建議至少包含：

- `project_id`
- `template_status`
- `active_proposal_batch_id`
- `template_processing_started_at`
- `template_ready_at`
- `template_selected_at`
- `last_claimed_by_worker`
- `last_claimed_run_id`
- `last_claimed_at`

### `template_proposal_batches`

若目前沒有 batch table，建議新增。  
若暫時不新增，也至少要能在 `template_proposals` 中明確保存 `proposal_batch_id` 並能查出 batch 級狀態。

建議欄位：

- `proposal_batch_id`
- `project_id`
- `status`
- `claimed_by_worker`
- `worker_run_id`
- `claimed_at`
- `expires_at`
- `completed_at`
- `regenerated_from_batch_id`
- `created_at`
- `updated_at`

建議 batch 狀態：

- `processing`
- `pending`
- `ready`
- `sent_to_client`
- `selected`
- `failed`
- `expired`
- `archived`

### `template_proposals`

除了既有 Chat D integration 欄位外，建議補上：

- `proposal_batch_id`
- `worker_run_id`

---

## 必要唯一性與約束

### Proposal 唯一性

同一批 proposal 內，A/B/C 不可重複：

- unique key: `proposal_batch_id + proposal_code`

### Batch 有效唯一性

同一個 `project_id` 同時只能有一個「有效中」batch。

建議實作方式：

1. 若資料庫支援 partial unique index：
   - 對 `template_proposal_batches(project_id)` 建立 partial unique index
   - 條件：`status in ('processing', 'pending', 'ready', 'sent_to_client')`

2. 若資料庫不支援 partial unique index：
   - 在 claim transaction 中用 `SELECT ... FOR UPDATE`
   - 明確檢查是否已有有效 batch
   - 有就拒絕建立新 batch

### Project 狀態一致性

若 `course_projects.template_status` 已是以下狀態，不可再被一般 claim：

- `template_ready`
- `waiting_client_selection`
- `template_selected`
- `selected`

實際 enum 名稱可依系統現況對應，但語意必須一致。

---

## `claim.php` 修正規格

### 輸入

維持現有輸入即可：

- `limit`
- `worker_name`
- `worker_run_id`

可考慮新增：

- `allow_regenerate`，預設 `false`

### Claim 篩選條件

一般 claim 流程只能挑選真正待處理案件，例如：

- `template_status` 屬於 `pending_template`、`processing_template` 或等價待處理狀態
- 沒有有效中的 proposal batch
- 沒有已完成但仍有效的 proposal batch

### Claim 時的交易邏輯

`claim.php` 必須在單一 transaction 內完成：

1. 找出候選 project
2. 鎖定該 project row
3. 再次檢查：
   - `template_status` 是否仍可處理
   - 是否已有有效 batch
4. 若可處理：
   - 建立新的 `proposal_batch_id`
   - 建立 batch record，狀態 `processing`
   - 更新 `course_projects.template_status = processing_template`
   - 寫入 `active_proposal_batch_id`
   - 寫入 `last_claimed_by_worker`、`last_claimed_run_id`、`last_claimed_at`
5. commit 後才回傳 claim 結果

### Claim API 行為規則

- 若案件已有有效 batch，則該案件不可出現在 claim response 中。
- 若全部候選都不可處理，應回：
  - `ok: true`
  - `claimed_count: 0`
  - `projects: []`
- 不可因為 `template_status` 尚未同步就跳過 batch 檢查。
- batch 檢查優先級必須高於單純 project status。

---

## `template-proposals/` 成功回寫規格

當 worker POST 三款 proposal 成功時，後端必須在同一筆流程中：

1. 驗證 proposal count 必須剛好為 3
2. 驗證 proposal code 必須為 `A`、`B`、`C`
3. 驗證每筆 proposal 都有：
   - `primary_template_id`
   - `secondary_template_id`
   - `source_url`
   - `secondary_source_url`
   - 真實 `canva_url`
4. 寫入 `template_proposals`
5. 更新 batch 狀態為 `ready`
6. 更新 `course_projects.template_status = template_ready` 或等價值
7. 更新 `course_projects.active_proposal_batch_id = 該 batch`
8. 寫入 `template_ready_at`

### 成功回寫後的強制規則

成功回寫後，該 `project_id` 不可再被一般 claim 流程領出。

---

## `fail.php` 失敗回寫規格

當 worker 明確回報失敗時，後端應：

1. 根據 `project_id + worker_run_id` 或 `proposal_batch_id` 找到對應 batch
2. 將 batch 標記為 `failed`
3. 視情況把 `course_projects.template_status` 回退為：
   - `pending_template`
   - 或 `template_failed`

### 失敗後是否允許重新 claim

- 若 batch `failed`，可重新 claim
- 但應優先重用原案件，不可同時存在兩個 processing batch

---

## Regenerate 規格

### 何時允許 regenerate

僅在明確操作下允許：

- 後台按鈕
- 明確 regenerate API
- 人工指定

### Regenerate 的後端行為

1. 將舊 batch 標記為：
   - `archived`
   - 或 `expired`
2. 記錄：
   - `regenerated_from_batch_id`
3. 建立新 batch
4. 更新 `active_proposal_batch_id`

### 禁止事項

- 不可在一般 claim 流程中自動 regenerate
- 不可因 worker 名稱不同就視為可重產
- 不可因 `expires_at` 還沒到，就額外開第二批

---

## Admin / 後台顯示需求

後台建議清楚顯示：

- `project_id`
- `template_status`
- `active_proposal_batch_id`
- 每次 `proposal_batch_id`
- batch 狀態
- `worker_run_id`
- `claimed_by_worker`
- `claimed_at`
- 是否為 regenerate batch

這樣可以讓人一眼看出：

- 案件是不是被重複 claim
- 同一案有沒有兩批 proposal
- 哪個 worker 建了哪一批

---

## 驗收案例

### Case 1：正常首次 claim

前提：

- project 為 `pending_template`
- 無有效 batch

預期：

- claim 成功
- 建立 1 個新 batch
- project 轉為 `processing_template`

### Case 2：已 `template_ready` 不可再 claim

前提：

- project 已有一批 `ready`

預期：

- claim response 不可再出現此 project
- `claimed_count` 應為 0 或只回其他案件

### Case 3：同時兩個 worker claim

前提：

- 兩個 worker 幾乎同時請求 claim

預期：

- 只有一個 worker 成功拿到該 project
- 另一個 worker 不可拿到同一案

### Case 4：fail 後重新 claim

前提：

- batch 已標記 `failed`

預期：

- 可再次 claim
- 新 claim 前不可保留舊的 processing batch

### Case 5：明確 regenerate

前提：

- project 已有 `ready` batch
- 後台明確要求 regenerate

預期：

- 舊 batch 被標記 `archived` 或等價狀態
- 新 batch 建立成功
- 後續只以新 batch 為 active batch

---

## 建議優先修正順序

1. 修 `claim.php` 查詢條件，先排除 `template_ready` / 已有有效 batch 的案件
2. 在 claim transaction 補 row lock / 二次檢查
3. 補 `template_proposal_batches` 或等價 batch 級記錄
4. 補唯一性約束
5. 補 regenerate 明確入口，不再讓一般 claim 自動重開

---

## 建議交付給 Chat D 的 prompt

```text
請修正課程 proposal claim 流程，避免同一個 project 被重複 claim 與重複建立 proposal batch。

已觀察到真實問題：
project_id `CP-20260528-00012` 在 2026-05-28 07:27:12 已成功回寫為 `template_ready`，
但在 2026-05-28 08:01:29 又被 claim.php 再次派發，並開出新的 proposal_batch_id。

請依 `docs/CHAT_D_CLAIM_DEDUP_SPEC.md` 實作：
1. `claim.php` 不可再 claim 已 `template_ready` 或已有有效 batch 的 project
2. 同一個 project 同時間只能有一個有效 batch
3. 成功回寫 proposal 後，project 不可再被一般 claim
4. regenerate 必須是明確操作，不可由一般 claim 自動觸發
5. 請補必要的資料表欄位、唯一性約束、transaction lock 與驗收案例
```
