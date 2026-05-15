-- ============================================================
-- LUMEEGY — Migration: Product Variants
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
