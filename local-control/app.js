const $ = (selector) => document.querySelector(selector);

const state = {
  busy: false,
  lastPayload: null,
};

const request = async (path, options = {}) => {
  const response = await fetch(path, {
    headers: { "content-type": "application/json" },
    ...options,
  });
  const payload = await response.json();
  if (!response.ok) throw new Error(payload.error || JSON.stringify(payload));
  return payload;
};

const setBusy = (busy) => {
  state.busy = busy;
  for (const button of document.querySelectorAll("button")) button.disabled = busy;
};

const setStatus = (payload) => {
  const pill = $("#statusPill");
  const active = payload.activeRun && !payload.activeRun.done;
  const launchOk = payload.launchctl && payload.launchctl.code === 0;
  pill.className = `pill ${active ? "warn" : launchOk ? "ok" : "warn"}`;
  pill.textContent = active ? "執行中" : launchOk ? "待命中" : "需檢查";
};

const renderSummary = (payload) => {
  const active = payload.activeRun;
  const rows = [
    ["Runner", payload.runner],
    ["LaunchAgent", payload.launchAgent],
    ["排程狀態", payload.launchctl?.code === 0 ? "已載入" : "讀取失敗"],
    ["目前執行", active ? `${active.runId} / ${active.done ? `結束 ${active.exitCode}` : "執行中"}` : "沒有執行中任務"],
    ["Log 目錄", "/Users/phoebe/.codex/automations/chat-g-canva-proposals-automation/local-logs"],
  ];
  $("#summary").innerHTML = rows.map(([key, value]) => `<dt>${key}</dt><dd>${escapeHtml(String(value || ""))}</dd>`).join("");
};

const renderLogs = (payload) => {
  $("#logList").innerHTML = (payload.logs || [])
    .map((item) => {
      const time = new Date(item.mtimeMs).toLocaleString("zh-TW", { hour12: false });
      return `<li><strong>${escapeHtml(item.name)}</strong><span>${time}</span></li>`;
    })
    .join("");
};

const renderOutput = (payload) => {
  const active = payload.activeRun && !payload.activeRun.done
    ? `ACTIVE RUN\n${payload.activeRun.stdout || ""}\n${payload.activeRun.stderr || ""}\n\n`
    : "";
  $("#latestLog").textContent = active + (payload.latestLog || "尚無 log");
  $("#memoryLog").textContent = payload.memory || "尚無 memory";
};

const refresh = async () => {
  const payload = await request("/api/status");
  state.lastPayload = payload;
  setStatus(payload);
  renderSummary(payload);
  renderLogs(payload);
  renderOutput(payload);
};

const runAction = async (path) => {
  setBusy(true);
  try {
    await request(path, { method: "POST" });
    await refresh();
  } catch (error) {
    alert(error.message);
  } finally {
    setBusy(false);
  }
};

const escapeHtml = (value) =>
  value.replace(/[&<>"']/g, (char) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#039;",
  })[char]);

$("#dryRunBtn").addEventListener("click", () => runAction("/api/dry-run"));
$("#fullRunBtn").addEventListener("click", () => {
  const ok = confirm("正式執行會通過 preflight 後啟動 Chat G，可能會 claim 待處理案件。要繼續嗎？");
  if (ok) runAction("/api/full-run");
});
$("#kickstartBtn").addEventListener("click", () => {
  const ok = confirm("觸發排程會請 launchd 立刻跑一次正式 Chat G worker，可能會 claim 待處理案件。要繼續嗎？");
  if (ok) runAction("/api/kickstart");
});
$("#refreshBtn").addEventListener("click", refresh);
$("#copyLogBtn").addEventListener("click", () => navigator.clipboard.writeText($("#latestLog").textContent));
$("#copyMemoryBtn").addEventListener("click", () => navigator.clipboard.writeText($("#memoryLog").textContent));

refresh();
setInterval(refresh, 8000);
