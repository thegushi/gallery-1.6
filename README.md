# Gallery 1.6-RC3 — PHP 8.x Compatibility Port

> **Mostly for the Lulz.**

This branch (`php8-compat`) takes [Gallery 1.6-RC3](http://gallery.sourceforge.net) — a flat-file PHP photo gallery last touched in November 2008 — and makes it parse cleanly under PHP 8.5 with zero fatal errors and zero deprecation warnings.

The work was done with [Claude Code](https://claude.ai/code) (Anthropic's CLI coding assistant) in a single session. The original code is untouched on the `clean-import` branch for reference.

---

## What was broken and what was fixed

Gallery 1.6 was written for PHP 4/5 circa 2001–2008. Sixteen-plus years of PHP evolution had left it thoroughly unrunnable.

| Issue | Count | Fix |
|---|---|---|
| `ereg()`/`eregi()`/`ereg_replace()`/`eregi_replace()` — removed in PHP 7.0 | ~152 | Converted to `preg_match()`/`preg_replace()` with correct delimiters and `i` flags |
| `split()` — removed in PHP 7.0 | ~10 | `preg_split()` |
| PHP 4-style constructors (`function ClassName()`) — removed in PHP 8.0 | 28 classes | Renamed to `__construct()` across all core, CMS, Mail, and XML classes |
| `=& new` reference-on-construction — removed in PHP 8.0 | 24 | Stripped the `&` |
| Curly brace array/string access `$arr{"key"}`, `$str{0}` — removed in PHP 8.0 | ~50 | Changed to `$arr["key"]`, `$str[0]` |
| `get_magic_quotes_gpc()` / `set_magic_quotes_runtime()` — removed in PHP 7.4 | 14 | Removed the calls; the false-branch behavior is now always taken |
| `create_function()` — removed in PHP 8.0 | 1 | Replaced with a proper closure |
| `each()` — removed in PHP 8.0 | 1 | `foreach` |
| `mt_srand((double) microtime() * 1000000)` — deprecated PHP 7.1 | 4 | `mt_srand()` (PHP auto-seeds since 7.1) |
| `(double)` / `(boolean)` non-canonical casts — deprecated PHP 8.x | ~25 | `(float)` / `(bool)` |
| `${var}` string interpolation — deprecated PHP 8.2 | ~40 | `{$var}` |
| Pre-existing syntax bugs (`showInvalidReqMesg(echo ...)`, merged function names) | 4 | Fixed |
| Optional parameters declared before required ones | 5 functions | Gave the required params default values |

**87 files changed.** `php -l` across the entire tree: zero parse errors, zero deprecations.

---

## What is NOT fixed

- **`mysql_*` functions** — the MySQL database driver (`classes/database/mysql/`) still uses the long-removed `mysql_connect()` etc. This code path only runs when Gallery is embedded inside old PHP-Nuke/PostNuke/Joomla/Mambo CMS installations. For standalone flat-file use (the point of this exercise) it is never loaded.
- **Actual runtime correctness** — syntax-clean is not the same as works. The setup wizard, image manipulation, user auth, and templating system have not been tested end-to-end. There are almost certainly runtime issues beyond what `php -l` catches.
- **Security** — this is 2008 PHP code. It has CSRF, XSS, and path traversal issues that were considered acceptable at the time and are not acceptable now. Do not run this on a public server.

---

## Why

Gallery was a big deal in the early 2000s. Before Flickr, before Google Photos, before anyone had a smartphone camera in their pocket, self-hosted photo galleries were how families and hobbyists shared photos on the web. Gallery 1.x was the king of that era: flat files, no database required, runs on any shared hosting.

Gallery 2 and 3 followed and are long dead. The original project is archived. Most of the code that ran on millions of family websites in 2004 would now just crash on any modern server.

This is a small act of digital preservation — or at minimum, a proof that the job is doable in an afternoon with a good LLM.

---

## How it was done

1. `brew install php` to get PHP 8.5 as a linter
2. `php -l` across the whole tree to baseline the damage
3. Claude Code worked through the issues category by category, reading files and making edits, running `php -l` after each batch to verify
4. One commit on `php8-compat`, `clean-import` branch left untouched as the original

Total wall-clock time: one session. The original README is preserved below.

---

## Original README

See the `clean-import` branch for the verbatim Sourceforge SVN export, including the original `README` file.
