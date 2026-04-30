# Gallery 1.x — Lightweight Album Protection

Gallery 1.x serves album files directly from disk via Apache. There is no
PHP layer between a browser and a photo URL once the file path is known.
These snippets approximate the best protection a careful sysadmin could have
assembled around a Gallery 1 install in the mid-2000s.

None of this is a substitute for real authentication. If you need genuine
access control, put the whole gallery behind Anubis, HTTP Basic Auth, or
similar before exposing it to the world.

---

## Files

### `albums.htaccess`

Drop in your albums directory as `.htaccess`. Provides two layers:

**Referrer check** — rejects requests whose `Referer` header doesn't come
from your domain. Stops casual hotlinking. Edit the `example.com` line to
match your domain.

Limitations: easily defeated by spoofing the `Referer` header; also blocks
legitimate users whose browser strips referrers (privacy extensions,
HTTPS→HTTP navigation). Remove the referrer block if it causes problems.

**Cookie check** — rejects requests that don't carry the `gallery_access`
cookie. Anyone who visits the gallery gets the cookie automatically; direct
URL access and hotlinks from unknown referrers do not.

### `cookie_gate.php`

Drop in your Gallery root directory. Sets a daily-rotating HMAC cookie when
any Gallery page is loaded.

Wire it in by adding one line near the top of `init.php`:

```php
require_once(dirname(__FILE__) . '/cookie_gate.php');
```

Edit `GALLERY_COOKIE_SECRET` to a long random string and keep it out of
version control (add `cookie_gate.php` to `.gitignore`).

**How the rotation works:** the cookie value is `HMAC-SHA256(today's date,
secret)`. It changes at midnight. A captured token is valid for the rest of
the day it was issued and worthless after that.

**What mod_rewrite actually checks:** presence of the cookie, not the HMAC
value. Apache cannot verify an HMAC. The security comes from the value being
unguessable without knowing the secret — not from cryptographic verification
at the Apache layer.

---

## What this protects against

- Direct URL guessing without visiting the gallery
- Hotlinking from other domains
- Casual scrapers that don't run JavaScript or store cookies

## What this does NOT protect against

- Anyone who visits the gallery once (they have a valid token until midnight)
- Spoofed `Referer` headers
- Determined attackers who read the cookie value from their own browser
