<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/api/v1/models/Product.php';

try {
    $db = db();
    $model = new Product($db);
    $products = $model->getAll(null);
    echo "SUCCESS: Found " . count($products) . " products.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
