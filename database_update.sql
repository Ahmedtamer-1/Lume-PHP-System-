-- LUME Database Idempotent Update Script
-- This script safely updates your database by adding missing tables and columns.
-- If a table or column already exists, it will simply skip it.

-- 1. Create product_variants table
CREATE TABLE IF NOT EXISTS `product_variants` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` int(10) UNSIGNED NOT NULL,
  `size` varchar(20) DEFAULT NULL,
  `color_name` varchar(50) DEFAULT NULL,
  `color_hex` varchar(7) DEFAULT NULL,
  `sku` varchar(80) DEFAULT NULL,
  `price_override` decimal(10,2) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `stock` smallint(6) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_variant` (`product_id`,`size`,`color_name`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `fk_variant_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 2. Add new columns to products table
ALTER TABLE `products`
  ADD COLUMN IF NOT EXISTS `cost_price` decimal(10,2) DEFAULT NULL AFTER `price`,
  ADD COLUMN IF NOT EXISTS `has_variants` tinyint(1) NOT NULL DEFAULT 0 AFTER `is_active`,
  ADD COLUMN IF NOT EXISTS `color_galleries` text DEFAULT NULL AFTER `updated_at`,
  ADD COLUMN IF NOT EXISTS `size_chart` varchar(500) DEFAULT NULL AFTER `color_galleries`,
  ADD COLUMN IF NOT EXISTS `material` varchar(255) DEFAULT NULL AFTER `size_chart`,
  ADD COLUMN IF NOT EXISTS `gem` varchar(255) DEFAULT NULL AFTER `material`;


-- 3. Add new columns to order_items table
ALTER TABLE `order_items`
  ADD COLUMN IF NOT EXISTS `variant_id` int(10) UNSIGNED DEFAULT NULL AFTER `product_id`,
  ADD COLUMN IF NOT EXISTS `variant_size` varchar(20) DEFAULT NULL AFTER `variant_id`,
  ADD COLUMN IF NOT EXISTS `variant_color` varchar(50) DEFAULT NULL AFTER `variant_size`,
  ADD COLUMN IF NOT EXISTS `cost_price` decimal(10,2) DEFAULT NULL AFTER `price`;


-- 4. Add new columns to cart_sessions table
ALTER TABLE `cart_sessions`
  ADD COLUMN IF NOT EXISTS `variant_id` int(10) UNSIGNED DEFAULT NULL AFTER `product_id`;


-- 5. Create daily_visitors table
CREATE TABLE IF NOT EXISTS `daily_visitors` (
  `visit_date` date NOT NULL,
  `visitors` int(11) DEFAULT 1,
  `views` int(11) DEFAULT 1,
  PRIMARY KEY (`visit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 6. Create ad_spend table
CREATE TABLE IF NOT EXISTS `ad_spend` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` varchar(50) NOT NULL,
  `campaign_name` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date_logged` date NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
