# 課程招生 - 系統

這個 repo 目前保留課程招生頁的 design foundation、協作文件，以及三個全新視覺方向範例。先前建立的兩個前端樣板已依使用者要求刪除，不再作為可沿用模板。

## 另一台電腦接手方式

先確認另一台電腦已安裝 Git，然後執行：

```bash
git clone git@github.com:phoebe09010901/course-enrollment-system.git
cd course-enrollment-system
```

如果該電腦沒有設定 GitHub SSH key，可改用 HTTPS：

```bash
git clone https://github.com/phoebe09010901/course-enrollment-system.git
cd course-enrollment-system
```

## 接手前建議閱讀

1. `docs/PROJECT_CONTEXT.md`
2. `docs/PROJECT_STATUS.md`
3. `docs/STYLE_SYSTEM.md`
4. `docs/COLLABORATION_SETUP.md`
5. `styles/layout-rules/landing-page.md`
6. `styles/typography/course-brand.md`
7. `styles/motion/animation-style.md`
8. `styles/tokens/course-brand.css`

## 目前視覺範例

目前新增三個 sunshine-golden-pencil alternative examples，供確認視覺方向：

- `templates/sunshine-golden-pencil-alt-a/`：日系工作室型錄版
- `templates/sunshine-golden-pencil-alt-b/`：暗色策展導覽版
- `templates/sunshine-golden-pencil-alt-c/`：雜誌索引作品版

三者皆為候選視覺稿，保留既有課程內容與圖片資料，但刻意使用不同 Hero、內容導覽、作品展示與報名 CTA 結構。

## 目前工作範圍

已建立：

- `styles/`：course brand design foundation
- `templates/`：三個 frontend alternative examples
- `docs/`：專案脈絡、狀態與協作規則

尚未建立：

- 已確認採用的正式前端招生頁模板
- SQL
- webhook
- backend
- form schema
- admin
- worker

## 協作流程

每次開始工作前：

```bash
git pull
git status
```

完成修改後：

```bash
git add .
git commit -m "描述這次修改"
git push
```
