# Security — WM Newsticker

How to report a vulnerability is in the repository's [SECURITY.md](../SECURITY.md): GitHub's private vulnerability reporting, no public issues.

The previous edition of this file carried a second, different reporting policy (e-mail with a response timetable) and listed hosting measures such as HTTPS and security headers, which a plugin does not set.

What the code does against injection and unauthorised access, measured on 2026-09-15: [SECURITY-AUDIT.md](SECURITY-AUDIT.md). The dated read-only review of 2026-08-25: BACKEND-SECURITY-AUDIT-2026-08-25.md.

Dependencies: `@wordpress/scripts` is a development dependency for the build; the plugin has no runtime npm or Composer dependencies. Dependabot watches `package.json`.
