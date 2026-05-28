#!/usr/bin/env bash
set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
PORT="${CHAT_G_CONTROL_PORT:-8789}"

cd "$PROJECT_DIR" || exit 20

echo "Chat G control app: http://127.0.0.1:${PORT}"
node local-control/chat-g-control-server.mjs
