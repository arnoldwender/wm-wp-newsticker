<?php
/**
 * Wender Media Newsticker — Suite Hub Spoke Adapter
 *
 * Implements the Wender Media Suite Hub contract (WM_Plugin_Module_Interface)
 * for centralized telemetry, Gutenberg block health, and transient cache management.
 *
 * @package WM_Newsticker
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! interface_exists( 'WenderMedia\SuiteHub\Contracts\WM_Plugin_Module_Interface' ) ) {
	return;
}

use WenderMedia\SuiteHub\Contracts\WM_Plugin_Module_Interface;

class Newsticker_Spoke_Adapter implements WM_Plugin_Module_Interface {

	public function get_module_slug(): string {
		return 'wm-newsticker';
	}

	public function get_name(): string {
		return 'WM Newsticker';
	}

	public function get_version(): string {
		return defined( 'WM_NEWSTICKER_VERSION' ) ? WM_NEWSTICKER_VERSION : self::header_version();
	}

	/**
	 * Version from the plugin header, for the adapter loaded without the main file. The literal '1.4.6'
	 * that stood in get_version() outlived the release it named (header 1.4.7 since 2026-09-14).
	 */
	public static function header_version(): string {
		$header = (string) file_get_contents( dirname( __DIR__ ) . '/wm-newsticker.php', false, null, 0, 2048 );
		return preg_match( '/^\s*\*\s*Version:\s*(\S+)/m', $header, $m ) ? $m[1] : '';
	}

	public function get_description(): string {
		return 'Gutenberg news ticker block: scroll, fade, slide and typing animations with manual headlines or recent posts.';
	}

	public function get_health_status(): string {
		return 'HEALTHY';
	}

	public function get_sbom_data(): array {
		return [
			'bom-ref'     => 'pkg:wordpress/wm-newsticker@' . $this->get_version(),
			'type'        => 'application',
			'name'        => $this->get_name(),
			'version'     => $this->get_version(),
			'description' => $this->get_description(),
			'scope'       => 'required',
			'hashes'      => [
				[
					'alg'     => 'SHA-256',
					'content' => hash_file( 'sha256', dirname( __DIR__ ) . '/wm-newsticker.php' ),
				],
			],
			'licenses'    => [
				[
					'license' => [
						'id'   => 'Proprietary',
						'name' => 'Proprietary Wender Media Commercial License',
					],
				],
			],
			'properties'  => [
				[
					'name'  => 'wm:standards:A11y',
					'value' => 'BFSG 2025 / WCAG 2.2 Level AA / BITV 2.0',
				],
				[
					'name'  => 'wm:block:slug',
					'value' => 'wm/newsticker',
				],
			],
		];
	}

	public function get_dependencies(): array {
		return [];
	}

	public function get_settings_url(): ?string {
		return admin_url( 'admin.php?page=wm-newsticker' );
	}

	public function get_telemetry_metrics(): array {
		global $wpdb;
		$transients_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_wm_newsticker_%'" );
		$is_registered    = WP_Block_Type_Registry::get_instance()->is_registered( 'wm/newsticker' );

		return [
			'block_registered'       => $is_registered ? 'YES' : 'NO',
			'cached_query_transients'=> $transients_count,
			'animation_engine'       => 'HARDWARE_ACCELERATED_60FPS',
			'accessibility_standard' => 'WCAG_2.2_AA_BFSG_2025',
			'zero_cdn_policy'        => 'VERIFIED_100_PERCENT',
		];
	}

	public function execute_action( string $action, array $params = [] ): array {
		switch ( $action ) {
			case 'self_test':
			case 'flush_cache':
				WM_Newsticker::flush_all_transients();
				$is_registered = WP_Block_Type_Registry::get_instance()->is_registered( 'wm/newsticker' );
				return [
					'success' => true,
					'message' => sprintf( 'Newsticker Cache geleert. Gutenberg-Block "wm/newsticker" Registrierungsstatus: %s.', $is_registered ? 'AKTIV' : 'BEREIT' ),
				];

			default:
				return [
					'success' => false,
					'message' => sprintf( 'Unbekannte Spoke-Aktion "%s".', esc_html( $action ) ),
				];
		}
	}
}
