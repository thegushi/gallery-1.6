# Gallery 1.6-RC3 — PHP 8.x Compatibility Port

> **Mostly for the Lulz.**

This branch (`php8-compat`) takes [Gallery 1.6-RC3](http://gallery.sourceforge.net) — a flat-file PHP photo gallery last touched in November 2008 — and makes it run on PHP 8.x: no fatal errors, no deprecation warnings, working admin and upload.

The work was done with [Claude Code](https://claude.ai/code) (Anthropic's CLI coding assistant) across two sessions: a first pass to fix parse-level errors (`php -l` clean), then a second pass of runtime fixes found by deploying against a real gallery with real data. The original code is untouched on the `clean-import` branch for reference.

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

## Runtime fixes (found during actual deployment)

`php -l` catches syntax errors but not runtime behavior. Deploying against a real gallery with real data surfaced a second wave of issues, fixed in subsequent commits.

| Issue | Fix |
|---|---|
| `$gallery` is a bare `stdClass` before `Version.php` assigns properties to it — PHP 8 fatal on null object | Initialize `$gallery = new stdClass()` before the `require` in `init.php` |
| `$op` / `$mop` (CMS embed vars) undefined in standalone use — PHP 8 warns, `strcmp()` gets null | Use `?? ''` null-coalescing in `index.php` |
| `parent::ClassName()` — old PHP 4 explicit parent constructor call style | `parent::__construct()` in `XML_HTMLSax3_StateParser` subclasses and `Gallery_User` |
| `GallerySession`, `Album`, `AlbumDB` dynamic property deprecations (PHP 8.2) | Declare all runtime-assigned properties explicitly on each class |
| `Album::$transient` wiped by `loadFromFile()` copying old `.dat` file properties | Reinitialize `$this->transient = new stdClass()` after the property-copy loop |
| `get_class(false)` — PHP 8 fatal when `unserialize()` fails and returns `false` | Guard with `is_object()` before `get_class()` in `loadFromFile()` / `loadPhotosFromFile()` |
| `sizeof($this->comments)` — PHP 8 fatal when `comments` is null in old album data | `sizeof($this->comments ?? [])` |
| `urlencode(null)` — PHP 8.1 deprecation when image `name`/`resizedName` is null | `?? ''` guard in `Image.php` |
| `strftime()` — deprecated PHP 8.1, used in ~30 places across the codebase | Added `gallery_strftime()` shim in `util.php` that translates strftime format specifiers to `date()` equivalents; replaced all call sites |
| `${var}` interpolation missed in `layout/navigator.inc` and `layout/inline_imagewrap.inc` | `{$var}` |
| `HTML_Safe::parse()` accumulates `_xhtml` and `_stack` across calls — `sanitizeInput()` reuses a static instance, and `formVar()` calls `getRequestVar()` twice, so `set_albumListPage=1` became `'111...'`, always > `$maxPages`, always clamped to last page | Reset `_xhtml` and `_stack` at the top of `parse()` |
| Album list page navigation broken for unauthenticated visitors — static `cache.html` was served regardless of `set_albumListPage` query param | Bypass cache when a page-navigation parameter is present |
| `$gallery->language` read before `initLanguage()` runs | Seed `$gallery->language = 'en_US'` on the initial `stdClass` |

## Further runtime fixes (found during admin use and uploads)

A third wave surfaced when exercising admin features: album viewing, photo upload, and the slideshow.

| Issue | Fix |
|---|---|
| `nl2br(null)` — PHP 8.1 deprecation when photos/albums have no description or caption | `?? ''` guards on all `getDescription()`/`getCaption()` call sites in `view_album.php`, `lib/content.php`, and both photo templates |
| `$album->fields['lightbox']` missing from old album `.dat` files | `?? ''` guard in `view_album.php` at all three access sites |
| `stristr(null, ...)` — admin dropdown null when user is not an admin | `?? ''` guard in `lib/content.php` icon menu builder |
| `$adminCommandsDropdown` undefined for non-admin users | Initialize to `null` before the `if (!empty($adminCommands))` block in `album.tpl.default` |
| `DivisionByZeroError` in `galleryTable::render()` — `columnCount > 0` guard placed after the modulo | Move the zero-check first so `&&` short-circuits before `$i % 0` |
| `galleryLink()` called with `''` instead of `array()` for `$attrList` — PHP 8 fatal on string offset | Coerce `$attrList` to array at the top of the function |
| `Browser::singleton()` and `Browser::allowFileUploads()` called statically but not declared static — PHP 8 fatal | Add `static` keyword to both methods in `classes/horde/Browser.php` |
| `floor(getImVersion())` — `getImVersion()` returns a version string like `'7.1.2-16'`; PHP 8 rejects strings in `floor()` | Replace with `(int)getImVersion()` at all three call sites |
| `strlen(null)` — watermark name is null when no watermark is configured | `?? ''` guard in `Album.php` |
| `strpos(null, '_')` in `getAndSetAccessKey()` — null element from icon array | `?? ''` guard in `lib/content.php` |
| Upload "Upload Now" button did nothing — `parent.opener.showProgress()` threw a JS error (null opener in modern browsers), blocking the form submit | Use optional chaining `parent.opener?.showProgress?.()` so the submit always proceeds |
| Thumbnails created by ImageMagick via `exec()` got `600` permissions — unreadable by the web server as static files | `chmod(0644)` the output file after successful ImageMagick/Netpbm conversion |
| Java applet slideshow mode and upload tabs offered in UI — applets dead in all modern browsers since ~2017 | Remove applet mode from `slideshow.php` and `popups/add_photos.php` |
| Shutterfly print service link — API dead since ~2010 | Remove from `view_photo.php` print services list |
| `HTML_Safe::parse()` / `sanitizeInput()` null input — PHP 8.1 deprecation on `preg_replace(null)` | Coerce `$doc` to string at top of `parse()` |

The "What is NOT fixed" section below still applies. The gallery is now fully functional for viewing, navigating, and uploading photos as a logged-in admin and as an unauthenticated visitor.

---

## What is NOT fixed

- **`mysql_*` functions** — the MySQL database driver (`classes/database/mysql/`) still uses the long-removed `mysql_connect()` etc. This code path only runs when Gallery is embedded inside old PHP-Nuke/PostNuke/Joomla/Mambo CMS installations. For standalone flat-file use (the point of this exercise) it is never loaded.
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
4. Deployed against a real 20-year-old gallery with real albums and photos
5. Fixed each runtime error as it surfaced, in order, until the gallery was fully functional

The `clean-import` branch is left untouched as the original. The original README is preserved below.

---

## Original README

See the `clean-import` branch for the verbatim Sourceforge SVN export, including the original `README` file.
