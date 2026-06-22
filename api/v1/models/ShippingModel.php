<?php
/**
 * Headless API Shipping Model
 */

class ShippingModel {
    /**
     * Get active shipping zones
     */
    public static function getZones(): array {
        $stmt = db()->query('SELECT id, name, cost FROM shipping_zones WHERE is_active = 1 ORDER BY sort_order ASC, name ASC');
        return $stmt->fetchAll() ?: [];
    }
}
