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

## 剩餘風險

- 若下一輪 automation 在 `local` mode 下仍從 `4938`、`4d27`、`1210`、`945c` 或其他非 `e89a` cwd 啟動，代表問題不在 repo 或 `automation.toml` 內容，而是 scheduler / run session cache 層仍有舊上下文。
- 若發生上述情況，應刪除並重建 `chat-g-canva-proposals-automation`，或由 Codex app 層清除 stale scheduler session。

## 下一輪觀察標準

下一輪 Chat G automation 必須先回報：

- actual cwd 是否等於 `/Users/phoebe/.codex/worktrees/e89a/課程招生 - 系統`
- 若相等，才可進行 DNS / health / claim。
- 若不相等，直接停止並記錄 `stale_worktree_context`，不得做任何 proposal 工作。
