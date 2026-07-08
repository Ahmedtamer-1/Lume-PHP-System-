<?php
require_once __DIR__ . '/../../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'track') {
        $orderNumber = trim($_GET['order_number'] ?? '');
        $email = trim($_GET['email'] ?? '');
        
        if (!$orderNumber || !$email) {
            echo json_encode(['status' => 400, 'error' => 'Please provide both order number and email.']);
            exit;
        }

        try {
            $stmt = db()->prepare('
                SELECT o.*, u.email as user_email 
                FROM orders o 
                LEFT JOIN users u ON u.id = o.user_id 
                WHERE o.order_number = ?
            ');
            $stmt->execute([$orderNumber]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                echo json_encode(['status' => 404, 'error' => 'Order not found. Please check your order number and try again.']);
                exit;
            }

            $orderEmail = !empty($order['guest_email']) ? $order['guest_email'] : $order['user_email'];
            if (strtolower(trim((string)$orderEmail)) !== strtolower($email)) {
                echo json_encode(['status' => 403, 'error' => 'Order not found. Please check your email and try again.']);
                exit;
            }
            
            // Fetch items
            $itemsStmt = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
            $itemsStmt->execute([$order['id']]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $order['items'] = $items;
            
            echo json_encode(['status' => 200, 'data' => $order]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 500, 'error' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['status' => 400, 'error' => 'Invalid action']);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 405, 'error' => 'Method not allowed']);
}
