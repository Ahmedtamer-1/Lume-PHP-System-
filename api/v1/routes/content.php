<?php
/**
 * Content API Route
 */
require_once __DIR__ . '/../models/ContentModel.php';

$method = $_SERVER['REQUEST_METHOD'];
$type = $_GET['type'] ?? 'homepage';

if ($method === 'GET') {
    if ($type === 'homepage') {
        $sections = ContentModel::getHomepageSections();
        echo json_encode(['status' => 200, 'data' => $sections]);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 400, 'error' => 'Invalid content type']);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 405, 'error' => 'Method Not Allowed']);
}
