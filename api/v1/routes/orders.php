<?php
/**
 * Orders API Route
 */
require_once __DIR__ . '/../models/OrderModel.php';
require_once __DIR__ . '/../models/CartModel.php';
require_once __DIR__ . '/../middleware/Middleware.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($method === 'POST') {
    Middleware::requireCsrf();

    if ($action === 'create') {
        // Enforce rate limits to prevent spam checkout
        Middleware::enforceRateLimit('checkout', 10, 3600);

        $name    = trim($_POST['shipping_name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $addr    = trim($_POST['address'] ?? '');
        $zoneId  = (int)($_POST['shipping_zone'] ?? 0);
        $country = trim($_POST['country'] ?? 'Egypt');
        $notes   = trim($_POST['notes'] ?? '');
        $paymentMethod = $_POST['payment_method'] ?? 'online';

        // Load cart
        $items = CartModel::getItems();
        if (empty($items)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'Cart is empty.']);
            exit;
        }

        if (!$name || !$email || !$phone || !$addr || !$zoneId) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'Please fill in all required fields.']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'Please enter a valid email.']);
            exit;
        }

        $codEnabled = setting('cod_enabled', '1') === '1';
        if ($paymentMethod === 'cod' && !$codEnabled) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'Cash on delivery is not available.']);
            exit;
        }

        // Zones
        $zones = db()->query('SELECT id, name, cost FROM shipping_zones WHERE is_active = 1')->fetchAll();
        $selectedZone = null;
        foreach ($zones as $z) {
            if ($z['id'] == $zoneId) { $selectedZone = $z; break; }
        }
        if (!$selectedZone) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'Invalid shipping zone selected.']);
            exit;
        }

        // Inventory check
        $stockError = null;
        foreach ($items as $i) {
            $reqQty = (int)$i['quantity'];
            $stock = !empty($i['has_variants']) ? (int)$i['variant_stock'] : (int)$i['stock'];
            if ($stock < $reqQty) {
                $stockError = "Sorry, we don't have enough stock for " . $i['name'] . ".";
                break;
            }
        }
        if ($stockError) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => $stockError]);
            exit;
        }

        // Totals
        $subtotal = 0;
        $orderItems = [];
        foreach ($items as $i) {
            $price = !empty($i['variant_price']) ? (float)$i['variant_price'] : 
                     (!empty($i['sale_price']) ? (float)$i['sale_price'] : (float)$i['price']);
            $cost = $i['variant_cost_price'] ?? $i['cost_price'] ?? null;
            $qty = (int)$i['quantity'];
            $subtotal += $price * $qty;
            
            $orderItems[] = [
                'product_id'    => $i['product_id'],
                'variant_id'    => $i['variant_id'] ?? null,
                'variant_size'  => $i['variant_size'] ?? null,
                'variant_color' => $i['variant_color'] ?? null,
                'name'          => $i['name'],
                'sku'           => $i['variant_sku'] ?? $i['sku'] ?? null,
                'price'         => $price,
                'cost_price'    => $cost,
                'quantity'      => $qty,
            ];
        }

        $freeShippingOver = (float)setting('free_shipping_over', '2000');
        $shipping = ($freeShippingOver > 0 && $subtotal >= $freeShippingOver) ? 0 : (float)$selectedZone['cost'];
        $total = $subtotal + $shipping;
        
        if ($paymentMethod === 'cod') {
            $total += (float)setting('cod_extra_fee', '0');
        }

        lume_session_start();
        $userId = is_logged_in() ? $_SESSION['user_id'] : null;

        $orderId = OrderModel::createOrder([
            'user_id'        => $userId,
            'guest_email'    => $userId ? null : $email,
            'subtotal'       => $subtotal,
            'shipping_cost'  => $shipping,
            'total'          => $total,
            'payment_method' => $paymentMethod,
            'shipping_name'  => $name,
            'shipping_addr'  => $addr,
            'shipping_city'  => $selectedZone['name'],
            'shipping_country' => $country,
            'shipping_zone'  => $selectedZone['name'],
            'notes'          => $notes,
            'phone'          => $phone,
            'items'          => $orderItems,
        ]);

        if ($paymentMethod === 'online') {
            // Cancel stale pending online orders
            $stalePendingId = $_SESSION['paymob_pending_order'] ?? null;
            if ($stalePendingId) {
                OrderModel::cancelOrder((int)$stalePendingId);
            }

            try {
                require_once __DIR__ . '/../../includes/paymob.php';
                $names = explode(' ', $name, 2);
                $billingData = [
                    'first_name' => $names[0] ?? 'NA',
                    'last_name' => $names[1] ?? 'NA',
                    'email' => $email,
                    'phone_number' => $phone,
                    'street' => $addr,
                    'city' => $selectedZone['name'],
                ];
                
                $iframeUrl = paymob_generate_iframe_url($orderId, $total * 100, $billingData);
                
                CartModel::clear();
                $_SESSION['paymob_pending_order'] = $orderId;
                Middleware::clearRateLimit('checkout');
                
                echo json_encode(['status' => 200, 'data' => [
                    'success' => true,
                    'order_id' => $orderId,
                    'redirect_url' => $iframeUrl
                ]]);
            } catch (Exception $e) {
                OrderModel::cancelOrder($orderId);
                http_response_code(500);
                echo json_encode(['status' => 500, 'error' => 'Payment initiation failed: ' . $e->getMessage()]);
            }
        } else {
            CartModel::clear();
            require_once __DIR__ . '/../../includes/mailer.php';
            send_order_email('confirmation', $orderId);
            $_SESSION['order_success'] = $orderId;
            Middleware::clearRateLimit('checkout');
            
            echo json_encode(['status' => 200, 'data' => [
                'success' => true,
                'order_id' => $orderId,
                'redirect_url' => '/checkout.php?success=1'
            ]]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['status' => 400, 'error' => 'Invalid action']);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 405, 'error' => 'Method Not Allowed']);
}
