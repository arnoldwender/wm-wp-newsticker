<?php
/**
 * Wender Media Newsticker — Clean Uninstaller
 *
 * Triggered when plugin is deleted via WordPress Admin.
 *
 * @package WM_Newsticker
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Delete transient caches
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wm_newsticker_%' OR option_name LIKE '_transient_timeout_wm_newsticker_%'" );
