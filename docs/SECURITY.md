# Security notes

## PHP2Shell (CVE-2026-109618, CVE-2026-109619, CVE-2026-115560), October 2026

Critical remote code execution bugs in PHP itself, not in Libraro's code. They are hit while PHP
parses the request (POST body, session upload progress), before any app code, login or
middleware runs, so no change in the app can block them. They are exploited in the wild (CISA KEV).

**Fix: run PHP 8.2.35 / 8.3.36 / 8.4.27 / 8.5.12 or newer** on every server, then restart Apache /
PHP-FPM so no old worker keeps running. PHP 8.1 and older get no official release, and PHP 8.2
reaches end of life on 31 Dec 2026, so move to 8.3 or 8.4. `tests/Unit/PhpSecurityVersionTest.php`
fails while the PHP in use is below the fixed version.

Interim mitigation in `.htaccess` (Apache + mod_php only, applied before PHP parses the request):

| Setting | Value | Why |
|---|---|---|
| `session.upload_progress.enabled` | off | CVE-2026-109619 / 115560 path. The app does not use it. |
| `memory_limit` | -1 | CVE-2026-109618 needs an out-of-memory abort while `$_POST` is built. |
| `post_max_size` / `upload_max_filesize` | 64M / 55M | Largest upload is a 50 MB video (`SiteController::videoStore`). |
| `LimitRequestBody` | 67108864 | Apache refuses bodies over 64 MB before PHP sees them, chunked ones too. |

On **PHP-FPM** (nginx or Apache + FPM) `.htaccess` `php_value` lines do nothing, and `.user.ini`
or `ini_set()` are too late. Set the same values in the FPM pool (`php_admin_flag[session.upload_progress.enabled] = off`,
`php_admin_value[memory_limit] = -1`, `php_admin_value[post_max_size] = 64M`, `php_admin_value[upload_max_filesize] = 55M`)
and cap the body at the web server (nginx `client_max_body_size 64m;`), then restart FPM.

App rules that keep CVE-2026-115560 out of reach: never change `session.serialize_handler` at
runtime and never call `ini_set()` for session settings.

Signs of an attack in the error log: `Allowed memory size ... exhausted ... in Unknown on line 0`,
Apache `AH00052: child pid ... exit signal Segmentation fault (11)`, and the web server user starting
a shell. Exploitation attempts started on 17 Oct 2026, so check logs from that date.
