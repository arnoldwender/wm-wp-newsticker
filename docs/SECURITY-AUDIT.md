# Security notes — WM Newsticker

> Measured against the code on 2026-09-15. The previous edition was titled "Enterprise Security Audit & Threat Model"; it was a description, not an audit. A dated read-only review of 2026-08-25 is in BACKEND-SECURITY-AUDIT-2026-08-25.md. How to report a vulnerability: [SECURITY.md](../SECURITY.md).

## Input handling in `render_block()`

| Input | Check | Fallback |
| --- | --- | --- |
| Colours | `sanitize_color()`: hex with 3, 4, 6 or 8 digits, `rgb()` / `rgba()`, `var(--name)`, `transparent`, `inherit`, `currentcolor` (anchored regular expressions) | `#000000` |
| Padding, margin, radius, border width | `sanitize_css_value()`: a number with an optional unit px, em, rem, %, vh or vw (`^-?\d*\.?\d+(…)?$`) | `0px` |
| Speed, font size, height, posts count | `sanitize_number_range()` with `absint()` | the attribute's default when out of range |
| Animation, direction, controls position, border style, order by, order | strict `in_array()` allowlists | the default value |
| Box shadow | allowlist of five literal values | `none` |
| Headline text, label, separator | `sanitize_text_field()`; separator cut to 5 characters | — |
| Links | `esc_url_raw()` when read, `esc_url()` when printed | empty |
| Post type | `sanitize_key()`, then `get_post_type_object()` must exist and be `public` | `post` |
| Category and tag ids | `absint()` each | filter dropped |

Every printed value passes `esc_html()`, `esc_attr()` or `esc_url()`. Style attributes are built from the checked values only and escaped with `esc_attr()`; `safecss_filter_attr()` is not used because it removes `animation-duration` (see the 2026-08-25 review, L-1).

## Routes and actions

- `GET /wp-json/wm-newsticker/v1/post-types`: `permission_callback` `current_user_can( 'edit_posts' )`; returns public post types with `show_in_rest`, without `attachment`.
- Handbook forms (demo content, reset): `manage_options` and a nonce per form (`check_admin_referer`).
- Demo cleanup deletes only posts with the meta `_is_wm_newsticker_demo` (until 2026-09-15 also every post titled "[Demo]").
- The singleton blocks `__clone()` and throws in `__wakeup()`.

## Scripts

- `src/frontend.js` changes the page only through `querySelector`, `classList`, `setAttribute` and `style`; it inserts no HTML strings and evaluates no code.
- `src/index.js` builds REST paths with `addQueryArgs()`, cancels stale requests with `AbortController` and decodes titles with `decodeEntities()`.
- `build/*.asset.php` get their `ABSPATH` guard back after every build (`bin/harden-build-assets.mjs`, `postbuild`).

## Not in place

- No rebuild check in CI: `.github/workflows/` has `ci.yml` only (the 2026-08-25 review names a `verify-build.yml` that is not in the repository).
