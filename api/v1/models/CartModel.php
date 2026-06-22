<?php
/**
 * Headless API Cart Model
 */

class CartModel {
    /**
     * Get the current session key for the cart
     */
    public static function getSessionKey(): string {
        lume_session_start();
        if (empty($_SESSION['cart_session_key'])) {
            $_SESSION['cart_session_key'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['cart_session_key'];
    }

    /**
     * Merge guest cart into user cart upon login
     */
    public static function mergeGuestCart(int $userId): void {
        $key = self::getSessionKey();
        $stmt = db()->prepare('SELECT id, product_id, variant_id, quantity FROM cart_sessions WHERE session_key = ? AND user_id IS NULL');
        $stmt->execute([$key]);
        $guestItems = $stmt->fetchAll();

        foreach ($guestItems as $item) {
            // Check if user already has this item
            if ($item['variant_id']) {
                $check = db()->prepare('SELECT id, quantity FROM cart_sessions WHERE user_id = ? AND product_id = ? AND variant_id = ?');
                $check->execute([$userId, $item['product_id'], $item['variant_id']]);
            } else {
                $check = db()->prepare('SELECT id, quantity FROM cart_sessions WHERE user_id = ? AND product_id = ? AND variant_id IS NULL');
                $check->execute([$userId, $item['product_id']]);
            }
            $existing = $check->fetch();

            if ($existing) {
                // Add quantities
                db()->prepare('UPDATE cart_sessions SET quantity = quantity + ? WHERE id = ?')
                    ->execute([$item['quantity'], $existing['id']]);
                // Delete guest row
                db()->prepare('DELETE FROM cart_sessions WHERE id = ?')->execute([$item['id']]);
            } else {
                // Reassign guest row to user
                db()->prepare('UPDATE cart_sessions SET user_id = ? WHERE id = ?')
                    ->execute([$userId, $item['id']]);
            }
        }
    }

    /**
     * Get all cart items
     */
    public static function getItems(): array {
        $key = self::getSessionKey();
        $userId = is_logged_in() ? $_SESSION['user_id'] : null;

        $cols = 'cs.id AS cart_id, cs.quantity, cs.product_id, cs.variant_id, 
                 p.name, p.price, p.sale_price, p.cost_price, p.image, p.slug, p.stock, p.has_variants,
                 pv.size AS variant_size, pv.color_name AS variant_color,
                 pv.color_hex AS variant_color_hex, pv.sku AS variant_sku,
                 pv.price_override AS variant_price, pv.stock AS variant_stock,
                 pv.image AS variant_image';

        if ($userId) {
            $stmt = db()->prepare(
                "SELECT $cols
                 FROM cart_sessions cs
                 JOIN products p ON p.id = cs.product_id
                 LEFT JOIN product_variants pv ON pv.id = cs.variant_id
                 WHERE cs.user_id = ? AND p.is_active = 1"
            );
            $stmt->execute([$userId]);
        } else {
            $stmt = db()->prepare(
                "SELECT $cols
                 FROM cart_sessions cs
                 JOIN products p ON p.id = cs.product_id
                 LEFT JOIN product_variants pv ON pv.id = cs.variant_id
                 WHERE cs.session_key = ? AND cs.user_id IS NULL AND p.is_active = 1"
            );
            $stmt->execute([$key]);
        }
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Add to cart with stock validation
     */
    public static function add(int $productId, int $qty, ?int $variantId = null): array {
        if ($qty <= 0) return ['success' => false, 'message' => 'Invalid quantity'];

        $product = db()->prepare('SELECT name, has_variants, stock FROM products WHERE id = ? AND is_active = 1');
        $product->execute([$productId]);
        $prodData = $product->fetch();

        if (!$prodData) {
            return ['success' => false, 'message' => 'Product not found'];
        }

        $maxStock = 0;
        if (!empty($prodData['has_variants'])) {
            if (!$variantId) {
                return ['success' => false, 'message' => 'Please select a size/color'];
            }
            $vStmt = db()->prepare('SELECT stock FROM product_variants WHERE id = ? AND is_active = 1');
            $vStmt->execute([$variantId]);
            $variantStock = $vStmt->fetchColumn();
            if ($variantStock === false) {
                return ['success' => false, 'message' => 'Variant not found'];
            }
            $maxStock = (int)$variantStock;
        } else {
            $maxStock = (int)$prodData['stock'];
        }

        // Get current cart quantity for this specific product/variant
        $currentCartQty = 0;
        $items = self::getItems();
        foreach ($items as $i) {
            if ($i['product_id'] == $productId && $i['variant_id'] == $variantId) {
                $currentCartQty += (int)$i['quantity'];
                break;
            }
        }

        if ($currentCartQty + $qty > $maxStock) {
            return ['success' => false, 'message' => "Only $maxStock available in stock."];
        }

        // Safe to add
        $key = self::getSessionKey();
        $userId = is_logged_in() ? (int) $_SESSION['user_id'] : null;

        if ($userId) {
            if ($variantId) {
                $stmt = db()->prepare('SELECT id FROM cart_sessions WHERE user_id = ? AND product_id = ? AND variant_id = ?');
                $stmt->execute([$userId, $productId, $variantId]);
            } else {
                $stmt = db()->prepare('SELECT id FROM cart_sessions WHERE user_id = ? AND product_id = ? AND variant_id IS NULL');
                $stmt->execute([$userId, $productId]);
            }
        } else {
            if ($variantId) {
                $stmt = db()->prepare('SELECT id FROM cart_sessions WHERE session_key = ? AND user_id IS NULL AND product_id = ? AND variant_id = ?');
                $stmt->execute([$key, $productId, $variantId]);
            } else {
                $stmt = db()->prepare('SELECT id FROM cart_sessions WHERE session_key = ? AND user_id IS NULL AND product_id = ? AND variant_id IS NULL');
                $stmt->execute([$key, $productId]);
            }
        }
        $existing = $stmt->fetch();

        if ($existing) {
            db()->prepare('UPDATE cart_sessions SET quantity = quantity + ? WHERE id = ?')
                ->execute([$qty, $existing['id']]);
        } else {
            db()->prepare('INSERT INTO cart_sessions (session_key, user_id, product_id, variant_id, quantity) VALUES (?,?,?,?,?)')
                ->execute([$key, $userId, $productId, $variantId, $qty]);
        }
        
        return ['success' => true];
    }

    /**
     * Update quantity of a cart item
     */
    public static function update(int $cartId, int $qty): array {
        if ($qty <= 0) {
            db()->prepare('DELETE FROM cart_sessions WHERE id = ?')->execute([$cartId]);
            return ['success' => true];
        }

        // Validate stock
        $items = self::getItems();
        $cartItem = null;
        foreach ($items as $i) {
            if ($i['cart_id'] == $cartId) {
                $cartItem = $i;
                break;
            }
        }

        if (!$cartItem) {
            return ['success' => false, 'message' => 'Cart item not found'];
        }

        $maxStock = !empty($cartItem['has_variants']) ? (int)$cartItem['variant_stock'] : (int)$cartItem['stock'];
        if ($qty > $maxStock) {
            return ['success' => false, 'message' => "Only $maxStock available in stock.", 'max_stock' => $maxStock];
        }

        db()->prepare('UPDATE cart_sessions SET quantity = ? WHERE id = ?')->execute([$qty, $cartId]);
        return ['success' => true];
    }

    /**
     * Clear the cart
     */
    public static function clear(): void {
        $key = self::getSessionKey();
        $userId = is_logged_in() ? (int) $_SESSION['user_id'] : null;
        if ($userId) {
            db()->prepare('DELETE FROM cart_sessions WHERE user_id = ?')->execute([$userId]);
        } else {
            db()->prepare('DELETE FROM cart_sessions WHERE session_key = ? AND user_id IS NULL')->execute([$key]);
        }
    }
    
    /**
     * Calculate summary
     */
    public static function getSummary(): array {
        $items = self::getItems();
        $count = 0;
        $total = 0.0;
        foreach ($items as $item) {
            $count += (int)$item['quantity'];
            $price = !empty($item['variant_price']) ? (float)$item['variant_price'] : 
                     (!empty($item['sale_price']) ? (float)$item['sale_price'] : (float)$item['price']);
            $total += $price * (int)$item['quantity'];
        }
        return [
            'count' => $count,
            'total' => $total,
            'total_display' => money($total)
        ];
    }
}
