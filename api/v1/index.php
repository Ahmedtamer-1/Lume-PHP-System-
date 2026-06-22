<?php
/**
 * Headless API Front Controller
 */
header('Content-Type: application/json');

// Set up error handling to always return JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    http_response_code(500);
    echo json_encode(['status' => 500, 'error' => 'Internal Server Error', 'details' => $errstr]);
    exit;
});

set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode(['status' => 500, 'error' => 'Internal Server Error', 'details' => $e->getMessage()]);
    exit;
});

require_once __DIR__ . '/../../includes/functions.php';

// Extract the requested resource
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/api/v1/';
$pos = strpos($requestUri, $basePath);

if ($pos !== false) {
    $path = substr($requestUri, $pos + strlen($basePath));
    $path = trim(parse_url($path, PHP_URL_PATH), '/');
    $parts = explode('/', $path);
    $resource = $parts[0] ?: 'index';
} else {
    $resource = 'index';
}

// Map endpoints to route files
$routes = [
    'products'   => 'catalog.php',
    'categories' => 'categories.php',
    'cart'       => 'cart.php',
    'auth'       => 'auth.php',
    'orders'     => 'orders.php',
    'shipping'   => 'shipping.php',
    'settings'   => 'settings.php',
    'content'    => 'content.php',
    'newsletter' => 'newsletter.php',
    'contact'    => 'contact.php',
];

if (array_key_exists($resource, $routes)) {
    $routeFile = __DIR__ . '/routes/' . $routes[$resource];
    if (file_exists($routeFile)) {
        require_once $routeFile;
    } else {
        http_response_code(501);
        echo json_encode(['status' => 501, 'error' => 'Endpoint not implemented yet']);
    }
} else {
    http_response_code(404);
    echo json_encode(['status' => 404, 'error' => 'Resource not found']);
}
