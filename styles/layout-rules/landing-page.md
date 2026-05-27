# Landing Page Layout Rules

## Frontend Contract

Import `styles/tokens/course-brand.css` before implementing a brand-led course page.

Required page classes:

```html
<main class="cb-page">
  <section class="cb-hero">
    <div class="cb-shell cb-hero__grid">
      <div class="cb-hero__content">
        <p class="cb-kicker">Course category</p>
        <h1 class="cb-hero-title">Hero title</h1>
        <p class="cb-body">Course promise.</p>
        <div class="cb-hero__actions">...</div>
      </div>
      <div class="cb-hero__visual">...</div>
    </div>
  </section>
</main>
```

## Hero Layout Rules

The Hero is the strongest visual moment on the page.

### Required Structure

- `.cb-hero`
  - owns vertical Hero rhythm.
- `.cb-shell`
  - constrains page width.
- `.cb-hero__grid`
  - defines desktop and mobile Hero layout.
- `.cb-hero__content`
  - contains label, H1, promise, CTA, proof cue.
- `.cb-hero__visual`
  - contains the dominant course-specific visual.

### Desktop Hero

Use this layout:

```css
grid-template-columns: minmax(0, 1.05fr) minmax(320px, 0.95fr);
gap: clamp(2.5rem, 6vw, 6rem);
min-height: min(860px, 92svh);
```

Rules:

- Content sits left by default.
- Visual sits right by default.
- H1 must be the strongest type element.
- Visual must have one dominant focal point.
- CTA cluster appears after promise text.
- A hint of the next section should remain visible where possible.

### Mobile Hero

Use this layout:

```css
grid-template-columns: 1fr;
gap: var(--cb-space-5);
```

Rules:

- Content appears before visual.
- H1 and CTA must be visible before the page feels image-heavy.
- Visual height uses `clamp(260px, 68vw, 420px)`.
- Do not hide the Hero visual unless no real visual asset exists.

## Hero Visual Rules

Use `.cb-hero__visual` for:

- one course-specific image
- one dominant image with supporting overlays
- a controlled image grid with one clear lead image
- an editorial typographic visual when the brand is type-led

Do not use:

- generic gradient-only Hero background
- equal-weight image collage
- decorative floating shapes as the main signal
- a framed card around the whole Hero copy

## Section Rules

Use these section classes:

| Class | Token | Use |
| --- | --- | --- |
| `.cb-section` | `--cb-section-standard` | Normal story sections |
| `.cb-section--compact` | `--cb-section-compact` | FAQ, metadata, dense proof |
| `.cb-section--feature` | `--cb-section-feature` | Promise, gallery, final CTA |

Rules:

- Alternate dense sections with breathable sections.
- Use feature sections for high-emotion or high-trust moments.
- Use compact sections for utility, never for the Hero.
- Do not use identical section rhythm across the whole page.

## Spacing Rules

Frontend spacing must use `--cb-space-*` or section tokens.

Use:

- `--cb-space-1`: inline icon gaps, small label gaps
- `--cb-space-2`: button groups, compact text groups
- `--cb-space-3`: label to title, card internal rhythm
- `--cb-space-4`: title to body, body to CTA
- `--cb-space-5`: mobile Hero grid gap
- `--cb-space-6`: card grid gap, proof group gap
- `--cb-space-7`: gallery internal gap
- `--cb-space-8` and above: large story transitions

Do not use arbitrary one-off spacing unless a component cannot be expressed with existing tokens.

## Visual Hierarchy Rules

Page sections must map to one of these weights:

| Weight | Sections | Required Behavior |
| --- | --- | --- |
| Primary | Hero, final CTA | largest scale, strongest contrast, most air |
| Feature | promise, method, gallery | strong visual identity, composed rhythm |
| Proof | outcomes, testimonials, student work | structured repetition, high trust density |
| Utility | FAQ, schedule, metadata | compact, readable, low drama |

Do not style every section as a card. Cards are allowed for repeated items only.

## Section Choreography

Default order:

1. Hero
2. Promise
3. Audience tension
4. Method
5. Proof
6. Curriculum
7. Gallery
8. Instructor trust
9. FAQ
10. Final CTA

Rules:

- Promise follows Hero quickly.
- Proof appears before final commitment.
- Gallery appears after enough context exists.
- Final CTA should feel like a conclusion, not a repeated button block.

## Gallery Rhythm

Use one of three gallery patterns:

### Editorial Lead

Frontend structure:

- one 2-column or 2-row lead image
- two or three support images
- one caption or proof line

Use for atmosphere and brand story.

### Proof Grid

Frontend structure:

- repeated cards
- consistent image crop
- strong captions
- compact metadata

Use for student work or outcomes.

### Immersive Strip

Frontend structure:

- horizontal image rhythm
- larger leading image
- small amount of text

Use as a breathing section between dense blocks.

