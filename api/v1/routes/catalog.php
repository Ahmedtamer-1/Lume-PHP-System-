<?php
/**
 * Catalog API Route (Products)
 */
require_once __DIR__ . '/../models/ProductModel.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Check if a specific slug is requested (e.g. ?slug=...)
    $slug = $_GET['slug'] ?? null;
    
    if ($slug) {
        $product = ProductModel::getProductBySlug($slug);
        if ($product) {
            $product['variants'] = ProductModel::getVariants((int)$product['id']);
            echo json_encode(['status' => 200, 'data' => $product]);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 404, 'error' => 'Product not found']);
        }
    } else {
        $opts = [
            'limit' => (int)($_GET['limit'] ?? 24),
            'offset' => (int)($_GET['offset'] ?? 0),
            'category_slug' => $_GET['category'] ?? null,
            'search' => $_GET['q'] ?? null,
        ];
        
        $products = ProductModel::getProducts($opts);
        $productIds = array_column($products, 'id');
        $colors = ProductModel::getProductColorSwatches($productIds);
        
        // Attach color swatches to products
        foreach ($products as &$p) {
            $p['color_swatches'] = $colors[$p['id']] ?? [];
        }
        
        echo json_encode(['status' => 200, 'data' => $products]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 405, 'error' => 'Method Not Allowed']);
}
