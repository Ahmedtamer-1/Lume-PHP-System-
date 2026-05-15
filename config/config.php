<?php
/**
 * LUMEEGY — Central Configuration
 * ─────────────────────────────────────────────────────────────
 * Edit this file to set your local / Hostinger credentials.
 * NEVER commit real API keys to version control.
 */

// ── Environment ──────────────────────────────────────────────
define('ENV', 'production'); // 'development' | 'production'
define('SITE_URL', 'https://darkorange-kangaroo-844530.hostingersite.com');
define('SITE_NAME', 'LUMEEGY');
define('SITE_TAGLINE', 'Illuminate Your Ritual');

// ── Database ─────────────────────────────────────────────────
// Hostinger: use credentials from hPanel → Databases
define('DB_HOST', 'localhost');
define('DB_NAME', 'u670046331_Lume_database');
define('DB_USER', 'u670046331_lume');
define('DB_PASS', ':/eIZO@V3Si');
define('DB_CHARSET', 'utf8mb4');

// ── Meta Pixel ───────────────────────────────────────────────
// Paste your Pixel ID from Meta Events Manager
define('META_PIXEL_ID', '');        // e.g. '1234567890123456'

// ── Payment Gateway ──────────────────────────────────────────
// Supports: paymob | fawry | stripe (set below)
define('GATEWAY_PROVIDER', 'paymob');
define('GATEWAY_API_KEY', '');      // Your gateway secret key
define('GATEWAY_IFRAME_ID', '');    // Paymob iframe integration ID (if Paymob)
define('GATEWAY_HMAC_SECRET', ''); // Paymob HMAC secret

// ── Currency ─────────────────────────────────────────────────
define('CURRENCY_CODE',   'EGP');
define('CURRENCY_SYMBOL', 'EGP ');
define('FREE_SHIPPING_OVER', 2000);  // Order total for free shipping
define('FLAT_SHIPPING_RATE',  100);

// ── Security ─────────────────────────────────────────────────
define('SESSION_NAME', 'lumeegy_session');
define('CSRF_TOKEN_NAME', '_lume_csrf');

// ── Paths ─────────────────────────────────────────────────────
define('ROOT_PATH', dirname(__DIR__));
define('UPLOADS_PATH', ROOT_PATH . '/assets/images/products');
define('UPLOADS_URL',  SITE_URL . '/assets/images/products');

// ── Display errors (dev only) ─────────────────────────────────
if (ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// ── Timezone ──────────────────────────────────────────────────
date_default_timezone_set('Africa/Cairo');
