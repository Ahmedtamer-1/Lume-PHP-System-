-- ============================================================
-- Adorna — Complete Database Setup File
-- Generated automatically by combining schema and migrations
-- ============================================================

-- --------------------------------------------------------
-- FILE: schema.sql
-- --------------------------------------------------------
-- ============================================================
-- Adorna — MySQL Database Schema
-- Compatible with phpMyAdmin / Hostinger MySQL 5.7 / 8.0
-- Run this in phpMyAdmin or: mysql -u root -p adorna < schema.sql
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- If running on Hostinger phpMyAdmin, the database is already selected.
-- Remove or comment out the next two lines if your DB has a different name.
-- CREATE DATABASE IF NOT EXISTS `u670046331_Lume_database`
--   CHARACTER SET utf8mb4
--   COLLATE utf8mb4_unicode_ci;
-- USE `u670046331_Lume_database`;

-- --------------------------------------------------------
-- Table: categories
-- --------------------------------------------------------
CREATE TABLE `categories` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100) NOT NULL,
  `slug`        VARCHAR(120) NOT NULL,
  `description` TEXT,
  `image`       VARCHAR(255),
  `sort_order`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: products
-- --------------------------------------------------------
CREATE TABLE `products` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id`  INT UNSIGNED,
  `name`         VARCHAR(200) NOT NULL,
  `slug`         VARCHAR(220) NOT NULL,
  `description`  TEXT,
  `price`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `cost_price`   DECIMAL(10,2) DEFAULT NULL,
  `sale_price`   DECIMAL(10,2) DEFAULT NULL,
  `sku`          VARCHAR(80),
  `stock`        SMALLINT NOT NULL DEFAULT 0,
  `is_featured`  TINYINT(1) NOT NULL DEFAULT 0,
  `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
  `image`        VARCHAR(255),
  `gallery`      JSON,
  `meta_title`   VARCHAR(200),
  `meta_desc`    VARCHAR(300),
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  KEY `is_featured` (`is_featured`),
  CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name`      VARCHAR(80) NOT NULL,
  `last_name`       VARCHAR(80) NOT NULL,
  `email`           VARCHAR(180) NOT NULL,
  `password_hash`   VARCHAR(255) NOT NULL,
  `role`            ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  `email_verified`  TINYINT(1) NOT NULL DEFAULT 0,
  `verify_token`    VARCHAR(64),
  `reset_token`     VARCHAR(64),
  `reset_expires`   DATETIME,
  `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: user_addresses
-- --------------------------------------------------------
CREATE TABLE `user_addresses` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED NOT NULL,
  `label`       VARCHAR(60) NOT NULL DEFAULT 'Home',
  `full_name`   VARCHAR(160) NOT NULL,
  `phone`       VARCHAR(30),
  `address_1`   VARCHAR(255) NOT NULL,
  `address_2`   VARCHAR(255),
  `city`        VARCHAR(100) NOT NULL,
  `state`       VARCHAR(100),
  `postcode`    VARCHAR(20),
  `country`     VARCHAR(80) NOT NULL DEFAULT 'EG',
  `is_default`  TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_address_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: orders
-- --------------------------------------------------------
CREATE TABLE `orders` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number`    VARCHAR(30) NOT NULL,
  `user_id`         INT UNSIGNED,
  `guest_email`     VARCHAR(180),
  `status`          ENUM('pending','paid','processing','shipped','delivered','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `subtotal`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `shipping_cost`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax`             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total`           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency`        VARCHAR(5) NOT NULL DEFAULT 'EGP',
  `payment_method`  VARCHAR(80),
  `payment_ref`     VARCHAR(255),
  `shipping_name`   VARCHAR(160),
  `shipping_addr`   TEXT,
  `shipping_city`   VARCHAR(100),
  `shipping_country`VARCHAR(80),
  `notes`           TEXT,
  `meta_pixel_event_sent` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: order_items
-- --------------------------------------------------------
CREATE TABLE `order_items` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`    INT UNSIGNED NOT NULL,
  `product_id`  INT UNSIGNED,
  `name`        VARCHAR(200) NOT NULL,
  `sku`         VARCHAR(80),
  `price`       DECIMAL(10,2) NOT NULL,
  `cost_price`  DECIMAL(10,2) DEFAULT NULL,
  `quantity`    SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `subtotal`    DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `fk_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: cart_sessions (server-side persistent cart)
-- --------------------------------------------------------
CREATE TABLE `cart_sessions` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_key` VARCHAR(64) NOT NULL,
  `user_id`     INT UNSIGNED,
  `product_id`  INT UNSIGNED NOT NULL,
  `quantity`    SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `session_key` (`session_key`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: newsletter_subscribers
-- --------------------------------------------------------
CREATE TABLE `newsletter_subscribers` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`        VARCHAR(180) NOT NULL,
  `status`       ENUM('active','unsubscribed') NOT NULL DEFAULT 'active',
  `source`       VARCHAR(80) DEFAULT 'website',
  `subscribed_at`TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: contact_messages
-- --------------------------------------------------------
CREATE TABLE `contact_messages` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(160) NOT NULL,
  `email`      VARCHAR(180) NOT NULL,
  `subject`    VARCHAR(255),
  `message`    TEXT NOT NULL,
  `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
  `ip`         VARCHAR(45),
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: settings
-- --------------------------------------------------------
CREATE TABLE `settings` (
  `key_name`   VARCHAR(100) NOT NULL,
  `value`      TEXT,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: login_attempts (rate limiting)
-- --------------------------------------------------------
CREATE TABLE `login_attempts` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `identifier`   VARCHAR(180) NOT NULL,
  `ip_address`   VARCHAR(45) NOT NULL,
  `success`      TINYINT(1) NOT NULL DEFAULT 0,
  `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `identifier` (`identifier`, `attempted_at`),
  KEY `ip_address` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: activity_log (admin audit trail)
-- --------------------------------------------------------
CREATE TABLE `activity_log` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED,
  `action`      VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(50),
  `entity_id`   INT UNSIGNED,
  `details`     TEXT,
  `ip_address`  VARCHAR(45),
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA — Sample categories & products
-- ============================================================
INSERT INTO `categories` (`name`, `slug`, `description`, `sort_order`) VALUES
('Tops',       'tops',       'Shirts, tees, and blouses',      1),
('Bottoms',    'bottoms',    'Trousers, jeans, and skirts',    2),
('Outerwear',  'outerwear',  'Jackets, coats, and layers',     3),
('Accessories','accessories','Bags, scarves, and essentials',   4);

INSERT INTO `products` (`category_id`, `name`, `slug`, `description`, `price`, `sale_price`, `sku`, `stock`, `is_featured`, `image`) VALUES
(1, 'Linen Oversized Shirt',   'linen-oversized-shirt',   'A relaxed-fit linen shirt in washed sand. Breathable, effortless, and endlessly versatile.', 1250.00, NULL,    'LME-TP-001', 50, 1, 'assets/images/products/product-1.jpg'),
(3, 'Midnight Bomber Jacket',  'midnight-bomber-jacket',  'A sleek bomber in matte black with ribbed cuffs and a satin-finish lining.',                2400.00, 1980.00,'LME-OW-001', 30, 1, 'assets/images/products/product-2.jpg'),
(2, 'Tailored Wide-Leg Trouser','tailored-wide-leg-trouser','High-waisted wide-leg trousers in soft crepe. A modern silhouette for day or night.',        1890.00, NULL,    'LME-BT-001', 40, 1, 'assets/images/products/product-3.jpg'),
(4, 'Canvas Tote Bag',         'canvas-tote-bag',         'A structured canvas tote with leather handles. Minimal, functional, and made to last.',       980.00, NULL,    'LME-AC-001', 60, 0, 'assets/images/products/product-4.jpg'),
(1, 'Essentials Starter Set',  'essentials-starter-set',  'A curated trio of wardrobe staples — the perfect introduction to the Adorna world.',          3200.00, 2750.00,'LME-TP-002', 20, 1, 'assets/images/products/product-5.jpg'),
(2, 'Ribbed Knit Skirt',       'ribbed-knit-skirt',       'A fitted midi skirt in ribbed knit. Pairs effortlessly with any top in the collection.',       2100.00, NULL,    'LME-BT-002', 25, 0, 'assets/images/products/product-6.jpg');

INSERT INTO `settings` (`key_name`, `value`) VALUES
('site_name',          'Adorna'),
('site_tagline',       'Elegance in Every Detail'),
('meta_pixel_id',      ''),
('gateway_api_key',    ''),
('currency',           'EGP'),
('currency_symbol',    'EGP '),
('shipping_flat_rate', '100'),
('free_shipping_over', '2000'),
('contact_email',      'hello@adorna.com'),
('instagram_url',      'https://instagram.com/adorna'),
('tiktok_url',         ''),
('facebook_url',       '');


-- --------------------------------------------------------
-- FILE: migration-variants.sql
-- --------------------------------------------------------
-- ============================================================
-- Adorna — Migration: Product Variants
-- Run this in phpMyAdmin on your existing database
-- ============================================================

-- Add has_variants flag to products
ALTER TABLE `products` ADD COLUMN `has_variants` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`;

-- Product variants table
CREATE TABLE IF NOT EXISTS `product_variants` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id`     INT UNSIGNED NOT NULL,
  `size`           VARCHAR(20) DEFAULT NULL,
  `color_name`     VARCHAR(50) DEFAULT NULL,
  `color_hex`      VARCHAR(7) DEFAULT NULL,
  `sku`            VARCHAR(80) DEFAULT NULL,
  `price_override` DECIMAL(10,2) DEFAULT NULL,
  `cost_price`     DECIMAL(10,2) DEFAULT NULL,
  `stock`          SMALLINT NOT NULL DEFAULT 0,
  `image`          VARCHAR(255) DEFAULT NULL,
  `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  UNIQUE KEY `unique_variant` (`product_id`, `size`, `color_name`),
  CONSTRAINT `fk_variant_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add variant_id to cart_sessions
ALTER TABLE `cart_sessions` ADD COLUMN `variant_id` INT UNSIGNED DEFAULT NULL AFTER `product_id`;
ALTER TABLE `cart_sessions` ADD CONSTRAINT `fk_cart_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;

-- Add variant columns to order_items
ALTER TABLE `order_items` ADD COLUMN `variant_id` INT UNSIGNED DEFAULT NULL AFTER `product_id`;
ALTER TABLE `order_items` ADD COLUMN `variant_size` VARCHAR(20) DEFAULT NULL AFTER `variant_id`;
ALTER TABLE `order_items` ADD COLUMN `variant_color` VARCHAR(50) DEFAULT NULL AFTER `variant_size`;

-- ============================================================
-- SAMPLE VARIANT DATA (for existing seed products)
-- ============================================================
-- Linen Oversized Shirt (product 1) — sizes only
INSERT INTO `product_variants` (`product_id`, `size`, `color_name`, `color_hex`, `sku`, `stock`, `sort_order`) VALUES
(1, 'S',  NULL, NULL, 'LME-TP-001-S',  8,  1),
(1, 'M',  NULL, NULL, 'LME-TP-001-M',  12, 2),
(1, 'L',  NULL, NULL, 'LME-TP-001-L',  15, 3),
(1, 'XL', NULL, NULL, 'LME-TP-001-XL', 10, 4);

-- Midnight Bomber Jacket (product 2) — sizes + colors
INSERT INTO `product_variants` (`product_id`, `size`, `color_name`, `color_hex`, `sku`, `stock`, `sort_order`) VALUES
(2, 'S',  'Black',    '#1a1a1a', 'LME-OW-001-S-BK',  5,  1),
(2, 'M',  'Black',    '#1a1a1a', 'LME-OW-001-M-BK',  8,  2),
(2, 'L',  'Black',    '#1a1a1a', 'LME-OW-001-L-BK',  6,  3),
(2, 'S',  'Charcoal', '#36454F', 'LME-OW-001-S-CH',  4,  4),
(2, 'M',  'Charcoal', '#36454F', 'LME-OW-001-M-CH',  7,  5),
(2, 'L',  'Charcoal', '#36454F', 'LME-OW-001-L-CH',  5,  6);

-- Tailored Wide-Leg Trouser (product 3)
INSERT INTO `product_variants` (`product_id`, `size`, `color_name`, `color_hex`, `sku`, `stock`, `sort_order`) VALUES
(3, 'S',  'Sand',  '#C2B280', 'LME-BT-001-S',  10, 1),
(3, 'M',  'Sand',  '#C2B280', 'LME-BT-001-M',  12, 2),
(3, 'L',  'Sand',  '#C2B280', 'LME-BT-001-L',  8,  3),
(3, 'XL', 'Sand',  '#C2B280', 'LME-BT-001-XL', 6,  4);

-- Mark these products as having variants
UPDATE `products` SET `has_variants` = 1 WHERE `id` IN (1, 2, 3);


-- --------------------------------------------------------
-- FILE: migration-shipping-settings.sql
-- --------------------------------------------------------
-- ============================================================
-- Adorna — Migration: Shipping Zones, COD & Site Settings
-- Run this in phpMyAdmin on your existing database
-- ============================================================

-- ── Shipping Zones (Egyptian Governorates) ────────────────────
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

-- Seed Egyptian governorates with default shipping costs
INSERT INTO `shipping_zones` (`name`, `cost`, `sort_order`) VALUES
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

-- ── Add phone + shipping_zone to orders ──────────────────────
ALTER TABLE `orders` ADD COLUMN `phone` VARCHAR(30) DEFAULT NULL AFTER `guest_email`;
ALTER TABLE `orders` ADD COLUMN `shipping_zone` VARCHAR(100) DEFAULT NULL AFTER `shipping_country`;

-- ── Expanded site settings ───────────────────────────────────
INSERT INTO `settings` (`key_name`, `value`) VALUES
('maintenance_mode',       '0'),
('maintenance_message',    'We are currently performing scheduled maintenance. We will be back online shortly.'),
('site_logo',              ''),
('site_favicon',           ''),
('og_image',               ''),
('default_meta_title',     'Adorna — Elegance in Every Detail'),
('default_meta_description','Discover Adorna — a luxury fashion brand. Curated clothing and accessories crafted in Egypt.'),
('default_meta_keywords',  'Adorna, fashion, luxury clothing, accessories, fashion brand Egypt'),
('google_analytics_id',    ''),
('cod_enabled',            '1'),
('cod_label',              'Cash on Delivery'),
('cod_extra_fee',          '0'),
('phone_display_name',     'Phone Number')
ON DUPLICATE KEY UPDATE `key_name` = `key_name`;


-- --------------------------------------------------------
-- FILE: migration-security.sql
-- --------------------------------------------------------
-- ============================================================
-- Adorna — Migration: Security & Analytics Tables
-- Run this in phpMyAdmin on your existing database
-- ============================================================

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `identifier`   VARCHAR(180) NOT NULL,
  `ip_address`   VARCHAR(45) NOT NULL,
  `success`      TINYINT(1) NOT NULL DEFAULT 0,
  `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `identifier` (`identifier`, `attempted_at`),
  KEY `ip_address` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `activity_log` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED,
  `action`      VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(50),
  `entity_id`   INT UNSIGNED,
  `details`     TEXT,
  `ip_address`  VARCHAR(45),
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- FILE: migration-v2-overhaul.sql
-- --------------------------------------------------------
-- ============================================================
-- Adorna — Migration V2: Major Feature Overhaul
-- Run this in phpMyAdmin on your existing database
-- ============================================================

-- ── 1. Media Library ──
CREATE TABLE IF NOT EXISTS `media_library` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `filename`    VARCHAR(255) NOT NULL,
  `filepath`    VARCHAR(500) NOT NULL,
  `filetype`    VARCHAR(50) NOT NULL DEFAULT 'image',
  `filesize`    INT UNSIGNED NOT NULL DEFAULT 0,
  `width`       INT UNSIGNED DEFAULT NULL,
  `height`      INT UNSIGNED DEFAULT NULL,
  `alt_text`    VARCHAR(255) DEFAULT NULL,
  `uploaded_by` INT UNSIGNED DEFAULT NULL,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `filetype` (`filetype`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. Homepage Sections ──
CREATE TABLE IF NOT EXISTS `homepage_sections` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `section_type` VARCHAR(50) NOT NULL DEFAULT 'text_block',
  `title`       VARCHAR(255) DEFAULT NULL,
  `subtitle`    VARCHAR(500) DEFAULT NULL,
  `content`     TEXT DEFAULT NULL,
  `image`       VARCHAR(500) DEFAULT NULL,
  `button_text` VARCHAR(100) DEFAULT NULL,
  `button_url`  VARCHAR(500) DEFAULT NULL,
  `settings`    JSON DEFAULT NULL,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order`  INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sort_order` (`sort_order`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. Theme/Appearance Settings (stored in settings table) ──
INSERT INTO `settings` (`key_name`, `value`) VALUES
  ('theme_color_bg',           '#FFFFFF'),
  ('theme_color_bg_card',      '#F9F6EE'),
  ('theme_color_cream',        '#E5E4E2'),
  ('theme_color_gold',         '#D4AF37'),
  ('theme_color_accent',       '#800020'),
  ('theme_color_muted',        '#888880'),
  ('theme_font_heading',       'Playfair Display'),
  ('theme_font_body',          'Arial'),
  ('theme_font_heading_weight','400;700;900'),
  ('theme_font_body_weight',   '300;400;500;600'),
  ('show_stock_indicator',     '1'),
  ('stock_low_threshold',      '5'),
  ('show_marquee',             '1'),
  ('marquee_text',             ''),
  ('show_newsletter',          '1'),
  ('newsletter_title',         'The Inner Circle'),
  ('newsletter_eyebrow',       'Join the Ritual'),
  ('newsletter_subtitle',      'Early access. Exclusive drops. Members-only offers.')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- ── 4. Seed default homepage sections ──
INSERT INTO `homepage_sections` (`section_type`, `title`, `subtitle`, `content`, `button_text`, `button_url`, `is_active`, `sort_order`, `settings`) VALUES
('hero', 'Adorna', 'Elegance in Every Detail', NULL, 'Explore the Collection', '/shop.php', 1, 1,
 '{"bg_image": "assets/images/hero-bg.png", "show_particles": true}'),
('featured_products', 'Featured Products', 'Our most-loved pieces, hand-picked by our editors.', NULL, 'View All Products', '/shop.php', 1, 2,
 '{"eyebrow": "Curated for You", "product_count": 4}'),
('brand_story', 'Born from Light', NULL, 'Adorna was born from a simple belief — that style is a ritual, not a routine. Rooted in the spirit of Egyptian elegance, each piece is crafted to bring a moment of luxury into your everyday.\n\nFrom our signature silhouettes to carefully selected fabrics, every detail is designed to illuminate — your look, your confidence, your spirit.', 'Read Our Story', '/about.php', 1, 3,
 '{"eyebrow": "Our Story", "image": "assets/images/hero-bg.png"}');


-- --------------------------------------------------------
-- FILE: migration-add-bombo-sizes.sql
-- --------------------------------------------------------
-- ============================================================
-- Adorna — Migration: Add Missing Size Variants for bombo
-- Run this in phpMyAdmin on your existing database
-- This adds M and L size variants for the White color
-- ============================================================

-- First find the product ID for bombo (adjust if needed)
-- Assuming the product slug is 'bombo'

-- Add Medium variant for White color (matching existing White/S variant)
INSERT IGNORE INTO `product_variants` 
(`product_id`, `size`, `color_name`, `color_hex`, `sku`, `stock`, `sort_order`, `is_active`)
SELECT 
    p.id, 'M', 'White', '#FFFFFF', 
    CONCAT(COALESCE(p.sku, 'BMB'), '-M-WH'),
    10, 2, 1
FROM products p WHERE p.slug = 'bombo';

-- Add Large variant for White color
INSERT IGNORE INTO `product_variants` 
(`product_id`, `size`, `color_name`, `color_hex`, `sku`, `stock`, `sort_order`, `is_active`)
SELECT 
    p.id, 'L', 'White', '#FFFFFF', 
    CONCAT(COALESCE(p.sku, 'BMB'), '-L-WH'),
    10, 3, 1
FROM products p WHERE p.slug = 'bombo';

-- Ensure has_variants is set to 1
UPDATE `products` SET `has_variants` = 1 WHERE `slug` = 'bombo';

-- NOTE: Images for the White color should be managed via Color Galleries 
-- in the admin panel (Admin > Products > Variants > Color Image Galleries section).
-- This eliminates the need to attach images to each individual size variant.


-- --------------------------------------------------------
-- FILE: migration-fix-all.sql
-- --------------------------------------------------------
-- ============================================================
-- Adorna — Fix-All Migration (Safe / Idempotent)
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
('default_meta_title',     'Adorna — Elegance in Every Detail'),
('default_meta_description','Discover Adorna — a luxury fashion brand. Curated clothing and accessories crafted in Egypt.'),
('default_meta_keywords',  'Adorna, fashion, luxury clothing, accessories, fashion brand Egypt'),
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


