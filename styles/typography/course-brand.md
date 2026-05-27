# Course Brand Typography System

## Frontend Contract

Typography is implemented through `styles/tokens/course-brand.css`.

Required classes:

- `.cb-kicker`
- `.cb-hero-title`
- `.cb-section-title`
- `.cb-body`
- `.cb-button`

## Type Tokens

```css
--cb-font-display: "Noto Serif TC", "Songti TC", "PingFang TC", serif;
--cb-font-sans: "Noto Sans TC", "PingFang TC", "Microsoft JhengHei", sans-serif;

--cb-text-label: 0.8125rem;
--cb-text-caption: 0.875rem;
--cb-text-body: 1.0625rem;
--cb-text-body-lg: 1.1875rem;
--cb-text-subtitle: 1.5rem;
--cb-text-section: clamp(1.85rem, 2.6vw, 2.55rem);
--cb-text-hero: clamp(2.45rem, 5.4vw, 4.35rem);

--cb-leading-tight: 1.04;
--cb-leading-title: 1.14;
--cb-leading-body: 1.72;
--cb-leading-caption: 1.42;
```

## Type Rules

### Hero Title

Use `.cb-hero-title`.

```css
font-family: var(--cb-font-display);
font-size: var(--cb-text-hero);
font-weight: var(--cb-weight-bold);
line-height: var(--cb-leading-tight);
max-width: 9.5em;
```

Rules:

- Use only once per page.
- Must be visually stronger than all section titles.
- Keep line breaks intentional.
- Do not place inside a decorative card.

### Section Title

Use `.cb-section-title`.

```css
font-family: var(--cb-font-display);
font-size: var(--cb-text-section);
font-weight: var(--cb-weight-bold);
line-height: var(--cb-leading-title);
max-width: 11em;
```

Rules:

- Use for major section transitions.
- Keep title length tight enough to scan.
- Pair with `.cb-body` for persuasive context.
- Desktop section titles must be refined and restrained; do not scale them like campaign posters.
- Section titles must not visually compete with the Hero title.

### Body Copy

Use `.cb-body`.

```css
font-family: var(--cb-font-sans);
font-size: var(--cb-text-body);
line-height: var(--cb-leading-body);
max-width: var(--cb-content-narrow);
```

Rules:

- Body copy should not exceed `--cb-content-narrow`.
- Use short paragraphs.
- Do not use body copy as the only hierarchy tool.

### Kicker

Use `.cb-kicker`.

```css
font-size: var(--cb-text-label);
font-weight: var(--cb-weight-bold);
line-height: var(--cb-leading-caption);
letter-spacing: 0.08em;
```

Rules:

- Use for course category, audience cue, or section orientation.
- Do not use as decoration without information.
- Use sparingly to prevent visual noise.
- Visible kicker copy must be Chinese by default.
- Do not use English uppercase labels in rendered UI unless the user explicitly requests bilingual copy.

### CTA Text

Use `.cb-button`.

Rules:

- Text must be short and action-specific.
- Preferred length: 2 to 8 Chinese characters.
- Avoid vague CTA copy when enrollment intent is expected.
- Do not introduce English CTA copy in Chinese course templates.

## Hierarchy Rules

Use this hierarchy order:

1. `.cb-hero-title`
2. `.cb-section-title`
3. subtitle or card title using `--cb-text-subtitle`
4. `.cb-body`
5. `.cb-kicker` and caption text

Do not introduce another display scale without adding a token first.

## Frontend Usage Pattern

```html
<section class="cb-section cb-section--feature">
  <div class="cb-shell">
    <p class="cb-kicker">課程方法</p>
    <h2 class="cb-section-title">清楚拆解每一步學習節奏</h2>
    <p class="cb-body">用安定、可閱讀的語氣說明課程方法與成果。</p>
  </div>
</section>
```

## Failure Rules

Reject a frontend implementation if:

- H1 is not using `.cb-hero-title`.
- Section titles visually compete with Hero title.
- Body text is wider than the narrow content token.
- CTA text wraps awkwardly inside the button.
- Typography relies on color only for hierarchy.
- Visible UI text uses English section labels without explicit user approval.
- Desktop section headings are oversized compared with the Hero rhythm.
