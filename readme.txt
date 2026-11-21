=== WM Newsticker ===
Contributors: arnoldwender
Tags: news ticker, marquee, announcements, breaking news, gutenberg block
Requires at least: 5.9
Tested up to: 7.1
Stable tag: 1.6.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Animated news ticker block with scroll, fade, slide and typing animations. Dynamic posts support.

== Description ==

A Gutenberg block for creating animated news tickers. Supports multiple animation styles (scroll, fade, slide, typing), dynamic content from posts, RTL languages, and full customization.

= Features =

**Animation Styles**

* **Scroll (Marquee)** - Classic continuous horizontal/vertical scrolling
* **Fade** - Smooth fade in/out transitions
* **Slide** - Items slide in from any direction
* **Typing** - Typewriter effect with blinking cursor

**Dynamic Content Sources**

* Manual announcements with custom text and links
* Automatic display of recent WordPress posts
* Filter by category or tag
* Custom post type support
* Show post dates (relative or absolute format)

**Full Customization**

* 5 customizable colors (background, text, label, label text, border)
* Complete spacing controls (padding, margin)
* Border radius for rounded corners
* Border width, style, and color
* Box shadow presets (small, medium, large, inset)
* Adjustable speed, font size, and height
* RTL (Right-to-Left) language support

**User Controls (Enabled by Default)**

* Play/Pause button (minimum 44 x 44 px touch target, translated state labels)
* Previous/Next navigation
* Automatic pause on hover and when a link inside the ticker receives keyboard focus
* Progress indicator bar
* Position controls left or right

**Block Editor, GenerateBlocks 2.x & Developer Extensibility**

* WordPress 6.5+ Block Bindings source (`wm-newsticker/feed`) for binding headlines and URLs to Core Paragraph, Heading, and Button blocks
* GenerateBlocks 2.x Dynamic Tag (`{{wm_newsticker_headline}}`) when GenerateBlocks 2.0+ is active
* Ready-to-insert Block Patterns (`wm-newsticker/breaking-news-bar` and GenerateBlocks `wm-newsticker/gb-breaking-section`)
* WordPress 6.9+ Ability (`wm-newsticker/get-headlines`) plus PHP hooks (`wm_newsticker_query_args`, `wm_newsticker_rendered_items`, `wm_newsticker_after_render`)

**Multilingual**

* Block editor and PHP admin/frontend strings translated across 28 locales (`.po`, `.mo`, and Gutenberg JED `.json` catalogs)
* Text domain `wm-newsticker`
* `wpml-config.xml` marks label, separator and headline texts as translatable for WPML

= Perfect For =

* Breaking news and alerts
* Stock tickers and financial updates
* Event announcements
* Product promotions and sales
* Sports scores and updates
* Social media feed highlights
* Company announcements
* Emergency notifications

= Performance =

* Frontend stylesheet 7.2 KB (1.5 KB gzip), view script 3.5 KB (1.1 KB gzip), loaded only on pages with the block
* No jQuery dependency
* CSS keyframe animation for the scroll ticker
* No external API calls
* Server-side rendering; post queries cached for 5 minutes

= Security =

* All output escaped with esc_html(), esc_attr(), esc_url()
* All input sanitized with sanitize_text_field(), sanitize_key(), absint()
* Color values validated with strict anchored regex patterns
* Enum attributes validated with allowlists (strict in_array)
* Numeric attributes clamped within safe min/max ranges
* REST API endpoints protected with permission_callback
* Post type queries restricted to registered public types
* Block attributes constrained with JSON Schema (enum, minimum, maximum)
* Singleton pattern hardened against cloning and unserialization
* No innerHTML or eval() usage in frontend JavaScript

**No Support & Zero Liability ("AS IS"):** This plugin is provided free of charge as personal open-source software with **zero support** and **no private support**. Do not contact the author via email, `arnoldwender.com`, or direct messages. Report reproducible defects exclusively at `https://github.com/arnoldwender/wm-wp-newsticker/issues` (no SLA or response guarantee). Under GPLv2+ Sections 11-12 it is distributed **"AS IS"** without warranty, legal advice, or author liability.

== Installation ==

1. Upload the `wm-newsticker` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Create or edit a post/page and add the "Newsticker" block
4. Configure your ticker settings in the block sidebar
5. Publish the post

== Frequently Asked Questions ==

= Does this plugin work with the Classic Editor? =

No, WM Newsticker is a Gutenberg block and requires the WordPress Block Editor. If you're using the Classic Editor, you'll need to switch to Gutenberg or use a different plugin.

= Can I have multiple tickers on the same page? =

Yes! You can add as many Newsticker blocks as you need. Each ticker operates independently with its own settings.

= Does it work with custom post types? =

Yes, when using the "Recent posts" content source, you can select any public custom post type that has REST API support enabled.

= Is it compatible with page builders? =

It is a Gutenberg block. Page builders that can place Gutenberg blocks can show it; no page builder has been tested.

= Can I change the animation direction? =

Yes, you can choose from four directions: Left, Right, Up, or Down. This works with all animation types.

= How do I make the ticker full-width? =

Select the block and use the alignment options in the toolbar to choose "Wide width" or "Full width" alignment.

= Does it support RTL languages? =

Yes! Enable the RTL toggle in the Animation panel for proper right-to-left text display and animation direction.

= Can I pause the ticker on hover? =

Yes, the "Pause on hover" option is enabled by default. You can disable it in the Animation settings.

= Can visitors stop the ticker with the keyboard? =

Yes. Play/Pause and Previous/Next controls are shown by default (measuring at least 44 x 44 px with visible focus rings), and tabbing onto any link inside the ticker pauses the motion until focus leaves the ticker.

= Is the block accessible? =

The markup provides a labelled region (`role="region"`, `aria-roledescription="marquee"`, `aria-live="off"`), 44 x 44 px keyboard-operable controls with translated `Pause` / `Play` labels, visible `:focus-visible` outlines, automatic pause on hover and keyboard focus inside the ticker, default label contrast of 6.42:1 (`#b81d38` with `#ffffff`), and `prefers-reduced-motion: reduce` support. No formal external conformity audit has been conducted, and no legal conformity is claimed.

= How do I show recent posts instead of manual text? =

In the "Content Source" panel, change the source from "Manual announcements" to "Recent posts". Then configure the post type, number of posts, and optional filters.

= Can I filter posts by category? =

Yes, when using posts as the content source, you can filter by a specific category or tag.

= Is technical or private support provided? =

No. This plugin comes with zero support (`0` support). Private inquiries sent via email, `arnoldwender.com`, or social/chat channels are not answered. Reproducible bugs belong solely on GitHub Issues (`https://github.com/arnoldwender/wm-wp-newsticker/issues`) with no guarantee of reply or resolution. The software is provided **"AS IS"** at your sole risk with zero warranty or liability (see `SUPPORT.md` in the repository).

== Screenshots ==

1. WM Newsticker handbook screen with real server-rendered live preview and demo content controls
2. Gutenberg editor with wm/newsticker block, live ticker bar and sidebar animation controls
3. Published accessible news ticker bar with 44x44 px play/pause and navigation buttons
4. WordPress dashboard widget summarizing pages with the block, demo posts and cache state

== Changelog ==

= 1.6.1 =
* Removed: uninstalling the plugin and the handbook's factory reset no longer delete the option `wm_newsticker_settings`, which no version of the plugin ever wrote. Both still delete everything the plugin does write.

= 1.6.0 =
* The WM Suite Hub contract now lives in the `ArnoldWender\SuiteHub\Contracts` namespace. The spoke adapter registers only with WM Suite Hub 1.2.0 or later, so update the hub together with this plugin; used on its own, the plugin works as before.
* The `.wordpress-org/` banners show the current version.
* Removed: The archived pre-2026-11-19 marketing visuals in `versionen/`: they claimed features and conformity the plugin does not have. They were never part of the installable package.

= 1.5.3 =
* Removed the agency name, links to unrelated projects and the personal mailbox from code, catalogs and documentation; the admin family badge and the hub menu read "WM Suite". The READMEs say the plugin was built with an agentic workflow (AI coding agents) and should be reviewed like any third-party code.
* Added root `SUPPORT.md`, GitHub Issue templates, explicit zero-support & "AS IS" liability notices in READMEs and `readme.txt`, and normalized canonical GPLv2+ `arnoldwender.com` / `arnoldwender/wm-wp-newsticker` links.
* Updated `.wordpress-org/` visual banners to personal `ARNOLD WENDER · OPEN SOURCE` branding.

= 1.5.2 =
* Add `.wordpress-org/` vector icons, banners (`772x250`, `1544x500`), live WordPress screenshots (`screenshot-1..4.png`), and updated `== Screenshots ==` descriptions.
* Add `README.de.md` and `README.es.md` with language switcher badges, embed live `.wordpress-org/` previews in `README*.md`, and link the official plugin page (`https://arnoldwender.com/wordpress-plugins/wm-newsticker/`).
* Archive superseded mockups/screenshots under `versionen/visuals-pre-2026-11-19/` and update stale version notes in `docs/DEVELOPER-GUIDE.md`.

= 1.5.1 =
* Point Plugin URI to the official plugin page.

= 1.5.0 =
* Accessibility: Controls are enabled by default (`showControls: true`), measure at least 44 x 44 px on desktop and mobile (`height` default 48 px), carry `:focus-visible` outlines, and keep translated `Pause` / `Play` ARIA labels when toggled.
* Accessibility: Focusing a link inside the ticker pauses motion (`focusin` / `focusout` in `src/frontend.js` and `:focus-within` in `src/style.scss`), and the default label background (`#b81d38` on `#ffffff`) provides 6.42:1 contrast.
* Integrations: Added WordPress 6.5+ Block Bindings source (`wm-newsticker/feed`), GenerateBlocks 2.x Dynamic Tag (`{{wm_newsticker_headline}}`), two Block Patterns (`wm-newsticker/breaking-news-bar` and `wm-newsticker/gb-breaking-section`), WordPress 6.9+ Ability (`wm-newsticker/get-headlines`), `wm_newsticker_query_args` filter, and `wm_newsticker_after_render` action.
* Translations: Completed `.po`, `.mo`, and Gutenberg editor JED `.json` translations across all 28 bundled locales (`msgfmt --check-format` verified).
* Admin & Uninstall: Added opt-in handbook setting (`wm_newsticker_delete_demo_on_uninstall`, default off) allowing administrators to delete plugin-seeded demo posts/pages automatically upon uninstall.
* Hardening & Distribution: Wrapped all `$wpdb` queries in `$wpdb->prepare()`, added `declare(strict_types=1);` across all PHP entrypoints, added `.wordpress-org/blueprints/blueprint.json` for Live Preview, and included `/src/`, `package.json`, and `bin/` in the WordPress.org package for reproducible builds.

= 1.4.9 =
* Fix: the SBOM the plugin reports to the WM Suite Hub declared it proprietary; it now declares GPL-2.0-or-later, the plugin's licence. The notes of the 1.4.8 release announced this fix, but that package did not contain it.
* Fix: the hub adapter no longer reports accessibility conformity or "60 FPS". No code measured them and no accessibility audit was made; the telemetry reports what it counts.
* 30 of the 158 translatable strings had no entry in the .pot, so no translator could see them in any language. The catalog is regenerated from the code; every .po is merged and every .mo rebuilt, and the entries that no longer match a string are kept as obsolete instead of discarded. tests/test-i18n.php could not catch this: it checks that the files exist and that a .mo resolves a string, which stays true of a catalog that drifted away from the code. tests/test-pot-freshness.php and a CI step ask the other question.

= Also in the 1.4.7 and 1.4.8 releases (2026-09-19) =
* Documentation describes the real behaviour and its gaps (no keyboard-focus pause, 28 px controls, default label contrast 3.83:1); no accessibility conformity is claimed.
* Fix: `wp newsticker demodaten --reset` ended in a fatal error; `--import` printed "Array".
* Fix: cleaning up demo data deleted every post titled "[Demo]"; it now deletes only the plugin's own demo items.
* The demo content no longer invents news about real organisations; the demo page's tickers render.
* Handbook, widget and help describe the block's real behaviour (pause on hover, not on keyboard focus) and make no accessibility conformity claim.
* Handbook screen and dashboard widget on the WM Suite family sheets (shared tokens and components, visible focus rings, native wrap, family badge); no inline styles or scripts left in wp-admin.
* The handbook's live preview is the block's real output; the widget shows counted figures (pages with a ticker, demo posts, cached queries) instead of fixed text.
* New: the documented `wm_newsticker_rendered_items` filter is applied in render_block().

= 1.4.8 =
* Fix: the plugin's own links pointed at a repository that is not public, so "GitHub Repository" in the plugin list and the Plugin URI in the header were a 404 for everyone. They now point at github.com/arnoldwender/wm-wp-newsticker.
* Docs: the author line said "Lead Architect". In Germany "Architekt" is a title reserved by the Architektengesetz of each Land; the line now reads Web Developer, in every translated README.
* Tests: the mutation runner copied the tree with rsync and sent the error to /dev/null. rsync is absent from ordinary CI images, so the copy came out empty and the runner blamed the test suite. It now uses tar, reports what failed, and refuses a copy shorter than what git listed.

= 1.4.7 =
* Security: Fixed CSS injection via incomplete regex in color sanitization
* Security: Fixed path traversal in REST API path construction
* Security: Added public post type validation to prevent unauthorized content exposure
* Security: Hardened singleton pattern with __clone()/__wakeup() protection
* Security: Added defined() guards on plugin constants
* Security: Added field allowlist in editor to prevent prototype pollution
* Security: Added AbortController to prevent race conditions on API fetches
* Accessibility: ARIA labels now update dynamically on play/pause toggle
* Accessibility: prefers-reduced-motion now respected for JS-driven animations
* Fix: Hover no longer overrides manual pause state
* Fix: Play/pause icons now update correctly on hover in scroll ticker
* Fix: Timezone consistency using wp_date() and current_time()
* Fix: HTML entities in post titles now decoded correctly in editor preview
* Fix: Editor font size and height ranges now match server-side limits
* Hardening: Added enum constraints on all string-enum attributes in block.json
* Hardening: Added minimum/maximum on numeric attributes in block.json
* Hardening: Added items/properties schemas on array/object attributes in block.json
* Hardening: All dynamic class attributes now use esc_attr() (WPCS compliance)
* Hardening: Support for 4/8-digit hex colors and named colors (transparent, inherit)

= 1.4.6 =
* Added block.json metadata file for modern block registration
* Moved frontend JavaScript to separate enqueued file (replaces inline scripts)
* Added prefers-reduced-motion CSS support for accessibility
* Added ARIA attributes (role, aria-label, aria-live) for screen readers
* Added aria-hidden and focusable attributes to SVG icons
* Fixed deprecated current_time() usage
* Fixed label visibility bug (empty label now correctly hidden on frontend)
* Fixed version mismatch in editor panel
* Removed unused PanelColorSettings import
* Removed font-family override to respect theme fonts
* Removed unnecessary CSS !important declarations
* Translated all code comments to English

= 1.4.5 =
* Added source code and build instructions for WordPress.org compliance
* Included /src/ folder with human-readable source files

= 1.4.4 =
* Cleaned up promotional content
* Updated author information
* Simplified readme

= 1.4.3 =
* Fixed: Plugin URI and Author URI are now different (WordPress.org requirement)
* Fixed: Description changed to English

= 1.4.2 =
* Fixed: Plugin name in readme.txt now matches plugin header
* Fixed: Updated "Tested up to" to WordPress 6.9
* Fixed: Short description shortened to under 150 characters

= 1.4.1 =
* Fixed: Plugin Check compatibility - removed deprecated load_plugin_textdomain
* Fixed: Proper escaping for all output attributes
* Fixed: Using wp_print_inline_script_tag for inline scripts
* Removed: README.md (WordPress.org uses readme.txt)

= 1.4.0 =
* New: Complete spacing controls (padding, margin)
* New: Border radius customization
* New: Border width, style, and color options
* New: Box shadow presets
* Improved: Redesigned color controls with compact pickers
* Improved: Better color input with hex field

= 1.3.0 =
* New: Dynamic content from WordPress posts
* New: Filter posts by category or tag
* New: Custom post type support
* New: Show post dates (relative/absolute)
* New: Order posts by date, title, modified, random, or comments

= 1.2.0 =
* New: Optional user controls (play/pause, prev/next)
* New: Progress indicator bar
* New: Controls position option (left/right)

= 1.1.0 =
* New: Fade animation type
* New: Slide animation type
* New: Typing (typewriter) animation
* New: Directional controls (left, right, up, down)
* New: RTL language support

= 1.0.0 =
* Initial release
* Scroll (marquee) animation
* Manual announcements with links
* Customizable colors and styling
* Multilingual support (EN, DE, ES)

== Upgrade Notice ==

= 1.6.1 =
Maintenance release: uninstall and factory reset stop deleting an option the plugin never wrote. Nothing changes for visitors or editors.

= 1.6.0 =
Hub users: update WM Suite Hub to 1.2.0 together with this plugin. The hub contract moved to a new PHP namespace; next to an older hub the plugin keeps working, but it is not listed in the cockpit and its admin page has no menu entry until the hub is updated.

= 1.4.0 =
Major update with full spacing and border customization. Redesigned color controls for better usability.

= 1.3.0 =
New dynamic content feature! Display your latest posts automatically in the ticker.

== Development / Source Code ==

The unminified source files for the compiled assets in `/build/` are included in this plugin under `/src/`.

Source repository: https://github.com/arnoldwender/wm-wp-newsticker

= Build Instructions =

1. `npm install`
2. `npm run build`

This generates:

* `build/index.js` - Compiled editor script
* `build/index.css` - Compiled editor styles
* `build/style-index.css` - Compiled frontend styles

= Source Files =

* `src/index.js` - Block editor component (React/JSX)
* `src/editor.scss` - Editor styles (SCSS)
* `src/style.scss` - Frontend styles (SCSS)

The build uses `@wordpress/scripts` which handles Webpack, Babel, and PostCSS configuration.

== Additional Information ==

= Requirements =

* WordPress 5.9 or higher
* PHP 7.4 or higher
* Gutenberg editor enabled

= Support =

For support, feature requests, or bug reports, use the WordPress.org support forum or open an issue on GitHub.

= Privacy =

This plugin does not collect any user data, does not use cookies, and does not connect to external services.
