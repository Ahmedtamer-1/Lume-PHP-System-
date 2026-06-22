<?php
/**
 * Headless API User Model
 */
require_once __DIR__ . '/CartModel.php';

class UserModel {
    /**
     * Get user by ID
     */
    public static function getUserById(int $id): ?array {
        $stmt = db()->prepare('SELECT id, first_name, last_name, email, role FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get user by Email
     */
    public static function getUserByEmail(string $email): ?array {
        $stmt = db()->prepare('SELECT id, password_hash, role FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Login user
     */
    public static function login(string $email, string $password): array {
        if (is_rate_limited($email)) {
            return ['success' => false, 'message' => 'Too many login attempts. Please wait 15 minutes.'];
        }

        $user = self::getUserByEmail($email);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            clear_login_attempts($email);
            record_login_attempt($email, true);
            self::setSession((int)$user['id'], $user['role']);
            return ['success' => true, 'user_id' => $user['id']];
        } else {
            record_login_attempt($email, false);
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }
    }

    /**
     * Register user
     */
    public static function register(string $firstName, string $lastName, string $email, string $password): array {
        $exists = self::getUserByEmail($email);
        if ($exists) {
            return ['success' => false, 'message' => 'An account with this email already exists.'];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        db()->prepare('INSERT INTO users (first_name, last_name, email, password_hash) VALUES (?,?,?,?)')
            ->execute([$firstName, $lastName, $email, $hash]);
            
        $userId = (int)db()->lastInsertId();
        self::setSession($userId, 'customer'); // Default role
        return ['success' => true, 'user_id' => $userId];
    }
    
    /**
     * Handle Google Login
     */
    public static function googleLogin(array $googleUser): array {
        $email = $googleUser['email'];
        $googleId = $googleUser['id'];
        $firstName = $googleUser['given_name'] ?? 'User';
        $lastName = $googleUser['family_name'] ?? '';
        
        $stmt = db()->prepare('SELECT id, role, google_id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update google_id if it's empty
            if (empty($existing['google_id'])) {
                db()->prepare('UPDATE users SET google_id = ? WHERE id = ?')->execute([$googleId, $existing['id']]);
            }
            self::setSession((int)$existing['id'], $existing['role']);
            return ['success' => true, 'user_id' => $existing['id']];
        } else {
            // New user
            // We use a dummy password hash since they use Google
            $dummyHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
            
            db()->prepare('INSERT INTO users (first_name, last_name, email, password_hash, google_id, email_verified) VALUES (?,?,?,?,?,?)')
                ->execute([$firstName, $lastName, $email, $dummyHash, $googleId, 1]);
                
            $userId = (int)db()->lastInsertId();
            self::setSession($userId, 'customer');
            return ['success' => true, 'user_id' => $userId];
        }
    }

    /**
     * Helper to set user session
     */
    private static function setSession(int $userId, string $role): void {
        lume_session_start();
        $_SESSION['user_id'] = $userId;
        $_SESSION['role'] = $role;
        CartModel::mergeGuestCart($userId); // Centralized merge cart logic
    }
    
    /**
     * Logout
     */
    public static function logout(): void {
        lume_session_start();
        unset($_SESSION['user_id'], $_SESSION['role']);
        session_regenerate_id(true);
    }
}
