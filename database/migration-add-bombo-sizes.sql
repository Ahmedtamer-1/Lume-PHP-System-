-- ============================================================
-- LUMEEGY — Migration: Add Missing Size Variants for bombo
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
