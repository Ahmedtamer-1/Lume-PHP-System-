<?php
require_once __DIR__ . '/../includes/functions.php';
lume_session_start();
$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) json_response(['results' => []]);
$products = get_products(['search' => $q, 'limit' => 8]);
$results = [];
foreach ($products as $p) {
    $price = !empty($p['sale_price']) ? money((float)$p['sale_price']) : money((float)$p['price']);
    $results[] = [
        'id'            => (int)$p['id'],
        'name'          => $p['name'],
        'slug'          => $p['slug'],
        'image'         => product_image($p),
        'category_name' => $p['category_name'] ?? 'Product',
        'display_price' => $price,
    ];
}
json_response(['results' => $results]);
