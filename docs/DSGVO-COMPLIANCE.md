# Privacy — WM Newsticker

> Measured against the code on 2026-09-15. The previous edition was a "compliance specification" with legal bases per data point and a reference to TTDSG § 25 (renamed TDDDG in 2024). Which legal basis applies to a site is the site operator's assessment, not the plugin's; this file lists what the code does.

## Requests and storage

- **No external requests.** The block's scripts and styles are served from `build/` in the plugin; the control icons are inline SVG. The plugin loads no fonts and calls no third-party service. The editor fetches post types from the site's own REST route and posts from `/wp/v2/…` of the same site.
- **No cookies, no browser storage.** `src/frontend.js` sets no cookies and writes neither `localStorage` nor `sessionStorage`.
- **No tracking.** No analytics, no beacons, no fingerprinting.

## Data the plugin handles

| Data | Where it comes from | What happens |
| --- | --- | --- |
| Headlines, links, label (manual source) | block attributes saved in the post | rendered as text and links |
| Titles, permalinks, dates of published posts (posts source) | `WP_Query` on the site | rendered; cached 5 minutes in a transient `wm_newsticker_*` |
| Demo posts and example page | created on request from the handbook or `wp newsticker demodaten --import` | stored as normal posts with the meta `_is_wm_newsticker_demo` |
| Dashboard counts | counts over posts, post meta and options | cached 5 minutes in `wm_newsticker_widget_stats` |

The plugin processes no personal data of visitors. It does not register exporters or erasers for the WordPress privacy tools because it stores nothing about a person.

## Removal

`uninstall.php` deletes the plugin's transients. Demo posts stay until "Demodaten bereinigen" or `wp newsticker demodaten --delete` removes them.
