# Developer guide — WM Newsticker

> Measured against the code on 2026-09-15 (version 1.4.7). The previous edition said version 1.0.0 and described a `wm-ticker-scroll` keyframe on `.wm-newsticker__track` with `translate3d` and a `:focus-within` pause, a `ResizeObserver` that clones items, `keydown` handlers for Space and Enter, a "Nachrichten-Ticker" label, a static scrollable reduced-motion view, "60fps" and "strict WCAG 2.2 AA". None of that is in the code.

## Layout

| Path | Role |
| --- | --- |
| `wm-newsticker.php` | Constants, hub filter, `WM_Newsticker` (block, REST route, cache, admin, demo content), WP-CLI include, plugin row links |
| `block.json` | Block `wm/newsticker`: attributes with defaults, scripts and styles |
| `src/index.js` → `build/index.js` | Editor: inspector panels, live preview, post and taxonomy fetches |
| `src/frontend.js` → `build/frontend.js` | View script: pause / play, rotation, controls |
| `src/style.scss` → `build/style-index.css`, `src/editor.scss` → `build/index.css` | Frontend and editor styles |
| `includes/class-newsticker-cli.php` | `wp newsticker` |
| `includes/class-newsticker-spoke-adapter.php` | WM Suite Hub contract |
| `assets/css/`, `assets/js/newsticker-admin.js` | Admin family sheets, plugin layer, confirmation dialogs |
| `languages/` | `.pot`, 28 `.po` / `.mo`, editor JSON for de_DE and es_ES |
| `bin/harden-build-assets.mjs` | `postbuild`: `ABSPATH` guard in `build/*.asset.php` |
| `tests/` | `test-suite.php` (standalone), `run-mutations.sh` + `mutations.php`, `test-ui-family.php`; `test-newsticker-core.php` and `test-i18n.php` run inside WordPress |

## `WM_Newsticker`

| Method | What it does |
| --- | --- |
| `get_instance()` | Singleton; `__clone()` private, `__wakeup()` throws |
| `register_block()` | Text domain, `register_block_type( WM_NEWSTICKER_PLUGIN_DIR, render_callback )`, `wp_set_script_translations()` for the editor script |
| `register_rest_routes()` / `get_post_types()` | `GET wm-newsticker/v1/post-types` (`edit_posts`): public post types with REST support, without attachments |
| `render_block( $attributes )` | Items → filter → checks → markup (see [ARCHITECTURE.md](ARCHITECTURE.md)) |
| `get_dynamic_items()` (private) | `WP_Query` + 5-minute transient keyed on the query |
| `flush_transient_cache()` | Deletes all `wm_newsticker_*` transients; on `save_post` and `deleted_post` |
| `sanitize_color()`, `sanitize_spacing()`, `sanitize_css_value()`, `sanitize_box_shadow()`, `sanitize_number_range()`, `sanitize_item()` (private) | Attribute checks ([SECURITY-AUDIT.md](SECURITY-AUDIT.md)) |
| `render_controls()`, `render_items()` (private) | Buttons with inline SVG; item links and separators |
| `enqueue_admin_assets( $hook_suffix )` | Family sheets and plugin layer on the plugin screens and `index.php`; block style, view script and dialog script on the plugin screens |
| `register_admin_menu()`, `render_admin_docs_page()`, `add_contextual_help()` | Handbook with live preview, three help tabs |
| `register_dashboard_widget()`, `render_dashboard_widget()`, `get_widget_stats()` (private) | Widget with counts cached 5 minutes |
| `handle_admin_actions()` | Seed / delete demo content, reset (`manage_options`, nonces) |
| `seed_demo_data()`, `delete_demo_data()`, `reset_to_factory_defaults()`, `flush_all_transients()` (static) | Four example posts and an example page marked `_is_wm_newsticker_demo`; deletion by that marker; reset = delete demo content + transients |

## Attributes (`block.json`)

`items` (array of text, link, newTab), `speed` 50, `pauseOnHover` true, `backgroundColor` #1a1a2e, `textColor` #ffffff, `labelText` "", `labelBackgroundColor` #e94560, `labelTextColor` #ffffff, `separator` •, `fontSize` 14, `height` 45, `animationType` scroll (scroll, fade, slide, typing), `direction` left (left, right, up, down), `isRTL` false, `showControls` false, `showPlayPause` true, `showPrevNext` true, `showProgress` false, `controlsPosition` right, `contentSource` manual (manual, posts), `postType` post, `postsCount` 5, `categoryIds` [], `tagIds` [], `orderBy` date, `order` DESC, `showDate` false, `dateFormat` relative, `borderRadius` / `padding` / `margin` (top, right, bottom, left), `borderWidth` 0px, `borderColor` #000000, `borderStyle` solid, `boxShadow` none.

## Filter

```php
add_filter( 'wm_newsticker_rendered_items', function ( array $items, array $attributes ): array {
	foreach ( $items as &$item ) {
		$item['text'] = 'Eilmeldung: ' . $item['text'];
	}
	return $items;
}, 10, 2 );
```

Items are `[ 'text' => string, 'link' => string, 'newTab' => bool ]`. The filter runs before escaping; text and links are escaped afterwards. Applied since 2026-09-14 (documented since 1.1.0 without being applied).

The plugin offers no other filters or actions.

## WP-CLI

```bash
wp newsticker post-types                 # all public post types (the editor's REST route excludes attachments)
wp newsticker flush-cache
wp newsticker demodaten --import         # four example posts and an example page
wp newsticker demodaten --delete         # posts and pages with _is_wm_newsticker_demo
wp newsticker demodaten --reset [--yes]  # delete demo content, clear the cache
```

## Gotchas

- The editor lists post types from the plugin's REST route and posts from `/wp/v2/<rest_base>`; a public post type without `show_in_rest` does not appear.
- `flush_transient_cache()` deletes every `wm_newsticker_*` transient, the widget counts included.
- With more than two scroll items the server duplicates the list; `render_block()` output for a scroll ticker therefore contains each headline twice.
