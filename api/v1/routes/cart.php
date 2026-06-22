<?php
/**
 * Cart API Route
 */
require_once __DIR__ . '/../models/CartModel.php';
require_once __DIR__ . '/../middleware/Middleware.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($method === 'GET') {
    $items = CartModel::getItems();
    $out = [];
    foreach ($items as $i) {
        $price = !empty($i['variant_price']) ? (float)$i['variant_price'] : 
                 (!empty($i['sale_price']) ? (float)$i['sale_price'] : (float)$i['price']);
        
        $img = !empty($i['variant_image']) ? SITE_URL . '/' . ltrim($i['variant_image'], '/') : thumb_url($i['image']);
        
        $out[] = [
            'id'            => (int)$i['cart_id'],
            'product_id'    => (int)$i['product_id'],
            'name'          => $i['name'],
            'slug'          => $i['slug'],
            'image'         => $img,
            'quantity'      => (int)$i['quantity'],
            'display_price' => money($price * (int)$i['quantity']),
            'variant_size'  => $i['variant_size'],
            'variant_color' => $i['variant_color'],
        ];
    }
    
    $summary = CartModel::getSummary();
    echo json_encode([
        'status' => 200, 
        'data' => [
            'items' => $out, 
            'count' => $summary['count'], 
            'total_display' => $summary['total_display']
        ]
    ]);
} 
elseif ($method === 'POST') {
    Middleware::requireCsrf();
    
    if ($action === 'add') {
        $pid = (int)($_POST['product_id'] ?? 0);
        $qty = max(1, (int)($_POST['quantity'] ?? 1));
        $vid = (int)($_POST['variant_id'] ?? 0) ?: null;
        
        $result = CartModel::add($pid, $qty, $vid);
        
        if ($result['success']) {
            $summary = CartModel::getSummary();
            echo json_encode(['status' => 200, 'data' => ['success' => true, 'count' => $summary['count']]]);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => $result['message']]);
        }
    } 
    elseif ($action === 'update') {
        $cid = (int)($_POST['cart_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 1);
        
        $result = CartModel::update($cid, $qty);
        
        if ($result['success']) {
            $summary = CartModel::getSummary();
            echo json_encode(['status' => 200, 'data' => ['success' => true, 'count' => $summary['count']]]);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => $result['message'], 'max_stock' => $result['max_stock'] ?? 0]);
        }
    }
    elseif ($action === 'remove') {
        $cid = (int)($_POST['cart_id'] ?? 0);
        CartModel::update($cid, 0);
        $summary = CartModel::getSummary();
        echo json_encode(['status' => 200, 'data' => ['success' => true, 'count' => $summary['count']]]);
    }
    else {
        http_response_code(400);
        echo json_encode(['status' => 400, 'error' => 'Invalid action']);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 405, 'error' => 'Method Not Allowed']);
}
