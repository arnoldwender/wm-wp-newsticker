# Accessibility — WM Newsticker

> Measured against the code on 2026-11-16 (version 1.5.0). No external conformity audit against WCAG or the BFSG has been carried out, and no legal conformity is claimed; whether a site that uses the block meets statutory requirements depends on the theme, surrounding page landmarks, and author-configured colours.

## What the code does

### Markup (`WM_Newsticker::render_block()`)

- The wrapper is a `<div>` with `role="region"`, `aria-roledescription="marquee"`, a translated `aria-label` ("News ticker") and `aria-live="off"`, so screen readers do not announce every movement.
- Links carry `target="_blank" rel="noopener noreferrer"` only when "open in new tab" is set.

### Controls (enabled by default: `showControls = true`)

- Previous, play / pause and next are `<button type="button">` elements with translated `aria-label` attributes ("Previous", "Pause", "Next"); the icons are inline SVG with `aria-hidden="true"` and `focusable="false"` (`render_controls()`).
- `render_controls()` outputs server-translated `data-label-pause` and `data-label-play` attributes on `.wm-control-play-pause`, and `src/frontend.js` updates `aria-label` from those attributes (`dataset.labelPause` / `dataset.labelPlay`) while keeping `data-playing` synchronized.
- Focus: `outline: 2px solid currentColor; outline-offset: 2px` on `:focus` and `:focus-visible` (`src/style.scss`), plus visible `:focus-visible` outlines on links inside `.wm-newsticker-content`.
- Size: `min-width: 44px; min-height: 44px` on `.wm-control-btn` across desktop and mobile viewports; default block height is `48 px`.

### Motion

- Pause while the mouse is over the ticker (`mouseenter` / `mouseleave`, attribute `pauseOnHover`, on by default).
- Pause when keyboard focus enters `.wm-newsticker-content`: `focusin` / `focusout` handlers in `src/frontend.js` pause scroll and slide/fade/typing intervals (without overriding a manual pause via the Play/Pause button), and `.wm-newsticker-content:focus-within .wm-newsticker-track` pauses CSS marquee motion even before JavaScript executes.
- With `prefers-reduced-motion: reduce` the CSS sets `animation: none` on the scroll track, the progress bar and the typing slide and `transition: none` on the slides; the script pauses the scroll track and starts the fade / slide / typing rotation paused. With controls enabled, a visitor can still step through items manually.

### Colours

- Default background `#1a1a2e` with text `#ffffff`: **17.06:1**.
- Default label background `#b81d38` with text `#ffffff`: **6.42:1** (updated in 1.5.0 from `#e94560`, which measured 3.83:1). The label text is empty by default.

## Remaining boundaries

- Authors can disable `showControls` or pick custom foreground/background colour combinations in the block inspector that fall below 4.5:1.
- Automated tests verify markup attributes, default colour contrast, button target rules, and JS focus listeners, not live screen-reader announcements across assistive technology combinations.
