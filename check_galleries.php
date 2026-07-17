<?php
require_once __DIR__ . '/includes/functions.php';

$stmt = db()->prepare('SELECT id, color_galleries FROM products WHERE color_galleries IS NOT NULL AND color_galleries != "" AND color_galleries != "{}"');
$stmt->execute();
$rows = $stmt->fetchAll();

foreach ($rows as $row) {
    echo "ID: " . $row['id'] . "\n";
    echo "Raw: " . $row['color_galleries'] . "\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
    print_r(json_decode($row['color_galleries'], true));
    echo "\n----------------------\n";
}
echo "Done.\n";
