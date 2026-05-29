# 課程招生 - 系統

這個 repo 目前已建立第一版課程招生頁前端模板與 design foundation。

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

## 開啟目前招生頁模板

直接用瀏覽器開啟：

```text
templates/course-brand-template-v1/index.html
```

目前模板是靜態 HTML/CSS/JS，不需要安裝套件或啟動後端。

## 接手前建議閱讀

1. `docs/PROJECT_CONTEXT.md`
2. `docs/PROJECT_STATUS.md`
3. `docs/STYLE_SYSTEM.md`
4. `docs/COLLABORATION_SETUP.md`
5. `docs/WEBSITE_FACTORY_MIGRATION.md`
6. `styles/course-brand-template-v1.json`
7. `styles/layout-rules/landing-page.md`
8. `styles/typography/course-brand.md`
9. `styles/motion/animation-style.md`
10. `styles/tokens/course-brand.css`

## 目前工作範圍

已建立：

- `styles/`：course brand design foundation
- `templates/course-brand-template-v1/`：靜態招生頁模板
- `docs/`：專案脈絡、狀態與協作規則

尚未建立：

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
