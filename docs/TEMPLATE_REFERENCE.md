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

## Template Reference Pairing Rules

以下 10 組 pairing 是正式樣板參考資料。Chat A / Chat G 製作 Canva 樣板前，必須從這 10 組中挑選 primary / secondary template，不可使用 `example.com`、`localhost`、空白網址或未登錄的假 `template_id`。

每次製作 Canva 樣板前，Chat A / Chat G 必須指定：

- `primary_template_id`
- `secondary_template_id`
- `source_url`
- `secondary_source_url`

`primary_template_id` 代表主要版型骨架，決定 Hero 架構、主要排版節奏、頁面氣質與圖片使用方式。`secondary_template_id` 代表輔助視覺語言，用來補強作品展示、色彩比例、CTA 形式、圖片裁切與留白方式。

### TPL-001 LifeTime Pairing

- `primary_template_id`: `TPL-001`
- `primary_template_name`: `LifeTime`
- `source_url`: https://ld-wt73.template-help.com/wt_62267_v8/prod-23871-motivation-speakers/index.html
- `secondary_template_id`: `TPL-007`
- `secondary_template_name`: `Furni`
- `secondary_source_url`: https://ld-wt73.template-help.com/wt_62267_v8/62272-furni/index.html
- `usage`: 適合手作課、色鉛課、水彩課、iPad 繪圖課、花藝課、畫室、藝術工作室、個人品牌、招生頁。
- `layout_direction`: 小工作室感、清楚、輕鬆、簡約、左文右圖、留白型、圖片大、區塊清楚、不擁擠。

### TPL-002 Aparto Pairing

- `primary_template_id`: `TPL-002`
- `primary_template_name`: `Aparto`
- `source_url`: https://ld-wt73.template-help.com/wt_62267_v8/prod-15821-real-estate/index.html
- `secondary_template_id`: `TPL-004`
- `secondary_template_name`: `Creek Construction`
- `secondary_source_url`: https://ld-wt73.template-help.com/wt_62267_v8/prod-13732-construction/index-variant-2.html
- `usage`: 適合花藝課、場地出租、畫室、品牌網站、招生頁、藝術工作室、個人品牌。
- `layout_direction`: 高級、舒服、時尚、自然、有空氣感、品牌形象型、中央文字、大圖背景、強烈對比。

### TPL-003 Quart Pairing

- `primary_template_id`: `TPL-003`
- `primary_template_name`: `Quart`
- `source_url`: https://ld-wt73.template-help.com/wt_62267_v8/prod-13850-business/index.html
- `secondary_template_id`: `TPL-009`
- `secondary_template_name`: `Monstroid2 Commercial`
- `secondary_source_url`: https://ld-wt73.template-help.com/wt_62267_v8/62267-default/home-commercial.html
- `usage`: 適合花藝課、iPad 繪圖課、手作課、品牌網站、招生頁、藝術工作室、個人品牌。
- `layout_direction`: 專業、熱情、有品牌感、資訊清楚、中央文字、輪播、卡片式、強烈對比。

### TPL-004 Creek Construction Pairing

- `primary_template_id`: `TPL-004`
- `primary_template_name`: `Creek Construction`
- `source_url`: https://ld-wt73.template-help.com/wt_62267_v8/prod-13732-construction/index-variant-2.html
- `secondary_template_id`: `TPL-002`
- `secondary_template_name`: `Aparto`
- `secondary_source_url`: https://ld-wt73.template-help.com/wt_62267_v8/prod-15821-real-estate/index.html
- `usage`: 適合花藝課、iPad 繪圖課、畫室、品牌網站、招生頁、藝術工作室、個人品牌、銀飾課。
- `layout_direction`: 大圖滿版、形象清楚、專業、有活力、黃色點綴、雜誌編排、區塊距離大。

### TPL-005 Interior Design Pairing

- `primary_template_id`: `TPL-005`
- `primary_template_name`: `Interior Design`
- `source_url`: https://ld-wt73.template-help.com/wt_62267_v8/prod-13716-interior/index-variant-2.html
- `secondary_template_id`: `TPL-007`
- `secondary_template_name`: `Furni`
- `secondary_source_url`: https://ld-wt73.template-help.com/wt_62267_v8/62272-furni/index.html
- `usage`: 適合場地出租、手繪課、攝影、水彩課、鉛筆素描課、iPad 繪圖課、手作課、品牌網站、招生頁、藝術工作室、個人品牌。
- `layout_direction`: 高級、舒服、日系、簡約、有空氣感、清爽、照片顏色協調、大圖滿版。

### TPL-006 Resto Pairing

- `primary_template_id`: `TPL-006`
- `primary_template_name`: `Resto`
- `source_url`: https://ld-wt73.template-help.com/wt_62267_v8/62276-resto/index.html
- `secondary_template_id`: `TPL-008`
- `secondary_template_name`: `Fashion Blog`
- `secondary_source_url`: https://ld-wt73.template-help.com/wt_62267_v8/62273-fashion-blog/index.html
- `usage`: 適合美食、食品攝影、咖啡館、生活風格課程、手作課、花藝課、iPad 繪圖課、品牌網站、招生頁、藝術工作室、個人品牌。
- `layout_direction`: 高級、有品牌感、有溫度、有藝術感、美式、故事感、黑白極簡、大圖滿版、中央文字。

### TPL-007 Furni Pairing

- `primary_template_id`: `TPL-007`
- `primary_template_name`: `Furni`
- `source_url`: https://ld-wt73.template-help.com/wt_62267_v8/62272-furni/index.html
- `secondary_template_id`: `TPL-005`
- `secondary_template_name`: `Interior Design`
- `secondary_source_url`: https://ld-wt73.template-help.com/wt_62267_v8/prod-13716-interior/index-variant-2.html
- `usage`: 適合傢俱、藝術品、作品、商品、花藝課、水彩課、鉛筆素描課、iPad 繪圖課、手作課、美甲、畫室、品牌網站、招生頁、藝術工作室、個人品牌。
- `layout_direction`: 日系、簡約、高級、舒服、有空氣感、商品作品展示、左文右圖、留白型。

### TPL-008 Fashion Blog Pairing

- `primary_template_id`: `TPL-008`
- `primary_template_name`: `Fashion Blog`
- `source_url`: https://ld-wt73.template-help.com/wt_62267_v8/62273-fashion-blog/index.html
- `secondary_template_id`: `TPL-007`
- `secondary_template_name`: `Furni`
- `secondary_source_url`: https://ld-wt73.template-help.com/wt_62267_v8/62272-furni/index.html
- `usage`: 適合美髮、藝術照、人像攝影、彩妝、服裝、美甲、鉛筆素描課、畫室、品牌網站、招生頁、藝術工作室、個人品牌。
- `layout_direction`: 時尚、雜誌感、黑白極簡、品牌感、藝術感、大圖滿版、輪播、留白型。

### TPL-009 Monstroid2 Commercial Pairing

- `primary_template_id`: `TPL-009`
- `primary_template_name`: `Monstroid2 Commercial`
- `source_url`: https://ld-wt73.template-help.com/wt_62267_v8/62267-default/home-commercial.html
- `secondary_template_id`: `TPL-010`
- `secondary_template_name`: `Monstroid2 Landing`
- `secondary_source_url`: https://ld-wt73.template-help.com/wt_62267_v8/landing/
- `usage`: 適合 iPad 繪圖課、品牌網站、招生頁、個人品牌。
- `layout_direction`: 高級、品牌感、簡約、空氣感、通用品牌站、大圖滿版、輪播、品牌形象型。

### TPL-010 Monstroid2 Landing Pairing

- `primary_template_id`: `TPL-010`
- `primary_template_name`: `Monstroid2 Landing`
- `source_url`: https://ld-wt73.template-help.com/wt_62267_v8/landing/
- `secondary_template_id`: `TPL-009`
- `secondary_template_name`: `Monstroid2 Commercial`
- `secondary_source_url`: https://ld-wt73.template-help.com/wt_62267_v8/62267-default/home-commercial.html
- `usage`: 適合品牌網站、招生頁、個人品牌、通用 landing page。
- `layout_direction`: 療癒、品牌感、舒服、簡約、空氣感、大圖滿版、小影片、品牌形象型。

## 禁止事項

- 不可使用 `https://example.com/...` 或任何假網址當作 `source_url`。
- 不可使用未在本文件登錄的 `TPL-*`。
- 不可只回傳 client-facing 名稱而省略 primary / secondary template metadata。
