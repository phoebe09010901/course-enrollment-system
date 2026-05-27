# 課程招生 - 系統

課程招生頁系統目前處於 MVP 規格與 LINE AI 接待助理重構階段。

## 協作起步

```bash
git clone git@github.com:phoebe09010901/course-enrollment-system.git
cd course-enrollment-system
git fetch --all
git switch codex/collaboration-handoff
node --test tests/line-ai-worker-scenarios.test.mjs
```

沒有 GitHub SSH key 時可改用 HTTPS：

```bash
git clone https://github.com/phoebe09010901/course-enrollment-system.git
```

## 目前主線

- LINE AI 不再逐步收集客戶資料。
- LINE AI 改為課程招生頁系統接待助理。
- 客戶資料收集改由網頁表單處理。
- Cloudflare Worker 可部署檔案在 `cloudflare-workers/worker.js`。
- 本地測試在 `tests/line-ai-worker-scenarios.test.mjs`。

詳細交接請讀：

1. `docs/HANDOFF_FOR_NEW_COMPUTER.md`
2. `docs/PROJECT_STATUS.md`
3. `docs/COLLABORATION_SETUP.md`
4. `docs/LINE_AI_CUSTOMER_SERVICE_FLOW.md`
