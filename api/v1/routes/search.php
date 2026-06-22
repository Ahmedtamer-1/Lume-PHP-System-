<?php
/**
 * Search API Route
 */
require_once __DIR__ . '/../models/ProductModel.php';

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$products = ProductModel::getProducts(['search' => $q, 'limit' => 8]);
$results = [];
foreach ($products as $p) {
    $price = !empty($p['sale_price']) ? money((float)$p['sale_price']) : money((float)$p['price']);
    $image = !empty($p['image']) ? $p['image'] : (!empty($p['gallery']) ? (json_decode($p['gallery'], true)[0] ?? '') : '');
    
    $results[] = [
        'id'            => (int)$p['id'],
        'name'          => $p['name'],
        'slug'          => $p['slug'],
        'image'         => $image,
        'category_name' => $p['category_name'] ?? 'Product',
        'display_price' => $price,
    ];
}

echo json_encode(['results' => $results]);
