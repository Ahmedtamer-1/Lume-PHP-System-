-- ============================================================
-- LUMEEGY — Email Settings Migration
-- Safe / Idempotent — run in Hostinger phpMyAdmin.
-- Adds all email/SMTP settings keys with sensible defaults.
-- ============================================================

INSERT IGNORE INTO `settings` (`key_name`, `value`) VALUES
('email_enabled',              '0'),
('email_from_name',            'LUMEEGY'),
('email_from_address',         ''),
('email_confirmation_enabled', '1'),
('email_shipped_enabled',      '1'),
('email_cancelled_enabled',    '1'),
('email_resend_api_key',       ''),
('smtp_host',                  'smtp.zoho.com'),
('smtp_port',                  '587'),
('smtp_username',              ''),
('smtp_password',              ''),
('smtp_secure',                'tls');

-- Done! ✓
