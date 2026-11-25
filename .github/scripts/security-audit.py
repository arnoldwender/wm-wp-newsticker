#!/usr/bin/env python3
"""
security-audit.py — static checks of a WM Suite WordPress plugin repository, run in CI from the repository root.

Usage: python3 .github/scripts/security-audit.py   (from the repository root)
Exit:  0 clean · 1 findings · 2 the built-in controls failed
"""
import os
import re
import sys

SECRET_PATTERNS = [
    (r"AKIA[0-9A-Z]{16}", "AWS Access Key"),
    (r"sk-[a-zA-Z0-9]{48}", "OpenAI API Key"),
    (r"ghp_[a-zA-Z0-9]{36}", "GitHub Personal Access Token"),
    (r"-----BEGIN (?:RSA |EC )?PRIVATE KEY-----", "Private Key"),
    (r"Bearer\s+[a-zA-Z0-9_\-\.]{35,}", "Bearer Token"),
]

DANGEROUS_CALL = re.compile(
    r"(?<![\w$>:])(?<!function )(?<!new )(eval|passthru|system|shell_exec|exec|proc_open|popen)\s*\(",
    re.IGNORECASE,
)

DANGEROUS_CALL_POSITIVES = [
    "system\x28'id');", "\\system\x28$cmd);", "exec \x28$cmd);", "$out = shell_exec\x28$c);", "eval\x28$code);",
    "proc_open\x28$c, $d, $p);", "popen\x28$c, 'r');", "passthru\x28$c);", "SYSTEM\x28$c);",
]
DANGEROUS_CALL_NEGATIVES = [
    "if ( WP_Filesystem\x28) ) {", "curl_exec\x28$ch);", "$pdo->exec\x28$sql);", "Foo::system\x28$x);", "$system\x28$x);",
    "wp_filesystem_eval_x\x28);", "public function exec\x28 array $args ) {}", "$runner = new Exec\x28 $cmd );",
]

LEXER_POSITIVES = [
    "<?php if ( preg_match\x28 '/x/', $a ) ) { system\x28 $cmd ); }",
    "<?php if ( str_contains\x28 $a, 'x' ) ) { shell_exec\x28 $c ); }",
    "<?php $out = `ls -la /`;",
    "<?php $x = \"a\"; passthru\x28 $c ); // trailing comment",
    "<html><body><?php eval\x28 $code ); ?></body></html>",
]
LEXER_NEGATIVES = [
    "<?php preg_match\x28 '/system\\x28|exec\\x28/', $line );",
    "<?php $sql = \"SELECT `id` FROM `{$table}` WHERE a = 'system\x281)'\";",
    "<?php // run `wp cli` or system\x28 $x ) here\n$a = 1;",
    "<?php /* eval\x28 $x ) and `ls` */ $a = 1;",
    "<?php # exec\x28 $x )\n$a = 1;",
    "<?php $q = <<<SQL\nSELECT `id` FROM t WHERE system\x281)\nSQL;\n$b = 2;",
    "<?php $q = <<<'TXT'\n`backticks` and exec\x28 $x ) in a nowdoc\nTXT;\n",
    "<?php $s = 'it\\'s `quoted` and exec\x28 $x )';",
    "<html><script>const s = `template ${x}`;</script></html><?php $a = 1; ?>",
    "<?php #[Attribute] class Foo {}",
]

HEREDOC_START = re.compile(r"<<<[ \t]*(['\"]?)([A-Za-z_][A-Za-z0-9_]*)\1\r?\n")


def _blank(text):
    return re.sub(r"[^\n]", " ", text)


def _string_end(src, start, quote):
    i = start + 1
    n = len(src)
    while i < n:
        ch = src[i]
        if ch == "\\":
            i += 2
            continue
        if ch == quote:
            return i + 1
        i += 1
    return n


def php_code_only(src):
    out = []
    i = 0
    n = len(src)
    in_php = False
    while i < n:
        if not in_php:
            starts = [p for p in (src.find("<?php", i), src.find("<?=", i)) if p != -1]
            if not starts:
                out.append(_blank(src[i:]))
                break
            s = min(starts)
            out.append(_blank(src[i:s]))
            tag = "<?php" if src.startswith("<?php", s) else "<?="
            out.append(tag)
            i = s + len(tag)
            in_php = True
            continue
        if src.startswith("?>", i):
            out.append("?>")
            i += 2
            in_php = False
            continue
        ch = src[i]
        if src.startswith("#[", i):
            out.append("#[")
            i += 2
            continue
        if ch == "#" or src.startswith("//", i):
            end = src.find("\n", i)
            end = n if end == -1 else end
            close = src.find("?>", i)
            if close != -1 and close < end:
                end = close
            out.append(_blank(src[i:end]))
            i = end
            continue
        if src.startswith("/*", i):
            end = src.find("*/", i + 2)
            end = n if end == -1 else end + 2
            out.append(_blank(src[i:end]))
            i = end
            continue
        if ch in ("'", '"'):
            end = _string_end(src, i, ch)
            out.append(_blank(src[i:end]))
            i = end
            continue
        if src.startswith("<<<", i):
            m = HEREDOC_START.match(src, i)
            if m:
                closing = re.compile(r"^[ \t]*" + re.escape(m.group(2)) + r"\b", re.M).search(src, m.end())
                end = n if closing is None else closing.end()
                out.append(_blank(src[i:end]))
                i = end
                continue
        out.append(ch)
        i += 1
    return "".join(out)


def shell_findings(content):
    code_lines = php_code_only(content).split("\n")
    source_lines = content.split("\n")
    found = []
    for number, line in enumerate(code_lines, 1):
        shown = source_lines[number - 1].strip() if number <= len(source_lines) else ""
        call = DANGEROUS_CALL.search(line)
        if call:
            found.append(f"CRITICAL: Dangerous shell/eval function {call.group(1)} on line {number}: {shown}")
        if "`" in line:
            found.append(f"CRITICAL: Backtick shell execution on line {number}: {shown}")
    return found


def in_top_level_tests(path):
    parts = os.path.normpath(os.path.relpath(path)).split(os.sep)
    return len(parts) > 1 and parts[0] == "tests"


def controls_hold():
    return (
        all(DANGEROUS_CALL.search(s) for s in DANGEROUS_CALL_POSITIVES)
        and not any(DANGEROUS_CALL.search(s) for s in DANGEROUS_CALL_NEGATIVES)
        and all(shell_findings(s) for s in LEXER_POSITIVES)
        and not any(shell_findings(s) for s in LEXER_NEGATIVES)
        and in_top_level_tests(os.path.join("tests", "test-suite.php"))
        and not in_top_level_tests(os.path.join("includes", "latests", "x.php"))
        and not in_top_level_tests("tests.php")
    )


def scan_file(filepath):
    findings = []
    try:
        with open(filepath, "r", encoding="utf-8", errors="ignore") as f:
            content = f.read()
    except Exception:
        return []

    tests = in_top_level_tests(filepath)
    php = filepath.endswith(".php")
    name = os.path.basename(filepath)

    if not tests:
        for pattern, label in SECRET_PATTERNS:
            if re.search(pattern, content):
                findings.append(f"CRITICAL: Found potential {label}")

    if php and not tests:
        m = re.search(
            r"wp_enqueue_(?:script|style)\s*\([^,]+,\s*['\"](https?:)?//"
            r"(?!localhost|127\.0\.0\.1|\{\{)([^'\"/]+)",
            content, re.IGNORECASE)
        if m:
            findings.append(f"CRITICAL: wp_enqueue to external host '{m.group(2)}' (Zero-CDN violation)")

    if php and not tests and name != "uninstall.php":
        for line in content.splitlines():
            if "$wpdb->query(" in line and "$" in line.split("$wpdb->query(")[1]:
                if not re.search(r"\b(CREATE\s+TABLE|DROP\s+TABLE|ALTER\s+TABLE|TRUNCATE\s+TABLE)\b", line, re.IGNORECASE):
                    if "$wpdb->prepare" not in line and "SET is_active = 0" not in line:
                        findings.append(f"HIGH: Unprepared dynamic $wpdb->query: {line.strip()}")

    if php and not tests:
        if re.search(r"\$signature_hash\s*==\s*\$", content) or re.search(r"\$hash\s*===?\s*\$prev_hash", content):
            if "hash_equals" not in content:
                findings.append("HIGH: Insecure hash comparison detected without hash_equals()")

    if php and not tests and name not in ("RequestShield.php", "UploadShield.php"):
        findings.extend(shell_findings(content))

    if filepath.endswith("robots.txt"):
        if "crawl-delay" in content.lower() and "googlebot" in content.lower():
            findings.append("ANTI-FEATURE: Crawl-delay configured on Googlebot in robots.txt")

    return findings


def main():
    if not controls_hold():
        print("FAIL: security-audit.py self-check failed: the patterns, the PHP lexer or the tests/ rule no longer separate their controls.")
        sys.exit(2)

    repo_path = os.getcwd()
    repo_findings = []

    for root, dirs, files in os.walk(repo_path):
        for skipped in (".git", "node_modules", "vendor"):
            if skipped in dirs:
                dirs.remove(skipped)
        for file in files:
            fpath = os.path.join(root, file)
            for finding in scan_file(fpath):
                repo_findings.append(f"{os.path.relpath(fpath, repo_path)}: {finding}")

    if repo_findings:
        print("FAIL: Security audit found vulnerabilities:")
        for f in repo_findings:
            print(f"    - {f}")
        sys.exit(1)
    print("PASS: Security audit clean.")
    sys.exit(0)


if __name__ == "__main__":
    main()
