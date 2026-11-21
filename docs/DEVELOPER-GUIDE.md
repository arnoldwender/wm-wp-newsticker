# Developer guide — WM Newsticker

> Measured against the code on 2026-11-19 (version 1.5.2).

## Layout

| Path | Role |
| --- | --- |
| `wm-newsticker.php` | Constants, hub filter, `WM_Newsticker` (block, REST route, cache, admin, demo content, uninstall preference), integrations & WP-CLI includes, plugin row links |
| `block.json` | Block `wm/newsticker` (API v3): attributes with defaults, scripts and styles |
| `src/index.js` → `build/index.js` | Editor: inspector panels, live preview, post and taxonomy fetches |
| `src/frontend.js` → `build/frontend.js` | View script: pause / play, hover & keyboard-focus pause, rotation, controls |
| `src/style.scss` → `build/style-index.css`, `src/editor.scss` → `build/index.css` | Frontend and editor styles |
| `includes/class-newsticker-integrations.php` | `WM_Newsticker_Integrations`: WP 6.5+ Block Bindings (`wm-newsticker/feed`), GenerateBlocks 2.x Dynamic Tag (`{{wm_newsticker_headline}}`), Block Patterns, WP 6.9+ Ability (`wm-newsticker/get-headlines`) |
| `includes/class-newsticker-cli.php` | `WM_Newsticker_CLI_Command` (`wp newsticker`) |
| `includes/class-newsticker-spoke-adapter.php` | `Newsticker_Spoke_Adapter` (WM Suite Hub contract) |
| `assets/css/`, `assets/js/newsticker-admin.js` | Admin family sheets, plugin layer, confirmation dialogs |
| `languages/` | `.pot`, 28 `.po` / `.mo` catalogs, 28 Gutenberg editor JED `.json` catalogs |
| `bin/harden-build-assets.mjs` | `postbuild`: `ABSPATH` guard in `build/*.asset.php` |
| `tests/` | `test-suite.php` (standalone), `run-mutations.sh` + `mutations.php`, `test-ui-family.php`, `test-pot-freshness.php`; `test-newsticker-core.php` and `test-i18n.php` run inside WordPress |

## `WM_Newsticker` & `WM_Newsticker_Integrations`

| Method | What it does |
| --- | --- |
| `WM_Newsticker::get_instance()` | Singleton; `__clone()` private, `__wakeup()` throws |
| `register_block()` | Text domain, `register_block_type( WM_NEWSTICKER_PLUGIN_DIR, render_callback )`, `wp_set_script_translations()` for `wm-newsticker-editor-script` |
| `register_rest_routes()` / `get_post_types()` | `GET wm-newsticker/v1/post-types` (`edit_posts`): public post types with REST support, without attachments |
| `get_latest_items( array $attributes )` | Public accessor returning cached dynamic items passed through `wm_newsticker_rendered_items` |
| `render_block( $attributes )` | Items → `wm_newsticker_rendered_items` → sanitization → markup → `wm_newsticker_after_render` |
| `get_dynamic_items()` (private) | `wm_newsticker_query_args` filter (`post_status = 'publish'` enforced) + `WP_Query` + 5-minute transient |
| `flush_transient_cache()` | Deletes all `wm_newsticker_*` transients on `save_post` and `deleted_post` |
| `handle_admin_actions()` | Seed / delete demo content, save `wm_newsticker_delete_demo_on_uninstall`, factory reset (`manage_options`, nonces) |
| `WM_Newsticker_Integrations::init()` | Registers Block Bindings (`wm-newsticker/feed`), GenerateBlocks 2.x Dynamic Tag (`wm_newsticker_headline`), Block Patterns (`wm-newsticker/breaking-news-bar`, `wm-newsticker/gb-breaking-section`), and WP 6.9+ Ability (`wm-newsticker/get-headlines`) |

## Attributes (`block.json`)

`items` (array of `text`, `link`, `newTab`), `speed` 50, `pauseOnHover` true, `backgroundColor` `#1a1a2e`, `textColor` `#ffffff`, `labelText` `""`, `labelBackgroundColor` `#b81d38`, `labelTextColor` `#ffffff`, `separator` `•`, `fontSize` 14, `height` 48, `animationType` `scroll` (`scroll`, `fade`, `slide`, `typing`), `direction` `left` (`left`, `right`, `up`, `down`), `isRTL` false, `showControls` true, `showPlayPause` true, `showPrevNext` true, `showProgress` false, `controlsPosition` `right`, `contentSource` `manual` (`manual`, `posts`), `postType` `post`, `postsCount` 5, `categoryIds` `[]`, `tagIds` `[]`, `orderBy` `date`, `order` `DESC`, `showDate` false, `dateFormat` `relative`, `borderRadius` / `padding` / `margin` (`top`, `right`, `bottom`, `left`), `borderWidth` `0px`, `borderColor` `#000000`, `borderStyle` `solid`, `boxShadow` `none`.

## Hooks, Block Bindings & GenerateBlocks 2.x

```php
// 1. Filter WP_Query arguments for dynamic post tickers (post_status stays forced to 'publish')
add_filter( 'wm_newsticker_query_args', function ( array $args, array $attributes ): array {
	$args['meta_key'] = '_breaking_priority';
	return $args;
}, 10, 2 );

// 2. Filter ticker items before rendering (text and links are escaped afterwards)
add_filter( 'wm_newsticker_rendered_items', function ( array $items, array $attributes ): array {
	foreach ( $items as &$item ) {
		$item['text'] = 'Eilmeldung: ' . $item['text'];
	}
	return $items;
}, 10, 2 );

// 3. Observe rendered ticker instances
add_action( 'wm_newsticker_after_render', function ( string $unique_id, array $items, array $attributes ): void {
	// Custom integration logic
}, 10, 3 );
```

### WordPress 6.5+ Block Bindings (`wm-newsticker/feed`)

```html
<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"wm-newsticker/feed","args":{"key":"headline","post_type":"post","offset":0}}}}} -->
<p></p>
<!-- /wp:paragraph -->
```

### GenerateBlocks 2.x Dynamic Tag

Inside any GenerateBlocks 2.0+ Text or Element block:

```text
{{wm_newsticker_headline post_type:post|offset:0|field:headline}}
```

## WP-CLI

```bash
wp newsticker post-types                 # all public post types (the editor's REST route excludes attachments)
wp newsticker flush-cache
wp newsticker demodaten --import         # four example posts and an example page
wp newsticker demodaten --delete         # posts and pages with _is_wm_newsticker_demo
wp newsticker demodaten --reset [--yes]  # delete demo content, clear the cache and options
```

## Gotchas

- The editor lists post types from the plugin's REST route and posts from `/wp/v2/<rest_base>`; a public post type without `show_in_rest` does not appear.
- `flush_transient_cache()` deletes every `wm_newsticker_*` transient, the widget counts included.
- With more than two scroll items the server duplicates the list; `render_block()` output for a scroll ticker therefore contains each headline twice.
