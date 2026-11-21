#!/usr/bin/env node
/**
 * Re-add the direct-access guard to the *.asset.php files emitted by wp-scripts.
 *
 * build/ is committed on purpose (see .gitignore) so the plugin ships without a build step, which
 * means those generated PHP files are part of the distributed plugin and are reachable over HTTP
 * on any host that serves the plugin directory. wp-scripts writes them as a bare
 * `<?php return array(...);` with no guard, and regenerates them on every build — so patching
 * them by hand lasts exactly until the next `npm run build`.
 *
 * Runs as `postbuild`. Idempotent: a file that already carries the guard is left alone.
 */

import { readdir, readFile, writeFile } from 'node:fs/promises';
import { join } from 'node:path';

const BUILD_DIR = 'build';
const GUARD = "defined( 'ABSPATH' ) || exit;";

const files = ( await readdir( BUILD_DIR ) ).filter( ( f ) => f.endsWith( '.asset.php' ) );

if ( files.length === 0 ) {
	console.error( `harden-build-assets: no *.asset.php found in ${ BUILD_DIR }/ — did the build run?` );
	process.exit( 1 );
}

let patched = 0;

for ( const file of files ) {
	const path = join( BUILD_DIR, file );
	const source = await readFile( path, 'utf8' );

	if ( source.includes( 'ABSPATH' ) ) {
		continue;
	}

	if ( ! source.startsWith( '<?php' ) ) {
		console.error( `harden-build-assets: ${ path } does not start with <?php — refusing to touch it` );
		process.exit( 1 );
	}

	await writeFile( path, source.replace( /^<\?php\s*/, `<?php\n${ GUARD }\n` ), 'utf8' );
	patched += 1;
}

console.log( `harden-build-assets: ${ files.length } asset file(s) checked, ${ patched } guarded.` );
