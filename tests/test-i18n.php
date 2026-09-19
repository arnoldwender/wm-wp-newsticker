<?php
/**
 * Test Suite: WM Newsticker — translation files
 *
 * Run inside WordPress: wp eval-file wp-content/plugins/wm-newsticker/tests/test-i18n.php
 *
 * Checks what the files in languages/ are: the template, one .po and a valid .mo per locale, and that
 * WordPress resolves a string from a loaded .mo. It also prints how many entries each catalog really
 * translates (msgstr not empty and not a copy of msgid), because most of them do not.
 *
 * Until 2026-09-15 this file asserted that "📖 Handbuch" translates to "📖 Manual", "📖 Manuel" and
 * "📖 Manuale". The string left the plugin on 2026-09-14 and the catalogs never carried those
 * translations, so three of its seven assertions failed.
 *
 * @package WM_Newsticker
 */

if ( ! defined( 'ABSPATH' ) ) {
	require_once dirname( __DIR__, 4 ) . '/wp-load.php';
}

echo "=== WM Newsticker: translation files ===\n\n";

$languages_dir = WM_NEWSTICKER_PLUGIN_DIR . 'languages/';

$GLOBALS['test_passed'] = 0;
$GLOBALS['test_total']  = 0;

function assert_test( bool $condition, string $description ): void {
	$GLOBALS['test_total']++;
	if ( $condition ) {
		$GLOBALS['test_passed']++;
		echo " [PASS] $description\n";
	} else {
		echo " [FAIL] $description\n";
	}
}

/**
 * Single-line msgid / msgstr pairs of a .po file without context or plural forms.
 *
 * @return array<string,string>
 */
function wm_newsticker_po_pairs( string $po_file ): array {
	$pairs = [];
	$src   = (string) file_get_contents( $po_file );
	preg_match_all( '/^(msgctxt .*\n)?msgid "(.*)"\nmsgstr "(.*)"$/m', $src, $m, PREG_SET_ORDER );
	foreach ( $m as $entry ) {
		if ( '' === $entry[1] && '' !== $entry[2] ) {
			$pairs[ stripcslashes( $entry[2] ) ] = stripcslashes( $entry[3] );
		}
	}
	return $pairs;
}

// 1. Template.
assert_test( file_exists( $languages_dir . 'wm-newsticker.pot' ), 'POT template exists' );

// 2. One .po and a .mo with a gettext header per locale.
$po_files = glob( $languages_dir . 'wm-newsticker-*.po' ) ?: [];
$missing  = [];
$invalid  = [];
foreach ( $po_files as $po_file ) {
	$mo_file = substr( $po_file, 0, -3 ) . '.mo';
	if ( ! file_exists( $mo_file ) ) {
		$missing[] = basename( $mo_file );
		continue;
	}
	$magic = bin2hex( (string) file_get_contents( $mo_file, false, null, 0, 4 ) );
	if ( '950412de' !== $magic && 'de120495' !== $magic ) {
		$invalid[] = basename( $mo_file ) . " ({$magic})";
	}
}
assert_test( count( $po_files ) >= 25, 'At least 25 .po files (' . count( $po_files ) . ')' );
assert_test( [] === $missing, 'Every .po has its .mo: ' . wp_json_encode( $missing ) );
assert_test( [] === $invalid, 'Every .mo carries a gettext header: ' . wp_json_encode( $invalid ) );

// 3. WordPress resolves a translated entry from a loaded .mo (es_ES, the first entry that is translated).
$es_pairs  = wm_newsticker_po_pairs( $languages_dir . 'wm-newsticker-es_ES.po' );
$es_real   = array_filter( $es_pairs, static fn( $str, $id ) => '' !== $str && $str !== $id, ARRAY_FILTER_USE_BOTH );
$probe_id  = (string) array_key_first( $es_real );
unload_textdomain( 'wm-newsticker' );
$loaded    = load_textdomain( 'wm-newsticker', $languages_dir . 'wm-newsticker-es_ES.mo' );
// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- the probe string comes from the catalog.
$resolved  = '' === $probe_id ? '' : __( $probe_id, 'wm-newsticker' );
unload_textdomain( 'wm-newsticker' );
assert_test(
	$loaded && '' !== $probe_id && $resolved === $es_real[ $probe_id ],
	"es_ES .mo loads and resolves '{$probe_id}' to '{$resolved}'"
);

// 4. Report: entries each catalog really translates.
echo "\nTranslated entries (msgstr not empty and not a copy of msgid):\n";
foreach ( $po_files as $po_file ) {
	$pairs = wm_newsticker_po_pairs( $po_file );
	$real  = count( array_filter( $pairs, static fn( $str, $id ) => '' !== $str && $str !== $id, ARRAY_FILTER_USE_BOTH ) );
	printf( "  %-28s %3d of %3d\n", basename( $po_file, '.po' ), $real, count( $pairs ) );
}

echo "\n--- Summary ---\n";
echo "Tests Passed: {$GLOBALS['test_passed']} / {$GLOBALS['test_total']}\n";

exit( $GLOBALS['test_passed'] === $GLOBALS['test_total'] && $GLOBALS['test_total'] > 0 ? 0 : 1 );
