-- ============================================================
-- LUMEEGY — Migration: Security & Analytics Tables
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
