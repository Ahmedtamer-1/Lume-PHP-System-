-- ============================================================
-- LUMEEGY — MySQL Database Schema
-- Compatible with phpMyAdmin / Hostinger MySQL 5.7 / 8.0
-- Run this in phpMyAdmin or: mysql -u root -p lumeegy < schema.sql
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
(1, 'Essentials Starter Set',  'essentials-starter-set',  'A curated trio of wardrobe staples — the perfect introduction to the LUMEEGY world.',          3200.00, 2750.00,'LME-TP-002', 20, 1, 'assets/images/products/product-5.jpg'),
(2, 'Ribbed Knit Skirt',       'ribbed-knit-skirt',       'A fitted midi skirt in ribbed knit. Pairs effortlessly with any top in the collection.',       2100.00, NULL,    'LME-BT-002', 25, 0, 'assets/images/products/product-6.jpg');

INSERT INTO `settings` (`key_name`, `value`) VALUES
('site_name',          'LUMEEGY'),
('site_tagline',       'Illuminate Your Ritual'),
('meta_pixel_id',      ''),
('gateway_api_key',    ''),
('currency',           'EGP'),
('currency_symbol',    'EGP '),
('shipping_flat_rate', '100'),
('free_shipping_over', '2000'),
('contact_email',      'hello@lumeegy.com'),
('instagram_url',      'https://instagram.com/lumeegy'),
('tiktok_url',         ''),
('facebook_url',       '');
