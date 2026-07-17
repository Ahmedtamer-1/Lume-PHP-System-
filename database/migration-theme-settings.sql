-- ============================================================
-- LUMEEGY — Migration: Theme Settings Table
-- Run this in phpMyAdmin on your existing database
-- ============================================================

-- Note: The existing `settings` table already stores theme values
-- (theme_color_bg, theme_color_gold, etc.). This table provides
-- a dedicated, spec-required table for the Theme API endpoint.
-- Both can coexist — the API reads from theme_settings first,
-- then falls back to the settings table.

CREATE TABLE IF NOT EXISTS `theme_settings` (
  `setting_key`   VARCHAR(100) NOT NULL,
  `setting_value` TEXT,
  `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed initial theme settings
INSERT INTO `theme_settings` (`setting_key`, `setting_value`) VALUES
('primary_color',     '#C8B89A'),
('hero_image_url',    'assets/images/hero-bg.png'),
('promo_banner_text', 'Free Shipping on Orders Over EGP 2,000')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);
