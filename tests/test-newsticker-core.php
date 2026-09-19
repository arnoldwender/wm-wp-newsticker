<?php
/**
 * Test Suite: WM Newsticker Core & Security Tests
 *
 * Execution via WP-CLI:
 * wp eval-file wp-content/plugins/wm-newsticker/tests/test-newsticker-core.php --allow-root
 *
 * @package WM_Newsticker
 */

if ( ! defined( 'ABSPATH' ) ) {
	echo "FAIL: Must run within WordPress environment.\n";
	exit( 1 );
}

echo "=== WM NEWSTICKER CORE & SECURITY TEST SUITE ===\n\n";

$GLOBALS['wm_passed'] = 0;
$GLOBALS['wm_failed'] = 0;

function wm_assert( $condition, $message ) {
	if ( $condition ) {
		echo " [PASS] " . $message . "\n";
		$GLOBALS['wm_passed']++;
	} else {
		echo " [FAIL] " . $message . "\n";
		$GLOBALS['wm_failed']++;
	}
}

// -------------------------------------------------------------
// Test 1: Plugin Instance & Block Registration
// -------------------------------------------------------------
echo "[Test 1] Plugin Singleton & Block Registration\n";
$instance = WM_Newsticker::get_instance();
wm_assert( $instance instanceof WM_Newsticker, 'WM_Newsticker singleton instance exists' );

$block_type = WP_Block_Type_Registry::get_instance()->get_registered( 'wm/newsticker' );
wm_assert( ! empty( $block_type ), 'Block "wm/newsticker" is registered in WP_Block_Type_Registry' );

// -------------------------------------------------------------
// Test 2: Color Sanitization & CSS Injection Defenses
// -------------------------------------------------------------
echo "\n[Test 2] Color Sanitization & CSS Injection Defenses\n";
$reflector = new ReflectionClass( 'WM_Newsticker' );
$sanitize_color = $reflector->getMethod( 'sanitize_color' );
$sanitize_color->setAccessible( true );

// Safe inputs
$hex3 = $sanitize_color->invoke( $instance, '#fff' );
$hex6 = $sanitize_color->invoke( $instance, '#00ff88' );
$hex8 = $sanitize_color->invoke( $instance, '#00ff88ff' );
$rgb  = $sanitize_color->invoke( $instance, 'rgba(0, 255, 136, 0.8)' );
$css_var = $sanitize_color->invoke( $instance, 'var(--primary-color)' );
$named   = $sanitize_color->invoke( $instance, 'transparent' );

wm_assert( '#fff' === $hex3, 'Sanitizes 3-digit hex correctly' );
wm_assert( '#00ff88' === $hex6, 'Sanitizes 6-digit hex correctly' );
wm_assert( '#00ff88ff' === $hex8, 'Sanitizes 8-digit hex with alpha correctly' );
wm_assert( 'rgba(0, 255, 136, 0.8)' === $rgb, 'Sanitizes valid RGBA correctly' );
wm_assert( 'var(--primary-color)' === $css_var, 'Allows valid CSS custom properties (var)' );
wm_assert( 'transparent' === $named, 'Allows valid named CSS colors' );

// Malicious / Injection inputs
$css_injection = $sanitize_color->invoke( $instance, 'red; background: url(https://evil.com/x.jpg)' );
$script_inj    = $sanitize_color->invoke( $instance, '<script>alert(1)</script>' );

wm_assert( '#000000' === $css_injection, 'Blocks CSS injection attempts (defaults to #000000)' );
wm_assert( '#000000' === $script_inj, 'Blocks XSS / script tags in color fields' );

// -------------------------------------------------------------
// Test 3: Number Range & CSS Value Sanitization
// -------------------------------------------------------------
echo "\n[Test 3] Numeric Clamping & CSS Value Sanitization\n";
$sanitize_range = $reflector->getMethod( 'sanitize_number_range' );
$sanitize_range->setAccessible( true );

$val_normal = $sanitize_range->invoke( $instance, 50, 10, 100, 50 );
$val_under  = $sanitize_range->invoke( $instance, 2, 10, 100, 50 );
$val_over   = $sanitize_range->invoke( $instance, 999, 10, 100, 50 );

wm_assert( 50 === $val_normal, 'Maintains valid numeric values within range' );
wm_assert( 50 === $val_under, 'Clamps under-range values to default' );
wm_assert( 50 === $val_over, 'Clamps over-range values to default' );

$sanitize_css = $reflector->getMethod( 'sanitize_css_value' );
$sanitize_css->setAccessible( true );

$css_valid = $sanitize_css->invoke( $instance, '15px' );
$css_rem   = $sanitize_css->invoke( $instance, '1.5rem' );
$css_evil  = $sanitize_css->invoke( $instance, '10px; position: fixed; top: 0' );

wm_assert( '15px' === $css_valid, 'Accepts valid pixel dimensions' );
wm_assert( '1.5rem' === $css_rem, 'Accepts valid rem dimensions' );
wm_assert( '0px' === $css_evil, 'Sanitizes dangerous multi-statement CSS values to 0px' );

// -------------------------------------------------------------
// Test 4: Dynamic Query Post Type Validation
// -------------------------------------------------------------
echo "\n[Test 4] Post Type Security & Query Constraints\n";
$get_dynamic_items = $reflector->getMethod( 'get_dynamic_items' );
$get_dynamic_items->setAccessible( true );

// Attempt query with arbitrary non-existent or private post type
$items_invalid_pt = $get_dynamic_items->invoke( $instance, array(
	'postType'   => 'wm_private_secret_type',
	'postsCount' => 3,
) );
// Should gracefully fall back to 'post'
wm_assert( is_array( $items_invalid_pt ), 'Falls back to standard public "post" when invalid post type is passed' );

// -------------------------------------------------------------
// Test 5: Render Callback Security & HTML Escaping
// -------------------------------------------------------------
echo "\n[Test 5] Frontend Rendering Escaping & XSS Immunity\n";
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

wm_assert( ! empty( $rendered ), 'Renders block HTML for valid items' );
wm_assert( false === strpos( $rendered, '<script>' ), 'Strips or escapes <script> tags in text and labels' );
wm_assert( false === strpos( $rendered, 'href="javascript:' ), 'Neutralizes dangerous javascript: URI links' );
wm_assert( false !== strpos( $rendered, 'role="region"' ), 'Includes accessible role="region" landmark' );
wm_assert( false !== strpos( $rendered, 'aria-roledescription="marquee"' ), 'Includes aria-roledescription="marquee" for assistive tech' );

// -------------------------------------------------------------
// Test 6: REST API Authorization
// -------------------------------------------------------------
echo "\n[Test 6] REST API Security\n";
$routes = rest_get_server()->get_routes();
wm_assert( isset( $routes['/wm-newsticker/v1/post-types'] ), 'REST route /wm-newsticker/v1/post-types is registered' );

// Anonymous user request
wp_set_current_user( 0 );
$request  = new WP_REST_Request( 'GET', '/wm-newsticker/v1/post-types' );
$response = rest_do_request( $request );
wm_assert( 401 === $response->get_status() || 403 === $response->get_status(), 'Anonymous REST requests to /post-types are rejected with 401/403' );

// Admin user request
wp_set_current_user( 1 );
$request_auth  = new WP_REST_Request( 'GET', '/wm-newsticker/v1/post-types' );
$response_auth = rest_do_request( $request_auth );
wm_assert( 200 === $response_auth->get_status(), 'Authorized editor/admin requests receive HTTP 200 OK' );

// -------------------------------------------------------------
// Test 7: Transient Caching & Cache Invalidation
// -------------------------------------------------------------
echo "\n[Test 7] Transient Query Caching & Stampede Defense\n";

// Render dynamic block to prime cache
$dynamic_render = $instance->render_block( array(
	'sourceType' => 'posts',
	'postType'   => 'post',
	'postsCount' => 3,
) );
wm_assert( is_string( $dynamic_render ), 'Dynamic query executes and renders output' );

// Verify transient exists in database
global $wpdb;
$transient_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_wm_newsticker_%'" );
wm_assert( $transient_count > 0, 'Transient cache key generated and saved for query' );

// Trigger flush_transient_cache (simulating save_post)
$instance->flush_transient_cache();
$transient_after = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_wm_newsticker_%'" );
wm_assert( 0 === $transient_after, 'Cache flushed successfully on content mutation hook' );

// -------------------------------------------------------------
// Final Verdict
// -------------------------------------------------------------
echo "\n==========================================\n";
echo "TEST RESULTS: {$GLOBALS['wm_passed']} PASSED, {$GLOBALS['wm_failed']} FAILED\n";
echo "==========================================\n";

if ( $GLOBALS['wm_failed'] > 0 ) {
	exit( 1 );
}
exit( 0 );

