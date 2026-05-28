#!/usr/bin/env bash
set -u

PROJECT_DIR="/Users/phoebe/.codex/worktrees/e89a/課程招生 - 系統"
AUTOMATION_DIR="/Users/phoebe/.codex/automations/chat-g-canva-proposals-automation"
LOG_DIR="${AUTOMATION_DIR}/local-logs"
LOCK_DIR="${AUTOMATION_DIR}/local-runner.lock"
CODEX_BIN="/Applications/Codex.app/Contents/Resources/codex"
PROMPT_FILE="${PROJECT_DIR}/scripts/chat-g-local-worker-prompt.md"
PREFLIGHT_SCRIPT="${PROJECT_DIR}/scripts/chat-g-network-preflight.sh"
RUN_ID="${WORKER_RUN_ID:-chat-g-local-$(date +%Y%m%d%H%M%S)}"
DRY_RUN="${CHAT_G_LOCAL_DRY_RUN:-0}"

mkdir -p "$LOG_DIR" "$AUTOMATION_DIR"

log_file="${LOG_DIR}/${RUN_ID}.log"

log() {
  printf '[%s] %s\n' "$(date '+%Y-%m-%dT%H:%M:%S%z')" "$*" | tee -a "$log_file"
}

if ! mkdir "$LOCK_DIR" 2>/dev/null; then
  log "status=skipped reason=lock_exists run_id=${RUN_ID}"
  exit 0
fi

cleanup() {
  rmdir "$LOCK_DIR" 2>/dev/null || true
}
trap cleanup EXIT

cd "$PROJECT_DIR" || {
  log "status=blocked error_code=missing_project_dir project_dir=${PROJECT_DIR}"
  exit 20
}

log "run_id=${RUN_ID}"
log "cwd=$(pwd)"
log "dry_run=${DRY_RUN}"

CHAT_G_RUNTIME_CONTEXT=local_launchd \
WORKER_RUN_ID="$RUN_ID" \
ADMISSION_API_KEY="${ADMISSION_API_KEY:-admission-api-20260528-chat-a-trigger}" \
"$PREFLIGHT_SCRIPT" >> "$log_file" 2>&1
preflight_status=$?

if [[ "$preflight_status" -ne 0 ]]; then
  log "status=blocked stage=preflight exit_code=${preflight_status}"
  exit "$preflight_status"
fi

log "status=preflight_passed"

if [[ "$DRY_RUN" = "1" ]]; then
  log "status=dry_run_complete"
  exit 0
fi

if [[ ! -x "$CODEX_BIN" ]]; then
  log "status=blocked error_code=codex_cli_missing codex_bin=${CODEX_BIN}"
  exit 30
fi

if [[ ! -f "$PROMPT_FILE" ]]; then
  log "status=blocked error_code=prompt_missing prompt_file=${PROMPT_FILE}"
  exit 31
fi

log "status=starting_codex_exec"

CHAT_G_RUNTIME_CONTEXT=local_launchd \
WORKER_RUN_ID="$RUN_ID" \
ADMISSION_API_KEY="${ADMISSION_API_KEY:-admission-api-20260528-chat-a-trigger}" \
"$CODEX_BIN" exec \
  --dangerously-bypass-approvals-and-sandbox \
  --dangerously-bypass-hook-trust \
  --cd "$PROJECT_DIR" \
  --output-last-message "${LOG_DIR}/${RUN_ID}.final.md" \
  - < "$PROMPT_FILE" >> "$log_file" 2>&1
codex_status=$?

log "status=codex_exec_finished exit_code=${codex_status}"
exit "$codex_status"
