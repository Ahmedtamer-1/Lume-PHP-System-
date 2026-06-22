<?php
/**
 * Auth API Route
 */
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../middleware/Middleware.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($method === 'POST') {
    Middleware::requireCsrf();

    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        
        if (!$email || !$pass) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'Email and password are required.']);
            exit;
        }

        $result = UserModel::login($email, $pass);
        if ($result['success']) {
            echo json_encode(['status' => 200, 'data' => ['success' => true, 'user_id' => $result['user_id']]]);
        } else {
            http_response_code(401);
            echo json_encode(['status' => 401, 'error' => $result['message']]);
        }
    } 
    elseif ($action === 'register') {
        $fn    = trim($_POST['first_name'] ?? '');
        $ln    = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $pass2 = $_POST['password_confirm'] ?? '';

        if (!$fn || !$ln || !$email || !$pass) { 
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'All fields are required.']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { 
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'Invalid email.']);
            exit;
        }
        if (strlen($pass) < 6) { 
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'Password must be at least 6 characters.']);
            exit;
        }
        if ($pass !== $pass2) { 
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'Passwords do not match.']);
            exit;
        }

        $result = UserModel::register($fn, $ln, $email, $pass);
        if ($result['success']) {
            echo json_encode(['status' => 200, 'data' => ['success' => true, 'user_id' => $result['user_id']]]);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => $result['message']]);
        }
    } 
    elseif ($action === 'logout') {
        UserModel::logout();
        echo json_encode(['status' => 200, 'data' => ['success' => true]]);
    }
    else {
        http_response_code(400);
        echo json_encode(['status' => 400, 'error' => 'Invalid action']);
    }
} 
elseif ($method === 'GET') {
    if ($action === 'me') {
        Middleware::requireCustomer();
        $user = UserModel::getUserById($_SESSION['user_id']);
        if ($user) {
            echo json_encode(['status' => 200, 'data' => $user]);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 404, 'error' => 'User not found']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['status' => 400, 'error' => 'Invalid action']);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 405, 'error' => 'Method Not Allowed']);
}
