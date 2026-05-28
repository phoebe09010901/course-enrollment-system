# Chat G Local Scheduler

## Purpose

Chat G Canva proposal automation is now scheduled by macOS `launchd` on Phoebe's Mac because Codex automation cron has restricted network access.

This local scheduler uses the Mac's normal network context, so it can resolve `ftm.com.tw` and call the backend API.

Current operating model: `semi_automated_canva_proposals`.

That means the local scheduler is the official runtime for:

- cwd validation
- DNS / health preflight
- atomic claim
- project-level success / failure callbacks

It does not promise that every run can finish the Canva generation step without manual help. If headless Canva capability or required template docs are missing, the worker should claim safely, then route the project into a documented manual / interactive Canva handoff path rather than pretending full automation exists.

## Installed LaunchAgent

```text
/Users/phoebe/Library/LaunchAgents/com.phoebe.chat-g-canva-worker.plist
```

Source plist in repo:

```text
launchd/com.phoebe.chat-g-canva-worker.plist
```

Current schedule:

```text
StartInterval = 3600
```

This means it runs once per hour while the Mac is awake and logged in.

## Runner

```text
scripts/chat-g-local-runner.sh
```

The runner:

- creates a local run id: `chat-g-local-<YYYYMMDDHHMMSS>`,
- prevents overlapping runs with a lock directory,
- runs `scripts/chat-g-network-preflight.sh` with `CHAT_G_RUNTIME_CONTEXT=local_launchd`,
- stops if DNS / health preflight fails,
- starts local Codex CLI only after preflight passes.

In semi-automated mode, successful preflight does not imply that the whole proposal batch can be completed unattended. The worker prompt owns the post-claim capability decision.

## Preflight

```text
scripts/chat-g-network-preflight.sh
```

Preflight order:

1. Check cwd.
2. Run `socket.gethostbyname_ex("ftm.com.tw")`.
3. Run system / public DNS probes.
4. Run health endpoint probe.
5. Stop before claim if DNS or health fails.

## Logs

LaunchAgent stdout:

```text
/Users/phoebe/.codex/automations/chat-g-canva-proposals-automation/local-launchd.stdout.log
```

LaunchAgent stderr:

```text
/Users/phoebe/.codex/automations/chat-g-canva-proposals-automation/local-launchd.stderr.log
```

Per-run logs:

```text
/Users/phoebe/.codex/automations/chat-g-canva-proposals-automation/local-logs/
```

Automation memory:

```text
/Users/phoebe/.codex/automations/chat-g-canva-proposals-automation/memory.md
```

## Commands

Check status:

```bash
launchctl print gui/$(id -u)/com.phoebe.chat-g-canva-worker
```

Run now:

```bash
launchctl kickstart -k gui/$(id -u)/com.phoebe.chat-g-canva-worker
```

Unload:

```bash
launchctl bootout gui/$(id -u) /Users/phoebe/Library/LaunchAgents/com.phoebe.chat-g-canva-worker.plist
```

Reload:

```bash
launchctl bootstrap gui/$(id -u) /Users/phoebe/Library/LaunchAgents/com.phoebe.chat-g-canva-worker.plist
```

Dry run:

```bash
CHAT_G_LOCAL_DRY_RUN=1 scripts/chat-g-local-runner.sh
```

## Local Control App

A small local control app is available for manual runs and diagnosis:

```bash
scripts/chat-g-control-app.sh
```

Open:

```text
http://127.0.0.1:8789
```

The app can run a dry network test, start a confirmed full run, kickstart the installed `launchd` worker, and show the latest logs and automation memory.

Full details:

```text
docs/CHAT_G_CONTROL_APP.md
```

## Important Notes

- The Mac must be awake, logged in, and online.
- If the Mac sleeps or disconnects, that run is skipped or fails safely.
- The old Codex automation cron is paused because its runtime has `Network access is restricted`.
- If Cloudflare Worker Cron or another cloud worker is introduced later, this local scheduler can be disabled.
- Missing `docs/TEMPLATE_REFERENCE.md` or unavailable headless Canva persistence should be treated as project-level/manual-handoff blockers, not as reasons to bypass claim forever.
