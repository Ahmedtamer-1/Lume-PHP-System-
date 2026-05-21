<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug Shop.php</h1>";
echo "<hr>";

// Step 1: Test config loading
echo "<h2>Step 1: Config</h2>";
try {
    require_once __DIR__ . '/config/config.php';
    echo "<p style='color:green'>✅ Config loaded. ENV=" . ENV . ", SITE_URL=" . SITE_URL . "</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>❌ Config error: " . htmlspecialchars($e->getMessage()) . " in " . $e->getFile() . ":" . $e->getLine() . "</p>";
    exit;
}

// Step 2: Test database connection
echo "<h2>Step 2: Database</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    $pdo = db();
    echo "<p style='color:green'>✅ Database connected</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>❌ DB error: " . htmlspecialchars($e->getMessage()) . " in " . $e->getFile() . ":" . $e->getLine() . "</p>";
    exit;
}

// Step 3: Test functions loading
echo "<h2>Step 3: Functions</h2>";
try {
    require_once __DIR__ . '/includes/functions.php';
    echo "<p style='color:green'>✅ Functions loaded</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>❌ Functions error: " . htmlspecialchars($e->getMessage()) . " in " . $e->getFile() . ":" . $e->getLine() . "</p>";
    exit;
}

// Step 4: Test get_categories
echo "<h2>Step 4: get_categories()</h2>";
try {
    $categories = get_categories();
    echo "<p style='color:green'>✅ Categories: " . count($categories) . " found</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>❌ Categories error: " . htmlspecialchars($e->getMessage()) . " in " . $e->getFile() . ":" . $e->getLine() . "</p>";
}

// Step 5: Test get_products
echo "<h2>Step 5: get_products()</h2>";
try {
    $products = get_products(['limit' => 24]);
    echo "<p style='color:green'>✅ Products: " . count($products) . " found</p>";
    if (!empty($products)) {
        $cols = array_keys($products[0]);
        echo "<p>Columns: " . htmlspecialchars(implode(', ', $cols)) . "</p>";
    }
} catch (Throwable $e) {
    echo "<p style='color:red'>❌ Products error: " . htmlspecialchars($e->getMessage()) . " in " . $e->getFile() . ":" . $e->getLine() . "</p>";
}

// Step 6: Test product color swatches
echo "<h2>Step 6: get_product_color_swatches()</h2>";
try {
    $productIds = array_column($products ?? [], 'id');
    if (!empty($productIds)) {
        $productColors = get_product_color_swatches($productIds);
        echo "<p style='color:green'>✅ Color swatches loaded for " . count($productColors) . " products</p>";
    } else {
        echo "<p style='color:orange'>⚠️ No product IDs to test</p>";
    }
} catch (Throwable $e) {
    echo "<p style='color:red'>❌ Swatches error: " . htmlspecialchars($e->getMessage()) . " in " . $e->getFile() . ":" . $e->getLine() . "</p>";
}

// Step 7: Test has_variants query
echo "<h2>Step 7: has_variants query</h2>";
try {
    if (!empty($productIds)) {
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = db()->prepare("SELECT id, has_variants FROM products WHERE id IN ($placeholders)");
        $stmt->execute($productIds);
        $rows = $stmt->fetchAll();
        echo "<p style='color:green'>✅ has_variants query OK, " . count($rows) . " rows</p>";
    } else {
        echo "<p style='color:orange'>⚠️ No products to test</p>";
    }
} catch (Throwable $e) {
    echo "<p style='color:red'>❌ has_variants error: " . htmlspecialchars($e->getMessage()) . " in " . $e->getFile() . ":" . $e->getLine() . "</p>";
}

// Step 8: Test settings / currency_symbol
echo "<h2>Step 8: Settings & Currency</h2>";
try {
    $cs = currency_symbol();
    echo "<p style='color:green'>✅ currency_symbol() = '" . htmlspecialchars($cs) . "'</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>❌ Currency error: " . htmlspecialchars($e->getMessage()) . " in " . $e->getFile() . ":" . $e->getLine() . "</p>";
}

// Step 9: Test header include
echo "<h2>Step 9: Header (the big test)</h2>";
try {
    // Simulate what shop.php does
    $pageTitle = 'Shop — LUMEEGY';
    $pageDescription = 'Browse our full collection.';
    ob_start();
    require __DIR__ . '/includes/header.php';
    $headerHtml = ob_get_clean();
    echo "<p style='color:green'>✅ Header rendered OK (" . strlen($headerHtml) . " bytes)</p>";
} catch (Throwable $e) {
    ob_end_clean();
    echo "<p style='color:red'>❌ Header error: " . htmlspecialchars($e->getMessage()) . " in " . $e->getFile() . ":" . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr><p><strong>Debug complete.</strong></p>";
