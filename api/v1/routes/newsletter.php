<?php
/**
 * Newsletter API Route
 */
require_once __DIR__ . '/../models/MarketingModel.php';
require_once __DIR__ . '/../middleware/Middleware.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    Middleware::requireCsrf();
    
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'error' => 'Please enter a valid email address.']);
        exit;
    }
    
    $source = $_POST['source'] ?? 'footer';
    $result = MarketingModel::subscribeNewsletter($email, $source);
    
    if ($result['success']) {
        echo json_encode(['status' => 200, 'data' => ['success' => true, 'message' => $result['message']]]);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 400, 'error' => $result['message']]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 405, 'error' => 'Method Not Allowed']);
}
