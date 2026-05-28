import { createServer } from "node:http";
import { readFile, readdir, stat } from "node:fs/promises";
import { createReadStream } from "node:fs";
import { spawn } from "node:child_process";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(__dirname, "..");
const automationDir = "/Users/phoebe/.codex/automations/chat-g-canva-proposals-automation";
const logsDir = path.join(automationDir, "local-logs");
const runner = path.join(repoRoot, "scripts/chat-g-local-runner.sh");
const launchAgent = "/Users/phoebe/Library/LaunchAgents/com.phoebe.chat-g-canva-worker.plist";
const label = `gui/${process.getuid()}/com.phoebe.chat-g-canva-worker`;
const port = Number(process.env.CHAT_G_CONTROL_PORT || 8789);

let activeRun = null;

const json = (res, status, body) => {
  const data = JSON.stringify(body, null, 2);
  res.writeHead(status, {
    "content-type": "application/json; charset=utf-8",
    "cache-control": "no-store",
  });
  res.end(data);
};

const text = (res, status, body, type = "text/plain; charset=utf-8") => {
  res.writeHead(status, {
    "content-type": type,
    "cache-control": "no-store",
  });
  res.end(body);
};

const runCommand = (command, args, options = {}) =>
  new Promise((resolve) => {
    const child = spawn(command, args, {
      cwd: repoRoot,
      env: { ...process.env, ...(options.env || {}) },
      stdio: ["ignore", "pipe", "pipe"],
    });
    let stdout = "";
    let stderr = "";
    child.stdout.on("data", (chunk) => {
      stdout += chunk.toString();
    });
    child.stderr.on("data", (chunk) => {
      stderr += chunk.toString();
    });
    child.on("close", (code) => {
      resolve({ code, stdout, stderr });
    });
  });

const startRunner = (dryRun) => {
  if (activeRun && !activeRun.done) {
    return { ok: false, message: "A local Chat G run is already active.", run: activeRun };
  }

  const runId = `chat-g-ui-${dryRun ? "dry" : "full"}-${timestamp()}`;
  const child = spawn(runner, [], {
    cwd: repoRoot,
    env: {
      ...process.env,
      WORKER_RUN_ID: runId,
      CHAT_G_LOCAL_DRY_RUN: dryRun ? "1" : "0",
      ADMISSION_API_KEY: process.env.ADMISSION_API_KEY || "admission-api-20260528-chat-a-trigger",
    },
    stdio: ["ignore", "pipe", "pipe"],
  });

  activeRun = {
    runId,
    dryRun,
    startedAt: new Date().toISOString(),
    done: false,
    exitCode: null,
    stdout: "",
    stderr: "",
  };

  child.stdout.on("data", (chunk) => {
    activeRun.stdout += chunk.toString();
  });
  child.stderr.on("data", (chunk) => {
    activeRun.stderr += chunk.toString();
  });
  child.on("close", (code) => {
    activeRun.done = true;
    activeRun.exitCode = code;
    activeRun.finishedAt = new Date().toISOString();
  });

  return { ok: true, message: dryRun ? "Started network test." : "Started full Chat G run.", run: activeRun };
};

const timestamp = () => {
  const now = new Date();
  const pad = (value) => String(value).padStart(2, "0");
  return [
    now.getFullYear(),
    pad(now.getMonth() + 1),
    pad(now.getDate()),
    pad(now.getHours()),
    pad(now.getMinutes()),
    pad(now.getSeconds()),
  ].join("");
};

const latestLogFiles = async () => {
  try {
    const names = await readdir(logsDir);
    const rows = await Promise.all(
      names
        .filter((name) => name.endsWith(".log") || name.endsWith(".md"))
        .map(async (name) => {
          const filePath = path.join(logsDir, name);
          const info = await stat(filePath);
          return { name, path: filePath, mtimeMs: info.mtimeMs, size: info.size };
        }),
    );
    return rows.sort((a, b) => b.mtimeMs - a.mtimeMs).slice(0, 12);
  } catch {
    return [];
  }
};

const tailFile = async (filePath, maxBytes = 60000) => {
  try {
    const info = await stat(filePath);
    const start = Math.max(0, info.size - maxBytes);
    return await new Promise((resolve, reject) => {
      let data = "";
      const stream = createReadStream(filePath, { start, end: info.size });
      stream.on("data", (chunk) => {
        data += chunk.toString();
      });
      stream.on("error", reject);
      stream.on("end", () => resolve(data));
    });
  } catch (error) {
    return `Cannot read ${filePath}: ${error.message}`;
  }
};

const statusPayload = async () => {
  const launch = await runCommand("launchctl", ["print", label]);
  const logs = await latestLogFiles();
  const latestLog = logs[0] ? await tailFile(logs[0].path, 30000) : "";
  const memory = await tailFile(path.join(automationDir, "memory.md"), 30000);
  return {
    repoRoot,
    runner,
    launchAgent,
    label,
    activeRun,
    launchctl: {
      code: launch.code,
      output: launch.stdout || launch.stderr,
    },
    logs,
    latestLog,
    memory,
  };
};

const serveIndex = async (res) => {
  const html = await readFile(path.join(__dirname, "index.html"), "utf8");
  text(res, 200, html, "text/html; charset=utf-8");
};

const serveAsset = async (res, filename, type) => {
  const body = await readFile(path.join(__dirname, filename), "utf8");
  text(res, 200, body, type);
};

const server = createServer(async (req, res) => {
  try {
    const url = new URL(req.url || "/", `http://${req.headers.host}`);

    if (req.method === "GET" && url.pathname === "/") return serveIndex(res);
    if (req.method === "GET" && url.pathname === "/app.css") return serveAsset(res, "app.css", "text/css; charset=utf-8");
    if (req.method === "GET" && url.pathname === "/app.js") return serveAsset(res, "app.js", "text/javascript; charset=utf-8");
    if (req.method === "GET" && url.pathname === "/api/status") return json(res, 200, await statusPayload());

    if (req.method === "POST" && url.pathname === "/api/dry-run") {
      return json(res, 200, startRunner(true));
    }

    if (req.method === "POST" && url.pathname === "/api/full-run") {
      return json(res, 200, startRunner(false));
    }

    if (req.method === "POST" && url.pathname === "/api/kickstart") {
      const result = await runCommand("launchctl", ["kickstart", "-k", label]);
      return json(res, result.code === 0 ? 200 : 500, result);
    }

    if (req.method === "POST" && url.pathname === "/api/reload") {
      await runCommand("launchctl", ["bootout", `gui/${process.getuid()}`, launchAgent]);
      const result = await runCommand("launchctl", ["bootstrap", `gui/${process.getuid()}`, launchAgent]);
      return json(res, result.code === 0 ? 200 : 500, result);
    }

    json(res, 404, { error: "Not found" });
  } catch (error) {
    json(res, 500, { error: error.message, stack: error.stack });
  }
});

server.listen(port, "127.0.0.1", () => {
  console.log(`Chat G control app: http://127.0.0.1:${port}`);
});
