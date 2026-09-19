<?php
/**
 * Standalone Test Suite for WM Newsticker (Round 21)
 *
 * Runs without external WordPress server or MySQL database.
 * Tests color sanitization, CSS injection defenses, numeric clamping,
 * HTML escaping/XSS immunity, BFSG 2025 accessibility markup,
 * transient caching, and Suite Hub Spoke Adapter integration.
 *
 * @package WM_Newsticker
 */

declare(strict_types=1);

namespace WenderMedia\SuiteHub\Contracts {
	if ( ! interface_exists( 'WenderMedia\SuiteHub\Contracts\WM_Plugin_Module_Interface' ) ) {
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
	// IN-MEMORY WP CORE MOCKS
	// -------------------------------------------------------------
	$GLOBALS['mock_options']    = [];
	$GLOBALS['mock_transients'] = [];
	$GLOBALS['mock_blocks']     = [];

	function get_option( string $option, mixed $default = false ): mixed {
		return $GLOBALS['mock_options'][ $option ] ?? $default;
	}

	function update_option( string $option, mixed $value, mixed $autoload = null ): bool {
		$GLOBALS['mock_options'][ $option ] = $value;
		return true;
	}

	function delete_option( string $option ): bool {
		unset( $GLOBALS['mock_options'][ $option ] );
		return true;
	}

	function get_transient( string $transient ): mixed {
		return $GLOBALS['mock_transients'][ $transient ] ?? false;
	}

	function set_transient( string $transient, mixed $value, int $expiration = 0 ): bool {
		$GLOBALS['mock_transients'][ $transient ] = $value;
		return true;
	}

	function delete_transient( string $transient ): bool {
		unset( $GLOBALS['mock_transients'][ $transient ] );
		return true;
	}

	function sanitize_text_field( string $str ): string {
		return trim( strip_tags( $str ) );
	}

	function sanitize_key( string $key ): string {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) );
	}

	function wp_kses_post( string $data ): string {
		return strip_tags( $data, '<b><strong><i><em><a><span><br>' );
	}

	function wp_parse_args( mixed $args, array $defaults = [] ): array {
		if ( is_object( $args ) ) {
			$args = get_object_vars( $args );
		}
		return array_merge( $defaults, (array) $args );
	}

	function absint( mixed $maybeint ): int {
		return abs( (int) $maybeint );
	}

	function wp_strip_all_tags( string $text, bool $remove_breaks = false ): string {
		return trim( strip_tags( $text ) );
	}

	function sanitize_hex_color( string $color ): ?string {
		if ( '' === $color ) {
			return '';
		}
		// 3 or 6 hex digits
		if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
			return $color;
		}
		return null;
	}

	function esc_html( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
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

	function esc_attr( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
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

	function wp_json_encode( mixed $data, int $options = 0, int $depth = 512 ): string|false {
		return json_encode( $data, $options, $depth );
	}

	function wp_unique_id( string $prefix = '' ): string {
		static $id = 0;
		return $prefix . ( ++$id );
	}

	function add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {}
	function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {}
	$GLOBALS['mock_filters'] = [];
	function apply_filters( string $hook, mixed $value, mixed ...$args ): mixed {
		$GLOBALS['mock_filters'][] = $hook;
		return $value;
	}
	function do_action( string $hook, mixed ...$args ): void {}

	function register_block_type( string $block_name, array $args = [] ): bool {
		$GLOBALS['mock_blocks'][ $block_name ] = $args;
		return true;
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
				'post_title' => 'Breaking News: Wender Media Suite Launched',
				'guid'       => 'https://example.com/post-1',
			],
			(object) [
				'ID'         => 2,
				'post_title' => 'Security: Zero-CDN Invariant Enforced',
				'guid'       => 'https://example.com/post-2',
			],
		];
	}

	function get_permalink( mixed $post = 0 ): string {
		return 'https://example.com/post-1';
	}

	class WP_Query {
		public array $posts = [];
		public int $current_post = -1;
		public ?object $post = null;

		public function __construct( array $args = [] ) {
			$this->posts = [
				(object) [
					'ID'         => 1,
					'post_title' => 'Breaking News: Wender Media Suite Launched',
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
			$this->post = $this->posts[ $this->current_post ];
			$GLOBALS['post'] = $this->post;
		}
	}

	function get_the_ID(): int {
		return $GLOBALS['post']->ID ?? 1;
	}

	function get_the_title( mixed $post = 0 ): string {
		return $GLOBALS['post']->post_title ?? 'Default Title';
	}

	function wp_reset_postdata(): void {}

	class Mock_WPDB_Newsticker {
		public string $prefix = 'wp_';
		public string $options = 'wp_options';

		public function query( string $query ): int|bool {
			if ( str_contains( $query, '_transient_wm_newsticker_' ) || str_contains( $query, 'DELETE' ) ) {
				foreach ( array_keys( $GLOBALS['mock_transients'] ) as $k ) {
					if ( str_starts_with( $k, 'wm_newsticker_' ) ) {
						unset( $GLOBALS['mock_transients'][ $k ] );
					}
				}
			}
			return true;
		}

		public function get_var( string $query ): mixed {
			if ( str_contains( $query, 'COUNT(*)' ) && str_contains( $query, '_transient_wm_newsticker_' ) ) {
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

	echo "===============================================================\n";
	echo "WM NEWSTICKER — STANDALONE TEST SUITE (ROUND 21)\n";
	echo "Environment: Standalone PHP " . PHP_VERSION . " | In-Memory Mock Engine\n";
	echo "===============================================================\n\n";

	$instance = WM_Newsticker::get_instance();
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
	$versions = [
		'header'           => $hv[1] ?? null,
		'constant'         => WM_NEWSTICKER_VERSION,
		'readme Stable tag' => $rv[1] ?? null,
		'package.json'     => json_decode( (string) file_get_contents( $root_dir . '/package.json' ), true )['version'] ?? null,
		'block.json'       => json_decode( (string) file_get_contents( $root_dir . '/block.json' ), true )['version'] ?? null,
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

	$hex3     = $sanitize_color->invoke( $instance, '#fff' );
	$hex6     = $sanitize_color->invoke( $instance, '#00ff88' );
	$hex8     = $sanitize_color->invoke( $instance, '#00ff88ff' );
	$rgb      = $sanitize_color->invoke( $instance, 'rgba(0, 255, 136, 0.8)' );
	$css_var  = $sanitize_color->invoke( $instance, 'var(--primary-color)' );
	$named    = $sanitize_color->invoke( $instance, 'transparent' );
	$css_inj  = $sanitize_color->invoke( $instance, 'red; background: url(https://evil.com/x.jpg)' );
	$xss_inj  = $sanitize_color->invoke( $instance, '<script>alert(1)</script>' );

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

	$val_normal = $sanitize_range->invoke( $instance, 50, 10, 100, 50 );
	$val_under  = $sanitize_range->invoke( $instance, 2, 10, 100, 50 );
	$val_over   = $sanitize_range->invoke( $instance, 999, 10, 100, 50 );

	test_assert( 50 === $val_normal, 'Maintains valid numeric values within range' );
	test_assert( 50 === $val_under, 'Clamps under-range values to default' );
	test_assert( 50 === $val_over, 'Clamps over-range values to default' );

	$sanitize_css = $reflector->getMethod( 'sanitize_css_value' );

	$css_valid = $sanitize_css->invoke( $instance, '15px' );
	$css_rem   = $sanitize_css->invoke( $instance, '1.5rem' );
	$css_evil  = $sanitize_css->invoke( $instance, '10px; position: fixed; top: 0' );

	test_assert( '15px' === $css_valid, 'Accepts valid pixel dimensions' );
	test_assert( '1.5rem' === $css_rem, 'Accepts valid rem dimensions' );
	test_assert( '0px' === $css_evil, 'Sanitizes dangerous multi-statement CSS values to 0px' );

	// ---------------------------------------------------------
	// 4. ACCESSIBILITY (BFSG 2025) & XSS IMMUNITY RENDERING
	// ---------------------------------------------------------
	echo "\n[TEST GROUP 4] Accessibility (BFSG 2025) & HTML Escaping\n";
	$rendered = $instance->render_block( array(
		'contentSource' => 'manual',
		'items'         => array(
			array(
				'text'   => 'Breaking: <script>alert("XSS")</script> <b>Clean Headline</b>',
				'link'   => 'javascript:alert(1)',
				'newTab' => false,
			),
		),
		'label'         => '<script>alert(2)</script>BREAKING',
	) );

	test_assert( ! empty( $rendered ), 'Renders block HTML for manual items' );
	test_assert( false === strpos( $rendered, '<script>' ), 'Strips or escapes <script> tags in text and labels' );
	test_assert( false === strpos( $rendered, 'href="javascript:' ), 'Neutralizes dangerous javascript: URI links' );
	test_assert( false !== strpos( $rendered, 'role="region"' ), 'Includes accessible role="region" landmark for BFSG 2025' );
	test_assert( false !== strpos( $rendered, 'aria-roledescription="marquee"' ), 'Includes aria-roledescription="marquee" for assistive tech' );

	// ---------------------------------------------------------
	// 5. TRANSIENT QUERY CACHING & STAMPEDE DEFENSE
	// ---------------------------------------------------------
	echo "\n[TEST GROUP 5] Transient Query Caching\n";
	$dynamic_render = $instance->render_block( array(
		'contentSource' => 'posts',
		'postType'      => 'post',
		'postsCount'    => 2,
	) );

	test_assert( is_string( $dynamic_render ), 'Dynamic query executes and renders output' );

	// Prime mock transient
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

	// ---------------------------------------------------------
	// 7. ADMIN SURFACE ON THE WENDER MEDIA FAMILY SHEETS (2026-09-14)
	// Before: the handbook page carried 70 inline style attributes with 53 hex colours, two
	// inline onsubmit handlers and 18 emoji; nothing was enqueued in wp-admin; the widget printed
	// fixed "6 Stil-Presets / 60 FPS / A11Y"; the documented wm_newsticker_rendered_items filter
	// was never applied.
	// ---------------------------------------------------------
	echo "\n[TEST GROUP 7] Admin surface on the family sheets\n";
	$plugin_root = dirname( __DIR__ );
	$main_src    = (string) file_get_contents( $plugin_root . '/wm-newsticker.php' );

	test_assert(
		in_array( 'wm_newsticker_rendered_items', $GLOBALS['mock_filters'], true ),
		'render_block() applies the documented wm_newsticker_rendered_items filter'
	);

	// The admin surface: enqueue + icon + header + preview attributes + widget (one block up to
	// register_admin_menu) and the handbook page (render_admin_docs_page to the end of the class).
	// The demo seed data in between is content, not UI, and stays out of these checks.
	$admin_start   = strpos( $main_src, 'public function enqueue_admin_assets' );
	$admin_end     = strpos( $main_src, 'public function register_admin_menu' );
	$docs_start    = strpos( $main_src, 'public function render_admin_docs_page' );
	$docs_end      = strpos( $main_src, '// Initialize the plugin.' );
	$admin_src     = ( false === $admin_start || false === $admin_end ) ? '' : substr( $main_src, $admin_start, $admin_end - $admin_start );
	$admin_src    .= ( false === $docs_start || false === $docs_end ) ? '' : substr( $main_src, $docs_start, $docs_end - $docs_start );
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
	// 8. CLI, DEMO CLEANUP AND UI COPY (2026-09-15)
	// Before: `wp newsticker demodaten --reset` called a method that never existed (fatal), --import printed
	// "Array", the demo cleanup force-deleted every post titled "[Demo]", the seed published invented news
	// about the Cyberagentur and the DAX, its showcase page used an attribute the block ignores (two empty
	// tickers), and the handbook, widget and help promised a keyboard-focus pause and BFSG 2025 / WCAG 2.2
	// AA conformity.
	// ---------------------------------------------------------
	echo "\n[TEST GROUP 8] CLI, demo cleanup and UI copy\n";
	// Comments explain what was wrong and quote the old claims; the checks read code only.
	$main_code = '';
	foreach ( token_get_all( $main_src ) as $tok ) {
		if ( ! is_array( $tok ) || ! in_array( $tok[0], [ T_COMMENT, T_DOC_COMMENT ], true ) ) {
			$main_code .= is_array( $tok ) ? $tok[1] : $tok;
		}
	}
	$cli_src = (string) file_get_contents( $plugin_root . '/includes/class-newsticker-cli.php' );
	preg_match_all( '/\$instance->(\w+)\(/', $cli_src, $cli_calls );
	$missing_methods = array_values( array_filter( array_unique( $cli_calls[1] ), static fn( $m ) => ! method_exists( 'WM_Newsticker', $m ) ) );
	test_assert( count( $cli_calls[1] ) >= 3 && [] === $missing_methods, 'Every WM_Newsticker method the WP-CLI command calls exists: ' . json_encode( $missing_methods ) );
	test_assert(
		0 === preg_match( '/\$count\s*=\s*\$instance->seed_demo_data\(/', $cli_src ) && false !== strpos( $cli_src, "\$res['posts']" ) && false !== strpos( $cli_src, '[--yes]' ),
		'WP-CLI --import prints the counts, --reset accepts --yes for its confirmation'
	);

	$delete_src = substr( $main_src, (int) strpos( $main_src, 'public static function delete_demo_data' ), 1200 );
	test_assert(
		false === stripos( $delete_src, 'post_title LIKE' ) && false !== strpos( $delete_src, "_is_wm_newsticker_demo" ),
		'Demo cleanup deletes only posts carrying the plugin\'s demo marker, not every "[Demo]" title'
	);

	$seed_src = substr( $main_code, (int) strpos( $main_code, 'public static function seed_demo_data' ), 4000 );
	preg_match_all( "/'title'\s*=>\s*'([^']+)'/", $seed_src, $seed_titles );
	preg_match_all( '/<!-- wp:wm\/newsticker (\{.*?\}) \/-->/', $seed_src, $seed_blocks );
	$seed_attrs  = array_map( static fn( $j ) => (array) json_decode( $j, true ), $seed_blocks[1] );
	$real_names  = preg_match( '/Cyberagentur|DAX|Fachexperten|in Kraft|BFSG/u', $seed_src );
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
	$adapter_src = (string) file_get_contents( $plugin_root . '/includes/class-newsticker-spoke-adapter.php' );
	test_assert(
		[] === $claims && false === strpos( $adapter_src, 'WCAG 2.2 AA' ),
		'Handbook, widget, help tabs and hub description make no focus-pause, 60 FPS or conformity claim: ' . json_encode( $claims )
	);

	echo "\n===============================================================\n";
	echo "RESULT: {$passed} PASSED | {$failed} FAILED\n";
	echo "===============================================================\n\n";

	if ( $failed > 0 ) {
		exit( 1 );
	}
	exit( 0 );
}
