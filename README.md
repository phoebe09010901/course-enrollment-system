# 課程招生 - 系統

## 協作接手

另一台電腦接手請先看：

```text
docs/REMOTE_COLLABORATION.md
docs/PROJECT_STATUS.md
docs/COLLABORATION_SETUP.md
```

目前測試環境：

```text
https://ftm.com.tw/demo/admission-system
```

公開課程資料表單：

```text
https://ftm.com.tw/demo/admission-system/public-course-intake.php
```

照片素材可設定 Cloudflare R2 儲存。啟用後 MySQL 只保留圖片瀏覽網址；未設定 R2 時會使用本機 `public/uploads/course-intakes/` 作為測試備援。

公開表單可設定 Cloudflare Turnstile。啟用後，後端會驗證 Turnstile token，驗證失敗不會寫入 MySQL 或處理圖片。

## LINE Intake API

Endpoint:

```text
POST /api/line-intakes
```

Auth:

```text
X-Admission-Api-Key: {ADMISSION_API_KEY}
```

也支援：

```text
Authorization: Bearer {ADMISSION_API_KEY}
```

設定方式：

1. 套用 `database/migrations/001_create_line_intakes.sql`。
2. 複製 `config/local.example.php` 為 `config/local.php`。
3. 填入 DB 連線與 `api_key`。
4. Cloudflare Worker 將 LINE intake JSON POST 到 `/api/line-intakes`。
5. 後台 `admin/clients.php` 會讀取 `clients` 與 `course_intakes` 顯示客戶資料。

範例 payload:

```json
{
  "source": "line_ai",
  "line": {
    "user_id": "Uxxxxxxxx",
    "display_name": "王小明"
  },
  "client": {
    "client_name": "王小明",
    "phone": "0912345678",
    "email": "demo@example.com",
    "brand_name": "小明英文教室",
    "location_area": "台北"
  },
  "course": {
    "course_name": "兒童英語口說班",
    "course_type": "親子兒童",
    "course_format": "實體課",
    "course_location": "台北市",
    "target_audience": "國小三到六年級",
    "course_features": "小班制、口說練習、成果發表"
  }
}
```
