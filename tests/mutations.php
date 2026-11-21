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
			[ 'wm-newsticker.php', ' * Version: 1.6.1', ' * Version: 1.6.2' ],
		],
	],
	[
		'package.json behind the header',
		[
			[ 'package.json', '"version": "1.6.1"', '"version": "1.6.0"' ],
		],
	],
	[
		'CHANGELOG newest release differs from the header',
		[
			[ 'CHANGELOG.md', '## [1.6.1] - 2026-11-21', '## [1.6.2] - 2026-11-21' ],
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
				"return '1.5.3';",
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
			[
				'wm-newsticker.php',
				<<<'SEARCH'
		$demo_post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s",
				'_is_wm_newsticker_demo'
			)
		);
SEARCH,
				"\t\t\$demo_post_ids = \$wpdb->get_col( \"SELECT post_id FROM {\$wpdb->postmeta} WHERE meta_key = '_is_wm_newsticker_demo' UNION SELECT ID FROM {\$wpdb->posts} WHERE post_title LIKE '%[Demo]%'\" );",
			],
		],
	],
	[
		'demo seed publishes news about a real authority again',
		[
			[ 'wm-newsticker.php', "'Beispielmeldung: Der Ticker zeigt die neuesten Beiträge dieser Website [Demo]'", "'Bundesamt stellt neuartigen Quanten-Sicherheitsstandard vor [Demo]'" ],
		],
	],
	[
		'showcase page ticker back on the ignored "mode" attribute',
		[
			[ 'wm-newsticker.php', '{"contentSource":"manual","animationType":"fade"', '{"mode":"manual","animationType":"fade"' ],
		],
	],
	[
		'help tab promises raw :focus-within conformity claim again',
		[
			[ 'wm-newsticker.php', "esc_html__( 'Die Bewegung hält bei Hover (Blockeinstellung) sowie bei Fokus auf einem Link im Ticker an", "esc_html__( 'Animationen stoppen bei Tastatur-Fokus (:focus-within). Die Bewegung hält bei Hover (Blockeinstellung)" ],
		],
	],
	[
		'SBOM licence back to the proprietary name',
		[
			[ 'includes/class-newsticker-spoke-adapter.php', "'id' => 'GPL-2.0-or-later',", "'name' => 'Proprietary Commercial License'," ],
		],
	],
	[
		'SBOM claims an accessibility standard again, in the spelling the old check missed',
		[
			[
				'includes/class-newsticker-spoke-adapter.php',
				"'name'  => 'wm:block:slug',",
				"'name'  => 'wm:standards:A11y',\n\t\t\t\t\t'value' => 'BFSG 2025 / WCAG 2.2 Level AA / BITV 2.0',\n\t\t\t\t],\n\t\t\t\t[\n\t\t\t\t\t'name'  => 'wm:block:slug',",
			],
		],
	],
	[
		'hub description claims WCAG 2.2 AA again',
		[
			[ 'includes/class-newsticker-spoke-adapter.php', "'Gutenberg news ticker block: scroll, fade, slide and typing animations with manual headlines or recent posts.'", "'High-Performance Gutenberg News Ticker with BFSG 2025 / WCAG 2.2 AA Accessibility.'" ],
		],
	],
	[
		'default labelBackgroundColor regresses to 3.83:1 #e94560',
		[
			[ 'block.json', '"default": "#b81d38"', '"default": "#e94560"' ],
		],
	],
	[
		'control button touch target drops below 44x44px',
		[
			[ 'src/style.scss', "    min-width: 44px;\n    min-height: 44px;", "    min-width: 28px;\n    min-height: 28px;" ],
		],
	],
	[
		'GenerateBlocks 2.x Dynamic Tag slug broken',
		[
			[ 'includes/class-newsticker-integrations.php', "'tag'         => 'wm_newsticker_headline',", "'tag'         => 'wm_broken_tag'," ],
		],
	],
	[
		'wm_newsticker_query_args stops enforcing publish post_status',
		[
			[ 'wm-newsticker.php', "\$filtered_args['post_status'] = 'publish';", "// post_status left unfiltered" ],
		],
	],
	[
		'uninstall.php deletes an option no version of the plugin writes again',
		[
			[ 'uninstall.php', "delete_option( 'wm_newsticker_delete_demo_on_uninstall' );", "delete_option( 'wm_newsticker_delete_demo_on_uninstall' );\ndelete_option( 'wm_newsticker_settings' );" ],
		],
	],
	[
		'the factory reset deletes an option no version of the plugin writes again',
		[
			[ 'wm-newsticker.php', "\t\tdelete_option( 'wm_newsticker_delete_demo_on_uninstall' );\n\t}", "\t\tdelete_option( 'wm_newsticker_delete_demo_on_uninstall' );\n\t\tdelete_option( 'wm_newsticker_settings' );\n\t}" ],
		],
	],
];
