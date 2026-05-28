#!/usr/bin/env bash
set -u

EXPECTED_CWD="/Users/phoebe/.codex/worktrees/e89a/課程招生 - 系統"
AUTOMATION_MEMORY="/Users/phoebe/.codex/automations/chat-g-canva-proposals-automation/memory.md"
HEALTH_URL="https://ftm.com.tw/demo/admission-system/api/chat-a-trigger/health.php"
HOSTNAME_TO_CHECK="ftm.com.tw"
API_KEY="${ADMISSION_API_KEY:-admission-api-20260528-chat-a-trigger}"
WORKER_RUN_ID="${WORKER_RUN_ID:-chat-g-$(date +%Y%m%d%H%M%S)}"
RUNTIME_CONTEXT="${CHAT_G_RUNTIME_CONTEXT:-manual_shell}"
MAX_ATTEMPTS="${MAX_ATTEMPTS:-3}"
SLEEP_SECONDS="${SLEEP_SECONDS:-3}"

log() {
  printf '%s\n' "$*"
}

append_memory_header() {
  {
    printf '\n## %s\n\n' "$(date '+%Y-%m-%dT%H:%M:%S%z')"
    printf -- '- worker_run_id: `%s`\n' "$WORKER_RUN_ID"
    printf -- '- script: `scripts/chat-g-network-preflight.sh`\n'
    printf -- '- runtime_context: `%s`\n' "$RUNTIME_CONTEXT"
    printf -- '- actual_cwd: `%s`\n' "$PWD"
    printf -- '- expected_cwd: `%s`\n' "$EXPECTED_CWD"
    printf -- '- health_endpoint: `%s`\n' "$HEALTH_URL"
  } >> "$AUTOMATION_MEMORY"
}

append_memory_line() {
  printf -- '- %s\n' "$*" >> "$AUTOMATION_MEMORY"
}

append_memory_block() {
  local title="$1"
  local body="$2"
  {
    printf -- '- %s:\n\n' "$title"
    printf '```text\n'
    printf '%s\n' "$body"
    printf '```\n'
  } >> "$AUTOMATION_MEMORY"
}

run_capture() {
  local output
  output="$("$@" 2>&1)"
  local status=$?
  printf '%s' "$output"
  return "$status"
}

append_memory_header

log "worker_run_id=$WORKER_RUN_ID"
log "runtime_context=$RUNTIME_CONTEXT"
log "actual_cwd=$PWD"
log "expected_cwd=$EXPECTED_CWD"

if [[ "$PWD" != "$EXPECTED_CWD" ]]; then
  append_memory_line 'status: `blocked`'
  append_memory_line 'error_code: `stale_worktree_context`'
  append_memory_line 'result: stopped before DNS, health, claim, callback, or Canva generation.'
  log "error_code=stale_worktree_context"
  exit 20
fi

append_memory_line 'cwd_preflight: `pass`'

dns_config="$(scutil --dns 2>&1 | sed -n '1,80p')"
append_memory_block 'dns_config_sample' "$dns_config"

attempt=1
last_dns_error=""
last_curl_error=""
last_curl_exit=0

while [[ "$attempt" -le "$MAX_ATTEMPTS" ]]; do
  log "attempt=$attempt"

  socket_dns="$(run_capture python3 - "$HOSTNAME_TO_CHECK" <<'PY'
import json
import socket
import sys

host = sys.argv[1]
try:
    canonical_name, aliases, addresses = socket.gethostbyname_ex(host)
    print(json.dumps({
        "ok": True,
        "host": host,
        "canonical_name": canonical_name,
        "aliases": aliases,
        "addresses": addresses,
    }, ensure_ascii=False, sort_keys=True))
except Exception as exc:
    print(json.dumps({
        "ok": False,
        "host": host,
        "error_type": exc.__class__.__name__,
        "error": str(exc),
    }, ensure_ascii=False, sort_keys=True))
    sys.exit(1)
PY
)"
  socket_dns_status=$?
  append_memory_line "attempt_${attempt}_socket_dns_status: \`$socket_dns_status\`"
  append_memory_block "attempt_${attempt}_socket_gethostbyname_ex" "$socket_dns"

  system_dns="$(run_capture dscacheutil -q host -a name "$HOSTNAME_TO_CHECK")"
  system_dns_status=$?
  dig_system="$(run_capture dig +time=3 +tries=1 "$HOSTNAME_TO_CHECK" A)"
  dig_system_status=$?
  dig_cloudflare="$(run_capture dig @1.1.1.1 +time=3 +tries=1 "$HOSTNAME_TO_CHECK" A)"
  dig_cloudflare_status=$?

  append_memory_line "attempt_${attempt}_system_dns_status: \`$system_dns_status\`"
  append_memory_block "attempt_${attempt}_system_dns" "$system_dns"
  append_memory_line "attempt_${attempt}_dig_system_status: \`$dig_system_status\`"
  append_memory_block "attempt_${attempt}_dig_system" "$dig_system"
  append_memory_line "attempt_${attempt}_dig_cloudflare_status: \`$dig_cloudflare_status\`"
  append_memory_block "attempt_${attempt}_dig_cloudflare" "$dig_cloudflare"

  if [[ "$socket_dns_status" -ne 0 ]]; then
    last_dns_error="$socket_dns"
    append_memory_line "attempt_${attempt}_health_probe: \`skipped_socket_dns_failed\`"
    if [[ "$attempt" -lt "$MAX_ATTEMPTS" ]]; then
      sleep "$SLEEP_SECONDS"
    fi
    attempt=$((attempt + 1))
    continue
  fi

  curl_output="$(curl -sS -o /tmp/chat-g-health-preflight-body.json \
    -w 'curl_exit=%{exitcode}\nhttp_code=%{http_code}\nremote_ip=%{remote_ip}\ntime_namelookup=%{time_namelookup}\ntime_connect=%{time_connect}\ntime_appconnect=%{time_appconnect}\ntime_total=%{time_total}\n' \
    --connect-timeout 10 \
    --max-time 20 \
    -H "X-Admission-Api-Key: ${API_KEY}" \
    "$HEALTH_URL" 2>&1)"
  last_curl_exit=$?
  health_body="$(sed -n '1,20p' /tmp/chat-g-health-preflight-body.json 2>/dev/null || true)"

  append_memory_line "attempt_${attempt}_health_curl_status: \`$last_curl_exit\`"
  append_memory_block "attempt_${attempt}_health_curl" "$curl_output"
  append_memory_block "attempt_${attempt}_health_body" "$health_body"

  if [[ "$last_curl_exit" -eq 0 ]] && printf '%s\n' "$curl_output" | rg -q '^http_code=200$'; then
    append_memory_line 'status: `preflight_passed`'
    append_memory_line 'result: DNS and health preflight passed; automation may proceed to claim gate.'
    log "preflight_status=pass"
    exit 0
  fi

  last_curl_error="$curl_output"

  if [[ "$attempt" -lt "$MAX_ATTEMPTS" ]]; then
    sleep "$SLEEP_SECONDS"
  fi

  attempt=$((attempt + 1))
done

if [[ -n "$last_dns_error" ]]; then
  append_memory_line 'status: `blocked`'
  append_memory_line 'attempts: `3`'
  append_memory_line 'error_code: `dns_resolution_failed`'
  append_memory_block 'last_socket_dns_error' "$last_dns_error"
  append_memory_line 'result: stopped before health, claim, success/fail callback, and Canva generation because socket DNS failed in all bounded retries.'
  log "error_code=dns_resolution_failed"
  exit 6
fi

if printf '%s\n' "$last_curl_error" | rg -q 'Could not resolve host|curl_exit=6'; then
  append_memory_line 'status: `blocked`'
  append_memory_line "attempts: \`${MAX_ATTEMPTS}\`"
  append_memory_line 'error_code: `health_dns_resolution_failed`'
  append_memory_line 'result: socket DNS passed, but health curl failed at DNS resolution; stop before claim, success/fail callback, and Canva generation.'
  log "error_code=health_dns_resolution_failed"
  exit 6
fi

append_memory_line 'status: `blocked`'
append_memory_line "attempts: \`${MAX_ATTEMPTS}\`"
append_memory_line 'error_code: `health_preflight_failed`'
append_memory_line 'result: stopped before claim, success/fail callback, and Canva generation because health preflight did not pass.'
log "error_code=health_preflight_failed"
exit 7
