<?php
/**
 * LUMEEGY — Central Configuration
 * ─────────────────────────────────────────────────────────────
 * Edit this file to set your local / Hostinger credentials.
 * NEVER commit real API keys to version control.
 */

// ── Environment ──────────────────────────────────────────────
if(!defined('ENV')) define('ENV', 'production'); // 'development' | 'production'
if(!defined('SITE_URL')) define('SITE_URL', 'https://darkorange-kangaroo-844530.hostingersite.com');
if(!defined('SITE_NAME')) define('SITE_NAME', 'LUMEEGY');
if(!defined('SITE_TAGLINE')) define('SITE_TAGLINE', 'Illuminate Your Ritual');

// ── Database ─────────────────────────────────────────────────
// Hostinger: use credentials from hPanel → Databases
if(!defined('DB_HOST')) define('DB_HOST', 'localhost');
if(!defined('DB_NAME')) define('DB_NAME', 'u670046331_Lume_database');
if(!defined('DB_USER')) define('DB_USER', 'u670046331_lume');
if(!defined('DB_PASS')) define('DB_PASS', ':/eIZO@V3Si');
if(!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// ── Meta Pixel ───────────────────────────────────────────────
// Paste your Pixel ID from Meta Events Manager
if(!defined('META_PIXEL_ID')) define('META_PIXEL_ID', '');        // e.g. '1234567890123456'

// ── Payment Gateway ──────────────────────────────────────────
// Supports: paymob | fawry | stripe (set below)
if(!defined('GATEWAY_PROVIDER')) define('GATEWAY_PROVIDER', 'paymob');
if(!defined('GATEWAY_API_KEY')) define('GATEWAY_API_KEY', '');      // Your gateway secret key
if(!defined('GATEWAY_IFRAME_ID')) define('GATEWAY_IFRAME_ID', '');    // Paymob iframe integration ID (if Paymob)
if(!defined('GATEWAY_HMAC_SECRET')) define('GATEWAY_HMAC_SECRET', ''); // Paymob HMAC secret

// ── Currency ─────────────────────────────────────────────────
if(!defined('CURRENCY_CODE')) define('CURRENCY_CODE',   'EGP');
if(!defined('CURRENCY_SYMBOL')) define('CURRENCY_SYMBOL', 'EGP ');
if(!defined('FREE_SHIPPING_OVER')) define('FREE_SHIPPING_OVER', 2000);  // Order total for free shipping
if(!defined('FLAT_SHIPPING_RATE')) define('FLAT_SHIPPING_RATE',  100);

// ── Security ─────────────────────────────────────────────────
if(!defined('SESSION_NAME')) define('SESSION_NAME', 'lumeegy_session');
if(!defined('CSRF_TOKEN_NAME')) define('CSRF_TOKEN_NAME', '_lume_csrf');

// ── Paths ─────────────────────────────────────────────────────
if(!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
if(!defined('UPLOADS_PATH')) define('UPLOADS_PATH', ROOT_PATH . '/assets/images/products');
if(!defined('UPLOADS_URL')) define('UPLOADS_URL',  SITE_URL . '/assets/images/products');

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
