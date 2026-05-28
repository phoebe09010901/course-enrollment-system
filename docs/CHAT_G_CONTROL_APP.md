# Chat G Control App

## Purpose

This is a small local control app for the Chat G Canva proposal worker.

It is meant for manual operation and diagnosis when you want to know:

- whether local DNS can resolve `ftm.com.tw`,
- whether the health endpoint is reachable,
- whether the local `launchd` worker is loaded,
- what the latest worker log says,
- whether the latest failure happened before claim, during claim, or after claim.

## Start The App

From the project root:

```bash
scripts/chat-g-control-app.sh
```

Then open:

```text
http://127.0.0.1:8789
```

Optional custom port:

```bash
CHAT_G_CONTROL_PORT=8790 scripts/chat-g-control-app.sh
```

## Buttons

### 測試網路

Runs `scripts/chat-g-local-runner.sh` in dry-run mode.

This only runs the local preflight checks:

1. cwd validation
2. DNS probe
3. health probe

It does not claim a project and does not start Canva proposal generation.

Use this first when diagnosing DNS or API access issues.

### 正式執行

Runs `scripts/chat-g-local-runner.sh` in full mode.

After preflight passes, it can start the Chat G worker and may claim a pending project.

The UI shows a confirmation dialog before starting this action.

### 觸發排程

Runs:

```bash
launchctl kickstart -k gui/$(id -u)/com.phoebe.chat-g-canva-worker
```

This asks the installed `launchd` schedule to run immediately.

The UI shows a confirmation dialog because this can also claim a pending project.

### 重新整理

Reloads current worker status, latest per-run log, and automation memory.

## Files

Local app:

```text
local-control/
```

Start script:

```text
scripts/chat-g-control-app.sh
```

Runner:

```text
scripts/chat-g-local-runner.sh
```

Logs:

```text
/Users/phoebe/.codex/automations/chat-g-canva-proposals-automation/local-logs/
```

Automation memory:

```text
/Users/phoebe/.codex/automations/chat-g-canva-proposals-automation/memory.md
```

## Safety Notes

- The app binds to `127.0.0.1`, so it is local-only.
- The app does not accept arbitrary shell commands.
- The app prevents overlapping runs inside the same server process.
- The runner also has a lock directory, so a second run should fail safely if one is already active.
- Use `測試網路` before `正式執行` when the issue may be DNS, network, or health endpoint related.
