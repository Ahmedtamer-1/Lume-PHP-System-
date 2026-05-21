<?php
/**
 * LUMEEGY — Core Functions
 */
require_once __DIR__ . '/../config/database.php';

// ════════════════════════════════════════════════════════
// SESSION
// ════════════════════════════════════════════════════════
function lume_session_start(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => ENV === 'production',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function csrf_token(): string
{
    lume_session_start();
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function csrf_verify(string $token): bool
{
    return hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token);
}

// ════════════════════════════════════════════════════════
// SECURITY — Rate Limiting & SHA-256
// ════════════════════════════════════════════════════════
/**
 * Rate-limit login attempts. Returns true if the request should be blocked.
 */
function is_rate_limited(string $identifier, int $maxAttempts = 5, int $windowSeconds = 900): bool
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM login_attempts WHERE identifier = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)'
    );
    $stmt->execute([$identifier, $windowSeconds]);
    return (int) $stmt->fetchColumn() >= $maxAttempts;
}

function record_login_attempt(string $identifier, bool $success = false): void
{
    db()->prepare(
        'INSERT INTO login_attempts (identifier, ip_address, success, attempted_at) VALUES (?, ?, ?, NOW())'
    )->execute([$identifier, get_client_ip(), $success ? 1 : 0]);
}

function clear_login_attempts(string $identifier): void
{
    db()->prepare('DELETE FROM login_attempts WHERE identifier = ?')->execute([$identifier]);
}

/**
 * Generate a SHA-256 HMAC signature for data integrity.
 */
function hmac_sign(string $data): string
{
    $key = defined('GATEWAY_HMAC_SECRET') && GATEWAY_HMAC_SECRET
        ? GATEWAY_HMAC_SECRET
        : hash('sha256', DB_PASS . SESSION_NAME);
    return hash_hmac('sha256', $data, $key);
}

function hmac_verify(string $data, string $signature): bool
{
    return hash_equals(hmac_sign($data), $signature);
}

/**
 * Generate a secure, time-limited nonce (SHA-256 based).
 */
function generate_nonce(string $action, int $ttlHours = 24): string
{
    $tick = ceil(time() / (3600 * $ttlHours));
    return hash_hmac('sha256', $action . '|' . $tick, csrf_token());
}

function verify_nonce(string $nonce, string $action, int $ttlHours = 24): bool
{
    $tick = ceil(time() / (3600 * $ttlHours));
    $expected = hash_hmac('sha256', $action . '|' . $tick, csrf_token());
    if (hash_equals($expected, $nonce))
        return true;
    // Check previous window
    $prev = hash_hmac('sha256', $action . '|' . ($tick - 1), csrf_token());
    return hash_equals($prev, $nonce);
}

// ════════════════════════════════════════════════════════
// AUTH
// ════════════════════════════════════════════════════════
function current_user(): ?array
{
    static $user = null;
    if ($user !== null) return $user;

    lume_session_start();
    if (empty($_SESSION['user_id']))
        return null;
    
    $stmt = db()->prepare('SELECT id, first_name, last_name, email, role FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;
    return $user;
}

function is_logged_in(): bool
{
    lume_session_start();
    return !empty($_SESSION['user_id']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('/account.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
}

function login_user(int $userId): void
{
    lume_session_start();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    // Merge guest cart to user cart
    if (!empty($_SESSION['cart_session_key'])) {
        db()->prepare(
            'UPDATE cart_sessions SET user_id = ? WHERE session_key = ? AND user_id IS NULL'
        )->execute([$userId, $_SESSION['cart_session_key']]);
    }
}

function logout_user(): void
{
    lume_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function log_activity(string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void
{
    $user = current_user();
    $userId = $user ? (int)$user['id'] : null;
    $ip = get_client_ip();

    db()->prepare(
        'INSERT INTO activity_log (user_id, action, entity_type, entity_id, details, ip_address, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW())'
    )->execute([$userId, $action, $entityType, $entityId, $details, $ip]);
}

// ════════════════════════════════════════════════════════
// VARIANTS
// ════════════════════════════════════════════════════════
function get_product_variants(int $productId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM product_variants WHERE product_id = ? AND is_active = 1 ORDER BY sort_order ASC, id ASC'
    );
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

function get_variant(int $variantId): ?array
{
    $stmt = db()->prepare('SELECT * FROM product_variants WHERE id = ?');
    $stmt->execute([$variantId]);
    return $stmt->fetch() ?: null;
}

/**
 * Batch-fetch unique color swatches for multiple products.
 */
function get_product_color_swatches(array $productIds): array
{
    if (empty($productIds)) return [];
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stmt = db()->prepare(
        "SELECT product_id, color_name, color_hex, image 
         FROM product_variants 
         WHERE product_id IN ($placeholders) AND is_active = 1 AND color_name IS NOT NULL AND color_name != ''
         ORDER BY sort_order ASC"
    );
    $stmt->execute($productIds);
    $result = [];
    foreach ($stmt->fetchAll() as $row) {
        if (!isset($result[(int)$row['product_id']][$row['color_name']])) {
            $result[(int)$row['product_id']][$row['color_name']] = [
                'hex' => $row['color_hex'],
                'image' => $row['image'] ?? null,
            ];
        }
    }
    return $result;
}

/**
 * Compute the effective price for a cart/order item that may have a variant.
 */
function item_effective_price(array $item): float
{
    if (isset($item['variant_price']) && $item['variant_price'] !== null)
        return (float) $item['variant_price'];
    if (!empty($item['sale_price']))
        return (float) $item['sale_price'];
    return (float) $item['price'];
}

// ════════════════════════════════════════════════════════
// CART
// ════════════════════════════════════════════════════════
function cart_session_key(): string
{
    lume_session_start();
    if (empty($_SESSION['cart_session_key'])) {
        $_SESSION['cart_session_key'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['cart_session_key'];
}

function cart_items(): array
{
    $key = cart_session_key();
    $userId = is_logged_in() ? $_SESSION['user_id'] : null;

    $cols = 'cs.*, p.name, p.price, p.sale_price, p.cost_price, p.image, p.slug, p.stock, p.sku, p.has_variants,
             pv.id AS pv_id, pv.size AS variant_size, pv.color_name AS variant_color,
             pv.color_hex AS variant_color_hex, pv.sku AS variant_sku,
             pv.price_override AS variant_price, pv.cost_price AS variant_cost_price, pv.stock AS variant_stock,
             pv.image AS variant_image';

    if ($userId) {
        $stmt = db()->prepare(
            "SELECT $cols
             FROM cart_sessions cs
             JOIN products p ON p.id = cs.product_id
             LEFT JOIN product_variants pv ON pv.id = cs.variant_id
             WHERE cs.user_id = ? AND p.is_active = 1"
        );
        $stmt->execute([$userId]);
    } else {
        $stmt = db()->prepare(
            "SELECT $cols
             FROM cart_sessions cs
             JOIN products p ON p.id = cs.product_id
             LEFT JOIN product_variants pv ON pv.id = cs.variant_id
             WHERE cs.session_key = ? AND cs.user_id IS NULL AND p.is_active = 1"
        );
        $stmt->execute([$key]);
    }
    return $stmt->fetchAll();
}

function cart_count(): int
{
    return (int) array_sum(array_column(cart_items(), 'quantity'));
}

function cart_total(): float
{
    $total = 0.0;
    foreach (cart_items() as $item) {
        $total += item_effective_price($item) * (int) $item['quantity'];
    }
    return $total;
}

function cart_add(int $productId, int $qty = 1, ?int $variantId = null): bool
{
    $key = cart_session_key();
    $userId = is_logged_in() ? (int) $_SESSION['user_id'] : null;

    // Check existing (same product + same variant)
    if ($userId) {
        if ($variantId) {
            $stmt = db()->prepare('SELECT id, quantity FROM cart_sessions WHERE user_id = ? AND product_id = ? AND variant_id = ?');
            $stmt->execute([$userId, $productId, $variantId]);
        } else {
            $stmt = db()->prepare('SELECT id, quantity FROM cart_sessions WHERE user_id = ? AND product_id = ? AND variant_id IS NULL');
            $stmt->execute([$userId, $productId]);
        }
    } else {
        if ($variantId) {
            $stmt = db()->prepare('SELECT id, quantity FROM cart_sessions WHERE session_key = ? AND user_id IS NULL AND product_id = ? AND variant_id = ?');
            $stmt->execute([$key, $productId, $variantId]);
        } else {
            $stmt = db()->prepare('SELECT id, quantity FROM cart_sessions WHERE session_key = ? AND user_id IS NULL AND product_id = ? AND variant_id IS NULL');
            $stmt->execute([$key, $productId]);
        }
    }
    $existing = $stmt->fetch();

    if ($existing) {
        db()->prepare('UPDATE cart_sessions SET quantity = quantity + ? WHERE id = ?')
            ->execute([$qty, $existing['id']]);
    } else {
        db()->prepare('INSERT INTO cart_sessions (session_key, user_id, product_id, variant_id, quantity) VALUES (?,?,?,?,?)')
            ->execute([$key, $userId, $productId, $variantId, $qty]);
    }
    return true;
}

function cart_update(int $cartId, int $qty): void
{
    if ($qty <= 0) {
        db()->prepare('DELETE FROM cart_sessions WHERE id = ?')->execute([$cartId]);
    } else {
        db()->prepare('UPDATE cart_sessions SET quantity = ? WHERE id = ?')->execute([$qty, $cartId]);
    }
}

function cart_clear(): void
{
    $key = cart_session_key();
    $userId = is_logged_in() ? (int) $_SESSION['user_id'] : null;
    if ($userId) {
        db()->prepare('DELETE FROM cart_sessions WHERE user_id = ?')->execute([$userId]);
    } else {
        db()->prepare('DELETE FROM cart_sessions WHERE session_key = ? AND user_id IS NULL')->execute([$key]);
    }
}

// ════════════════════════════════════════════════════════
// PRODUCTS
// ════════════════════════════════════════════════════════
function get_featured_products(int $limit = 4): array
{
    $stmt = db()->prepare(
        'SELECT * FROM products WHERE is_featured = 1 AND is_active = 1 ORDER BY created_at DESC LIMIT ?'
    );
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function get_products(array $opts = []): array
{
    $where = ['p.is_active = 1'];
    $params = [];
    $limit = (int) ($opts['limit'] ?? 12);
    $offset = (int) ($opts['offset'] ?? 0);

    if (!empty($opts['category_slug'])) {
        $where[] = 'c.slug = ?';
        $params[] = $opts['category_slug'];
    }
    if (!empty($opts['search'])) {
        $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
        $term = '%' . $opts['search'] . '%';
        $params[] = $term;
        $params[] = $term;
    }

    $sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?';
    $params[] = $limit;
    $params[] = $offset;

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_product_by_slug(string $slug): ?array
{
    $stmt = db()->prepare(
        'SELECT p.*, c.name AS category_name, c.slug AS category_slug
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.slug = ? AND p.is_active = 1'
    );
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

function get_categories(): array
{
    $stmt = db()->query('SELECT * FROM categories ORDER BY sort_order ASC, name ASC');
    return $stmt->fetchAll();
}

// ════════════════════════════════════════════════════════
// FORMATTING
// ════════════════════════════════════════════════════════
function currency_symbol(): string
{
    $symbol = setting('currency_symbol', '');
    if (!$symbol || !is_string($symbol) || strlen($symbol) > 10 || preg_match('/^\d+$/', trim($symbol))) {
        $symbol = defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : 'EGP ';
    }
    if (!is_string($symbol) || strlen($symbol) > 10 || preg_match('/^\d+$/', trim($symbol))) {
        $symbol = 'EGP ';
    }
    return $symbol;
}

function money(float $amount): string
{
    return currency_symbol() . number_format($amount, 2);
}

function product_price(array $product): string
{
    if (!empty($product['sale_price'])) {
        return '<span class="price-sale">' . money((float) $product['sale_price']) . '</span>'
            . '<span class="price-original">' . money((float) $product['price']) . '</span>';
    }
    return '<span class="price">' . money((float) $product['price']) . '</span>';
}

function asset_url(?string $path): string
{
    if (empty($path)) return SITE_URL . '/assets/images/placeholder.jpg';
    if (strpos($path, 'http') === 0) return $path;
    return SITE_URL . '/' . ltrim($path, '/');
}

function product_image(array $product): string
{
    $img = !empty($product['image']) ? h($product['image']) : '';
    return asset_url($img);
}

// ════════════════════════════════════════════════════════
// ORDERS
// ════════════════════════════════════════════════════════
function generate_order_number(): string
{
    return 'LME-' . strtoupper(substr(uniqid(), -6)) . '-' . date('ymd');
}

function create_order(array $data): int
{
    $pdo = db();
    $number = generate_order_number();

    $pdo->beginTransaction();
    try {
        $pdo->prepare(
            'INSERT INTO orders
             (order_number, user_id, guest_email, subtotal, shipping_cost, discount, tax, total,
              currency, payment_method, shipping_name, shipping_addr, shipping_city, shipping_country, notes, phone, shipping_zone)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([
                    $number,
                    $data['user_id'] ?? null,
                    $data['guest_email'] ?? null,
                    $data['subtotal'],
                    $data['shipping_cost'],
                    $data['discount'] ?? 0,
                    $data['tax'] ?? 0,
                    $data['total'],
                    CURRENCY_CODE,
                    $data['payment_method'],
                    $data['shipping_name'],
                    $data['shipping_addr'],
                    $data['shipping_city'],
                    $data['shipping_country'],
                    $data['notes'] ?? null,
                    $data['phone'] ?? null,
                    $data['shipping_zone'] ?? null,
                ]);

        $orderId = (int) $pdo->lastInsertId();

        foreach ($data['items'] as $item) {
            $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, variant_id, variant_size, variant_color, name, sku, price, cost_price, quantity, subtotal)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                        $orderId,
                        $item['product_id'] ?? null,
                        $item['variant_id'] ?? null,
                        $item['variant_size'] ?? null,
                        $item['variant_color'] ?? null,
                        $item['name'],
                        $item['sku'] ?? null,
                        $item['price'],
                        $item['cost_price'] ?? null,
                        $item['quantity'],
                        $item['price'] * $item['quantity'],
                    ]);

            // Deduct stock
            if (!empty($item['variant_id'])) {
                $pdo->prepare('UPDATE product_variants SET stock = GREATEST(0, stock - ?) WHERE id = ?')
                    ->execute([$item['quantity'], $item['variant_id']]);
            }
            if (!empty($item['product_id'])) {
                $pdo->prepare('UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?')
                    ->execute([$item['quantity'], $item['product_id']]);
            }
        }

        $pdo->commit();
        return $orderId;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// ════════════════════════════════════════════════════════
// MEDIA LIBRARY
// ════════════════════════════════════════════════════════
function media_upload(array $file, ?int $userId = null): ?array
{
    if ($file['error'] !== UPLOAD_ERR_OK)
        return null;

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/svg+xml' => 'svg'
    ];

    if (!array_key_exists($mime, $allowedMimes))
        return null;

    $ext = $allowedMimes[$mime];
    $newName = 'media_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = UPLOADS_PATH . '/' . $newName;
    $filepath = 'assets/images/products/' . $newName; // Keep relative for DB storage

    // Ensure uploads directory exists
    if (!is_dir(UPLOADS_PATH))
        mkdir(UPLOADS_PATH, 0755, true);

    if (!move_uploaded_file($file['tmp_name'], $dest))
        return null;
    $size = filesize($dest);

    // Get dimensions for images
    $width = null;
    $height = null;
    $imgInfo = @getimagesize($dest);
    if ($imgInfo) {
        $width = $imgInfo[0];
        $height = $imgInfo[1];
    }

    db()->prepare(
        'INSERT INTO media_library (filename, filepath, filetype, filesize, width, height, uploaded_by)
         VALUES (?,?,?,?,?,?,?)'
    )->execute([$file['name'], $filepath, 'image', $size, $width, $height, $userId]);

    $id = (int) db()->lastInsertId();
    return [
        'id' => $id,
        'filename' => $file['name'],
        'filepath' => $filepath,
        'url' => SITE_URL . '/' . $filepath,
        'width' => $width,
        'height' => $height,
    ];
}

function media_list(int $limit = 50, int $offset = 0): array
{
    $stmt = db()->prepare(
        'SELECT * FROM media_library ORDER BY created_at DESC LIMIT ? OFFSET ?'
    );
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll();
}

function media_delete(int $id): bool
{
    $stmt = db()->prepare('SELECT filepath FROM media_library WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row)
        return false;

    $path = ROOT_PATH . '/' . $row['filepath'];
    if (file_exists($path))
        @unlink($path);

    db()->prepare('DELETE FROM media_library WHERE id = ?')->execute([$id]);
    return true;
}

function media_count(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM media_library')->fetchColumn();
}

// ════════════════════════════════════════════════════════
// HOMEPAGE SECTIONS
// ════════════════════════════════════════════════════════
function get_homepage_sections(bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM homepage_sections';
    if ($activeOnly)
        $sql .= ' WHERE is_active = 1';
    $sql .= ' ORDER BY sort_order ASC, id ASC';
    return db()->query($sql)->fetchAll();
}

function get_section(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM homepage_sections WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

// ════════════════════════════════════════════════════════
// UTILITIES
// ════════════════════════════════════════════════════════
function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    $base = rtrim(SITE_URL, '/');
    $path = ltrim($path, '/');
    header('Location: ' . $base . '/' . $path);
    exit;
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function is_ajax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function get_client_ip(): string
{
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        return filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP) ?: 'unknown';
    }
    return 'unknown';
}

function setting(string $key, string $default = ''): string
{
    static $cache = [];
    if (!isset($cache[$key])) {
        try {
            $stmt = db()->prepare('SELECT value FROM settings WHERE key_name = ?');
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            $cache[$key] = $val !== false ? $val : $default;
        } catch (\Exception $e) {
            $cache[$key] = $default;
        }
    }
    return (string) $cache[$key];
}

/**
 * Convert hex color (#RRGGBB) to "r, g, b" string for use in rgba().
 */
function hexToRgb(string $hex): string
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (strlen($hex) !== 6)
        return '10, 10, 10';
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return "$r, $g, $b";
}

// Note: currency_symbol() is defined above (line ~393).