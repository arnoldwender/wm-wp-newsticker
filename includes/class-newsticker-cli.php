<?php
/**
 * WP-CLI Commands for WM Newsticker
 *
 * @package WM_Newsticker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Manage WM Newsticker news items, cache, and demo data from WP-CLI.
 */
class WM_Newsticker_CLI_Command {

	/**
	 * Flush the newsticker transient cache.
	 *
	 * ## EXAMPLES
	 *
	 *     wp newsticker flush-cache
	 *
	 * @subcommand flush-cache
	 */
	public function flush_cache( $args, $assoc_args ) {
		WM_Newsticker::get_instance()->flush_transient_cache();
		WP_CLI::success( 'WM Newsticker Transient-Cache wurde erfolgreich geleert.' );
	}

	/**
	 * List available ticker post types.
	 *
	 * ## EXAMPLES
	 *
	 *     wp newsticker post-types
	 *
	 * @subcommand post-types
	 */
	public function post_types( $args, $assoc_args ) {
		$post_types = get_post_types( [ 'public' => true ], 'objects' );
		$items      = [];

		foreach ( $post_types as $pt ) {
			$items[] = [
				'Slug'  => $pt->name,
				'Label' => $pt->label,
			];
		}

		WP_CLI\Utils\format_items( 'table', $items, [ 'Slug', 'Label' ] );
	}

	/**
	 * Manage Demo Data and Factory Reset.
	 *
	 * ## OPTIONS
	 *
	 * [--import]
	 * : Import realistic enterprise demo news and create interactive showcase hub.
	 *
	 * [--delete]
	 * : Delete demo news and showcase pages, clear transient cache.
	 *
	 * [--reset]
	 * : Delete the demo content and clear the cache (the plugin stores no settings of its own).
	 *
	 * [--yes]
	 * : Answer yes to the confirmation of --reset.
	 *
	 * ## EXAMPLES
	 *
	 *     wp newsticker demodaten --import
	 *     wp newsticker demodaten --delete
	 *     wp newsticker demodaten --reset
	 *
	 * @subcommand demodaten
	 */
	public function demodaten( $args, $assoc_args ) {
		$instance = WM_Newsticker::get_instance();

		if ( isset( $assoc_args['import'] ) ) {
			WP_CLI::line( 'Importiere WM Newsticker Demodaten...' );
			// seed_demo_data() returns counts; printing the array itself read "Array Demo-Meldungen" until 2026-09-15.
			$res = $instance->seed_demo_data();
			WP_CLI::success( sprintf( '%d Demo-Beiträge und %d Beispielseite angelegt.', (int) $res['posts'], (int) $res['pages'] ) );
		} elseif ( isset( $assoc_args['delete'] ) ) {
			WP_CLI::line( 'Lösche WM Newsticker Demodaten...' );
			$count = $instance->delete_demo_data();
			WP_CLI::success( "{$count} Demo-Beiträge wurden gelöscht und der Cache geleert." );
		} elseif ( isset( $assoc_args['reset'] ) ) {
			WP_CLI::confirm( 'Demo-Inhalte löschen und Cache leeren?', $assoc_args );
			// factory_reset() never existed: --reset ended in a fatal error until 2026-09-15.
			$instance->reset_to_factory_defaults();
			WP_CLI::success( 'Demo-Inhalte gelöscht, Cache geleert.' );
		} else {
			WP_CLI::error( 'Bitte geben Sie eine Option an: --import, --delete oder --reset.' );
		}
	}
}

WP_CLI::add_command( 'newsticker', 'WM_Newsticker_CLI_Command' );
