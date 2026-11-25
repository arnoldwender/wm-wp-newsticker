<?php
/**
 * Gate: does languages/*.pot still describe the strings this code translates?
 *
 * A string the code wraps in __() but that has no entry in the .pot cannot be translated by anyone,
 * to any language, ever — the translator never sees it, and nothing reports an error. The interface
 * simply stays German. Measured here on 2026-09-20: 30 of the 158 translatable strings had no
 * entry. tests/test-i18n.php already checked that a .po and a valid .mo exist per locale and that
 * WordPress resolves a string from one — all true of a catalog that has drifted away from the code.
 *
 * WHAT DEFINES "FRESH" IS NOT A REGEX IN THIS FILE. It is `wp i18n make-pot`, the extractor
 * WordPress itself ships, which understands concatenation, escapes, contexts and plurals. A
 * hand-rolled extractor would disagree with the real one on exactly the strings that are hard to
 * extract — which is where drift hides. This file only compares the msgid SETS.
 *
 * Byte comparison would be wrong for the reason it is tempting: make-pot stamps POT-Creation-Date
 * on every run, so a byte diff always reports "stale" and the check is ignored within a week.
 *
 * NO ESCAPE HATCH ON PURPOSE. It requires wp-cli and fails without it, saying how to get it. A gate
 * that skips itself when a tool is missing is a gate that is never green and never red — it just
 * prints a warning nobody reads. It lives OUTSIDE tests/test-suite.php so that suite keeps its own
 * virtue of needing no WordPress and no MySQL.
 *
 * Usage:
 *     php tests/test-pot-freshness.php
 *     php tests/test-pot-freshness.php --selftest
 *
 * Fix what it reports:
 *     python3 the fleet tooling wm-newsticker --write
 *
 * @package WM_Newsticker
 */

declare(strict_types=1);

const POT_EXCLUDE = 'tests,vendor,node_modules,dist,build,.git';

/**
 * The text domain, read from the main plugin file — never a constant in this file.
 *
 * A second copy of the domain is a second thing that can be wrong, and it would go wrong silently:
 * make-pot would extract nothing for a domain no code uses, the comparison would find 0 strings in
 * the code, and the check would report a perfectly fresh catalog. Same reason the plugin's own
 * version lives in one place.
 */
function plugin_text_domain( string $repo ): ?string {
	foreach ( glob( $repo . '/*.php' ) ?: [] as $php ) {
		$head = (string) file_get_contents( $php, false, null, 0, 4096 );
		if ( ! str_contains( $head, 'Plugin Name:' ) ) {
			continue;
		}
		if ( preg_match( '/^\s*\*?\s*Text Domain:\s*(\S+)/m', $head, $m ) ) {
			return $m[1];
		}
	}
	return null;
}

/**
 * Every msgid in a .pot, mapped to "this entry came from the plugin header".
 *
 * Entries are separated by a blank line and the `#.` comments belong to the entry below them, so
 * the parse is per block. A msgid wrapped over several lines is joined: reading only the first line
 * would report every long string as missing, which is a green check on a broken one.
 *
 * make-pot labels the plugin header with "#. <Field> of the plugin". Nobody translates an author
 * name or a URL, and counting those as defects is how a check earns a reputation for crying wolf.
 *
 * @return array<string, bool>
 */
function pot_entries( string $text ): array {
	$out = [];
	foreach ( preg_split( '/\R\s*\R/', $text ) as $block ) {
		if ( ! preg_match( '/^msgid((?:\s+"(?:[^"\\\\]|\\\\.)*")+)/m', $block, $m ) ) {
			continue;
		}
		preg_match_all( '/"((?:[^"\\\\]|\\\\.)*)"/', $m[1], $parts );
		$msgid = implode( '', $parts[1] );
		if ( '' === $msgid ) {
			continue;
		}
		$out[ $msgid ] = (bool) preg_match( '/^#\.\s+.*\bof the (plugin|theme)\b/m', $block );
	}
	return $out;
}

/** Run wp i18n make-pot without a shell; returns the generated text or null. */
function make_pot( string $repo, string $domain, string $dest ): ?string {
	$process = proc_open(
		[ 'wp', 'i18n', 'make-pot', $repo, $dest, '--domain=' . $domain, '--exclude=' . POT_EXCLUDE, '--skip-audit', '--allow-root' ],
		[ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ],
		$pipes
	);
	if ( ! is_resource( $process ) ) {
		return null;
	}
	stream_get_contents( $pipes[1] );
	$stderr = (string) stream_get_contents( $pipes[2] );
	fclose( $pipes[1] );
	fclose( $pipes[2] );
	$code = proc_close( $process );

	if ( 0 !== $code || ! is_file( $dest ) ) {
		fwrite( STDERR, "  wp i18n make-pot failed (exit {$code}): " . substr( trim( $stderr ), 0, 300 ) . "\n" );
		return null;
	}
	return (string) file_get_contents( $dest );
}

/**
 * Compare the catalog of one plugin directory against a freshly extracted one.
 *
 * @return array{missing: list<string>, stale: list<string>, in_code: int, in_pot: int}|null
 */
function measure_pot( string $repo, string $domain ): ?array {
	$tmp = tempnam( sys_get_temp_dir(), 'potfresh' );
	$fresh_text = make_pot( $repo, $domain, $tmp );
	$fresh_all = null === $fresh_text ? null : pot_entries( $fresh_text );
	@unlink( $tmp );
	if ( null === $fresh_all ) {
		return null;
	}

	$pot_path = $repo . '/languages/' . $domain . '.pot';
	$current = is_file( $pot_path ) ? array_keys( pot_entries( (string) file_get_contents( $pot_path ) ) ) : [];

	$from_code = array_keys( array_filter( $fresh_all, static fn( bool $is_header ): bool => ! $is_header ) );

	return [
		'missing' => array_values( array_diff( $from_code, $current ) ),
		'stale'   => array_values( array_diff( $current, array_keys( $fresh_all ) ) ),
		'in_code' => count( $from_code ),
		'in_pot'  => count( $current ),
	];
}

$passed = 0;
$failed = 0;
function check( string $name, bool $ok, string $detail = '' ): void {
	global $passed, $failed;
	if ( $ok ) {
		echo "  [PASS] {$name}\n";
		$passed++;
		return;
	}
	echo "  [FAIL] {$name}: {$detail}\n";
	$failed++;
}

/** A throwaway plugin proves the check can go red, and that the header is not counted. */
function selftest(): int {
	$dir = sys_get_temp_dir() . '/wm-pot-selftest-' . bin2hex( random_bytes( 4 ) );
	mkdir( $dir . '/languages', 0o777, true );
	file_put_contents(
		$dir . '/wm-pot-selftest.php',
		"<?php\n/**\n * Plugin Name: Pot Selftest\n * Text Domain: wm-pot-selftest\n */\n"
		. "__( 'string that has an entry', 'wm-pot-selftest' );\n"
		. "__( 'string with no entry at all', 'wm-pot-selftest' );\n"
		. "__( 'string of another plugin', 'somebody-else' );\n"
	);
	file_put_contents(
		$dir . '/languages/wm-pot-selftest.pot',
		"msgid \"\"\nmsgstr \"\"\n\nmsgid \"string that has an entry\"\nmsgstr \"\"\n"
	);

	$res = measure_pot( $dir, 'wm-pot-selftest' );
	if ( null === $res ) {
		echo "  [FAIL] the self-test could not run make-pot\n";
		return 1;
	}

	check( 'a string with no entry is reported', in_array( 'string with no entry at all', $res['missing'], true ), implode( ' | ', $res['missing'] ) );
	check( 'a string that has an entry is not reported', ! in_array( 'string that has an entry', $res['missing'], true ), implode( ' | ', $res['missing'] ) );
	check( "another plugin's text domain is ignored", ! in_array( 'string of another plugin', $res['missing'], true ), implode( ' | ', $res['missing'] ) );
	check( 'the plugin header is not counted as a missing translation', ! in_array( 'Pot Selftest', $res['missing'], true ), implode( ' | ', $res['missing'] ) );

	array_map( 'unlink', (array) glob( $dir . '/languages/*' ) );
	array_map( 'unlink', (array) glob( $dir . '/*.php' ) );
	rmdir( $dir . '/languages' );
	rmdir( $dir );
	return 0;
}

// ------------------------------------------------------------

$repo = dirname( __DIR__ );

echo "==============================================================================\n";
echo "  POT FRESHNESS — does languages/*.pot describe the strings the code translates?\n";
echo "==============================================================================\n\n";

$which = proc_open( [ 'which', 'wp' ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes );
$has_wp = false;
if ( is_resource( $which ) ) {
	$has_wp = '' !== trim( (string) stream_get_contents( $pipes[1] ) );
	fclose( $pipes[1] );
	fclose( $pipes[2] );
	proc_close( $which );
}

if ( ! $has_wp ) {
	// Deliberately fatal. `wp i18n make-pot` IS the definition of fresh here; without it this file
	// has no opinion, and a gate that shrugs when its tool is missing protects nothing.
	fwrite( STDERR, "wp-cli is required: this check compares against `wp i18n make-pot`.\n" );
	fwrite( STDERR, "  macOS:  brew install wp-cli\n" );
	fwrite( STDERR, "  CI:     curl -sL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /usr/local/bin/wp && chmod +x /usr/local/bin/wp\n" );
	exit( 1 );
}

if ( in_array( '--selftest', $argv, true ) ) {
	selftest();
	echo "\n  SELF-TEST: {$passed} passed, {$failed} failed\n";
	exit( $failed > 0 ? 1 : 0 );
}

// The self-test runs first on every invocation: a check whose own machinery is broken reports a
// clean plugin exactly the way a working one reports a clean plugin.
echo "Self-test (the check must be able to go red):\n";
selftest();
echo "\n";

$domain = plugin_text_domain( $repo );
if ( null === $domain ) {
	fwrite( STDERR, "No main plugin file with a Plugin Name and a Text Domain header in {$repo}.\n" );
	exit( 1 );
}

$result = measure_pot( $repo, $domain );
if ( null === $result ) {
	fwrite( STDERR, "make-pot did not produce a catalog; see the error above.\n" );
	exit( 1 );
}

// An empty extraction is not a clean bill of health: it is the shape a wrong domain takes.
check( 'the code has translatable strings to check at all', $result['in_code'] > 0, "0 strings extracted for domain {$domain}" );

echo "Domain {$domain}: " . $result['in_code'] . " translatable string(s) in the code, " . $result['in_pot'] . " entry/entries in the .pot\n\n";

check(
	'every string the code translates has an entry in the .pot',
	[] === $result['missing'],
	count( $result['missing'] ) . ' missing, e.g. ' . implode( ' | ', array_map( static fn( string $s ): string => substr( $s, 0, 60 ), array_slice( $result['missing'], 0, 3 ) ) )
);

if ( [] !== $result['stale'] ) {
	// Not a failure: an entry whose string is gone is noise, and msgmerge keeps it as #~ where a
	// translator can still reuse it. Said out loud so it does not accumulate unseen.
	echo "  [NOTE] " . count( $result['stale'] ) . " entry/entries in the .pot no longer exist in the code\n";
}

echo "\n==============================================================================\n";
echo "  POT FRESHNESS: {$passed} passed, {$failed} failed\n";
echo "==============================================================================\n\n";

if ( $failed > 0 ) {
	fwrite( STDERR, "fix: python3 the fleet tooling " . basename( $repo ) . " --write\n" );
}

exit( $failed > 0 ? 1 : 0 );
