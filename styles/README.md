# Style Foundation

## Purpose

This directory defines the design foundation for a brand-led course landing page. It is not a website implementation, but it is a frontend-usable design system contract. It provides tokens, classes, layout rules, and animation rules for:

- Hero system
- Typography hierarchy
- Spacing rhythm
- Motion system
- Gallery rhythm
- Visual hierarchy
- Section choreography

The goal is to prevent the course page from feeling like a generic landing-page template. Every section should support a clear branded learning offer: who the course is for, why it matters, why the visitor should trust it, and what action they should take next.

## Current Files

- `styles/course-brand-template-v1.json`
  - Machine-readable frontend contract for the first course brand template.
- `styles/tokens/course-brand.css`
  - CSS custom properties, base classes, Hero layout classes, section classes, button styles, and reveal animation.
- `styles/layout-rules/landing-page.md`
  - Frontend layout rules for Hero, spacing, visual hierarchy, gallery rhythm, and section choreography.
- `styles/typography/course-brand.md`
  - Frontend typography tokens, required classes, and hierarchy rules.
- `styles/motion/animation-style.md`
  - Motion tokens, reveal animation rules, Hero sequencing, and reduced-motion rules.

## Frontend Usage

Import this file before implementing course landing page components:

```css
@import "../styles/tokens/course-brand.css";
```

Stable class contracts:

- `.cb-page`
- `.cb-shell`
- `.cb-hero`
- `.cb-hero__grid`
- `.cb-hero__content`
- `.cb-hero__actions`
- `.cb-hero__visual`
- `.cb-kicker`
- `.cb-hero-title`
- `.cb-section-title`
- `.cb-body`
- `.cb-section`
- `.cb-section--compact`
- `.cb-section--feature`
- `.cb-button`
- `.cb-reveal`

Stable token prefixes:

- `--cb-color-*`
- `--cb-font-*`
- `--cb-text-*`
- `--cb-leading-*`
- `--cb-weight-*`
- `--cb-space-*`
- `--cb-section-*`
- `--cb-duration-*`
- `--cb-ease-*`

## Design Priorities

1. Hero main visual
2. Typography hierarchy
3. Spacing rhythm
4. Section choreography
5. Motion restraint
6. Gallery rhythm

## Brand-Led Course Page Principles

- The Hero must feel like the brand's front door, not a generic headline block.
- Typography must create authority before decoration.
- Spacing must create pacing, confidence, and premium perception.
- Motion must guide attention, not entertain by default.
- Gallery sections must build evidence and atmosphere, not just display images.
- Section order must feel composed, with each block increasing clarity or trust.

## Non-Goals

This style foundation does not define:

- SQL schema
- Webhook behavior
- Form backend
- Admin workflows
- Worker jobs
- Payment logic
- Authentication

Those belong to other chats or future system documents.

## How AI Agents Should Use This Directory

Before designing or editing a course landing page, read:

1. `styles/README.md`
2. `styles/course-brand-template-v1.json`
3. `styles/layout-rules/landing-page.md`
4. `styles/typography/course-brand.md`
5. `styles/motion/animation-style.md`

Then apply the rules in this order:

1. Establish Hero composition and visual weight.
2. Set typography hierarchy.
3. Apply spacing rhythm.
4. Arrange section choreography.
5. Add motion only where it clarifies attention.

If a requested design conflicts with this foundation, state the conflict before changing the page.

## Override Rule

Specific course brands may override token values, but must not rename token roles or stable class contracts. For example, a brand may change `--cb-color-brand`, but should not invent `--primary-red` inside course-page components.
