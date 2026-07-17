<?php
/**
 * Migration: Add size_chart and color_galleries columns to products table.
 * Run this once via browser, then delete it.
 */
require_once __DIR__ . '/includes/functions.php';
lume_session_start();

echo '<pre style="font-family:monospace;background:#111;color:#0f0;padding:24px">';
try {
    $pdo = db();
    
    $cols = array_column($pdo->query("SHOW COLUMNS FROM products")->fetchAll(), 'Field');
    
    if (!in_array('size_chart', $cols)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN size_chart VARCHAR(500) DEFAULT NULL");
        echo "✓ Added size_chart column\n";
    } else {
        echo "• size_chart already exists\n";
    }
    
    if (!in_array('color_galleries', $cols)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN color_galleries TEXT DEFAULT NULL");
        echo "✓ Added color_galleries column\n";
    } else {
        echo "• color_galleries already exists\n";
    }
    
    echo "\n✅ Migration complete! Delete this file now.\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo '</pre>';
