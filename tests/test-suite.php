<?php
/**
 * Standalone Test Suite for WM Newsticker (v1.5.2)
 *
 * Runs without external WordPress server or MySQL database (PHP 7.4 – 8.5 compatible).
 * Tests color sanitization, CSS injection defenses, numeric clamping,
 * HTML escaping/XSS immunity, accessibility markup & focus/touch defaults,
 * transient caching, Suite Hub Spoke Adapter, Block Bindings, GenerateBlocks 2.x
 * Dynamic Tags, Block Patterns, WP 6.9 Abilities, uninstall preference, and 28-locale i18n.
 *
 * @package WM_Newsticker
 */

declare(strict_types=1);

namespace ArnoldWender\SuiteHub\Contracts {
	if ( ! interface_exists( 'ArnoldWender\SuiteHub\Contracts\WM_Plugin_Module_Interface' ) ) {
		interface WM_Plugin_Module_Interface {
			public function get_module_slug(): string;
			public function get_name(): string;
			public function get_version(): string;
			public function get_description(): string;
			public function get_health_status(): string;
			public function get_sbom_data(): array;
		}
	}
}

namespace {

	if ( ! function_exists( 'str_contains' ) ) {
		function str_contains( string $haystack, string $needle ): bool {
			return '' === $needle || false !== strpos( $haystack, $needle );
		}
	}

	if ( ! function_exists( 'str_starts_with' ) ) {
		function str_starts_with( string $haystack, string $needle ): bool {
			return '' === $needle || 0 === strpos( $haystack, $needle );
		}
	}

	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ . '/../' );
	}
	if ( ! defined( 'WPINC' ) ) {
		define( 'WPINC', 'wp-includes' );
	}
	// WM_NEWSTICKER_VERSION is left to wm-newsticker.php. Until 2026-09-15 this file defined it as 1.4.6
	// first, the plugin's own define was skipped, and the three version assertions below checked this
	// mock instead of the plugin (header 1.4.7 from 2026-09-14 on, the suite still green on 1.4.6).
	if ( ! defined( 'WM_NEWSTICKER_PLUGIN_DIR' ) ) {
		define( 'WM_NEWSTICKER_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
	}
	if ( ! defined( 'WM_NEWSTICKER_PLUGIN_URL' ) ) {
		define( 'WM_NEWSTICKER_PLUGIN_URL', 'https://example.com/wp-content/plugins/wm-newsticker/' );
	}
	if ( ! defined( 'GENERATEBLOCKS_VERSION' ) ) {
		define( 'GENERATEBLOCKS_VERSION', '2.1.0' );
	}
	if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
		define( 'MINUTE_IN_SECONDS', 60 );
	}
	if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
		define( 'HOUR_IN_SECONDS', 3600 );
	}
	if ( ! defined( 'DAY_IN_SECONDS' ) ) {
		define( 'DAY_IN_SECONDS', 86400 );
	}

	// -------------------------------------------------------------
	// IN-MEMORY WP CORE & GENERATEBLOCKS MOCKS (PHP 7.4+ COMPATIBLE)
	// -------------------------------------------------------------
	$GLOBALS['mock_options']          = [];
	$GLOBALS['mock_transients']       = [];
	$GLOBALS['mock_blocks']           = [];
	$GLOBALS['mock_block_bindings']   = [];
	$GLOBALS['mock_block_patterns']   = [];
	$GLOBALS['mock_pattern_cats']     = [];
	$GLOBALS['mock_abilities']        = [];
	$GLOBALS['mock_gb_dynamic_tags']  = [];
	$GLOBALS['mock_filters']          = [];
	$GLOBALS['mock_filter_callbacks'] = [];
	$GLOBALS['mock_actions']          = [];
	$GLOBALS['mock_deleted_posts']    = [];
	$GLOBALS['mock_last_wp_query']    = [];

	function get_option( string $option, $default = false ) {
		return $GLOBALS['mock_options'][ $option ] ?? $default;
	}

	function update_option( string $option, $value, $autoload = null ): bool {
		$GLOBALS['mock_options'][ $option ] = $value;
		return true;
	}

	function delete_option( string $option ): bool {
		unset( $GLOBALS['mock_options'][ $option ] );
		return true;
	}

	function get_transient( string $transient ) {
		return $GLOBALS['mock_transients'][ $transient ] ?? false;
	}

	function set_transient( string $transient, $value, int $expiration = 0 ): bool {
		$GLOBALS['mock_transients'][ $transient ] = $value;
		return true;
	}

	function delete_transient( string $transient ): bool {
		unset( $GLOBALS['mock_transients'][ $transient ] );
		return true;
	}

	function sanitize_text_field( $str ): string {
		return trim( strip_tags( (string) $str ) );
	}

	function sanitize_key( $key ): string {
		return (string) preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
	}

	function wp_kses_post( string $data ): string {
		return strip_tags( $data, '<b><strong><i><em><a><span><br>' );
	}

	function wp_parse_args( $args, array $defaults = [] ): array {
		if ( is_object( $args ) ) {
			$args = get_object_vars( $args );
		}
		return array_merge( $defaults, (array) $args );
	}

	function absint( $maybeint ): int {
		return abs( (int) $maybeint );
	}

	function wp_strip_all_tags( string $text, bool $remove_breaks = false ): string {
		return trim( strip_tags( $text ) );
	}

	function sanitize_hex_color( string $color ): ?string {
		if ( '' === $color ) {
			return '';
		}
		if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
			return $color;
		}
		return null;
	}

	function esc_html( $text ): string {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}

	function esc_html__( string $text, string $domain = 'default' ): string {
		return $text;
	}

	function esc_html_e( string $text, string $domain = 'default' ): void {
		echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}

	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}

	function _e( string $text, string $domain = 'default' ): void {
		echo $text;
	}

	function esc_attr( $text ): string {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}

	function esc_attr__( string $text, string $domain = 'default' ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}

	function esc_attr_e( string $text, string $domain = 'default' ): void {
		echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}

	function esc_url( string $url ): string {
		if ( '' === $url ) {
			return '';
		}
		if ( str_starts_with( strtolower( trim( $url ) ), 'javascript:' ) ) {
			return '';
		}
		return filter_var( $url, FILTER_SANITIZE_URL ) ?: '';
	}

	function esc_url_raw( string $url ): string {
		return esc_url( $url );
	}

	function checked( $checked, $current = true, bool $display = true ): string {
		$result = (string) $checked === (string) $current ? "checked='checked'" : '';
		if ( $display ) {
			echo $result;
		}
		return $result;
	}

	function admin_url( string $path = '' ): string {
		return 'https://example.com/wp-admin/' . ltrim( $path, '/' );
	}

	function wp_delete_post( int $post_id = 0, bool $force_delete = false ) {
		$GLOBALS['mock_deleted_posts'][] = $post_id;
		return (object) [ 'ID' => $post_id ];
	}

	function wp_json_encode( $data, int $options = 0, int $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}

	function wp_unique_id( string $prefix = '' ): string {
		static $id = 0;
		return $prefix . ( ++$id );
	}

	function add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {}

	function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$GLOBALS['mock_filter_callbacks'][ $hook ][] = [ $callback, $accepted_args ];
	}

	function remove_all_filters( string $hook ): void {
		unset( $GLOBALS['mock_filter_callbacks'][ $hook ] );
	}

	function apply_filters( string $hook, $value, ...$args ) {
		$GLOBALS['mock_filters'][] = $hook;
		if ( ! empty( $GLOBALS['mock_filter_callbacks'][ $hook ] ) ) {
			foreach ( $GLOBALS['mock_filter_callbacks'][ $hook ] as $entry ) {
				list( $cb, $accepted ) = $entry;
				$call_args             = array_slice( array_merge( [ $value ], $args ), 0, max( 1, (int) $accepted ) );
				$value                 = call_user_func_array( $cb, $call_args );
			}
		}
		return $value;
	}

	function do_action( string $hook, ...$args ): void {
		$GLOBALS['mock_actions'][] = $hook;
	}

	function register_block_type( string $block_name, array $args = [] ): bool {
		$GLOBALS['mock_blocks'][ $block_name ] = $args;
		return true;
	}

	function register_block_bindings_source( string $source_name, array $source_properties ): bool {
		$GLOBALS['mock_block_bindings'][ $source_name ] = $source_properties;
		return true;
	}

	function register_block_pattern_category( string $category_name, array $category_properties ): bool {
		$GLOBALS['mock_pattern_cats'][ $category_name ] = $category_properties;
		return true;
	}

	function register_block_pattern( string $pattern_name, array $pattern_properties ): bool {
		$GLOBALS['mock_block_patterns'][ $pattern_name ] = $pattern_properties;
		return true;
	}

	function wp_register_ability( string $name, array $args ): bool {
		$GLOBALS['mock_abilities'][ $name ] = $args;
		return true;
	}

	class GenerateBlocks_Register_Dynamic_Tag {
		public function __construct( array $args ) {
			$tag = isset( $args['tag'] ) ? (string) $args['tag'] : '';
			if ( '' !== $tag ) {
				$GLOBALS['mock_gb_dynamic_tags'][ $tag ] = $args;
			}
		}
	}

	class WP_Block_Type_Registry {
		private static $instance = null;
		public static function get_instance(): self {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}
		public function is_registered( string $name ): bool {
			return 'wm/newsticker' === $name || isset( $GLOBALS['mock_blocks'][ $name ] );
		}
	}

	function plugin_dir_path( string $file ): string {
		return dirname( $file ) . '/';
	}

	function plugin_dir_url( string $file ): string {
		return 'https://example.com/wp-content/plugins/wm-newsticker/';
	}

	function plugin_basename( string $file ): string {
		return basename( dirname( $file ) ) . '/' . basename( $file );
	}

	function load_plugin_textdomain( string $domain, bool $deprecated = false, string $plugin_rel_path = '' ): bool {
		return true;
	}

	function wp_get_post_types( array $args = [], string $output = 'names' ): array {
		return [ 'post' => 'post', 'page' => 'page' ];
	}

	function get_post_type_object( string $post_type ): ?object {
		if ( in_array( $post_type, [ 'post', 'page' ], true ) ) {
			return (object) [ 'public' => true, 'name' => $post_type ];
		}
		return null;
	}

	function get_posts( array $args = [] ): array {
		return [
			(object) [
				'ID'         => 1,
				'post_title' => 'Breaking News: WM Suite Launched',
				'guid'       => 'https://example.com/post-1',
			],
			(object) [
				'ID'         => 2,
				'post_title' => 'Security: Zero-CDN Invariant Enforced',
				'guid'       => 'https://example.com/post-2',
			],
		];
	}

	function get_permalink( $post = 0 ): string {
		return 'https://example.com/post-1';
	}

	class WP_Query {
		public array $posts = [];
		public int $current_post = -1;
		public ?object $post = null;

		public function __construct( array $args = [] ) {
			$GLOBALS['mock_last_wp_query'] = $args;
			$this->posts                   = [
				(object) [
					'ID'         => 1,
					'post_title' => 'Breaking News: WM Suite Launched',
				],
				(object) [
					'ID'         => 2,
					'post_title' => 'Security: Zero-CDN Invariant Enforced',
				],
			];
		}

		public function have_posts(): bool {
			return $this->current_post + 1 < count( $this->posts );
		}

		public function the_post(): void {
			$this->current_post++;
			$this->post      = $this->posts[ $this->current_post ];
			$GLOBALS['post'] = $this->post;
		}
	}

	function get_the_ID(): int {
		return $GLOBALS['post']->ID ?? 1;
	}

	function get_the_title( $post = 0 ): string {
		return $GLOBALS['post']->post_title ?? 'Default Title';
	}

	function wp_reset_postdata(): void {}

	class Mock_WPDB_Newsticker {
		public string $prefix   = 'wp_';
		public string $options  = 'wp_options';
		public string $posts    = 'wp_posts';
		public string $postmeta = 'wp_postmeta';
		public array $demo_ids  = [ 101, 102 ];

		public function esc_like( string $text ): string {
			return addcslashes( $text, '_%\\' );
		}

		public function prepare( string $query, ...$args ): string {
			if ( 1 === count( $args ) && is_array( $args[0] ) ) {
				$args = $args[0];
			}
			$idx = 0;
			return (string) preg_replace_callback(
				'/%(?:d|s)/',
				static function ( array $m ) use ( $args, &$idx ): string {
					$val = $args[ $idx++ ] ?? '';
					if ( '%d' === $m[0] ) {
						return (string) ( (int) $val );
					}
					return "'" . addslashes( (string) $val ) . "'";
				},
				$query
			);
		}

		public function query( string $query ) {
			if ( str_contains( $query, 'transient_wm_newsticker_' ) || str_contains( $query, 'DELETE' ) ) {
				foreach ( array_keys( $GLOBALS['mock_transients'] ) as $k ) {
					if ( str_starts_with( $k, 'wm_newsticker_' ) ) {
						unset( $GLOBALS['mock_transients'][ $k ] );
					}
				}
			}
			return true;
		}

		public function get_col( string $query ): array {
			if ( str_contains( $query, '_is_wm_newsticker_demo' ) ) {
				return $this->demo_ids;
			}
			return [];
		}

		public function get_var( string $query ) {
			if ( str_contains( $query, 'COUNT(*)' ) && str_contains( $query, 'transient_wm_newsticker_' ) ) {
				$count = 0;
				foreach ( array_keys( $GLOBALS['mock_transients'] ) as $k ) {
					if ( str_starts_with( $k, 'wm_newsticker_' ) ) {
						$count++;
					}
				}
				return $count;
			}
			return 0;
		}
	}

	$GLOBALS['wpdb'] = new Mock_WPDB_Newsticker();

	// -------------------------------------------------------------
	// LOAD PLUGIN CODE UNDER TEST
	// -------------------------------------------------------------
	require_once __DIR__ . '/../includes/class-newsticker-spoke-adapter.php';
	require_once __DIR__ . '/../wm-newsticker.php';

	// ---------------------------------------------------------
	// TEST RUNNER
	// ---------------------------------------------------------
	$passed = 0;
	$failed = 0;

	function test_assert( bool $condition, string $description ): void {
		global $passed, $failed;
		if ( $condition ) {
			echo "  [PASS] {$description}\n";
			$passed++;
		} else {
			echo "  [FAIL] {$description}\n";
			$failed++;
		}
	}

	// Source without its comments: the comments explain what was wrong and quote the old claims, so the
	// claim checks read code only.
	function php_code_only( string $src ): string {
		$code = '';
		foreach ( token_get_all( $src ) as $tok ) {
			if ( ! is_array( $tok ) || ! in_array( $tok[0], [ T_COMMENT, T_DOC_COMMENT ], true ) ) {
				$code .= is_array( $tok ) ? $tok[1] : $tok;
			}
		}
		return $code;
	}

	/**
	 * Compute WCAG 2.x relative luminance contrast ratio between two 6-digit hex colours.
	 */
	function wcag_contrast_ratio( string $hex_a, string $hex_b ): float {
		$lum = static function ( string $hex ): float {
			$h = ltrim( $hex, '#' );
			$c = [];
			foreach ( [ 0, 2, 4 ] as $offset ) {
				$v   = hexdec( substr( $h, $offset, 2 ) ) / 255.0;
				$c[] = $v <= 0.03928 ? $v / 12.92 : pow( ( $v + 0.055 ) / 1.055, 2.4 );
			}
			return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
		};
		$l1 = $lum( $hex_a );
		$l2 = $lum( $hex_b );
		return ( max( $l1, $l2 ) + 0.05 ) / ( min( $l1, $l2 ) + 0.05 );
	}

	echo "===============================================================\n";
	echo "WM NEWSTICKER — STANDALONE TEST SUITE (v1.5.2)\n";
	echo "Environment: Standalone PHP " . PHP_VERSION . " | In-Memory Mock Engine\n";
	echo "===============================================================\n\n";

	$instance  = WM_Newsticker::get_instance();
	$reflector = new ReflectionClass( 'WM_Newsticker' );

	// ---------------------------------------------------------
	// 1. SINGLETON & INSTANCE INTEGRITY
	// ---------------------------------------------------------
	echo "[TEST GROUP 1] Singleton & Plugin Setup\n";
	test_assert( $instance instanceof WM_Newsticker, 'WM_Newsticker singleton instance instantiated' );
	$root_dir      = dirname( __DIR__ );
	$header_src    = (string) file_get_contents( $root_dir . '/wm-newsticker.php' );
	$readme_src    = (string) file_get_contents( $root_dir . '/readme.txt' );
	$changelog_src = (string) file_get_contents( $root_dir . '/CHANGELOG.md' );
	preg_match( '/^\s*\*\s*Version:\s*(\S+)/m', $header_src, $hv );
	preg_match( '/^Stable tag:\s*(\S+)/m', $readme_src, $rv );
	preg_match( '/^## \[(\d+\.\d+\.\d+)\]/m', $changelog_src, $cv );
	$block_meta = (array) json_decode( (string) file_get_contents( $root_dir . '/block.json' ), true );
	$versions   = [
		'header'                   => $hv[1] ?? null,
		'constant'                 => WM_NEWSTICKER_VERSION,
		'readme Stable tag'        => $rv[1] ?? null,
		'package.json'             => json_decode( (string) file_get_contents( $root_dir . '/package.json' ), true )['version'] ?? null,
		'block.json'               => $block_meta['version'] ?? null,
		'CHANGELOG newest release' => $cv[1] ?? null,
	];
	test_assert(
		null !== $versions['header'] && 1 === count( array_unique( $versions ) ),
		'Header, WM_NEWSTICKER_VERSION, readme Stable tag, package.json, block.json and the newest CHANGELOG release agree: ' . json_encode( $versions )
	);

	// ---------------------------------------------------------
	// 2. COLOR SANITIZATION & CSS INJECTION DEFENSES
	// ---------------------------------------------------------
	echo "\n[TEST GROUP 2] Color Sanitization & CSS Injection Defenses\n";
	$sanitize_color = $reflector->getMethod( 'sanitize_color' );
	if ( PHP_VERSION_ID < 80100 ) {
		$sanitize_color->setAccessible( true );
	}

	$hex3    = $sanitize_color->invoke( $instance, '#fff' );
	$hex6    = $sanitize_color->invoke( $instance, '#00ff88' );
	$hex8    = $sanitize_color->invoke( $instance, '#00ff88ff' );
	$rgb     = $sanitize_color->invoke( $instance, 'rgba(0, 255, 136, 0.8)' );
	$css_var = $sanitize_color->invoke( $instance, 'var(--primary-color)' );
	$named   = $sanitize_color->invoke( $instance, 'transparent' );
	$css_inj = $sanitize_color->invoke( $instance, 'red; background: url(https://evil.com/x.jpg)' );
	$xss_inj = $sanitize_color->invoke( $instance, '<script>alert(1)</script>' );

	test_assert( '#fff' === $hex3, 'Sanitizes 3-digit hex correctly' );
	test_assert( '#00ff88' === $hex6, 'Sanitizes 6-digit hex correctly' );
	test_assert( '#00ff88ff' === $hex8, 'Sanitizes 8-digit hex with alpha correctly' );
	test_assert( 'rgba(0, 255, 136, 0.8)' === $rgb, 'Sanitizes valid RGBA correctly' );
	test_assert( 'var(--primary-color)' === $css_var, 'Allows valid CSS custom properties (var)' );
	test_assert( 'transparent' === $named, 'Allows valid named CSS colors' );
	test_assert( '#000000' === $css_inj, 'Blocks CSS injection attempts (falls back to #000000)' );
	test_assert( '#000000' === $xss_inj, 'Blocks script tags in color fields' );

	// ---------------------------------------------------------
	// 3. NUMERIC CLAMPING & CSS VALUE SANITIZATION
	// ---------------------------------------------------------
	echo "\n[TEST GROUP 3] Numeric Clamping & CSS Value Sanitization\n";
	$sanitize_range = $reflector->getMethod( 'sanitize_number_range' );
	if ( PHP_VERSION_ID < 80100 ) {
		$sanitize_range->setAccessible( true );
	}

	$val_normal = $sanitize_range->invoke( $instance, 50, 10, 100, 50 );
	$val_under  = $sanitize_range->invoke( $instance, 2, 10, 100, 50 );
	$val_over   = $sanitize_range->invoke( $instance, 999, 10, 100, 50 );

	test_assert( 50 === $val_normal, 'Maintains valid numeric values within range' );
	test_assert( 50 === $val_under, 'Clamps under-range values to default' );
	test_assert( 50 === $val_over, 'Clamps over-range values to default' );

	$sanitize_css = $reflector->getMethod( 'sanitize_css_value' );
	if ( PHP_VERSION_ID < 80100 ) {
		$sanitize_css->setAccessible( true );
	}

	$css_valid = $sanitize_css->invoke( $instance, '15px' );
	$css_rem   = $sanitize_css->invoke( $instance, '1.5rem' );
	$css_evil  = $sanitize_css->invoke( $instance, '10px; position: fixed; top: 0' );

	test_assert( '15px' === $css_valid, 'Accepts valid pixel dimensions' );
	test_assert( '1.5rem' === $css_rem, 'Accepts valid rem dimensions' );
	test_assert( '0px' === $css_evil, 'Sanitizes dangerous multi-statement CSS values to 0px' );

	// ---------------------------------------------------------
	// 4. ACCESSIBILITY MARKUP & XSS IMMUNITY RENDERING
	// ---------------------------------------------------------
	echo "\n[TEST GROUP 4] Accessibility Markup & HTML Escaping\n";
	$rendered = $instance->render_block( array(
		'contentSource' => 'manual',
		'items'         => array(
			array(
				'text'   => 'Breaking: <script>alert("XSS")</script> <b>Clean Headline</b>',
				'link'   => 'javascript:alert(1)',
				'newTab' => false,
			),
		),
		'labelText'     => '<script>alert(2)</script>BREAKING',
	) );

	test_assert( ! empty( $rendered ), 'Renders block HTML for manual items' );
	test_assert( false === strpos( $rendered, '<script>' ), 'Strips or escapes <script> tags in text and labels' );
	test_assert( false === strpos( $rendered, 'href="javascript:' ), 'Neutralizes dangerous javascript: URI links' );
	test_assert( false !== strpos( $rendered, 'role="region"' ), 'Includes accessible role="region" landmark' );
	test_assert( false !== strpos( $rendered, 'aria-roledescription="marquee"' ), 'Includes aria-roledescription="marquee" for assistive tech' );

	// ---------------------------------------------------------
	// 5. TRANSIENT QUERY CACHING
	// ---------------------------------------------------------
	echo "\n[TEST GROUP 5] Transient Query Caching\n";
	$dynamic_render = $instance->render_block( array(
		'contentSource' => 'posts',
		'postType'      => 'post',
		'postsCount'    => 2,
	) );

	test_assert( is_string( $dynamic_render ), 'Dynamic query executes and renders output' );

	set_transient( 'wm_newsticker_posts_post_2', 'cached_content', 3600 );
	test_assert( 'cached_content' === get_transient( 'wm_newsticker_posts_post_2' ), 'Transient cache set successfully' );

	$instance->flush_transient_cache();
	test_assert( false === get_transient( 'wm_newsticker_posts_post_2' ), 'Cache flushed cleanly on mutation trigger' );

	// ---------------------------------------------------------
	// 6. SUITE HUB SPOKE ADAPTER & SBOM CONTRACT
	// ---------------------------------------------------------
	echo "\n[TEST GROUP 6] Suite Hub Spoke Adapter & SBOM Contract\n";
	$adapter = new Newsticker_Spoke_Adapter();

	test_assert( 'wm-newsticker' === $adapter->get_module_slug(), 'Module slug is "wm-newsticker"' );
	test_assert( 'WM Newsticker' === $adapter->get_name(), 'Module name is "WM Newsticker"' );
	test_assert( $versions['header'] === $adapter->get_version(), 'Module version is the plugin header version' );
	test_assert( $versions['header'] === Newsticker_Spoke_Adapter::header_version(), 'Adapter fallback without WM_NEWSTICKER_VERSION reads the header, not a literal' );
	test_assert( 'HEALTHY' === $adapter->get_health_status(), 'Module health status is HEALTHY' );

	$sbom = $adapter->get_sbom_data();
	test_assert( 'application' === $sbom['type'], 'SBOM type is "application"' );
	test_assert( 'pkg:wordpress/wm-newsticker@' . $versions['header'] === $sbom['bom-ref'], 'SBOM bom-ref names the plugin header version' );
	test_assert( ! empty( $sbom['hashes'] ), 'SBOM contains SHA-256 cryptographic hashes' );
	test_assert(
		[ [ 'license' => [ 'id' => 'GPL-2.0-or-later' ] ] ] === $sbom['licenses'],
		'SBOM declares the licence as the SPDX id GPL-2.0-or-later, alone: ' . json_encode( $sbom['licenses'] )
	);
	$telemetry = $adapter->get_telemetry_metrics();
	test_assert(
		'YES' === ( $telemetry['block_registered'] ?? '' ) && is_int( $telemetry['cached_query_transients'] ?? null ),
		'Spoke telemetry executes prepared SQL and reports registration & transient count'
	);

	// ---------------------------------------------------------
	// 7. ADMIN SURFACE ON THE WM SUITE FAMILY SHEETS
	// ---------------------------------------------------------
	echo "\n[TEST GROUP 7] Admin surface on the family sheets\n";
	$plugin_root = dirname( __DIR__ );
	$main_src    = (string) file_get_contents( $plugin_root . '/wm-newsticker.php' );

	test_assert(
		in_array( 'wm_newsticker_rendered_items', $GLOBALS['mock_filters'], true ),
		'render_block() applies the documented wm_newsticker_rendered_items filter'
	);

	$admin_start = strpos( $main_src, 'public function enqueue_admin_assets' );
	$admin_end   = strpos( $main_src, 'public function register_admin_menu' );
	$docs_start  = strpos( $main_src, 'public function render_admin_docs_page' );
	$docs_end    = strpos( $main_src, '// Initialize the plugin.' );
	$admin_src   = ( false === $admin_start || false === $admin_end ) ? '' : substr( $main_src, $admin_start, $admin_end - $admin_start );
	$admin_src  .= ( false === $docs_start || false === $docs_end ) ? '' : substr( $main_src, $docs_start, $docs_end - $docs_start );
	test_assert( '' !== $admin_src && false !== $admin_start, 'Main file carries enqueue_admin_assets() for the family sheets' );
	test_assert(
		false === strpos( $admin_src, 'style="' ) && false === strpos( $admin_src, '<style' ),
		'Admin markup (widget, header, handbook) carries no inline styles'
	);
	test_assert(
		0 === preg_match( '/\son(submit|click|input|change)=/', $admin_src ),
		'Handbook forms carry no inline event handlers (data-wm-confirm + assets/js instead)'
	);
	test_assert(
		0 === preg_match( '/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $admin_src ),
		'Admin strings carry no emoji (dashicons instead)'
	);
	test_assert(
		false !== strpos( $admin_src, 'assets/css/wm-admin-tokens.css' )
			&& false !== strpos( $admin_src, 'assets/css/wm-admin-ui.css' )
			&& false !== strpos( $admin_src, 'assets/css/newsticker-admin.css' )
			&& false !== strpos( $admin_src, 'assets/js/newsticker-admin.js' ),
		'enqueue_admin_assets() references the family sheets, the admin layer and the handbook script'
	);
	test_assert(
		false !== strpos( $admin_src, "wp_enqueue_style( 'wm-newsticker-style' )" ) && false !== strpos( $admin_src, "wp_enqueue_script( 'wm-newsticker-view-script' )" ),
		'The live preview loads the block\'s own frontend sheet and view script'
	);
	test_assert(
		false !== strpos( $admin_src, '$this->render_block( $this->demo_preview_attributes() )' ),
		'The live preview is the real render_block() output, not a static mock-up'
	);
	test_assert(
		false === strpos( $admin_src, '60 FPS</div>' ) && false === strpos( $admin_src, '$preset_count = 6' ),
		'Dashboard widget prints counted figures, no fixed telemetry'
	);

	foreach ( [ 'assets/css/wm-admin-tokens.css', 'assets/css/wm-admin-ui.css', 'assets/css/newsticker-admin.css', 'assets/js/newsticker-admin.js' ] as $asset ) {
		test_assert( file_exists( $plugin_root . '/' . $asset ), "Admin asset {$asset} ships with the plugin" );
	}

	$layer_src = (string) file_get_contents( $plugin_root . '/assets/css/newsticker-admin.css' );
	test_assert(
		0 === preg_match( '/#[0-9a-fA-F]{3,6}\b/', $layer_src ) && 0 === preg_match( '/outline\s*:\s*(none|0)\b/', $layer_src ),
		'Admin layer uses family tokens only and suppresses no focus outline'
	);

	// ---------------------------------------------------------
	// 8. CLI, DEMO CLEANUP AND UI COPY
	// ---------------------------------------------------------
	echo "\n[TEST GROUP 8] CLI, demo cleanup and UI copy\n";
	$main_code = php_code_only( $main_src );
	$cli_src   = (string) file_get_contents( $plugin_root . '/includes/class-newsticker-cli.php' );
	preg_match_all( '/\$instance->(\w+)\(/', $cli_src, $cli_calls );
	$missing_methods = array_values( array_filter( array_unique( $cli_calls[1] ), static fn( $m ) => ! method_exists( 'WM_Newsticker', $m ) ) );
	test_assert( count( $cli_calls[1] ) >= 3 && [] === $missing_methods, 'Every WM_Newsticker method the WP-CLI command calls exists: ' . json_encode( $missing_methods ) );
	test_assert(
		0 === preg_match( '/\$count\s*=\s*\$instance->seed_demo_data\(/', $cli_src ) && false !== strpos( $cli_src, "\$res['posts']" ) && false !== strpos( $cli_src, '[--yes]' ),
		'WP-CLI --import prints the counts, --reset accepts --yes for its confirmation'
	);

	$delete_src = substr( $main_src, (int) strpos( $main_src, 'public static function delete_demo_data' ), 1200 );
	test_assert(
		false === stripos( $delete_src, 'post_title LIKE' ) && false !== strpos( $delete_src, '_is_wm_newsticker_demo' ),
		'Demo cleanup deletes only posts carrying the plugin\'s demo marker, not every "[Demo]" title'
	);

	$seed_src = substr( $main_code, (int) strpos( $main_code, 'public static function seed_demo_data' ), 4000 );
	preg_match_all( "/'title'\s*=>\s*'([^']+)'/", $seed_src, $seed_titles );
	preg_match_all( '/<!-- wp:wm\/newsticker (\{.*?\}) \/-->/', $seed_src, $seed_blocks );
	$seed_attrs = array_map( static fn( $j ) => (array) json_decode( $j, true ), $seed_blocks[1] );
	$real_names = preg_match( '/Bundesamt|Agentur|DAX|Fachexperten|in Kraft|BFSG/u', $seed_src );
	test_assert(
		4 === count( $seed_titles[1] ) && 0 === $real_names && [] === array_filter( $seed_titles[1], static fn( $s ) => 0 !== strpos( $s, 'Beispielmeldung:' ) ),
		'Demo seed titles are marked examples and name no real organisation, market or law'
	);
	test_assert(
		2 === count( $seed_attrs ) && [] === array_filter( $seed_attrs, static fn( $a ) => ! isset( $a['contentSource'] ) || isset( $a['mode'] ) ),
		'Showcase page tickers use the contentSource attribute the block reads (the "mode" attribute rendered nothing)'
	);
	$manual_block = array_values( array_filter( $seed_attrs, static fn( $a ) => 'manual' === ( $a['contentSource'] ?? '' ) ) )[0] ?? [];
	test_assert(
		'' !== $instance->render_block( $manual_block ) && false !== strpos( $instance->render_block( $manual_block ), 'Zweite manuelle Meldung' ),
		'The showcase page\'s manual ticker renders its headlines'
	);

	$ui_src = substr( $main_code, (int) strpos( $main_code, 'private function demo_preview_attributes' ) );
	$claims = [];
	foreach ( [ '60 FPS', 'Tastaturfokus pausiert', 'focus-within', 'erfüllt alle Vorgaben', 'BFSG 2025 /', 'Barrierefreie Laufbänder', 'Thundering' ] as $claim ) {
		if ( false !== strpos( $ui_src, $claim ) ) {
			$claims[] = $claim;
		}
	}
	$adapter_code   = php_code_only( (string) file_get_contents( $plugin_root . '/includes/class-newsticker-spoke-adapter.php' ) );
	$adapter_claims = preg_match_all( '/WCAG|BFSG|BITV|60\s*FPS/i', $adapter_code, $adapter_hits ) ? $adapter_hits[0] : [];
	test_assert(
		[] === $claims && [] === $adapter_claims,
		'Handbook, widget, help tabs and the hub adapter make no 60 FPS or conformity claim: ' . json_encode( array_merge( $claims, $adapter_claims ) )
	);

	// ---------------------------------------------------------
	// 9. v1.5.2 ACCESSIBILITY, INTEGRATIONS, UNINSTALL & I18N
	// ---------------------------------------------------------
	echo "\n[TEST GROUP 9] v1.5.2 Accessibility, Block Bindings, GenerateBlocks 2.x, Abilities & 28-Locale i18n\n";
	$attrs_schema   = $block_meta['attributes'] ?? [];
	$default_lbl_bg = (string) ( $attrs_schema['labelBackgroundColor']['default'] ?? '' );
	$default_lbl_fg = (string) ( $attrs_schema['labelTextColor']['default'] ?? '' );
	$default_height = (int) ( $attrs_schema['height']['default'] ?? 0 );
	$default_ctrls  = (bool) ( $attrs_schema['showControls']['default'] ?? false );
	$lbl_contrast   = ( 1 === preg_match( '/^#[0-9a-fA-F]{6}$/', $default_lbl_bg ) && 1 === preg_match( '/^#[0-9a-fA-F]{6}$/', $default_lbl_fg ) )
		? wcag_contrast_ratio( $default_lbl_bg, $default_lbl_fg )
		: 0.0;

	test_assert(
		'#b81d38' === strtolower( $default_lbl_bg ) && $lbl_contrast >= 4.5,
		sprintf( 'Default labelBackgroundColor (%s on %s) achieves >= 4.5:1 contrast (%.2f:1)', $default_lbl_bg, $default_lbl_fg, $lbl_contrast )
	);
	test_assert(
		$default_height >= 48 && true === $default_ctrls,
		'block.json enables controls by default (showControls=true) and sets default height >= 48px'
	);
	test_assert(
		false !== strpos( $rendered, 'wm-has-controls' )
			&& false !== strpos( $rendered, 'height: 48px' )
			&& false !== strpos( $rendered, 'data-label-pause="Pause"' )
			&& false !== strpos( $rendered, 'data-label-play="Play"' ),
		'Default render_block() outputs controls, 48px height, and translated data-label-pause / data-label-play attributes'
	);
	test_assert(
		in_array( 'wm_newsticker_after_render', $GLOBALS['mock_actions'], true ),
		'render_block() fires wm_newsticker_after_render action'
	);

	$frontend_js = (string) file_get_contents( $plugin_root . '/src/frontend.js' );
	$style_scss  = (string) file_get_contents( $plugin_root . '/src/style.scss' );
	test_assert(
		false !== strpos( $frontend_js, "'focusin'" )
			&& false !== strpos( $frontend_js, "'focusout'" )
			&& false !== strpos( $frontend_js, 'dataset.labelPause' )
			&& false !== strpos( $frontend_js, 'dataset.labelPlay' ),
		'src/frontend.js pauses on keyboard focusin/focusout and reads translated dataset.labelPause / dataset.labelPlay'
	);
	test_assert(
		1 === preg_match( '/\.wm-control-btn\s*\{[^}]*min-width:\s*44px;[^}]*min-height:\s*44px;/s', $style_scss )
			&& false !== strpos( $style_scss, '.wm-newsticker-content:focus-within .wm-newsticker-track' ),
		'src/style.scss enforces 44x44px minimum touch target on .wm-control-btn and CSS :focus-within marquee pause'
	);

	// Query filter security invariant: post_status must remain 'publish' even when a filter mutates it.
	$instance->flush_transient_cache();
	add_filter(
		'wm_newsticker_query_args',
		static function ( array $args ): array {
			$args['post_status'] = 'private';
			$args['orderby']     = 'title';
			return $args;
		}
	);
	$instance->get_latest_items( [ 'postType' => 'post', 'postsCount' => 2 ] );
	remove_all_filters( 'wm_newsticker_query_args' );
	test_assert(
		'publish' === ( $GLOBALS['mock_last_wp_query']['post_status'] ?? '' ) && 'title' === ( $GLOBALS['mock_last_wp_query']['orderby'] ?? '' ),
		'wm_newsticker_query_args allows third-party query customization while enforcing post_status = publish'
	);

	// Block Bindings, GenerateBlocks 2.x Dynamic Tags, Block Patterns & WP 6.9 Abilities.
	WM_Newsticker_Integrations::register_block_bindings();
	WM_Newsticker_Integrations::register_generateblocks_tags();
	WM_Newsticker_Integrations::register_patterns();
	WM_Newsticker_Integrations::register_abilities();

	test_assert(
		isset( $GLOBALS['mock_block_bindings']['wm-newsticker/feed'] )
			&& 'Breaking News: WM Suite Launched' === WM_Newsticker_Integrations::resolve_binding_value( [ 'key' => 'headline', 'offset' => 0 ] )
			&& 'https://example.com/post-1' === WM_Newsticker_Integrations::resolve_binding_value( [ 'key' => 'url', 'offset' => 0 ] ),
		'WordPress 6.5+ Block Bindings source wm-newsticker/feed resolves headline text and URL'
	);
	test_assert(
		isset( $GLOBALS['mock_gb_dynamic_tags']['wm_newsticker_headline'] )
			&& 'Breaking News: WM Suite Launched' === WM_Newsticker_Integrations::render_generateblocks_tag( [ 'field' => 'headline', 'offset' => 0 ] ),
		'GenerateBlocks 2.x Dynamic Tag {{wm_newsticker_headline}} registers and renders cached headlines'
	);
	test_assert(
		isset( $GLOBALS['mock_block_patterns']['wm-newsticker/breaking-news-bar'] )
			&& isset( $GLOBALS['mock_block_patterns']['wm-newsticker/gb-breaking-section'] )
			&& false !== strpos( $GLOBALS['mock_block_patterns']['wm-newsticker/gb-breaking-section']['content'], 'gbp-section__inner' ),
		'Block Patterns register core ticker bar and two-layer GenerateBlocks gbp-section + gbp-section__inner section'
	);
	$ability_cb  = $GLOBALS['mock_abilities']['wm-newsticker/get-headlines']['execute_callback'] ?? null;
	$ability_out = is_callable( $ability_cb ) ? $ability_cb( [ 'post_type' => 'post', 'count' => 2 ] ) : [];
	test_assert(
		is_array( $ability_out ) && 2 === count( $ability_out ) && isset( $ability_out[0]['text'], $ability_out[0]['link'] ),
		'WordPress 6.9+ Ability wm-newsticker/get-headlines registers and executes cleanly'
	);

	// Uninstall demo retention vs opt-in deletion.
	if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
		define( 'WP_UNINSTALL_PLUGIN', true );
	}
	$GLOBALS['mock_deleted_posts'] = [];
	update_option( 'wm_newsticker_delete_demo_on_uninstall', '0' );
	require $plugin_root . '/uninstall.php';
	$kept_by_default = ( [] === $GLOBALS['mock_deleted_posts'] );

	$GLOBALS['mock_deleted_posts'] = [];
	update_option( 'wm_newsticker_delete_demo_on_uninstall', '1' );
	require $plugin_root . '/uninstall.php';
	$deleted_when_opted_in = ( [ 101, 102 ] === $GLOBALS['mock_deleted_posts'] );

	test_assert(
		$kept_by_default && $deleted_when_opted_in,
		'uninstall.php preserves demo posts by default and deletes _is_wm_newsticker_demo items when opted in'
	);

	// Until 1.6.1 uninstall.php and the factory reset deleted `wm_newsticker_settings`, an option no version of the
	// plugin ever wrote (git log -S): every option either of them deletes must be one the plugin writes.
	$nt_delete_src = (string) file_get_contents( $plugin_root . '/uninstall.php' );
	$nt_main_src   = (string) file_get_contents( $plugin_root . '/wm-newsticker.php' );
	if ( preg_match( '/function reset_to_factory_defaults\(\).*?\n\t}/s', $nt_main_src, $nt_reset ) ) {
		$nt_delete_src .= $nt_reset[0];
	}
	preg_match_all( "/delete_option\(\s*'([a-z0-9_]+)'/", $nt_delete_src, $nt_deleted );
	$nt_written_src = $nt_main_src;
	foreach ( glob( $plugin_root . '/includes/*.php' ) ?: [] as $nt_src_file ) {
		$nt_written_src .= (string) file_get_contents( $nt_src_file );
	}
	$nt_never_written = [];
	foreach ( array_unique( $nt_deleted[1] ) as $nt_option ) {
		if ( ! preg_match( "/(update_option|add_option|register_setting)\([^;]*'" . preg_quote( $nt_option, '/' ) . "'/", $nt_written_src ) ) {
			$nt_never_written[] = $nt_option;
		}
	}
	test_assert(
		isset( $nt_reset[0] ) && [] !== $nt_deleted[1] && [] === $nt_never_written,
		'uninstall.php and the factory reset delete only options the plugin writes' . ( $nt_never_written ? ': ' . implode( ', ', $nt_never_written ) : '' )
	);

	// Complete 28-locale catalog check (.po, .mo, Gutenberg JED .json).
	$po_count   = count( glob( $plugin_root . '/languages/wm-newsticker-*.po' ) ?: [] );
	$mo_count   = count( glob( $plugin_root . '/languages/wm-newsticker-*.mo' ) ?: [] );
	$json_count = count( glob( $plugin_root . '/languages/wm-newsticker-*-*.json' ) ?: [] );
	test_assert(
		28 === $po_count && 28 === $mo_count && 28 === $json_count,
		"All 28 locales ship complete .po ({$po_count}), .mo ({$mo_count}) and Gutenberg JED .json ({$json_count}) files"
	);

	echo "\n===============================================================\n";
	echo "RESULT: {$passed} PASSED | {$failed} FAILED\n";
	echo "===============================================================\n\n";

	if ( $failed > 0 ) {
		exit( 1 );
	}
	exit( 0 );
}
