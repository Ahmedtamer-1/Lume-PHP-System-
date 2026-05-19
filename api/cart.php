<?php
require_once __DIR__ . '/../includes/functions.php';
lume_session_start();

// GET — return cart contents
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'get';
    if ($action === 'get') {
        $items = cart_items();
        $out = [];
        foreach ($items as $i) {
            $p = item_effective_price($i);
            $img = !empty($i['variant_image']) ? SITE_URL . '/' . ltrim(h($i['variant_image']), '/') : product_image($i);
            $out[] = [
                'id'            => (int)$i['id'],
                'product_id'    => (int)$i['product_id'],
                'name'          => $i['name'],
                'slug'          => $i['slug'],
                'image'         => $img,
                'quantity'      => (int)$i['quantity'],
                'display_price' => money($p * (int)$i['quantity']),
                'variant_size'  => $i['variant_size'] ?? null,
                'variant_color' => $i['variant_color'] ?? null,
            ];
        }
        json_response(['items' => $out, 'count' => cart_count(), 'total_display' => money(cart_total())]);
    }
}

// POST — add / update / remove
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    // Non-AJAX form submissions (from cart page)
    $isAjax = is_ajax();

    if (!csrf_verify($_POST['csrf'] ?? '')) {
        if ($isAjax) json_response(['success' => false, 'message' => 'Invalid CSRF token'], 400);
        else redirect('/cart.php');
    }

    if ($action === 'add') {
        $pid = (int)($_POST['product_id'] ?? 0);
        $qty = max(1, (int)($_POST['quantity'] ?? 1));
        $vid = (int)($_POST['variant_id'] ?? 0) ?: null;
        if ($pid <= 0) { if ($isAjax) json_response(['success'=>false,'message'=>'Invalid product'],400); else redirect('/cart.php'); }
        
        $product = db()->prepare('SELECT has_variants FROM products WHERE id = ?');
        $product->execute([$pid]);
        $prodData = $product->fetch();

        if ($prodData && !empty($prodData['has_variants']) && !$vid) {
            if ($isAjax) json_response(['success' => false, 'message' => 'Please select a size/color.'], 400); else redirect('/cart.php');
        }
        
        cart_add($pid, $qty, $vid);
        if ($isAjax) json_response(['success'=>true,'count'=>cart_count()]);
        redirect('/cart.php');
    }

    if ($action === 'update') {
        $cid = (int)($_POST['cart_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 1);
        cart_update($cid, $qty);
        if ($isAjax) json_response(['success'=>true,'count'=>cart_count()]);
        redirect('/cart.php');
    }

    if ($action === 'remove') {
        $cid = (int)($_POST['cart_id'] ?? 0);
        cart_update($cid, 0);
        if ($isAjax) json_response(['success'=>true,'count'=>cart_count()]);
        redirect('/cart.php');
    }
}

json_response(['error'=>'Bad request'], 400);
