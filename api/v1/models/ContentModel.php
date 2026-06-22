<?php
/**
 * Headless API Content Model
 */

class ContentModel {
    /**
     * Get active homepage sections
     */
    public static function getHomepageSections(): array {
        $stmt = db()->query('SELECT * FROM homepage_sections WHERE is_active = 1 ORDER BY sort_order ASC');
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Get media library items
     */
    public static function getMedia(int $limit = 50, int $offset = 0): array {
        $stmt = db()->prepare('SELECT * FROM media_library ORDER BY created_at DESC LIMIT ? OFFSET ?');
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll() ?: [];
    }
}
