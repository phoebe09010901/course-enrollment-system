# WT 62267 v8 Home Personal Block Extraction

Source page:
- `https://ld-wt73.template-help.com/wt_62267_v8/62267-default/home-personal.html`

Local output follows the `template-001` style:
- `index.html`
- `style.css`
- `script.js`
- `images/`
- `fonts/`
- `video/`
- `blocks/`

Notes:
- `style.css` combines source `css/bootstrap.css` and `css/style.css`.
- `script.js` combines source `js/core.min.js` and `js/script.js`.
- `source-home-personal.html` is the untouched downloaded source HTML.
- External font/CDN links remain in `index.html`: Google Fonts and Font Awesome CDN.

## Block Map

| Block | Local block file | Source range | CSS dependencies | JS dependencies | Assets | Reusable | M-D style judgment |
|---|---|---:|---|---|---|---|---|
| Loader | `blocks/loader.html` | `index.html:18` | `#page-loader`, `.cssload-container`, `.cssload-speeding-wheel`; loader styles from source `css/style.css` | `plugins.preloader`, `#page-loader` handling in `vendor/source-script.js` | `images/preloader.gif`, `images/ajax-loader.gif`, `images/loading.gif` are downloaded support assets | Yes, as global page loader | Low. Keep only if factory templates need loading state |
| Header | `blocks/header.html` | `index.html:25` | `.page-header`, `.rd-navbar-wrap`, `.rd-navbar`, `.rd-navbar-nav`, `.rd-menu`, `.brand`, `.brand-logo-*` | RD Navbar init in `vendor/source-script.js`; depends on bundled jQuery/RD Navbar code in `script.js` | `images/logo-default-347x65.png`, `images/logo-inverse-347x65.png` | Yes, but heavy because mega menu is large | Yes. Needs M-D to simplify nav and decide brand/header density |
| Hero | `blocks/hero-swiper.html` | `index.html:289` | `.rd-parallax`, `.swiper-container`, `.swiper-slider_fullheight`, `.swiper-slide-caption`, `.button` | Swiper init, slide backgrounds, caption animation, video control in `vendor/source-script.js` | `images/slider-slide-10-1920x1080.jpg`, `images/slider-slide-11-1920x1080.jpg`, `video/video-lg.jpg`, `video/video-lg.mp4` | Yes, as visual hero pattern | Yes. Hero content and video/slider behavior need style choice |
| Swiper | `blocks/hero-swiper.html` | `index.html:289` | Same as Hero; pagination/nav classes `.swiper-pagination`, `.swiper-button-prev`, `.swiper-button-next` | Swiper plugin bundled in `script.js`; options from data attrs `data-loop`, `data-autoplay`, `data-slide-bg` | Same as Hero | Yes, if carousel is allowed | Yes. Decide whether carousel should stay or become static hero |
| About | `blocks/about.html` | `index.html:332` | `.section`, `.section-xxl`, `.figure-inline`, `.object-displacement-1`, `.rounded-circle` | None beyond base layout | `images/images-9-382x382.png` | Yes | Medium. Image crop and personal intro style should be judged |
| Workflow | `blocks/workflow-stats.html` | `index.html:349` | `.bg-image-4`, `.bg-accent`, `.progress-circle`, `.progress-circle-bar`, `.progress-circle-counter` | Progress circle animation in `vendor/source-script.js` | `images/bg-image-4.jpg` via CSS background | Yes, as stats/progress block | Medium. This is not a literal workflow; M-D may rename/recast |
| Service | `blocks/service.html` | `index.html:395` | `.blurb`, `.blurb-minimal`, `.blurb-minimal__icon`, `.linear-icon-*` | None beyond icon font/base layout | Icon font `fonts/Linearicons.ttf` | Yes | Medium. Current copy is generic template-service content |
| Gallery | `blocks/gallery.html` | `index.html:422` | `.thumb-modern`, `.thumb-modern__overlay`, grid utilities, lightGallery CSS | `data-lightgallery`, lightGallery group/item init in `vendor/source-script.js` | Thumb images `home-default-9`, `home-commercial-2..7`, `home-default-12`; originals `image-original-3/6/9/10/11/12/13/14` | Yes | Yes. Needs decision on proof/gallery rhythm and image meaning |
| Feedback | `blocks/feedback.html` | `index.html:462` | `.quote-default`, `.quote-default_left`, `.quote-default__image`, `.quote-default__mark`, `.group-quote` | None beyond base layout | `images/testimonials-1-120x120.jpg`, `images/testimonials-2-120x120.jpg` | Yes | Medium. Testimonial density and avatar style need content fit |
| Child Themes | `blocks/child-themes.html` | `index.html:503` | `.bg-accent`, `.section-lg`, `.button-gray-light-outline` | None beyond base button behavior | No direct image | Yes, as CTA/support block | Yes. Decide whether this maps to course themes, demos, or secondary CTA |
| Contact | `blocks/contact.html` | `index.html:516` | `.rd-mailform`, `.rd-mailform_style-1`, `.form-wrap`, `.form-input`, `.form-label`, `.form-icon` | RD Mailform validation/AJAX in `vendor/source-script.js`; posts to `bat/rd-mailform.php` in original | Icon font `fonts/Linearicons.ttf`; no image | Reusable front-end only | Yes. Backend/form action must be removed or replaced outside M-E scope |
| Footer | `blocks/footer.html` | `index.html:549` | `.footer-corporate`, `.footer-corporate__inner`, `.rights`, `.list-inline-xxs`, icon classes | Copyright year fill in `vendor/source-script.js` | Font Awesome / icon fonts | Yes | Low to medium. Social links and copyright need brand fit |

## Downloaded Asset Summary

Key local assets:
- Logo: `images/logo-default-347x65.png`, `images/logo-inverse-347x65.png`
- Hero/Swiper: `images/slider-slide-10-1920x1080.jpg`, `images/slider-slide-11-1920x1080.jpg`, `video/video-lg.jpg`, `video/video-lg.mp4`
- About: `images/images-9-382x382.png`
- Gallery thumbnails/originals: `images/home-*.jpg`, `images/image-original-*.jpg`
- Feedback: `images/testimonials-1-120x120.jpg`, `images/testimonials-2-120x120.jpg`
- CSS backgrounds/loaders: `images/bg-image-2.jpg`, `images/bg-image-3.jpg`, `images/bg-image-4.jpg`, `images/bg-image-6.jpg`, `images/isotope-loader.png`, `images/ajax-loader.gif`, `images/preloader.gif`, `images/loading.gif`
- Fonts: `fonts/Linearicons.ttf`, `fonts/fontawesome-webfont.*`, `fonts/lg.*`

Missing but non-blocking:
- `images/vimeo-play.png`
- `images/video-play.png`
- `images/youtube-play.png`

These were referenced by source CSS but returned 404 and are not directly used by the extracted homepage blocks.

## M-E Notes

- The homepage's Hero and Swiper are the same physical block.
- There is no separate standalone `Workflow` block; the progress-circle stats band is the closest match.
- There is no course-specific schema or data model in this extraction.
- Contact block is front-end markup only; original form action points to `bat/rd-mailform.php`.
- This extraction does not perform zip export or final acceptance QA.
