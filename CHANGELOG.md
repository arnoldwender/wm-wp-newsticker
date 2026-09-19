# Changelog

All notable changes to the **WM Newsticker** plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Family sheets (2026-09-16)
- Re-synced from the hub: the badge variants carry the state tokens on body surfaces, where the command-banner inks measured 1.49:1 to 2.21:1 against the composed background (hub PR #19), and the canonical sheet dropped five classes that belonged to a single plugin (hub PR #21). `tests/test-ui-family.php` re-pins both md5s.

### Design family (hackaton phase 3, 2026-09-14)
- The handbook screen (served by the top-level menu, the two submenus and the Settings entry) and the dashboard widget render from the two Wender Media family sheets (`assets/css/wm-admin-tokens.css`, `assets/css/wm-admin-ui.css`, byte-identical copies of the hub's canonical files, pinned by `tests/test-ui-family.php`) plus a plugin layer in `assets/css/newsticker-admin.css` (family tokens only). Before this the plugin enqueued nothing in wp-admin: the page was styled by 70 inline `style` attributes with 53 hex colours, two inline `onsubmit` handlers and 18 emoji. The confirmation questions now travel in `data-wm-confirm` and `assets/js/newsticker-admin.js`.
- Family header with the hub/standalone badge, family menu icon, native `.wrap.wm-admin`, `section` landmarks, dashicons instead of emoji (menu label, widget title, buttons, plugin action link), danger zone and destructive buttons on the family tokens, heading order h1 → h2 → h3.
- **The "Live-Vorschau" was a static box** with the words "LIVE DEMO" and no animation. It is now the block's real `render_block()` output with four manual headlines, controls and progress bar, plus the block's own frontend sheet and view script, so the handbook shows what the frontend renders.
- **Dashboard widget prints counted figures:** pages carrying the block, demo posts and cached ticker queries (cached 5 minutes under the plugin's transient prefix, flushed with the query cache on `save_post`). Before it printed a fixed "6 Stil-Presets", "60 FPS" and "A11Y" and read an option nobody writes; the banner always said "aktiv", it now reports whether `wm/newsticker` is registered.
- **`wm_newsticker_rendered_items` exists now.** The handbook and `docs/DEVELOPER-GUIDE.md` documented the filter since 1.1.0; `render_block()` never applied it. It runs before escaping, so filtered text and links still pass `esc_html()` / `esc_url()`. The handbook's ARIA note said `aria-live="polite"`; the block ships `aria-live="off"`, and the note says so now.
- Demo seed headlines and the showcase page separators carry no emoji (the plugin's content policy); an existing seeded set keeps its old titles until re-seeded, and the cleanup still finds them by the `[Demo]` suffix. Redirects after the demo actions and the plugin links point at `admin.php?page=wm-newsticker`, the registered slug.
- Test group 7 in `tests/test-suite.php` pins all of this (45 assertions; red when an inline style returns to the widget). `.gitignore` carries the `assets/` negation: the machine's global ignore rule kept the family sheets out of the repo. Captures before/after in `docs/screenshots/design-family/`.

### Fixed
- **Version 1.4.7 everywhere.** `readme.txt` had carried `Stable tag: 1.4.7` with its changelog entry since 2026-08-27 while the plugin header, `WM_NEWSTICKER_VERSION`, `package.json` and `block.json` still said 1.4.6 (Plugin Check: `stable_tag_mismatch`). The code is the 1.4.7 the readme describes; the four version fields now say so.
- **Minimum WordPress 5.9.** The contextual help checks `str_contains()`, which WordPress polyfills from 5.9; the header and readme said 5.8.
- Plugin Check (dist view, 2026-09-14): 6 → 0 errors. Translators comments on the two demo-data notices, the developer-docs heading translated under the plugin's own text domain (it carried `wm-prototype-nda-gate`, a copy-paste from that plugin's template), `.license-allowlist.yml` and tooling dotfiles kept out of the ZIP.

### Documentation (2026-09-15)
- `README.md`, `readme.txt`, `llms.txt` and `docs/` describe what the code does. They claimed conformity with BFSG 2025 and WCAG 2.2 AA (no audit exists), a pause on keyboard focus (hover only), 44 × 44 px controls (28 × 28 px), `:focus-visible` rings and an `<aside>` wrapper with polite announcements (a `<div>` with `aria-live="off"`), "60 FPS" `translate3d` animations (`translateX` keyframes), "~3KB CSS, ~1KB JS" (6.9 KB and 2.8 KB), compatibility with Polylang and named page builders (not tested), "DSGVO Art. 17 data erasure tools" (the plugin stores no personal data and registers none), "pre-compiled translations for 24 EU languages" (mostly copies of the source strings), WordPress 5.8 (5.9) and a test run of 28 assertions presented as the whole test suite.
- New in the README and `docs/ACCESSIBILITY.md`: the measured gaps — no keyboard-focus pause with controls off by default, 28 px controls, default label contrast 3.83:1, untranslated play/pause label, untranslated PHP catalogs, demo posts kept on uninstall. `docs/TODO.md` lists them instead of a generic web-project checklist; `docs/SECURITY.md` points to the one reporting policy; the dated 2026-08-25 review carries a note on what changed since.
- `llms.txt` said "Proprietary — All Rights Reserved" and recorded a licence change to proprietary on 2026-05-18; the plugin is GPLv2 or later (header, `LICENSE`, readme), as restored in PR #23.

### Fixed (2026-09-15)
- **`wp newsticker demodaten --reset` ended in a fatal error.** It called `WM_Newsticker::factory_reset()`, which never existed (the method is `reset_to_factory_defaults()`), and its confirmation could not be answered with `--yes` because the command's synopsis did not list it. `--import` printed the returned array ("Array Demo-Meldungen"). Test group 8 checks that every method the command calls exists.
- **"Demodaten bereinigen" and the factory reset force-deleted every post and page whose title contains "[Demo]"**, whoever created it. They delete only what the seed marked with `_is_wm_newsticker_demo` (every seeded item carries it).
- **The demo seed published invented news about real organisations:** a new quantum security standard of the Cyberagentur, a conference in Halle with "1,200 experts", new BFSG audit rules "in force", the DAX. The four seed posts are now marked example headlines about the plugin itself. Posts seeded earlier stay until "Demodaten bereinigen" removes them. The seeded showcase page set a `mode` attribute the block does not read, so both of its tickers rendered nothing; it uses `contentSource` and renders.
- **Handbook, dashboard widget, help tabs and the hub description promised more than the block does:** a pause on keyboard focus (`:focus-within`; the block pauses on hover only), "60 FPS GPU", "10s bis 120s", and conformity with BFSG 2025 and WCAG 2.2 AA (no audit was made). The texts describe the attributes and behaviour the code has; the help tab says that no BFSG or WCAG audit was carried out.
- **`tests/test-i18n.php` failed 3 of 7.** It expected "📖 Handbuch" to translate to "📖 Manual" / "Manuel" / "Manuale"; the string left the plugin on 2026-09-14 and the catalogs never held those translations. It now checks the files and that WordPress resolves a translated entry from a loaded `.mo`, and prints how many entries each catalog really translates (de_DE 2 of 171, es_ES 3 of 171).
- **The version tests checked their own mock.** `tests/test-suite.php` defined `WM_NEWSTICKER_VERSION` as 1.4.6 before loading the plugin, so the plugin's define was skipped and the assertions "Plugin version is 1.4.6", the adapter version and the SBOM `bom-ref` stayed green while the header said 1.4.7. The suite leaves the constant to `wm-newsticker.php` and requires header, constant, readme `Stable tag`, `package.json`, `block.json` and the newest release in this file to agree. The spoke adapter's fallback, a literal `'1.4.6'` for the case the constant is missing, reads the header instead.
- **This file skipped the plugin's release line.** It jumped from nothing to 1.0.0 / 1.1.0 / 1.1.1 (all 2026-08-27) while the plugin shipped 1.4.4 → 1.4.7. The entries [1.4.7] to [1.4.4] below are reconstructed from `git log -S` and `readme.txt`.

## [1.4.7] - 2026-08-27

Reconstructed on 2026-09-15. `readme.txt` set `Stable tag: 1.4.7` with its list of changes in `b134162` (2026-08-27); header, `WM_NEWSTICKER_VERSION`, `package.json` and `block.json` followed in `ee59393` (2026-09-14). The code changes behind it:

- `7537250`, `ddfd265`, `c5563d8` (2026-03-26): the security and hardening items of `readme.txt` `= 1.4.7 =` — CSS injection through an incomplete colour regex, path traversal in the REST path built from `postType`, public post type validation, singleton `__clone()` / `__wakeup()`, `defined()` guards on the constants, an editor field allowlist, `AbortController` on API fetches, `block.json` enum and minimum/maximum constraints, 4/8-digit hex and named colours, plus the accessibility and editor fixes listed there.
- `034ccad` … `25e459f` (2026-08-25 to 2026-08-27): ARIA role, transient query cache flushed on `save_post`, in-plugin handbook with preview and contextual help, demo data and factory reset, top-level admin menu, `wp newsticker` WP-CLI commands (`includes/class-newsticker-cli.php`), `.po` / `.mo` files in `languages/`, the `build/*.asset.php` guard. [1.1.1], [1.1.0] and [1.0.0] further down describe this work under a numbering that restarts at 1.0.0; they are kept as written.

## [1.4.6] - 2026-02-09

Reconstructed: `47031d3` "[Fix] WordPress.org review - v1.4.6" set the header to 1.4.6. `readme.txt` `= 1.4.6 =` lists `block.json` registration, the frontend script as an enqueued file instead of inline, `prefers-reduced-motion`, ARIA attributes on the ticker and its SVG icons, the `current_time()` and label visibility fixes and English code comments.

## [1.4.5]

Reconstructed: no commit in this repository carries version 1.4.5 (the history goes from 1.4.4 in `58a98e8` to 1.4.6 in `47031d3`). `readme.txt` `= 1.4.5 =`: source files in `/src/` and build instructions for WordPress.org.

## [1.4.4] - 2026-01-26

Reconstructed: `58a98e8` "[Init] WM Newsticker plugin v1.4.4", the first commit of this repository. `readme.txt` `= 1.4.4 =`: promotional content removed, author information updated, readme simplified. Versions 1.0.0 to 1.4.3 in `readme.txt` predate this repository.

## [1.1.1] - 2026-08-27

### Security
- **`build/*.asset.php` wurden ohne Direktzugriffsschutz ausgeliefert.** `build/` ist bewusst
  versioniert, damit das Plugin ohne Build-Schritt verteilt werden kann — damit sind die von
  `wp-scripts` erzeugten PHP-Dateien Teil des ausgelieferten Plugins und über HTTP erreichbar.
  Betroffen: `build/index.asset.php` und `build/frontend.asset.php`.
- Der Schutz wird von `bin/harden-build-assets.mjs` gesetzt, das als `postbuild` in der
  `package.json` hängt — von Hand gesetzt hielte er nur bis zum nächsten `npm run build`. Das
  Skript ist idempotent und **bricht ab**, wenn eine `.asset.php` nicht mit `<?php` beginnt oder
  gar keine gefunden wird, statt blind zu schreiben.

## [1.1.0] - 2026-08-27

### Added
- **WP-CLI Commands Suite (`wp newsticker`):**
  - `wp newsticker post-types`: Output all public post types registered for ticker feed querying.
  - `wp newsticker flush-cache`: Dynamic transient query cache purging.
  - `wp newsticker demodaten`: Demo data management (`--import`, `--delete`, `--reset`).
- **European Union Internationalization (24 EU Locales):**
  - Full GNU gettext `.po`/`.mo` translation packages for all 24 EU official languages.
- **Privacy & Accessibility Hardening:**
  - Full DSGVO Art. 17 data erasure compliance and 100% Zero-CDN guarantee.
- **Demodaten Management & Factory Reset:**
  - Dedicated Admin UI menu and WP-CLI commands for sample news generation and settings reset.

## [1.0.0] - 2026-08-27

### Added
- **Core Gutenberg Block Suite:**
  - Registered `wm/newsticker` dynamic block with block API v3.
  - Multi-mode animation engine (Marquee continuous scroll, smooth fade, directional slide, typewriter).
  - Dual content sources: Manual headlines repeater and dynamic WordPress query engine.
- **Performance & Concurrency Hardening:**
  - 60 FPS GPU hardware-accelerated CSS animations (`transform: translate3d`).
  - Transient query caching with dynamic MD5 cache keys to prevent database stampedes (Thundering Herd).
  - Automated cache purging on `save_post` and `deleted_post` hooks.
- **Accessibility & Compliance (BFSG 2025 / WCAG 2.2 AA):**
  - Semantic `<div role="region" aria-roledescription="marquee">` container.
  - Automatic pause on mouse hover and keyboard `:focus-within`.
  - Full respect for OS-level `prefers-reduced-motion: reduce`.
- **Defensive Engineering & Security:**
  - CSS injection defense (CWE-74) with regex token allowlists.
  - Server-side escaping on all HTML tokens via `esc_html()` and `esc_url()`.
  - Public post type verification and REST route permission callbacks (`edit_posts`).
- **Comprehensive Documentation & Testing:**
  - Added `docs/DEVELOPER-GUIDE.md` with complete API reference.
  - Standalone automated test suite in `tests/test-newsticker-core.php` (28/28 passing tests).
