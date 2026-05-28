#!/usr/bin/env bash
set -u

PROJECT_DIR="/Users/phoebe/.codex/worktrees/e89a/課程招生 - 系統"
PORT="${CHAT_G_CONTROL_PORT:-8789}"

cd "$PROJECT_DIR" || exit 20

echo "Chat G control app: http://127.0.0.1:${PORT}"
node local-control/chat-g-control-server.mjs
