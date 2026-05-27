# Template Reference

## 文件目的

本文件提供 Chat A / Canva 與 Chat D / Chat E 在產生三款樣板提案時的共同參考。它不是已完成的模板資料庫，目前先作為樣板挑選規則與提案格式的規格。

## 三款提案原則

每個課程專案預設產生 A / B / C 三款，三款應在視覺與內容策略上明顯不同，避免只是換色。

建議分工：

| proposal_code | 定位 | 適合情境 |
| --- | --- | --- |
| `A` | 信任專業型 | 高單價、顧問型、證照型、專業師資課程 |
| `B` | 活動轉換型 | 短期班、體驗課、招生檔期、限量名額 |
| `C` | 品牌故事型 | 親子、才藝、身心靈、地方品牌、講師個人品牌 |

## proposal 需要回填的欄位

Chat A / Canva 完成提案後，應回填到 `template_proposals`：

- `proposal_code`
- `proposal_name`
- `primary_template_id`
- `secondary_template_id`
- `source_url`
- `secondary_source_url`
- `canva_url`
- `notes`

## Canva ready 品質門檻

`canva_url` 不能只代表「有建立 Canva 檔案」。該 Canva 檔案必須是真正完成客製化的課程招生頁視覺提案。

不可標記為 ready 的情況：

- Canva 裡只是嵌入原始模板 demo 頁。
- 畫面仍出現原模板導覽選單、範本清單、demo 地址、demo 電話、英文室內設計文案等 placeholder。
- 畫面是瀏覽器預設樣式、raw HTML、CSS 未載入或明顯破版。
- 未使用表單送出的課程名稱、課程內容與圖片素材。

若發生上述任一情況，Chat A 必須回報 `canva_generation_failed`，不得寫入 `proposal_ready`。

## 課程類型建議對應

| course_type | A 款建議 | B 款建議 | C 款建議 |
| --- | --- | --- | --- |
| 專業證照 | 專業可信、成果導向 | 報名期限、考照衝刺 | 學員成功故事 |
| 親子兒童 | 安全信任、師資介紹 | 寒暑假活動、梯次清楚 | 溫暖品牌故事 |
| 才藝技能 | 作品成果展示 | 體驗課導流 | 講師風格與學習歷程 |
| 身心靈成長 | 安心陪伴、專業背景 | 小班限額、立即預約 | 情緒共鳴與轉變故事 |
| 企業培訓 | 商務專業、成效指標 | 方案比較、詢問表單 | 案例與品牌方法論 |

## 命名規則

`primary_template_id` 建議格式：

```text
course-{course_type_slug}-{strategy}-v1
```

範例：

- `course-cert-professional-v1`
- `course-kids-campaign-v1`
- `course-wellness-story-v1`

`secondary_template_id` 可用於同一款中的輔助頁型或延伸素材：

- 報名 CTA 區塊
- 講師介紹版型
- FAQ 區塊
- LINE 訊息卡片

## 待補資料

- 實際 Canva template id。
- 實際 source URL。
- 每種 course_type 的視覺 token。
- 與 `styles/` 目錄的 style id 對應。
