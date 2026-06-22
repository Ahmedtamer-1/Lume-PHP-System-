<?php
/**
 * Shipping API Route
 */
require_once __DIR__ . '/../models/ShippingModel.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $zones = ShippingModel::getZones();
    echo json_encode(['status' => 200, 'data' => $zones]);
} else {
    http_response_code(405);
    echo json_encode(['status' => 405, 'error' => 'Method Not Allowed']);
}
