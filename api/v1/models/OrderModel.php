<?php
/**
 * Headless API Order Model
 */
require_once __DIR__ . '/CartModel.php';

class OrderModel {
    /**
     * Create a new order
     */
    public static function createOrder(array $data): int {
        $orderNum = self::generateOrderNumber();
        
        db()->prepare(
            'INSERT INTO orders 
            (order_number, user_id, guest_email, subtotal, shipping_cost, discount, tax, total, 
             payment_method, shipping_name, shipping_addr, shipping_city, shipping_country, 
             shipping_zone, notes, phone, status) 
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            $orderNum,
            $data['user_id'] ?? null,
            $data['guest_email'] ?? null,
            $data['subtotal'] ?? 0,
            $data['shipping_cost'] ?? 0,
            $data['discount'] ?? 0,
            $data['tax'] ?? 0,
            $data['total'] ?? 0,
            $data['payment_method'] ?? 'cod',
            $data['shipping_name'],
            $data['shipping_addr'],
            $data['shipping_city'],
            $data['shipping_country'],
            $data['shipping_zone'],
            $data['notes'] ?? '',
            $data['phone'] ?? '',
            $data['payment_method'] === 'online' ? 'pending' : 'processing'
        ]);

        $orderId = (int)db()->lastInsertId();

        // Insert items and deduct stock
        $stmtItem = db()->prepare(
            'INSERT INTO order_items 
            (order_id, product_id, variant_id, variant_size, variant_color, name, sku, price, cost_price, quantity, subtotal) 
            VALUES (?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmtUpdateProduct = db()->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
        $stmtUpdateVariant = db()->prepare('UPDATE product_variants SET stock = stock - ? WHERE id = ?');

        foreach ($data['items'] as $item) {
            $qty = (int)$item['quantity'];
            $price = (float)$item['price'];
            $sub = $price * $qty;
            
            $stmtItem->execute([
                $orderId,
                $item['product_id'],
                $item['variant_id'] ?? null,
                $item['variant_size'] ?? null,
                $item['variant_color'] ?? null,
                $item['name'],
                $item['sku'] ?? null,
                $price,
                $item['cost_price'] ?? null,
                $qty,
                $sub
            ]);

            // Deduct stock
            if (!empty($item['variant_id'])) {
                $stmtUpdateVariant->execute([$qty, $item['variant_id']]);
            } else {
                $stmtUpdateProduct->execute([$qty, $item['product_id']]);
            }
        // Trigger Tracking Integrations async-style or directly
        // We do this immediately after successful insertion.
        require_once __DIR__ . '/../../integrations/meta/MetaIntegration.php';
        require_once __DIR__ . '/../../integrations/tiktok/TiktokIntegration.php';
        require_once __DIR__ . '/../../integrations/google/GoogleIntegration.php';
        
        try {
            MetaIntegration::sendPurchaseEvent($orderId, $data, $data['items']);
        } catch (Throwable $t) { error_log('Meta CAPI Error: ' . $t->getMessage()); }
        
        try {
            TiktokIntegration::sendPurchaseEvent($orderId, $data, $data['items']);
        } catch (Throwable $t) { error_log('TikTok CAPI Error: ' . $t->getMessage()); }
        
        try {
            GoogleIntegration::sendPurchaseEvent($orderId, $data, $data['items']);
        } catch (Throwable $t) { error_log('Google GA4 Error: ' . $t->getMessage()); }

        return $orderId;
    }

    /**
     * Generate unique order number
     */
    private static function generateOrderNumber(): string {
        $prefix = setting('order_prefix', 'LUME-');
        $stmt = db()->query('SELECT MAX(id) FROM orders');
        $maxId = (int)$stmt->fetchColumn();
        return $prefix . str_pad((string)($maxId + 1), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Cancel an order and restock
     */
    public static function cancelOrder(int $orderId): void {
        $stmt = db()->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if (!$order || $order['status'] === 'cancelled') return;

        // Restore stock
        $items = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $items->execute([$orderId]);
        foreach ($items->fetchAll() as $item) {
            if ($item['variant_id']) {
                db()->prepare('UPDATE product_variants SET stock = stock + ? WHERE id = ?')
                    ->execute([$item['quantity'], $item['variant_id']]);
            } else {
                db()->prepare('UPDATE products SET stock = stock + ? WHERE id = ?')
                    ->execute([$item['quantity'], $item['product_id']]);
            }
        }

        // Mark cancelled
        db()->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$orderId]);
    }

    /**
     * Get order by ID
     */
    public static function getOrderById(int $orderId): ?array {
        $stmt = db()->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if (!$order) return null;
        
        $items = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $items->execute([$orderId]);
        $order['items'] = $items->fetchAll();
        return $order;
    }

    /**
     * Get order by Number
     */
    public static function getOrderByNumber(string $orderNumber): ?array {
        $stmt = db()->prepare('
            SELECT o.*, u.email as user_email 
            FROM orders o 
            LEFT JOIN users u ON u.id = o.user_id 
            WHERE o.order_number = ?
        ');
        $stmt->execute([$orderNumber]);
        $order = $stmt->fetch();
        if (!$order) return null;
        
        $items = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $items->execute([$order['id']]);
        $order['items'] = $items->fetchAll();
        return $order;
    }
}
