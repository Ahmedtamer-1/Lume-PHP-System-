-- ============================================================
-- LUMEEGY — Migration V2: Major Feature Overhaul
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
  ('theme_color_bg',           '#0A0A0A'),
  ('theme_color_bg_card',      'rgba(10,10,10,0.88)'),
  ('theme_color_cream',        '#F5F5F0'),
  ('theme_color_gold',         '#C8B89A'),
  ('theme_color_accent',       '#C4714A'),
  ('theme_color_muted',        '#888880'),
  ('theme_font_heading',       'Bodoni Moda'),
  ('theme_font_body',          'Red Hat Display'),
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
('hero', 'LUMEEGY', 'Illuminate Your Ritual', NULL, 'Explore the Collection', '/shop.php', 1, 1,
 '{"bg_image": "assets/images/hero-bg.png", "show_particles": true}'),
('featured_products', 'Featured Products', 'Our most-loved pieces, hand-picked by our editors.', NULL, 'View All Products', '/shop.php', 1, 2,
 '{"eyebrow": "Curated for You", "product_count": 4}'),
('brand_story', 'Born from Light', NULL, 'LUMEEGY was born from a simple belief — that style is a ritual, not a routine. Rooted in the spirit of Egyptian elegance, each piece is crafted to bring a moment of luxury into your everyday.\n\nFrom our signature silhouettes to carefully selected fabrics, every detail is designed to illuminate — your look, your confidence, your spirit.', 'Read Our Story', '/about.php', 1, 3,
 '{"eyebrow": "Our Story", "image": "assets/images/hero-bg.png"}');
