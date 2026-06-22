<?php
/**
 * Headless API Middleware
 */

class Middleware {
    /**
     * Ensure the request is POST and valid CSRF
     */
    public static function requireCsrf() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            return; // Only enforce CSRF on state-changing methods
        }
        
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf'] ?? '';
        if (!csrf_verify($token)) {
            http_response_code(403);
            echo json_encode(['status' => 403, 'error' => 'Invalid CSRF token']);
            exit;
        }
    }

    /**
     * Require authentication for customer endpoints
     */
    public static function requireCustomer() {
        lume_session_start();
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['status' => 401, 'error' => 'Unauthorized. Please log in.']);
            exit;
        }
    }

    /**
     * Require authentication for admin endpoints
     */
    public static function requireAdmin() {
        lume_session_start();
        if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['status' => 403, 'error' => 'Forbidden. Admin access required.']);
            exit;
        }
    }

    /**
     * Rate limiting wrapper
     */
    public static function enforceRateLimit(string $action, int $maxAttempts = 5, int $windowSeconds = 900) {
        $identifier = $action . '|' . get_client_ip();
        if (is_rate_limited($identifier, $maxAttempts, $windowSeconds)) {
            http_response_code(429);
            echo json_encode(['status' => 429, 'error' => 'Too many requests. Please try again later.']);
            exit;
        }
        record_login_attempt($identifier, false); // Register an attempt
    }

    /**
     * Clear rate limit on success
     */
    public static function clearRateLimit(string $action) {
        $identifier = $action . '|' . get_client_ip();
        clear_login_attempts($identifier);
    }
}
