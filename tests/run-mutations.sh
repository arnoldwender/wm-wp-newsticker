#!/usr/bin/env bash
#
# tests/run-mutations.sh — checks that tests/test-suite.php defends each correction listed in tests/mutations.php.
#
# For every entry in tests/mutations.php: copy the repository (tracked and untracked files, no .git)
# to a temp dir, undo one correction with literal replacements, run the standalone suite and require
# it to report at least one failed assertion. A search string that does not occur exactly once is
# reported as "not applied" and counts as a failure, so the list cannot rot silently when the code
# moves; so does a run that never reaches the results line. A control run on the unmutated copy goes
# first.
#
# Usage: bash tests/run-mutations.sh        (from anywhere)
# Exit:  0 when the control is green and every mutation applied and turned the suite red.

set -uo pipefail

ROOT=$(cd "$(dirname "$0")/.." && pwd)
WORK=$(mktemp -d "${TMPDIR:-/tmp}/wm-newsticker-mutations.XXXXXX")
trap 'rm -rf "$WORK"' EXIT

RESULTS_GREEN='^RESULT: [1-9][0-9]* PASSED \| 0 FAILED$'
RESULTS_RED='^RESULT: [0-9]+ PASSED \| [1-9][0-9]* FAILED$'

# True when a line of $2 matches the extended regex $1. A here-string, not `printf | grep -q`: with
# pipefail, grep -q exits at the first match, printf gets SIGPIPE on output larger than the pipe buffer
# and the pipeline fails although the line is there. It classified two red mutations of
# wm-cyber-events-core as "no result" on CI (2026-09-15); measured on a 2 MB string, 20 of 20 pipe checks missed.
has_line() {
  grep -Eq "$1" <<<"$2"
}

# Self-check: a match followed by far more output than a pipe buffer holds must be found.
selfcheck_out="RESULT: 1 PASSED | 1 FAILED"$'\n'"$(head -c 1048576 /dev/zero | tr '\0' 'x')"
if ! has_line "$RESULTS_RED" "$selfcheck_out"; then
  echo "RUNNER SELF-CHECK FAILED  a results line followed by 1 MB of output was not found"
  exit 1
fi
unset selfcheck_out

fresh_copy() {
  rm -rf "$WORK/repo" && mkdir -p "$WORK/repo"
  (cd "$ROOT" && git ls-files -z --cached --others --exclude-standard) | rsync -a --from0 --files-from=- "$ROOT/" "$WORK/repo/" 2>/dev/null
}

count=$(php -r 'echo count(require $argv[1]);' "$ROOT/tests/mutations.php")

fresh_copy
# Exit 0 alone is not green: a file that leaves PHP mode early prints its source and exits 0 without
# running one assertion. The control must also print the results line with 0 failures.
control_out=$(cd "$WORK/repo" && php tests/test-suite.php 2>&1)
control_exit=$?
if [ "$control_exit" -ne 0 ] || ! has_line "$RESULTS_GREEN" "$control_out"; then
  echo "CONTROL FAILED  the unmutated copy is not green (exit $control_exit, no results line with 0 failures); mutations are meaningless"
  printf '%s\n' "$control_out" | grep -E '\[FAIL\]' | head -5
  exit 1
fi
echo "CONTROL      unmutated copy green ($(printf '%s\n' "$control_out" | grep -Eo '[0-9]+ PASSED'))"

failed=0
for ((i = 0; i < count; i++)); do
  fresh_copy
  # Applies entry $i; prints the description, exits 3 when a search string does not occur exactly once.
  desc=$(php -r '
    $m = (require $argv[1])[(int) $argv[2]];
    echo $m[0];
    foreach ($m[1] as [$file, $search, $replace]) {
      $path = $argv[3] . "/" . $file;
      $src  = (string) file_get_contents($path);
      if (substr_count($src, $search) !== 1) {
        fwrite(STDERR, $file . ": search occurs " . substr_count($src, $search) . " times");
        exit(3);
      }
      file_put_contents($path, str_replace($search, $replace, $src));
    }
  ' "$ROOT/tests/mutations.php" "$i" "$WORK/repo" 2>"$WORK/apply.err")
  apply_exit=$?
  if [ "$apply_exit" -ne 0 ]; then
    echo "NOT APPLIED  $desc ($(cat "$WORK/apply.err"))"
    failed=1
    continue
  fi

  # Red means the suite ran to its results line and counted a failure; a file that stopped parsing
  # proves nothing about the assertion that should catch the mutation.
  out=$(cd "$WORK/repo" && php tests/test-suite.php 2>&1)
  if has_line '(PHP )?Parse error' "$out"; then
    echo "NO RESULT    $desc: the mutation made a file unparsable"
    failed=1
  elif has_line "$RESULTS_RED" "$out"; then
    echo "RED          $desc"
  elif has_line "$RESULTS_GREEN" "$out"; then
    echo "SURVIVED     $desc"
    failed=1
  else
    echo "NO RESULT    $desc: the suite did not reach its results line"
    failed=1
  fi
done

exit "$failed"
