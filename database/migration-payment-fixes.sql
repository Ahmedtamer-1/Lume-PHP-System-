-- ============================================================
-- LUMEEGY — Payment Fixes Migration
-- Safe / Idempotent — run in Hostinger phpMyAdmin.
-- Adds a composite index for efficient stale pending-online-order
-- queries, and ensures all required columns exist.
-- ============================================================

-- ── 1. Ensure phone & shipping_zone exist on orders (covered by migration-fix-all.sql too) ──
ALTER TABLE `orders`
  ADD COLUMN IF NOT EXISTS `phone`         VARCHAR(30)  DEFAULT NULL AFTER `guest_email`,
  ADD COLUMN IF NOT EXISTS `shipping_zone` VARCHAR(100) DEFAULT NULL AFTER `shipping_country`;

-- ── 2. Add composite index for fast lookup of stale pending online orders ──
-- Used to find: WHERE payment_method = 'online' AND status = 'pending'
ALTER TABLE `orders`
  ADD INDEX IF NOT EXISTS `idx_payment_status` (`payment_method`(20), `status`);

-- ── 3. Ensure paymob settings keys exist ──
INSERT IGNORE INTO `settings` (`key_name`, `value`) VALUES
('paymob_api_key',        ''),
('paymob_integration_id', ''),
('paymob_iframe_id',      ''),
('paymob_hmac',           '');

-- Done! ✓
