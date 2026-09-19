# Accessibility — WM Newsticker

> Measured against the code on 2026-09-15. The previous edition declared compliance with BFSG 2025 and WCAG 2.2 AA, an `<aside>` wrapper with polite announcements, 44 × 44 px controls, `:focus-visible` rings, a pause on keyboard focus, a "paginated" reduced-motion view and at least 4.5:1 contrast for the defaults. None of it was measured, and several points are wrong. No audit against WCAG or the BFSG has been made; whether a site that uses the block meets the BFSG depends on the theme, the content and the whole page.

## What the code does

### Markup (`WM_Newsticker::render_block()`)

- The wrapper is a `<div>` with `role="region"`, `aria-roledescription="marquee"`, a translatable `aria-label` ("News ticker") and `aria-live="off"`, so screen readers do not announce every movement.
- Links carry `target="_blank" rel="noopener noreferrer"` only when "open in new tab" is set.

### Controls (optional, off by default)

- Previous, play / pause and next are `<button type="button">` elements with `aria-label` ("Previous", "Play/Pause", "Next", translatable); the icons are inline SVG with `aria-hidden="true"` and `focusable="false"` (`render_controls()`).
- After a click the play / pause label becomes "Pause" or "Play" (English, set in `src/frontend.js`, not translated) and `data-playing` follows the state.
- Focus: `outline: 2px solid currentColor; outline-offset: 2px` on `:focus` (`src/style.scss`).
- Size: 16 px icon plus 6 px padding = 28 × 28 px; 24 × 24 px up to 782 px viewport width.

### Motion

- Pause while the mouse is over the ticker (`mouseenter` / `mouseleave`, attribute `pauseOnHover`, on by default). Keyboard focus does not pause the ticker.
- With `prefers-reduced-motion: reduce` the CSS sets `animation: none` on the scroll track, the progress bar and the typing slide and `transition: none` on the slides; the script pauses the scroll track and starts the fade / slide / typing rotation paused. With controls, a visitor can still start or step through it.
- A visitor can stop the motion only through the controls (keyboard or pointer) or by hovering.

### Colours

- Default background `#1a1a2e` with text `#ffffff`: 17.06:1.
- Default label `#e94560` with text `#ffffff`: 3.83:1 — below 4.5:1 for the 14 px default size. The label is empty by default.

## Gaps

- No pause on keyboard focus; without controls, keyboard and screen reader users cannot stop the motion (WCAG 2.2.2 asks for a way to pause moving content that lasts more than five seconds).
- Controls below 44 × 44 px.
- Default label contrast below 4.5:1.
- Play / pause label not translated after the first click.
- No test with screen readers or automated checkers in the repository.
