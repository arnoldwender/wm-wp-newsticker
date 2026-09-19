# Open items — WM Newsticker

Measured on 2026-09-15. The previous edition was a generic web-project checklist (address autocomplete with Google Places, exit-intent pop-ups, pricing pages, live chat) unrelated to this block.

## Accessibility

- [ ] Pause the motion while keyboard focus is inside the ticker (today: hover only).
- [ ] Controls at least 44 × 44 px (today 28 × 28 px, 24 × 24 px on narrow screens).
- [ ] Default label colours with at least 4.5:1 (today white on `#e94560`, 3.83:1).
- [ ] Translate the play / pause label that `src/frontend.js` sets after a click.
- [ ] Decide whether the controls should be on by default.

## Translations

- [ ] Translate the PHP catalogs or say in the readme that only the editor is translated (de_DE 2 of 171 entries, es_ES 3).

## Maintenance

- [ ] Rebuild check in CI (compare a fresh `npm run build` with the committed `build/`).
- [ ] `uninstall.php`: decide whether demo content is removed on uninstall.
