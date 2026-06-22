<?php
/**
 * Headless API Product Model
 */

class ProductModel {
    /**
     * Get products matching options
     */
    public static function getProducts(array $opts = []): array {
        $where = ['p.is_active = 1'];
        $params = [];
        $limit = (int) ($opts['limit'] ?? 12);
        $offset = (int) ($opts['offset'] ?? 0);

        if (!empty($opts['category_slug'])) {
            $where[] = 'c.slug = ?';
            $params[] = $opts['category_slug'];
        }
        if (!empty($opts['category_id'])) {
            $where[] = 'c.id = ?';
            $params[] = (int)$opts['category_id'];
        }
        if (!empty($opts['exclude_id'])) {
            $where[] = 'p.id != ?';
            $params[] = (int)$opts['exclude_id'];
        }
        if (!empty($opts['search'])) {
            $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
            $term = '%' . $opts['search'] . '%';
            $params[] = $term;
            $params[] = $term;
        }

        $sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Get a single product by slug
     */
    public static function getProductBySlug(string $slug): ?array {
        $stmt = db()->prepare(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.slug = ? AND p.is_active = 1'
        );
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get a single product by ID
     */
    public static function getProductById(int $id): ?array {
        $stmt = db()->prepare(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.id = ? AND p.is_active = 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get product color swatches mapping
     */
    public static function getProductColorSwatches(array $productIds): array {
        if (empty($productIds)) return [];
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        
        $stmt = db()->prepare(
            "SELECT product_id, color_name, color_hex, image 
             FROM product_variants 
             WHERE product_id IN ($placeholders) AND is_active = 1 
               AND color_name IS NOT NULL AND color_hex IS NOT NULL 
             GROUP BY product_id, color_name, color_hex, image
             ORDER BY sort_order ASC"
        );
        $stmt->execute($productIds);
        
        $colors = [];
        foreach ($stmt->fetchAll() as $row) {
            $pid = (int)$row['product_id'];
            if (!isset($colors[$pid])) {
                $colors[$pid] = [];
            }
            $colors[$pid][$row['color_name']] = [
                'hex'   => $row['color_hex'],
                'image' => $row['image']
            ];
        }
        return $colors;
    }

    /**
     * Get variants for a product
     */
    public static function getVariants(int $productId): array {
        $stmt = db()->prepare('SELECT * FROM product_variants WHERE product_id = ? AND is_active = 1 ORDER BY sort_order ASC');
        $stmt->execute([$productId]);
        return $stmt->fetchAll() ?: [];
    }
}
