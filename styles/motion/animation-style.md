# Motion System

## Frontend Contract

Motion is implemented through `styles/tokens/course-brand.css`.

Required motion class:

- `.cb-reveal`

Required Hero sequencing attribute:

- `data-hero-step="1"` through `data-hero-step="5"`

## Motion Tokens

```css
--cb-duration-micro: 160ms;
--cb-duration-fast: 220ms;
--cb-duration-standard: 320ms;
--cb-duration-reveal: 620ms;
--cb-duration-hero: 840ms;
--cb-stagger-tight: 70ms;
--cb-stagger-standard: 110ms;
--cb-stagger-wide: 160ms;
--cb-ease-default: cubic-bezier(0.22, 1, 0.36, 1);
--cb-ease-sharp: cubic-bezier(0.16, 1, 0.3, 1);
--cb-ease-gentle: cubic-bezier(0.25, 0.46, 0.45, 0.94);
```

## Animation Rules

### Reveal

Use `.cb-reveal`.

```css
.cb-reveal {
  opacity: 0;
  transform: translateY(18px);
  animation: cb-reveal var(--cb-duration-reveal) var(--cb-ease-default) forwards;
}
```

Rules:

- Apply to section groups, Hero layers, gallery groups, or proof groups.
- Do not apply to every paragraph.
- Do not block content visibility with long delays.

### Hero Entrance

Use this order:

```html
<p class="cb-kicker cb-reveal" data-hero-step="1">...</p>
<h1 class="cb-hero-title cb-reveal" data-hero-step="2">...</h1>
<p class="cb-body cb-reveal" data-hero-step="3">...</p>
<div class="cb-hero__actions cb-reveal" data-hero-step="4">...</div>
<div class="cb-hero__visual cb-reveal" data-hero-step="5">...</div>
```

Rules:

- Label enters first.
- H1 enters second.
- CTA enters before visual if conversion clarity is more important than atmosphere.
- Visual enters last or alongside CTA when the brand image is essential.

### Button Motion

Use `.cb-button`.

Rules:

- Hover may lift by `translateY(-2px)`.
- Hover must not resize the button.
- Focus state must remain visible.
- Transition duration must use `--cb-duration-fast`.

### Gallery Motion

Use `.cb-reveal` on the gallery group and optional stagger on children.

Rules:

- Use `--cb-stagger-tight` for compact proof grids.
- Use `--cb-stagger-standard` for editorial image sets.
- Use `--cb-stagger-wide` only for feature gallery sections.
- Avoid auto-moving galleries by default.

## Reduced Motion

The token CSS includes:

```css
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 1ms !important;
    animation-iteration-count: 1 !important;
    scroll-behavior: auto !important;
    transition-duration: 1ms !important;
  }
}
```

Rules:

- Do not remove content for reduced-motion users.
- Do not rely on animation to reveal essential content.
- Keep hover and focus states visible without movement.

## Prohibited Motion

- Infinite decorative loops.
- Large parallax movement behind text.
- Bounce, spin, or elastic motion for the Hero.
- Scroll-jacking.
- Delayed content reveal that slows comprehension.

