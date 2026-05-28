# Chat D Infra Request

## 文件目的

本文件整理 Chat G：Canva 三案提案自動化在 direct claim 前遇到的基礎設施問題，交給 Chat D 檢查後端與 API 網域穩定性。

## 事件摘要

- 日期：2026-05-28
- 失敗 automation：`Chat G：Canva 三案提案（自動化）`
- claim endpoint：`https://ftm.com.tw/demo/admission-system/api/chat-a-trigger/claim.php`
- 最近失敗 run id：`chat-g-20260528110319`
- 錯誤：`curl: (6) Could not resolve host: ftm.com.tw`
- 同一輪已連續重試 3 次仍失敗。
- 失敗發生在 direct claim 前，沒有 claimed project，也沒有 Canva proposal 產出。
- 後續另有一輪 `chat-g-20260528111256` 在 direct claim 前因 `stale_worktree_context` 停止：expected cwd 為 `945c`，actual cwd 為已不存在的 `4d27`。

## Chat E 已確認

- 正式 automation 設定檔：`/Users/phoebe/.codex/automations/chat-g-canva-proposals-automation/automation.toml`
- 正式 automation `cwds` 已更新為：`/Users/phoebe/.codex/worktrees/e89a/課程招生 - 系統`
- 舊的 `945c` worktree 目前存在，但為 detached HEAD 且停在較舊 commit。
- `/Users/phoebe/.codex/worktrees/4d27/課程招生 - 系統` 目前不存在。
- `/Users/phoebe/.codex/worktrees/1210/課程招生 - 系統` 目前不存在。
- 目前正式 automation 設定中未找到 `4d27` 或 `1210` 殘留。
- 2026-05-28 11:07 左右，本機連續 5 次 preflight 都可解析 `ftm.com.tw` 到 `60.249.109.44`。
- `claim.php` route 有回應；以非 POST 測試時回 `405`，代表 host / route 目前可達。
- 2026-05-28 11:18，本機 health endpoint 回 `ok=true`、`authenticated=true`、`db_ok=true`；該檢查未呼叫 claim。
- 2026-05-28 11:31，本機 DNS / health 檢查正常：
  - system DNS 可解析 `ftm.com.tw` 到 `60.249.109.44`。
  - `dig @1.1.1.1` 與 `dig @8.8.8.8` 也可解析。
  - health endpoint 回 `HTTP 200`。
  - `curl --resolve ftm.com.tw:443:60.249.109.44` 也回 `HTTP 200`。
- Chat G automation 已補強：
  - 啟動時檢查 cwd 是否等於 configured cwd `e89a`。
  - cwd 不一致時，在 DNS / health / claim / callback / Canva generation 前停止並記錄 `stale_worktree_context`。
  - claim 前固定執行 `scripts/chat-g-network-preflight.sh`，記錄 pwd / `socket.gethostbyname_ex("ftm.com.tw")` DNS probe / health probe。
  - DNS / host resolution 失敗最多 3 次 bounded retry，每次間隔 2 到 5 秒。
  - 若 manual shell socket DNS 成功但 automation runtime socket DNS 失敗，優先查 scheduler/runtime network context。
  - DNS 無法解析時不嘗試 POST fail callback，改記錄 infra blocker。

## 請 Chat D 檢查

1. 後端 access log 是否有收到 `chat-g-20260528110319` 或同時間附近的 claim request。
2. 若沒有收到 request，請確認是 DNS / network 層就失敗，而不是 PHP route / auth 失敗。
3. 檢查 `ftm.com.tw` DNS / hosting 是否有短暫解析失敗紀錄。
4. 確認 `claim.php`、`template-proposals/`、`fail.php` 三個 endpoint 的 API contract：
   - method
   - required headers
   - request JSON
   - success response
   - retryable error response
   - non-retryable error response
5. 建議新增或確認健康檢查 endpoint，例如：
   - `GET /demo/admission-system/api/health.php`
   - 回傳目前 API version、DB 連線狀態、server time。
6. 評估是否需要備援網域或更穩定的 API hostname，避免 claim / success / fail 都被同一個 DNS 問題阻斷。

## 建議錯誤分類

- `dns_resolution_failed`：worker 無法解析 `ftm.com.tw`，後端通常收不到 request。
- `api_unreachable`：DNS 成功但 TCP / TLS / HTTP 無法連線。
- `api_contract_error`：後端收到 request，但 method、header 或 JSON 格式不符。
- `auth_failed`：API key 錯誤。
- `claim_empty`：成功呼叫但目前沒有可 claim project。

## Chat E 後續

- 每日巡檢 Chat G automation 的 `cwds` 是否仍指向存在 worktree。
- 若再次出現 `curl: (6)`，保留 memory log 並標記 infra blocker。
- 若 Chat D 提供 health endpoint，將 Chat G preflight 改成先打 health endpoint，再 claim。
- 若下一輪仍從非 `e89a` cwd 啟動，需視為 scheduler / run session cache 問題，優先重建 automation 或清除 stale scheduler context。
