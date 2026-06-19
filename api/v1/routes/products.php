<?php
require_once __DIR__ . '/../models/Product.php';

$method = $_SERVER['REQUEST_METHOD'];
$productModel = new Product(db());

if ($method === 'GET') {
    if ($id) {
        $product = $productModel->getById($id);
        if ($product) {
            echo json_encode(['status' => 200, 'data' => $product]);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 404, 'error' => 'Product not found']);
        }
    } else {
        $products = $productModel->getAll();
        echo json_encode(['status' => 200, 'data' => $products]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 405, 'error' => 'Method not allowed']);
}
