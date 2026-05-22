-- ============================================================
-- Migration: Add Google OAuth Support
-- ============================================================

ALTER TABLE `users`
ADD COLUMN `google_id` VARCHAR(100) NULL DEFAULT NULL AFTER `password_hash`,
ADD UNIQUE KEY `google_id` (`google_id`);
