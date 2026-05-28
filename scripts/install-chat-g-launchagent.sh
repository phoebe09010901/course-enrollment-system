#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
AUTOMATION_DIR="${CHAT_G_AUTOMATION_DIR:-$HOME/.codex/automations/chat-g-canva-proposals-automation}"
LAUNCH_AGENTS_DIR="$HOME/Library/LaunchAgents"
LAUNCH_AGENT_PATH="${LAUNCH_AGENTS_DIR}/com.phoebe.chat-g-canva-worker.plist"
LABEL="com.phoebe.chat-g-canva-worker"
START_INTERVAL="${CHAT_G_LAUNCHD_INTERVAL_SECONDS:-3600}"
CODEX_BIN="${CODEX_BIN:-/Applications/Codex.app/Contents/Resources/codex}"
API_KEY="${ADMISSION_API_KEY:-admission-api-20260528-chat-a-trigger}"

mkdir -p "$AUTOMATION_DIR" "$LAUNCH_AGENTS_DIR"

cat > "$LAUNCH_AGENT_PATH" <<PLIST
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
  <key>Label</key>
  <string>${LABEL}</string>

  <key>ProgramArguments</key>
  <array>
    <string>${PROJECT_DIR}/scripts/chat-g-local-runner.sh</string>
  </array>

  <key>WorkingDirectory</key>
  <string>${PROJECT_DIR}</string>

  <key>StartInterval</key>
  <integer>${START_INTERVAL}</integer>

  <key>RunAtLoad</key>
  <false/>

  <key>StandardOutPath</key>
  <string>${AUTOMATION_DIR}/local-launchd.stdout.log</string>

  <key>StandardErrorPath</key>
  <string>${AUTOMATION_DIR}/local-launchd.stderr.log</string>

  <key>EnvironmentVariables</key>
  <dict>
    <key>PATH</key>
    <string>/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin:/Applications/Codex.app/Contents/Resources</string>
    <key>ADMISSION_API_KEY</key>
    <string>${API_KEY}</string>
    <key>CHAT_G_AUTOMATION_DIR</key>
    <string>${AUTOMATION_DIR}</string>
    <key>CODEX_BIN</key>
    <string>${CODEX_BIN}</string>
  </dict>
</dict>
</plist>
PLIST

launchctl bootout "gui/$(id -u)" "$LAUNCH_AGENT_PATH" >/dev/null 2>&1 || true
launchctl bootstrap "gui/$(id -u)" "$LAUNCH_AGENT_PATH"

echo "Installed launch agent:"
echo "  ${LAUNCH_AGENT_PATH}"
echo "Project dir:"
echo "  ${PROJECT_DIR}"
echo "Automation dir:"
echo "  ${AUTOMATION_DIR}"
echo
echo "Run now:"
echo "  launchctl kickstart -k gui/$(id -u)/${LABEL}"
