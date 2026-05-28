# Chat G Runtime Network Blocker

## Summary

Chat G automation is currently blocked before API / Canva logic because the automation runtime has restricted network access.

This is not a stale worktree issue, not a backend API contract issue, and not a Canva proposal generation issue.

## Latest Blocked Run

- Date: 2026-05-28
- automation id: `chat-g-canva-proposals-automation`
- worker_run_id: `chat-g-20260528113920`
- cwd: `/Users/phoebe/.codex/worktrees/e89a/課程招生 - 系統`
- error_code: `dns_resolution_failed`
- stop point: before health, claim, success/fail callback, and Canva generation

## Evidence

- Manual shell from the same cwd can resolve the host:

```text
socket.gethostbyname_ex("ftm.com.tw") -> 60.249.109.44
dig ftm.com.tw A -> 60.249.109.44
```

- Automation runtime from the same cwd cannot resolve the host:

```text
socket.gethostbyname_ex("ftm.com.tw") -> gaierror [Errno 8]
dns_config_sample -> No DNS configuration available
dig -> bind: Operation not permitted
```

- Latest automation archived session:

```text
/Users/phoebe/.codex/archived_sessions/rollout-2026-05-28T11-38-54-019e6ca9-e752-72d1-bcb2-e2a3828e59ba.jsonl
```

- The session developer permissions include:

```text
Network access is restricted
```

## Conclusion

The automation worker is running in a scheduler/runtime sandbox that does not expose normal DNS / outbound network capability. Because `dig` cannot bind a socket and Python `socket.gethostbyname_ex` cannot resolve a normal hostname, bounded retry is working correctly but cannot recover from this environment-level restriction.

## Required Fix

Chat G automation must run in a runtime with outbound network / DNS enabled, or Codex app automation needs a setting that grants network access to this cron.

Until the runtime has network access, the correct behavior is:

- stop at preflight,
- write `dns_resolution_failed`,
- do not call health,
- do not claim projects,
- do not call success/fail callbacks,
- do not start Canva generation.

## Non-Fixes

- Increasing DNS retry count will not fix a sandbox-level network restriction.
- Switching back to `worktree` execution mode will not fix DNS and may reintroduce stale worktree problems.
- Bypassing DNS with a hardcoded IP should not be used as the default fix because success/fail callbacks and future host changes still require a healthy runtime network context.
