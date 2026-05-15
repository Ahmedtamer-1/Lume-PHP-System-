-- ============================================================
-- LUMEEGY — Fix-All Migration (Safe / Idempotent)
-- Run this in Hostinger phpMyAdmin if checkout or shipping is broken.
-- It is SAFE to run more than once — uses IF NOT EXISTS / IGNORE.
-- ============================================================

-- ── 1. Shipping Zones table ───────────────────────────────────
CREATE TABLE IF NOT EXISTS `shipping_zones` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `cost`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. Add `phone` and `shipping_zone` to orders (safe) ───────
ALTER TABLE `orders`
  ADD COLUMN IF NOT EXISTS `phone`         VARCHAR(30)  DEFAULT NULL AFTER `guest_email`,
  ADD COLUMN IF NOT EXISTS `shipping_zone` VARCHAR(100) DEFAULT NULL AFTER `shipping_country`;

-- ── 3. Add variant columns to order_items (safe) ──────────────
ALTER TABLE `order_items`
  ADD COLUMN IF NOT EXISTS `variant_id`    INT UNSIGNED DEFAULT NULL AFTER `product_id`,
  ADD COLUMN IF NOT EXISTS `variant_size`  VARCHAR(50)  DEFAULT NULL AFTER `variant_id`,
  ADD COLUMN IF NOT EXISTS `variant_color` VARCHAR(80)  DEFAULT NULL AFTER `variant_size`;

-- ── 4. Add `variant_id` to cart_sessions (safe) ───────────────
ALTER TABLE `cart_sessions`
  ADD COLUMN IF NOT EXISTS `variant_id` INT UNSIGNED DEFAULT NULL AFTER `product_id`;

-- ── 5. Settings — add all required keys (ignore duplicates) ───
INSERT IGNORE INTO `settings` (`key_name`, `value`) VALUES
('maintenance_mode',       '0'),
('maintenance_message',    'We are currently performing scheduled maintenance. We will be back online shortly.'),
('site_logo',              ''),
('site_favicon',           ''),
('og_image',               ''),
('default_meta_title',     'LUMEEGY — Illuminate Your Ritual'),
('default_meta_description','Discover LUMEEGY — a luxury Egyptian fashion brand. Curated clothing and accessories crafted in Egypt.'),
('default_meta_keywords',  'LUMEEGY, Egyptian fashion, luxury clothing, accessories, fashion brand Egypt'),
('google_analytics_id',    ''),
('meta_pixel_id',          ''),
('cod_enabled',            '1'),
('cod_label',              'Cash on Delivery'),
('cod_extra_fee',          '0'),
('phone_display_name',     'Phone Number'),
('free_shipping_over',     '2000');

-- ── 6. Seed Egyptian governorates (ignore if already exist) ───
INSERT IGNORE INTO `shipping_zones` (`name`, `cost`, `sort_order`) VALUES
('Cairo',                  60.00,  1),
('Giza',                   60.00,  2),
('6th of October',         70.00,  3),
('Sheikh Zayed',           70.00,  4),
('Alexandria',             80.00,  5),
('Qalyubia',               75.00,  6),
('Sharqia',                85.00,  7),
('Dakahlia',               85.00,  8),
('Gharbia',                85.00,  9),
('Monufia',                85.00, 10),
('Beheira',                90.00, 11),
('Kafr El Sheikh',         90.00, 12),
('Damietta',               85.00, 13),
('Port Said',              80.00, 14),
('Ismailia',               80.00, 15),
('Suez',                   80.00, 16),
('Fayoum',                 90.00, 17),
('Beni Suef',              95.00, 18),
('Minya',                 100.00, 19),
('Assiut',                110.00, 20),
('Sohag',                 110.00, 21),
('Qena',                  120.00, 22),
('Luxor',                 120.00, 23),
('Aswan',                 130.00, 24),
('Red Sea',               130.00, 25),
('Matrouh',               130.00, 26),
('North Sinai',           140.00, 27),
('South Sinai',           140.00, 28),
('New Valley',            140.00, 29);

-- Done! ✓
