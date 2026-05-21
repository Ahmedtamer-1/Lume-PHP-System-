<?php
/**
 * LUMEEGY — Admin Products & Variants AJAX API
 * All AJAX calls from admin/products.php go here.
 */
require_once __DIR__ . '/../includes/functions.php';
lume_session_start();

header('Content-Type: application/json; charset=utf-8');

// Auth check — admin only
$user = current_user();
if (!$user || ($user['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── GET: fetch variants for a product ──
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_variants') {
    $productId = (int)($_GET['product_id'] ?? 0);
    if (!$productId) { echo json_encode(['success' => false]); exit; }

    $stmt = db()->prepare('SELECT * FROM product_variants WHERE product_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$productId]);
    $variants = $stmt->fetchAll();

    foreach ($variants as &$v) {
        $v['image_url'] = !empty($v['image']) ? SITE_URL . '/' . $v['image'] : null;
    }
    echo json_encode(['success' => true, 'variants' => $variants]);
    exit;
}

// ── GET: fetch single variant for editing ──
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_variant') {
    $id = (int)($_GET['id'] ?? 0);
    $productId = (int)($_GET['product_id'] ?? 0);
    $stmt = db()->prepare('SELECT * FROM product_variants WHERE id = ? AND product_id = ?');
    $stmt->execute([$id, $productId]);
    $v = $stmt->fetch();
    if ($v) {
        $v['image_url'] = !empty($v['image']) ? SITE_URL . '/' . $v['image'] : null;
    }
    echo json_encode(['success' => (bool)$v, 'variant' => $v ?: null]);
    exit;
}

// ── GET: fetch product images (main + gallery) ──
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_product_images') {
    $productId = (int)($_GET['product_id'] ?? 0);
    if (!$productId) { echo json_encode(['success' => false, 'images' => []]); exit; }

    $stmt = db()->prepare('SELECT image, gallery FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    $p = $stmt->fetch();

    $images = [];
    if ($p) {
        if (!empty($p['image'])) {
            $images[] = ['path' => $p['image'], 'url' => SITE_URL . '/' . $p['image']];
        }
        $gallery = json_decode($p['gallery'] ?? 'null', true) ?: [];
        foreach ($gallery as $g) {
            if ($g) $images[] = ['path' => $g, 'url' => SITE_URL . '/' . $g];
        }
    }
    echo json_encode(['success' => true, 'images' => $images]);
    exit;
}

// ── POST: save product (add/edit) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_product') {
    $id          = (int)($_POST['id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $slug        = trim($_POST['slug'] ?? '');
    $categoryId  = (int)($_POST['category_id'] ?? 0) ?: null;
    $description = trim($_POST['description'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $salePrice   = trim($_POST['sale_price'] ?? '') !== '' ? (float)$_POST['sale_price'] : null;
    $costPrice   = trim($_POST['cost_price'] ?? '') !== '' ? (float)$_POST['cost_price'] : null;
    $sku         = trim($_POST['sku'] ?? '');
    $stock       = (int)($_POST['stock'] ?? 0);
    $isFeatured  = isset($_POST['is_featured']) ? 1 : 0;
    $isActive    = isset($_POST['is_active']) ? 1 : 0;
    $hasVariants = isset($_POST['has_variants']) ? 1 : 0;
    $sizeChart   = trim($_POST['size_chart'] ?? '') ?: null;

    if (!$slug && $name) {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug = trim($slug, '-');
    }

    if (!$name) {
        echo json_encode(['success' => false, 'message' => 'Product name is required.']);
        exit;
    }
    if ($price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Price must be greater than zero.']);
        exit;
    }

    // Main image — from media library
    $imagePath = $_POST['existing_image'] ?? '';
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $result = media_upload($_FILES['image'], (int)$user['id']);
        if ($result) {
            $imagePath = $result['filepath'];
        }
    }

    // Gallery — media paths sent as JSON array
    $galleryPaths = [];
    $galleryJson  = $_POST['gallery_paths'] ?? '[]';
    $galleryPaths = json_decode($galleryJson, true) ?: [];

    // Also handle new gallery file uploads
    if (!empty($_FILES['gallery_images']['name'][0])) {
        foreach ($_FILES['gallery_images']['tmp_name'] as $gi => $gtmp) {
            if (empty($gtmp) || $_FILES['gallery_images']['error'][$gi] !== UPLOAD_ERR_OK) continue;
            $gfile = [
                'name'     => $_FILES['gallery_images']['name'][$gi],
                'type'     => $_FILES['gallery_images']['type'][$gi],
                'tmp_name' => $gtmp,
                'error'    => $_FILES['gallery_images']['error'][$gi],
                'size'     => $_FILES['gallery_images']['size'][$gi],
            ];
            $gresult = media_upload($gfile, (int)$user['id']);
            if ($gresult) {
                $galleryPaths[] = $gresult['filepath'];
            }
        }
    }
    $galleryEncoded = json_encode(array_values(array_unique($galleryPaths))) ?: '[]';

    try {
        if ($id > 0) {
            db()->prepare(
                'UPDATE products SET category_id=?, name=?, slug=?, description=?, price=?, sale_price=?, cost_price=?,
                 sku=?, stock=?, is_featured=?, is_active=?, has_variants=?, image=?, gallery=?, size_chart=? WHERE id=?'
            )->execute([$categoryId, $name, $slug, $description, $price, $salePrice, $costPrice,
                        $sku, $stock, $isFeatured, $isActive, $hasVariants, $imagePath, $galleryEncoded, $sizeChart, $id]);
            log_activity('update_product', 'product', $id);
            echo json_encode(['success' => true, 'message' => 'Product updated!', 'product_id' => $id]);
        } else {
            db()->prepare(
                'INSERT INTO products (category_id, name, slug, description, price, sale_price, cost_price, sku, stock, is_featured, is_active, has_variants, image, gallery, size_chart)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([$categoryId, $name, $slug, $description, $price, $salePrice, $costPrice,
                        $sku, $stock, $isFeatured, $isActive, $hasVariants, $imagePath, $galleryEncoded, $sizeChart]);
            $newId = (int)db()->lastInsertId();
            log_activity('create_product', 'product', $newId);
            echo json_encode(['success' => true, 'message' => 'Product added!', 'product_id' => $newId]);
        }
    } catch (PDOException $e) {
        $msg = str_contains($e->getMessage(), 'Duplicate') ? 'A product with this slug already exists.' : 'Database error.';
        echo json_encode(['success' => false, 'message' => $msg]);
    }
    exit;
}

// ── POST: delete product ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_product') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false]); exit; }
    db()->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
    log_activity('delete_product', 'product', $id);
    echo json_encode(['success' => true]);
    exit;
}

// ── POST: save variant ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_variant') {
    $vid        = (int)($_POST['variant_id'] ?? 0);
    $productId  = (int)($_POST['product_id'] ?? 0);
    $size       = trim($_POST['size'] ?? '') ?: null;
    $colorName  = trim($_POST['color_name'] ?? '') ?: null;
    $colorHex   = trim($_POST['color_hex'] ?? '') ?: null;
    $sku        = trim($_POST['sku'] ?? '') ?: null;
    $priceOver  = trim($_POST['price_override'] ?? '');
    $priceOverV = $priceOver !== '' ? (float)$priceOver : null;
    $costPrice  = trim($_POST['cost_price'] ?? '');
    $costPriceV = $costPrice !== '' ? (float)$costPrice : null;
    $stock      = (int)($_POST['stock'] ?? 0);
    $sortOrder  = (int)($_POST['sort_order'] ?? 0);
    $isActive   = isset($_POST['is_active']) ? 1 : 0;

    if (!$productId) { echo json_encode(['success' => false, 'message' => 'No product ID.']); exit; }
    if (!$size && !$colorName) {
        echo json_encode(['success' => false, 'message' => 'Size or color is required.']);
        exit;
    }

    // Image handling: media library path OR file upload
    $imagePath = null;
    if ($vid > 0) {
        $st = db()->prepare('SELECT image FROM product_variants WHERE id=? AND product_id=?');
        $st->execute([$vid, $productId]);
        $imagePath = $st->fetchColumn() ?: null;
    }

    // If a media library path was sent
    if (isset($_POST['media_image_path'])) {
        $imagePath = $_POST['media_image_path'] !== '' ? $_POST['media_image_path'] : null;
    }

    // If a new file was uploaded
    if (!empty($_FILES['image_file']['tmp_name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $result = media_upload($_FILES['image_file'], (int)$user['id']);
        if ($result) $imagePath = $result['filepath'];
    }

    try {
        if ($vid > 0) {
            db()->prepare(
                'UPDATE product_variants SET size=?, color_name=?, color_hex=?, sku=?, price_override=?, cost_price=?, stock=?, sort_order=?, is_active=?, image=? WHERE id=? AND product_id=?'
            )->execute([$size, $colorName, $colorHex, $sku, $priceOverV, $costPriceV, $stock, $sortOrder, $isActive, $imagePath, $vid, $productId]);
            log_activity('update_variant', 'variant', $vid);
            echo json_encode(['success' => true, 'message' => 'Variant updated.', 'variant_id' => $vid]);
        } else {
            db()->prepare(
                'INSERT INTO product_variants (product_id, size, color_name, color_hex, sku, price_override, cost_price, stock, sort_order, is_active, image) VALUES (?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([$productId, $size, $colorName, $colorHex, $sku, $priceOverV, $costPriceV, $stock, $sortOrder, $isActive, $imagePath]);
            $newVid = (int)db()->lastInsertId();
            log_activity('create_variant', 'variant', $newVid);
            echo json_encode(['success' => true, 'message' => 'Variant added.', 'variant_id' => $newVid]);
        }
    } catch (PDOException $e) {
        $msg = str_contains($e->getMessage(), 'Duplicate') ? 'This size/color combination already exists.' : 'Database error.';
        echo json_encode(['success' => false, 'message' => $msg]);
    }
    exit;
}

// ── POST: delete variant ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_variant') {
    $id        = (int)($_POST['id'] ?? 0);
    $productId = (int)($_POST['product_id'] ?? 0);
    db()->prepare('DELETE FROM product_variants WHERE id = ? AND product_id = ?')->execute([$id, $productId]);
    log_activity('delete_variant', 'variant', $id);
    echo json_encode(['success' => true]);
    exit;
}

// ── Helper: ensure color_galleries column exists ──
function ensure_color_galleries_column() {
    static $checked = false;
    if ($checked) return;
    $checked = true;
    try {
        $cols = array_column(db()->query('SHOW COLUMNS FROM products')->fetchAll(), 'Field');
        if (!in_array('color_galleries', $cols)) {
            db()->exec('ALTER TABLE products ADD COLUMN color_galleries TEXT DEFAULT NULL');
        }
        if (!in_array('size_chart', $cols) && !in_array('size_chart', array_column(db()->query('SHOW COLUMNS FROM products')->fetchAll(), 'Field'))) {
            db()->exec('ALTER TABLE products ADD COLUMN size_chart VARCHAR(500) DEFAULT NULL');
        }
    } catch (Exception $e) { /* silently skip */ }
}

// ── POST: save color galleries ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_color_galleries') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $galleries = $_POST['color_galleries'] ?? '{}';
    if (!$productId) { echo json_encode(['success' => false]); exit; }
    try {
        ensure_color_galleries_column();
        db()->prepare('UPDATE products SET color_galleries = ? WHERE id = ?')->execute([$galleries, $productId]);
        echo json_encode(['success' => true, 'message' => 'Color galleries saved.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
    }
    exit;
}

// ── GET: get color galleries ──
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_color_galleries') {
    $productId = (int)($_GET['product_id'] ?? 0);
    if (!$productId) { echo json_encode(['success' => false]); exit; }
    try {
        ensure_color_galleries_column();
        $stmt = db()->prepare('SELECT color_galleries FROM products WHERE id = ?');
        $stmt->execute([$productId]);
        $val = $stmt->fetchColumn();
        $galleries = json_decode($val ?: '{}', true) ?: [];
        echo json_encode(['success' => true, 'color_galleries' => $galleries]);
    } catch (Exception $e) {
        echo json_encode(['success' => true, 'color_galleries' => []]);
    }
    exit;
}

// ── GET: get variants for a product ──
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_variants') {
    $productId = (int)($_GET['product_id'] ?? 0);
    if (!$productId) { echo json_encode(['success' => false]); exit; }
    $stmt = db()->prepare('SELECT * FROM product_variants WHERE product_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$productId]);
    $variants = $stmt->fetchAll();
    foreach ($variants as &$v) {
        $v['image_url'] = !empty($v['image']) ? SITE_URL . '/' . $v['image'] : null;
    }
    echo json_encode(['success' => true, 'variants' => $variants]);
    exit;
}

// ── GET: get single variant ──
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_variant') {
    $id = (int)($_GET['id'] ?? 0);
    $productId = (int)($_GET['product_id'] ?? 0);
    $stmt = db()->prepare('SELECT * FROM product_variants WHERE id = ? AND product_id = ?');
    $stmt->execute([$id, $productId]);
    $v = $stmt->fetch();
    if ($v) {
        $v['image_url'] = !empty($v['image']) ? SITE_URL . '/' . $v['image'] : null;
    }
    echo json_encode(['success' => (bool)$v, 'variant' => $v ?: null]);
    exit;
}

// ── GET: list all products ──
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list_products') {
    $products = db()->query(
        'SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.created_at DESC'
    )->fetchAll();
    foreach ($products as &$p) {
        $p['image_url'] = !empty($p['image']) ? SITE_URL . '/' . $p['image'] : null;
    }
    echo json_encode(['success' => true, 'products' => $products]);
    exit;
}

// ── GET: fetch single product ──
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_product') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    if ($p) {
        $p['gallery_arr'] = json_decode($p['gallery'] ?? 'null', true) ?: [];
        $p['image_url']   = !empty($p['image']) ? SITE_URL . '/' . $p['image'] : null;
        $p['size_chart_url'] = !empty($p['size_chart']) ? SITE_URL . '/' . $p['size_chart'] : null;
    }
    echo json_encode(['success' => (bool)$p, 'product' => $p ?: null]);
    exit;
}

// ── POST: bulk action ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'bulk_action') {
    $bulkAction = $_POST['bulk_action_type'] ?? '';
    $ids = json_decode($_POST['bulk_ids'] ?? '[]', true);
    if (!is_array($ids) || empty($ids)) {
        echo json_encode(['success' => false, 'message' => 'No products selected']);
        exit;
    }
    $cleanIds = array_values(array_map('intval', $ids));
    $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
    
    if ($bulkAction === 'delete') {
        db()->prepare("DELETE FROM product_variants WHERE product_id IN ($placeholders)")->execute($cleanIds);
        db()->prepare("DELETE FROM products WHERE id IN ($placeholders)")->execute($cleanIds);
        echo json_encode(['success' => true]);
        exit;
    } elseif ($bulkAction === 'mark_active') {
        db()->prepare("UPDATE products SET is_active = 1 WHERE id IN ($placeholders)")->execute($cleanIds);
        echo json_encode(['success' => true]);
        exit;
    } elseif ($bulkAction === 'mark_inactive') {
        db()->prepare("UPDATE products SET is_active = 0 WHERE id IN ($placeholders)")->execute($cleanIds);
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
exit;
