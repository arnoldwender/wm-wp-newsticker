=== WM Newsticker ===
Contributors: arnoldwender
Tags: news ticker, marquee, announcements, breaking news, gutenberg block
Requires at least: 5.9
Tested up to: 7.1
Stable tag: 1.4.8
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

**User Controls (Optional)**

* Play/Pause button
* Previous/Next navigation
* Progress indicator bar
* Position controls left or right

**Multilingual**

* Block editor translated into German and Spanish
* Text domain wm-newsticker; the PHP translation files in languages/ are mostly untranslated
* wpml-config.xml marks label, separator and headline texts as translatable for WPML

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

* Frontend stylesheet 6.9 KB (1.5 KB gzip), view script 2.8 KB (1.0 KB gzip), loaded only on pages with the block
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

Only with the controls: switch on the play/pause button in the block settings. Keyboard focus on a link does not pause the ticker.

= Is the block accessible? =

The markup has a labelled region, labelled buttons and a focus outline, and the animation does not run with "reduce motion". Known gaps: no pause on keyboard focus, 28 px controls, and the default label colours reach 3.83:1. No accessibility audit was made.

= How do I show recent posts instead of manual text? =

In the "Content Source" panel, change the source from "Manual announcements" to "Recent posts". Then configure the post type, number of posts, and optional filters.

= Can I filter posts by category? =

Yes, when using posts as the content source, you can filter by a specific category or tag.

== Screenshots ==

1. Newsticker block in the editor with live preview
2. Animation settings panel with type and direction options
3. User controls configuration (play/pause, navigation)
4. Content source selection - manual or dynamic posts
5. Colors panel with compact color pickers
6. Spacing and borders customization
7. Frontend display with scroll animation
8. Frontend display with typing animation
9. Mobile responsive design

== Changelog ==

= Unreleased =
* Documentation describes the real behaviour and its gaps (no keyboard-focus pause, 28 px controls, default label contrast 3.83:1); no accessibility conformity is claimed.
* Fix: `wp newsticker demodaten --reset` ended in a fatal error; `--import` printed "Array".
* Fix: cleaning up demo data deleted every post titled "[Demo]"; it now deletes only the plugin's own demo items.
* The demo content no longer invents news about real organisations; the demo page's tickers render.
* Handbook, widget and help describe the block's real behaviour (pause on hover, not on keyboard focus) and make no accessibility conformity claim.
* Handbook screen and dashboard widget on the Wender Media family sheets (shared tokens and components, visible focus rings, native wrap, family badge); no inline styles or scripts left in wp-admin.
* The handbook's live preview is the block's real output; the widget shows counted figures (pages with a ticker, demo posts, cached queries) instead of fixed text.
* New: the documented `wm_newsticker_rendered_items` filter is applied in render_block().

= 1.4.8 =
* Fix: the plugin's own links pointed at a repository that is not public, so "GitHub Repository" in the plugin list and the Plugin URI in the header were a 404 for everyone. They now point at github.com/arnoldwender/wm-wp-newsticker.
* Docs: the author line said "Lead Architect". In Germany "Architekt" is a title reserved by the Architektengesetz of each Land; the line now reads Web Developer, in every translated README.
* Docs: the author's legal role is stated once and correctly — Inhaber, Wender Media (Einzelunternehmen). Wender Media is one Einzelunternehmen; SEO Halle is one of its brands, not a second company.
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
