# Client Selection Flow

## 文件目的

本文件定義「三款樣板提案流程」。之後為新課程產生三款樣板時，不是每次重新發明三款，而是必須從 `docs/TEMPLATE_REFERENCE.md` 的 10 套樣板資料中挑選合適組合。

這份文件服務 Chat A、Chat B 與 Codex：

- Chat A 用它產生客戶可看的三款 Canva 視覺方向。
- Chat B / Codex 用它理解被選定方案背後的 template id 與 source URL。
- 客戶看到的是簡化後的方向名稱；內部必須保留完整 template reference。

## 前置規則

1. 每次為新課程產生三款樣板時，Chat A 必須先查看 `docs/TEMPLATE_REFERENCE.md`。
2. 三款提案不可憑空產生，必須來自 10 套樣板資料或其合理組合。
3. 若要新增第 11 套樣板，必須先更新 `docs/TEMPLATE_REFERENCE.md`，再納入選版流程。
4. 所有提案仍需遵守 `docs/STYLE_SYSTEM.md` 的全域設計規則。
5. 樣板設計先經過 Canva 視覺確認，再交給 Chat B / Codex 進入前端實作。

## 三款提案必填欄位

每一款 proposal 都必須包含：

- `proposal_id`
- `proposal_name`
- `primary_template_id`
- `secondary_template_id`
- `source_url`
- `secondary_source_url`
- `適合原因`
- `視覺方向`

## 客戶顯示格式

客戶看到的是簡化後的三款方向名稱，例如：

- A 款：清爽藝術風
- B 款：高級品牌風
- C 款：雜誌作品集風

對客戶不需要顯示大量 template 技術欄位，但內部紀錄必須保留。

## 內部記錄格式

內部記錄必須保留 template id 與 source URL，方便 Chat B / Codex 後續前端實作。

```text
proposal_id: A
proposal_name: 清爽藝術風
primary_template_id: TPL-005
secondary_template_id: TPL-007
source_url: https://ld-wt73.template-help.com/wt_62267_v8/prod-13716-interior/index-variant-2.html
secondary_source_url: https://ld-wt73.template-help.com/wt_62267_v8/62272-furni/index.html
適合原因: 適合清爽、高級、日系感的藝術課程與作品展示。
視覺方向: 大圖滿版、清爽留白、照片色調協調、作品展示有秩序。
```

## 選定後交接規格

選定其中一款後，Chat A 必須輸出交接資料給 Chat B：

- `selected_primary_template_id`
- `selected_secondary_template_id`
- Canva 樣板連結或截圖
- Hero 版面說明
- About 版面說明
- Gallery 版面說明
- CTA 版面說明
- 字體規則
- 色彩規則
- 圖片比例規則
- 禁止事項

Chat B / Codex 只能依照這份交接規格進行前端，不可自行改成另一種視覺方向。

## 三款提案選擇方式

Chat A 應根據課程類型、品牌調性、圖片素材、客戶偏好與招生目標，從 `docs/TEMPLATE_REFERENCE.md` 選三組最合適 pairing。

建議三款方向要有明確差異：

- 一款偏清爽、留白、藝術課程感。
- 一款偏高級、品牌形象、空間感。
- 一款偏作品展示、雜誌感或故事感。

不要為了湊三款而選擇不適合課程的 template。

## 範例三款提案

以下只是格式範例，實際提案需依課程內容重新選擇。

### A 款：清爽藝術風

- `proposal_id`: `A`
- `proposal_name`: `清爽藝術風`
- `primary_template_id`: `TPL-005`
- `secondary_template_id`: `TPL-007`
- `source_url`: https://ld-wt73.template-help.com/wt_62267_v8/prod-13716-interior/index-variant-2.html
- `secondary_source_url`: https://ld-wt73.template-help.com/wt_62267_v8/62272-furni/index.html
- `適合原因`: 適合水彩、手繪、場地、畫室或高級清爽感課程。
- `視覺方向`: 清爽留白、大圖滿版、照片色調協調、作品展示有秩序。

### B 款：高級品牌風

- `proposal_id`: `B`
- `proposal_name`: `高級品牌風`
- `primary_template_id`: `TPL-002`
- `secondary_template_id`: `TPL-004`
- `source_url`: https://ld-wt73.template-help.com/wt_62267_v8/prod-15821-real-estate/index.html
- `secondary_source_url`: https://ld-wt73.template-help.com/wt_62267_v8/prod-13732-construction/index-variant-2.html
- `適合原因`: 適合花藝、場地出租、藝術工作室或需要高級形象的招生頁。
- `視覺方向`: 中央文字、大圖背景、空間感、強形象、高級留白。

### C 款：雜誌作品集風

- `proposal_id`: `C`
- `proposal_name`: `雜誌作品集風`
- `primary_template_id`: `TPL-008`
- `secondary_template_id`: `TPL-007`
- `source_url`: https://ld-wt73.template-help.com/wt_62267_v8/62273-fashion-blog/index.html
- `secondary_source_url`: https://ld-wt73.template-help.com/wt_62267_v8/62272-furni/index.html
- `適合原因`: 適合作品、人像、風格強烈、藝術感或作品集導向的招生頁。
- `視覺方向`: 雜誌感、大圖滿版、作品展示、黑白極簡、個人風格。

## 禁止事項

- 不可未查詢 `docs/TEMPLATE_REFERENCE.md` 就產生三款提案。
- 不可用沒有登錄在 `docs/TEMPLATE_REFERENCE.md` 的新 template 作為正式提案。
- 不可只給客戶方向名稱，內部卻沒有 template id。
- 不可讓 Chat B / Codex 在客戶選定後自行更換 template。
- 不可跳過 Canva 視覺確認直接進入 HTML/CSS。
