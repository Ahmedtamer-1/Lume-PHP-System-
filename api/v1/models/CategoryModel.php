<?php
/**
 * Headless API Category Model
 */

class CategoryModel {
    /**
     * Get all categories sorted by sort_order
     */
    public static function getCategories(): array {
        $stmt = db()->query('SELECT * FROM categories ORDER BY sort_order ASC, name ASC');
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Get a specific category by slug
     */
    public static function getCategoryBySlug(string $slug): ?array {
        $stmt = db()->prepare('SELECT * FROM categories WHERE slug = ?');
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }
}
