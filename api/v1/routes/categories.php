<?php
/**
 * Categories API Route
 */
require_once __DIR__ . '/../models/CategoryModel.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $slug = $_GET['slug'] ?? null;
    
    if ($slug) {
        $category = CategoryModel::getCategoryBySlug($slug);
        if ($category) {
            echo json_encode(['status' => 200, 'data' => $category]);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 404, 'error' => 'Category not found']);
        }
    } else {
        $categories = CategoryModel::getCategories();
        echo json_encode(['status' => 200, 'data' => $categories]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 405, 'error' => 'Method Not Allowed']);
}
