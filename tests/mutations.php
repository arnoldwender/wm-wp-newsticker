<?php
/**
 * Mutations for tests/run-mutations.sh: each entry undoes one correction with literal replacements
 * [ file, search, replace ]; the standalone suite must turn red for every one of them.
 *
 * @package WM_Newsticker
 */

return [
	[
		'plugin header ahead of the other version fields',
		[
			[ 'wm-newsticker.php', ' * Version: 1.4.8', ' * Version: 1.4.9' ],
		],
	],
	[
		'package.json behind the header',
		[
			[ 'package.json', '"version": "1.4.8"', '"version": "1.4.7"' ],
		],
	],
	[
		'CHANGELOG newest release differs from the header',
		[
			[ 'CHANGELOG.md', '## [1.4.8] - 2026-09-19', '## [1.4.9] - 2026-09-19' ],
		],
	],
	[
		'adapter fallback back to a literal version',
		[
			[
				'includes/class-newsticker-spoke-adapter.php',
				<<<'SEARCH'
return preg_match( '/^\s*\*\s*Version:\s*(\S+)/m', $header, $m ) ? $m[1] : '';
SEARCH,
				"return '1.4.7';",
			],
		],
	],
	[
		'the suite defines the version constant itself again',
		[
			[
				'tests/test-suite.php',
				"\tif ( ! defined( 'WM_NEWSTICKER_PLUGIN_DIR' ) ) {",
				"\tif ( ! defined( 'WM_NEWSTICKER_VERSION' ) ) {\n\t\tdefine( 'WM_NEWSTICKER_VERSION', '1.4.6' );\n\t}\n\tif ( ! defined( 'WM_NEWSTICKER_PLUGIN_DIR' ) ) {",
			],
		],
	],
	[
		'WP-CLI --reset calls the method that never existed',
		[
			[ 'includes/class-newsticker-cli.php', '$instance->reset_to_factory_defaults();', '$instance->factory_reset();' ],
		],
	],
	[
		'WP-CLI --import prints the returned array again',
		[
			[ 'includes/class-newsticker-cli.php', '$res = $instance->seed_demo_data();', '$count = $instance->seed_demo_data(); $res = [ \'posts\' => 0, \'pages\' => 0 ];' ],
		],
	],
	[
		'demo cleanup deletes every "[Demo]" title again',
		[
			[ 'wm-newsticker.php', "\"SELECT DISTINCT post_id FROM {\$wpdb->postmeta} WHERE meta_key = '_is_wm_newsticker_demo'\"", "\"SELECT post_id FROM {\$wpdb->postmeta} WHERE meta_key = '_is_wm_newsticker_demo' UNION SELECT ID FROM {\$wpdb->posts} WHERE post_title LIKE '%[Demo]%'\"" ],
		],
	],
	[
		'demo seed publishes news about a real authority again',
		[
			[ 'wm-newsticker.php', "'Beispielmeldung: Der Ticker zeigt die neuesten Beiträge dieser Website [Demo]'", "'Cyberagentur stellt neuartigen Quanten-Sicherheitsstandard vor [Demo]'" ],
		],
	],
	[
		'showcase page ticker back on the ignored "mode" attribute',
		[
			[ 'wm-newsticker.php', '{"contentSource":"manual","animationType":"fade"', '{"mode":"manual","animationType":"fade"' ],
		],
	],
	[
		'help tab promises the keyboard-focus pause again',
		[
			[ 'wm-newsticker.php', "esc_html__( 'Die Bewegung hält bei Hover an (Blockeinstellung)", "esc_html__( 'Animationen stoppen bei Tastatur-Fokus (:focus-within). Die Bewegung hält bei Hover an (Blockeinstellung)" ],
		],
	],
	[
		'hub description claims WCAG 2.2 AA again',
		[
			[ 'includes/class-newsticker-spoke-adapter.php', "'Gutenberg news ticker block: scroll, fade, slide and typing animations with manual headlines or recent posts.'", "'High-Performance Gutenberg News Ticker with BFSG 2025 / WCAG 2.2 AA Accessibility.'" ],
		],
	],
];
