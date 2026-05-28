# Chat E Automation Infra Report

## 文件目的

本文件記錄 Chat E 對 `Chat G：Canva 三案提案（自動化）` 的 scheduler / worktree 基礎設施檢查結果。

## 事件摘要

- 日期：2026-05-28
- automation id：`chat-g-canva-proposals-automation`
- worker_run_id：`chat-g-20260528111256`
- error_code：`stale_worktree_context`
- expected_cwd：`/Users/phoebe/.codex/worktrees/945c/課程招生 - 系統`
- actual_cwd：`/Users/phoebe/.codex/worktrees/4d27/課程招生 - 系統`
- 結論：這一輪依規則已在 DNS、health check、claim、callback、Canva generation 之前停止，沒有取件，也沒有產生 proposal。
- 後續 run `chat-g-20260528112131` 再次出現 `stale_worktree_context`：
  - expected_cwd：`/Users/phoebe/.codex/worktrees/e89a/課程招生 - 系統`
  - actual_cwd：`/Users/phoebe/.codex/worktrees/4938/課程招生 - 系統`
  - 結論：`cwds` 更新後仍跑到新的 generated worktree，代表根因不是 active cwd 設定值，而是 `worktree` execution environment 會建立或沿用每輪臨時 worktree。

## Chat E 檢查結果

- active automation 設定檔只找到一份：`/Users/phoebe/.codex/automations/chat-g-canva-proposals-automation/automation.toml`。
- active automation 設定中未找到 `4d27`。
- active automation 設定中未找到 `4938`。
- `/Users/phoebe/.codex/worktrees/4938/課程招生 - 系統` 目前不存在。
- `/Users/phoebe/.codex/worktrees/4d27/課程招生 - 系統` 目前不存在。
- `/Users/phoebe/.codex/worktrees/1210/課程招生 - 系統` 目前不存在。
- 原設定的 `/Users/phoebe/.codex/worktrees/945c/課程招生 - 系統` 存在，但為 detached HEAD，最後 commit 是 `379de5f docs: formalize Canva-first proposal flow`。
- 目前 Chat E 使用的 `/Users/phoebe/.codex/worktrees/e89a/課程招生 - 系統` 存在，位於 `codex/collaboration-handoff`，最後 commit 是 `9f41ce6 Add Chat D infra followup for Canva automation`。

## 已修復項目

- 已將 Chat G automation 的 `cwds` 改為：

```text
/Users/phoebe/.codex/worktrees/e89a/課程招生 - 系統
```

- 已將 Chat G automation 的 `execution_environment` 從 `worktree` 改為 `local`。
- 修正原因：`worktree` execution environment 會讓 scheduler 建立或使用 generated worktree，例如 `4938`；若規則要求 actual cwd 必須等於 configured cwd，就不能使用 `worktree` mode。

- 已同步更新 automation prompt 的 infrastructure preflight：
  - expected cwd 改為 `e89a`。
  - 若 run context 指向 `4938`、`4d27`、`1210`、舊的 `945c` 或任何非 `e89a` cwd，必須立刻停止。
  - 停止點必須在 DNS、health check、claim、callback、Canva generation 之前。
  - 必須寫入 `stale_worktree_context` 到 automation memory。
- 已更新 `/Users/phoebe/.codex/automations/chat-g-canva-proposals-automation/memory.md`。

## 驗證結果

- 本機 local cwd preflight：pass。
- active automation 設定：`execution_environment = "local"`。
- active automation `cwds`：`/Users/phoebe/.codex/worktrees/e89a/課程招生 - 系統`。
- Chat D health endpoint：pass。
- health endpoint 回傳重點：
  - `ok=true`
  - `authenticated=true`
  - `db_ok=true`
  - `worker_name=course-canva-proposal-worker`
  - `worker_run_id_prefix=chat-g-`

最新一次 local-mode 修復只做 cwd 與設定檔驗證，沒有做 DNS / health / claim / callback / Canva。

## Network Preflight Update

2026-05-28 後續 run 已確認 cwd 正確，但在 health preflight 前連續 3 次出現：

```text
curl: (6) Could not resolve host: ftm.com.tw
```

Chat E 已新增 `scripts/chat-g-network-preflight.sh`，並將 Chat G automation 改為 claim 前固定執行：

```text
CHAT_G_RUNTIME_CONTEXT=automation_runtime WORKER_RUN_ID=<worker_run_id> ADMISSION_API_KEY=admission-api-20260528-chat-a-trigger scripts/chat-g-network-preflight.sh
```

此 preflight gate 會固定寫入 automation memory：

- `pwd` / actual cwd / expected cwd。
- `runtime_context`，用來比對 `manual_shell` 與 `automation_runtime`。
- 第一層 DNS gate：Python `socket.gethostbyname_ex("ftm.com.tw")`。
- macOS DNS resolver sample。
- system DNS probe：`dscacheutil` 與 system `dig`。
- public DNS probe：`dig @1.1.1.1`。
- health curl exit code、HTTP status、remote IP、DNS lookup time、connect time、TLS time、total time。
- health response body 前段。
- 最終 `preflight_passed`、`dns_resolution_failed` 或 `health_preflight_failed`。

本機 runtime 檢查結果：

- manual shell socket DNS probe 成功：`socket.gethostbyname_ex("ftm.com.tw") -> 60.249.109.44`。
- macOS resolver 目前來自 Wi-Fi / hotspot，包含 link-local IPv6 resolver 與 `172.20.10.1`。
- `dscacheutil`、system `dig`、`dig @1.1.1.1`、`dig @8.8.8.8` 皆可解析 `ftm.com.tw` 到 `60.249.109.44`。
- health curl 可回 `HTTP 200`，`remote_ip=60.249.109.44`。
- `curl --resolve ftm.com.tw:443:60.249.109.44` 也可回 `HTTP 200`，表示後端 HTTPS endpoint 可達。

目前判斷：latest automation run 的 `curl (6)` 比較像 automation runtime 當下 DNS resolver 抖動，而不是 API endpoint 永久失效。後續若 manual shell socket DNS 成功、但 automation runtime socket DNS 失敗，優先查 scheduler/runtime network context，不要先查 backend/API/Canva。

## Runtime Network Blocker

2026-05-28 `chat-g-20260528113920` 已確認不是單純 DNS 抖動，而是 automation runtime network context 被限制。

已確認：

- automation runtime cwd 正確：`/Users/phoebe/.codex/worktrees/e89a/課程招生 - 系統`。
- automation runtime `socket.gethostbyname_ex("ftm.com.tw")` 連續 3 次失敗。
- automation runtime `dns_config_sample` 顯示 `No DNS configuration available`。
- automation runtime `dig` 顯示 `bind: Operation not permitted`。
- manual shell 從同一 cwd 可解析 `ftm.com.tw -> 60.249.109.44`。
- 最新 automation archived session 的 developer permissions 顯示 `Network access is restricted`。

結論：scheduler / automation runtime 目前沒有正常 DNS / outbound network capability。這不是 stale worktree、backend API 或 Canva 產圖邏輯問題。

詳見 `docs/CHAT_G_RUNTIME_NETWORK_BLOCKER.md`。

## 剩餘風險

- 若下一輪 automation 在 `local` mode 下仍從 `4938`、`4d27`、`1210`、`945c` 或其他非 `e89a` cwd 啟動，代表問題不在 repo 或 `automation.toml` 內容，而是 scheduler / run session cache 層仍有舊上下文。
- 若發生上述情況，應刪除並重建 `chat-g-canva-proposals-automation`，或由 Codex app 層清除 stale scheduler session。

## 下一輪觀察標準

下一輪 Chat G automation 必須先回報：

- actual cwd 是否等於 `/Users/phoebe/.codex/worktrees/e89a/課程招生 - 系統`
- 若相等，必須先執行 `scripts/chat-g-network-preflight.sh`。
- preflight 必須先通過 `socket.gethostbyname_ex("ftm.com.tw")`，再做 health endpoint probe。
- preflight script exit 0 後才可 claim。
- preflight script exit 6 或記錄 `dns_resolution_failed` 時，必須停止且不可 claim。
- 若不相等，直接停止並記錄 `stale_worktree_context`，不得做任何 proposal 工作。
