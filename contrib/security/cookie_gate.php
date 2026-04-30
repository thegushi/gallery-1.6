<?php
/*
 * cookie_gate.php — lightweight album directory protection for Gallery 1.x
 *
 * Sets a daily-rotating HMAC cookie that the albums directory .htaccess
 * can check. Anyone who visits the gallery gets the cookie; anyone who
 * tries to access album files directly (hotlinking, URL-guessing) does not.
 *
 * The token rotates at midnight. A captured token is only valid for the
 * rest of the day it was issued.
 *
 * INSTALLATION
 * 1. Copy this file to your Gallery root directory.
 * 2. Set GALLERY_COOKIE_SECRET to a long random string (keep it private).
 * 3. Add to init.php near the top, before anything else:
 *        require_once(dirname(__FILE__) . '/cookie_gate.php');
 * 4. Copy albums.htaccess to your albums directory as .htaccess
 *    (merge with any existing .htaccess content).
 * 5. Add cookie_gate.php to .gitignore so the secret stays out of the repo.
 *
 * CAVEATS
 * - mod_rewrite checks for cookie presence only; it cannot verify the HMAC.
 *   Security comes from the token being unguessable, not from verification
 *   at the Apache layer.
 * - Anyone who visits the gallery can share today's token and it works until
 *   midnight. This is not a substitute for real authentication.
 * - Set $secure = true if your site runs HTTPS-only (recommended).
 */

define('GALLERY_COOKIE_SECRET', 'replace-this-with-a-long-random-string');

$_gallery_token = hash_hmac('sha256', date('Y-m-d'), GALLERY_COOKIE_SECRET);
$_gallery_secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

if (($_COOKIE['gallery_access'] ?? '') !== $_gallery_token) {
    setcookie('gallery_access', $_gallery_token, 0, '/', '', $_gallery_secure, true);
}

unset($_gallery_token, $_gallery_secure);
