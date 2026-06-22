<?php
/**
 * Contact API Route
 */
require_once __DIR__ . '/../models/MarketingModel.php';
require_once __DIR__ . '/../middleware/Middleware.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    Middleware::requireCsrf();
    
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (!$name || !$email || !$subject || !$message) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'error' => 'All fields are required.']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'error' => 'Please enter a valid email address.']);
        exit;
    }
    
    $result = MarketingModel::submitContact($name, $email, $subject, $message);
    
    if ($result['success']) {
        echo json_encode(['status' => 200, 'data' => ['success' => true, 'message' => $result['message']]]);
    } else {
        http_response_code(429);
        echo json_encode(['status' => 429, 'error' => $result['message']]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 405, 'error' => 'Method Not Allowed']);
}
