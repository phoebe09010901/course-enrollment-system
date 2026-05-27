# Remote Collaboration

## 文件目的

本文件說明另一台電腦如何接手「課程招生 - 系統」專案。目標是讓協作者可以從 GitHub 取得程式碼、知道目前使用哪個測試環境、如何設定本機敏感資料，以及如何結束工作並推回 git。

## GitHub 專案

SSH：

```bash
git clone git@github.com:phoebe09010901/course-enrollment-system.git
```

HTTPS：

```bash
git clone https://github.com/phoebe09010901/course-enrollment-system.git
```

目前後端 / 後台 / LINE intake 工作分支：

```bash
git switch codex/line-intake-admin-updates
```

若另一台電腦要接最新工作：

```bash
git fetch origin
git switch codex/line-intake-admin-updates
git pull --ff-only
```

## 測試環境

目前以 `ftm.com.tw` 作為測試環境：

```text
https://ftm.com.tw/demo/admission-system
```

後台：

```text
https://ftm.com.tw/demo/admission-system/admin/
```

LINE intake API：

```text
POST https://ftm.com.tw/demo/admission-system/api/line-intakes
```

## 敏感設定

不要把 FTP、cPanel、DB 密碼或 API key commit 進 repo。

本機設定方式：

```bash
cp config/local.example.php config/local.php
```

然後在 `config/local.php` 填入：

- DB host
- DB name
- DB user
- DB password
- API key
- Cloudflare R2 account ID
- Cloudflare R2 access key ID
- Cloudflare R2 secret access key
- Cloudflare R2 bucket
- Cloudflare R2 public base URL

`config/local.php` 已被 `.gitignore` 排除。

## Cloudflare R2 圖片素材

公開課程資料表單支援把老師照片、作品照片與教室照片上傳到 Cloudflare R2。

啟用條件是以下設定皆存在：

```text
CLOUDFLARE_R2_ACCOUNT_ID
CLOUDFLARE_R2_ACCESS_KEY_ID
CLOUDFLARE_R2_SECRET_ACCESS_KEY
CLOUDFLARE_R2_BUCKET
CLOUDFLARE_R2_PUBLIC_BASE_URL
```

R2 物件 key 規則：

```text
admission-system/course-intakes/{record_id}/{intake_id}/{teacher|works|classroom}/{file_name}
```

MySQL 只存圖片瀏覽網址與素材資訊，主要在：

```text
course_intakes.course_assets_json
course_intakes.image_fields_json
course_intakes.photo_asset_statuses_json
```

若 R2 尚未設定，表單會退回使用本機 `public/uploads/course-intakes/`，避免測試環境中斷。

## LINE Intake API 合約

Cloudflare Worker 送入：

```text
type = line_intake_confirmed
```

目前 API 支援：

- `client.user_name`
- `client.email`
- `client.email_status`
- `client.line_id_link`
- `client.line_id_link_status`
- `client.needs_human_contact_review`
- `line.userId`
- `line.displayName`
- `course_project`
- `expected_launch_date_status`
- `expected_start_date_status`
- `image_fields`
- `course_assets`

成功回傳：

```json
{
  "ok": true,
  "client_id": 123,
  "record_id": "CLI-20260527-001",
  "intake_id": 456
}
```

失敗時需回非 2xx 與錯誤 JSON，讓 Worker 保留暫存資料。

## 工作約定

### 開啟專案

使用者說「開啟專案」時：

```bash
git fetch origin
git pull --ff-only
```

若目前不在正確分支，先確認後再切分支。

### 結束專案

使用者說「結束專案」時：

1. `git status --short --branch`
2. 檢查是否有敏感資訊。
3. `git add` 需要提交的檔案。
4. `git commit`
5. `git push`

若工作在 detached HEAD，先建立 `codex/` 開頭的分支再 push。

## 接手時建議先讀

1. `docs/PROJECT_CONTEXT.md`
2. `docs/PROJECT_STATUS.md`
3. `docs/REMOTE_COLLABORATION.md`
4. `docs/COLLABORATION_SETUP.md`
5. `docs/BACKEND_AUTOMATION_FLOW.md`
6. `docs/FORM_SCHEMA.md`

## 目前注意事項

- GitHub repo 內是可協作的程式碼與規格。
- `ftm.com.tw` 遠端測試環境有部分檔案比本機 repo 更新，尤其是後台實際 PHP 檔案。
- 若要改後台功能，優先讀遠端現況再改，避免覆蓋既有功能。
- 完成重要遠端更新時，要保留 `.bak-YYYYMMDDHHMMSS` 備份。
