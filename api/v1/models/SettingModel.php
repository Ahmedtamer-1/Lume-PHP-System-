<?php
/**
 * Headless API Setting Model
 */

class SettingModel {
    /**
     * Get a specific setting
     */
    public static function get(string $key, $default = null) {
        $stmt = db()->prepare('SELECT value FROM settings WHERE key_name = ?');
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    }

    /**
     * Get all public settings (filtering out secrets)
     */
    public static function getPublicSettings(): array {
        $stmt = db()->query('SELECT key_name, value FROM settings');
        $all = $stmt->fetchAll();
        $public = [];
        
        $secretKeys = [
            'paymob_api_key', 'paymob_hmac', 'tiktok_access_token',
            'ga4_api_secret', 'meta_conversions_api_token', 'smtp_pass'
        ];

        foreach ($all as $row) {
            if (!in_array($row['key_name'], $secretKeys)) {
                $public[$row['key_name']] = $row['value'];
            }
        }
        return $public;
    }
}
