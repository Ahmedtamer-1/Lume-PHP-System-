-- Migration: Headless Architecture v1
-- Adds tracking columns to orders, the pixel_event_log table, and new settings keys

-- 1. Add event tracking flags to orders
ALTER TABLE `orders` 
ADD COLUMN IF NOT EXISTS `tiktok_event_sent` TINYINT(1) NOT NULL DEFAULT 0 AFTER `meta_pixel_event_sent`,
ADD COLUMN IF NOT EXISTS `google_event_sent` TINYINT(1) NOT NULL DEFAULT 0 AFTER `tiktok_event_sent`;

-- 2. Create internal pixel event log for debugging
CREATE TABLE IF NOT EXISTS `pixel_event_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED DEFAULT NULL,
  `platform` VARCHAR(50) NOT NULL,
  `event_name` VARCHAR(100) NOT NULL,
  `success` TINYINT(1) NOT NULL DEFAULT 0,
  `response` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `platform` (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Add new settings keys (IGNORE if they already exist, primary key is key_name)
INSERT IGNORE INTO `settings` (`key_name`, `value`) VALUES
('tiktok_pixel_id', ''),
('tiktok_access_token', ''),
('tiktok_enabled', '0'),
('google_ads_conversion_id', ''),
('ga4_measurement_id', ''),
('ga4_api_secret', ''),
('google_events_enabled', '0'),
('meta_conversions_api_token', ''),
('meta_test_event_code', ''),
('meta_capi_enabled', '0');
