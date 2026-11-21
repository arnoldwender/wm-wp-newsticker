<?php
/**
 * WM Suite Newsticker — Clean Uninstaller
 *
 * Triggered when plugin is deleted via WordPress Admin.
 * Always deletes transient caches; deletes seeded demo content only when the
 * administrator explicitly opted into `wm_newsticker_delete_demo_on_uninstall`.
 *
 * @package WM_Newsticker
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// 1. Delete transient caches.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_wm_newsticker_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_wm_newsticker_' ) . '%'
	)
);

// 2. Optional removal of plugin-seeded demo posts/pages when explicitly enabled in settings.
if ( '1' === (string) get_option( 'wm_newsticker_delete_demo_on_uninstall', '0' ) ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wm_newsticker_demo_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s",
			'_is_wm_newsticker_demo'
		)
	);
	if ( is_array( $wm_newsticker_demo_ids ) ) {
		foreach ( $wm_newsticker_demo_ids as $wm_newsticker_pid ) {
			wp_delete_post( (int) $wm_newsticker_pid, true );
		}
	}
}

// Until 1.6.1 this also deleted `wm_newsticker_settings`, an option no version of the plugin ever wrote.
delete_option( 'wm_newsticker_delete_demo_on_uninstall' );
