# Open items — WM Newsticker

Updated on 2026-11-16 (version 1.5.0).

## Completed in 1.5.0

- [x] Pause the motion while keyboard focus is inside the ticker (`focusin` / `focusout` in `src/frontend.js` and `:focus-within` in `src/style.scss`).
- [x] Controls at least 44 × 44 px across desktop and mobile (`min-width: 44px; min-height: 44px`, default height `48 px`).
- [x] Default label colours with at least 4.5:1 (`#b81d38` with `#ffffff` = `6.42:1`).
- [x] Translate the play / pause label that `src/frontend.js` sets after a click (`data-label-pause` / `data-label-play`).
- [x] Enable controls by default (`showControls: true`).
- [x] Complete `.po`, `.mo`, and Gutenberg editor JED `.json` translations across all 28 bundled locales.
- [x] Rebuild check in CI (`npm install` + `npm run build` + `harden-build-assets.mjs` verification).
- [x] `uninstall.php`: opt-in setting (`wm_newsticker_delete_demo_on_uninstall`, default off) in the handbook for deleting demo content on uninstall.
- [x] WordPress 6.5+ Block Bindings (`wm-newsticker/feed`), GenerateBlocks 2.x Dynamic Tag (`{{wm_newsticker_headline}}`), Block Patterns, and WP 6.9+ Ability (`wm-newsticker/get-headlines`).

## Future enhancements

- [ ] Optional transient lock to deduplicate concurrent cold-cache `WP_Query` runs on high-traffic sites.
- [ ] WordPress Interactivity API evaluation once minimum supported WordPress moves to 6.5+.
